<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SabbathPlanService
{
    public function plan(): array
    {
        return [
            'id' => (string) config('sabbath.id', 'sabbath'),
            'name' => (string) config('sabbath.name', 'Sabbath Plan'),
            'amount' => 0,
            'price' => __('Free'),
            'billing' => __('Automatically available every Sabbath'),
            'timezone' => $this->timezone(),
            'visible_to_students' => false,
        ];
    }

    public function isActiveFor(?User $user, ?CarbonInterface $at = null): bool
    {
        return $user !== null
            && $user->role === 'student'
            && $this->isActive($at);
    }

    public function isActive(?CarbonInterface $at = null): bool
    {
        if (! config('sabbath.enabled', true)) {
            return false;
        }

        $window = $this->windowFor($at);
        $local = $this->localTime($at);

        return $local->greaterThanOrEqualTo($window['starts_at'])
            && $local->lessThan($window['ends_at']);
    }

    /**
     * Return the Sabbath window in the configured local timezone for the week
     * containing the supplied instant.
     *
     * @return array{starts_at: Carbon, ends_at: Carbon}
     */
    public function windowFor(?CarbonInterface $at = null): array
    {
        $local = $this->localTime($at);
        $weekStart = $local->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $startsAt = $weekStart->copy()
            ->addDays(max(0, (int) config('sabbath.starts.day', 5) - 1))
            ->setTimeFromTimeString((string) config('sabbath.starts.time', '18:00'));
        $endsAt = $weekStart->copy()
            ->addDays(max(0, (int) config('sabbath.ends.day', 6) - 1))
            ->setTimeFromTimeString((string) config('sabbath.ends.time', '19:00'));

        return ['starts_at' => $startsAt, 'ends_at' => $endsAt];
    }

    /** @return array{starts_at: Carbon, ends_at: Carbon} */
    public function currentOrNextWindow(?CarbonInterface $at = null): array
    {
        $local = $this->localTime($at);
        $window = $this->windowFor($local);

        if ($local->greaterThanOrEqualTo($window['ends_at'])) {
            return [
                'starts_at' => $window['starts_at']->copy()->addWeek(),
                'ends_at' => $window['ends_at']->copy()->addWeek(),
            ];
        }

        return $window;
    }

    private function localTime(?CarbonInterface $at = null): Carbon
    {
        return Carbon::instance($at ?? now())
            ->copy()
            ->setTimezone($this->timezone());
    }

    private function timezone(): string
    {
        return (string) config('sabbath.timezone', 'Africa/Nairobi');
    }
}
