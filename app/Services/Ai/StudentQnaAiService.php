<?php

namespace App\Services\Ai;

use App\Models\LessonQuestion;
use App\Models\LessonReply;
use RuntimeException;

class StudentQnaAiService
{
    public function __construct(
        private readonly AiDocumentRetrievalService $retrievalService,
        private readonly AiProviderRouterService $routerService,
        private readonly OpenAiChatProvider $openAiProvider,
        private readonly AiTutorUserService $aiTutorUserService
    ) {
    }

    public function answerQuestion(LessonQuestion $question): ?LessonReply
    {
        $question->loadMissing(['course:id,title,slug,instructor_id', 'lesson:id,title']);

        if (! $question->course?->instructor_id) {
            return null;
        }

        if ($question->replies()->where('is_ai', true)->exists()) {
            return $question->replies()->where('is_ai', true)->latest()->first();
        }

        $questionText = trim($question->question_title . "\n\n" . $question->question_description);
        $retrieval = $this->retrievalService->search($question->course->instructor_id, $questionText, [
            'limit' => 5,
        ]);
        $retrievalThreshold = (int) config('services.ai.retrieval_min_score', 10);
        $topScore = (int) data_get($retrieval, 'results.0.score', 0);
        $useRetrievalContext = $topScore >= $retrievalThreshold && ! empty($retrieval['results']);
        $retrievalContext = $useRetrievalContext ? ($retrieval['context'] ?? '') : '';
        $retrievalResults = $useRetrievalContext ? ($retrieval['results'] ?? []) : [];

        $providerOrder = $this->routerService->orderedProviders($questionText, $retrieval);
        $messages = $this->buildMessages($question, $retrievalContext, $useRetrievalContext);
        $lastException = null;

        foreach ($providerOrder as $providerName) {
            try {
                $provider = $this->resolveProvider($providerName);
                $result = $provider->answer($messages, [
                    'temperature' => 0.2,
                    'max_tokens' => 700,
                ]);

                $content = trim((string) ($result['content'] ?? ''));

                if ($content === '') {
                    continue;
                }

                return LessonReply::create([
                    'question_id' => $question->id,
                    'user_id' => $this->aiTutorUserService->resolve()->id,
                    'reply' => $content,
                    'is_ai' => true,
                    'ai_provider' => $result['provider'] ?? $providerName,
                    'ai_model' => $result['model'] ?? null,
                    'ai_context' => $retrievalContext ?: null,
                    'metadata' => [
                        'retrieval' => [
                            'used' => $useRetrievalContext,
                            'threshold' => $retrievalThreshold,
                            'top_score' => $topScore,
                            'results' => $retrievalResults,
                        ],
                        'provider_order' => $providerOrder,
                        'course_id' => $question->course_id,
                        'lesson_id' => $question->lesson_id,
                    ],
                ]);
            } catch (RuntimeException $exception) {
                $lastException = $exception;
                continue;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return null;
    }

    private function resolveProvider(string $providerName): AiProviderInterface
    {
        return $this->openAiProvider;
    }

    private function buildMessages(LessonQuestion $question, string $retrievalContext, bool $useRetrievalContext): array
    {
        $courseTitle = $question->course?->title ?? __('the course');
        $lessonTitle = $question->lesson?->title ?? __('the lesson');
        $context = $retrievalContext;

        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are a helpful tutoring assistant for a student learning platform.',
                    'Answer using the instructor-provided context first.',
                    'If the context is sufficient, be direct and concise.',
                    'If the context is incomplete, clearly say what is inferred and avoid pretending to know unsupported details.',
                    'When relevant, mention the source name and page numbers from the provided context.',
                    'Keep the answer friendly, clear, and study-focused.',
                ]),
            ],
            [
                'role' => 'user',
                'content' => trim(implode("\n\n", [
                    "Course: {$courseTitle}",
                    "Lesson: {$lessonTitle}",
                    "Student question: " . $question->question_title,
                    "Question details: " . $question->question_description,
                    $useRetrievalContext && $context !== ''
                        ? "Instructor PDF context:\n{$context}"
                        : 'Instructor PDF context: none found with enough confidence, so answer from general tutoring knowledge and the question details.',
                ])),
            ],
        ];
    }

    public function hasAiReply(LessonQuestion|int $question): bool
    {
        $questionId = $question instanceof LessonQuestion ? $question->id : $question;

        return LessonReply::query()
            ->where('question_id', $questionId)
            ->where('is_ai', true)
            ->exists();
    }
}
