<?php

namespace App\Services\Push;

use App\Models\PushDeviceToken;
use Illuminate\Database\Eloquent\Builder;

class PushAudience
{
    public function eligibleTokens(?string $educationLevel = null): Builder
    {
        return PushDeviceToken::query()
            ->whereHas('user', function (Builder $query) use ($educationLevel) {
                $query->where('role', 'student')
                    ->where('status', 'active')
                    ->where('is_banned', 'no');

                if ($educationLevel !== null) {
                    $query->where('education_level', $educationLevel);
                }
            });
    }

    /**
     * @return array{all: int, by_level: array<string, int>}
     */
    public function countsByEducationLevel(): array
    {
        $counts = $this->eligibleTokens()
            ->join('users', 'users.id', '=', 'push_device_tokens.user_id')
            ->selectRaw('users.education_level, COUNT(push_device_tokens.id) as token_count')
            ->groupBy('users.education_level')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->education_level => (int) $row->token_count]);

        return [
            'all' => $counts->sum(),
            'by_level' => $counts->filter(fn ($count, $level) => $level !== '')->all(),
        ];
    }
}
