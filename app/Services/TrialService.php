<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class TrialService
{
    public function start(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());

            if (! $lockedUser
                || $lockedUser->role !== 'student'
                || ! $lockedUser->email_verified_at
                || $lockedUser->trial_claimed_at) {
                return false;
            }

            $startedAt = now();

            $lockedUser->forceFill([
                'trial_started_at' => $startedAt,
                'trial_expires_at' => $startedAt->copy()->addDays($this->durationDays()),
                'trial_claimed_at' => $startedAt,
            ])->save();

            $user->setRawAttributes($lockedUser->getAttributes(), true);
            $user->syncOriginal();

            return true;
        });
    }

    public function isActive(?User $user): bool
    {
        return $user !== null
            && $user->role === 'student'
            && $user->trial_started_at !== null
            && $user->trial_started_at->lte(now())
            && $user->trial_expires_at !== null
            && $user->trial_expires_at->isFuture();
    }

    public function remainingDays(User $user): int
    {
        if (! $this->isActive($user)) {
            return 0;
        }

        return max(1, (int) ceil(now()->diffInSeconds($user->trial_expires_at, false) / 86400));
    }

    public function durationDays(): int
    {
        return max(1, (int) config('subscriptions.trial_days', 7));
    }
}
