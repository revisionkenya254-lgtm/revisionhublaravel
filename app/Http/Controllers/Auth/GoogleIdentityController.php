<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthAccountResolverService;
use App\Services\GoogleTokenVerifier;
use App\Services\TokenSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleIdentityController extends Controller
{
    public function store(
        Request $request,
        GoogleTokenVerifier $tokenVerifier,
        TokenSessionService $tokenSessions,
        AuthAccountResolverService $accountResolver,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'credential' => ['nullable', 'string', 'max:8192', 'required_without:id_token'],
            'id_token' => ['nullable', 'string', 'max:8192', 'required_without:credential'],
            'nonce' => ['nullable', 'string', 'max:255'],
            'device_installation_id' => ['nullable', 'string', 'max:128'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:android'],
            'app_version' => ['nullable', 'string', 'max:64'],
        ]);

        // GIS posts `credential`; Android Credential Manager sends `id_token`.
        $isWeb = isset($validated['credential']);
        $wantsJson = $request->expectsJson();

        if ($isWeb && isset($validated['id_token'])) {
            return $this->failure($wantsJson, __('Provide exactly one Google ID token.'), 422);
        }

        if ($isWeb && ! $this->hasValidGoogleCsrfToken($request)) {
            return $this->failure($wantsJson, __('Invalid sign-in request.'), 400);
        }

        $idToken = $validated[$isWeb ? 'credential' : 'id_token'];

        try {
            $claims = $tokenVerifier->verify($idToken);
        } catch (\Throwable $exception) {
            report($exception);
            $claims = null;
        }

        if (! $claims || empty($claims['sub']) || empty($claims['email']) || ! filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOL)) {
            return $this->failure($wantsJson, __('Invalid Google ID token.'), 401);
        }

        if (isset($validated['nonce']) && ! hash_equals($validated['nonce'], (string) ($claims['nonce'] ?? ''))) {
            return $this->failure($wantsJson, __('Invalid Google sign-in nonce.'), 401);
        }

        if ($accountResolver->isAdminAccount($claims['email'])) {
            return $this->failure(
                $wantsJson,
                __('Admin accounts must sign in from the admin login page.'),
                403,
                route('admin.login'),
            );
        }

        $result = DB::transaction(function () use ($claims) {
            $user = User::query()
                ->where('google_id', $claims['sub'])
                ->lockForUpdate()
                ->first();

            // Preserve accounts created by the previous Socialite integration.
            $user ??= User::query()
                ->whereHas('socialite', fn ($query) => $query
                    ->where('provider_name', 'google')
                    ->where('provider_id', $claims['sub']))
                ->lockForUpdate()
                ->first();

            $user ??= User::query()
                ->where('email', $claims['email'])
                ->lockForUpdate()
                ->first();

            if ($user && $user->google_id && ! hash_equals((string) $user->google_id, (string) $claims['sub'])) {
                return ['error' => __('This email is linked to another Google account.'), 'status' => 409];
            }

            if ($user && $user->status !== UserStatus::ACTIVE->value) {
                return ['error' => __('Inactive account'), 'status' => 403];
            }

            if ($user && $user->is_banned === UserStatus::BANNED->value) {
                return ['error' => __('Your account has been banned'), 'status' => 403];
            }

            if (! $user) {
                $user = User::create([
                    'role' => 'student',
                    'name' => $claims['name'] ?? $claims['email'],
                    'email' => $claims['email'],
                    'google_id' => $claims['sub'],
                    'status' => UserStatus::ACTIVE->value,
                    'is_banned' => UserStatus::UNBANNED->value,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(64)),
                ]);
            } elseif (! $user->google_id) {
                $user->forceFill([
                    'google_id' => $claims['sub'],
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }

            return ['user' => $user];
        });

        if (isset($result['error'])) {
            return $this->failure($wantsJson, $result['error'], $result['status']);
        }

        /** @var User $user */
        $user = $result['user'];

        if ($wantsJson) {
            $tokens = $tokenSessions->createSession($user, $request);

            return response()->json([
                'status' => 'success',
                'message' => __('Logged in successfully.'),
                'bearer_token' => $tokens['access_token'],
                ...$tokens,
                'user_id' => $user->id,
            ]);
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->intended($this->dashboardRedirect($user))
            ->with(['messege' => __('Logged in successfully.'), 'alert-type' => 'success']);
    }

    private function hasValidGoogleCsrfToken(Request $request): bool
    {
        $cookieToken = $request->cookie('g_csrf_token');
        $bodyToken = $request->input('g_csrf_token');

        return is_string($cookieToken)
            && is_string($bodyToken)
            && $cookieToken !== ''
            && hash_equals($cookieToken, $bodyToken);
    }

    private function failure(bool $wantsJson, string $message, int $status, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($wantsJson) {
            return response()->json(['status' => 'error', 'message' => $message], $status);
        }

        return redirect($redirect ?? route('login'))->with([
            'messege' => $message,
            'alert-type' => 'error',
        ]);
    }

    private function dashboardRedirect(User $user): string
    {
        return match ($user->role) {
            'instructor' => route('instructor.dashboard'),
            default => route('student.dashboard'),
        };
    }
}
