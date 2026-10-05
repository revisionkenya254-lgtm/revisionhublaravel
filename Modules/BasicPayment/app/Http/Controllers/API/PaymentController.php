<?php

namespace Modules\BasicPayment\app\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiCreditLedgerService;
use App\Services\Ai\AiCreditPurchaseService;
use App\Jobs\DefaultMailJob;
use App\Mail\DefaultMail;
use App\Models\Course;
use App\Models\Product;
use App\Traits\GetGlobalInformationTrait;
use App\Traits\MailSenderTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Modules\BasicPayment\app\Services\MpesaStkPushService;
use Modules\BasicPayment\app\Http\Controllers\PaymentController as WebPaymentController;
use Modules\GlobalSetting\app\Models\EmailTemplate;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Modules\Refund\app\Models\InstructorEarningsHold;

class PaymentController extends Controller
{
    use GetGlobalInformationTrait, MailSenderTrait;

    private $paymentService;
    private AiCreditLedgerService $aiCreditLedgerService;
    private AiCreditPurchaseService $aiCreditPurchaseService;

    public function __construct()
    {
        $this->paymentService = app(\Modules\BasicPayment\app\Services\PaymentMethodService::class);
        $this->aiCreditLedgerService = app(AiCreditLedgerService::class);
        $this->aiCreditPurchaseService = app(AiCreditPurchaseService::class);
    }

    public function all_payment(): JsonResponse
    {
        $data = $this->paymentService->getActiveGatewaysWithDetails();

        return $data
            ? response()->json(['status' => 'success', 'data' => $data], 200)
            : response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    /**
     * Unified mobile M-Pesa checkout entrypoint.
     *
     * The client selects only where the items come from. Prices, quantities,
     * currency and gateway charges are always resolved from server data.
     */
    public function createUnifiedClientMpesaOrder(Request $request): JsonResponse
    {
        $allowedFields = ['source', 'product_id'];
        $unexpectedFields = array_diff(array_keys($request->all()), $allowedFields);

        if ($unexpectedFields !== []) {
            return response()->json([
                'status' => 'error',
                'state' => 'invalid_request',
                'message' => 'The request contains unsupported fields.',
                'errors' => ['fields' => array_values($unexpectedFields)],
            ], 422);
        }

        $validated = $request->validate([
            'source' => ['required', 'string', 'in:cart,buy_now'],
            'product_id' => ['nullable', 'integer', 'min:1', 'required_if:source,buy_now', 'prohibited_if:source,cart'],
        ]);

        // The existing creator is deliberately shared here so the two sources
        // keep the same pricing, idempotency and pending-order behaviour.
        if ($validated['source'] === 'cart') {
            $request->request->remove('product_id');
        }

        return $this->createClientMpesaOrder($request);
    }

    /**
     * Creates a pending M-Pesa order from either the cart or one Buy Now
     * product. The server remains authoritative for price and availability.
     */
    public function createClientMpesaOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (filled($validated['product_id'] ?? null)) {
            return $this->createClientMpesaBuyNowOrder($request, (int) $validated['product_id']);
        }

        if (!$this->paymentService->isActive($this->paymentService::MPESA_STK_PUSH)) {
            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => 'M-Pesa payments are currently unavailable.',
            ], 503);
        }

        $user = $request->user();

        if (hasActiveSubscription($user)) {
            $user->carts()->delete();

            return $this->subscriptionAccessResponse();
        }

        try {
            $order = DB::transaction(function () use ($user) {
                // Reuse an order which has been prepared but has not yet sent
                // an STK Push. This makes a repeated checkout tap safe.
                $existingOrder = $user->orders()
                    ->where('payment_method', $this->paymentService::MPESA_STK_PUSH)
                    ->where('status', 'pending')
                    ->where('payment_status', 'pending')
                    ->whereNull('transaction_id')
                    ->has('orderItems')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($existingOrder) {
                    return $existingOrder->load('orderItems.product', 'orderItems.course');
                }

                $cartItems = $user->carts()
                    ->with([
                        'course:id,title,slug,price,discount',
                        'product:id,title,slug,type,price,discount',
                    ])
                    ->lockForUpdate()
                    ->get();

                if ($cartItems->isEmpty()) {
                    return null;
                }

                $commissionRate = (float) (Cache::get('setting')?->commission_rate ?? 0);
                $payableAmount = 0.0;

                foreach ($cartItems as $item) {
                    $purchasable = $item->purchasable();
                    if (!$purchasable) {
                        throw new Exception('A cart item is no longer available.');
                    }

                    $unitPrice = $this->clientCartItemPrice($purchasable);
                    $payableAmount += $unitPrice * max(1, (int) $item->qty);
                }

                $charge = $this->paymentService->getPayableAmount(
                    $this->paymentService::MPESA_STK_PUSH,
                    $payableAmount,
                    'KES'
                );
                // STK Push supports whole KES amounts. Persist exactly the
                // amount we will send to Safaricom.
                $paidAmount = (int) ceil((float) $charge->payable_with_charge);

                $order = Order::create([
                    'invoice_id' => Str::random(10),
                    'buyer_id' => $user->id,
                    'status' => 'pending',
                    'payment_method' => $this->paymentService::MPESA_STK_PUSH,
                    'payment_status' => 'pending',
                    'payable_amount' => $payableAmount,
                    'gateway_charge' => $paidAmount - $payableAmount,
                    'payable_with_charge' => $paidAmount,
                    'paid_amount' => $paidAmount,
                    'payable_currency' => 'KES',
                    'conversion_rate' => 1,
                    'commission_rate' => $commissionRate,
                    'order_type' => 'catalog',
                    // Keep the precise cart snapshot until M-Pesa has made a
                    // final decision.  The callback uses these IDs instead
                    // of clearing a customer's whole (possibly changed) cart.
                    'payment_details' => json_encode([
                        'cart_item_ids' => $cartItems->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->values()
                            ->all(),
                    ]),
                ]);

                foreach ($cartItems as $item) {
                    $purchasable = $item->purchasable();
                    $itemType = $item->item_type ?: 'course';

                    OrderItem::create([
                        'order_id' => $order->id,
                        'qty' => max(1, (int) $item->qty),
                        'price' => $this->clientCartItemPrice($purchasable),
                        'item_type' => $itemType,
                        'course_id' => $itemType === 'product' ? null : $item->course?->id,
                        'product_id' => $itemType === 'product' ? $item->product?->id : null,
                        'commission_rate' => $commissionRate,
                    ]);
                }

                return $order->load('orderItems.product', 'orderItems.course');
            });
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => 'Unable to prepare your order. Please review your cart and try again.',
            ], 422);
        }

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => 'Your cart is empty.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'state' => 'ready_for_payment',
            'message' => 'Order created. Enter your M-Pesa number to continue.',
            'data' => $this->clientMpesaOrderData($order, true),
        ], 201);
    }

    /**
     * Creates a one-item order without changing the user's cart. Product and
        * price are resolved on the server; the app only identifies the product
        * by its database ID at checkout time.
     */
        public function createClientMpesaBuyNowOrder(Request $request, int $productId): JsonResponse
    {
        if (!$this->paymentService->isActive($this->paymentService::MPESA_STK_PUSH)) {
            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => 'M-Pesa payments are currently unavailable.',
            ], 503);
        }

        $user = $request->user();

        if (hasActiveSubscription($user)) {
            return $this->subscriptionAccessResponse();
        }

        try {
            $order = DB::transaction(function () use ($user, $productId) {
                $product = Product::active()->whereKey($productId)->lockForUpdate()->first();

                if (!$product) {
                    return null;
                }

                if ((int) $product->instructor_id === (int) $user->id) {
                    throw new Exception('You cannot purchase your own product.');
                }

                $alreadyPurchased = OrderItem::where('item_type', 'product')
                    ->where('product_id', $product->id)
                    ->whereHas('order', fn ($query) => $query
                        ->where('buyer_id', $user->id)
                        ->where('payment_status', 'paid')
                        ->where('status', 'completed'))
                    ->exists();

                if ($alreadyPurchased) {
                    throw new Exception('This product has already been purchased.');
                }

                // Reuse an order prepared by a repeated Buy Now tap, provided
                // no STK Push has been sent yet.
                $existingOrder = $user->orders()
                    ->where('order_type', 'buy_now')
                    ->where('payment_method', $this->paymentService::MPESA_STK_PUSH)
                    ->where('status', 'pending')
                    ->where('payment_status', 'pending')
                    ->whereNull('transaction_id')
                    ->whereHas('orderItems', fn ($query) => $query
                        ->where('item_type', 'product')
                        ->where('product_id', $product->id))
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($existingOrder) {
                    return $existingOrder->load('orderItems.product', 'orderItems.course');
                }

                $payableAmount = $this->clientCartItemPrice($product);
                $charge = $this->paymentService->getPayableAmount(
                    $this->paymentService::MPESA_STK_PUSH,
                    $payableAmount,
                    'KES'
                );
                $paidAmount = (int) ceil((float) $charge->payable_with_charge);
                $commissionRate = (float) (Cache::get('setting')?->commission_rate ?? 0);

                $order = Order::create([
                    'invoice_id' => Str::random(10),
                    'buyer_id' => $user->id,
                    'status' => 'pending',
                    'payment_method' => $this->paymentService::MPESA_STK_PUSH,
                    'payment_status' => 'pending',
                    'payable_amount' => $payableAmount,
                    'gateway_charge' => $paidAmount - $payableAmount,
                    'payable_with_charge' => $paidAmount,
                    'paid_amount' => $paidAmount,
                    'payable_currency' => 'KES',
                    'conversion_rate' => 1,
                    'commission_rate' => $commissionRate,
                    'order_type' => 'buy_now',
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'qty' => 1,
                    'price' => $payableAmount,
                    'item_type' => 'product',
                    'product_id' => $product->id,
                    'commission_rate' => $commissionRate,
                ]);

                return $order->load('orderItems.product', 'orderItems.course');
            });
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => $e->getMessage() === 'This product has already been purchased.'
                    ? $e->getMessage()
                    : 'Unable to prepare this product for payment.',
            ], 422);
        }

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'state' => 'not_found',
                'message' => 'Product not found or unavailable.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'state' => 'ready_for_payment',
            'message' => 'Order created. Enter your M-Pesa number to continue.',
            'data' => $this->clientMpesaOrderData($order, true),
        ], 201);
    }

    /**
     * Starts an STK Push for a server-created order. Phone number is the only
     * client-controlled payment value.
     */
    public function clientMpesaPayment(Request $request, string $invoiceId): JsonResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        $phoneNumber = normalizeMpesaPhoneNumber($validated['phone_number']);
        if (!$phoneNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Use a valid Safaricom number like 07XXXXXXXX or 2547XXXXXXXX.',
            ], 422);
        }

        if (!$this->paymentService->isActive($this->paymentService::MPESA_STK_PUSH)) {
            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => 'M-Pesa payments are currently unavailable.',
            ], 503);
        }

        $order = Order::where('invoice_id', $invoiceId)
            ->where('buyer_id', $request->user()->id)
            ->where('payment_method', $this->paymentService::MPESA_STK_PUSH)
            ->withCount('orderItems')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'state' => 'not_found',
                'message' => 'M-Pesa order not found.',
            ], 404);
        }

        if ($order->order_items_count === 0) {
            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => 'This order has no purchasable items.',
            ], 422);
        }

        if (!in_array($order->payment_status, ['pending', 'unpaid'], true)) {
            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => 'This order cannot be paid in its current state.',
            ], 422);
        }

        // A CheckoutRequestID means an STK prompt is already in flight.
        // Starting another one can result in the customer paying twice.
        if ($order->payment_status === 'pending' && filled($order->transaction_id)) {
            return response()->json([
                'status' => 'pending',
                'state' => 'awaiting_pin',
                'message' => 'An M-Pesa prompt is already awaiting confirmation on your phone.',
                'data' => $this->clientMpesaOrderData($order),
            ], 202);
        }

        try {
            $result = (new MpesaStkPushService($this->get_basic_payment_info()))->initiate(
                phoneNumber: $phoneNumber,
                amount: (float) $order->paid_amount,
                accountReference: $order->invoice_id,
                description: "Order {$order->invoice_id}"
            );

            $response = $result['response'] ?? [];
            if (($response['ResponseCode'] ?? null) !== '0') {
                $paymentDetails = $this->mergeClientMpesaPaymentDetails($order, [
                    'response' => $response,
                    'local_status_reason' => 'stk_request_rejected',
                ]);

                $order->update([
                    'status' => 'declined',
                    'payment_status' => 'failed',
                    'payment_details' => json_encode($paymentDetails),
                ]);
                $this->clearClientMpesaCartSnapshot($order);

                return response()->json([
                    'status' => 'error',
                    'state' => 'failed',
                    'message' => $response['ResponseDescription'] ?? 'M-Pesa did not accept the STK Push request.',
                    'data' => [
                        'order_id' => $order->invoice_id,
                        'response_code' => $response['ResponseCode'] ?? null,
                    ],
                ], 502);
            }

            $order->update([
                'transaction_id' => $response['CheckoutRequestID'] ?? $order->transaction_id,
                'payment_status' => 'pending',
                'status' => 'pending',
                'payment_details' => json_encode($this->mergeClientMpesaPaymentDetails($order, [
                    'phone_number' => $phoneNumber,
                    'response' => $response,
                ])),
            ]);

            return response()->json([
                'status' => 'pending',
                'state' => 'awaiting_pin',
                'message' => 'Enter your M-Pesa PIN on your phone to complete the payment.',
                'data' => [
                    ...$this->clientMpesaOrderData($order),
                    'customer_message' => $response['CustomerMessage'] ?? null,
                ],
            ], 202);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to initiate the M-Pesa payment.',
            ], 502);
        }
    }

    /**
     * Mobile clients poll this endpoint after the initiation response. The
     * Safaricom callback remains the only source of a paid/failed decision.
     */
    public function clientMpesaStatus(Request $request, string $invoiceId): JsonResponse
    {
        $order = Order::where('invoice_id', $invoiceId)
            ->where('buyer_id', $request->user()->id)
            ->where('payment_method', $this->paymentService::MPESA_STK_PUSH)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'state' => 'not_found',
                'message' => 'M-Pesa order not found.',
            ], 404);
        }

        if ($order->payment_status === 'paid' && $order->status === 'completed') {
            return response()->json([
                'status' => 'success',
                'state' => 'paid',
                'message' => 'Payment received. Your order is complete.',
                'data' => $this->clientMpesaOrderData($order),
            ]);
        }

        if (in_array($order->payment_status, ['cancelled', 'failed'], true) || $order->status === 'declined') {
            return response()->json([
                'status' => 'error',
                'state' => 'failed',
                'message' => $this->extractMpesaFailureMessage($order),
                'data' => $this->clientMpesaOrderData($order),
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'state' => 'awaiting_pin',
            'message' => 'Enter your M-Pesa PIN on your phone to complete the payment.',
            'data' => $this->clientMpesaOrderData($order),
        ], 202);
    }

    private function clientMpesaOrderData(Order $order, bool $includeItems = false): array
    {
        $data = [
            'order_id' => $order->invoice_id,
            'checkout_request_id' => $order->transaction_id,
            'amount' => (int) $order->paid_amount,
            'currency' => $order->payable_currency,
            'source' => match ($order->order_type) {
                'buy_now' => 'buy_now',
                Order::ORDER_TYPE_SUBSCRIPTION => 'subscription',
                default => 'cart',
            },
        ];

        if ($includeItems) {
            $data['items'] = $order->orderItems->map(fn (OrderItem $item) => [
                'type' => $item->item_type,
                'product_type' => $item->product?->type,
                'title' => $item->product?->title ?? $item->course?->title,
                'quantity' => (int) $item->qty,
                'price' => (float) $item->price,
            ])->values();
        }

        return $data;
    }

    private function clientCartItemPrice(object $purchasable): float
    {
        $discount = (float) ($purchasable->discount ?? 0);

        return $discount > 0 ? $discount : (float) $purchasable->price;
    }

    private function subscriptionAccessResponse(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'state' => 'access_granted',
            'message' => 'Your active subscription already includes all catalogue products.',
            'data' => [
                'access_granted' => true,
                'access_source' => 'subscription',
                'payment_required' => false,
            ],
        ]);
    }

    /** Preserve cart snapshot metadata while recording M-Pesa responses. */
    private function mergeClientMpesaPaymentDetails(Order $order, array $details): array
    {
        $existing = json_decode((string) $order->payment_details, true);

        return array_merge(is_array($existing) ? $existing : [], $details);
    }

    /** Remove only the cart rows captured when this cart checkout began. */
    private function clearClientMpesaCartSnapshot(Order $order): void
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

    public function pay_via_mpesa_stk_push(Request $request)
    {
        $normalizedMsisdn = normalizeMpesaPhoneNumber($request->input('msisdn'));

        $request->validate([
            'msisdn' => ['required', 'string', 'regex:/^2547\\d{8}$/'],
        ], [
            'msisdn.required' => __('The phone number is required.'),
            'msisdn.string' => __('The phone number must be a valid string.'),
            'msisdn.regex' => __('Use a valid Safaricom number like 07XXXXXXXX or 2547XXXXXXXX.'),
        ]);

        try {
            $payment_setting = $this->get_basic_payment_info();
            $stkPushService = new MpesaStkPushService($payment_setting);
            $order = session()->get('order');

            $result = $stkPushService->initiate(
                phoneNumber: $normalizedMsisdn,
                amount: (float) session()->get('paid_amount'),
                accountReference: (string) ($order?->invoice_id ?? ('INV' . now()->timestamp)),
                description: 'Course enrollment payment'
            );

            $response = $result['response'] ?? [];
            if (($response['ResponseCode'] ?? null) !== '0') {
                return $this->renderMpesaPendingPage(
                    status: 'failed',
                    order: $order,
                    title: __('Unable to send M-Pesa STK Push'),
                    message: $response['ResponseDescription'] ?? __('M-Pesa did not accept the STK Push request.'),
                    details: $response,
                    token: (string) $request->bearer_token
                );
            }

            if ($order) {
                $order->transaction_id = $response['CheckoutRequestID'] ?? $order->transaction_id;
                $order->payment_status = 'pending';
                $order->status = 'pending';
                $order->payment_details = json_encode($result);
                $order->save();
            }

            Session::put('after_success_transaction', $response['CheckoutRequestID']);
            Session::put('payment_details', json_encode($result));

            return $this->renderMpesaPendingPage(
                status: 'pending',
                order: $order,
                title: __('STK Push sent'),
                message: __('We have sent the payment prompt to your phone. This page will update automatically after M-Pesa sends the callback.'),
                details: [
                    'phone_number' => $normalizedMsisdn,
                    'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                    'customer_message' => $response['CustomerMessage'] ?? null,
                ],
                token: (string) $request->bearer_token
            );
        } catch (Exception $e) {
            info($e->getMessage());

            return $this->renderMpesaPendingPage(
                status: 'failed',
                order: session()->get('order'),
                title: __('Unable to send M-Pesa STK Push'),
                message: __('We could not send the STK Push right now. Please confirm the phone number and gateway credentials, then try again.'),
                details: [
                    'error' => $e->getMessage(),
                    'phone_number' => $normalizedMsisdn ?? $request->msisdn,
                ],
                token: (string) $request->bearer_token
            );
        }
    }

    public function mpesa_stk_push_status(Request $request)
    {
        $order = $this->resolveTokenOrder($request);

        if (!$order) {
            return response()->json([
                'status' => 'not_found',
                'message' => __('We could not find this M-Pesa order.'),
            ], 404);
        }

        if ($order->payment_status === 'paid' && $order->status === 'completed') {
            return response()->json([
                'status' => 'paid',
                'redirect_url' => route('payment-api.mpesa-stk-push.success', [
                    'bearer_token' => $request->bearer_token,
                    'order_id' => $order->invoice_id,
                ], false),
            ]);
        }

        if ($this->shouldExpireMpesaStkPush($order)) {
            $this->markMpesaStkPushAsTimedOut($order);
            $order->refresh();
        }

        if (in_array($order->payment_status, ['cancelled', 'failed'], true) || $order->status === 'declined') {
            return response()->json([
                'status' => 'failed',
                'redirect_url' => route('payment-api.mpesa-stk-push.failed', [
                    'bearer_token' => $request->bearer_token,
                    'order_id' => $order->invoice_id,
                ], false),
                'message' => $this->extractMpesaFailureMessage($order),
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'message' => __('Waiting for the M-Pesa callback. Complete the prompt on your phone to continue.'),
            'expires_in' => $this->remainingMpesaStkPushSeconds($order),
        ]);
    }

    public function mpesa_stk_push_success(Request $request)
    {
        $order = $this->resolveTokenOrder($request);
        abort_unless($order && $order->payment_status === 'paid' && $order->status === 'completed', 404);
        $this->paymentService->removeSessions();

        return view('basicpayment::app_order_notification', [
            'image' => 'success.png',
            'title' => __('Your order has been placed'),
            'sub_title' => __('M-Pesa confirmed your payment successfully. You can now review the order from your account.'),
        ]);
    }

    public function mpesa_stk_push_failed(Request $request)
    {
        $order = $this->resolveTokenOrder($request);
        abort_unless($order && (in_array($order->payment_status, ['cancelled', 'failed'], true) || $order->status === 'declined'), 404);
        $this->paymentService->removeSessions();

        return view('basicpayment::app_order_notification', [
            'image' => 'fail.png',
            'title' => __('M-Pesa payment was not completed'),
            'sub_title' => $this->extractMpesaFailureMessage($order),
        ]);
    }

    public function placeOrder(Request $request, string $paymentMethod)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'UnAuthenticated'], 401);
        }

        $activeGateways = array_keys($this->paymentService->getActiveGatewaysWithDetails());
        if (!in_array($paymentMethod, $activeGateways, true)) {
            return response()->json(['status' => 'error', 'message' => 'The selected payment method is now inactive.'], 400);
        }

        $payable_currency = strtoupper($request->query('currency', 'KES'));
        if (!$this->paymentService->isCurrencySupported($paymentMethod, $payable_currency)) {
            $supportedCurrencies = $this->paymentService->getSupportedCurrencies($paymentMethod);

            return response()->json([
                'status' => 'error',
                'message' => 'You are trying to use unsupported currency',
                'supportCurrency' => sprintf('%s %s: %s', strtoupper($paymentMethod), 'supports only these types of currencies', implode(', ', $supportedCurrencies)),
            ], 400);
        }

        $aiCreditPurchase = $this->resolveAiCreditPurchase($request);
        if ($aiCreditPurchase) {
            $payable_amount = (int) data_get($aiCreditPurchase, 'amount', 0);
            $calculatePayableCharge = $this->paymentService->getPayableAmount($paymentMethod, $payable_amount, $payable_currency);

            DB::beginTransaction();

            $order = Order::create([
                'invoice_id' => Str::random(10),
                'buyer_id' => $user->id,
                'has_coupon' => Session::has('coupon_code') ? 1 : 0,
                'coupon_code' => Session::get('coupon_code'),
                'coupon_discount_percent' => Session::get('offer_percentage'),
                'coupon_discount_amount' => Session::get('coupon_discount_amount'),
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'payable_amount' => $payable_amount,
                'gateway_charge' => $calculatePayableCharge?->gateway_charge,
                'payable_with_charge' => $calculatePayableCharge?->payable_with_charge,
                'paid_amount' => $calculatePayableCharge?->payable_amount + $calculatePayableCharge?->gateway_charge,
                'payable_currency' => $calculatePayableCharge?->currency_code,
                'conversion_rate' => $calculatePayableCharge?->currency_rate,
                'commission_rate' => Cache::get('setting')->commission_rate,
                'order_type' => 'ai_credits',
                'order_details' => (object) [
                    'purchase_id' => data_get($aiCreditPurchase, 'id'),
                    'title' => data_get($aiCreditPurchase, 'title', __('AI Credits Recharge')),
                    'price' => data_get($aiCreditPurchase, 'price', currency($payable_amount)),
                    'amount' => $payable_amount,
                    'credits' => (int) data_get($aiCreditPurchase, 'credits', 0),
                    'subtitle' => data_get($aiCreditPurchase, 'subtitle'),
                    'description' => data_get($aiCreditPurchase, 'description'),
                    'is_custom' => (bool) data_get($aiCreditPurchase, 'is_custom', false),
                ],
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'price' => $order->payable_amount,
                'item_type' => 'ai_credits',
                'commission_rate' => Cache::get('setting')->commission_rate,
            ]);

            DB::commit();

            Session::put('ai_credit_purchase', $aiCreditPurchase);

            $newToken = $user->createToken('extra-token', ['extra'], now()->addWeek())->plainTextToken;

            return response()->json(['status' => 'success', 'url' => route('payment-api.payment', ['token' => $newToken, 'order_id' => $order?->invoice_id])], 200);
        }

        if (Session::has('ai_credit_purchase')) {
            $aiCreditPurchase = (array) Session::get('ai_credit_purchase', []);

            if (!filled(data_get($aiCreditPurchase, 'amount'))) {
                return response()->json(['status' => 'error', 'message' => 'Please choose a valid AI credit package.'], 400);
            }

            $payable_amount = (int) data_get($aiCreditPurchase, 'amount', 0);
            $calculatePayableCharge = $this->paymentService->getPayableAmount($paymentMethod, $payable_amount, $payable_currency);

            DB::beginTransaction();

            $order = Order::create([
                'invoice_id' => Str::random(10),
                'buyer_id' => $user->id,
                'has_coupon' => Session::has('coupon_code') ? 1 : 0,
                'coupon_code' => Session::get('coupon_code'),
                'coupon_discount_percent' => Session::get('offer_percentage'),
                'coupon_discount_amount' => Session::get('coupon_discount_amount'),
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'payable_amount' => $payable_amount,
                'gateway_charge' => $calculatePayableCharge?->gateway_charge,
                'payable_with_charge' => $calculatePayableCharge?->payable_with_charge,
                'paid_amount' => $calculatePayableCharge?->payable_amount + $calculatePayableCharge?->gateway_charge,
                'payable_currency' => $calculatePayableCharge?->currency_code,
                'conversion_rate' => $calculatePayableCharge?->currency_rate,
                'commission_rate' => Cache::get('setting')->commission_rate,
                'order_type' => 'ai_credits',
                'order_details' => (object) [
                    'purchase_id' => data_get($aiCreditPurchase, 'id'),
                    'title' => data_get($aiCreditPurchase, 'title', __('AI Credits Recharge')),
                    'price' => data_get($aiCreditPurchase, 'price', currency($payable_amount)),
                    'amount' => $payable_amount,
                    'credits' => (int) data_get($aiCreditPurchase, 'credits', 0),
                    'subtitle' => data_get($aiCreditPurchase, 'subtitle'),
                    'description' => data_get($aiCreditPurchase, 'description'),
                    'is_custom' => (bool) data_get($aiCreditPurchase, 'is_custom', false),
                ],
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'price' => $order->payable_amount,
                'item_type' => 'ai_credits',
                'commission_rate' => Cache::get('setting')->commission_rate,
            ]);

            DB::commit();

            $settings = cache()->get('setting');
            $marketingSettings = cache()->get('marketing_setting');
            if ($user && $settings->google_tagmanager_status == 'active' && $marketingSettings->order_success) {
                session()->put('enrollSuccess', [
                    'invoice_id' => $order->invoice_id,
                    'transaction_id' => $order->transaction_id,
                    'payment_method' => $order->payment_method,
                    'payable_currency' => $order->payable_currency,
                    'paid_amount' => $order->paid_amount,
                    'payment_status' => $order->payment_status,
                    'order_items' => [[
                        'course_name' => $order->order_details->title ?? __('AI Credits Recharge'),
                        'price' => currency($order->payable_amount),
                        'url' => route('ai-chat.credits'),
                    ]],
                    'student_info' => ['name' => $user->name, 'email' => $user->email],
                ]);
            }

            $newToken = $user->createToken('extra-token', ['extra'], now()->addWeek())->plainTextToken;

            return response()->json(['status' => 'success', 'url' => route('payment-api.payment', ['token' => $newToken, 'order_id' => $order?->invoice_id])], 200);
        }

        // AI credits are handled above. Everything below is catalogue checkout
        // and must be bypassed for an active all-products subscription.
        if (hasActiveSubscription($user)) {
            $user->carts()->delete();

            return $this->subscriptionAccessResponse();
        }

        if ($user->cart_count == 0) {
            return response()->json(['status' => 'error', 'message' => 'Please add some courses in your cart.'], 404);
        }

        try {
            $payable_amount = $user->cart_total;
            $calculatePayableCharge = $this->paymentService->getPayableAmount($paymentMethod, $payable_amount, $payable_currency);

            DB::beginTransaction();

            $order = Order::create([
                'invoice_id' => Str::random(10),
                'buyer_id' => $user->id,
                'has_coupon' => Session::has('coupon_code') ? 1 : 0,
                'coupon_code' => Session::get('coupon_code'),
                'coupon_discount_percent' => Session::get('offer_percentage'),
                'coupon_discount_amount' => Session::get('coupon_discount_amount'),
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'payable_amount' => $payable_amount,
                'gateway_charge' => $calculatePayableCharge?->gateway_charge,
                'payable_with_charge' => $calculatePayableCharge?->payable_with_charge,
                'paid_amount' => $calculatePayableCharge?->payable_amount + $calculatePayableCharge?->gateway_charge,
                'payable_currency' => $calculatePayableCharge?->currency_code,
                'conversion_rate' => $calculatePayableCharge?->currency_rate,
                'commission_rate' => Cache::get('setting')->commission_rate,
            ]);

            $data_layer_order_items = [];
            $carts = $user->carts()->with('course:id,title,slug,price,discount')->get(['id', 'user_id', 'course_id']);

            foreach ($carts as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'price' => $item->course->price,
                    'course_id' => $item->course->id,
                    'commission_rate' => Cache::get('setting')->commission_rate,
                ]);

                $data_layer_order_items[] = [
                    'course_name' => $item->course->title,
                    'price' => currency($item->course->price),
                    'url' => route('course.show', $item->course->slug),
                ];

                $commissionAmount = $item->course->price * ($order->commission_rate / 100);
                $amountAfterCommission = $item->course->price - $commissionAmount;
                $instructor = Course::find($item->course->id)->instructor;
                InstructorEarningsHold::create(['order_id' => $order->id, 'instructor_id' => $instructor->id, 'amount' => $amountAfterCommission]);
            }

            DB::commit();
            $user->carts()->delete();

            $settings = cache()->get('setting');
            $marketingSettings = cache()->get('marketing_setting');
            if ($user && $settings->google_tagmanager_status == 'active' && $marketingSettings->order_success) {
                session()->put('enrollSuccess', [
                    'invoice_id' => $order->invoice_id,
                    'transaction_id' => $order->transaction_id,
                    'payment_method' => $order->payment_method,
                    'payable_currency' => $order->payable_currency,
                    'paid_amount' => $order->paid_amount,
                    'payment_status' => $order->payment_status,
                    'order_items' => $data_layer_order_items,
                    'student_info' => ['name' => $user->name, 'email' => $user->email],
                ]);
            }

            if ($paymentMethod !== $this->paymentService::MPESA_STK_PUSH) {
                $this->handleMailSending([
                    'email' => $user->email,
                    'name' => $user->name,
                    'order_id' => $order->invoice_id,
                    'paid_amount' => $order->paid_amount . ' ' . $order->payable_currency,
                    'payment_method' => $order->payment_method,
                ]);

                if (cache()->get('setting')?->sms_order_completed) {
                    $message = SMSTemplate('order_completed', [
                        'order_id' => $order->invoice_id,
                        'paid_amount' => $order->paid_amount . ' ' . $order->payable_currency,
                        'payment_method' => $order->payment_method,
                    ]);
                    sendSMS($user->phone, $message);
                }
            }

            $newToken = $user->createToken('extra-token', ['extra'], now()->addWeek())->plainTextToken;

            return response()->json(['status' => 'success', 'url' => route('payment-api.payment', ['token' => $newToken, 'order_id' => $order?->invoice_id])], 200);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());

            return to_route('payment-api.webview-failed-payment');
        }
    }

    public function pay_via_free_gateway()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'UnAuthenticated'], 401);
        }
        if ($user->cart_total != 0) {
            return response()->json(['status' => 'error', 'message' => 'Payment failed, please try again'], 400);
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'invoice_id' => Str::random(10),
                'buyer_id' => $user->id,
                'payment_method' => 'Free',
                'status' => 'completed',
                'payment_status' => 'paid',
                'payable_amount' => 0,
                'gateway_charge' => 0,
                'payable_with_charge' => 0,
                'paid_amount' => 0,
                'payable_currency' => getSessionCurrency(),
                'transaction_id' => Str::random(10),
            ]);

            $carts = $user->carts()->with('course:id,title,slug,price,discount')->get(['id', 'user_id', 'course_id']);
            foreach ($carts as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'price' => $item->course->price,
                    'course_id' => $item->course->id,
                ]);

                Enrollment::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'course_id' => $item->course->id,
                    'has_access' => 1,
                ]);
            }

            DB::commit();
            $user->carts()->delete();

            return response()->json(['status' => 'success', 'message' => 'Your order has been placed'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['status' => 'error', 'message' => 'Your order has been fail'], 400);
        }
    }

    public function payment(Request $request)
    {
        $token = $request?->token ?? null;
        $order_id = $request?->order_id ?? null;
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $user = auth('sanctum')->user();
        if (!$user) {
            abort(401);
        }

        $order = $user?->orders()->where('invoice_id', $order_id)->where('status', 'pending')->first();
        if (!$order) {
            abort(404);
        }

        $paymentMethod = $order->payment_method;
        if (!$this->paymentService->isActive($paymentMethod)) {
            return response()->json(['status' => 'error', 'message' => 'The selected payment method is now inactive.'], 400);
        }

        $calculatePayableCharge = $this->paymentService->getPayableAmount($paymentMethod, $order?->payable_amount, $order?->payable_currency);

        Session::put('order', $order);
        Session::put('payable_currency', $order?->payable_currency);
        Session::put('paid_amount', $calculatePayableCharge?->payable_with_charge);

        $paymentService = $this->paymentService;
        $view = $this->paymentService->getBladeView($paymentMethod);

        return view($view, compact('order', 'paymentService', 'paymentMethod', 'user', 'token', 'order_id'));
    }

    public function payment_success()
    {
        $order = session()->get('order');
        if (!$order) {
            abort(404);
        }

        $after_success_transaction = session()->get('after_success_transaction');
        $payment_details = session()->get('payment_details');
        $user = auth()->user();
        $pendingMpesaStkPush = $order->payment_method === $this->paymentService::MPESA_STK_PUSH;

        try {
            $order->transaction_id = $after_success_transaction;
            $order->payment_status = $pendingMpesaStkPush ? 'pending' : 'paid';
            $order->status = 'completed';
            $order->payment_details = $pendingMpesaStkPush ? $payment_details : json_encode($payment_details);
            $order->save();

            if (!$pendingMpesaStkPush) {
                if ($order->isAiCreditsOrder()) {
                    $this->grantAiCreditPurchase($order, $user);
                } elseif ($order->isSubscriptionOrder()) {
                    activateSubscriptionPlanForUser($user, (string) data_get($order->order_details, 'plan_id'), $order);
                    $this->grantSubscriptionAiBonus($order, $user);
                } else {
                    foreach ($order->orderItems as $item) {
                        Enrollment::create([
                            'order_id' => $order->id,
                            'user_id' => $order->buyer_id,
                            'course_id' => $item->course_id,
                            'has_access' => 1,
                        ]);
                    }
                }
            }

            $subscriptionBonusCredits = $order->isSubscriptionOrder()
                ? $this->aiCreditLedgerService->subscriptionBonusCredits((int) data_get($order->order_details, 'plan_amount', $order->payable_amount))
                : 0;

            $this->handleMailSending([
                'email' => $user->email,
                'name' => $user->name,
                'order_id' => $order->invoice_id,
                'paid_amount' => $order->paid_amount . ' ' . $order->payable_currency,
                'payment_status' => $order->payment_status,
                'subscription_bonus_credits' => $subscriptionBonusCredits,
            ]);

            if (cache()->get('setting')?->sms_payment_status) {
                $message = SMSTemplate('payment_status', [
                    'order_id' => $order->invoice_id,
                    'payment_status' => $order->payment_status,
                ]);
                sendSMS($user->phone, $message);
            }

            $this->paymentService->removeSessions();

            return view('basicpayment::app_order_notification', [
                'image' => 'success.png',
                'title' => 'Your order has been placed',
                'sub_title' => __('For check more details you can go to your dashboard'),
            ]);
        } catch (Exception $e) {
            info($e->getMessage());

            return view('basicpayment::app_order_notification', [
                'image' => 'fail.png',
                'title' => 'Your order has been fail',
                'sub_title' => __('Please try again for more details connect with us'),
            ]);
        }
    }

    private function grantAiCreditPurchase(Order $order, $user): void
    {
        $purchase = (array) Session::get('ai_credit_purchase', []);
        $credits = (int) data_get($purchase, 'credits', $order->payable_amount * $this->aiCreditPurchaseService->creditsPerKes());

        $this->aiCreditLedgerService->recordPurchase($user, $credits, [
            'reference' => $order->invoice_id,
            'note' => __('AI credits recharge'),
            'order_id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'purchase_id' => data_get($purchase, 'id'),
            'amount' => $order->payable_amount,
            'currency' => $order->payable_currency,
        ]);
    }

    private function grantSubscriptionAiBonus(Order $order, $user): void
    {
        $bonusCredits = $this->aiCreditLedgerService->subscriptionBonusCredits(
            (int) data_get($order->order_details, 'plan_amount', $order->payable_amount)
        );

        $this->aiCreditLedgerService->recordSubscriptionBonus($user, $order, $bonusCredits, [
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

    public function payment_failed()
    {
        $order = session()->get('order');
        if ($order) {
            $order->payment_status = 'cancelled';
            $order->save();
        }

        $user = auth()->user();
        $this->sendingPaymentStatusMail([
            'email' => $user->email,
            'name' => $user->name,
            'order_id' => $order->invoice_id,
            'paid_amount' => $order->paid_amount . ' ' . $order->payable_currency,
            'payment_status' => $order->payment_status,
        ]);

        if (cache()->get('setting')?->sms_payment_status) {
            $message = SMSTemplate('payment_status', [
                'order_id' => $order->invoice_id,
                'payment_status' => $order->payment_status,
            ]);
            sendSMS($user->phone, $message);
        }

        $this->paymentService->removeSessions();

        return view('basicpayment::app_order_notification', [
            'image' => 'fail.png',
            'title' => 'Your order has been fail',
            'sub_title' => __('Please try again for more details connect with us'),
        ]);
    }

    private function resolveAiCreditPurchase(Request $request): ?array
    {
        if ($request->string('purchase_type')->toString() !== 'ai_credits' && !$request->filled('ai_credit_amount')) {
            return Session::has('ai_credit_purchase') ? (array) Session::get('ai_credit_purchase') : null;
        }

        $amount = (int) $request->input('ai_credit_amount', 0);
        if ($amount <= 0) {
            return null;
        }

        return $this->aiCreditPurchaseService->resolve($amount);
    }

    public function handleMailSending(array $mailData)
    {
        try {
            self::setMailConfig();
            $template = EmailTemplate::where('name', 'order_completed')->firstOrFail();
            $mailData['subject'] = $template->subject;
            $bonusCredits = (int) ($mailData['subscription_bonus_credits'] ?? 0);

            $message = str_replace('{{name}}', $mailData['name'], $template->message);
            $message = str_replace('{{order_id}}', $mailData['order_id'], $message);
            $message = str_replace('{{paid_amount}}', $mailData['paid_amount'], $message);
            $message = str_replace('{{payment_method}}', $mailData['payment_method'], $message);
            $message = str_replace('{{subscription_bonus_line}}', $this->buildSubscriptionBonusLine($bonusCredits), $message);

            if (!str_contains($template->message, '{{subscription_bonus_line}}') && $bonusCredits > 0) {
                $message .= $this->buildSubscriptionBonusLine($bonusCredits);
            }

            if (self::isQueable()) {
                DefaultMailJob::dispatch($mailData['email'], $mailData, $message);
            } else {
                Mail::to($mailData['email'])->send(new DefaultMail($mailData, $message));
            }
        } catch (Exception $e) {
            info($e->getMessage());
        }
    }

    private function buildSubscriptionBonusLine(int $credits): string
    {
        if ($credits <= 0) {
            return '';
        }

        return '<p><strong>' . __('You get :credits bonus AI credits.', ['credits' => number_format($credits)]) . '</strong></p>';
    }

    public function sendingPaymentStatusMail(array $mailData)
    {
        try {
            self::setMailConfig();
            $template = EmailTemplate::where('name', 'payment_status')->firstOrFail();
            $mailData['subject'] = $template->subject;

            $message = str_replace('{{name}}', $mailData['name'], $template->message);
            $message = str_replace('{{order_id}}', $mailData['order_id'], $message);
            $message = str_replace('{{paid_amount}}', $mailData['paid_amount'], $message);
            $message = str_replace('{{payment_status}}', $mailData['payment_status'], $message);

            if (self::isQueable()) {
                DefaultMailJob::dispatch($mailData['email'], $mailData, $message);
            } else {
                Mail::to($mailData['email'])->send(new DefaultMail($mailData, $message));
            }
        } catch (Exception $e) {
            info($e->getMessage());
        }
    }

    private function renderMpesaPendingPage(string $status, ?Order $order, string $title, string $message, array $details = [], ?string $token = null)
    {
        return view('basicpayment::gateway-actions.mpesa-stk-push-status', [
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'details' => $details,
            'order' => $order,
            'token' => $token,
            'pollUrl' => ($order && $token)
                ? route('payment-api.mpesa-stk-push.status', ['bearer_token' => $token, 'order_id' => $order->invoice_id], false)
                : null,
            'expiresAt' => $order ? $order->updated_at?->copy()->addSeconds(WebPaymentController::MPESA_STK_PUSH_TIMEOUT_SECONDS)?->toIso8601String() : null,
        ]);
    }

    private function resolveTokenOrder(Request $request): ?Order
    {
        $token = (string) ($request->bearer_token ?? $request->token ?? '');
        $orderId = (string) ($request->order_id ?? '');

        if ($token !== '') {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        $user = auth('sanctum')->user();

        if (!$user || $orderId === '') {
            return null;
        }

        return $user->orders()->where('invoice_id', $orderId)->first();
    }

    private function extractMpesaFailureMessage(Order $order): string
    {
        $details = json_decode((string) $order->payment_details, true);

        if (($details['local_status_reason'] ?? null) === 'stk_timeout') {
            return $details['local_status_message'] ?? __('The M-Pesa STK Push expired before a callback was received.');
        }

        return data_get($details, 'Body.stkCallback.ResultDesc')
            ?? data_get($details, 'response.ResponseDescription')
            ?? data_get($details, 'response.errorMessage')
            ?? __('The M-Pesa payment was cancelled or rejected before completion.');
    }

    private function shouldExpireMpesaStkPush(Order $order): bool
    {
        return $order->payment_method === $this->paymentService::MPESA_STK_PUSH
            && $order->payment_status === 'pending'
            && $order->status === 'pending'
            && $this->remainingMpesaStkPushSeconds($order) <= 0;
    }

    private function remainingMpesaStkPushSeconds(Order $order): int
    {
        $expiresAt = $order->updated_at?->copy()->addSeconds(WebPaymentController::MPESA_STK_PUSH_TIMEOUT_SECONDS);

        if (!$expiresAt) {
            return 0;
        }

        return max(0, now()->diffInSeconds($expiresAt, false));
    }

    private function markMpesaStkPushAsTimedOut(Order $order): void
    {
        $details = json_decode((string) $order->payment_details, true) ?: [];

        $details['local_status_reason'] = 'stk_timeout';
        $details['local_status_message'] = __('The M-Pesa STK Push expired before a callback was received.');
        $details['local_timeout_at'] = now()->toIso8601String();

        $order->update([
            'status' => 'declined',
            'payment_status' => 'cancelled',
            'payment_details' => json_encode($details),
        ]);
    }
}
