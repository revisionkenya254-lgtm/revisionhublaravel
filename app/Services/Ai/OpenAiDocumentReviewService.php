<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAiDocumentReviewService
{
    public function review(string $path, ?string $originalName = null): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(__('The source file could not be found.'));
        }

        $extension = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));
        if (! $this->supports($extension)) {
            return [];
        }

        $apiKey = trim((string) config('services.openai_vision.api_key', config('services.openai.api_key')));
        $baseUrl = rtrim((string) config('services.openai_vision.base_url', config('services.openai.base_url', 'https://api.openai.com/v1')), '/');
        $model = (string) config('services.openai_vision.model', config('services.openai.model', 'gpt-4o-mini'));

        if ($apiKey === '') {
            throw new RuntimeException(__('OpenAI API key is not configured for direct file review.'));
        }

        $mimeType = $this->mimeTypeForExtension($extension);
        $binary = file_get_contents($path);

        if ($binary === false || $binary === '') {
            throw new RuntimeException(__('Unable to read the source file for OpenAI review.'));
        }

        $fileData = 'data:' . $mimeType . ';base64,' . base64_encode($binary);
        $inputFile = [
            'type' => 'input_file',
            'filename' => $originalName ?: basename($path),
            'file_data' => $fileData,
        ];

        if ($extension === 'pdf') {
            $inputFile['detail'] = 'high';
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(180)
            ->post($baseUrl . '/responses', [
                'model' => $model,
                'input' => [
                    [
                        'role' => 'system',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => implode("\n", [
                                    'You are reviewing an uploaded exam paper directly from the original file.',
                                    'Extract each question separately and give it back question by question.',
                                    'Do not combine multiple questions into one item.',
                                    'Always return visible questions as separate entries such as Question 1, Question 2, Question 3, and so on.',
                                    'Never merge the instruction line with the first question.',
                                    'If a question references a sub-part like 4(b) or 4b, keep that reference exactly in the question label and content.',
                                    'Keep clean searchable text for the full paper, but make the questions array one item per visible question or sub-question.',
                                    'Do not invent content. Preserve section labels, question numbers, parts, and marks when visible.',
                                    'Also extract the editable paper metadata for the Basic Details section, including title, education level, class or grade, subject, course, exam category, year, language, access type, preview pages, and a short description when visible, including the values that should populate the dropdown or select controls.',
                                    'Infer the subject or course from the paper title only when the title makes it clear.',
                                    'Leave unknown dropdown or select values null instead of guessing.',
                                    'If a metadata value is not visible, return null for that field.',
                                    'Return only valid JSON that matches the schema.',
                                ]),
                            ],
                        ],
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            $inputFile,
                            [
                                'type' => 'input_text',
                                'text' => implode("\n", [
                                    'Review this original file directly and extract the exam paper.',
                                    'Return a clean_text field suitable for searchable chunks, a short excerpt, and a structured questions array.',
                                    'Extract each question separately and give it back question by question.',
                                    'Do not combine multiple questions into one item.',
                                    'Always return visible questions as separate entries such as Question 1, Question 2, Question 3, and so on.',
                                    'Never merge the instruction line with the first question.',
                                    'If a question references a sub-part like 4(b) or 4b, keep that reference exactly in the question label and content.',
                                    'If the file is a PDF, use the visual and text content available in the file input.',
                                    'If the file is a Word document, read the file directly and extract the best clean text you can from it.',
                                    'Also extract the editable paper metadata for the Basic Details section, including title, education level, class or grade, subject, course, exam category, year, language, access type, preview pages, and a short description when visible, including the values that should populate the dropdown or select controls.',
                                    'Infer the subject or course from the paper title only when the title makes it clear.',
                                    'Leave unknown dropdown or select values null instead of guessing.',
                                    'If a metadata value is not visible, return null for that field.',
                                ]),
                            ],
                        ],
                    ],
                ],
                'max_output_tokens' => 6000,
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'exam_paper_direct_review',
                        'strict' => true,
                        'schema' => $this->schema(),
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $this->logFailedResponse('OpenAI direct file review failed', $response, [
                'path' => $path,
                'original_name' => $originalName,
                'extension' => $extension,
                'model' => $model,
            ]);

            throw new RuntimeException($response->json('error.message') ?: __('OpenAI direct file review failed.'));
        }

        $responseJson = $response->json();
        $outputText = trim((string) data_get($responseJson, 'output_text', ''));

        if ($outputText === '') {
            $outputText = $this->extractOutputText($responseJson);
        }

        $payload = json_decode($outputText, true);
        if (! is_array($payload)) {
            Log::error('OpenAI direct file review returned invalid JSON payload', [
                'path' => $path,
                'original_name' => $originalName,
                'extension' => $extension,
                'model' => $model,
                'output_text_preview' => $this->truncateText($outputText, 4000),
            ]);

            throw new RuntimeException($outputText !== ''
                ? __('OpenAI direct file review returned an invalid response.')
                : __('OpenAI direct file review returned no textual payload.'));
        }

        return $this->normalizeResult($payload, $extension);
    }

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), ['pdf', 'doc', 'docx', 'rtf', 'odt'], true);
    }

    private function normalizeResult(array $payload, string $extension): array
    {
        $questions = [];
        foreach ((array) ($payload['questions'] ?? []) as $index => $question) {
            if (! is_array($question)) {
                continue;
            }

            $content = trim((string) ($question['content'] ?? ''));
            $label = trim((string) ($question['question_label'] ?? ''));

            if ($content === '' || $label === '') {
                continue;
            }

            $questions[] = [
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
                    'source' => 'openai_direct_review',
                ]),
            ];
        }

        $cleanText = trim((string) ($payload['clean_text'] ?? $payload['text'] ?? ''));
        $excerpt = trim((string) ($payload['excerpt'] ?? ''));

        if ($cleanText === '' && ! empty($questions)) {
            $cleanText = implode("\n\n", array_map(
                fn (array $question) => trim(($question['question_label'] ?? '') . "\n" . ($question['content'] ?? '')),
                $questions
            ));
        }

        if ($excerpt === '') {
            $excerpt = mb_substr($cleanText, 0, 1000);
        }

        $pageCount = (int) ($payload['page_count'] ?? 0);
        $characterCount = (int) ($payload['character_count'] ?? mb_strlen($cleanText));

        return [
            'text' => $cleanText,
            'excerpt' => $excerpt,
            'page_count' => $pageCount > 0 ? $pageCount : ($cleanText !== '' ? 1 : 0),
            'character_count' => $characterCount,
            'requires_transcription' => false,
            'method' => 'openai_direct_review',
            'pages' => $cleanText !== '' ? [[
                'page_number' => 1,
                'text' => $cleanText,
                'page_width' => 1,
                'page_height' => 1,
                'lines' => [],
            ]] : [],
            'questions' => $questions,
            'question_count' => count($questions),
            'question_extraction_method' => 'openai',
            'metadata' => [
                'review_source' => 'openai_direct_review',
                'review_extension' => $extension,
                ...((array) ($payload['metadata'] ?? [])),
            ],
        ];
    }

    private function mimeTypeForExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'rtf' => 'application/rtf',
            'odt' => 'application/vnd.oasis.opendocument.text',
            default => 'application/octet-stream',
        };
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'clean_text' => ['type' => 'string'],
                'excerpt' => ['type' => 'string'],
                'page_count' => ['type' => 'integer', 'minimum' => 0],
                'character_count' => ['type' => 'integer', 'minimum' => 0],
                'metadata' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'title' => ['type' => ['string', 'null']],
                        'description' => ['type' => ['string', 'null']],
                        'education_level' => ['type' => ['string', 'null']],
                        'class_grade' => ['type' => ['string', 'null']],
                        'subject' => ['type' => ['string', 'null']],
                        'course' => ['type' => ['string', 'null']],
                        'exam_category' => ['type' => ['string', 'null']],
                        'year' => ['type' => ['string', 'integer', 'null']],
                        'language' => ['type' => ['string', 'null']],
                        'access_type' => ['type' => ['string', 'null']],
                        'preview_pages' => ['type' => ['string', 'integer', 'null']],
                        'paper' => ['type' => ['string', 'null']],
                    ],
                    'required' => [
                        'title',
                        'description',
                        'education_level',
                        'class_grade',
                        'subject',
                        'course',
                        'exam_category',
                        'year',
                        'language',
                        'access_type',
                        'preview_pages',
                        'paper',
                    ],
                ],
                'questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'sort_order' => ['type' => 'integer', 'minimum' => 1],
                            'section_label' => ['type' => ['string', 'null']],
                            'question_number' => ['type' => ['integer', 'null']],
                            'question_label' => ['type' => 'string'],
                            'part_label' => ['type' => ['string', 'null']],
                            'marks' => ['type' => ['integer', 'null']],
                            'marks_label' => ['type' => ['string', 'null']],
                            'content' => ['type' => 'string'],
                            'raw_text' => ['type' => ['string', 'null']],
                        ],
                        'required' => [
                            'sort_order',
                            'section_label',
                            'question_number',
                            'question_label',
                            'part_label',
                            'marks',
                            'marks_label',
                            'content',
                            'raw_text',
                        ],
                    ],
                ],
            ],
            'required' => ['clean_text', 'excerpt', 'page_count', 'character_count', 'metadata', 'questions'],
        ];
    }

    private function extractOutputText(array $responseJson): string
    {
        $text = '';

        foreach ((array) data_get($responseJson, 'output', []) as $outputItem) {
            if (! is_array($outputItem)) {
                continue;
            }

            foreach ((array) data_get($outputItem, 'content', []) as $contentItem) {
                if (! is_array($contentItem)) {
                    continue;
                }

                $type = strtolower(trim((string) ($contentItem['type'] ?? '')));
                if (! in_array($type, ['output_text', 'text', 'input_text'], true)) {
                    continue;
                }

                $candidate = trim((string) ($contentItem['text'] ?? ''));
                if ($candidate !== '') {
                    $text .= ($text === '' ? '' : "\n") . $candidate;
                }
            }
        }

        return trim($text);
    }

    private function logFailedResponse(string $message, $response, array $context = []): void
    {
        $body = (string) $response->body();

        Log::error($message, array_merge($context, [
            'status' => $response->status(),
            'content_type' => $response->header('content-type'),
            'response_body' => $body,
            'response_preview' => $this->truncateText($body, 4000),
        ]));
    }

    private function truncateText(string $text, int $limit = 4000): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit) . '...[truncated]';
    }
}
