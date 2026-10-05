<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductQuizUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:course_categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive', 'is_draft'])],
            'tier' => ['required', Rule::in(['short', 'long'])],
            'difficulty' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'attempt_limit' => ['required', 'integer', 'min:1'],
            'pass_mark' => ['required', 'integer', 'min:0'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'class_grade' => ['nullable', 'string', 'max:255'],
            'exam_category' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:255'],
            'total_questions' => ['nullable', 'integer', 'min:1'],
            'question_order' => ['nullable', Rule::in(['random', 'fixed'])],
            'show_results' => ['nullable', Rule::in(['immediately', 'after_attempt'])],
            'is_enabled' => ['nullable', 'boolean'],
            'show_correct_answers' => ['nullable', 'boolean'],
            'allow_review' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'one_question_per_page' => ['nullable', 'boolean'],
            'show_progress_bar' => ['nullable', 'boolean'],
            'question_numbering' => ['nullable', Rule::in(['continuous', 'per_page'])],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.prompt' => ['required', 'string'],
            'questions.*.question_type' => ['required', Rule::in(['single_choice', 'short_answer'])],
            'questions.*.marks' => ['required', 'integer', 'min:1'],
            'questions.*.correct_text_answer' => ['nullable', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.option_text' => ['nullable', 'string'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $questions = collect($this->input('questions', []))->values();
            $discountInput = $this->input('discount');
            $effectivePrice = $discountInput !== null && $discountInput !== ''
                ? (float) $discountInput
                : (float) $this->input('price', 0);

            if ($this->input('tier') === 'short' && $questions->count() >= 10) {
                $validator->errors()->add('questions', __('Short quizzes must have fewer than 10 questions.'));
            }

            if ($this->input('tier') === 'long' && $questions->count() <= 10) {
                $validator->errors()->add('questions', __('Long quizzes must have more than 10 questions.'));
            }

            if ($this->input('tier') === 'short' && $effectivePrice > 50) {
                $validator->errors()->add('price', __('Short quiz sale price cannot exceed KES 50.'));
            }

            if ($this->input('tier') === 'long' && $effectivePrice < 20) {
                $validator->errors()->add('price', __('Long quiz sale price cannot be less than KES 20.'));
            }

            foreach ($questions as $index => $question) {
                $label = 'questions.' . $index;
                $type = $question['question_type'] ?? null;

                if ($type === 'single_choice') {
                    $options = collect($question['options'] ?? [])
                        ->filter(fn ($option) => filled($option['option_text'] ?? null))
                        ->values();
                    $correctCount = $options->where('is_correct', true)->count();

                    if ($options->count() < 2) {
                        $validator->errors()->add($label . '.options', __('Single-choice questions require at least two options.'));
                    }

                    if ($correctCount !== 1) {
                        $validator->errors()->add($label . '.options', __('Single-choice questions require exactly one correct option.'));
                    }
                }

                if ($type === 'short_answer' && !filled($question['correct_text_answer'] ?? null)) {
                    $validator->errors()->add($label . '.correct_text_answer', __('Short-answer questions require an instructor answer key.'));
                }
            }

            $totalMarks = $questions->sum(fn ($question) => (int) ($question['marks'] ?? 0));

            if ((int) $this->input('pass_mark', 0) > $totalMarks) {
                $validator->errors()->add('pass_mark', __('Pass mark cannot exceed the total quiz marks.'));
            }
        });
    }
}
