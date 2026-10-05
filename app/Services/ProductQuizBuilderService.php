<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductQuiz;
use App\Models\ProductQuizQuestion;
use App\Services\ProductIdentityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductQuizBuilderService
{
    public function __construct(
        private readonly ProductMetadataCatalogService $metadataCatalog,
        private readonly ProductIdentityService $identityService
    )
    {
    }

    public function sync(Product $product, array $validated, bool $publishNow = false): ProductQuiz
    {
        return DB::transaction(function () use ($product, $validated, $publishNow) {
            $shouldPublish = $publishNow && ($validated['status'] ?? null) === 'active';

            $quiz = ProductQuiz::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'tier' => $validated['tier'],
                    'difficulty' => $validated['difficulty'],
                    'duration_minutes' => $validated['duration_minutes'] ?? null,
                    'attempt_limit' => $validated['attempt_limit'],
                    'pass_mark' => $validated['pass_mark'],
                    'published_at' => $shouldPublish ? now() : null,
                ]
            );

            $existingQuestionIds = $quiz->questions()->pluck('id')->all();
            $keptQuestionIds = [];

            foreach (array_values($validated['questions']) as $questionIndex => $questionData) {
                $question = ProductQuizQuestion::updateOrCreate(
                    [
                        'id' => $questionData['id'] ?? null,
                        'product_quiz_id' => $quiz->id,
                    ],
                    [
                        'prompt' => $questionData['prompt'],
                        'question_type' => $questionData['question_type'],
                        'marks' => (int) $questionData['marks'],
                        'sort_order' => $questionIndex,
                        'correct_text_answer' => $questionData['question_type'] === 'short_answer'
                            ? $this->normalizeTextAnswer($questionData['correct_text_answer'] ?? '')
                            : null,
                    ]
                );

                $keptQuestionIds[] = $question->id;

                $existingOptionIds = $question->options()->pluck('id')->all();
                $keptOptionIds = [];

                if ($question->question_type === 'single_choice') {
                    foreach (array_values($questionData['options'] ?? []) as $optionIndex => $optionData) {
                        if (!filled($optionData['option_text'] ?? null)) {
                            continue;
                        }

                        $option = $question->options()->updateOrCreate(
                            [
                                'id' => $optionData['id'] ?? null,
                                'question_id' => $question->id,
                            ],
                            [
                                'option_text' => $optionData['option_text'],
                                'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                                'sort_order' => $optionIndex,
                            ]
                        );

                        $keptOptionIds[] = $option->id;
                    }
                }

                if ($existingOptionIds !== []) {
                    $question->options()->whereNotIn('id', $keptOptionIds ?: [0])->delete();
                }
            }

            if ($existingQuestionIds !== []) {
                $quiz->questions()->whereNotIn('id', $keptQuestionIds ?: [0])->delete();
            }

            $quiz->update([
                'question_count_cache' => $quiz->questions()->count(),
                'published_at' => $shouldPublish ? now() : null,
            ]);

            if ($shouldPublish) {
                $quiz->refresh();
                $this->ensurePublishable($product->fresh(), $quiz->loadMissing('questions.options'));
            }

            $product->update([
                'metadata' => $this->buildMetadata($product, $quiz, $validated),
            ]);

            return $quiz->fresh(['questions.options']);
        });
    }

    public function buildMetadata(Product $product, ProductQuiz $quiz, array $validated = []): array
    {
        $existingMetadata = $product->metadata ?? [];
        $defaults = $this->metadataCatalog->sharedDefaults();
        $subject = $validated['subject'] ?? ($existingMetadata['subject'] ?? $defaults['subject']);

        $metadata = array_filter(array_merge($existingMetadata, [
            'subject' => $subject,
            'quiz_subject' => $subject,
            'tier' => $quiz->tier,
            'difficulty' => $quiz->difficulty,
            'question_count' => $quiz->question_count_cache,
            'duration' => $quiz->duration_minutes,
            'education_level' => $validated['education_level'] ?? ($existingMetadata['education_level'] ?? $defaults['education_level']),
            'class_grade' => $validated['class_grade'] ?? ($existingMetadata['class_grade'] ?? $defaults['class_grade']),
            'exam_category' => $validated['exam_category'] ?? ($existingMetadata['exam_category'] ?? $defaults['exam_category']),
            'topic' => $validated['topic'] ?? ($existingMetadata['topic'] ?? null),
            'tags' => isset($validated['tags'])
                ? collect(explode(',', (string) $validated['tags']))->map(fn ($tag) => trim($tag))->filter()->values()->all()
                : ($existingMetadata['tags'] ?? null),
            'total_questions' => $validated['total_questions'] ?? ($existingMetadata['total_questions'] ?? null),
            'question_order' => $validated['question_order'] ?? ($existingMetadata['question_order'] ?? null),
            'show_results' => $validated['show_results'] ?? ($existingMetadata['show_results'] ?? null),
            'is_enabled' => isset($validated['is_enabled']) ? (bool) $validated['is_enabled'] : ($existingMetadata['is_enabled'] ?? true),
            'show_correct_answers' => isset($validated['show_correct_answers']) ? (bool) $validated['show_correct_answers'] : ($existingMetadata['show_correct_answers'] ?? true),
            'allow_review' => isset($validated['allow_review']) ? (bool) $validated['allow_review'] : ($existingMetadata['allow_review'] ?? true),
            'shuffle_options' => isset($validated['shuffle_options']) ? (bool) $validated['shuffle_options'] : ($existingMetadata['shuffle_options'] ?? true),
            'one_question_per_page' => isset($validated['one_question_per_page']) ? (bool) $validated['one_question_per_page'] : ($existingMetadata['one_question_per_page'] ?? false),
            'show_progress_bar' => isset($validated['show_progress_bar']) ? (bool) $validated['show_progress_bar'] : ($existingMetadata['show_progress_bar'] ?? true),
            'question_numbering' => $validated['question_numbering'] ?? ($existingMetadata['question_numbering'] ?? 'continuous'),
        ]), fn ($value) => $value !== null && $value !== '');

        return $this->identityService->stampMetadata($metadata, [
            'type' => Product::TYPE_QUIZ,
            'education_level' => $metadata['education_level'] ?? null,
            'class_grade' => $metadata['class_grade'] ?? null,
            'exam_category' => $metadata['exam_category'] ?? null,
            'subject' => $metadata['subject'] ?? null,
            'course' => $metadata['quiz_subject'] ?? null,
            'topic' => $metadata['topic'] ?? null,
            'tags' => $metadata['tags'] ?? null,
        ], $product);
    }

    public function formData(Product $product): array
    {
        $quiz = $product->quiz;
        $metadata = $product->metadata ?? [];
        $defaults = $this->metadataCatalog->quizDefaults($quiz?->tier ?? ($metadata['tier'] ?? 'short'));

        if (!$quiz) {
            return [
                'tier' => $metadata['tier'] ?? $defaults['tier'],
                'difficulty' => $metadata['difficulty'] ?? $defaults['difficulty'],
                'duration_minutes' => $metadata['duration'] ?? $defaults['duration_minutes'],
                'attempt_limit' => $metadata['attempt_limit'] ?? $defaults['attempt_limit'],
                'pass_mark' => $metadata['pass_mark'] ?? $defaults['pass_mark'],
                'education_level' => $metadata['education_level'] ?? $defaults['education_level'],
                'class_grade' => $metadata['class_grade'] ?? $defaults['class_grade'],
                'exam_category' => $metadata['exam_category'] ?? $defaults['exam_category'],
                'subject' => $metadata['subject'] ?? $metadata['quiz_subject'] ?? $defaults['subject'],
                'topic' => $metadata['topic'] ?? $defaults['topic'],
                'tags' => isset($metadata['tags']) ? implode(', ', (array) $metadata['tags']) : $defaults['tags'],
                'total_questions' => $metadata['total_questions'] ?? $defaults['total_questions'],
                'question_order' => $metadata['question_order'] ?? $defaults['question_order'],
                'show_results' => $metadata['show_results'] ?? $defaults['show_results'],
                'is_enabled' => $metadata['is_enabled'] ?? $defaults['is_enabled'],
                'show_correct_answers' => $metadata['show_correct_answers'] ?? $defaults['show_correct_answers'],
                'allow_review' => $metadata['allow_review'] ?? $defaults['allow_review'],
                'shuffle_options' => $metadata['shuffle_options'] ?? $defaults['shuffle_options'],
                'one_question_per_page' => $metadata['one_question_per_page'] ?? $defaults['one_question_per_page'],
                'show_progress_bar' => $metadata['show_progress_bar'] ?? $defaults['show_progress_bar'],
                'question_numbering' => $metadata['question_numbering'] ?? $defaults['question_numbering'],
                'questions' => [],
            ];
        }

        return [
            'tier' => $quiz->tier,
            'difficulty' => $quiz->difficulty,
            'duration_minutes' => $quiz->duration_minutes,
            'attempt_limit' => $quiz->attempt_limit,
            'pass_mark' => $quiz->pass_mark,
            'education_level' => $metadata['education_level'] ?? $defaults['education_level'],
            'class_grade' => $metadata['class_grade'] ?? $defaults['class_grade'],
            'exam_category' => $metadata['exam_category'] ?? $defaults['exam_category'],
            'subject' => $metadata['subject'] ?? $metadata['quiz_subject'] ?? $defaults['subject'],
            'topic' => $metadata['topic'] ?? $defaults['topic'],
            'tags' => isset($metadata['tags']) ? implode(', ', (array) $metadata['tags']) : $defaults['tags'],
            'total_questions' => $metadata['total_questions'] ?? $defaults['total_questions'],
            'question_order' => $metadata['question_order'] ?? $defaults['question_order'],
            'show_results' => $metadata['show_results'] ?? $defaults['show_results'],
            'is_enabled' => $metadata['is_enabled'] ?? $defaults['is_enabled'],
            'show_correct_answers' => $metadata['show_correct_answers'] ?? $defaults['show_correct_answers'],
            'allow_review' => $metadata['allow_review'] ?? $defaults['allow_review'],
            'shuffle_options' => $metadata['shuffle_options'] ?? $defaults['shuffle_options'],
            'one_question_per_page' => $metadata['one_question_per_page'] ?? $defaults['one_question_per_page'],
            'show_progress_bar' => $metadata['show_progress_bar'] ?? $defaults['show_progress_bar'],
            'question_numbering' => $metadata['question_numbering'] ?? $defaults['question_numbering'],
            'questions' => $quiz->questions->map(function (ProductQuizQuestion $question) {
                return [
                    'id' => $question->id,
                    'prompt' => $question->prompt,
                    'question_type' => $question->question_type,
                    'marks' => $question->marks,
                    'correct_text_answer' => $question->question_type === 'short_answer' ? $question->correct_text_answer : null,
                    'options' => $question->options->map(fn ($option) => [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'is_correct' => $option->is_correct,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    public function ensurePublishable(Product $product, ProductQuiz $quiz): void
    {
        $questionCount = $quiz->question_count_cache ?: $quiz->questions()->count();
        $effectivePrice = $product->effective_price;
        $errors = [];

        if ($questionCount < 1) {
            $errors['questions'] = __('A quiz must contain at least one question.');
        }

        if ($quiz->tier === 'short' && $questionCount >= 10) {
            $errors['tier'] = __('Short quizzes must have fewer than 10 questions.');
        }

        if ($quiz->tier === 'long' && $questionCount <= 10) {
            $errors['tier'] = __('Long quizzes must have more than 10 questions.');
        }

        if ($quiz->tier === 'short' && $effectivePrice > 50) {
            $errors['price'] = __('Short quiz sale price cannot exceed KES 50.');
        }

        if ($quiz->tier === 'long' && $effectivePrice < 20) {
            $errors['price'] = __('Long quiz sale price cannot be less than KES 20.');
        }

        if ($quiz->pass_mark > $quiz->total_marks) {
            $errors['pass_mark'] = __('Pass mark cannot exceed the total quiz marks.');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function normalizeTextAnswer(?string $value): string
    {
        return trim((string) $value);
    }
}
