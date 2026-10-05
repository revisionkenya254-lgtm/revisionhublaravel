<?php

namespace Tests\Unit;

use App\Models\AiCreditLedger;
use App\Models\User;
use App\Services\Ai\AiCreditLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\app\Models\Order;
use Tests\TestCase;

class AiCreditLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_bonus_uses_a_fraction_of_direct_package_credits(): void
    {
        $service = app(AiCreditLedgerService::class);

        $this->assertSame(1500, $service->subscriptionBonusCredits(500));
        $this->assertSame(3200, $service->subscriptionBonusCredits(1000));
        $this->assertSame(5000, $service->subscriptionBonusCredits(2500));
        $this->assertSame(8000, $service->subscriptionBonusCredits(4000));
    }

    public function test_subscription_bonus_is_recorded_only_once_per_order(): void
    {
        $service = app(AiCreditLedgerService::class);
        $user = User::factory()->create();

        $order = Order::create([
            'invoice_id' => 'INV-SUB-001',
            'buyer_id' => $user->id,
            'payment_method' => 'paypal',
            'payment_status' => 'paid',
            'status' => 'completed',
            'payable_amount' => 500,
            'payable_currency' => 'KES',
            'order_type' => Order::ORDER_TYPE_SUBSCRIPTION,
            'order_details' => [
                'plan_id' => 'month-1',
            ],
        ]);

        $first = $service->recordSubscriptionBonus($user, $order, 1500, [
            'reference' => $service->subscriptionBonusReference($order),
            'note' => __('Subscription AI bonus credits'),
            'order_id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'plan_id' => 'month-1',
            'amount' => $order->payable_amount,
            'currency' => $order->payable_currency,
            'bonus_fraction' => revisionHubSubscriptionBonusFraction(),
        ]);

        $second = $service->recordSubscriptionBonus($user, $order, 1500, [
            'reference' => $service->subscriptionBonusReference($order),
            'note' => __('Subscription AI bonus credits'),
            'order_id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'plan_id' => 'month-1',
            'amount' => $order->payable_amount,
            'currency' => $order->payable_currency,
            'bonus_fraction' => revisionHubSubscriptionBonusFraction(),
        ]);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, AiCreditLedger::query()->where('user_id', $user->id)->where('type', AiCreditLedgerService::TYPE_SUBSCRIPTION_BONUS)->count());
    }
}

