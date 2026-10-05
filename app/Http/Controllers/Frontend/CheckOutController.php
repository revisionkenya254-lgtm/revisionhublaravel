<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiCreditPurchaseService;
use App\Traits\GetGlobalInformationTrait;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CheckOutController extends Controller
{
    use GetGlobalInformationTrait;

    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(Request $request)
    {
        $user = userAuth();

        $paymentService = app(\Modules\BasicPayment\app\Services\PaymentMethodService::class);
        $activeGateways = $paymentService->getActiveGatewaysWithDetails();

        if ($request->string('purchase_type')->toString() === 'ai_credits' || $request->filled('ai_credit_amount')) {
            $purchaseService = app(AiCreditPurchaseService::class);
            $aiCreditPurchase = $this->resolveAiCreditPurchase($request, $purchaseService);

            if (!$aiCreditPurchase) {
                return redirect()->route('ai-chat.credits')->with(['messege' => __('Please choose a valid AI credit package.'), 'alert-type' => 'error']);
            }

            Session::forget('subscription_plan_id');
            Session::forget('subscription_upgrade_quote');
            Session::put('ai_credit_purchase', $aiCreditPurchase);
            Session::put('payable_amount', $aiCreditPurchase['amount']);

            $products = collect([$this->makeAiCreditCartItem($aiCreditPurchase)]);
            $cart_count = 1;
            $discountPercent = 0;
            $discountAmount = 0;
            $total = defaultCurrency($aiCreditPurchase['amount']);
            $coupon = '';

            return view('frontend.pages.checkout')->with([
                'products' => $products,
                'cart_count' => $cart_count,
                'total' => $total,
                'discountAmount' => $discountAmount,
                'discountPercent' => $discountPercent,
                'coupon' => $coupon,
                'payable_amount' => $aiCreditPurchase['amount'],
                'paymentService' => $paymentService,
                'activeGateways' => $activeGateways,
            ]);
        }

        if ($request->filled('subscription_plan')) {
            $subscriptionPlan = $this->resolveSubscriptionPlan($request->string('subscription_plan')->toString());

            if (!$subscriptionPlan) {
                return redirect()->route('subscriptions')->with(['messege' => __('Please choose a valid subscription plan.'), 'alert-type' => 'error']);
            }

            $upgradeQuote = revisionHubSubscriptionUpgradeQuote($user, $subscriptionPlan);
            if (! $upgradeQuote['allowed']) {
                return redirect()->route('subscriptions')->with(['messege' => $upgradeQuote['message'], 'alert-type' => 'error']);
            }

            $subscriptionPlans = $this->subscriptionPlans();
            Session::put('subscription_plan_id', $subscriptionPlan['id']);
            Session::put('subscription_upgrade_quote', $upgradeQuote);
            $payable_amount = $upgradeQuote['payable_amount'];
            Session::put('payable_amount', $payable_amount);

            $products = collect([$this->makeSubscriptionCartItem($subscriptionPlan, $upgradeQuote)]);
            $cart_count = 1;
            $discountPercent = 0;
            $discountAmount = 0;
            $total = defaultCurrency($payable_amount);
            $coupon = '';

            return view('frontend.pages.checkout')->with([
                'products' => $products,
                'cart_count' => $cart_count,
                'total' => $total,
                'discountAmount' => $discountAmount,
                'discountPercent' => $discountPercent,
                'coupon' => $coupon,
                'payable_amount' => $payable_amount,
                'paymentService' => $paymentService,
                'activeGateways' => $activeGateways,
                'subscriptionPlans' => $subscriptionPlans,
                'selectedSubscriptionPlan' => $subscriptionPlan,
                'subscriptionUpgradeQuote' => $upgradeQuote,
            ]);
        }

        $cart_count = $user->cart_count;

        if($cart_count == 0){
            return redirect()->route('catalog')->with(['messege' => __('Please add some resources in your cart.'), 'alert-type' => 'error']);
        }

        $cartTotal = $user->cart_total;
        $discountPercent = Session::has('offer_percentage') ? Session::get('offer_percentage') : 0;
        $discountAmount = ($cartTotal * $discountPercent) / 100;
        $total = defaultCurrency($cartTotal - $discountAmount);
        $coupon = Session::has('coupon_code') ? Session::get('coupon_code') : '';

        $payable_amount = $cartTotal - $discountAmount;
        Session::put('payable_amount', $payable_amount);

        $products = $user->carts()->with([
            'course:id,title,slug,price,discount,thumbnail',
            'product:id,title,slug,type,price,discount,thumbnail',
        ])->get(['id', 'user_id', 'course_id', 'product_id', 'item_type']);

        $subscriptionPlans = $this->subscriptionPlans();

        Session::forget('subscription_plan_id');
        Session::forget('subscription_upgrade_quote');

        return view('frontend.pages.checkout')->with([
            'products' => $products,
            'cart_count' => $cart_count,
            'total' => $total,
            'discountAmount' => $discountAmount,
            'discountPercent' => $discountPercent,
            'coupon' => $coupon,
            'payable_amount' => $payable_amount,
            'paymentService' => $paymentService,
            'activeGateways' => $activeGateways,
            'subscriptionPlans' => $subscriptionPlans,
            'selectedSubscriptionPlan' => null,
        ]);
    }

    private function resolveSubscriptionPlan(string $planId): ?array
    {
        return collect($this->subscriptionPlans())->firstWhere('id', $planId);
    }

    private function resolveAiCreditPurchase(Request $request, AiCreditPurchaseService $purchaseService): ?array
    {
        $amount = (int) $request->input('ai_credit_amount', 0);

        if ($amount <= 0) {
            return null;
        }

        return $purchaseService->resolve($amount);
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
                'ai_bonus_credits' => revisionHubSubscriptionBonusCredits(500),
            ],
            [
                'id' => 'month-3',
                'name' => __('3 Months Plan'),
                'amount' => 1000,
                'price' => 'KES 1,000',
                'period' => __('3 months'),
                'billing' => __('Billed every 3 months'),
                'ai_bonus_credits' => revisionHubSubscriptionBonusCredits(1000),
            ],
            [
                'id' => 'month-6',
                'name' => __('6 Months Plan'),
                'amount' => 2500,
                'price' => 'KES 2,500',
                'period' => __('6 months'),
                'billing' => __('Billed every 6 months'),
                'ai_bonus_credits' => revisionHubSubscriptionBonusCredits(2500),
            ],
            [
                'id' => 'year-1',
                'name' => __('1 Year Plan'),
                'amount' => 4000,
                'price' => 'KES 4,000',
                'period' => __('year'),
                'billing' => __('Billed every year'),
                'ai_bonus_credits' => revisionHubSubscriptionBonusCredits(4000),
            ],
        ];
    }

    private function makeAiCreditCartItem(array $purchase): object
    {
        return new class($purchase) {
            public function __construct(public array $purchase)
            {
            }

            public string $item_type = 'ai_credits';

            public function purchasable(): object
            {
                return (object) [
                    'title' => __('AI Credits Recharge'),
                    'slug' => 'ai-credits',
                    'thumbnail' => null,
                    'price' => $this->purchase['amount'],
                    'discount' => 0,
                    'plan_id' => null,
                    'credits' => $this->purchase['credits'],
                    'amount_label' => $this->purchase['price'] ?? null,
                    'subtitle' => $this->purchase['subtitle'] ?? null,
                ];
            }
        };
    }

    private function makeSubscriptionCartItem(array $plan, ?array $quote = null): object
    {
        return new class($plan, $quote) {
            public function __construct(public array $plan, public ?array $quote)
            {
            }

            public string $item_type = 'subscription';

            public function purchasable(): object
            {
                return (object) [
                    'title' => $this->plan['name'],
                    'slug' => $this->plan['id'],
                    'thumbnail' => null,
                    'price' => $this->quote['payable_amount'] ?? $this->plan['amount'],
                    'discount' => 0,
                    'plan_id' => $this->plan['id'],
                    'ai_bonus_credits' => $this->plan['ai_bonus_credits'] ?? 0,
                    'prorated_credit' => $this->quote['credit_amount'] ?? 0,
                ];
            }
        };
    }
}

