@extends('frontend.layouts.master')

@section('meta_title', __('Subscriptions') . ' || ' . $setting->app_name)
@section('meta_description', __('Choose a study plan that unlocks all RevisionHub Kenya resources.'))
@section('body_class', 'subscription-page')

@php
    $isLoggedIn = auth('web')->check();
    $isStudent = $isLoggedIn && userAuth()->role === 'student';
    $dashboardUrl = $isLoggedIn
        ? (userAuth()->role === 'instructor' ? route('instructor.dashboard') : route('student.dashboard'))
        : route('login');
    $libraryUrl = $isStudent ? route('student.library') : route('login');
    $ordersUrl = $isStudent ? route('student.orders.index') : route('login');
    $wishlistUrl = $isStudent ? route('student.wishlist') : route('login');
    $reviewsUrl = $isStudent ? route('student.reviews.index') : route('login');
    $settingsUrl = $isStudent ? route('student.setting.index') : route('login');
    $messagesUrl = $isLoggedIn ? route('ai-chat') : route('login');
    $logoutLabel = __('Logout');

    $plans = [
        [
            'id' => 'month-1',
            'name' => __('1 Month Plan'),
            'period' => __('month'),
            'price' => 'KES 500',
            'billing' => __('Billed every month'),
            'ai_bonus_credits' => revisionHubSubscriptionBonusCredits(500),
            'badge' => __('Most Popular'),
            'featured' => true,
            'save' => null,
        ],
        [
            'id' => 'month-3',
            'name' => __('3 Months Plan'),
            'period' => __('3 months'),
            'price' => 'KES 1,000',
            'billing' => __('Billed every 3 months'),
            'ai_bonus_credits' => revisionHubSubscriptionBonusCredits(1000),
            'badge' => null,
            'featured' => false,
            'save' => __('Save KES 500'),
        ],
        [
            'id' => 'month-6',
            'name' => __('6 Months Plan'),
            'period' => __('6 months'),
            'price' => 'KES 2,500',
            'billing' => __('Billed every 6 months'),
            'ai_bonus_credits' => revisionHubSubscriptionBonusCredits(2500),
            'badge' => null,
            'featured' => false,
            'save' => __('Save KES 500'),
        ],
        [
            'id' => 'year-1',
            'name' => __('1 Year Plan'),
            'period' => __('year'),
            'price' => 'KES 4,000',
            'billing' => __('Billed every year'),
            'ai_bonus_credits' => revisionHubSubscriptionBonusCredits(4000),
            'badge' => null,
            'featured' => false,
            'save' => __('Save KES 2,000'),
        ],
    ];

    $selectedPlanId = request('plan', $plans[0]['id']);
    $selectedPlan = collect($plans)->firstWhere('id', $selectedPlanId) ?? $plans[0];
    $checkoutUrl = route('checkout.index');

    $planFeatures = [
        __('Unlimited access to all resources'),
        __('All video lessons'),
        __('Past papers & predictions'),
        __('Notes & study guides'),
        __('All quizzes & mock tests'),
        __('Access on all devices'),
        __('Regular content updates'),
        __('Cancel anytime'),
    ];

    $featureCards = [
        [
            'title' => __('All Resources'),
            'text' => __('Unlimited access to videos, notes, past papers, predictions & quizzes'),
            'icon' => 'fa-layer-group',
            'tone' => 'violet',
        ],
        [
            'title' => __('Download & Study'),
            'text' => __('Download materials and study offline any time, anywhere'),
            'icon' => 'fa-download',
            'tone' => 'green',
        ],
        [
            'title' => __('All Devices'),
            'text' => __('Access your learning on phone, tablet, laptop or desktop'),
            'icon' => 'fa-laptop',
            'tone' => 'amber',
        ],
        [
            'title' => __('Regular Updates'),
            'text' => __('New content added weekly to keep you ahead'),
            'icon' => 'fa-star',
            'tone' => 'rose',
        ],
        [
            'title' => __('Cancel Anytime'),
            'text' => __('No long-term contracts. Change or cancel whenever you need'),
            'icon' => 'fa-shield-alt',
            'tone' => 'blue',
        ],
    ];

    $compareRows = [
        __('Unlimited Access'),
        __('All Video Lessons'),
        __('Past Papers'),
        __('Notes & Study Guides'),
        __('Quizzes & Mock Tests'),
        __('Access on All Devices'),
        __('Regular Updates'),
        __('Cancel Anytime'),
    ];

    $faqs = [
        [
            'question' => __('What happens after my subscription expires?'),
            'answer' => __('Your premium access pauses until you renew. Your account and progress stay safe so you can pick up where you left off.'),
        ],
        [
            'question' => __('Can I cancel my subscription anytime?'),
            'answer' => __('Yes. You can cancel anytime and your plan will remain active until the end of the current billing period.'),
        ],
        [
            'question' => __('Can I change my plan later?'),
            'answer' => __('Absolutely. You can move to a longer plan whenever you are ready, and we will help you choose the best option.'),
        ],
        [
            'question' => __('What payment methods do you accept?'),
            'answer' => __('Payments are powered by M-Pesa, Visa and PayPal for a simple checkout experience.'),
        ],
    ];

    $sidebarItems = [
        ['label' => __('Dashboard'), 'href' => $dashboardUrl, 'icon' => 'fa-border-all', 'active' => false],
        ['label' => __('My Library'), 'href' => $libraryUrl, 'icon' => 'fa-book', 'active' => false],
        ['label' => __('Video Lessons'), 'href' => route('courses'), 'icon' => 'fa-video', 'active' => false],
        ['label' => __('Past Papers'), 'href' => route('catalog'), 'icon' => 'fa-file-alt', 'active' => false],
        ['label' => __('Notes'), 'href' => route('catalog'), 'icon' => 'fa-sticky-note', 'active' => false],
        ['label' => __('Predictions'), 'href' => route('catalog'), 'icon' => 'fa-bullseye', 'active' => false],
        ['label' => __('Quizzes'), 'href' => route('catalog'), 'icon' => 'fa-poll', 'active' => false],
        ['label' => __('My Orders'), 'href' => $ordersUrl, 'icon' => 'fa-shopping-cart', 'active' => false],
        ['label' => __('Cart'), 'href' => route('cart'), 'icon' => 'fa-cart-arrow-down', 'active' => false, 'badge' => Cart::content()->count()],
        ['label' => __('Subscriptions'), 'href' => route('subscriptions'), 'icon' => 'fa-crown', 'active' => true],
        ['label' => __('Wishlist'), 'href' => $wishlistUrl, 'icon' => 'fa-heart', 'active' => false],
        ['label' => __('Messages'), 'href' => $messagesUrl, 'icon' => 'fa-comment-dots', 'active' => false],
        ['label' => __('Reviews & Ratings'), 'href' => $reviewsUrl, 'icon' => 'fa-star', 'active' => false],
    ];
@endphp

@push('styles')
    <style>
        body.subscription-page {
            background:
                radial-gradient(circle at top left, rgba(91, 55, 255, 0.11), transparent 28%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.08), transparent 20%),
                #f6f7fb;
        }

        body.subscription-page .footer__area,
        body.subscription-page .scroll__top {
            display: none !important;
        }

        body.subscription-page .main-area.fix {
            padding-top: 0;
            background: transparent;
        }

        .subscription-shell {
            padding: 18px 0 40px;
        }

        .subscription-layout {
            display: grid;
            grid-template-columns: 228px minmax(0, 1fr);
            gap: 22px;
            align-items: start;
            max-width: 1480px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .subscription-sidebar {
            position: sticky;
            top: 16px;
            min-height: calc(100vh - 32px);
            display: flex;
            flex-direction: column;
            padding: 18px 14px 16px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(86, 36, 208, 0.1);
            box-shadow: 0 18px 40px rgba(17, 24, 39, 0.06);
            backdrop-filter: blur(12px);
        }

        .subscription-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 8px 18px;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(86, 36, 208, 0.08);
        }

        .subscription-brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .subscription-brand strong {
            display: block;
            color: #1c1d1f;
            font-size: 18px;
            line-height: 1.1;
            font-weight: 800;
        }

        .subscription-brand span {
            color: #5624d0;
            font-size: 15px;
            font-weight: 700;
        }

        .subscription-nav {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin: 0;
            padding: 0;
        }

        .subscription-nav li {
            list-style: none;
        }

        .subscription-nav a,
        .subscription-nav button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 42px;
            padding: 0 12px;
            border-radius: 12px;
            color: #48506a;
            font-size: 14px;
            font-weight: 500;
            background: transparent;
            border: 0;
            text-align: left;
            transition: color 0.18s ease, background-color 0.18s ease, transform 0.18s ease;
        }

        .subscription-nav a:hover,
        .subscription-nav button:hover {
            background: rgba(86, 36, 208, 0.07);
            color: #5624d0;
            transform: translateX(2px);
        }

        .subscription-nav a.is-active {
            background: rgba(86, 36, 208, 0.1);
            color: #5624d0;
            font-weight: 700;
        }

        .subscription-nav i {
            width: 18px;
            text-align: center;
            color: inherit;
        }

        .subscription-nav__badge {
            margin-left: auto;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #6b4df5;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .subscription-sidebar__section {
            padding: 12px 0;
        }

        .subscription-sidebar__section + .subscription-sidebar__section {
            border-top: 1px solid rgba(86, 36, 208, 0.08);
        }

        .subscription-sidebar__section-label {
            margin: 0 0 8px;
            padding: 0 8px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .subscription-promo {
            margin-top: auto;
            padding: 18px;
            border-radius: 20px;
            background: linear-gradient(135deg, #5b37ff 0%, #4b2ad8 100%);
            color: #fff;
            box-shadow: 0 18px 32px rgba(91, 55, 255, 0.24);
        }

        .subscription-promo__icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.16);
            margin-bottom: 12px;
            font-size: 18px;
        }

        .subscription-promo h3 {
            margin: 0 0 6px;
            color: #fff;
            font-size: 22px;
            font-weight: 800;
        }

        .subscription-promo p {
            margin: 0 0 16px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            line-height: 1.55;
        }

        .subscription-promo .revision-btn {
            width: 100%;
            min-height: 44px;
            border-color: rgba(255, 255, 255, 0.4);
            color: #fff;
            background: transparent;
        }

        .subscription-main {
            min-width: 0;
            padding-top: 2px;
        }

        .subscription-topbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
            padding: 20px 22px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(86, 36, 208, 0.08);
            box-shadow: 0 18px 40px rgba(17, 24, 39, 0.05);
            backdrop-filter: blur(12px);
        }

        .subscription-topbar__copy {
            display: grid;
            gap: 6px;
        }

        .subscription-topbar__eyebrow {
            color: #5b37ff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .subscription-topbar__copy h1 {
            margin: 0;
            color: #161c35;
            font-size: clamp(28px, 2.8vw, 40px);
            line-height: 1.02;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .subscription-topbar__copy p {
            margin: 0;
            color: #667085;
            font-size: 15px;
            line-height: 1.5;
        }

        .subscription-top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-self: end;
        }

        .subscription-topbar__security {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 14px;
            background: rgba(240, 253, 244, 0.92);
            border: 1px solid rgba(22, 163, 74, 0.12);
            color: #166534;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .subscription-topbar__security i {
            color: #16a34a;
        }

        .subscription-payment-icons {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(86, 36, 208, 0.08);
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.05);
        }

        .subscription-payment-icon {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid rgba(86, 36, 208, 0.08);
            color: #475569;
            box-shadow: 0 10px 20px rgba(17, 24, 39, 0.05);
            overflow: hidden;
            flex: 0 0 auto;
        }

        .subscription-payment-icon i {
            font-size: 22px;
            line-height: 1;
        }

        .subscription-payment-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .subscription-payment-icon--mpesa {
            border-color: rgba(22, 163, 74, 0.15);
            background: rgba(240, 253, 244, 0.95);
        }

        .subscription-payment-icon--visa {
            color: #1a1f71;
        }

        .subscription-payment-icon--paypal {
            color: #003087;
        }

        .subscription-payment-icon__tag {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            border-radius: 999px;
            background: #5b37ff;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .subscription-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px 6px 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(86, 36, 208, 0.08);
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.05);
        }

        .subscription-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .subscription-user strong {
            color: #1c1d1f;
            font-size: 14px;
            font-weight: 700;
        }

        .subscription-user span {
            display: block;
            color: #70758a;
            font-size: 12px;
        }

        .subscription-plan-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .subscription-plan {
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            padding: 26px 22px 22px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(86, 36, 208, 0.08);
            box-shadow: 0 18px 38px rgba(17, 24, 39, 0.05);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .subscription-plan:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 46px rgba(17, 24, 39, 0.08);
        }

        .subscription-plan.is-featured {
            border: 2px solid rgba(91, 55, 255, 0.45);
            box-shadow: 0 20px 46px rgba(91, 55, 255, 0.14);
        }

        .subscription-plan.is-selected {
            border-color: rgba(91, 55, 255, 0.75);
            box-shadow: 0 24px 54px rgba(91, 55, 255, 0.18);
            transform: translateY(-2px);
        }

        .subscription-plan__radio {
            position: absolute;
            inset: 0 auto auto 0;
            opacity: 0;
            pointer-events: none;
        }

        .subscription-plan__selector {
            position: absolute;
            top: 18px;
            right: 18px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(241, 245, 249, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: #667085;
            font-size: 12px;
            font-weight: 700;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .subscription-plan__selector-mark {
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 1.5px solid currentColor;
        }

        .subscription-plan.is-selected .subscription-plan__selector {
            background: rgba(236, 246, 255, 0.98);
            border-color: rgba(91, 55, 255, 0.18);
            color: #5b37ff;
        }

        .subscription-plan.is-selected .subscription-plan__selector-mark {
            background: #5b37ff;
            border-color: #5b37ff;
            color: #fff;
        }

        .subscription-plan.is-selected .subscription-plan__selector-mark::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 10px;
            line-height: 1;
        }

        .subscription-plan__selector-text {
            white-space: nowrap;
        }

        .subscription-plan__badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            padding: 7px 16px;
            border-radius: 999px;
            background: #5b37ff;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            box-shadow: 0 10px 18px rgba(91, 55, 255, 0.22);
        }

        .subscription-plan__title {
            margin: 0 0 14px;
            color: #20253f;
            font-size: 17px;
            line-height: 1.35;
            font-weight: 800;
        }

        .subscription-plan__price {
            display: flex;
            align-items: baseline;
            gap: 6px;
            color: #1f2544;
            font-size: 30px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .subscription-plan__period {
            color: #667085;
            font-size: 14px;
            font-weight: 600;
        }

        .subscription-plan__save {
            display: inline-flex;
            width: fit-content;
            margin-top: 12px;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e8f7ef;
            color: #18a14a;
            font-size: 12px;
            font-weight: 800;
        }

        .subscription-plan__billing {
            margin-top: 12px;
            color: #667085;
            font-size: 14px;
        }

        .subscription-plan__features {
            display: grid;
            gap: 10px;
            margin: 20px 0 18px;
            padding: 0;
        }

        .subscription-plan__features li {
            list-style: none;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: #3f475f;
            font-size: 14px;
            line-height: 1.45;
        }

        .subscription-plan__features i {
            margin-top: 2px;
            color: #16a34a;
        }

        .subscription-plan .revision-btn {
            margin-top: auto;
            min-height: 52px;
            width: 100%;
            padding-inline: 18px;
            border-radius: 16px;
            letter-spacing: 0;
            font-size: 15px;
        }

        .subscription-plan__cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #ffc52f;
            border: 1.5px solid #1d1d1d;
            color: #181c2f;
            box-shadow: 4px 6px 0 rgba(29, 29, 29, 0.82);
        }

        .subscription-plan__cta i {
            font-size: 13px;
            transition: transform 0.18s ease;
        }

        .subscription-plan .subscription-plan__cta:hover {
            background: #ffca3f;
            color: #10131f;
            transform: translate(-1px, -1px);
            box-shadow: 6px 8px 0 rgba(29, 29, 29, 0.9);
        }

        .subscription-plan .subscription-plan__cta:hover i {
            transform: translateX(2px);
        }

        .subscription-plan__footnote {
            margin-top: 12px;
            color: #667085;
            font-size: 12px;
            text-align: center;
        }

        .subscription-selection {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 0 0 16px;
            padding: 14px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(86, 36, 208, 0.08);
            box-shadow: 0 14px 30px rgba(17, 24, 39, 0.04);
        }

        .subscription-selection__copy {
            display: grid;
            gap: 4px;
        }

        .subscription-selection__eyebrow {
            color: #5b37ff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .subscription-selection__copy strong {
            color: #18203d;
            font-size: 16px;
            font-weight: 800;
        }

        .subscription-selection__copy span:last-child {
            color: #667085;
            font-size: 13px;
        }

        .subscription-selection__hint {
            color: #667085;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .subscription-features {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
            padding: 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(86, 36, 208, 0.08);
        }

        .subscription-feature {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 10px 8px;
            border-right: 1px solid rgba(86, 36, 208, 0.08);
        }

        .subscription-feature:last-child {
            border-right: 0;
        }

        .subscription-feature__icon {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            border-radius: 999px;
            font-size: 18px;
        }

        .subscription-feature--violet .subscription-feature__icon {
            background: rgba(91, 55, 255, 0.12);
            color: #5b37ff;
        }

        .subscription-feature--green .subscription-feature__icon {
            background: rgba(19, 161, 82, 0.12);
            color: #13a152;
        }

        .subscription-feature--amber .subscription-feature__icon {
            background: rgba(245, 158, 11, 0.14);
            color: #d97706;
        }

        .subscription-feature--rose .subscription-feature__icon {
            background: rgba(244, 63, 94, 0.14);
            color: #e11d48;
        }

        .subscription-feature--blue .subscription-feature__icon {
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
        }

        .subscription-feature__body strong {
            display: block;
            margin-bottom: 4px;
            color: #18203d;
            font-size: 15px;
            font-weight: 800;
        }

        .subscription-feature__body span {
            display: block;
            color: #667085;
            font-size: 13px;
            line-height: 1.45;
        }

        .subscription-lower {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 18px;
            margin-top: 18px;
        }

        .subscription-panel {
            padding: 20px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(86, 36, 208, 0.08);
            box-shadow: 0 18px 40px rgba(17, 24, 39, 0.05);
        }

        .subscription-panel h2 {
            margin: 0 0 14px;
            color: #18203d;
            font-size: 20px;
            line-height: 1.25;
            font-weight: 800;
        }

        .subscription-compare {
            width: 100%;
            border-collapse: collapse;
        }

        .subscription-compare th,
        .subscription-compare td {
            padding: 14px 10px;
            border-bottom: 1px solid rgba(86, 36, 208, 0.08);
            text-align: left;
            color: #334155;
            font-size: 14px;
        }

        .subscription-compare th {
            color: #18203d;
            font-size: 13px;
            font-weight: 800;
        }

        .subscription-compare td.is-check {
            color: #16a34a;
            font-size: 18px;
            text-align: center;
        }

        .subscription-faq {
            display: grid;
            gap: 10px;
        }

        .subscription-faq details {
            overflow: hidden;
            border-radius: 16px;
            border: 1px solid rgba(86, 36, 208, 0.08);
            background: #fff;
        }

        .subscription-faq summary {
            position: relative;
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 16px 18px;
            color: #18203d;
            font-size: 15px;
            font-weight: 700;
        }

        .subscription-faq summary::-webkit-details-marker {
            display: none;
        }

        .subscription-faq summary::after {
            content: "\f078";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            color: #667085;
            transition: transform 0.18s ease;
        }

        .subscription-faq details[open] summary::after {
            transform: rotate(180deg);
        }

        .subscription-faq p {
            margin: 0;
            padding: 0 18px 18px;
            color: #667085;
            font-size: 14px;
            line-height: 1.65;
        }

        .subscription-footer-cta {
            margin-top: 18px;
            padding: 22px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(91, 55, 255, 0.08), rgba(255, 255, 255, 0.98));
            border: 1px solid rgba(86, 36, 208, 0.1);
            box-shadow: 0 18px 38px rgba(17, 24, 39, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .subscription-footer-cta h2 {
            margin: 0 0 6px;
            color: #18203d;
            font-size: 22px;
            font-weight: 800;
        }

        .subscription-footer-cta p {
            margin: 0;
            color: #667085;
            font-size: 14px;
            line-height: 1.5;
        }

        .subscription-footer-cta .revision-btn {
            flex: 0 0 auto;
        }

        .subscription-plan .revision-btn--primary,
        .subscription-plan .revision-btn--ghost {
            background: #ffc52f;
            border-color: #1d1d1d;
            color: #181c2f;
            box-shadow: 4px 6px 0 rgba(29, 29, 29, 0.82);
        }

        .subscription-plan .revision-btn--primary:hover,
        .subscription-plan .revision-btn--ghost:hover {
            background: #ffca3f;
            border-color: #1d1d1d;
            color: #10131f;
            box-shadow: 6px 8px 0 rgba(29, 29, 29, 0.9);
        }

        .subscription-plan .revision-btn--primary:focus-visible,
        .subscription-plan .revision-btn--ghost:focus-visible {
            outline: 3px solid rgba(91, 55, 255, 0.18);
            outline-offset: 2px;
        }

        @media (max-width: 1399.98px) {
            .subscription-plan-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .subscription-features {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 1199.98px) {
            .subscription-layout {
                grid-template-columns: 1fr;
            }

            .subscription-sidebar {
                position: static;
                min-height: auto;
            }

            .subscription-topbar {
                grid-template-columns: 1fr;
            }

            .subscription-topbar__security {
                justify-self: start;
            }

            .subscription-top-actions {
                justify-self: start;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 767.98px) {
            .subscription-shell {
                padding-top: 10px;
            }

            .subscription-layout {
                padding-inline: 12px;
                gap: 16px;
            }

            .subscription-sidebar {
                padding: 14px 12px 12px;
                border-radius: 18px;
            }

            .subscription-topbar {
                padding: 16px 14px;
                border-radius: 18px;
            }

            .subscription-plan-grid,
            .subscription-features,
            .subscription-lower {
                grid-template-columns: 1fr;
            }

            .subscription-plan {
                padding: 22px 18px 18px;
            }

            .subscription-feature {
                border-right: 0;
                border-bottom: 1px solid rgba(86, 36, 208, 0.08);
                padding: 10px 0 14px;
            }

            .subscription-feature:last-child {
                border-bottom: 0;
            }

            .subscription-footer-cta {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endpush

@section('contents')
    <div class="subscription-shell">
        <div class="subscription-layout">
            <aside class="subscription-sidebar" aria-label="{{ __('Subscription navigation') }}">
                <a href="{{ route('home') }}" class="subscription-brand">
                    <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name }}">
                    <div>
                        <strong>RevisionHub</strong>
                        <span>{{ __('Kenya') }}</span>
                    </div>
                </a>

                <div class="subscription-sidebar__section">
                    <ul class="subscription-nav">
                        @foreach ($sidebarItems as $item)
                            <li>
                                @if (!empty($item['badge']))
                                    <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}">
                                        <i class="fas {{ $item['icon'] }}"></i>
                                        <span>{{ $item['label'] }}</span>
                                        <span class="subscription-nav__badge">{{ $item['badge'] }}</span>
                                    </a>
                                @else
                                    <a href="{{ $item['href'] }}" class="{{ $item['active'] ? 'is-active' : '' }}">
                                        <i class="fas {{ $item['icon'] }}"></i>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="subscription-sidebar__section">
                    <p class="subscription-sidebar__section-label">{{ __('Account') }}</p>
                    <ul class="subscription-nav">
                        <li>
                            <a href="{{ $settingsUrl }}">
                                <i class="fas fa-user-cog"></i>
                                <span>{{ __('Profile Settings') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ $settingsUrl }}">
                                <i class="fas fa-lock"></i>
                                <span>{{ __('Security') }}</span>
                            </a>
                        </li>
                        <li>
                            @auth('web')
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>{{ $logoutLabel }}</span>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>{{ __('Sign in') }}</span>
                                </a>
                            @endauth
                        </li>
                    </ul>
                </div>

                <div class="subscription-promo">
                    <div class="subscription-promo__icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h3>{{ __('Go Premium') }}</h3>
                    <p>{{ __('Get unlimited access to all study resources, notes, videos, quizzes and past papers.') }}</p>
                    <a href="{{ $dashboardUrl }}" class="revision-btn revision-btn--ghost">
                        {{ __('View Plans') }}
                    </a>
                </div>
            </aside>

            <main class="subscription-main">
                <div class="subscription-topbar">
                    <div class="subscription-topbar__copy">
                        <span class="subscription-topbar__eyebrow">{{ __('Membership plans') }}</span>
                        <h1>{{ __('Subscriptions') }}</h1>
                        <p>{{ __('Choose a plan and get unlimited access to all learning resources.') }}</p>
                    </div>

                    <div class="subscription-top-actions">
                        <span class="subscription-topbar__security">
                            <i class="fas fa-shield-alt"></i>
                            {{ __('Secure payments powered by M-Pesa, Visa & PayPal') }}
                        </span>
                        <div class="subscription-payment-icons" aria-label="{{ __('Payment methods') }}">
                            <span class="subscription-payment-icon subscription-payment-icon--mpesa" title="M-Pesa" aria-label="M-Pesa">
                                <img src="{{ asset('uploads/website-images/mpesa.webp') }}" alt="M-Pesa">
                                <span class="subscription-payment-icon__tag">
                                    <i class="fas fa-check"></i>
                                </span>
                            </span>
                            <span class="subscription-payment-icon subscription-payment-icon--visa" title="Visa" aria-label="Visa">
                                <i class="fab fa-cc-visa" aria-hidden="true"></i>
                            </span>
                            <span class="subscription-payment-icon subscription-payment-icon--paypal" title="PayPal" aria-label="PayPal">
                                <i class="fab fa-cc-paypal" aria-hidden="true"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="subscription-selection" aria-live="polite">
                    <div class="subscription-selection__copy">
                        <span class="subscription-selection__eyebrow">{{ __('Selected package') }}</span>
                        <strong data-selected-plan-name>{{ $selectedPlan['name'] }}</strong>
                        <span data-selected-plan-meta>{{ $selectedPlan['price'] }} &middot; {{ $selectedPlan['billing'] }}</span>
                    </div>
                    <span class="subscription-selection__hint">{{ __('Click any package to switch your selection.') }}</span>
                </div>

                <section class="subscription-plan-grid" aria-label="{{ __('Subscription plans') }}">
                    @foreach ($plans as $plan)
                        @php
                            $isSelected = $plan['id'] === $selectedPlan['id'];
                        @endphp
                        <article class="subscription-plan {{ $plan['featured'] ? 'is-featured' : '' }} {{ $isSelected ? 'is-selected' : '' }}" data-plan-card data-plan-id="{{ $plan['id'] }}" tabindex="0" role="button" aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                            <input type="radio" class="subscription-plan__radio" name="subscription_plan" id="subscription-plan-{{ $plan['id'] }}" value="{{ $plan['id'] }}" @checked($isSelected)>
                            <label class="subscription-plan__selector" for="subscription-plan-{{ $plan['id'] }}">
                                <span class="subscription-plan__selector-mark" aria-hidden="true"></span>
                                <span class="subscription-plan__selector-text">{{ $isSelected ? __('Selected') : __('Select package') }}</span>
                            </label>
                            @if ($plan['badge'])
                                <div class="subscription-plan__badge">{{ $plan['badge'] }}</div>
                            @endif
                            <h2 class="subscription-plan__title">{{ $plan['name'] }}</h2>
                            <div class="subscription-plan__price">
                                <span>{{ $plan['price'] }}</span>
                                <span class="subscription-plan__period">/ {{ $plan['period'] }}</span>
                            </div>
                            @if ($plan['save'])
                                <span class="subscription-plan__save">{{ $plan['save'] }}</span>
                            @endif
                            <div class="subscription-plan__billing">{{ $plan['billing'] }}</div>
                            <div class="subscription-plan__billing">
                                {{ __('Includes :credits bonus AI credits', ['credits' => number_format($plan['ai_bonus_credits'] ?? 0)]) }}
                            </div>

                            <ul class="subscription-plan__features">
                                @foreach ($planFeatures as $feature)
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ $checkoutUrl }}?subscription_plan={{ $plan['id'] }}" class="revision-btn subscription-plan__cta revision-btn--primary" data-plan-select data-checkout-url="{{ $checkoutUrl }}?subscription_plan={{ $plan['id'] }}">
                                <span>{{ __('Subscribe Now') }}</span>
                                <i class="fas {{ $isSelected ? 'fa-check' : 'fa-arrow-right' }}" aria-hidden="true"></i>
                            </a>
                            <div class="subscription-plan__footnote">
                                {{ $plan['price'] }} {{ __('billed every') }} {{ $plan['period'] }} • {{ __('Bonus: :credits AI credits', ['credits' => number_format($plan['ai_bonus_credits'] ?? 0)]) }}
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="subscription-features" aria-label="{{ __('Subscription benefits') }}">
                    @foreach ($featureCards as $feature)
                        <article class="subscription-feature subscription-feature--{{ $feature['tone'] }}">
                            <span class="subscription-feature__icon">
                                <i class="fas {{ $feature['icon'] }}"></i>
                            </span>
                            <div class="subscription-feature__body">
                                <strong>{{ $feature['title'] }}</strong>
                                <span>{{ $feature['text'] }}</span>
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="subscription-lower">
                    <div class="subscription-panel">
                        <h2>{{ __('Compare Plans') }}</h2>
                        <div class="table-responsive">
                            <table class="subscription-compare">
                                <thead>
                                    <tr>
                                        <th>{{ __('Features') }}</th>
                                        <th>{{ __('1 Month') }}</th>
                                        <th>{{ __('3 Months') }}</th>
                                        <th>{{ __('6 Months') }}</th>
                                        <th>{{ __('1 Year') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($compareRows as $row)
                                        <tr>
                                            <td>{{ $row }}</td>
                                            <td class="is-check"><i class="fas fa-check"></i></td>
                                            <td class="is-check"><i class="fas fa-check"></i></td>
                                            <td class="is-check"><i class="fas fa-check"></i></td>
                                            <td class="is-check"><i class="fas fa-check"></i></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="subscription-panel">
                        <h2>{{ __('Frequently Asked Questions') }}</h2>
                        <div class="subscription-faq">
                            @foreach ($faqs as $faq)
                                <details @if ($loop->first) open @endif>
                                    <summary>{{ $faq['question'] }}</summary>
                                    <p>{{ $faq['answer'] }}</p>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="subscription-footer-cta">
                    <div>
                        <h2>{{ __('Need help choosing the right plan?') }}</h2>
                        <p>{{ __('Reach out and we will help you pick the best subscription for your revision goals.') }}</p>
                    </div>
                    <a href="{{ route('contact.index') }}" class="revision-btn revision-btn--primary">{{ __('Contact Support') }}</a>
                </section>
            </main>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const cards = Array.from(document.querySelectorAll('[data-plan-card]'));
            const selectedPlanName = document.querySelector('[data-selected-plan-name]');
            const selectedPlanMeta = document.querySelector('[data-selected-plan-meta]');
            const selectionKey = 'revisionhub-selected-subscription-plan';
            const selectLabel = @json(__('Select package'));
            const selectedLabel = @json(__('Selected'));
            const subscribeNowLabel = @json(__('Subscribe Now'));

            if (!cards.length) {
                return;
            }

            const syncCard = (card) => {
                const radio = card.querySelector('.subscription-plan__radio');
                const labelText = card.querySelector('.subscription-plan__selector-text');
                const buttonText = card.querySelector('[data-plan-select] span');
                const buttonIcon = card.querySelector('[data-plan-select] i');
                const isSelected = radio.checked;

                card.classList.toggle('is-selected', isSelected);
                card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                if (labelText) {
                    labelText.textContent = isSelected ? selectedLabel : selectLabel;
                }
                if (buttonText) {
                    buttonText.textContent = subscribeNowLabel;
                }
                if (buttonIcon) {
                    buttonIcon.className = isSelected ? 'fas fa-check' : 'fas fa-arrow-right';
                }
            };

            const updateSelection = (card, persist = true) => {
                cards.forEach((item) => {
                    const radio = item.querySelector('.subscription-plan__radio');
                    radio.checked = item === card;
                    syncCard(item);
                });

                const name = card.querySelector('.subscription-plan__title')?.textContent?.trim();
                const price = card.querySelector('.subscription-plan__price > span')?.textContent?.trim();
                const billing = card.querySelector('.subscription-plan__billing')?.textContent?.trim();

                if (selectedPlanName && name) {
                    selectedPlanName.textContent = name;
                }

                if (selectedPlanMeta && price && billing) {
                    selectedPlanMeta.textContent = `${price} - ${billing}`;
                }

                if (persist) {
                    const planId = card.getAttribute('data-plan-id');
                    if (planId) {
                        const url = new URL(window.location.href);
                        url.searchParams.set('plan', planId);
                        window.history.replaceState({}, '', url);
                        try {
                            window.localStorage.setItem(selectionKey, planId);
                        } catch (error) {
                            // Ignore storage failures and keep the selection in-memory.
                        }
                    }
                }
            };

            cards.forEach((card) => {
                const radio = card.querySelector('.subscription-plan__radio');
                const selectButton = card.querySelector('[data-plan-select]');

                radio.addEventListener('change', () => updateSelection(card));

                selectButton.addEventListener('click', () => {
                    updateSelection(card);
                });

                card.addEventListener('click', (event) => {
                    if (event.target.closest('button, a, label, input')) {
                        return;
                    }

                    updateSelection(card);
                });

                card.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        updateSelection(card);
                    }
                });

                syncCard(card);
            });

            const urlPlan = new URL(window.location.href).searchParams.get('plan');
            const storedPlan = (() => {
                try {
                    return window.localStorage.getItem(selectionKey);
                } catch (error) {
                    return null;
                }
            })();
            const initialPlanId = urlPlan || storedPlan;
            const initialCard = initialPlanId
                ? cards.find((card) => card.getAttribute('data-plan-id') === initialPlanId)
                : cards.find((card) => card.classList.contains('is-selected')) || cards[0];

            if (initialCard) {
                updateSelection(initialCard, false);
            }
        })();
    </script>
@endpush
