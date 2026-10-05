<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BasicPayment\app\Services\PaymentMethodService;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;

class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => collect(revisionHubSubscriptionPlans())->map(function (array $plan): array {
                return [
                    ...$plan,
                    'ai_bonus_credits' => revisionHubSubscriptionBonusCredits((int) $plan['amount']),
                ];
            })->values(),
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $active = hasActiveSubscription($user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'active' => $active,
                'plan' => filled($user->subscription_plan_id)
                    ? revisionHubSubscriptionPlan($user->subscription_plan_id)
                    : null,
                'plan_id' => $user->subscription_plan_id,
                'started_at' => $user->subscription_started_at?->toIso8601String(),
                'expires_at' => $user->subscription_expires_at?->toIso8601String(),
            ],
        ]);
    }

    /** Create a server-priced subscription order ready for an M-Pesa STK Push. */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'string'],
        ]);

        $plan = revisionHubSubscriptionPlan($validated['plan_id']);
        if (!$plan) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected subscription plan is invalid.',
            ], 422);
        }

        $user = $request->user();
        $quote = revisionHubSubscriptionUpgradeQuote($user, $plan);
        if (! $quote['allowed']) {
            return response()->json([
                'status' => 'error',
                'message' => $quote['message'],
            ], 422);
        }

        $paymentService = app(PaymentMethodService::class);
        if (!$paymentService->isActive(PaymentMethodService::MPESA_STK_PUSH)) {
            return response()->json([
                'status' => 'error',
                'message' => 'M-Pesa payments are currently unavailable.',
            ], 503);
        }

        $order = DB::transaction(function () use ($user, $plan, $quote, $paymentService): Order {
            // Repeated taps before an STK Push is sent return the same order.
            $existing = $user->orders()
                ->where('order_type', Order::ORDER_TYPE_SUBSCRIPTION)
                ->where('payment_method', PaymentMethodService::MPESA_STK_PUSH)
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->whereNull('transaction_id')
                ->where('order_details->plan_id', $plan['id'])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $charge = $paymentService->getPayableAmount(
                PaymentMethodService::MPESA_STK_PUSH,
                $quote['payable_amount'],
                'KES'
            );
            $paidAmount = (int) ceil((float) $charge->payable_with_charge);

            $order = Order::create([
                'invoice_id' => Str::random(10),
                'buyer_id' => $user->id,
                'status' => 'pending',
                'payment_method' => PaymentMethodService::MPESA_STK_PUSH,
                'payment_status' => 'pending',
                'payable_amount' => $quote['payable_amount'],
                'gateway_charge' => $paidAmount - $quote['payable_amount'],
                'payable_with_charge' => $paidAmount,
                'paid_amount' => $paidAmount,
                'payable_currency' => 'KES',
                'conversion_rate' => 1,
                'order_type' => Order::ORDER_TYPE_SUBSCRIPTION,
                'order_details' => [
                    'plan_id' => $plan['id'],
                    'plan_name' => $plan['name'],
                    'months' => $plan['months'],
                    'plan_amount' => $quote['base_amount'],
                    'upgrade_type' => $quote['kind'],
                    'previous_plan_id' => data_get($quote, 'current_plan.id'),
                    'prorated_credit' => $quote['credit_amount'],
                ],
            ]);

            // Payment processing requires an order item; it is deliberately
            // non-product so a subscription never creates a course enrollment.
            OrderItem::create([
                'order_id' => $order->id,
                'qty' => 1,
                'price' => $quote['payable_amount'],
                'item_type' => 'subscription',
                'commission_rate' => 0,
            ]);

            return $order;
        });

        return response()->json([
            'status' => 'success',
            'state' => 'ready_for_payment',
            'message' => 'Subscription order created. Enter your M-Pesa number to continue.',
            'data' => $this->orderData($order, $plan),
        ], 201);
    }

    public function order(Request $request, string $invoiceId): JsonResponse
    {
        $order = $request->user()->orders()
            ->where('invoice_id', $invoiceId)
            ->where('order_type', Order::ORDER_TYPE_SUBSCRIPTION)
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Subscription order not found.'], 404);
        }

        $plan = revisionHubSubscriptionPlan((string) data_get($order->order_details, 'plan_id'));

        return response()->json([
            'status' => 'success',
            'data' => $this->orderData($order, $plan),
        ]);
    }

    private function orderData(Order $order, ?array $plan): array
    {
        return [
            'order_id' => $order->invoice_id,
            'state' => $order->payment_status === 'paid' && $order->status === 'completed'
                ? 'paid'
                : ($order->status === 'declined' ? 'failed' : 'ready_for_payment'),
            'payment_status' => $order->payment_status,
            'amount' => (int) $order->paid_amount,
            'currency' => $order->payable_currency,
            'plan' => $plan,
            'upgrade_type' => data_get($order->order_details, 'upgrade_type', 'new'),
            'base_amount' => (int) data_get($order->order_details, 'plan_amount', $order->payable_amount),
            'prorated_credit' => (int) data_get($order->order_details, 'prorated_credit', 0),
            'ai_bonus_credits' => $plan ? revisionHubSubscriptionBonusCredits((int) data_get($order->order_details, 'plan_amount', $plan['amount'])) : 0,
        ];
    }
}
