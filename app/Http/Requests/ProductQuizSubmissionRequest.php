<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductQuizSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*.selected_option_id' => ['nullable', 'integer'],
            'answers.*.typed_answer' => ['nullable', 'string'],
        ];
    }
}
