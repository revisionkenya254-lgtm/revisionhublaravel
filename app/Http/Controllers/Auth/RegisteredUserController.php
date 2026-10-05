<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\CustomRecaptcha;
use App\Services\AuthOtpService;
use Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, AuthOtpService $authOtpService): RedirectResponse
    {
        $setting = Cache::get('setting');
        $request->merge([
            'phone' => normalizeRegistrationPhone($request->input('phone')),
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', 'min:4', 'max:100'],
            'g-recaptcha-response' => $setting->recaptcha_status == 'active' ? ['required', new CustomRecaptcha()] : '',
        ], [
            'name.required' => __('Name is required'),
            'email.required' => __('Email is required'),
            'email.unique' => __('Email already exist'),
            'country_id.required' => __('You must select a country.'),
            'country_id.integer' => __('Country ID must be an integer.'),
            'country_id.exists' => __('The selected country is invalid.'),
            'phone.required' => __('Phone number is required'),
            'phone.max' => __('Phone number cannot be longer than :max characters.', ['max' => 30]),
            'password.required' => __('Password is required'),
            'password.confirmed' => __('Confirm password does not match'),
            'password.min' => __('You have to provide minimum 4 character password'),
            'g-recaptcha-response.required' => __('Please complete the recaptcha to submit the form'),
        ]);

        $user = User::create([
            'role' => 'student',
            'name' => $request->name,
            'email' => $request->email,
            'country_id' => $request->country_id,
            'phone' => $request->phone,
            'status' => 'active',
            'is_banned' => 'no',
            'password' => Hash::make($request->password),
        ]);

        $settings = cache()->get('setting');
        $marketingSettings = cache()->get('marketing_setting');
        if ($user && $settings->google_tagmanager_status == 'active' && $marketingSettings->register) {
            $register_user = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country_id' => $user->country_id,
            ];
            session()->put('registerUser', $register_user);
        }

        if (!$authOtpService->send($user, AuthOtpService::PURPOSE_REGISTER)) {
            $user->delete();

            $notification = __('We could not send the OTP email right now. Please try again.');
            $notification = ['messege' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->withInput($request->except('password', 'password_confirmation'))->with($notification);
        }

        return redirect()->route('auth.otp.notice', [
            'email' => $user->email,
            'purpose' => AuthOtpService::PURPOSE_REGISTER,
        ])->with([
            'messege' => __('We sent a 5-digit OTP to your email. Enter it below to activate your account.'),
            'alert-type' => 'success',
        ]);
    }
}
