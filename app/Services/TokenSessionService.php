<?php

namespace App\Services;

use App\Models\DeviceSession;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class TokenSessionService
{
    public function createSession(User $user, Request $request): array
    {
        return DB::transaction(function () use ($user, $request) {
            $session = DeviceSession::create([
                'user_id' => $user->id,
                'family_id' => (string) Str::uuid(),
                'installation_id' => $this->limitedInput($request, 'device_installation_id', 128),
                'device_name' => $this->limitedInput($request, 'device_name', 255),
                'platform' => $this->limitedInput($request, 'platform', 32) ?: 'android',
                'app_version' => $this->limitedInput($request, 'app_version', 64),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1024),
                'last_seen_at' => now(),
                'expires_at' => now()->addDays((int) config('auth.refresh_token_lifetime_days', 60)),
            ]);
            $this->enforceDeviceLimit($user->id, $session->id);

            return $this->issuePair($user, $session, $request);
        });
    }

    public function rotate(string $presentedToken, Request $request): ?array
    {
        [$id, $secret] = array_pad(explode('.', $presentedToken, 2), 2, null);
        if (! $id || ! $secret) return null;

        return DB::transaction(function () use ($id, $secret, $request) {
            $token = RefreshToken::with('deviceSession.user')->lockForUpdate()->find($id);
            if (! $token || ! hash_equals($token->token_hash, $this->hash($secret))) return null;

            $session = $token->deviceSession;
            if (! $session || $session->revoked_at || now()->gte($session->expires_at) || now()->gte($token->expires_at)) return null;
            if ($token->used_at || $token->revoked_at) {
                $this->revokeSession($session, 'refresh_token_reuse');
                return null;
            }

            $token->used_at = now();
            $token->save();
            $pair = $this->issuePair($session->user, $session, $request, $token);
            $session->update(['last_seen_at' => now(), 'ip_address' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 1024)]);
            return $pair;
        });
    }

    public function revokeSession(DeviceSession $session, string $reason = 'logout'): void
    {
        if (! $session->revoked_at) $session->update(['revoked_at' => now(), 'revocation_reason' => $reason]);
        $session->refreshTokens()->whereNull('revoked_at')->update(['revoked_at' => now(), 'revocation_reason' => $reason]);
        PersonalAccessToken::where('tokenable_id', $session->user_id)->where('tokenable_type', User::class)->where('name', 'mobile:'.$session->family_id)->delete();
    }

    public function revokeAllForUser(User $user, string $reason = 'security_event'): void
    {
        DeviceSession::where('user_id', $user->id)->whereNull('revoked_at')->get()->each(fn (DeviceSession $session) => $this->revokeSession($session, $reason));
    }

    public function revokeOtherForUser(User $user, ?string $currentFamilyId, string $reason = 'logout_other_devices'): void
    {
        DeviceSession::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->when($currentFamilyId, fn ($query) => $query->where('family_id', '!=', $currentFamilyId))
            ->get()
            ->each(fn (DeviceSession $session) => $this->revokeSession($session, $reason));
    }

    private function issuePair(User $user, DeviceSession $session, Request $request, ?RefreshToken $replaced = null): array
    {
        $accessExpiry = now()->addMinutes((int) config('auth.access_token_lifetime_minutes', 20));
        $access = $user->createToken('mobile:'.$session->family_id, ['*'], $accessExpiry);
        $secret = bin2hex(random_bytes(32));
        $refresh = RefreshToken::create([
            'device_session_id' => $session->id,
            'token_hash' => $this->hash($secret),
            'issued_at' => now(),
            'expires_at' => $this->refreshExpiry($session->expires_at),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1024),
        ]);
        if ($replaced) $replaced->update(['replaced_by_id' => $refresh->id]);

        return [
            'access_token' => $access->plainTextToken,
            'access_token_expires_at' => $accessExpiry,
            'refresh_token' => $refresh->id.'.'.$secret,
            'refresh_token_expires_at' => $refresh->expires_at,
            'session_id' => $session->id,
        ];
    }

    private function enforceDeviceLimit(int $userId, string $exceptSessionId): void
    {
        $max = (int) config('auth.max_devices', 5);
        $sessions = DeviceSession::where('user_id', $userId)->whereNull('revoked_at')->orderBy('last_seen_at')->lockForUpdate()->get();
        foreach ($sessions->where('id', '!=', $exceptSessionId)->take(max(0, $sessions->count() - $max)) as $session) $this->revokeSession($session, 'device_limit');
    }

    private function refreshExpiry(CarbonInterface $sessionExpiry): CarbonInterface { return now()->addDays((int) config('auth.refresh_token_idle_days', 30))->min($sessionExpiry); }
    private function hash(string $secret): string { return hash_hmac('sha256', $secret, (string) config('app.key')); }
    private function limitedInput(Request $request, string $key, int $length): ?string
    {
        $value = $request->input($key);
        return is_string($value) ? substr($value, 0, $length) : null;
    }
}
