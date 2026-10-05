<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductQuiz;
use App\Models\ProductQuizAttempt;
use App\Models\ProductQuizAttemptAnswer;
use App\Models\ProductQuizQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductQuizAttemptService
{
    public function submit(Product $product, ProductQuiz $quiz, int $userId, array $answers): ProductQuizAttempt
    {
        return DB::transaction(function () use ($product, $quiz, $userId, $answers) {
            $attemptNumber = ProductQuizAttempt::where('product_quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->count() + 1;

            $questions = $quiz->questions()->with('options')->get();
            $totalMarks = (int) $questions->sum('marks');
            $score = 0;

            $attempt = ProductQuizAttempt::create([
                'product_quiz_id' => $quiz->id,
                'product_id' => $product->id,
                'user_id' => $userId,
                'attempt_number' => $attemptNumber,
                'score' => 0,
                'total_marks' => $totalMarks,
                'percentage' => 0,
                'status' => 'fail',
                'submitted_at' => now(),
                'quiz_snapshot' => $this->quizSnapshot($quiz, $questions),
            ]);

            foreach ($questions as $question) {
                $submitted = $answers[$question->id] ?? [];
                [$isCorrect, $awardedMarks, $selectedOptionId, $typedAnswer] = $this->markQuestion($question, $submitted);
                $score += $awardedMarks;

                ProductQuizAttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_option_id' => $selectedOptionId,
                    'typed_answer' => $typedAnswer,
                    'is_correct' => $isCorrect,
                    'awarded_marks' => $awardedMarks,
                ]);
            }

            $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0;

            $attempt->update([
                'score' => $score,
                'percentage' => $percentage,
                'status' => $score >= $quiz->pass_mark ? 'pass' : 'fail',
            ]);

            return $attempt->fresh(['answers.question.options', 'answers.selectedOption', 'quiz.product']);
        });
    }

    public function attemptsUsed(ProductQuiz $quiz, int $userId): int
    {
        return ProductQuizAttempt::where('product_quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->count();
    }

    private function markQuestion(ProductQuizQuestion $question, array $submitted): array
    {
        if ($question->question_type === 'single_choice') {
            $selectedOptionId = isset($submitted['selected_option_id']) ? (int) $submitted['selected_option_id'] : null;
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect = $correctOption && $selectedOptionId === $correctOption->id;

            return [$isCorrect, $isCorrect ? (int) $question->marks : 0, $selectedOptionId, null];
        }

        $typedAnswer = trim((string) ($submitted['typed_answer'] ?? ''));
        $normalizedAnswer = mb_strtolower($typedAnswer);
        $correctAnswer = mb_strtolower(trim((string) $question->correct_text_answer));
        $isCorrect = $normalizedAnswer !== '' && $normalizedAnswer === $correctAnswer;

        return [$isCorrect, $isCorrect ? (int) $question->marks : 0, null, $typedAnswer];
    }

    private function quizSnapshot(ProductQuiz $quiz, Collection $questions): array
    {
        return [
            'quiz_id' => $quiz->id,
            'tier' => $quiz->tier,
            'difficulty' => $quiz->difficulty,
            'pass_mark' => $quiz->pass_mark,
            'questions' => $questions->map(fn (ProductQuizQuestion $question) => [
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
            ])->values()->all(),
        ];
    }
}
