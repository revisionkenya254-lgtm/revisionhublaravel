<?php

namespace App\Services\Ai;

class AiCreditPurchaseService
{
    /**
     * Base conversion used for custom AI credit top-ups.
     */
    public function creditsPerKes(): int
    {
        return max(1, (int) config('ai.purchase.credits_per_kes', 10));
    }

    public function minimumAmount(): int
    {
        return max(1, (int) config('ai.purchase.minimum_amount', 10));
    }

    public function packageAmounts(): array
    {
        return [10, 20, 50, 100, 200, 500, 1000, 2000, 5000];
    }

    private function packageDefinitions(): array
    {
        return [
            10 => 100,
            20 => 220,
            50 => 600,
            100 => 1_300,
            200 => 2_800,
            500 => 7_500,
            1000 => 16_000,
            2000 => 34_000,
            5000 => 95_000,
        ];
    }

    public function packages(): array
    {
        return array_map(function (int $amount) {
            $credits = $this->packageDefinitions()[$amount] ?? $this->creditsForAmount($amount);

            return [
                'id' => 'kes-' . $amount,
                'amount' => $amount,
                'credits' => $credits,
                'title' => 'KES ' . number_format($amount),
                'subtitle' => number_format($credits) . ' credits',
                'price' => 'KES ' . number_format($amount),
                'label' => $amount >= 1000 ? __('Power Pack') : ($amount >= 200 ? __('Value Pack') : __('Quick Top Up')),
                'description' => __('Recharge your AI balance instantly.'),
                'icon' => match (true) {
                    $amount <= 20 => 'fa-bolt',
                    $amount <= 100 => 'fa-battery-three-quarters',
                    $amount <= 500 => 'fa-wallet',
                    $amount <= 2000 => 'fa-layer-group',
                    default => 'fa-gem',
                },
                'tone' => match (true) {
                    $amount <= 20 => 'is-violet',
                    $amount <= 100 => 'is-green',
                    $amount <= 500 => 'is-orange',
                    $amount <= 2000 => 'is-blue',
                    default => 'is-pink',
                },
                'badge' => $amount === 500 ? __('Most Popular') : null,
            ];
        }, $this->packageAmounts());
    }

    public function creditsForAmount(int $amount): int
    {
        return max(0, $amount * $this->creditsPerKes());
    }

    public function resolve(int $amount): ?array
    {
        $amount = max(0, $amount);

        if ($amount < $this->minimumAmount()) {
            return null;
        }

        $package = collect($this->packages())->firstWhere('amount', $amount);

        if ($package) {
            return $package;
        }

        return [
            'id' => 'custom-' . $amount,
            'amount' => $amount,
            'credits' => $this->creditsForAmount($amount),
            'title' => 'KES ' . number_format($amount),
            'subtitle' => number_format($this->creditsForAmount($amount)) . ' credits',
            'price' => 'KES ' . number_format($amount),
            'label' => __('Custom Recharge'),
            'description' => __('Choose any amount and top up your AI balance.'),
            'icon' => 'fa-pen',
            'tone' => 'is-violet',
            'badge' => null,
            'is_custom' => true,
        ];
    }
}

