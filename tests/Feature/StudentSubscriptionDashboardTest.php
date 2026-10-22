<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\app\Models\Order;
use Tests\TestCase;

class StudentSubscriptionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_student_without_a_subscription_can_open_the_subscription_dashboard(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('student.subscription'));

        $response->assertOk();
        $response->assertSee('My Subscription');
        $response->assertSee('Choose a subscription plan');
        $response->assertSee('Explore plans');
        $response->assertSee(route('subscriptions', ['plan' => 'month-1']), false);
    }

    public function test_student_can_see_active_plan_and_subscription_payment_history(): void
    {
        $student = User::factory()->create([
            'subscription_plan_id' => 'month-1',
            'subscription_started_at' => now()->subDays(5),
            'subscription_expires_at' => now()->addDays(25),
        ]);

        $order = Order::create([
            'invoice_id' => 'SUB-TEST-1001',
            'buyer_id' => $student->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'mpesa',
            'paid_amount' => 500,
            'payable_amount' => 500,
            'payable_currency' => 'KES',
            'order_type' => Order::ORDER_TYPE_SUBSCRIPTION,
            'order_details' => [
                'plan_id' => 'month-1',
                'plan_name' => '1 Month Plan',
            ],
        ]);

        $response = $this->actingAs($student)->get(route('student.subscription'));

        $response->assertOk();
        $response->assertSee('Active');
        $response->assertSee('1 Month Plan');
        $response->assertSee('Subscription payment history');
        $response->assertSee($order->invoice_id);
        $response->assertSee('Upgrade plan');
        $response->assertSee(route('student.order.show', $order->id), false);
    }
}
