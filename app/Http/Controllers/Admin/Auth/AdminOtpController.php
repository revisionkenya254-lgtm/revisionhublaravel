<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthAccountResolverService;
use App\Services\AuthOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminOtpController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin');
    }

    public function show(Request $request): View
    {
        abort_unless($request->query('purpose') === AuthOtpService::PURPOSE_LOGIN, 404);

        return view('admin.auth.otp', [
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
            'purpose' => ['required', 'in:'.AuthOtpService::PURPOSE_LOGIN],
            'otp' => ['required', 'digits:5'],
        ], [
            'email.required' => __('Email is required'),
            'otp.required' => __('OTP is required'),
            'otp.digits' => __('OTP must be exactly 5 digits'),
        ]);

        $resolution = $accountResolver->resolveAdminOtpAccount($validated['email']);

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

        if ($resolution->isInactive()) {
            return back()->with([
                'messege' => $resolution->message,
                'alert-type' => 'error',
            ]);
        }

        $admin = $resolution->admin;

        if (!$authOtpService->verify($admin, $validated['otp'], $validated['purpose'])) {
            throw ValidationException::withMessages([
                'otp' => __('The OTP is invalid or has expired.'),
            ]);
        }

        $authOtpService->clear($admin);

        Auth::guard('admin')->login($admin, false);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))->with([
            'messege' => __('Logged in successfully.'),
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
            'purpose' => ['required', 'in:'.AuthOtpService::PURPOSE_LOGIN],
        ]);

        $resolution = $accountResolver->resolveAdminOtpAccount($validated['email']);

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

        if ($resolution->isInactive()) {
            return back()->with([
                'messege' => $resolution->message,
                'alert-type' => 'error',
            ]);
        }

        $admin = $resolution->admin;

        if (!$authOtpService->send($admin, $validated['purpose'])) {
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
