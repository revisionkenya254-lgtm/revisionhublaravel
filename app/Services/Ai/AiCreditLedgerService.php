<?php

namespace App\Services\Ai;

use App\Models\AiCreditLedger;
use App\Models\AiRequest;
use App\Models\User;
use App\Services\Ai\AiCreditPurchaseService;
use Modules\Order\app\Models\Order;

class AiCreditLedgerService
{
    public const TYPE_MONTHLY_GRANT = 'monthly_grant';
    public const TYPE_USAGE = 'usage';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SUBSCRIPTION_BONUS = 'subscription_bonus';

    public function currentBalance(User $user): int
    {
        return (int) AiCreditLedger::query()
            ->where('user_id', $user->id)
            ->sum('amount');
    }

    public function monthlyAllowance(User $user): int
    {
        $planAllowanceMap = [
            'month-1' => 1000,
            'month-3' => 3000,
            'month-6' => 6000,
            'year-1' => 12000,
        ];

        $planId = $user->subscription_plan_id;

        if (filled($planId) && isset($planAllowanceMap[$planId])) {
            return $planAllowanceMap[$planId];
        }

        return (int) config('ai.credits.default_monthly_allowance', 3000);
    }

    public function subscriptionBonusCredits(int $amount): int
    {
        $rate = max(0, (float) config('ai.purchase.subscription_bonus_fraction', 0.2));
        $purchaseService = app(AiCreditPurchaseService::class);
        $directCredits = (int) data_get($purchaseService->resolve($amount), 'credits', $purchaseService->creditsForAmount($amount));

        return (int) round($directCredits * $rate);
    }

    public function subscriptionBonusReference(Order $order): string
    {
        return 'subscription-bonus-' . $order->invoice_id;
    }

    public function ensureMonthlyGrant(User $user): AiCreditLedger
    {
        $periodKey = now()->format('Y-m');

        $existingGrant = AiCreditLedger::query()
            ->where('user_id', $user->id)
            ->where('type', self::TYPE_MONTHLY_GRANT)
            ->where('period_key', $periodKey)
            ->first();

        if ($existingGrant) {
            return $existingGrant;
        }

        $amount = $this->monthlyAllowance($user);
        $balanceAfter = $this->currentBalance($user) + $amount;

        return AiCreditLedger::create([
            'user_id' => $user->id,
            'type' => self::TYPE_MONTHLY_GRANT,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'period_key' => $periodKey,
            'reference' => 'grant-' . $periodKey,
            'note' => __('Monthly AI credit allowance'),
            'metadata' => [
                'allowance' => $amount,
                'plan_id' => $user->subscription_plan_id,
            ],
            'created_at' => now(),
        ]);
    }

    public function recordUsage(User $user, AiRequest $request): ?AiCreditLedger
    {
        $creditsUsed = max(0, (int) $request->credits_used);

        if ($creditsUsed <= 0) {
            return null;
        }

        $this->ensureMonthlyGrant($user);

        $currentBalance = $this->currentBalance($user);
        $amount = -$creditsUsed;

        return AiCreditLedger::create([
            'user_id' => $user->id,
            'ai_request_id' => $request->id,
            'conversation_id' => $request->conversation_id,
            'type' => self::TYPE_USAGE,
            'amount' => $amount,
            'balance_after' => $currentBalance + $amount,
            'reference' => $request->provider . ':' . $request->id,
            'note' => $this->buildUsageNote($request),
            'metadata' => array_filter([
                'provider' => $request->provider,
                'mode' => $request->mode,
                'model' => $request->model,
                'credits_used' => $creditsUsed,
                'estimated_cost' => $request->estimated_cost,
            ], static fn ($value) => $value !== null && $value !== ''),
            'created_at' => now(),
        ]);    
    }

    public function recordPurchase(User $user, int $credits, array $metadata = []): ?AiCreditLedger
    {
        return $this->recordOnce($user, self::TYPE_PURCHASE, max(0, $credits), $metadata, __('Purchased AI credits'));
    }

    public function recordSubscriptionBonus(User $user, Order $order, int $credits, array $metadata = []): ?AiCreditLedger
    {
        $reference = $metadata['reference'] ?? $this->subscriptionBonusReference($order);
        $metadata['reference'] = $reference;
        $metadata['order_id'] = $metadata['order_id'] ?? $order->id;
        $metadata['invoice_id'] = $metadata['invoice_id'] ?? $order->invoice_id;
        $metadata['plan_id'] = $metadata['plan_id'] ?? data_get($order->order_details, 'plan_id');

        return $this->recordOnce($user, self::TYPE_SUBSCRIPTION_BONUS, max(0, $credits), $metadata, __('Subscription AI bonus credits'));
    }

    private function creditsForAmount(int $amount, float $rate): int
    {
        return max(0, (int) round($amount * $rate));
    }

    private function recordOnce(User $user, string $type, int $credits, array $metadata, string $defaultNote): ?AiCreditLedger
    {
        $credits = max(0, $credits);
        if ($credits <= 0) {
            return null;
        }

        $reference = (string) data_get($metadata, 'reference', '');

        $query = AiCreditLedger::query()
            ->where('user_id', $user->id)
            ->where('type', $type);

        if (filled($reference)) {
            $query->where('reference', $reference);
        }

        $existing = $query->first();
        if ($existing) {
            return $existing;
        }

        $currentBalance = $this->currentBalance($user);

        return AiCreditLedger::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $credits,
            'balance_after' => $currentBalance + $credits,
            'reference' => filled($reference) ? $reference : ($type . '-' . now()->timestamp),
            'note' => data_get($metadata, 'note', $defaultNote),
            'metadata' => array_filter($metadata, static fn ($value) => $value !== null && $value !== ''),
            'created_at' => now(),
        ]);
    }

    private function buildUsageNote(AiRequest $request): string
    {
        return match (true) {
            str_contains(strtolower((string) $request->mode), 'image') => __('AI image generation'),
            str_contains(strtolower((string) $request->mode), 'pdf') => __('PDF / document analysis'),
            str_contains(strtolower((string) $request->mode), 'homework') => __('Homework help'),
            default => __('AI chat usage'),
        };
    }
}

