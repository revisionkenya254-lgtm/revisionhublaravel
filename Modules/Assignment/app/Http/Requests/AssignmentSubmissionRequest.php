<?php

namespace Modules\Assignment\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentSubmissionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'assignment_id' => ['required', 'exists:assignments,id'],
            'file_paths' => ['nullable', 'array'],
            'file_paths.*' => ['string'],
            'text_submission' => ['nullable', 'string'],
            'link_submission' => ['nullable', 'url'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
    public function messages(): array
    {
        return [
            'assignment_id.required' => __('Assignment is required.'),
            'assignment_id.exists' => __('Assignment not found.'),

            'file_paths.array' => __('File paths must be an array.'),

            'file_paths.*.string' => __('File path must be a string.'),

            'text_submission.string' => __('Text submission must be a string.'),

            'link_submission.url' => __('Link submission must be a valid URL.'),
        ];
    }
}
