@extends('frontend.layouts.master')
@section('meta_title', 'Checkout' . ' || ' . $setting->app_name)
@section('contents')
    <x-frontend.breadcrumb
        :title="__('Checkout')"
        :links="[
            ['url' => route('home'), 'text' => __('Home')],
            ['url' => '', 'text' => __('Checkout')],
        ]"
    />

    @php
        $gatewayAccents = [
            'mpesa_stk_push' => '#16a34a',
            'paypal' => '#0070ba',
        ];
    @endphp

    <div class="checkout__area section-py-120">
        <div class="preloader-two preloader-two-fixed d-none">
            <div class="loader-icon-two"><img src="{{ asset(Cache::get('setting')->preloader) }}" alt="Preloader"></div>
        </div>
        <div class="container">
            <div class="row g-4">
                <div class="col-xl-4 col-lg-4">
                    <aside class="checkout-auth-card">
                        <div class="checkout-summary checkout-summary--featured">
                            <h2 class="title">{{ __('Cart totals') }}</h2>
                            <div class="checkout-summary__items">
                                @foreach ($products as $product)
                                    @php
                                        $checkoutItem = $product->purchasable();
                                        $checkoutUrl = $product->item_type === 'product'
                                            ? route('product.show', $checkoutItem?->slug)
                                            : ($product->item_type === 'subscription'
                                                ? route('checkout.index', ['subscription_plan' => $checkoutItem?->plan_id ?? ''])
                                                : route('course.show', $checkoutItem?->slug));
                                        $thumbnail = filled($checkoutItem?->thumbnail)
                                            ? asset($checkoutItem?->thumbnail)
                                            : asset($setting?->logo);
                                    @endphp
                                    <div class="checkout-summary__item">
                                        <img src="{{ $thumbnail }}" alt="{{ $checkoutItem?->title }}">
                                        <div>
                                            <strong><a href="{{ $checkoutUrl }}">{{ $checkoutItem?->title }}</a></strong>
                                            <span>{{ defaultCurrency($checkoutItem?->discount > 0 ? $checkoutItem?->discount : $checkoutItem?->price) }}</span>
                                            @if ($product->item_type === 'subscription' && filled($checkoutItem?->ai_bonus_credits ?? null))
                                                <small class="d-block text-success">
                                                    {{ __('Includes :credits bonus AI credits', ['credits' => number_format($checkoutItem?->ai_bonus_credits)]) }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (($subscriptionUpgradeQuote['kind'] ?? null) === 'upgrade' && ($subscriptionUpgradeQuote['credit_amount'] ?? 0) > 0)
                                <p class="mb-3 text-success small">
                                    {{ __('Upgrade credit for unused time: :amount', ['amount' => defaultCurrency($subscriptionUpgradeQuote['credit_amount'])]) }}
                                </p>
                            @endif

                            <ul class="list-wrap pb-0">
                                <li>{{ __('Total Items') }}<span>{{ $cart_count }}</span></li>
                                <li>
                                    @if (Session::has('coupon_code'))
                                        <p class="coupon-discount m-0">
                                            <span>{{ __('Discount') }}</span>
                                            <br>
                                            <small>{{ $coupon }} ({{ $discountPercent }} %)<a class="ms-2 text-danger" href="/remove-coupon">&times;</a></small>
                                        </p>
                                        <span class="discount-amount">{{ defaultCurrency($discountAmount) }}</span>
                                    @else
                                        <p class="coupon-discount m-0">
                                            <span>{{ __('Discount') }}</span>
                                        </p>
                                        <span class="discount-amount">{{ defaultCurrency(0) }}</span>
                                    @endif
                                </li>
                                <li>{{ __('Total') }} <span class="amount">{{ defaultCurrency($payable_amount) }}</span></li>

                                @if ($payable_amount > 0)
                                    <h6 class="bold payable-bold">{{ __('Payable with gateway charge') }}:</h6>

                                    @php
                                        $firstGatewayKey = array_key_first($activeGateways);
                                        $firstGatewayName = $firstGatewayKey ? ($activeGateways[$firstGatewayKey]['name'] ?? '') : '';
                                        $firstGatewayCurrency = $firstGatewayKey ? ($paymentService->getGatewayCurrencyCode($firstGatewayKey) ?? getSessionCurrency()) : getSessionCurrency();
                                        $firstGatewayAmount = $firstGatewayKey && $paymentService->isCurrencySupported($firstGatewayKey, $firstGatewayCurrency)
                                            ? $paymentService->getPayableAmount($firstGatewayKey, $payable_amount, $firstGatewayCurrency)
                                            : null;
                                        $firstGatewayAccent = $firstGatewayKey ? ($gatewayAccents[$firstGatewayKey] ?? '#5b67f1') : '#5b67f1';
                                    @endphp

                                    <div class="payable-text checkout-summary__gateway-total">
                                        <span id="summaryGatewayName">{{ $firstGatewayName }}</span>
                                        <span id="summaryGatewayTotal" style="color: {{ $firstGatewayAccent }};">{{ $firstGatewayAmount?->payable_with_charge }} {{ $firstGatewayCurrency }}</span>
                                    </div>
                                @endif
                            </ul>
                        </div>

                        @if (!empty($subscriptionPlans ?? []) && ($selectedSubscriptionPlan ?? null))
                            <div class="checkout-subscription-switcher" id="checkoutSubscriptionSwitcher">
                                <div class="checkout-subscription-switcher__head">
                                    <div>
                                        <span>{{ __('Subscription plans') }}</span>
                                        <strong>{{ __('Switch your plan without leaving checkout') }}</strong>
                                    </div>
                                    <a href="{{ route('subscriptions', ['plan' => $selectedSubscriptionPlan['id']]) }}">
                                        {{ __('Compare plans') }}
                                    </a>
                                </div>

                                <div class="checkout-subscription-switcher__grid">
                                    @foreach ($subscriptionPlans as $plan)
                                        @php
                                            $isSelectedPlan = ($selectedSubscriptionPlan['id'] ?? '') === ($plan['id'] ?? '');
                                        @endphp
                                        <a
                                            href="{{ route('checkout.index', ['subscription_plan' => $plan['id']]) }}"
                                            class="checkout-subscription-switcher__card {{ $isSelectedPlan ? 'is-selected' : '' }}"
                                            aria-current="{{ $isSelectedPlan ? 'true' : 'false' }}"
                                        >
                                            <div class="checkout-subscription-switcher__copy">
                                                <strong>{{ $plan['name'] }}</strong>
                                                <span>{{ $plan['price'] }} · {{ $plan['billing'] }}</span>
                                            </div>
                                            <div class="checkout-subscription-switcher__meta">
                                                <span>{{ __('+ :credits AI credits', ['credits' => number_format($plan['ai_bonus_credits'] ?? 0)]) }}</span>
                                                @if ($isSelectedPlan)
                                                    <small>{{ __('Selected') }}</small>
                                                @else
                                                    <small>{{ __('Switch') }}</small>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="checkout-auth-card__divider"></div>

                        <div class="checkout-auth-card__icon" aria-hidden="true">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <span class="checkout-auth-card__eyebrow">{{ __('Account') }}</span>
                        <h3>{{ __('Link this purchase to an account') }}</h3>
                        <p>{{ __('Purchases are attached to your account so you can view them later in your library, redownload them, and access them across devices.') }}</p>

                        <ul class="checkout-auth-card__list">
                            <li><i class="fas fa-circle-check"></i> {{ __('Ownership stays in your library') }}</li>
                            <li><i class="fas fa-circle-check"></i> {{ __('Access on all devices') }}</li>
                            <li><i class="fas fa-circle-check"></i> {{ __('Download history stays organized') }}</li>
                        </ul>

                        @guest
                            <div class="checkout-auth-card__notice">
                                {{ __('Guests can browse, but sign in or create an account before paying so we can attach the resource to your profile.') }}
                            </div>
                            <a href="{{ route('login') }}" class="btn btn-dark btn-lg w-100 checkout-auth-card__cta">
                                {{ __('Sign in to continue') }}
                            </a>
                            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg w-100 checkout-auth-card__cta">
                                {{ __('Create account') }}
                            </a>
                        @else
                            <div class="checkout-auth-card__signed-in">
                                <strong>{{ userAuth()->name }}</strong>
                                <span>{{ userAuth()->email }}</span>
                            </div>
                            <a href="{{ route('student.dashboard') }}" class="btn btn-dark btn-lg w-100 checkout-auth-card__cta">
                                {{ __('Open dashboard') }}
                            </a>
                            <a href="{{ route('cart') }}" class="btn btn-outline-primary btn-lg w-100 checkout-auth-card__cta">
                                {{ __('Review cart') }}
                            </a>
                        @endguest
                    </aside>
                </div>

                <div class="col-xl-8 col-lg-8">
                    <div id="show_currency_notifications">
                        <div class="alert alert-warning d-none"></div>
                    </div>

                    <div class="checkout-card" id="checkoutGatewayCard" style="--gateway-accent: {{ $firstGatewayAccent ?? '#5b67f1' }};">
                        <div class="checkout-card__header">
                            <h3>{{ __('Select Payment Method') }}</h3>
                            <p>{{ __('Choose a payment method tab to reveal its form, complete the required details, then place your order.') }}</p>
                        </div>

                        @if ($payable_amount > 0)
                            <div class="gateway-tabs">
                                <div class="gateway-tabs__nav" role="tablist" aria-label="{{ __('Payment methods') }}">
                                    @foreach ($activeGateways as $gatewayKey => $gatewayDetails)
                                        @php
                                            $gatewayCurrency = $paymentService->getGatewayCurrencyCode($gatewayKey) ?? getSessionCurrency();
                                            $supported = $paymentService->isCurrencySupported($gatewayKey, $gatewayCurrency);
                                            $gatewayAmount = $supported ? $paymentService->getPayableAmount($gatewayKey, $payable_amount, $gatewayCurrency) : null;
                                            $accent = $gatewayAccents[$gatewayKey] ?? '#5b67f1';
                                        @endphp
                                        <button type="button"
                                            class="gateway-tab-btn {{ $loop->first ? 'is-active' : '' }}"
                                            data-target="#gateway-pane-{{ $gatewayKey }}"
                                            data-method="{{ $gatewayKey }}"
                                            data-name="{{ $gatewayDetails['name'] }}"
                                            data-supported="{{ $supported ? '1' : '0' }}"
                                            data-total="{{ $gatewayAmount?->payable_with_charge }}"
                                            data-currency="{{ $gatewayCurrency }}"
                                            data-accent="{{ $accent }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            <span class="gateway-tab-btn__icon-wrap">
                                                <span class="gateway-tab-btn__icon">
                                                    <img src="{{ asset($gatewayDetails['logo']) }}" alt="{{ $gatewayDetails['name'] }}">
                                                </span>
                                            </span>
                                            <span class="gateway-tab-btn__content">
                                                <span class="gateway-tab-btn__name">{{ $gatewayDetails['name'] }}</span>
                                                <span class="gateway-tab-btn__meta">
                                                    {{ $supported ? __('Click to open form') : __('Currency not supported') }}
                                                </span>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="gateway-tab-content">
                                    @foreach ($activeGateways as $gatewayKey => $gatewayDetails)
                                        @php
                                            $gatewayCurrency = $paymentService->getGatewayCurrencyCode($gatewayKey) ?? getSessionCurrency();
                                            $supported = $paymentService->isCurrencySupported($gatewayKey, $gatewayCurrency);
                                            $gatewayAmount = $supported ? $paymentService->getPayableAmount($gatewayKey, $payable_amount, $gatewayCurrency) : null;
                                            $accent = $gatewayAccents[$gatewayKey] ?? '#5b67f1';
                                        @endphp
                                        <section id="gateway-pane-{{ $gatewayKey }}"
                                            class="gateway-pane {{ $loop->first ? 'is-active' : '' }}"
                                            role="tabpanel"
                                            data-method="{{ $gatewayKey }}"
                                            data-name="{{ $gatewayDetails['name'] }}"
                                            data-supported="{{ $supported ? '1' : '0' }}"
                                            data-total="{{ $gatewayAmount?->payable_with_charge }}"
                                            data-currency="{{ $gatewayCurrency }}"
                                            data-accent="{{ $accent }}">
                                            <div class="gateway-pane__header">
                                                <div class="gateway-pane__logo">
                                                    <img src="{{ asset($gatewayDetails['logo']) }}" alt="{{ $gatewayDetails['name'] }}" class="img-fluid">
                                                </div>
                                                <div>
                                                    <h5>{{ $gatewayDetails['name'] }}</h5>
                                                    @if ($supported)
                                                        <p>{{ __('Payable amount') }}: {{ $gatewayAmount?->payable_with_charge }} {{ $gatewayCurrency }}</p>
                                                    @else
                                                        <p>{{ __('This payment method does not support your selected currency.') }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="gateway-pane__body">
                                                @if ($gatewayKey === 'mpesa_stk_push')
                                                    <div class="payment-extra-box">
                                                        <div class="payment-fields-grid payment-fields-grid--single">
                                                            <div>
                                                                <label for="checkout_msisdn">{{ __('M-Pesa Phone Number') }}</label>
                                                                <input type="text" id="checkout_msisdn" class="form-control" placeholder="07XXXXXXXX or 2547XXXXXXXX" value="{{ old('checkout_msisdn', userAuth()->phone ?? '') }}">
                                                                <small>{{ __('Enter the Safaricom number that should receive the STK push prompt.') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="payment-extra-box payment-extra-box--info">
                                                        <div class="payment-extra-box__title">{{ $gatewayDetails['name'] }} {{ __('Payment Form') }}</div>
                                                        <div class="payment-fields-grid">
                                                            <div>
                                                                <label>{{ __('Selected Gateway') }}</label>
                                                                <input type="text" class="form-control" value="{{ $gatewayDetails['name'] }}" readonly>
                                                                <small>{{ __('This order will continue through this payment provider.') }}</small>
                                                            </div>
                                                            <div>
                                                                <label>{{ __('Checkout Flow') }}</label>
                                                                <input type="text" class="form-control" value="{{ __('Redirect to secure payment page') }}" readonly>
                                                                <small>{{ __('After placing the order, you will be taken to complete payment securely.') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="gateway-pane__footer">
                                                <button type="button"
                                                    class="btn btn-lg gateway-place-order-btn"
                                                    data-method="{{ $gatewayKey }}"
                                                    data-accent="{{ $accent }}"
                                                    {{ $supported ? '' : 'disabled' }}>
                                                    {{ __('Place Order with') }} {{ $gatewayDetails['name'] }}
                                                </button>
                                            </div>
                                        </section>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <form action="{{ route('pay-via-free-gateway') }}" method="POST">
                                @csrf
                                <button class="btn btn-primary btn-lg">{{ __('Complete Free Order') }}</button>
                            </form>
                        @endif
                    </div>

                    <div class="checkout-choice-grid checkout-choice-grid--main">
                        <div class="checkout-choice-card checkout-choice-card--active">
                            <div class="checkout-choice-card__icon checkout-choice-card__icon--dark" aria-hidden="true">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <span class="checkout-choice-card__eyebrow">{{ __('Direct purchase') }}</span>
                            <h2>{{ __('Buy this resource only') }}</h2>
                            <p>{{ __('Continue to pay for the selected resource right here and finish checkout as usual.') }}</p>
                            <div class="checkout-choice-card__meta">
                                <span><i class="fas fa-check"></i> {{ __('One-time payment') }}</span>
                                <span><i class="fas fa-download"></i> {{ __('Instant access after payment') }}</span>
                            </div>
                            <a href="#checkoutGatewayCard" class="btn btn-dark btn-lg checkout-choice-card__cta">
                                {{ __('Continue direct purchase') }}
                            </a>
                        </div>

                        <div class="checkout-choice-card checkout-choice-card--accent">
                            <div class="checkout-choice-card__icon checkout-choice-card__icon--violet" aria-hidden="true">
                                <i class="fas fa-crown"></i>
                            </div>
                            <span class="checkout-choice-card__eyebrow">{{ __('Subscription') }}</span>
                            <h2>{{ __('Unlock everything with a plan') }}</h2>
                            <p>{{ __('If you want full access to all past papers, notes, quizzes, and videos, switch to a subscription plan instead.') }}</p>
                            <div class="checkout-choice-card__meta">
                                <span><i class="fas fa-layer-group"></i> {{ __('Unlimited resources') }}</span>
                                <span><i class="fas fa-rotate"></i> {{ __('Cancel anytime') }}</span>
                            </div>
                            <a href="#checkoutSubscriptionSwitcher" class="btn btn-outline-primary btn-lg checkout-choice-card__cta">
                                {{ __('Browse plans on this page') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('styles')
    <style>
        .checkout-choice-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .checkout-choice-grid--sidebar {
            grid-template-columns: 1fr;
            margin-top: 16px;
            margin-bottom: 0;
        }

        .checkout-choice-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 18px 20px;
            border: 1px solid #dfe3ff;
            border-radius: 18px;
            min-height: 100%;
            background:
                linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
        }

        .checkout-choice-card__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 16px;
            font-size: 18px;
            flex: 0 0 auto;
        }

        .checkout-choice-card__icon--dark {
            background: #111827;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(17, 24, 39, 0.18);
        }

        .checkout-choice-card__icon--violet {
            background: rgba(91, 103, 241, 0.12);
            color: #5b67f1;
            box-shadow: 0 10px 20px rgba(91, 103, 241, 0.14);
        }

        .checkout-choice-card--active {
            background:
                radial-gradient(circle at top right, rgba(15, 23, 42, 0.05), transparent 34%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-color: #d7dcff;
        }

        .checkout-choice-card--accent {
            background:
                radial-gradient(circle at top right, rgba(91, 103, 241, 0.08), transparent 34%),
                linear-gradient(180deg, #ffffff 0%, #fbfbff 100%);
        }

        .checkout-choice-card__eyebrow {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            width: fit-content;
            background: rgba(91, 103, 241, 0.1);
            color: #5b67f1;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .checkout-choice-card h2 {
            margin: 0;
            color: #111827;
            font-size: 22px;
            line-height: 1.15;
            letter-spacing: -0.03em;
        }

        .checkout-choice-card p {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
        }

        .checkout-choice-card__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        .checkout-choice-card__meta span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .checkout-choice-card__meta i {
            color: #5b67f1;
        }

        .checkout-choice-card__cta {
            margin-top: auto;
            align-self: flex-start;
        }

        .checkout-choice-card--active .checkout-choice-card__cta {
            background: #111827;
            border-color: #111827;
            color: #ffffff;
        }

        .checkout-choice-card--active .checkout-choice-card__cta:hover,
        .checkout-choice-card--active .checkout-choice-card__cta:focus-visible {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .checkout-choice-card--accent .checkout-choice-card__cta {
            background: #111827;
            border-color: #111827;
            color: #ffffff;
        }

        .checkout-choice-card--accent .checkout-choice-card__cta:hover,
        .checkout-choice-card--accent .checkout-choice-card__cta:focus-visible {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .checkout-choice-card__cta.btn {
            min-width: 100%;
        }

        .checkout-choice-grid--sidebar .checkout-choice-card {
            padding: 16px 18px;
        }

        .checkout-choice-grid--sidebar .checkout-choice-card h2 {
            font-size: 20px;
        }

        .checkout-choice-banner__actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex: 0 0 auto;
            flex-wrap: wrap;
        }

        .checkout-auth-card {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 22px 20px;
            border: 1px solid #dfe3ff;
            border-radius: 22px;
            background:
                radial-gradient(circle at top left, rgba(91, 103, 241, 0.08), transparent 30%),
                linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
        }

        .checkout-auth-card__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: rgba(91, 103, 241, 0.12);
            color: #5b67f1;
            font-size: 20px;
            box-shadow: 0 10px 20px rgba(91, 103, 241, 0.14);
        }

        .checkout-auth-card__eyebrow {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(91, 103, 241, 0.1);
            color: #5b67f1;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .checkout-auth-card h3 {
            margin: 0;
            color: #111827;
            font-size: 20px;
            line-height: 1.15;
            letter-spacing: -0.03em;
        }

        .checkout-auth-card p {
            margin: 0;
            color: #4b5563;
            line-height: 1.65;
        }

        .checkout-auth-card__list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .checkout-auth-card__list li {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            color: #374151;
            line-height: 1.55;
            font-size: 14px;
        }

        .checkout-auth-card__list i {
            margin-top: 4px;
            color: #16a34a;
        }

        .checkout-auth-card__notice {
            padding: 12px 14px;
            border-radius: 16px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
            line-height: 1.6;
            font-size: 14px;
        }

        .checkout-auth-card__signed-in {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 12px 14px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }

        .checkout-auth-card__signed-in strong {
            color: #111827;
        }

        .checkout-auth-card__signed-in span {
            color: #6b7280;
            font-size: 14px;
        }

        .checkout-auth-card__cta {
            min-width: 100%;
        }

        .checkout-summary--featured {
            margin-top: 10px;
            padding: 18px;
            border-radius: 20px;
            background:
                linear-gradient(180deg, rgba(91, 103, 241, 0.08) 0%, rgba(255, 255, 255, 0.96) 42%, #ffffff 100%);
            border: 1px solid rgba(91, 103, 241, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
        }

        .checkout-summary--featured .title {
            margin-bottom: 18px;
            color: #111827;
            font-size: 18px;
            letter-spacing: -0.03em;
        }

        .checkout-summary--featured .checkout-summary__items {
            margin-bottom: 16px;
        }

        .checkout-summary--featured .checkout-summary__item img {
            width: 72px;
            height: 54px;
        }

        .checkout-summary--featured .checkout-summary__item strong a {
            color: #111827;
        }

        .checkout-summary--featured .checkout-summary__item span {
            color: #4b5563;
            font-weight: 700;
        }

        .checkout-summary--featured .list-wrap {
            padding-top: 4px;
        }

        .checkout-card {
            --gateway-accent: #5b67f1;
            background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
            border: 1px solid #e8eaf4;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.06);
        }
        .checkout-card__header h3 {
            margin-bottom: 6px;
            color: #1d275f;
        }
        .checkout-card__header p {
            color: #6b7280;
            margin-bottom: 24px;
        }
        .checkout-summary__items {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 18px;
        }
        .checkout-summary__item {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .checkout-summary__item img {
            width: 62px;
            height: 46px;
            object-fit: cover;
            border-radius: 10px;
        }
        .checkout-summary__item div {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        .checkout-summary__item strong {
            color: #1d275f;
        }
        .checkout-summary__item span {
            color: #6b7280;
            font-size: 14px;
        }
        .checkout-summary__gateway-total {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .checkout-subscription-switcher {
            padding: 16px 16px 18px;
            margin-top: 14px;
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(91, 103, 241, 0.07) 0%, rgba(255, 255, 255, 0.95) 100%);
            border: 1px solid rgba(91, 103, 241, 0.16);
        }

        .checkout-subscription-switcher__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .checkout-subscription-switcher__head span {
            display: block;
            color: #5b67f1;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .checkout-subscription-switcher__head strong {
            display: block;
            color: #111827;
            font-size: 15px;
            line-height: 1.35;
            letter-spacing: -0.02em;
        }

        .checkout-subscription-switcher__head a {
            flex: 0 0 auto;
            color: #5b67f1;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .checkout-subscription-switcher__grid {
            display: grid;
            gap: 10px;
        }

        .checkout-subscription-switcher__card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e5e7f2;
            color: #111827;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .checkout-subscription-switcher__card:hover {
            transform: translateY(-1px);
            border-color: rgba(91, 103, 241, 0.22);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            color: #111827;
        }

        .checkout-subscription-switcher__card.is-selected {
            background: linear-gradient(180deg, rgba(91, 103, 241, 0.08) 0%, rgba(91, 103, 241, 0.04) 100%);
            border-color: rgba(91, 103, 241, 0.24);
        }

        .checkout-subscription-switcher__copy {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .checkout-subscription-switcher__copy strong {
            font-size: 14px;
            font-weight: 800;
            line-height: 1.2;
        }

        .checkout-subscription-switcher__copy span {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.35;
        }

        .checkout-subscription-switcher__meta {
            display: grid;
            justify-items: end;
            gap: 4px;
            flex: 0 0 auto;
        }

        .checkout-subscription-switcher__meta span {
            color: #5b67f1;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .checkout-subscription-switcher__meta small {
            color: #16a34a;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .gateway-tabs {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .gateway-tabs__nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            border-bottom: 2px solid #e7eaf5;
            padding-bottom: 2px;
        }
        .gateway-tab-btn {
            position: relative;
            flex: 1 1 220px;
            min-width: 0;
            border: 0;
            background: transparent;
            color: #1d275f;
            padding: 14px 18px 18px;
            text-align: left;
            transition: .2s ease;
            display: flex;
            align-items: center;
            gap: 14px;
            border-radius: 14px 14px 0 0;
        }
        .gateway-tab-btn::after {
            content: '';
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: -4px;
            height: 3px;
            border-radius: 999px;
            background: rgba(17, 24, 39, 0.08);
            transition: .2s ease;
        }
        .gateway-tab-btn:hover {
            background: color-mix(in srgb, var(--gateway-accent) 6%, white);
        }
        .gateway-tab-btn.is-active {
            background: color-mix(in srgb, var(--gateway-accent) 10%, white);
        }
        .gateway-tab-btn.is-active::after {
            background: var(--gateway-accent);
            box-shadow: 0 8px 18px color-mix(in srgb, var(--gateway-accent) 24%, transparent);
        }
        .gateway-tab-btn__icon-wrap {
            flex: 0 0 auto;
        }
        .gateway-tab-btn__icon {
            width: 54px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #e6ebf8;
            border-radius: 12px;
            padding: 6px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        }
        .gateway-tab-btn__icon img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .gateway-tab-btn__content {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .gateway-tab-btn__name {
            display: block;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .gateway-tab-btn__meta {
            display: block;
            font-size: 13px;
            color: #6b7280;
        }
        .gateway-tab-content {
            min-width: 0;
        }
        .gateway-pane {
            display: none;
            border: 1px solid #e8eaf4;
            border-radius: 20px;
            padding: 24px;
            background: #fbfcff;
        }
        .gateway-pane.is-active {
            display: block;
        }
        .gateway-pane__header {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
        }
        .gateway-pane__header h5 {
            margin-bottom: 6px;
            color: #1d275f;
        }
        .gateway-pane__header p {
            margin-bottom: 0;
            color: #6b7280;
        }
        .gateway-pane__logo {
            width: 88px;
            height: 68px;
            min-width: 88px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #edf0f8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        .payment-extra-box {
            border: 1px solid #e3e7f6;
            border-radius: 16px;
            padding: 20px;
            background: #fff;
        }
        .payment-extra-box__title {
            font-size: 15px;
            font-weight: 700;
            color: #1d275f;
            margin-bottom: 14px;
        }
        .payment-fields-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .payment-fields-grid--single {
            grid-template-columns: 1fr;
        }
        .payment-extra-box label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1d275f;
        }
        .payment-extra-box small {
            display: block;
            margin-top: 8px;
            color: #6b7280;
        }
        .payment-extra-box--info p {
            margin-bottom: 0;
            color: #4b5563;
        }
        .gateway-pane__footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-start;
        }
        .gateway-place-order-btn {
            min-width: 300px;
            min-height: 58px;
            padding: 0 28px;
            background: #fbbf24;
            border: 2px solid #1f2937;
            color: #111827;
            border-radius: 999px;
            font-weight: 800;
            letter-spacing: 0.01em;
            box-shadow: 6px 6px 0 #1f2937;
        }
        .gateway-place-order-btn:hover,
        .gateway-place-order-btn:focus {
            background: #f59e0b;
            border-color: #111827;
            color: #111827;
            transform: translate(1px, 1px);
            box-shadow: 4px 4px 0 #1f2937;
        }
        .gateway-place-order-btn:disabled {
            opacity: .55;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        @media (max-width: 767px) {
            .checkout-auth-card {
                padding: 18px;
                border-radius: 18px;
            }

            .checkout-auth-card__icon {
                width: 48px;
                height: 48px;
                border-radius: 16px;
                font-size: 18px;
            }

            .checkout-choice-grid {
                grid-template-columns: 1fr;
                margin-bottom: 14px;
            }

            .checkout-choice-card {
                padding: 16px;
            }

            .checkout-choice-card__icon {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                font-size: 16px;
            }

            .checkout-choice-card h2 {
                font-size: 20px;
            }

            .checkout-choice-card__cta.btn {
                width: 100%;
            }

            .checkout-auth-card__cta {
                width: 100%;
            }

            .checkout-card {
                padding: 20px;
            }
            .gateway-tabs__nav {
                gap: 10px;
                border-bottom: 0;
                padding-bottom: 0;
            }
            .gateway-tab-btn {
                flex-basis: 100%;
                border: 1px solid #e7eaf5;
                border-radius: 14px;
                padding: 14px 16px;
            }
            .gateway-tab-btn::after {
                left: 12px;
                right: 12px;
                bottom: 0;
            }
            .gateway-pane {
                padding: 18px;
            }
            .gateway-pane__header {
                align-items: flex-start;
            }
            .gateway-place-order-btn {
                min-width: 100%;
                box-shadow: 5px 5px 0 #1f2937;
            }
            .payment-fields-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/default/checkout.js') }}?v={{ filemtime(public_path('frontend/js/default/checkout.js')) }}"></script>
@endpush
