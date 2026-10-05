<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\CustomRecaptcha;
use App\Services\AuthAccountResolverService;
use App\Services\AuthOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Check whether the given email belongs to an admin account before login submits.
     */
    public function checkAccountType(Request $request, AuthAccountResolverService $accountResolver): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $resolution = $accountResolver->resolvePublicLogin($validated['email']);

        return response()->json([
            'is_admin' => $resolution->isRedirect(),
            'redirect_url' => $resolution->redirectRoute ? route($resolution->redirectRoute) : null,
            'message' => $resolution->message,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        Request $request,
        AuthOtpService $authOtpService,
        AuthAccountResolverService $accountResolver
    ): RedirectResponse
    {
        $setting = Cache::get('setting');

        $rules = [
            'email' => 'required|email',
            'g-recaptcha-response' => $setting->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : 'nullable',
        ];

        $customMessages = [
            'email.required' => __('Email is required'),
            'g-recaptcha-response.required' => __('Please complete the recaptcha to submit the form'),
        ];
        $this->validate($request, $rules, $customMessages);

        $resolution = $accountResolver->resolvePublicLogin($request->email);

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
                'email' => $resolution->message ?: __('We could not find an account with that email address'),
            ]);
        }

        if ($resolution->isInactive() || $resolution->isBanned()) {
            throw ValidationException::withMessages([
                'email' => $resolution->message ?: __('Inactive account'),
            ]);
        }

        $user = $resolution->user;

        $purpose = $user->email_verified_at ? AuthOtpService::PURPOSE_LOGIN : AuthOtpService::PURPOSE_REGISTER;

        if (!$authOtpService->send($user, $purpose)) {
            return redirect()->back()->with([
                'messege' => __('We could not send the OTP right now. Please try again.'),
                'alert-type' => 'error',
            ]);
        }

        return redirect()->route('auth.otp.notice', [
            'email' => $user->email,
            'purpose' => $purpose,
        ])->with([
            'messege' => $purpose === AuthOtpService::PURPOSE_LOGIN
                ? __('We sent a 5-digit OTP to your email. Use it to sign in.')
                : __('Your account is not verified yet. We sent a 5-digit OTP to your email.'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $notification = __('Logged out successfully.');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('login')->with($notification);
    }
}
