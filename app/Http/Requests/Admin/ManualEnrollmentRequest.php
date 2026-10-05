<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ManualEnrollmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'type' => ['required', 'in:free,paid'],
            'amount' => ['required_if:type,paid', 'nullable', 'numeric', 'min:0'],
            'payment_method' => ['required_if:type,paid', 'nullable', 'string', 'max:100'],
            'currency_id' => ['required_if:type,paid', 'nullable', 'exists:multi_currencies,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => __('Please select a student.'),
            'user_id.exists' => __('Selected student not found.'),
            'course_id.required' => __('Please select a course.'),
            'course_id.exists' => __('Selected course not found.'),
            'type.required' => __('Enrollment type is required.'),
            'type.in' => __('Enrollment type must be free or paid.'),
            'amount.required_if' => __('Amount is required for paid enrollment.'),
            'amount.numeric' => __('Amount must be a number.'),
            'amount.min' => __('Amount must be at least 0.'),
            'payment_method.required_if' => __('Payment method is required for paid enrollment.'),
            'currency_id.required_if' => __('Currency is required for paid enrollment.'),
        ];
    }
}
