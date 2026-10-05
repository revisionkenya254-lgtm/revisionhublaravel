<?php

namespace App\Http\Requests\Frontend;

use App\Rules\CustomRecaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Modules\InstructorRequest\app\Models\InstructorRequestSetting;
use Modules\PaymentWithdraw\app\Models\WithdrawMethod;

class BecomeInstructorStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $instructorRequestSetting = InstructorRequestSetting::first();

        $rules = [
            'teaching_experience' => ['required', Rule::in([
                'in_person_informally',
                'in_person_professionally',
                'online',
                'other',
            ])],
            'video_experience' => ['required', Rule::in([
                'beginner',
                'some_knowledge',
                'experienced',
                'ready_to_upload',
            ])],
            'audience_level' => ['required', Rule::in([
                'not_at_the_moment',
                'small_following',
                'sizeable_following',
            ])],
            'payout_account' => ['required', Rule::in(WithdrawMethod::enabledPaymentGateways()->pluck('name')->toArray())],
            'phone_number' => ['required', 'string', 'max:30'],
            'g-recaptcha-response' => Cache::get('setting')->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : 'nullable',
            'extra_information' => ['nullable', 'max:500'],
        ];
        if ($instructorRequestSetting?->need_certificate == 1) {
            $rules['certificate'] = [$this->requiresCertificate() ? 'required' : 'nullable', 'max:20000', 'mimes:pdf,docx,doc,jpg,jpeg,png'];
        }
        if ($instructorRequestSetting?->need_identity_scan == 1) {
            $rules['identity_scan'] = [$this->requiresIdentityScan() ? 'required' : 'nullable', 'max:20000', 'mimes:pdf,docx,doc,jpg,jpeg,png'];
        }

        return $rules;
    }

    function messages(): array
    {
        return [
            'payout_account.required' => __('Payout account is required'),
            'payout_account.in' => __('Payout account is invalid'),
            'phone_number.required' => __('Phone number is required'),
            'phone_number.max' => __('Phone number cannot be longer than :max characters.', ['max' => 30]),
            'teaching_experience.required' => __('Teaching experience is required'),
            'video_experience.required' => __('Video experience is required'),
            'audience_level.required' => __('Audience information is required'),
            'certificate.required' => __('Certificate is required'),
            'identity_scan.required' => __('Identity scan is required'),
            'certificate.max' => __('Certificate size is too large'),
            'certificate.mimes' => __('Certificate must be a PDF, DOCX, DOC, JPG, JPEG or PNG file'),
            'identity_scan.max' => __('Certificate size is too large'),
            'identity_scan.mimes' => __('Certificate must be a PDF, DOCX, DOC, JPG, JPEG or PNG file'),
        ];
    }

    private function requiresCertificate(): bool
    {
        if (!$this->isMethod('put')) {
            return true;
        }

        return !auth('web')->user()?->instructorInfo?->certificate;
    }

    private function requiresIdentityScan(): bool
    {
        if (!$this->isMethod('put')) {
            return true;
        }

        return !auth('web')->user()?->instructorInfo?->identity_scan;
    }
}
