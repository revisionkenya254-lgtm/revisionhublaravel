<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

class AiDocumentQuestionExtractionService
{
    public function extract(array|string $input, bool $allowHeuristicFallback = true): array
    {
        $text = $this->normalizeText(is_array($input) ? $this->textFromPages($input) : $input);
        if ($text === '') {
            return [
                'questions' => [],
                'question_count' => 0,
                'section_count' => 0,
                'method' => 'empty',
            ];
        }

        $openAiResult = $this->extractWithOpenAi($text);
        if (! empty($openAiResult['questions'])) {
            return $openAiResult;
        }

        if (! $allowHeuristicFallback) {
            return [
                'questions' => [],
                'question_count' => 0,
                'section_count' => 0,
                'method' => 'openai_failed',
            ];
        }

        return $this->extractHeuristically($text);
    }

    private function extractHeuristically(string $text): array
    {
        $lines = preg_split("/\R/u", $text) ?: [];
        $sections = [];
        $questions = [];
        $currentSection = null;
        $currentQuestion = null;
        $questionOrder = 0;

        $flushQuestion = function () use (&$questions, &$currentQuestion) {
            if ($currentQuestion === null) {
                return;
            }

            $currentQuestion['content'] = trim(preg_replace('/[ \t]+/', ' ', $currentQuestion['content']) ?? $currentQuestion['content']);
            $currentQuestion['raw_text'] = trim($currentQuestion['raw_text']);
            if ($currentQuestion['content'] === '' && $currentQuestion['raw_text'] === '') {
                $currentQuestion = null;
                return;
            }

            $questions[] = $currentQuestion;
            $currentQuestion = null;
        };

        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/^\*+(.*?)\*+$/', '$1', trim((string) $line)) ?? $line);
            $line = trim($line);

            if ($line === '') {
                if ($currentQuestion !== null) {
                    $currentQuestion['content'] .= "\n";
                    $currentQuestion['raw_text'] .= "\n";
                }
                continue;
            }

            if ($this->isSectionHeading($line, $sectionLabel)) {
                $flushQuestion();
                $currentSection = $sectionLabel;
                $sections[$currentSection] = ($sections[$currentSection] ?? 0) + 1;
                continue;
            }

            if ($this->isQuestionHeading($line, $questionNumber, $partLabel, $marksLabel, $remainingText)) {
                $flushQuestion();
                $questionOrder++;
                $currentQuestion = [
                    'sort_order' => $questionOrder,
                    'section_label' => $currentSection,
                    'question_number' => $questionNumber,
                    'question_label' => $partLabel
                        ? sprintf('Question %d(%s)', $questionNumber, $partLabel)
                        : sprintf('Question %d', $questionNumber),
                    'part_label' => $partLabel,
                    'marks' => $this->parseMarks($marksLabel),
                    'marks_label' => $marksLabel,
                    'content' => trim($remainingText),
                    'raw_text' => $line . "\n",
                    'metadata' => [
                        'source' => 'heuristic',
                    ],
                ];
                continue;
            }

            if ($currentQuestion !== null) {
                $currentQuestion['content'] .= ($currentQuestion['content'] === '' ? '' : "\n") . $line;
                $currentQuestion['raw_text'] .= $line . "\n";
                continue;
            }

            if ($this->looksLikeFallbackQuestionLine($line, $questionNumber, $partLabel, $marksLabel, $remainingText)) {
                $questionOrder++;
                $currentQuestion = [
                    'sort_order' => $questionOrder,
                    'section_label' => $currentSection,
                    'question_number' => $questionNumber,
                    'question_label' => $partLabel
                        ? sprintf('Question %d(%s)', $questionNumber, $partLabel)
                        : sprintf('Question %d', $questionNumber),
                    'part_label' => $partLabel,
                    'marks' => $this->parseMarks($marksLabel),
                    'marks_label' => $marksLabel,
                    'content' => trim($remainingText),
                    'raw_text' => $line . "\n",
                    'metadata' => [
                        'source' => 'heuristic-fallback',
                    ],
                ];
            }
        }

        $flushQuestion();

        return [
            'questions' => $questions,
            'question_count' => count($questions),
            'section_count' => count($sections),
            'sections' => array_keys($sections),
            'method' => 'heuristic',
        ];
    }

    private function extractWithOpenAi(string $text): array
    {
        $apiKey = trim((string) config('services.openai.api_key'));
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('services.openai.model', 'gpt-4o-mini');

        if ($apiKey === '' || $text === '') {
            return [];
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(90)
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You extract exam questions from OCR text. Extract each question separately and give it back question by question. Do not combine multiple questions into one item. Always return visible questions as separate entries such as Question 1, Question 2, Question 3, and so on. Return only valid JSON. Do not add commentary.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($text),
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                return [];
            }

            $payload = json_decode((string) data_get($response->json(), 'choices.0.message.content', ''), true);
            if (! is_array($payload)) {
                return [];
            }

            $questions = array_values(array_filter(array_map(function (array $question, int $index) {
                $content = trim((string) ($question['content'] ?? ''));
                $label = trim((string) ($question['question_label'] ?? ''));
                if ($content === '' || $label === '') {
                    return null;
                }

                return [
                    'sort_order' => (int) ($question['sort_order'] ?? ($index + 1)),
                    'section_label' => $question['section_label'] ?? null,
                    'question_number' => isset($question['question_number']) ? (int) $question['question_number'] : null,
                    'question_label' => $label,
                    'part_label' => $question['part_label'] ?? null,
                    'marks' => isset($question['marks']) ? (int) $question['marks'] : null,
                    'marks_label' => $question['marks_label'] ?? null,
                    'content' => $content,
                    'raw_text' => (string) ($question['raw_text'] ?? $content),
                    'metadata' => array_filter([
                        'source' => 'openai',
                    ]),
                ];
            }, $payload['questions'] ?? [], array_keys($payload['questions'] ?? []))));

            if (empty($questions)) {
                return [];
            }

            return [
                'questions' => $questions,
                'question_count' => count($questions),
                'section_count' => (int) ($payload['section_count'] ?? 0),
                'sections' => array_values(array_filter((array) ($payload['sections'] ?? []))),
                'method' => 'openai',
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildPrompt(string $text): string
    {
        return <<<PROMPT
Extract the exam questions from the OCR text below.
Extract each question separately and give it back question by question.
Do not combine multiple questions into one item.
Always return visible questions as separate entries such as Question 1, Question 2, Question 3, and so on.

Rules:
- Return JSON only.
- Keep one array item per question or sub-question.
- Include section headers when available, e.g. "SECTION A".
- For compound questions like 11(a) and 11(b), keep them in one item and preserve the parts inside the content.
- Preserve marks when visible.
- If a question number is unreadable, omit it.
- Do not invent questions.

Return this JSON shape:
{
  "questions": [
    {
      "sort_order": 1,
      "section_label": "SECTION A",
      "question_number": 1,
      "question_label": "Question 1",
      "part_label": null,
      "marks": 4,
      "marks_label": "(4 marks)",
      "content": "State three reasons...",
      "raw_text": "Original extracted block..."
    }
  ],
  "question_count": 1,
  "section_count": 1,
  "sections": ["SECTION A"]
}

OCR text:
{$text}
PROMPT;
    }

    private function textFromPages(array $pages): string
    {
        $parts = [];

        foreach ($pages as $page) {
            $text = trim((string) data_get($page, 'text', ''));
            if ($text === '') {
                continue;
            }

            $parts[] = $text;
        }

        return trim(implode("\n\n", $parts));
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/\r\n?/', "\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function isSectionHeading(string $line, ?string &$sectionLabel): bool
    {
        if (preg_match('/^SECTION\s+(?<label>[A-Z0-9]+)(?:\b.*)?$/i', $line, $matches)) {
            $sectionLabel = strtoupper(trim('SECTION ' . $matches['label']));
            return true;
        }

        return false;
    }

    private function isQuestionHeading(string $line, ?int &$questionNumber, ?string &$partLabel, ?string &$marksLabel, ?string &$remainingText): bool
    {
        $patterns = [
            '/^(?:Question\s*)?(?<number>\d{1,3})(?:\s*\((?<part>[a-z])\))?(?:[\.\)\:-]|\s+)?(?<rest>.*)$/i',
            '/^(?:Q\s*)?(?<number>\d{1,3})(?:\s*\((?<part>[a-z])\))?(?:[\.\)\:-]|\s+)?(?<rest>.*)$/i',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $line, $matches)) {
                continue;
            }

            $prefix = trim((string) ($matches['rest'] ?? ''));
            if ($prefix === '' && ! preg_match('/^\d{1,3}$/', trim((string) ($matches['number'] ?? '')))) {
                continue;
            }

            $questionNumber = (int) $matches['number'];
            $partLabel = isset($matches['part']) ? strtolower(trim((string) $matches['part'])) : null;
            $marksLabel = null;
            $remainingText = $this->stripMarks($prefix, $marksLabel);

            return true;
        }

        return false;
    }

    private function looksLikeFallbackQuestionLine(string $line, ?int &$questionNumber, ?string &$partLabel, ?string &$marksLabel, ?string &$remainingText): bool
    {
        if (! preg_match('/^(?:\*\*)?(?:Question\s*)?(?<number>\d{1,3})(?:\s*\((?<part>[a-z])\))?(?:[\.\)\:-]|\s+)(?<rest>.*)$/i', $line, $matches)) {
            return false;
        }

        $questionNumber = (int) $matches['number'];
        $partLabel = isset($matches['part']) ? strtolower(trim((string) $matches['part'])) : null;
        $marksLabel = null;
        $remainingText = $this->stripMarks(trim((string) ($matches['rest'] ?? '')), $marksLabel);

        return true;
    }

    private function stripMarks(string $text, ?string &$marksLabel = null): string
    {
        $marksLabel = null;

        if (preg_match('/\((?<label>[^)]*marks?[^)]*)\)\s*$/i', $text, $matches)) {
            $marksLabel = '(' . trim((string) $matches['label']) . ')';
            $text = trim(substr($text, 0, -strlen($matches[0])));
        }

        return trim($text);
    }

    private function parseMarks(?string $marksLabel): ?int
    {
        if (! $marksLabel) {
            return null;
        }

        if (preg_match('/(?<marks>\d+)/', $marksLabel, $matches)) {
            return (int) $matches['marks'];
        }

        return null;
    }
}
