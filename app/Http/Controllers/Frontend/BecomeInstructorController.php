<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\BecomeInstructorStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\InstructorRequest\app\Models\InstructorRequest;
use Modules\InstructorRequest\app\Models\InstructorRequestSetting;
use Modules\PaymentWithdraw\app\Models\WithdrawMethod;

class BecomeInstructorController extends Controller
{

    function index(): View|RedirectResponse
    {
        if (isInstructorAccount()) {
            return to_route('instructor.dashboard');
        }

        if (InstructorRequest::where('user_id', auth('web')->id())->exists()) {
            return to_route('become-instructor.review');
        }

        $instructorRequestSetting = InstructorRequestSetting::first();
        $withdrawMethods = WithdrawMethod::enabledPaymentGateways();
        return view('frontend.pages.become-instructor', compact('withdrawMethods', 'instructorRequestSetting'));
    }

    function store(BecomeInstructorStoreRequest $request): RedirectResponse
    {
        if (isInstructorAccount()) {
            return to_route('instructor.dashboard');
        }

        if (InstructorRequest::where('user_id', auth('web')->id())->exists()) {
            return to_route('become-instructor.review');
        }

        $user = auth('web')->user();
        $status = $user->role == 'instructor' ? 'approved' : 'pending';
        $extraInformation = $this->formatExtraInformation($request);
        $instructorRequest = InstructorRequest::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => $status,
                'payout_account' => $request->payout_account,
                'payout_information' => $request->phone_number,
                'extra_information' => $extraInformation,
                'correction_used' => false,
            ]
        );

        if($request->has('certificate')) {
            $filePath = file_upload($request->certificate);
            $instructorRequest->certificate = $filePath;
            $instructorRequest->save();
        }

        if($request->has('identity_scan')) {
            $filePath = file_upload($request->identity_scan);
            $instructorRequest->identity_scan = $filePath;
            $instructorRequest->save();
        }

        return redirect()->route('become-instructor.review')->with([
            'success' => __('Instructor request submitted successfully we will let you know when your account is approved'),
            'alert-type' => 'success'
        ]);
    }

    function review(): View|RedirectResponse
    {
        if (isInstructorAccount()) {
            return to_route('instructor.dashboard');
        }

        $instructorRequest = InstructorRequest::where('user_id', auth('web')->id())->first();

        if (!$instructorRequest) {
            return to_route('become-instructor');
        }

        $user = auth('web')->user();
        $withdrawMethods = WithdrawMethod::enabledPaymentGateways();
        $submittedDetails = $this->parseExtraInformation($instructorRequest->extra_information);
        $answerOptions = $this->answerOptions();
        $selectedAnswers = $this->selectedAnswers($submittedDetails, $answerOptions);
        $canUpdate = !$instructorRequest->correction_used && $instructorRequest->status !== UserStatus::APPROVED->value;

        return view('frontend.pages.become-instructor-review', compact(
            'user',
            'instructorRequest',
            'withdrawMethods',
            'submittedDetails',
            'answerOptions',
            'selectedAnswers',
            'canUpdate'
        ));
    }

    function update(BecomeInstructorStoreRequest $request): RedirectResponse
    {
        if (isInstructorAccount()) {
            return to_route('instructor.dashboard');
        }

        $instructorRequest = InstructorRequest::where('user_id', auth('web')->id())->first();

        if (!$instructorRequest) {
            return to_route('become-instructor');
        }

        if ($instructorRequest->correction_used || $instructorRequest->status === UserStatus::APPROVED->value) {
            return redirect()->route('become-instructor.review')->with([
                'messege' => __('You have already used your request update.'),
                'alert-type' => 'error',
            ]);
        }

        $instructorRequest->update([
            'payout_account' => $request->payout_account,
            'payout_information' => $request->phone_number,
            'extra_information' => $this->formatExtraInformation($request),
            'correction_used' => true,
        ]);

        if ($request->has('certificate')) {
            $instructorRequest->certificate = file_upload($request->certificate);
        }

        if ($request->has('identity_scan')) {
            $instructorRequest->identity_scan = file_upload($request->identity_scan);
        }

        $instructorRequest->save();

        return redirect()->route('become-instructor.review')->with([
            'messege' => __('Instructor request updated successfully.'),
            'alert-type' => 'success',
        ]);
    }

    private function formatExtraInformation(BecomeInstructorStoreRequest $request): string
    {
        $answerOptions = $this->answerOptions();
        $answers = [
            __('Teaching experience') => $answerOptions['teaching_experience'][$request->teaching_experience] ?? $request->teaching_experience,
            __('Video experience') => $answerOptions['video_experience'][$request->video_experience] ?? $request->video_experience,
            __('Audience') => $answerOptions['audience_level'][$request->audience_level] ?? $request->audience_level,
            __('Phone Number') => $request->phone_number,
        ];

        $lines = collect($answers)
            ->map(fn ($answer, $question) => "{$question}: {$answer}")
            ->push(__('Extra Information') . ': ' . ($request->extra_information ?: __('N/A')));

        return $lines->implode(PHP_EOL);
    }

    private function answerOptions(): array
    {
        return [
            'teaching_experience' => [
                'in_person_informally' => __('In person, informally'),
                'in_person_professionally' => __('In person, professionally'),
                'online' => __('Online'),
                'other' => __('Other'),
            ],
            'video_experience' => [
                'beginner' => __('I am a beginner'),
                'some_knowledge' => __('I have some knowledge'),
                'experienced' => __('I am experienced'),
                'ready_to_upload' => __('I have videos ready to upload'),
            ],
            'audience_level' => [
                'not_at_the_moment' => __('Not at the moment'),
                'small_following' => __('I have a small following'),
                'sizeable_following' => __('I have a sizeable following'),
            ],
        ];
    }

    private function parseExtraInformation(?string $extraInformation): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $extraInformation ?? ''))
            ->mapWithKeys(function ($line) {
                [$key, $value] = array_pad(explode(':', $line, 2), 2, null);

                return $key ? [trim($key) => trim((string) $value)] : [];
            })
            ->filter(fn ($value) => $value !== '')
            ->toArray();
    }

    private function selectedAnswers(array $submittedDetails, array $answerOptions): array
    {
        $detailKeys = [
            'teaching_experience' => __('Teaching experience'),
            'video_experience' => __('Video experience'),
            'audience_level' => __('Audience'),
        ];

        return collect($detailKeys)
            ->mapWithKeys(function ($detailKey, $field) use ($submittedDetails, $answerOptions) {
                $submittedValue = $submittedDetails[$detailKey] ?? null;
                $selectedValue = collect($answerOptions[$field])
                    ->search(fn ($label) => $label === $submittedValue);

                return [$field => $selectedValue === false ? null : $selectedValue];
            })
            ->toArray();
    }
}
