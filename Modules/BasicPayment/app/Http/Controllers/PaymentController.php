<?php

namespace Modules\BasicPayment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\DefaultMailJob;
use App\Mail\DefaultMail;
use App\Models\Course;
use App\Traits\GetGlobalInformationTrait;
use App\Traits\MailSenderTrait;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Services\Ai\AiCreditLedgerService;
use App\Services\Ai\AiCreditPurchaseService;
use Modules\BasicPayment\app\Services\MpesaStkPushService;
use Modules\CourseBundle\app\Models\CourseBundle;
use Modules\GiftCourse\app\Models\GiftCourse;
use Modules\GlobalSetting\app\Models\EmailTemplate;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Modules\Order\app\Traits\GiftOrderTraits;
use Modules\Refund\app\Models\InstructorEarningsHold;
use Nwidart\Modules\Facades\Module;

class PaymentController extends Controller
{
    use GetGlobalInformationTrait, MailSenderTrait, GiftOrderTraits;

    public const MPESA_STK_PUSH_TIMEOUT_SECONDS = 120;

    private $paymentService;
    private AiCreditLedgerService $aiCreditLedgerService;
    private AiCreditPurchaseService $aiCreditPurchaseService;

    public function __construct()
    {
        $this->paymentService = app(\Modules\BasicPayment\app\Services\PaymentMethodService::class);
        $this->aiCreditLedgerService = app(AiCreditLedgerService::class);
        $this->aiCreditPurchaseService = app(AiCreditPurchaseService::class);
        $this->middleware(['auth', 'verified']);
        $this->middleware(function (Request $request, Closure $next) {
            if (
                session()->has('order')
                || Route::is('payment')
                || Route::is('place.order')
                || Route::is('pay-via-free-gateway')
                || Route::is('mpesa-stk-push.status')
                || Route::is('mpesa-stk-push.success')
                || Route::is('mpesa-stk-push.failed')
            ) {
                return $next($request);
            }

            return redirect()->back()->with(['messege' => __('Not Found!'), 'alert-type' => 'error']);
        });
    }

    public function placeOrder($method)
    {
        $user = userAuth();
        $pendingNotificationMethods = [$this->paymentService::MPESA_STK_PUSH];
        $gatewayCurrency = $this->paymentService->getGatewayCurrencyCode($method) ?? getSessionCurrency();

        $activeGateways = array_keys($this->paymentService->getActiveGatewaysWithDetails());
        if (!in_array($method, $activeGateways, true)) {
            return response()->json(['status' => false, 'messege' => __('The selected payment method is now inactive.')]);
        }

        if (!$this->paymentService->isCurrencySupported($method, $gatewayCurrency)) {
            $supportedCurrencies = $this->paymentService->getSupportedCurrencies($method);

            return response()->json([
                'status' => false,
                'messege' => __('You are trying to use unsupported currency'),
                'supportCurrency' => sprintf(
                    '%s %s: %s',
                    strtoupper($method),
                    __('supports only these types of currencies'),
                    implode(', ', $supportedCurrencies)
                ),
            ]);
        }

        $aiCreditPurchase = $this->resolveAiCreditPurchase(request());
        if ($aiCreditPurchase) {
            Session::forget('subscription_plan_id');
            Session::put('ai_credit_purchase', $aiCreditPurchase);

            $carts = collect();
            $payable_amount = (int) data_get($aiCreditPurchase, 'amount', 0);
            $order_type = 'ai_credits';
            $order_details = (object) [
                'purchase_id' => data_get($aiCreditPurchase, 'id'),
                'title' => data_get($aiCreditPurchase, 'title', __('AI Credits Recharge')),
                'price' => data_get($aiCreditPurchase, 'price', currency($payable_amount)),
                'amount' => $payable_amount,
                'credits' => (int) data_get($aiCreditPurchase, 'credits', 0),
                'subtitle' => data_get($aiCreditPurchase, 'subtitle'),
                'description' => data_get($aiCreditPurchase, 'description'),
                'is_custom' => (bool) data_get($aiCreditPurchase, 'is_custom', false),
            ];
        } elseif (Module::has('CourseBundle') && Module::isEnabled('CourseBundle') && request()->query('bundle', null)) {
            $bundle_slug = request()->query('bundle', null);
            $bundle = CourseBundle::with('course_bundle_items:course_bundle_id,course_id', 'course_bundle_items.course:id,slug,thumbnail,title,price,discount')->whereSlug($bundle_slug)->first();

            if (!$bundle) {
                return response()->json(['status' => false, 'messege' => __('Not Found!')]);
            }
            if ($bundle->instructor_id == $user->id) {
                return response()->json(['status' => false, 'messege' => __('You cannot purchase your own bundle.')]);
            }

            $bundle_course_ids = $bundle->course_bundle_items->pluck('course_id')->toArray();
            $enrolledCount = $user->enrollments()->whereIn('course_id', $bundle_course_ids)->count();
            if ($enrolledCount == count($bundle_course_ids)) {
                return response()->json(['status' => false, 'messege' => __('You are already enrolled in all courses in this bundle.')]);
            }

            $carts = $bundle->course_bundle_items;
            $price = $bundle->price ?? 0;
            $discount = $bundle->discount ?? 0;
            $payable_amount = ($price > 0 && $discount > 0) ? $discount : $price;
            $order_type = 'bundle';
            $order_details = (object) [
                'id' => $bundle->id,
                'title' => $bundle->title,
                'price' => $bundle->price,
                'discount' => $bundle->discount,
                'thumbnail' => $bundle->thumbnail,
                'course_ids' => $bundle_course_ids,
            ];
        } elseif (Module::has('GiftCourse') && Module::isEnabled('GiftCourse') && request()->query('gift', null)) {
            $gift_id = request()->query('gift', null);
            $gift = GiftCourse::with('course:id,slug,thumbnail,title,price,discount')->whereGiftId($gift_id)->firstOrFail();

            $carts = [$gift];
            $price = $gift->course->price ?? 0;
            $discount = $gift->course->discount ?? 0;
            $payable_amount = ($price > 0 && $discount > 0) ? $discount : $price;
            $order_type = 'gift';
            $order_details = (object) [
                'gift_id' => $gift->gift_id,
                'user_id' => $gift->user_id,
                'course_id' => $gift->course_id,
                'recipient_name' => $gift->recipient_name,
                'recipient_email' => $gift->recipient_email,
                'message' => $gift->message,
            ];
        } elseif (Session::has('ai_credit_purchase')) {
            $aiCreditPurchase = (array) Session::get('ai_credit_purchase', []);

            if (!filled(data_get($aiCreditPurchase, 'amount'))) {
                return response()->json(['status' => false, 'messege' => __('Please choose a valid AI credit package.')]);
            }

            $carts = collect();
            $payable_amount = (int) data_get($aiCreditPurchase, 'amount', 0);
            $order_type = 'ai_credits';
            $order_details = (object) [
                'purchase_id' => data_get($aiCreditPurchase, 'id'),
                'title' => data_get($aiCreditPurchase, 'title', __('AI Credits Recharge')),
                'price' => data_get($aiCreditPurchase, 'price', currency($payable_amount)),
                'amount' => $payable_amount,
                'credits' => (int) data_get($aiCreditPurchase, 'credits', 0),
                'subtitle' => data_get($aiCreditPurchase, 'subtitle'),
                'description' => data_get($aiCreditPurchase, 'description'),
                'is_custom' => (bool) data_get($aiCreditPurchase, 'is_custom', false),
            ];
        } elseif (Session::has('subscription_plan_id')) {
            $subscriptionPlan = $this->resolveSubscriptionPlan((string) Session::get('subscription_plan_id'));

            if (!$subscriptionPlan) {
                return response()->json(['status' => false, 'messege' => __('Please choose a valid subscription plan.')]);
            }

            $upgradeQuote = revisionHubSubscriptionUpgradeQuote($user, $subscriptionPlan);
            if (! $upgradeQuote['allowed']) {
                return response()->json(['status' => false, 'messege' => $upgradeQuote['message']]);
            }

            $carts = collect();
            $payable_amount = $upgradeQuote['payable_amount'];
            $order_type = 'subscription';
            $order_details = (object) [
                'plan_id' => $subscriptionPlan['id'],
                'title' => $subscriptionPlan['name'],
                'price' => $subscriptionPlan['price'],
                'period' => $subscriptionPlan['period'],
                'billing' => $subscriptionPlan['billing'],
                'plan_amount' => $upgradeQuote['base_amount'],
                'upgrade_type' => $upgradeQuote['kind'],
                'previous_plan_id' => data_get($upgradeQuote, 'current_plan.id'),
                'prorated_credit' => $upgradeQuote['credit_amount'],
            ];
        } else {
            $carts = $user->carts()->with([
                'course:id,title,slug,price,discount',
                'product:id,title,slug,type,price,discount',
            ])->get(['id', 'user_id', 'course_id', 'product_id', 'item_type']);
            $payable_amount = Session::get('payable_amount', $user->cart_total);
            $order_type = 'catalog';
            $order_details = null;
        }

        try {
            $calculatePayableCharge = $this->paymentService->getPayableAmount($method, $payable_amount, $gatewayCurrency);
            DB::beginTransaction();

            $paid_amount = $calculatePayableCharge?->payable_amount + $calculatePayableCharge?->gateway_charge;

            $order = Order::create([
                'invoice_id' => Str::random(10),
                'buyer_id' => $user->id,
                'has_coupon' => Session::has('coupon_code') ? 1 : 0,
                'coupon_code' => Session::get('coupon_code'),
                'coupon_discount_percent' => Session::get('offer_percentage'),
                'coupon_discount_amount' => Session::get('coupon_discount_amount'),
                'payment_method' => $method,
                'payment_status' => 'pending',
                'payable_amount' => $payable_amount,
                'gateway_charge' => $calculatePayableCharge?->gateway_charge,
                'payable_with_charge' => $calculatePayableCharge?->payable_with_charge,
                'paid_amount' => $paid_amount,
                'payable_currency' => $calculatePayableCharge?->currency_code,
                'conversion_rate' => $calculatePayableCharge?->currency_rate ?? Session::get('currency_rate', 1),
                'commission_rate' => Cache::get('setting')->commission_rate,
                'order_type' => $order_type,
                'order_details' => $order_details,
            ]);

            $data_layer_order_items = [];

            if ($order_type === 'ai_credits') {
                OrderItem::create([
                    'order_id' => $order->id,
                    'price' => $order->payable_amount,
                    'item_type' => 'ai_credits',
                    'commission_rate' => Cache::get('setting')->commission_rate,
                ]);

                $data_layer_order_items[] = [
                    'course_name' => $order_details->title,
                    'price' => currency($order->payable_amount),
                    'url' => route('ai-chat.credits'),
                ];
            } else {
                foreach ($carts as $item) {
                    $purchasable = $item->purchasable();
                    $item_price = $order->isBundleOrder() ? ($order->payable_amount / max(1, $carts->count())) : $purchasable?->price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'price' => $item_price,
                        'item_type' => $item->item_type ?? 'course',
                        'course_id' => $item->item_type === 'product' ? null : $item->course?->id,
                        'product_id' => $item->item_type === 'product' ? $item->product?->id : null,
                        'commission_rate' => Cache::get('setting')->commission_rate,
                    ]);

                    $data_layer_order_items[] = [
                        'course_name' => $purchasable?->title,
                        'price' => currency($purchasable?->price),
                        'url' => $item->item_type === 'product' ? route('product.show', $purchasable?->slug) : route('course.show', $purchasable?->slug),
                    ];
                }
            }

            if ($order_type === 'subscription') {
                $data_layer_order_items[] = [
                    'course_name' => $order_details->title,
                    'price' => currency($order->payable_amount),
                    'url' => route('subscriptions', ['plan' => $order_details->plan_id]),
                ];
            }

            DB::commit();

            if (!$order->isBundleOrder() && !$order->isGiftOrder() && $order_type !== 'subscription') {
                $user->carts()->delete();
            }

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
                    'student_info' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ]);
            }

            if (!in_array($method, $pendingNotificationMethods, true)) {
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

            return response()->json(['success' => true, 'invoice_id' => $order?->invoice_id]);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());

            return response()->json(['status' => false, 'messege' => __('Payment Failed')]);
        }
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

    public function index()
    {
        $invoice_id = request('invoice_id', null);
        $user = userAuth();

        $order = $user?->orders()
            ->where('invoice_id', $invoice_id)
            ->whereNotIn('payment_status', ['paid', 'refunded'])
            ->where('payment_method', '!=', 'Free')
            ->first();
        if (!$order) {
            return redirect()->back()->with(['messege' => __('Not Found!'), 'alert-type' => 'error']);
        }

        $paymentMethod = $order->payment_method;
        if (!$this->paymentService->isActive($paymentMethod)) {
            return redirect()->back()->with(['messege' => __('The selected payment method is now inactive.'), 'alert-type' => 'error']);
        }

        $calculatePayableCharge = $this->paymentService->getPayableAmount($paymentMethod, $order?->payable_amount, $order?->payable_currency);

        Session::put('order', $order);
        Session::put('payable_currency', $order?->payable_currency);
        Session::put('paid_amount', $calculatePayableCharge?->payable_with_charge);

        if ($paymentMethod === $this->paymentService::MPESA_STK_PUSH && request()->boolean('autostk') && request()->filled('msisdn')) {
            return $this->initiateMpesaStkPush((string) request('msisdn'), $order->invoice_id);
        }

        $paymentService = $this->paymentService;
        $view = $this->paymentService->getBladeView($paymentMethod);

        return view($view, compact('order', 'paymentService', 'paymentMethod'));
    }

    public function pay_via_free_gateway()
    {
        $user = userAuth();

        if (Module::has('CourseBundle') && Module::isEnabled('CourseBundle') && request()->query('bundle', null)) {
            $bundle_slug = request()->query('bundle', null);
            $bundle = CourseBundle::with('course_bundle_items:course_bundle_id,course_id', 'course_bundle_items.course:id,slug,thumbnail,title,price,discount')->whereNot('instructor_id', $user->id)->whereSlug($bundle_slug)->first();
            if (!$bundle || $bundle->price != 0) {
                return redirect()->back()->with(['messege' => __('Payment failed, please try again'), 'alert-type' => 'error']);
            }

            $bundle_course_ids = $bundle->course_bundle_items->pluck('course_id')->toArray();
            $enrolledCount = $user->enrollments()->whereIn('course_id', $bundle_course_ids)->count();
            if ($enrolledCount == count($bundle_course_ids)) {
                return response()->json(['status' => false, 'messege' => __('You are already enrolled in all courses in this bundle.')]);
            }

            $carts = $bundle->course_bundle_items;
            $payable_amount = ($bundle->price > 0 && $bundle->discount > 0) ? $bundle->discount : $bundle->price;
            $order_type = 'bundle';
            $order_details = (object) [
                'id' => $bundle->id,
                'title' => $bundle->title,
                'price' => $bundle->price,
                'discount' => $bundle->discount,
                'thumbnail' => $bundle->thumbnail,
                'course_ids' => $bundle_course_ids,
            ];
        } elseif (Module::has('GiftCourse') && Module::isEnabled('GiftCourse') && request()->query('gift', null)) {
            $gift_id = request()->query('gift', null);
            $gift = GiftCourse::with('course:id,slug,thumbnail,title,price,discount')->whereGiftId($gift_id)->firstOrFail();
            if ($gift->course->price != 0) {
                return redirect()->back()->with(['messege' => __('Payment failed, please try again'), 'alert-type' => 'error']);
            }

            $carts = [$gift];
            $payable_amount = ($gift->course->price > 0 && $gift->course->discount > 0) ? $gift->course->discount : $gift->course->price;
            $order_type = 'gift';
            $order_details = (object) [
                'gift_id' => $gift->gift_id,
                'user_id' => $gift->user_id,
                'course_id' => $gift->course_id,
                'recipient_name' => $gift->recipient_name,
                'recipient_email' => $gift->recipient_email,
                'message' => $gift->message,
            ];
        } else {
            if ($user->cart_total != 0) {
                return redirect()->back()->with(['messege' => __('Payment failed, please try again'), 'alert-type' => 'error']);
            }

            $carts = $user->carts()->with([
                'course:id,title,slug,price,discount',
                'product:id,title,slug,type,price,discount',
            ])->get(['id', 'user_id', 'course_id', 'product_id', 'item_type']);
            $payable_amount = Session::get('payable_amount', $user->cart_total);
            $order_type = 'catalog';
            $order_details = null;
        }

        Session::put('after_success_transaction', Str::random(10));

        try {
            DB::beginTransaction();

            $order = Order::create([
                'invoice_id' => Str::random(10),
                'buyer_id' => $user->id,
                'payment_method' => 'Free',
                'status' => 'completed',
                'payment_status' => 'paid',
                'payable_amount' => $payable_amount,
                'gateway_charge' => 0,
                'payable_with_charge' => $payable_amount,
                'paid_amount' => $payable_amount,
                'payable_currency' => getSessionCurrency(),
                'transaction_id' => Str::random(10),
                'order_type' => $order_type,
                'order_details' => $order_details,
            ]);

            foreach ($carts as $item) {
                $purchasable = $item->purchasable();
                $item_price = $order->isBundleOrder() ? ($order->payable_amount / max(1, $carts->count())) : $purchasable?->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'price' => $item_price,
                    'item_type' => $item->item_type ?? 'course',
                    'course_id' => $item->item_type === 'product' ? null : $item->course?->id,
                    'product_id' => $item->item_type === 'product' ? $item->product?->id : null,
                ]);

                if (($item->item_type ?? 'course') === 'course') {
                    Enrollment::firstOrCreate([
                        'user_id' => $user->id,
                        'course_id' => $item->course->id,
                    ], [
                        'order_id' => $order->id,
                        'has_access' => 1,
                    ]);
                }
            }

            DB::commit();

            if (!$order->isBundleOrder() && !$order->isGiftOrder()) {
                $user->carts()->delete();
            }

            return view('frontend.pages.order-success')->with(['messege' => trans('Payment Success.'), 'alert-type' => 'success']);
        } catch (Exception $e) {
            DB::rollBack();
            info($e->getMessage());

            return redirect()->back()->with(['messege' => __('Payment failed, please try again'), 'alert-type' => 'error']);
        }
    }

    public function pay_via_paypal()
    {
        $basic_payment = $this->get_basic_payment_info();
        $paypal_credentials = (object) [
            'paypal_client_id' => $basic_payment->paypal_client_id,
            'paypal_secret_key' => $basic_payment->paypal_secret_key,
            'paypal_account_mode' => $basic_payment->paypal_account_mode,
        ];

        $paypal_payment = new FrontPaymentController();

        return $paypal_payment->pay_with_paypal($paypal_credentials, route('payment-success'), route('payment-failed'));
    }

    public function pay_via_mpesa_stk_push(Request $request)
    {
        return $this->initiateMpesaStkPush((string) $request->input('msisdn'), session('order')?->invoice_id);
    }

    public function mpesa_stk_push_status(string $invoice_id)
    {
        $order = userAuth()?->orders()->where('invoice_id', $invoice_id)->first();

        if (!$order) {
            return response()->json([
                'status' => 'not_found',
                'message' => __('We could not find this M-Pesa order.'),
            ], 404);
        }

        if ($order->payment_status === 'paid' && $order->status === 'completed') {
            return response()->json([
                'status' => 'paid',
                'redirect_url' => route('mpesa-stk-push.success', ['invoice_id' => $order->invoice_id]),
            ]);
        }

        if ($this->shouldExpireMpesaStkPush($order)) {
            $this->markMpesaStkPushAsTimedOut($order);
            $order->refresh();
        }

        if (in_array($order->payment_status, ['cancelled', 'failed'], true) || $order->status === 'declined') {
            return response()->json([
                'status' => 'failed',
                'redirect_url' => route('mpesa-stk-push.failed', ['invoice_id' => $order->invoice_id]),
                'message' => $this->extractMpesaFailureMessage($order),
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'message' => __('Waiting for the M-Pesa callback. Complete the prompt on your phone to continue.'),
            'expires_in' => $this->remainingMpesaStkPushSeconds($order),
        ]);
    }

    public function mpesa_stk_push_success(string $invoice_id)
    {
        $order = userAuth()?->orders()->where('invoice_id', $invoice_id)->firstOrFail();

        abort_unless($order->payment_status === 'paid' && $order->status === 'completed', 404);
        $this->orderSessionForget();

        return view('frontend.pages.order-success')->with([
            'messageTitle' => __('Your order has been placed'),
            'messageBody' => __('M-Pesa confirmed your payment successfully. You can now view the full order in your dashboard.'),
        ]);
    }

    public function mpesa_stk_push_failed(string $invoice_id)
    {
        $order = userAuth()?->orders()->where('invoice_id', $invoice_id)->firstOrFail();

        abort_unless(in_array($order->payment_status, ['cancelled', 'failed'], true) || $order->status === 'declined', 404);
        $this->orderSessionForget();

        return view('frontend.pages.order-fail')->with([
            'messageTitle' => __('M-Pesa payment was not completed'),
            'messageBody' => $this->extractMpesaFailureMessage($order),
        ]);
    }

    public function payment_success()
    {
        $order = session()->get('order');
        $after_success_transaction = session()->get('after_success_transaction');
        $payment_details = session()->get('payment_details');
        $user = userAuth();
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
                    $order_items = OrderItem::select('price', 'course_id', 'item_type', 'commission_rate')->where('order_id', $order->id)->get();
                    foreach ($order_items as $order_item) {
                        if ($order_item->item_type === 'product' || !$order_item->course_id) {
                            continue;
                        }

                        $commissionAmount = $order_item->price * ($order_item->commission_rate / 100);
                        $amountAfterCommission = $order_item->price - $commissionAmount;
                        $instructor = Course::find($order_item->course_id)->instructor;

                        InstructorEarningsHold::create([
                            'order_id' => $order->id,
                            'instructor_id' => $instructor->id,
                            'amount' => $amountAfterCommission,
                        ]);
                    }

                    if ($order->isGiftOrder()) {
                        $this->giftOrderDetailsUpdate($order);
                    } else {
                        foreach ($order->orderItems as $item) {
                            if ($item->item_type === 'product' || !$item->course_id) {
                                continue;
                            }

                            Enrollment::firstOrCreate([
                                'user_id' => $order->buyer_id,
                                'course_id' => $item->course_id,
                            ], [
                                'order_id' => $order->id,
                                'has_access' => 1,
                            ]);
                        }
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

            $this->orderSessionForget();

            $notificationText = $pendingMpesaStkPush
                ? __('M-Pesa STK Push sent. Complete the payment prompt on your phone to finish the order.')
                : __('Payment Success.');

            return view('frontend.pages.order-success')->with([
                'messege' => $notificationText,
                'alert-type' => $pendingMpesaStkPush ? 'info' : 'success',
            ]);
        } catch (Exception $e) {
            return redirect()->route('payment-failed')->with(['messege' => trans('Payment failed, please try again'), 'alert-type' => 'error']);
        }
    }

    public function payment_failed()
    {
        $order = session()->get('order');
        $order->payment_status = 'cancelled';
        $order->save();

        $user = userAuth();
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

        $this->orderSessionForget();

        return view('frontend.pages.order-fail')->with(['messege' => trans('Payment failed, please try again'), 'alert-type' => 'error']);
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
                DefaultMailJob::dispatch($mailData, $message);
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
                DefaultMailJob::dispatch($mailData, $message);
            } else {
                Mail::to($mailData['email'])->send(new DefaultMail($mailData, $message));
            }
        } catch (Exception $e) {
            info($e->getMessage());
        }
    }

    private function orderSessionForget()
    {
        session()->forget([
            'after_success_url',
            'after_failed_url',
            'order',
            'payable_amount',
            'gateway_charge',
            'after_success_gateway',
            'after_success_transaction',
            'subscription_plan_id',
            'subscription_upgrade_quote',
            'ai_credit_purchase',
            'payable_with_charge',
            'payable_currency',
            'subscription_plan_id',
            'paid_amount',
            'payment_details',
            'cart',
            'coupon_code',
            'offer_percentage',
            'coupon_discount_amount',
            'gateway_charge_in_usd',
        ]);
    }

    private function initiateMpesaStkPush(string $msisdn, ?string $fallbackInvoiceId = null)
    {
        $normalizedMsisdn = normalizeMpesaPhoneNumber($msisdn);
        $validator = validator(['msisdn' => $normalizedMsisdn], ['msisdn' => ['required', 'string', 'regex:/^2547\\d{8}$/']], [
            'msisdn.required' => __('The phone number is required.'),
            'msisdn.string' => __('The phone number must be a valid string.'),
            'msisdn.regex' => __('Use a valid Safaricom number like 07XXXXXXXX or 2547XXXXXXXX.'),
        ]);

        if ($validator->fails()) {
            $redirectUrl = $fallbackInvoiceId ? route('payment', ['invoice_id' => $fallbackInvoiceId]) : url()->previous();

            return redirect($redirectUrl)
                ->withErrors($validator)
                ->withInput(['msisdn' => $msisdn])
                ->with(['messege' => $validator->errors()->first('msisdn'), 'alert-type' => 'error']);
        }

        try {
            $payment_setting = $this->get_basic_payment_info();
            $stkPushService = new MpesaStkPushService($payment_setting);
            $order = session()->get('order');

            $result = $stkPushService->initiate(
                phoneNumber: $normalizedMsisdn,
                amount: (float) session()->get('paid_amount'),
                accountReference: (string) ($order?->invoice_id ?? ('INV' . now()->timestamp)),
                description: 'Order ' . (string) ($order?->invoice_id ?? ('INV' . now()->timestamp))
            );

            $response = $result['response'] ?? [];
            if (($response['ResponseCode'] ?? null) !== '0') {
                return $this->renderMpesaPendingPage(
                    status: 'failed',
                    order: $order,
                    title: __('Unable to send M-Pesa STK Push'),
                    message: $response['ResponseDescription'] ?? __('M-Pesa did not accept the STK Push request.'),
                    details: $response
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
                ]
            );
        } catch (Exception $e) {
            info($e->getMessage());

            $order = session()->get('order');

            return $this->renderMpesaPendingPage(
                status: 'failed',
                order: $order,
                title: __('Unable to send M-Pesa STK Push'),
                message: __('We could not send the STK Push right now. Please confirm the phone number and gateway credentials, then try again.'),
                details: [
                    'error' => $e->getMessage(),
                    'phone_number' => $normalizedMsisdn ?? $msisdn,
                    'invoice_id' => $fallbackInvoiceId,
                ]);
        }
    }

    private function renderMpesaPendingPage(string $status, $order, string $title, string $message, array $details = [])
    {
        return view('basicpayment::gateway-actions.mpesa-stk-push-status', [
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'details' => $details,
            'order' => $order,
            'pollUrl' => $order ? route('mpesa-stk-push.status', ['invoice_id' => $order->invoice_id], false) : null,
            'expiresAt' => $order ? $order->updated_at?->copy()->addSeconds(self::MPESA_STK_PUSH_TIMEOUT_SECONDS)?->toIso8601String() : null,
        ]);
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
        $expiresAt = $order->updated_at?->copy()->addSeconds(self::MPESA_STK_PUSH_TIMEOUT_SECONDS);

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

    private function resolveSubscriptionPlan(string $planId): ?array
    {
        return collect($this->subscriptionPlans())->firstWhere('id', $planId);
    }

    private function subscriptionPlans(): array
    {
        return [
            [
                'id' => 'month-1',
                'name' => __('1 Month Plan'),
                'amount' => 500,
                'price' => 'KES 500',
                'period' => __('month'),
                'billing' => __('Billed every month'),
            ],
            [
                'id' => 'month-3',
                'name' => __('3 Months Plan'),
                'amount' => 1000,
                'price' => 'KES 1,000',
                'period' => __('3 months'),
                'billing' => __('Billed every 3 months'),
            ],
            [
                'id' => 'month-6',
                'name' => __('6 Months Plan'),
                'amount' => 2500,
                'price' => 'KES 2,500',
                'period' => __('6 months'),
                'billing' => __('Billed every 6 months'),
            ],
            [
                'id' => 'year-1',
                'name' => __('1 Year Plan'),
                'amount' => 4000,
                'price' => 'KES 4,000',
                'period' => __('year'),
                'billing' => __('Billed every year'),
            ],
        ];
    }
}

