@extends('frontend.layouts.master')

@section('meta_title', __('AI Credits Manager'))
@section('body_class', 'revision-ai-credits-page')

@php
    use Illuminate\Support\Facades\Route;

    $currentUser = $user ?? auth()->user();
    $currentUserName = $currentUser?->name ?: __('Student');
    $currentUserInitial = strtoupper(mb_substr($currentUserName, 0, 1));
    $currentUserRole = ucfirst((string) ($currentUser?->role ?: __('Student')));
    $creditsUrl = route('ai-chat.credits');
    $chatUrl = request()->routeIs('ai-chat') || request()->routeIs('student.ai-chat.*')
        ? route('ai-chat')
        : route('ai-chat');
    $dashboardUrl = route('student.dashboard');
    $libraryUrl = route('student.library');
    $enrolledUrl = route('student.enrolled-courses');
    $downloadsUrl = Route::has('student.downloads.index') ? route('student.downloads.index') : null;
    $settingsUrl = route('student.setting.index');
    $wishlistUrl = route('student.wishlist');
    $quizUrl = route('student.quiz-attempts');
    $ordersUrl = route('student.orders.index');
    $logoutFormId = 'ai-credits-logout-form';

    $sidebarMenu = [
        ['label' => __('Dashboard'), 'icon' => 'fa-gauge-high', 'url' => $dashboardUrl, 'active' => request()->routeIs('student.dashboard')],
        ['label' => __('My Library'), 'icon' => 'fa-book-open', 'url' => $libraryUrl, 'active' => request()->routeIs('student.library')],
        ['label' => __('My Enrollments'), 'icon' => 'fa-graduation-cap', 'url' => $enrolledUrl, 'active' => request()->routeIs('student.enrolled-courses')],
        ['label' => __('Downloads'), 'icon' => 'fa-download', 'url' => $downloadsUrl ?: 'javascript:;', 'active' => false],
        ['label' => __('Watch History'), 'icon' => 'fa-clock', 'url' => 'javascript:;', 'active' => false],
        ['label' => __('My Quizzes'), 'icon' => 'fa-circle-question', 'url' => $quizUrl, 'active' => request()->routeIs('student.quiz-attempts')],
        ['label' => __('Bookmarks'), 'icon' => 'fa-bookmark', 'url' => 'javascript:;', 'active' => false],
        ['label' => __('AI Study Assistant'), 'icon' => 'fa-robot', 'url' => $chatUrl, 'active' => request()->routeIs('ai-chat') || request()->routeIs('student.ai-chat.*')],
        ['label' => __('AI Credits'), 'icon' => 'fa-wallet', 'url' => $creditsUrl, 'active' => true, 'meta' => __('Manage & Recharge')],
        ['label' => __('Orders & Transactions'), 'icon' => 'fa-receipt', 'url' => $ordersUrl, 'active' => request()->routeIs('student.orders.index')],
        ['label' => __('Wishlist'), 'icon' => 'fa-heart', 'url' => $wishlistUrl, 'active' => request()->routeIs('student.wishlist')],
        ['label' => __('Settings'), 'icon' => 'fa-gear', 'url' => $settingsUrl, 'active' => request()->routeIs('student.setting.index')],
        ['label' => __('Help & Support'), 'icon' => 'fa-circle-question', 'url' => 'javascript:;', 'active' => false],
    ];
@endphp

@section('contents')
    <section class="ai-credits-page">
        <div class="ai-credits-page__shell">
            <div class="ai-credits-topbar">
                <div class="ai-credits-brand">
                    <a href="{{ route('home') }}" class="ai-credits-brand__link">
                        <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name }}">
                        <span>{{ __('REVISIONHUB') }} <strong>{{ __('KENYA') }}</strong></span>
                    </a>
                </div>

                <div class="ai-credits-search">
                    <i class="fas fa-search"></i>
                    <input type="search" value="" placeholder="{{ __('Search for anything...') }}" aria-label="{{ __('Search for anything') }}">
                    <button type="button" aria-label="{{ __('Search') }}">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <div class="ai-credits-topbar__actions">
                    <a href="#top-up-packages" class="ai-credits-toplink">{{ __('Buy Credits') }}</a>
                    <a href="{{ $chatUrl }}" class="ai-credits-pill ai-credits-pill--primary">
                        <i class="far fa-comment-dots"></i>
                        <span>{{ __('AI Chat') }}</span>
                    </a>
                    <a href="{{ route('cart') }}" class="ai-credits-pill ai-credits-pill--light">
                        <i class="fas fa-shopping-cart"></i>
                        <span>{{ Cart::content()->count() ?: 2 }}</span>
                    </a>
                    <span class="ai-credits-badge">
                        <i class="fas fa-circle-check"></i>
                        {{ __('Instructor') }}
                    </span>
                    <div class="ai-credits-profile">
                        <img src="{{ asset($currentUser?->image) }}" alt="{{ $currentUserName }}">
                        <span>{{ $currentUserName }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </div>

            <div class="ai-credits-layout">
                <aside class="ai-credits-sidebar">
                    <a href="{{ $settingsUrl }}" class="ai-credits-profile-card">
                        <div class="ai-credits-profile-card__avatar">
                            @if ($currentUser?->image)
                                <img src="{{ asset($currentUser->image) }}" alt="{{ $currentUserName }}">
                            @else
                                <span>{{ $currentUserInitial }}</span>
                            @endif
                        </div>
                        <div class="ai-credits-profile-card__body">
                            <strong>{{ $currentUserName }}</strong>
                            <span>{{ $currentUserRole }}</span>
                        </div>
                        <span class="ai-credits-profile-card__pill">Pro</span>
                    </a>

                    <nav class="ai-credits-menu">
                        @foreach ($sidebarMenu as $item)
                                <a href="{{ $item['url'] }}" class="ai-credits-menu__item {{ $item['active'] ? 'is-active' : '' }}">
                                    <span class="ai-credits-menu__icon"><i class="fas {{ $item['icon'] }}"></i></span>
                                    <span class="ai-credits-menu__text">
                                        <strong>{{ $item['label'] }}</strong>
                                    @if (!empty($item['meta']))
                                        <small>{{ $item['meta'] }}</small>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('{{ $logoutFormId }}').submit();"
                            class="ai-credits-menu__item">
                            <span class="ai-credits-menu__icon"><i class="fas fa-right-from-bracket"></i></span>
                            <span class="ai-credits-menu__text">
                                <strong>{{ __('Logout') }}</strong>
                            </span>
                        </a>
                    </nav>

                    <div class="ai-credits-upgrade">
                        <div class="ai-credits-upgrade__icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h6>{{ __('Unlock All Resources') }}</h6>
                        <p>{{ __('Buy AI credits on demand and recharge whenever you need more.') }}</p>
                        <button type="button" class="ai-credits-upgrade__button ai-credits-open-recharge" data-open-recharge-modal data-amount="100">
                            {{ __('Recharge Now') }}
                        </button>
                    </div>
                </aside>

                <main class="ai-credits-main">
                    <div class="ai-credits-breadcrumbs">
                        <a href="{{ $dashboardUrl }}">{{ __('Home') }}</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>{{ __('AI Credits') }}</span>
                    </div>

                    <div class="ai-credits-heading">
                        <div>
                            <h1>{{ __('AI Credits Manager') }}</h1>
                            <p>{{ __('Manage your AI credits, track usage and top up anytime.') }}</p>
                        </div>
                    </div>

                    <section class="ai-credits-panel ai-credits-panel--packages ai-credits-panel--featured" id="top-up-packages">
                        <div class="ai-credits-panel__head">
                            <div>
                                <h3>{{ __('Top Up Credits') }}</h3>
                                <span>{{ __('Choose a credit package that works for you.') }}</span>
                            </div>
                        </div>

                        <div class="ai-credits-packages">
                            @foreach ($topUpPackages as $package)
                                <article class="ai-credits-package ai-credits-package--{{ $package['tone'] }}">
                                    @if (!empty($package['badge']))
                                        <span class="ai-credits-package__badge">{{ $package['badge'] }}</span>
                                    @endif
                                    <div class="ai-credits-package__icon">
                                        <i class="fas {{ $package['icon'] }}"></i>
                                    </div>
                                    <strong>{{ $package['title'] }}</strong>
                                    <h4>{{ $package['credits'] }} <span>{{ __('Credits') }}</span></h4>
                                    <p>{{ $package['price'] }}</p>
                                    <small>{{ $package['description'] }}</small>
                                    <button type="button"
                                        class="ai-credits-package__button ai-credits-open-recharge"
                                        data-open-recharge-modal
                                        data-amount="{{ $package['amount'] }}"
                                        data-package="{{ $package['id'] }}">
                                        {{ __('Recharge') }}
                                    </button>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <div class="ai-credits-stats">
                        <article class="ai-credits-card ai-credits-card--balance">
                            <div class="ai-credits-card__header">
                                <div>
                                    <span>{{ __('Current Balance') }}</span>
                                    <strong>{{ number_format($currentBalance) }}</strong>
                                </div>
                                <div class="ai-credits-card__icon ai-credits-card__icon--violet">
                                    <i class="fas fa-wallet"></i>
                                </div>
                            </div>
                            <p class="ai-credits-card__meta">{{ __('Based on :value monthly allowance', ['value' => number_format($monthlyAllowance)]) }}</p>
                            <div class="ai-credits-card__actions">
                                <button type="button" class="ai-credits-primary-btn ai-credits-open-recharge" data-open-recharge-modal data-amount="100">{{ __('Recharge Credits') }}</button>
                                <a href="#usage-history" class="ai-credits-secondary-btn">{{ __('View Usage History') }}</a>
                            </div>
                        </article>

                        <article class="ai-credits-card ai-credits-card--usage">
                            <div class="ai-credits-card__header">
                                <div>
                                    <span>{{ __('Credits Usage') }}</span>
                                    <strong>{{ $usagePercent }}%</strong>
                                </div>
                                <div class="ai-credits-card__icon ai-credits-card__icon--green">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <p class="ai-credits-card__meta">{{ number_format($usageThisMonth) }} / {{ number_format($monthlyAllowance) }} credits used</p>
                            <div class="ai-credits-progress">
                                <span style="width: {{ $usagePercent }}%"></span>
                            </div>
                            <p class="ai-credits-card__note"><i class="far fa-calendar-check"></i>{{ __('Renews on') }} {{ now()->endOfMonth()->format('d F, Y') }}</p>
                        </article>

                        <article class="ai-credits-card ai-credits-card--month">
                            <div class="ai-credits-card__header">
                                <div>
                                    <span>{{ __('This Month Usage') }}</span>
                                    <strong>{{ number_format($usageThisMonth) }}</strong>
                                </div>
                                <div class="ai-credits-card__icon ai-credits-card__icon--blue">
                                    <i class="far fa-clock"></i>
                                </div>
                            </div>
                            <p class="ai-credits-card__meta">
                                {{ $monthChangePercent >= 0 ? __(':value% more than last month', ['value' => $monthChangePercent]) : __(':value% less than last month', ['value' => abs($monthChangePercent)]) }}
                            </p>
                        </article>

                        <article class="ai-credits-card ai-credits-card--saved">
                            <div class="ai-credits-card__header">
                                <div>
                                    <span>{{ __('Estimated Spend This Year') }}</span>
                                    <strong>KES {{ number_format($estimatedSpendThisYear, 2) }}</strong>
                                </div>
                                <div class="ai-credits-card__icon ai-credits-card__icon--orange">
                                    <i class="fas fa-sack-dollar"></i>
                                </div>
                            </div>
                            <p class="ai-credits-card__meta">{{ __('Based on actual logged AI requests this year') }}</p>
                        </article>
                    </div>

                    <div class="ai-credits-grid">
                        <section class="ai-credits-panel ai-credits-panel--breakdown">
                            <div class="ai-credits-panel__head">
                                <div>
                                    <h3>{{ __('Usage Breakdown') }}</h3>
                                    <span>{{ __('This Month') }}</span>
                                </div>
                            </div>
                            <div class="ai-credits-breakdown">
                                <div class="ai-credits-donut" style="--usage-percent: {{ $usagePercent }};">
                                    <div class="ai-credits-donut__inner">
                                        <strong>{{ number_format($usageTotal ?: 1910) }}</strong>
                                        <span>{{ __('Credits Used') }}</span>
                                    </div>
                                </div>
                                <div class="ai-credits-breakdown__list">
                                    @foreach ($usageBreakdown as $item)
                                        <div class="ai-credits-breakdown__item">
                                            <span class="ai-credits-breakdown__swatch" style="background: {{ ['#6b4efc', '#60a5fa', '#60d394', '#fbbf24', '#f472b6'][$loop->index % 5] }}"></span>
                                            <div class="ai-credits-breakdown__body">
                                                <strong>{{ $item['label'] }}</strong>
                                                <span>{{ number_format($item['credits']) }} credits</span>
                                            </div>
                                            <div class="ai-credits-breakdown__share">
                                                <strong>{{ $item['share'] ?? round(((int) $item['credits'] / max(1, (int) ($usageTotal ?: 1910))) * 100) }}%</strong>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <a href="#top-up-packages" class="ai-credits-panel__footer-link">{{ __('View Full Analytics') }}</a>
                        </section>

                        <section class="ai-credits-panel ai-credits-panel--activity" id="usage-history">
                            <div class="ai-credits-panel__head">
                                <div>
                                    <h3>{{ __('Recent Activity') }}</h3>
                                </div>
                                <a href="#" class="ai-credits-link">{{ __('View All') }}</a>
                            </div>

                            <div class="ai-credits-activity">
                                @foreach ($recentActivity as $activity)
                                    <article class="ai-credits-activity__item">
                                        <div class="ai-credits-activity__icon {{ $activity['tone'] }}">
                                            <i class="fas {{ $activity['icon'] }}"></i>
                                        </div>
                                        <div class="ai-credits-activity__body">
                                            <strong>{{ $activity['title'] }}</strong>
                                            <span>{{ $activity['message'] }}</span>
                                        </div>
                                        <div class="ai-credits-activity__meta">
                                            <strong>{{ $activity['credits'] }}</strong>
                                            <span>{{ $activity['time'] }}</span>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <p class="ai-credits-panel__footnote">{{ __('All activities are recorded in real-time') }}</p>
                        </section>

                    </div>

                    <div class="ai-credits-grid ai-credits-grid--bottom" id="top-up-packages">
                        <section class="ai-credits-panel ai-credits-panel--payments">
                            <div class="ai-credits-panel__head">
                                <div>
                                    <h3>{{ __('Payment Methods') }}</h3>
                                    <span>{{ __('Secure payments processed by our trusted partners.') }}</span>
                                </div>
                            </div>

                            <div class="ai-credits-payments">
                                @foreach ($paymentMethods as $method)
                                    <a href="javascript:;" class="ai-credits-payment">
                                        <span class="ai-credits-payment__icon"><i class="fas {{ $method['icon'] }}"></i></span>
                                        <span class="ai-credits-payment__body">
                                            <strong>{{ $method['name'] }}</strong>
                                            <small>{{ $method['description'] }}</small>
                                        </span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                @endforeach
                            </div>

                            <p class="ai-credits-payment__secure"><i class="fas fa-shield-heart"></i>{{ __('Your payments are 100% secure') }}</p>
                        </section>
                    </div>

                    <div class="ai-credits-banner">
                        <div>
                            <strong>{{ __('Need more credits?') }}</strong>
                            <p>{{ __('Pick a recharge amount, confirm checkout, and top up AI credits instantly.') }}</p>
                        </div>
                        <button type="button" class="ai-credits-banner__button ai-credits-open-recharge" data-open-recharge-modal data-amount="100">{{ __('Buy Credits') }} <i class="fas fa-coins"></i></button>
                    </div>
                </main>
            </div>
        </div>
        <div class="ai-recharge-modal" id="ai-recharge-modal" aria-hidden="true">
            <div class="ai-recharge-modal__backdrop" data-close-recharge-modal></div>
            <div class="ai-recharge-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ai-recharge-modal-title">
                <button type="button" class="ai-recharge-modal__close" aria-label="{{ __('Close') }}" data-close-recharge-modal>
                    <i class="fas fa-times"></i>
                </button>

                <div class="ai-recharge-modal__intro">
                    <span>{{ __('AI Credits Recharge') }}</span>
                    <h3 id="ai-recharge-modal-title">{{ __('Top up your AI wallet') }}</h3>
                    <p>{{ __('Choose a quick amount or enter a custom recharge. Credits are added instantly after checkout.') }}</p>
                </div>

                <div class="ai-recharge-modal__spotlight">
                    <span>{{ __('Payment amount') }}</span>
                    <strong id="ai-recharge-spotlight-amount">KES 100</strong>
                    <small id="ai-recharge-spotlight-credits">1,000 credits</small>
                </div>

                <form id="ai-recharge-form"
                    action="javascript:;"
                    class="ai-recharge-form"
                    data-place-order-base="{{ url('/place-order') }}"
                    data-credits-per-kes="{{ app(\App\Services\Ai\AiCreditPurchaseService::class)->creditsPerKes() }}"
                    data-min-amount="{{ app(\App\Services\Ai\AiCreditPurchaseService::class)->minimumAmount() }}"
                    data-default-method="{{ array_key_first($paymentGateways) ?: 'mpesa_stk_push' }}">
                    <input type="hidden" name="purchase_type" value="ai_credits">
                    <input type="hidden" name="ai_credit_amount" id="ai-recharge-amount-hidden" value="100">
                    <input type="hidden" name="payment_method" id="ai-recharge-payment-method" value="{{ array_key_first($paymentGateways) ?: 'mpesa_stk_push' }}">

                    <div class="ai-recharge-form__packages">
                        @foreach ($topUpPackages as $package)
                            <button type="button"
                                class="ai-recharge-form__package ai-credits-open-recharge"
                                data-open-recharge-modal
                                data-amount="{{ $package['amount'] }}"
                                data-credits="{{ $package['credits'] }}"
                                data-package="{{ $package['id'] }}">
                                <strong>KES {{ number_format($package['amount']) }}</strong>
                                <span>{{ number_format($package['credits']) }} {{ __('credits') }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="ai-recharge-form__custom">
                        <label for="ai-recharge-custom-amount">{{ __('Custom amount') }}</label>
                        <div class="ai-recharge-form__input">
                            <span>KES</span>
                            <input type="number" min="{{ app(\App\Services\Ai\AiCreditPurchaseService::class)->minimumAmount() }}" step="1" id="ai-recharge-custom-amount" placeholder="{{ __('Enter amount') }}">
                        </div>
                        <div class="ai-recharge-preview">
                            <div class="ai-recharge-preview__head">
                                <span>{{ __('Preview') }}</span>
                                <strong id="ai-recharge-preview-credits">1,000 credits</strong>
                            </div>
                            <div class="ai-recharge-preview__bar">
                                <span id="ai-recharge-preview-fill"></span>
                            </div>
                            <div class="ai-recharge-preview__meta">
                                <span id="ai-recharge-preview-amount">KES 100</span>
                                <small>{{ __('Same recharge rate as the quick-pick amounts above.') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="ai-recharge-form__gateway-section">
                        <div class="ai-recharge-form__gateway-section-head">
                            <strong>{{ __('Choose payment method') }}</strong>
                            <span>{{ __('Tap one and pay now') }}</span>
                        </div>

                        <div class="ai-recharge-form__payment-grid">
                            <div class="ai-recharge-form__gateways">
                                @foreach ($paymentGateways as $gatewayKey => $gatewayDetails)
                                    <button type="button"
                                        class="ai-recharge-form__gateway {{ $loop->first ? 'is-active' : '' }}"
                                        data-gateway="{{ $gatewayKey }}"
                                        data-name="{{ $gatewayDetails['name'] }}">
                                        <span class="ai-recharge-form__gateway-icon">
                                            <img src="{{ asset($gatewayDetails['logo']) }}" alt="{{ $gatewayDetails['name'] }}">
                                        </span>
                                        <span class="ai-recharge-form__gateway-body">
                                            <strong>{{ $gatewayDetails['name'] }}</strong>
                                            <small>{{ $gatewayKey === 'mpesa_stk_push' ? __('Instant mobile payment') : __('Secure online payment') }}</small>
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="ai-recharge-form__payment-extra" id="ai-recharge-mpesa-wrap">
                                <label for="ai-recharge-msisdn">{{ __('M-Pesa Phone Number') }}</label>
                                <div class="ai-recharge-form__input">
                                    <span><i class="fas fa-mobile-screen-button"></i></span>
                                    <input type="text" id="ai-recharge-msisdn" placeholder="07XXXXXXXX or 2547XXXXXXXX" value="{{ userAuth()->phone ?? '' }}">
                                </div>
                                <small>{{ __('Required for M-Pesa STK Push payments.') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="ai-recharge-form__actions">
                        <button type="button" class="ai-recharge-form__ghost" data-close-recharge-modal aria-label="{{ __('Cancel') }}">
                            <i class="fas fa-xmark" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="ai-recharge-form__submit" id="ai-recharge-pay-now">{{ __('Pay Now') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <form id="{{ $logoutFormId }}" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
@endsection

@push('styles')
    <style>
        body.revision-ai-credits-page header,
        body.revision-ai-credits-page footer {
            display: none !important;
        }

        body.revision-ai-credits-page .main-area {
            padding-top: 0;
            padding-bottom: 0;
        }

        .ai-credits-page {
            min-height: 100vh;
            padding: 18px 0 28px;
            background:
                radial-gradient(circle at top left, rgba(16, 185, 129, 0.12), transparent 28%),
                radial-gradient(circle at top right, rgba(99, 102, 241, 0.14), transparent 26%),
                linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
        }

        .ai-credits-page__shell {
            max-width: 1520px;
            margin: 0 auto;
            padding: 0 18px;
        }

        .ai-credits-topbar {
            display: grid;
            grid-template-columns: auto minmax(280px, 1fr) auto;
            align-items: center;
            gap: 18px;
            padding: 16px 18px;
            margin-bottom: 18px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.14);
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.05);
            backdrop-filter: blur(18px);
        }

        .ai-credits-brand__link,
        .ai-credits-toplink,
        .ai-credits-pill,
        .ai-credits-banner a,
        .ai-credits-banner__button,
        .ai-credits-panel__footer-link,
        .ai-credits-payment,
        .ai-credits-menu__item,
        .ai-credits-upgrade__button,
        .ai-credits-package a,
        .ai-credits-package__button,
        .ai-recharge-form__package,
        .ai-credits-link {
            text-decoration: none;
        }

        .ai-credits-brand__link {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #0f172a;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .ai-credits-brand__link img {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .ai-credits-brand__link span {
            display: grid;
            line-height: 0.95;
            font-size: 17px;
            color: #0f766e;
        }

        .ai-credits-brand__link strong {
            color: #0891b2;
        }

        .ai-credits-search {
            display: grid;
            grid-template-columns: 24px minmax(0, 1fr) 56px;
            align-items: center;
            gap: 8px;
            min-height: 54px;
            padding: 0 12px 0 16px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: #fff;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .ai-credits-search i {
            color: #64748b;
        }

        .ai-credits-search input {
            width: 100%;
            border: 0;
            outline: 0;
            font-size: 14px;
            background: transparent;
            color: #0f172a;
        }

        .ai-credits-search button {
            width: 100%;
            height: 42px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #5b35f5, #7c3aed);
            color: #fff;
        }

        .ai-credits-topbar__actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .ai-credits-toplink {
            color: #111827;
            font-weight: 600;
        }

        .ai-credits-pill {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0 18px;
            border-radius: 999px;
            font-weight: 700;
        }

        .ai-credits-pill--primary {
            background: linear-gradient(135deg, #5b35f5, #7c3aed);
            color: #fff;
            box-shadow: 0 12px 26px rgba(91, 53, 245, 0.24);
        }

        .ai-credits-pill--light {
            background: #fff;
            color: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .ai-credits-badge {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0 18px;
            border-radius: 999px;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.18);
            color: #157f3e;
            font-weight: 700;
        }

        .ai-credits-badge i {
            color: #16a34a;
        }

        .ai-credits-profile {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding-left: 4px;
            color: #0f172a;
            font-weight: 600;
        }

        .ai-credits-profile img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(148, 163, 184, 0.18);
        }

        .ai-credits-profile i {
            color: #64748b;
        }

        .ai-credits-layout {
            display: grid;
            grid-template-columns: 268px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .ai-credits-sidebar {
            display: grid;
            gap: 18px;
            position: sticky;
            top: 18px;
        }

        .ai-credits-profile-card,
        .ai-credits-menu__item,
        .ai-credits-upgrade,
        .ai-credits-panel,
        .ai-credits-card,
        .ai-credits-banner {
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.06);
            backdrop-filter: blur(16px);
        }

        .ai-credits-profile-card {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 16px;
            border-radius: 22px;
            color: inherit;
        }

        .ai-credits-profile-card__avatar {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 800;
        }

        .ai-credits-profile-card__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ai-credits-profile-card__body {
            min-width: 0;
        }

        .ai-credits-profile-card__body strong,
        .ai-credits-profile-card__body span {
            display: block;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .ai-credits-profile-card__body strong {
            font-size: 15px;
            color: #0f172a;
        }

        .ai-credits-profile-card__body span {
            color: #64748b;
            font-size: 13px;
        }

        .ai-credits-profile-card__pill {
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.12);
            color: #5b35f5;
            font-size: 12px;
            font-weight: 700;
        }

        .ai-credits-menu {
            display: grid;
            gap: 8px;
        }

        .ai-credits-menu__item {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            color: #334155;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .ai-credits-menu__item:hover {
            transform: translateY(-1px);
            border-color: rgba(91, 53, 245, 0.22);
            box-shadow: 0 16px 28px rgba(91, 53, 245, 0.08);
        }

        .ai-credits-menu__item.is-active {
            background: linear-gradient(135deg, #5b35f5, #7c3aed);
            color: #fff;
            box-shadow: 0 18px 32px rgba(91, 53, 245, 0.24);
        }

        .ai-credits-menu__icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: rgba(99, 102, 241, 0.08);
            color: #5b35f5;
        }

        .ai-credits-menu__item.is-active .ai-credits-menu__icon {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
        }

        .ai-credits-menu__text {
            min-width: 0;
            display: grid;
            gap: 2px;
        }

        .ai-credits-menu__text strong {
            font-size: 14px;
            font-weight: 700;
        }

        .ai-credits-menu__text small {
            color: inherit;
            opacity: 0.74;
            font-size: 12px;
        }

        .ai-credits-upgrade {
            border-radius: 22px;
            padding: 18px;
            background:
                radial-gradient(circle at top right, rgba(91, 53, 245, 0.12), transparent 48%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 250, 240, 0.96));
        }

        .ai-credits-upgrade__icon {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.16), rgba(251, 191, 36, 0.16));
            color: #d97706;
            margin-bottom: 12px;
        }

        .ai-credits-upgrade h6 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #0f172a;
            font-weight: 800;
        }

        .ai-credits-upgrade p {
            margin: 0 0 16px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .ai-credits-upgrade__button {
            width: 100%;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(135deg, #5b35f5, #7c3aed);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 16px 28px rgba(91, 53, 245, 0.22);
            border: 0;
            cursor: pointer;
        }

        .ai-credits-main {
            min-width: 0;
        }

        .ai-credits-breadcrumbs {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 4px 0 10px;
            color: #64748b;
            font-size: 14px;
        }

        .ai-credits-breadcrumbs a {
            color: inherit;
            text-decoration: none;
        }

        .ai-credits-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .ai-credits-heading h1 {
            margin: 0 0 6px;
            font-size: clamp(28px, 3vw, 38px);
            line-height: 1.08;
            font-weight: 800;
            color: #0f172a;
        }

        .ai-credits-heading p {
            margin: 0;
            color: #64748b;
            font-size: 15px;
        }

        .ai-credits-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .ai-credits-card {
            border-radius: 22px;
            padding: 18px 18px 16px;
            background: rgba(255, 255, 255, 0.9);
        }

        .ai-credits-card--balance {
            background: linear-gradient(180deg, rgba(243, 241, 255, 0.98), rgba(255, 255, 255, 0.96));
        }

        .ai-credits-card--usage {
            background: linear-gradient(180deg, rgba(241, 253, 245, 0.98), rgba(255, 255, 255, 0.96));
        }

        .ai-credits-card--month {
            background: linear-gradient(180deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.96));
        }

        .ai-credits-card--saved {
            background: linear-gradient(180deg, rgba(255, 248, 239, 0.98), rgba(255, 255, 255, 0.96));
        }

        .ai-credits-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .ai-credits-card__header span {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #5b35f5;
            font-weight: 700;
        }

        .ai-credits-card--usage .ai-credits-card__header span {
            color: #0f7a43;
        }

        .ai-credits-card--month .ai-credits-card__header span {
            color: #2563eb;
        }

        .ai-credits-card--saved .ai-credits-card__header span {
            color: #f97316;
        }

        .ai-credits-card__header strong {
            display: block;
            color: #0f172a;
            font-size: clamp(24px, 3vw, 34px);
            line-height: 1.05;
            font-weight: 800;
        }

        .ai-credits-card__meta,
        .ai-credits-card__note {
            margin: 10px 0 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }

        .ai-credits-card__note {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #475569;
        }

        .ai-credits-card__icon {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 18px;
        }

        .ai-credits-card__icon--violet { background: rgba(91, 53, 245, 0.12); color: #5b35f5; }
        .ai-credits-card__icon--green { background: rgba(34, 197, 94, 0.12); color: #16a34a; }
        .ai-credits-card__icon--blue { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
        .ai-credits-card__icon--orange { background: rgba(249, 115, 22, 0.12); color: #f97316; }

        .ai-credits-progress {
            margin-top: 14px;
            width: 100%;
            height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(148, 163, 184, 0.18);
        }

        .ai-credits-progress span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .ai-credits-card__actions {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .ai-credits-primary-btn,
        .ai-credits-secondary-btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-weight: 700;
        }

        .ai-credits-primary-btn {
            background: linear-gradient(135deg, #5b35f5, #7c3aed);
            color: #fff;
            box-shadow: 0 16px 28px rgba(91, 53, 245, 0.22);
            border: 0;
            cursor: pointer;
        }

        .ai-credits-secondary-btn {
            border: 1px solid rgba(148, 163, 184, 0.34);
            color: #0f172a;
            background: rgba(255, 255, 255, 0.8);
        }

        .ai-credits-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
            gap: 16px;
            margin-bottom: 18px;
        }

        .ai-credits-panel {
            border-radius: 22px;
            padding: 18px;
        }

        .ai-credits-panel__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .ai-credits-panel__head h3 {
            margin: 0 0 4px;
            font-size: 18px;
            color: #0f172a;
            font-weight: 800;
        }

        .ai-credits-panel__head span {
            color: #64748b;
            font-size: 13px;
        }

        .ai-credits-link {
            color: #5b35f5;
            font-weight: 700;
        }

        .ai-credits-breakdown {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 18px;
            align-items: center;
        }

        .ai-credits-donut {
            width: 240px;
            aspect-ratio: 1;
            border-radius: 50%;
            margin: 0 auto;
            padding: 18px;
            background:
                radial-gradient(circle at center, #fff 0 44%, transparent 45%),
                conic-gradient(from 180deg,
                    #6b4efc 0 calc(var(--usage-percent) * 1%),
                    rgba(148, 163, 184, 0.18) calc(var(--usage-percent) * 1%) 100%);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
        }

        .ai-credits-donut__inner {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            border-radius: 50%;
            text-align: center;
        }

        .ai-credits-donut__inner strong {
            display: block;
            color: #0f172a;
            font-size: 32px;
            font-weight: 800;
            line-height: 1;
        }

        .ai-credits-donut__inner span {
            color: #64748b;
            font-size: 13px;
            margin-top: 6px;
        }

        .ai-credits-breakdown__list {
            display: grid;
            gap: 12px;
        }

        .ai-credits-breakdown__item {
            display: grid;
            grid-template-columns: 12px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
        }

        .ai-credits-breakdown__swatch {
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }

        .ai-credits-breakdown__body {
            min-width: 0;
        }

        .ai-credits-breakdown__body strong,
        .ai-credits-breakdown__body span {
            display: block;
        }

        .ai-credits-breakdown__body strong {
            font-size: 14px;
            color: #0f172a;
        }

        .ai-credits-breakdown__body span {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .ai-credits-breakdown__share strong {
            color: #0f172a;
            font-size: 13px;
        }

        .ai-credits-panel__footer-link {
            margin-top: 18px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(91, 53, 245, 0.18);
            color: #5b35f5;
            font-weight: 700;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(245, 243, 255, 0.9));
        }

        .ai-credits-activity {
            display: grid;
            gap: 12px;
        }

        .ai-credits-activity__item {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 10px 12px;
            border-radius: 16px;
            background: rgba(248, 250, 252, 0.88);
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .ai-credits-activity__icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
        }

        .ai-credits-activity__icon.is-primary { background: rgba(91, 53, 245, 0.12); color: #5b35f5; }
        .ai-credits-activity__icon.is-success { background: rgba(34, 197, 94, 0.12); color: #16a34a; }
        .ai-credits-activity__icon.is-warning { background: rgba(249, 115, 22, 0.12); color: #f97316; }

        .ai-credits-activity__body {
            min-width: 0;
        }

        .ai-credits-activity__body strong,
        .ai-credits-activity__body span {
            display: block;
        }

        .ai-credits-activity__body strong {
            color: #0f172a;
            font-size: 14px;
        }

        .ai-credits-activity__body span {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }

        .ai-credits-activity__meta {
            text-align: right;
        }

        .ai-credits-activity__meta strong {
            display: block;
            color: #0f172a;
            font-size: 13px;
        }

        .ai-credits-activity__meta span {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
        }

        .ai-credits-panel__footnote {
            margin: 14px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .ai-credits-grid--bottom {
            grid-template-columns: minmax(0, 1fr);
        }

        .ai-credits-packages {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .ai-credits-package {
            position: relative;
            display: grid;
            gap: 10px;
            padding: 18px 16px 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .ai-credits-package__badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            padding: 4px 10px;
            border-radius: 999px;
            background: #16a34a;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .ai-credits-package__icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 18px;
        }

        .ai-credits-package strong {
            color: #0f172a;
            font-size: 14px;
        }

        .ai-credits-package h4 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            line-height: 1;
            font-weight: 800;
        }

        .ai-credits-package h4 span {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .ai-credits-package p {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
        }

        .ai-credits-package small {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            min-height: 34px;
        }

        .ai-credits-package a,
        .ai-credits-package__button {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid currentColor;
            font-weight: 700;
            cursor: pointer;
        }

        .ai-credits-package--is-violet .ai-credits-package__icon,
        .ai-credits-package--is-violet a,
        .ai-credits-package--is-violet .ai-credits-package__button { color: #5b35f5; border-color: rgba(91, 53, 245, 0.2); background: rgba(91, 53, 245, 0.08); }
        .ai-credits-package--is-green .ai-credits-package__icon,
        .ai-credits-package--is-green a,
        .ai-credits-package--is-green .ai-credits-package__button { color: #16a34a; border-color: rgba(34, 197, 94, 0.2); background: rgba(34, 197, 94, 0.08); }
        .ai-credits-package--is-orange .ai-credits-package__icon,
        .ai-credits-package--is-orange a,
        .ai-credits-package--is-orange .ai-credits-package__button { color: #f97316; border-color: rgba(249, 115, 22, 0.2); background: rgba(249, 115, 22, 0.08); }
        .ai-credits-package--is-blue .ai-credits-package__icon,
        .ai-credits-package--is-blue a,
        .ai-credits-package--is-blue .ai-credits-package__button { color: #2563eb; border-color: rgba(37, 99, 235, 0.2); background: rgba(37, 99, 235, 0.08); }
        .ai-credits-package--is-pink .ai-credits-package__icon,
        .ai-credits-package--is-pink a,
        .ai-credits-package--is-pink .ai-credits-package__button { color: #d946ef; border-color: rgba(217, 70, 239, 0.2); background: rgba(217, 70, 239, 0.08); }

        .ai-credits-payment__secure {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 14px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .ai-credits-payment__secure i {
            color: #16a34a;
        }

        .ai-credits-payments {
            display: grid;
            gap: 10px;
        }

        .ai-credits-payment {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px 14px;
            border-radius: 16px;
            color: #0f172a;
            background: rgba(248, 250, 252, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.12);
        }

        .ai-credits-payment__icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(91, 53, 245, 0.12);
            color: #5b35f5;
        }

        .ai-credits-payment__body strong,
        .ai-credits-payment__body small {
            display: block;
        }

        .ai-credits-payment__body strong {
            font-size: 14px;
        }

        .ai-credits-payment__body small {
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
        }

        .ai-credits-banner {
            border-radius: 22px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: linear-gradient(135deg, rgba(91, 53, 245, 0.06), rgba(255, 255, 255, 0.96));
        }

        .ai-credits-banner strong {
            display: block;
            color: #5b35f5;
            font-size: 18px;
            margin-bottom: 4px;
        }

        .ai-credits-banner p {
            margin: 0;
            color: #475569;
            font-size: 13px;
        }

        .ai-credits-banner a,
        .ai-credits-banner__button {
            min-height: 44px;
            padding: 0 18px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 12px;
            background: linear-gradient(135deg, #5b35f5, #7c3aed);
            color: #fff;
            font-weight: 700;
            white-space: nowrap;
            border: 0;
            cursor: pointer;
        }

        .ai-recharge-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 1040;
        }

        .ai-recharge-modal.is-open {
            display: flex;
        }

        .ai-recharge-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.58);
            backdrop-filter: blur(10px);
        }

        .ai-recharge-modal__dialog {
            position: relative;
            width: min(920px, 100%);
            max-height: min(90vh, 920px);
            overflow: auto;
            padding: 26px;
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(243, 247, 253, 0.98));
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
        }

        .ai-recharge-modal__close {
            position: absolute;
            top: 18px;
            right: 18px;
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.06);
            color: #0f172a;
            cursor: pointer;
        }

        .ai-recharge-modal__intro {
            margin-bottom: 18px;
            padding-right: 48px;
        }

        .ai-recharge-modal__intro span {
            display: inline-flex;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            color: #5b35f5;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .ai-recharge-modal__intro h3 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: clamp(24px, 3vw, 36px);
            line-height: 1.08;
        }

        .ai-recharge-modal__intro p {
            margin: 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.6;
        }

        .ai-recharge-modal__spotlight {
            display: grid;
            justify-items: center;
            gap: 4px;
            margin: 8px 0 16px;
            padding: 18px 20px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(91, 53, 245, 0.12), rgba(91, 53, 245, 0.04), rgba(255, 255, 255, 0.96));
            border: 1px solid rgba(91, 53, 245, 0.16);
            box-shadow: 0 18px 40px rgba(91, 53, 245, 0.10);
            text-align: center;
        }

        .ai-recharge-modal__spotlight span {
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .ai-recharge-modal__spotlight strong {
            color: #0f172a;
            font-size: clamp(28px, 4vw, 46px);
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .ai-recharge-modal__spotlight small {
            color: #5b35f5;
            font-size: 13px;
            font-weight: 800;
        }

        .ai-recharge-modal__wallet {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr) auto;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(91, 53, 245, 0.08), rgba(255, 255, 255, 0.96));
            border: 1px solid rgba(91, 53, 245, 0.12);
        }

        .ai-recharge-modal__wallet-icon {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #5b35f5, #7c3aed);
            color: #fff;
            box-shadow: 0 14px 28px rgba(91, 53, 245, 0.22);
        }

        .ai-recharge-modal__wallet-body {
            min-width: 0;
        }

        .ai-recharge-modal__wallet-body strong {
            display: block;
            color: #0f172a;
            font-size: 20px;
            font-weight: 900;
            line-height: 1;
        }

        .ai-recharge-modal__wallet-body span {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .ai-recharge-modal__wallet-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.1);
            color: #0f766e;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .ai-recharge-modal__wallet-pill span {
            line-height: 1.3;
            font-weight: 900;
        }

        .ai-recharge-form {
            display: grid;
            gap: 18px;
        }

        .ai-recharge-form__packages {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }

        .ai-recharge-form__package {
            display: grid;
            gap: 4px;
            align-items: center;
            justify-items: center;
            padding: 14px 12px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: #fff;
            text-align: center;
            cursor: pointer;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .ai-recharge-form__package.is-active {
            border-color: rgba(91, 53, 245, 0.35);
            box-shadow: 0 12px 30px rgba(91, 53, 245, 0.14);
            transform: translateY(-1px);
        }

        .ai-recharge-form__package strong {
            color: #0f172a;
            font-size: 14px;
        }

        .ai-recharge-form__package span {
            color: #5b35f5;
            font-size: 12px;
            font-weight: 700;
        }

        .ai-recharge-form__custom {
            display: grid;
            gap: 12px;
        }

        .ai-recharge-form__custom label {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
        }

        .ai-recharge-form__gateway-section {
            display: grid;
            gap: 10px;
        }

        .ai-recharge-form__payment-grid {
            display: grid;
            grid-template-columns: minmax(220px, 260px) minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .ai-recharge-form__gateway-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .ai-recharge-form__gateway-section-head strong {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
        }

        .ai-recharge-form__gateway-section-head span {
            color: #64748b;
            font-size: 12px;
        }

        .ai-recharge-form__gateways {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .ai-recharge-form__gateway {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: #fff;
            text-align: left;
            cursor: pointer;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .ai-recharge-form__gateway.is-active {
            border-color: rgba(91, 53, 245, 0.35);
            box-shadow: 0 12px 26px rgba(91, 53, 245, 0.12);
            transform: translateY(-1px);
        }

        .ai-recharge-form__gateway-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: rgba(91, 53, 245, 0.06);
        }

        .ai-recharge-form__gateway-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .ai-recharge-form__gateway-body {
            min-width: 0;
            display: grid;
            gap: 2px;
        }

        .ai-recharge-form__gateway-body strong {
            color: #0f172a;
            font-size: 14px;
        }

        .ai-recharge-form__gateway-body small {
            color: #64748b;
            font-size: 12px;
            line-height: 1.3;
        }

        .ai-recharge-form__input {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            min-height: 54px;
            padding: 0 16px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: #fff;
        }

        .ai-recharge-form__input span {
            color: #64748b;
            font-weight: 800;
        }

        .ai-recharge-form__input input {
            width: 100%;
            height: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: #0f172a;
            font-size: 15px;
        }

        .ai-recharge-preview {
            display: grid;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(91, 53, 245, 0.07), rgba(15, 23, 42, 0.02));
            border: 1px solid rgba(91, 53, 245, 0.12);
        }

        .ai-recharge-preview__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .ai-recharge-preview__head span {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .ai-recharge-preview__head strong {
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .ai-recharge-preview__bar {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(148, 163, 184, 0.16);
        }

        .ai-recharge-preview__bar span {
            display: block;
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #5b35f5, #7c3aed);
            transition: width .18s ease;
        }

        .ai-recharge-preview__meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #64748b;
            font-size: 12px;
        }

        .ai-recharge-preview__meta span {
            color: #0f172a;
            font-weight: 700;
        }

        .ai-recharge-preview__meta small {
            text-align: right;
            line-height: 1.4;
        }

        .ai-recharge-form__payment-extra {
            display: grid;
            gap: 8px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .ai-recharge-form__payment-extra label {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
        }

        .ai-recharge-form__payment-extra small {
            color: #64748b;
            font-size: 12px;
        }

        .ai-recharge-form__actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .ai-recharge-modal.is-compact .ai-recharge-form__packages,
        .ai-recharge-modal.is-compact .ai-recharge-form__custom,
        .ai-recharge-modal.is-compact .ai-recharge-preview {
            display: none;
        }

        .ai-recharge-form__ghost,
        .ai-recharge-form__submit {
            min-height: 46px;
            padding: 0 18px;
            border-radius: 14px;
            font-weight: 800;
            border: 0;
            cursor: pointer;
        }

        .ai-recharge-form__ghost {
            background: rgba(15, 23, 42, 0.06);
            color: #0f172a;
        }

        .ai-recharge-form__submit {
            background: linear-gradient(135deg, #5b35f5, #7c3aed);
            color: #fff;
            box-shadow: 0 14px 30px rgba(91, 53, 245, 0.24);
        }

        @media (max-width: 991.98px) {
            .ai-recharge-modal__dialog {
                width: min(760px, 100%);
                padding: 22px;
            }

            .ai-recharge-modal__wallet {
                grid-template-columns: 46px minmax(0, 1fr);
            }

            .ai-recharge-modal__wallet-pill {
                grid-column: 1 / -1;
                justify-self: start;
            }

            .ai-recharge-form__packages {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ai-recharge-form__payment-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .ai-recharge-modal {
                padding: 10px;
                align-items: flex-end;
            }

            .ai-recharge-modal__dialog {
                width: 100%;
                max-height: 92vh;
                padding: 18px;
                border-radius: 22px 22px 18px 18px;
            }

            .ai-recharge-modal__intro {
                padding-right: 42px;
            }

            .ai-recharge-modal__wallet {
                gap: 10px;
            }

            .ai-recharge-modal__wallet-pill {
                font-size: 11px;
            }

            .ai-recharge-form__packages {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ai-recharge-form__payment-grid {
                grid-template-columns: 1fr;
            }

            .ai-recharge-preview__meta {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 1480px) {
            .ai-credits-stats,
            .ai-credits-grid,
            .ai-credits-grid--bottom {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ai-credits-packages {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 1199.98px) {
            .ai-credits-topbar {
                grid-template-columns: 1fr;
            }

            .ai-credits-topbar__actions {
                justify-content: flex-start;
            }

            .ai-credits-layout {
                grid-template-columns: 1fr;
            }

            .ai-credits-sidebar {
                position: static;
            }
        }

        @media (max-width: 991.98px) {
            .ai-credits-stats,
            .ai-credits-grid,
            .ai-credits-grid--bottom,
            .ai-credits-packages {
                grid-template-columns: 1fr;
            }

            .ai-credits-breakdown {
                grid-template-columns: 1fr;
            }

            .ai-credits-donut {
                width: 220px;
            }
        }

        @media (max-width: 767.98px) {
            .ai-credits-page__shell {
                padding: 0 12px;
            }

            .ai-credits-topbar,
            .ai-credits-panel,
            .ai-credits-card,
            .ai-credits-banner,
            .ai-credits-upgrade,
            .ai-credits-profile-card {
                border-radius: 18px;
            }

            .ai-credits-topbar__actions {
                gap: 10px;
            }

            .ai-credits-topbar__actions > * {
                width: 100%;
                justify-content: center;
            }

            .ai-credits-profile {
                justify-content: center;
            }

            .ai-credits-heading {
                flex-direction: column;
            }

            .ai-credits-banner {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('ai-recharge-modal');
            const form = document.getElementById('ai-recharge-form');
            const hiddenAmount = document.getElementById('ai-recharge-amount-hidden');
            const paymentMethodHidden = document.getElementById('ai-recharge-payment-method');
            const customAmount = document.getElementById('ai-recharge-custom-amount');
            const payNowButton = document.getElementById('ai-recharge-pay-now');
            const previewAmount = document.getElementById('ai-recharge-preview-amount');
            const previewCredits = document.getElementById('ai-recharge-preview-credits');
            const previewFill = document.getElementById('ai-recharge-preview-fill');
            const spotlightAmount = document.getElementById('ai-recharge-spotlight-amount');
            const spotlightCredits = document.getElementById('ai-recharge-spotlight-credits');
            const gatewayButtons = Array.from(modal?.querySelectorAll('.ai-recharge-form__gateway') || []);
            const mpesaWrap = document.getElementById('ai-recharge-mpesa-wrap');
            const mpesaPhone = document.getElementById('ai-recharge-msisdn');

            if (!modal || !form || !hiddenAmount || !customAmount || !previewAmount || !previewCredits || !previewFill || !payNowButton || !spotlightAmount || !spotlightCredits) {
                return;
            }

            const rate = parseInt(form.dataset.creditsPerKes || '10', 10);
            const minimumAmount = parseInt(form.dataset.minAmount || '10', 10);
            const defaultMethod = form.dataset.defaultMethod || (gatewayButtons[0]?.dataset.gateway || 'mpesa_stk_push');
            const packageButtons = Array.from(modal.querySelectorAll('.ai-recharge-form__package'));
            const triggerButtons = Array.from(document.querySelectorAll('[data-open-recharge-modal]'));
            const closeButtons = Array.from(modal.querySelectorAll('[data-close-recharge-modal]'));
            const maxPackageAmount = Math.max(minimumAmount, ...packageButtons.map((button) => parseInt(button.dataset.amount || '0', 10)));

            const currencyFormat = new Intl.NumberFormat('en-KE');

            function creditsForAmount(amount) {
                return Math.max(0, amount * rate);
            }

            function setActiveAmount(amount, creditsOverride = null) {
                const safeAmount = Math.max(minimumAmount, parseInt(amount || minimumAmount, 10) || minimumAmount);
                const percent = Math.min(100, Math.max(6, (safeAmount / maxPackageAmount) * 100));
                const credits = Math.max(0, parseInt(creditsOverride ?? creditsForAmount(safeAmount), 10) || 0);

                hiddenAmount.value = safeAmount;
                customAmount.value = safeAmount;
                spotlightAmount.textContent = `KES ${currencyFormat.format(safeAmount)}`;
                spotlightCredits.textContent = `${currencyFormat.format(credits)} credits`;
                previewAmount.textContent = `KES ${currencyFormat.format(safeAmount)}`;
                previewCredits.textContent = `${currencyFormat.format(credits)} credits`;
                previewFill.style.width = `${percent}%`;

                payNowButton.innerHTML = `{{ __('Pay Now') }} - KES ${currencyFormat.format(safeAmount)}`;

                packageButtons.forEach((button) => {
                    const buttonAmount = parseInt(button.dataset.amount || '0', 10);
                    button.classList.toggle('is-active', buttonAmount === safeAmount);
                });
            }

            function setActiveGateway(method) {
                const selectedMethod = method || defaultMethod;

                gatewayButtons.forEach((button) => {
                    button.classList.toggle('is-active', button.dataset.gateway === selectedMethod);
                });

                if (paymentMethodHidden) {
                    paymentMethodHidden.value = selectedMethod;
                }

                if (mpesaWrap) {
                    mpesaWrap.style.display = selectedMethod === 'mpesa_stk_push' ? '' : 'none';
                }

                return selectedMethod;
            }

            function openModal(amount, credits = null) {
                setActiveAmount(amount, credits);
                setActiveGateway(defaultMethod);
                modal.classList.add('is-compact');
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.classList.remove('is-open');
                modal.classList.remove('is-compact');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            triggerButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    openModal(button.dataset.amount || hiddenAmount.value || minimumAmount, button.dataset.credits || null);
                });
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            packageButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    openModal(button.dataset.amount || minimumAmount, button.dataset.credits || null);
                });
            });

            gatewayButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    setActiveGateway(button.dataset.gateway || defaultMethod);
                });
            });

            customAmount.addEventListener('input', function () {
                const amount = Math.max(minimumAmount, parseInt(customAmount.value || '0', 10) || minimumAmount);
                setActiveAmount(amount);
            });

            payNowButton.addEventListener('click', function () {
                const amount = Math.max(minimumAmount, parseInt(hiddenAmount.value || customAmount.value || minimumAmount, 10) || minimumAmount);
                const selectedMethod = setActiveGateway(paymentMethodHidden?.value || defaultMethod);
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const msisdn = (mpesaPhone?.value || '').trim();

                if (!selectedMethod) {
                    toastr.warning('{{ __('Please select a payment method.') }}');
                    return;
                }

                if (selectedMethod === 'mpesa_stk_push' && !msisdn) {
                    toastr.warning('{{ __('Please enter your M-Pesa phone number.') }}');
                    mpesaPhone?.focus();
                    return;
                }

                $.ajax({
                    url: `${base_url}/place-order/${selectedMethod}`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrf,
                        purchase_type: 'ai_credits',
                        ai_credit_amount: amount,
                    },
                    beforeSend: function () {
                        payNowButton.disabled = true;
                        payNowButton.innerHTML = '{{ __('Processing...') }}';
                    },
                    success: function (response) {
                        if (response.success && response.invoice_id) {
                            const query = selectedMethod === 'mpesa_stk_push' && msisdn
                                ? `&autostk=1&msisdn=${encodeURIComponent(msisdn)}`
                                : '';
                            window.location.href = `${base_url}/payment?invoice_id=${response.invoice_id}${query}`;
                            return;
                        }

                        toastr.warning(response.messege || '{{ __('Payment failed, please try again') }}');
                        payNowButton.disabled = false;
                        payNowButton.innerHTML = `{{ __('Pay Now') }} - KES ${currencyFormat.format(amount)}`;
                    },
                    error: function (error) {
                        const errorMessage = error.responseJSON?.message || '{{ __('Payment failed, please try again') }}';
                        toastr.error(errorMessage);
                        payNowButton.disabled = false;
                        payNowButton.innerHTML = `{{ __('Pay Now') }} - KES ${currencyFormat.format(amount)}`;
                    },
                    complete: function () {
                        payNowButton.disabled = false;
                    }
                });
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });

            setActiveAmount(parseInt(hiddenAmount.value || minimumAmount, 10));
            setActiveGateway(defaultMethod);
        });
    </script>
@endpush



