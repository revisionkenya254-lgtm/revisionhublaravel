<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class SabbathDownloadTagService
{
    public function __construct(private readonly SabbathPlanService $sabbathPlan) {}

    public function permanentPolicy(): array
    {
        return [
            'type' => 'permanent',
            'temporary' => false,
            'sabbath_download' => false,
            'tag' => null,
            'delete_after' => null,
        ];
    }

    public function temporaryPolicy(
        User $user,
        string $resourceType,
        int|string $resourceId,
        ?CarbonInterface $at = null
    ): array {
        $issuedAt = Carbon::instance($at ?? now())->copy();
        $window = $this->sabbathPlan->windowFor($issuedAt);
        $deleteAfter = $window['ends_at']->copy()->utc();
        $payload = [
            'version' => 1,
            'purpose' => 'sabbath_download',
            'user_id' => (int) $user->id,
            'resource_type' => $resourceType,
            'resource_id' => (string) $resourceId,
            'issued_at' => $issuedAt->copy()->utc()->getTimestamp(),
            'delete_after' => $deleteAfter->getTimestamp(),
            'nonce' => (string) Str::uuid(),
        ];

        return [
            'type' => 'sabbath_temporary',
            'temporary' => true,
            'sabbath_download' => true,
            'tag' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            'delete_after' => $deleteAfter->toIso8601String(),
        ];
    }

    public function validate(
        string $tag,
        string $resourceType,
        int|string $resourceId,
        ?CarbonInterface $at = null
    ): ?array {
        try {
            $payload = json_decode(Crypt::decryptString($tag), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        $now = Carbon::instance($at ?? now())->copy()->utc()->getTimestamp();

        if (($payload['purpose'] ?? null) !== 'sabbath_download'
            || ($payload['resource_type'] ?? null) !== $resourceType
            || (string) ($payload['resource_id'] ?? '') !== (string) $resourceId
            || ! is_numeric($payload['delete_after'] ?? null)
            || $now >= (int) $payload['delete_after']) {
            return null;
        }

        return $payload;
    }

    public function responseHeaders(array $policy): array
    {
        if (! ($policy['temporary'] ?? false)) {
            return [];
        }

        return [
            'X-RevisionHub-Download-Policy' => 'sabbath-temporary',
            'X-RevisionHub-Sabbath-Tag' => (string) $policy['tag'],
            'X-RevisionHub-Delete-After' => (string) $policy['delete_after'],
        ];
    }
}
