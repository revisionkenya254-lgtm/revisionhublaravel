<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthAccountResolverService;
use App\Services\AuthOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthOtpController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless(
            in_array($request->query('purpose'), [AuthOtpService::PURPOSE_LOGIN, AuthOtpService::PURPOSE_REGISTER], true),
            404
        );

        return view('auth.otp', [
            'email' => $request->query('email'),
            'purpose' => $request->query('purpose'),
        ]);
    }

    public function verify(
        Request $request,
        AuthOtpService $authOtpService,
        AuthAccountResolverService $accountResolver
    ): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'purpose' => ['required', 'in:'.AuthOtpService::PURPOSE_LOGIN.','.AuthOtpService::PURPOSE_REGISTER],
            'otp' => ['required', 'digits:5'],
        ], [
            'email.required' => __('Email is required'),
            'otp.required' => __('OTP is required'),
            'otp.digits' => __('OTP must be exactly 5 digits'),
        ]);

        $resolution = $accountResolver->resolvePublicOtpAccount($validated['email']);

        if ($resolution->isRedirect()) {
            return redirect()
                ->route($resolution->redirectRoute)
                ->with([
                    'messege' => $resolution->message,
                    'alert-type' => 'error',
                ]);
        }

        if ($resolution->isNotFound()) {
            throw ValidationException::withMessages([
                'otp' => $resolution->message ?: __('The OTP is invalid or has expired.'),
            ]);
        }

        if ($resolution->isInactive() || $resolution->isBanned()) {
            return redirect()->route('login')->with([
                'messege' => $resolution->message,
                'alert-type' => 'error',
            ]);
        }

        $user = $resolution->user;

        if (!$authOtpService->verify($user, $validated['otp'], $validated['purpose'])) {
            throw ValidationException::withMessages([
                'otp' => __('The OTP is invalid or has expired.'),
            ]);
        }

        if (!$user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $authOtpService->clear($user);

        Auth::guard('web')->login($user, true);
        sessionCartToDatabase();

        $redirectUrl = match ($user->role) {
            'admin' => route('admin.dashboard'),
            'instructor' => route('instructor.dashboard'),
            default => route('student.dashboard'),
        };

        return redirect()->intended($redirectUrl)->with([
            'messege' => $validated['purpose'] === AuthOtpService::PURPOSE_REGISTER
                ? __('Your account has been verified successfully.')
                : __('Logged in successfully.'),
            'alert-type' => 'success',
        ]);
    }

    public function resend(
        Request $request,
        AuthOtpService $authOtpService,
        AuthAccountResolverService $accountResolver
    ): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'purpose' => ['required', 'in:'.AuthOtpService::PURPOSE_LOGIN.','.AuthOtpService::PURPOSE_REGISTER],
        ]);

        $resolution = $accountResolver->resolvePublicOtpAccount($validated['email']);

        if ($resolution->isRedirect()) {
            return redirect()
                ->route($resolution->redirectRoute)
                ->with([
                    'messege' => $resolution->message,
                    'alert-type' => 'error',
                ]);
        }

        if ($resolution->isNotFound()) {
            throw ValidationException::withMessages([
                'email' => $resolution->message ?: __('We could not find an account for that email address.'),
            ]);
        }

        if ($resolution->isInactive() || $resolution->isBanned()) {
            return back()->with([
                'messege' => $resolution->message,
                'alert-type' => 'error',
            ]);
        }

        $user = $resolution->user;

        if (!$authOtpService->send($user, $validated['purpose'])) {
            return back()->with([
                'messege' => __('We could not send the OTP right now. Please try again.'),
                'alert-type' => 'error',
            ]);
        }

        return back()->with([
            'messege' => __('A new OTP has been sent to your email address.'),
            'alert-type' => 'success',
        ]);
    }
}
