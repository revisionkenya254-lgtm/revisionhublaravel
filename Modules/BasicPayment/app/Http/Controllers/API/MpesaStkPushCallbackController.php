<?php

namespace Modules\BasicPayment\app\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiCreditLedgerService;
use App\Models\Course;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\BasicPayment\app\Http\Controllers\PaymentController;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Traits\GiftOrderTraits;
use Modules\Refund\app\Models\InstructorEarningsHold;

class MpesaStkPushCallbackController extends Controller
{
    use GiftOrderTraits;

    private AiCreditLedgerService $aiCreditLedgerService;

    public function __construct()
    {
        $this->aiCreditLedgerService = app(AiCreditLedgerService::class);
    }

    public function __invoke(Request $request)
    {
        return $this->processCallbackPayload($request->all());
    }

    private function processCallbackPayload(array $payload): JsonResponse
    {
        try {
            $callback = data_get($payload, 'Body.stkCallback', []);
            $checkoutRequestId = data_get($callback, 'CheckoutRequestID');
            $resultCode = (int) data_get($callback, 'ResultCode', 1);

            if (!$checkoutRequestId) {
                info('M-Pesa STK Push Callback: Missing CheckoutRequestID.');
                return response()->json(['message' => 'Missing CheckoutRequestID'], 422);
            }

            $order = Order::with(['orderItems', 'user'])
                ->where(function ($query) use ($checkoutRequestId) {
                    $query->where('transaction_id', $checkoutRequestId)
                        ->orWhere('payment_details', 'like', '%' . $checkoutRequestId . '%');
                })
                ->first();

            if (!$order) {
                info('M-Pesa STK Push Callback: Order not found.');
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($order->payment_status === 'paid' && $order->status === 'completed') {
                return response()->json(['message' => 'Payment already processed']);
            }

            if ($resultCode !== 0) {
                $order->update([
                    'status' => 'declined',
                    'payment_status' => 'cancelled',
                    'payment_details' => json_encode($this->paymentDetailsWithCartSnapshot($order, $payload)),
                ]);
                $this->clearCartSnapshot($order);

                $this->sendPaymentStatusNotification($order);
                info('M-Pesa STK Push Callback: Payment failed.', ['invoice_id' => $order->invoice_id]);
                return response()->json(['message' => 'Payment failed recorded']);
            }

            $metadataItems = collect(data_get($callback, 'CallbackMetadata.Item', []));
            $receipt = $metadataItems->firstWhere('Name', 'MpesaReceiptNumber');
            $receiptNumber = is_array($receipt) ? ($receipt['Value'] ?? $checkoutRequestId) : $checkoutRequestId;

            $processed = DB::transaction(function () use ($order, $receiptNumber, $payload) {
                // Daraja can redeliver callbacks. Lock the order and decide
                // idempotency inside the transaction so entitlements cannot
                // be granted twice by concurrent deliveries.
                $order = Order::with(['orderItems', 'user'])->lockForUpdate()->findOrFail($order->id);

                if ($order->payment_status === 'paid' && $order->status === 'completed') {
                    return false;
                }

                $order->update([
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'transaction_id' => $receiptNumber,
                    'payment_details' => json_encode($this->paymentDetailsWithCartSnapshot($order, $payload)),
                ]);

                $this->clearCartSnapshot($order);

                if ($order->isAiCreditsOrder()) {
                    $this->aiCreditLedgerService->recordPurchase($order->user, (int) data_get($order->order_details, 'credits', (int) $order->payable_amount * 10), [
                        'reference' => $order->invoice_id,
                        'note' => __('AI credits recharge'),
                        'order_id' => $order->id,
                        'invoice_id' => $order->invoice_id,
                        'purchase_id' => data_get($order->order_details, 'purchase_id'),
                        'amount' => $order->payable_amount,
                        'currency' => $order->payable_currency,
                    ]);
                    return true;
                }

                if ($order->isSubscriptionOrder()) {
                    activateSubscriptionPlanForUser($order->user, (string) data_get($order->order_details, 'plan_id'), $order);
                    $bonusCredits = $this->aiCreditLedgerService->subscriptionBonusCredits(
                        (int) data_get($order->order_details, 'plan_amount', $order->payable_amount)
                    );
                    $this->aiCreditLedgerService->recordSubscriptionBonus($order->user, $order, $bonusCredits, [
                        'reference' => $this->aiCreditLedgerService->subscriptionBonusReference($order),
                        'note' => __('Subscription AI bonus credits'),
                        'order_id' => $order->id,
                        'invoice_id' => $order->invoice_id,
                        'plan_id' => data_get($order->order_details, 'plan_id'),
                        'amount' => $order->payable_amount,
                        'currency' => $order->payable_currency,
                        'bonus_fraction' => revisionHubSubscriptionBonusFraction(),
                    ]);
                }

                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->item_type === 'product' || !$orderItem->course_id) {
                        continue;
                    }

                    $commission = $orderItem->price * ($orderItem->commission_rate / 100);
                    $instructorEarning = $orderItem->price - $commission;
                    $instructor = Course::find($orderItem->course_id)?->instructor;

                    if ($instructor) {
                        InstructorEarningsHold::firstOrCreate(
                            ['order_id' => $order->id, 'instructor_id' => $instructor->id],
                            ['amount' => $instructorEarning]
                        );
                    }
                }

                if ($order->isGiftOrder()) {
                    $this->giftOrderDetailsUpdate($order);
                } else {
                    foreach ($order->orderItems as $item) {
                        if ($item->item_type === 'product' || !$item->course_id) {
                            continue;
                        }

                        Enrollment::firstOrCreate(
                            ['user_id' => $order->buyer_id, 'course_id' => $item->course_id],
                            ['order_id' => $order->id, 'has_access' => 1]
                        );
                    }
                }
                return true;
            });

            if (!$processed) {
                return response()->json(['message' => 'Payment already processed']);
            }

            $order->refresh();
            $this->sendPaymentStatusNotification($order);

            info('M-Pesa STK Push Callback: Payment success.', ['invoice_id' => $order->invoice_id]);
            return response()->json(['message' => 'Payment success']);
        } catch (Exception $e) {
            info('M-Pesa STK Push Callback: Payment Error', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Callback error'], 500);
        }
    }

    private function sendPaymentStatusNotification(Order $order): void
    {
        if (!$order->user) {
            return;
        }

        $paymentController = new PaymentController();
        $paymentController->sendingPaymentStatusMail([
            'email' => $order->user->email,
            'name' => $order->user->name,
            'order_id' => $order->invoice_id,
            'paid_amount' => "{$order->paid_amount} {$order->payable_currency}",
            'payment_status' => $order->payment_status,
        ]);
    }

    /** Keep the client cart snapshot when the gateway callback replaces details. */
    private function paymentDetailsWithCartSnapshot(Order $order, array $payload): array
    {
        $existing = json_decode((string) $order->payment_details, true);
        $cartItemIds = data_get($existing, 'cart_item_ids');

        if (is_array($cartItemIds)) {
            $payload['cart_item_ids'] = $cartItemIds;
        }

        return $payload;
    }

    /** Clear only the cart items captured by this cart checkout. */
    private function clearCartSnapshot(Order $order): void
    {
        $details = json_decode((string) $order->payment_details, true);
        $cartItemIds = collect(data_get($details, 'cart_item_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($cartItemIds !== [] && $order->user) {
            $order->user->carts()->whereKey($cartItemIds)->delete();
        }
    }

}

