<?php

namespace App\Http\Controllers\API;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserResource;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\DeviceSession;
use App\Services\AuthOtpService;
use App\Services\DeviceService;
use App\Services\GoogleTokenVerifier;
use App\Services\MailSenderService;
use App\Services\TokenSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\GlobalSetting\app\Models\MarketingSetting;
use Modules\GlobalSetting\app\Models\Setting;
use Modules\Location\app\Models\Country;

class AuthenticatedController extends Controller
{
    public function register(Request $request, AuthOtpService $authOtpService): JsonResponse
    {
        $request->merge([
            'phone' => normalizeRegistrationPhone($request->input('phone')),
        ]);

        // This app serves Kenya by default; callers may still override it explicitly.
        if (!$request->filled('country_id')) {
            $request->merge([
                'country_id' => Country::where('name', 'Kenya')->value('id'),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'phone' => ['required', 'string', 'max:30'],
        ], [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email already exist',
            'country_id.required' => 'You must select a country.',
            'country_id.integer' => 'Country ID must be an integer.',
            'country_id.exists' => 'The selected country is invalid.',
            'phone.required' => 'Phone number is required',
            'phone.max' => 'Phone number cannot be longer than 30 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'role' => 'student',
                'name' => $request->name,
                'email' => $request->email,
                'country_id' => $request->country_id,
                'phone' => $request->phone,
                'status' => 'active',
                'is_banned' => 'no',
                // Password authentication is no longer used; keep the required legacy column populated.
                'password' => Hash::make(Str::random(64)),
            ]);

            if (!$authOtpService->send($user, AuthOtpService::PURPOSE_REGISTER)) {
                throw new \Exception('Failed to send email.');
            }

            DB::commit();

            $googleTagManagerStatus = Setting::where('key', 'google_tagmanager_status')->value('value');
            $marketingSettingRegister = MarketingSetting::where('key', 'register')->value('value');
            if ($user && $googleTagManagerStatus == 'active' && $marketingSettingRegister) {
                session()->put('registerUser', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country_id' => $user->country_id,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'A 5-digit OTP has been sent to your email address.',
                'purpose' => AuthOtpService::PURPOSE_REGISTER,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed because the OTP email could not be sent. Please try again later.',
            ], 500);
        }
    }

    public function verifyOtp(Request $request, AuthOtpService $authOtpService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:5'],
            'purpose' => ['required', 'in:'.AuthOtpService::PURPOSE_LOGIN.','.AuthOtpService::PURPOSE_REGISTER],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !$authOtpService->verify($user, $request->otp, $request->purpose)) {
            return response()->json(['status' => 'error', 'message' => 'The OTP is invalid or has expired.'], 422);
        }

        if ($user->status != UserStatus::ACTIVE->value) {
            return response()->json(['status' => 'error', 'message' => 'Inactive account'], 403);
        }

        if ($user->is_banned == UserStatus::BANNED->value) {
            return response()->json(['status' => 'error', 'message' => 'Your account has been banned'], 403);
        }

        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        $authOtpService->clear($user);

        $tokens = app(TokenSessionService::class)->createSession($user, $request);

        return response()->json([
            'status' => 'success',
            'message' => $request->purpose === AuthOtpService::PURPOSE_REGISTER
                ? 'Account verified successfully.'
                : 'Logged in successfully.',
            // Retained during the mobile-client migration window.
            'bearer_token' => $tokens['access_token'],
            ...$tokens,
            'user_id' => $user->id,
        ], 200);
    }

    public function resendOtp(Request $request, AuthOtpService $authOtpService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'purpose' => ['required', 'in:'.AuthOtpService::PURPOSE_LOGIN.','.AuthOtpService::PURPOSE_REGISTER],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Email does not exist'], 404);
        }

        if (!$authOtpService->send($user, $request->purpose)) {
            return response()->json(['status' => 'error', 'message' => 'Failed to send OTP. Please try again later.'], 500);
        }

        return response()->json(['status' => 'success', 'message' => 'A new OTP has been sent to your email address.'], 200);
    }

    public function forgetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->forget_password_token = Str::random(100);
            $user->save();

            if (!(new MailSenderService)->sendUserForgetPasswordFromTrait($user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send password reset email. Please try again later.',
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'A password reset link has been send to your mail',
            ], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'Email does not exist'], 404);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'forget_password_token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', 'min:4', 'max:100'],
        ], [
            'email.required' => 'Email is required',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be 4 characters',
            'forget_password_token.required' => 'Forget password token is required',
            'password.confirmed' => 'Confirm password does not match',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $user = User::select('id', 'name', 'email', 'forget_password_token')
            ->where('forget_password_token', $request->forget_password_token)
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token, please try again',
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->forget_password_token = null;
        $user->save();
        app(TokenSessionService::class)->revokeAllForUser($user, 'password_reset');

        return response()->json([
            'status' => 'success',
            'message' => 'Password Reset successfully',
        ], 200);
    }

    public function login(Request $request, AuthOtpService $authOtpService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Email is required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Email does not exist'], 404);
        }

        if ($user->status != UserStatus::ACTIVE->value) {
            return response()->json(['status' => 'error', 'message' => 'Inactive account'], 403);
        }

        if ($user->is_banned == UserStatus::BANNED->value) {
            return response()->json(['status' => 'error', 'message' => 'Your account has been banned'], 403);
        }

        $purpose = $user->email_verified_at ? AuthOtpService::PURPOSE_LOGIN : AuthOtpService::PURPOSE_REGISTER;

        if (!$authOtpService->send($user, $purpose)) {
            return response()->json(['status' => 'error', 'message' => 'Failed to send OTP. Please try again later.'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => $purpose === AuthOtpService::PURPOSE_LOGIN
                ? 'A 5-digit OTP has been sent to your email address.'
                : 'Your account is not verified yet. A 5-digit OTP has been sent to your email address.',
            'purpose' => $purpose,
        ], 200);
    }

    public function googleLogin(Request $request, GoogleTokenVerifier $tokenVerifier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        try {
            $claims = $tokenVerifier->verify($request->string('id_token')->toString());
        } catch (\Throwable $exception) {
            report($exception);
            $claims = null;
        }

        if (!$claims || empty($claims['sub']) || empty($claims['email']) || empty($claims['email_verified'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid Google ID token.'], 401);
        }

        $user = User::where('google_id', $claims['sub'])->first();

        if (!$user) {
            $user = User::where('email', $claims['email'])->first();
        }

        if ($user && $user->google_id && $user->google_id !== $claims['sub']) {
            return response()->json(['status' => 'error', 'message' => 'This email is linked to another Google account.'], 409);
        }

        if ($user && $user->status != UserStatus::ACTIVE->value) {
            return response()->json(['status' => 'error', 'message' => 'Inactive account'], 403);
        }

        if ($user && $user->is_banned == UserStatus::BANNED->value) {
            return response()->json(['status' => 'error', 'message' => 'Your account has been banned'], 403);
        }

        if (!$user) {
            $user = User::create([
                'role' => 'student',
                'name' => $claims['name'] ?? $claims['email'],
                'email' => $claims['email'],
                'google_id' => $claims['sub'],
                'status' => 'active',
                'is_banned' => 'no',
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(64)),
            ]);
        } elseif (!$user->google_id) {
            $user->google_id = $claims['sub'];
            $user->email_verified_at ??= now();
            $user->save();
        }

        $tokens = app(TokenSessionService::class)->createSession($user, $request);

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully.',
            'bearer_token' => $tokens['access_token'],
            ...$tokens,
            'user_id' => $user->id,
        ], 200);
    }

    public function refresh(Request $request, TokenSessionService $tokenSessions): JsonResponse
    {
        $validator = Validator::make($request->all(), ['refresh_token' => ['required', 'string', 'max:512']]);
        if ($validator->fails()) return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);

        $tokens = $tokenSessions->rotate($request->string('refresh_token')->toString(), $request);
        if (! $tokens) return response()->json(['status' => 'error', 'message' => 'Your session has expired. Please sign in again.'], 401);

        return response()->json(['status' => 'success', ...$tokens]);
    }

    public function logout(TokenSessionService $tokenSessions): JsonResponse
    {
        $user = auth()->user();
        $currentToken = $user->currentAccessToken();

        if ($currentToken && str_starts_with($currentToken->name, 'mobile:')) {
            $familyId = substr($currentToken->name, strlen('mobile:'));
            $session = \App\Models\DeviceSession::where('user_id', $user->id)->where('family_id', $familyId)->first();
            if ($session) $tokenSessions->revokeSession($session);
        } elseif ($currentToken) {
            UserDevice::where('user_id', $user->id)
                ->where('session_id', 'like', $currentToken->id . '|%')
                ->delete();
            $currentToken->delete();
        }

        return response()->json(['status' => 'success', 'message' => 'Logged out successfully.'], 200);
    }

    public function logoutAllApp(TokenSessionService $tokenSessions): JsonResponse
    {
        $user = auth()->user();

        $tokenSessions->revokeAllForUser($user, 'logout_all');
        $user->devices()->delete(); // Legacy records during the migration window.
        $user->tokens()->delete();

        return response()->json(['status' => 'success', 'message' => 'Logged out successfully.'], 200);
    }

    public function checkAccessToken(): JsonResponse
    {
        return response()->json(['status' => 'success'], 200);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => new UserResource(auth()->user()),
        ], 200);
    }

    public function devices(DeviceService $deviceService): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'status' => 'success',
            'data' => DeviceSession::where('user_id', $user->id)->whereNull('revoked_at')->orderByDesc('last_seen_at')->get()->map(function (DeviceSession $device) {
                return [
                    'id' => $device->id,
                    'session_id' => (string) $device->id,
                    'ip_address' => (string) $device->ip_address,
                    'user_agent' => (string) $device->user_agent,
                    'device_type' => (string) $device->platform,
                    'device_name' => (string) $device->device_name,
                    'created_at' => optional($device->created_at)->toDateTimeString(),
                    'updated_at' => optional($device->last_seen_at)->toDateTimeString(),
                ];
            })->values(),
        ], 200);
    }

    public function destroyDevice(string $sessionId, DeviceService $deviceService, TokenSessionService $tokenSessions): JsonResponse
    {
        $session = DeviceSession::where('user_id', auth()->id())->where('id', $sessionId)->first();
        if ($session) {
            $tokenSessions->revokeSession($session, 'remote_logout');
            return response()->json(['status' => 'success', 'message' => 'Device logged out successfully.'], 200);
        }

        $device = UserDevice::where('user_id', auth()->id())->where('session_id', $sessionId)->first();

        if (! $device) {
            return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
        }

        $deviceService->destroy($device);

        return response()->json(['status' => 'success', 'message' => 'Device logged out successfully.'], 200);
    }

    public function destroyAllDevices(DeviceService $deviceService, TokenSessionService $tokenSessions): JsonResponse
    {
        $token = auth()->user()->currentAccessToken();
        $familyId = $token && str_starts_with($token->name, 'mobile:') ? substr($token->name, strlen('mobile:')) : null;
        $tokenSessions->revokeOtherForUser(auth()->user(), $familyId);
        $deviceService->destroyAll();

        return response()->json(['status' => 'success', 'message' => 'Logged out from all other devices.'], 200);
    }
}
