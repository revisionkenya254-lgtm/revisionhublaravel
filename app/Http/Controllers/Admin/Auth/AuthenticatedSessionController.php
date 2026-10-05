<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Services\AuthAccountResolverService;
use App\Services\AuthOtpService;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class AuthenticatedSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin')->except('destroy');
    }

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('admin.auth.login');
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
        $rules = [
            'email' => 'required|email',
        ];

        $customMessages = [
            'email.required' => __('Email is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $resolution = $accountResolver->resolveAdminLogin($request->email);

        if ($resolution->isRedirect()) {
            return redirect()
                ->route($resolution->redirectRoute)
                ->withInput($request->only('email'))
                ->with([
                    'messege' => $resolution->message,
                    'alert-type' => 'error',
                ]);
        }

        if ($resolution->isInactive()) {
            return redirect()->back()->with([
                'messege' => $resolution->message,
                'alert-type' => 'error',
            ]);
        }

        if ($resolution->isNotFound()) {
            return redirect()->back()->with([
                'messege' => $resolution->message,
                'alert-type' => 'error',
            ]);
        }

        $admin = $resolution->admin;

        if (!$authOtpService->send($admin, AuthOtpService::PURPOSE_LOGIN)) {
            return redirect()->back()->with([
                'messege' => __('We could not send the OTP right now. Please try again.'),
                'alert-type' => 'error',
            ]);
        }

        return redirect()->route('admin.otp.notice', [
            'email' => $admin->email,
            'purpose' => AuthOtpService::PURPOSE_LOGIN,
        ])->with([
            'messege' => __('We sent a 5-digit OTP to your email. Use it to sign in.'),
            'alert-type' => 'success',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $notification = __('Logged out successfully.');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('admin.login')->with($notification);
    }
}
