<?php

namespace Modules\Assignment\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentFeedbackRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'submission_id' => ['required', 'exists:assignment_submissions,id'],
            'marks' => ['required', 'numeric', 'min:0'],
            'written_feedback' => ['nullable', 'string'],
            'file_feedback' => ['nullable', 'string'],
            'allow_resubmission' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
    public function messages(): array
    {
        return [
            'submission_id.required' => __('Please select a submission.'),
            'submission_id.exists' => __('Submission not found.'),

            'marks.required' => __('Marks are required.'),
            'marks.numeric' => __('Marks must be a number.'),
            'marks.min' => __('Marks must be zero or greater.'),


            'written_feedback.string' => __('Written feedback must be a valid text.'),

            'file_feedback.string' => __('File feedback must be a valid file path or string.'),

            'allow_resubmission.boolean' => __('Allow resubmission must be true or false.'),
        ];
    }
}
