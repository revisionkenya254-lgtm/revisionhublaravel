<?php

namespace Modules\Assignment\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'submission_type' => ['required', 'in:file,text,link'],
            'due_date' => ['nullable', 'date'],
            'late_submission_allowed' => ['nullable', 'boolean'],
            'late_penalty' => ['nullable', 'string'],
            'max_marks' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:draft,published'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
    public function messages(): array
    {
        return [
            'title.required' => __('Title is required.'),
            'title.string' => __('Title must be a string.'),
            'title.max' => __('Title cannot exceed 255 characters.'),

            'description.string' => __('Description must be a string.'),

            'instructions.string' => __('Instructions must be a string.'),

            'submission_type.required' => __('Submission type is required.'),
            'submission_type.in' => __('Invalid submission type.'),

            'due_date.date' => __('Due date must be a valid date.'),

            'late_submission_allowed.boolean' => __('Allow late submission must be true or false.'),

            'late_penalty.string' => __('Late penalty must be a string.'),

            'max_marks.required' => __('Max marks is required.'),
            'max_marks.numeric' => __('Max marks must be a number.'),
            'max_marks.min' => __('Max marks must be zero or greater.'),

            'status.in' => __('Invalid status.'),
        ];
    }
}
