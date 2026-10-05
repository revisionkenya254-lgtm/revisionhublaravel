<!-- Mobile Menu  -->
<div class="tgmobile__menu revision-mobile-drawer" id="revision-mobile-drawer">
    <nav class="tgmobile__menu-box revision-mobile-drawer__box">
        <div class="revision-mobile-drawer__shell">
            @php
                $catalogLinks = [
                    [
                        'label' => __('Past Papers'),
                        'href' => route('catalog', ['type' => 'past_paper']),
                        'icon' => 'fa-file-alt',
                        'hint' => __('Exams and mark schemes'),
                    ],
                    [
                        'label' => __('Notes'),
                        'href' => route('catalog', ['type' => 'note']),
                        'icon' => 'fa-book-open',
                        'hint' => __('Study notes and summaries'),
                    ],
                    [
                        'label' => __('Videos'),
                        'href' => route('catalog', ['type' => 'course']),
                        'icon' => 'fa-play-circle',
                        'hint' => __('Video lessons and walkthroughs'),
                    ],
                    [
                        'label' => __('Predictions'),
                        'href' => route('catalog', ['type' => 'prediction']),
                        'icon' => 'fa-bullseye',
                        'hint' => __('Targeted revision picks'),
                    ],
                    [
                        'label' => __('Quizzes'),
                        'href' => route('catalog', ['type' => 'quiz']),
                        'icon' => 'fa-circle-question',
                        'hint' => __('Quick practice checks'),
                    ],
                ];
            @endphp

            <div class="revision-mobile-drawer__top">
                <div class="nav-logo revision-mobile-drawer__brand">
                    <a href="{{ route('home') }}">
                        <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name ?? config('app.name') }} logo">
                    </a>
                </div>
                <div class="close-btn revision-mobile-drawer__close" aria-label="{{ __('Close menu') }}">
                    <i class="tg-flaticon-close-1"></i>
                </div>
            </div>

            <div class="revision-mobile-drawer__intro">
                <strong>{{ $setting?->app_name ?? config('app.name') }}</strong>
                <span>{{ __('Learn. Revise. Excel.') }}</span>
            </div>

            <div class="revision-mobile-drawer__actions">
                @guest
                    <a href="{{ route('login') }}" class="revision-mobile-drawer__action revision-mobile-drawer__action--ghost">
                        {{ __('Login') }}
                    </a>
                    <a href="{{ route('register') }}" class="revision-mobile-drawer__action revision-mobile-drawer__action--primary">
                        {{ __('Sign Up') }}
                    </a>
                @else
                    <a href="{{ route('student.dashboard') }}" class="revision-mobile-drawer__action revision-mobile-drawer__action--primary">
                        {{ __('Dashboard') }}
                    </a>
                    <a href="{{ route('cart') }}" class="revision-mobile-drawer__action revision-mobile-drawer__action--ghost">
                        {{ __('Cart') }}
                    </a>
                @endguest
            </div>

            <div class="revision-mobile-drawer__search">
                @include('frontend.layouts.partials.header-mobile-search')
            </div>

            <section class="revision-mobile-drawer__browse" aria-label="{{ __('Browse catalog') }}">
                <div class="revision-mobile-drawer__section-head">
                    <div>
                        <span class="revision-mobile-drawer__eyebrow">{{ __('Browse') }}</span>
                        <h2>{{ __('Study materials') }}</h2>
                    </div>
                    <span class="revision-mobile-drawer__section-badge">{{ __('Quick access') }}</span>
                </div>

                <div class="revision-mobile-drawer__catalog-list">
                    @foreach ($catalogLinks as $catalogLink)
                        <a href="{{ $catalogLink['href'] }}" class="revision-mobile-drawer__catalog-item">
                            <span class="revision-mobile-drawer__catalog-icon">
                                <i class="fas {{ $catalogLink['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <span class="revision-mobile-drawer__catalog-copy">
                                <strong>{{ $catalogLink['label'] }}</strong>
                                <span>{{ $catalogLink['hint'] }}</span>
                            </span>
                            <i class="fas fa-chevron-right revision-mobile-drawer__catalog-chevron" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </section>

            <div class="revision-mobile-drawer__body">
                @include('frontend.layouts.partials.header-mobile-language-currency')
                @include('frontend.layouts.partials.header-mobile-auth-links')
                @include('frontend.layouts.partials.header-mobile-menu-placeholder')
            </div>

            <div class="revision-mobile-drawer__socials">
                @include('frontend.layouts.partials.header-mobile-social-links')
            </div>
        </div>
    </nav>

    <div class="revision-mobile-drawer__bottom">
        @guest
            <a href="{{ route('login') }}" class="revision-mobile-drawer__bottom-link revision-mobile-drawer__bottom-link--ghost">
                {{ __('Login') }}
            </a>
            <a href="{{ route('register') }}" class="revision-mobile-drawer__bottom-link revision-mobile-drawer__bottom-link--primary">
                {{ __('Sign Up') }}
            </a>
        @else
            @php
                $mobileDashboardRoute = isInstructorAccount() ? 'instructor.dashboard' : 'student.dashboard';
            @endphp
            <a href="{{ route($mobileDashboardRoute) }}" class="revision-mobile-drawer__bottom-link revision-mobile-drawer__bottom-link--ghost">
                {{ __('Dashboard') }}
            </a>
            <a href="{{ route('cart') }}" class="revision-mobile-drawer__bottom-link revision-mobile-drawer__bottom-link--primary">
                {{ __('Cart') }}
            </a>
        @endguest
    </div>
</div>
<div class="tgmobile__menu-backdrop"></div>
<!-- End Mobile Menu -->

{{-- start admin logout form --}}
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
{{-- end admin logout form --}}

@push('styles')
    <style>
        .revision-mobile-drawer .tgmobile__menu-box {
            position: relative;
            width: min(94vw, 520px);
            background:
                radial-gradient(circle at top right, rgba(93, 63, 255, 0.14), transparent 24%),
                linear-gradient(180deg, #ffffff 0%, #f7f6ff 100%);
            box-shadow: -28px 0 72px rgba(2, 6, 23, 0.28);
        }

        .revision-mobile-drawer .tgmobile__menu-box::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 8px;
            background: linear-gradient(180deg, #ff8a00 0%, #ff6a00 100%);
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.24), 10px 0 24px rgba(255, 106, 0, 0.18);
            pointer-events: none;
            z-index: 2;
        }

        .revision-mobile-drawer .tgmobile__menu-backdrop {
            background: rgba(2, 6, 23, 0.72);
            backdrop-filter: blur(5px);
        }

        .revision-mobile-drawer__shell {
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-height: 100%;
            padding: 0 16px 20px 24px;
        }

        .revision-mobile-drawer__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 0 -16px;
            padding: 14px 16px 14px 24px;
            background: linear-gradient(135deg, #14192d 0%, #1e2644 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.12);
        }

        .revision-mobile-drawer__brand img {
            max-height: 40px;
            width: auto;
            object-fit: contain;
        }

        .revision-mobile-drawer__close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
        }

        .revision-mobile-drawer__intro {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 16px;
            border-radius: 22px;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.9), transparent 55%),
                linear-gradient(135deg, rgba(93, 63, 255, 0.16), rgba(111, 57, 255, 0.06));
            box-shadow: inset 0 0 0 1px rgba(93, 63, 255, 0.08);
        }

        .revision-mobile-drawer__intro strong {
            color: #0f172a;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.03em;
            text-transform: uppercase;
        }

        .revision-mobile-drawer__intro span {
            color: #64748b;
            font-size: 13px;
            line-height: 1.4;
        }

        .revision-mobile-drawer__actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .revision-mobile-drawer__action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 16px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
        }

        .revision-mobile-drawer__action--ghost {
            border: 1px solid rgba(93, 63, 255, 0.18);
            color: #0f172a;
            background: #fff;
        }

        .revision-mobile-drawer__action--primary {
            color: #fff;
            background: linear-gradient(135deg, #5d3fff, #6f39ff);
            box-shadow: 0 12px 26px rgba(93, 63, 255, 0.22);
        }

        .revision-mobile-drawer__search {
            padding: 4px 0 0;
        }

        .revision-mobile-drawer__search .tgmobile__search {
            display: block;
        }

        .revision-mobile-drawer__search .tgmobile__search form {
            display: flex;
            align-items: stretch;
            gap: 0;
            width: 100%;
            height: 46px;
            border: 1px solid rgba(93, 63, 255, 0.14);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .revision-mobile-drawer__search .tgmobile__search-category {
            position: relative;
            flex: 0 0 106px;
            min-width: 0;
            height: 100%;
            border-right: 1px solid rgba(93, 63, 255, 0.1);
        }

        .revision-mobile-drawer__search .tgmobile__search-category > i.fa-th-large {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #5d3fff;
            font-size: 12px;
            pointer-events: none;
        }

        .revision-mobile-drawer__search .tgmobile__search select {
            width: 100%;
            height: 100%;
            padding: 0 24px 0 30px;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
            outline: none;
            appearance: none;
            transition: background-color 0.2s ease;
        }

        .revision-mobile-drawer__search .tgmobile__search-caret {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #5d3fff;
            font-size: 11px;
            pointer-events: none;
        }

        .revision-mobile-drawer__search .tgmobile__search-field {
            position: relative;
            flex: 1 1 auto;
            width: 100%;
            height: 100%;
        }

        .revision-mobile-drawer__search .tgmobile__search input {
            width: 100%;
            height: 100%;
            padding: 0 16px 0 38px;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #0f172a;
            font-size: 14px;
            outline: none;
        }

        .revision-mobile-drawer__search .tgmobile__search input::placeholder {
            color: #94a3b8;
        }

        .revision-mobile-drawer__search .tgmobile__search input:focus,
        .revision-mobile-drawer__search .tgmobile__search select:focus {
            background: rgba(255, 255, 255, 0.72);
        }

        .revision-mobile-drawer__search .tgmobile__search-field i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 13px;
            pointer-events: none;
        }

        .revision-mobile-drawer__search .tgmobile__search button {
            flex: 0 0 40px;
            width: 40px;
            height: 100%;
            border: 0;
            border-radius: 50%;
            margin: 0 4px;
            background: linear-gradient(135deg, #5d3fff, #6f39ff);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 10px 22px rgba(93, 63, 255, 0.24);
        }

        .revision-mobile-drawer__body {
            display: grid;
            gap: 12px;
            padding-top: 4px;
        }

        .revision-mobile-drawer__browse {
            display: grid;
            gap: 12px;
            padding: 16px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
        }

        .revision-mobile-drawer__section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .revision-mobile-drawer__eyebrow {
            display: inline-flex;
            margin-bottom: 4px;
            color: #5d3fff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .revision-mobile-drawer__section-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .revision-mobile-drawer__section-badge {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(93, 63, 255, 0.08);
            color: #5d3fff;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .revision-mobile-drawer__catalog-list {
            display: grid;
            gap: 10px;
        }

        .revision-mobile-drawer__catalog-item {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            min-height: 68px;
            padding: 10px 12px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .revision-mobile-drawer__catalog-item:hover {
            transform: translateY(-1px);
            border-color: rgba(93, 63, 255, 0.18);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
        }

        .revision-mobile-drawer__catalog-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(93, 63, 255, 0.14), rgba(111, 57, 255, 0.08));
            color: #5d3fff;
        }

        .revision-mobile-drawer__catalog-icon i {
            font-size: 17px;
        }

        .revision-mobile-drawer__catalog-copy {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .revision-mobile-drawer__catalog-copy strong {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .revision-mobile-drawer__catalog-copy span {
            color: #64748b;
            font-size: 12px;
            line-height: 1.35;
        }

        .revision-mobile-drawer__catalog-chevron {
            color: #cbd5e1;
            font-size: 12px;
        }

        .revision-mobile-drawer__section-title {
            margin-bottom: 10px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: -0.01em;
            text-transform: uppercase;
        }

        .revision-mobile-drawer .mobile_menu_login {
            display: grid !important;
            gap: 8px;
            padding: 0;
            margin: 0;
        }

        .revision-mobile-drawer .mobile_menu_login > li > a {
            display: flex;
            align-items: center;
            min-height: 46px;
            padding: 0 14px;
            border-radius: 12px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 14px;
            font-weight: 600;
        }

        .revision-mobile-drawer .mobile_menu_login > li > a:hover {
            background: rgba(93, 63, 255, 0.08);
            color: #5d3fff;
        }

        .revision-mobile-drawer .mobile-role-badge {
            width: 100%;
            justify-content: center;
        }

        .revision-mobile-drawer .tgmobile__menu-outer {
            margin-top: 6px;
        }

        .revision-mobile-drawer .tgmobile__menu-outer ul {
            display: grid;
            gap: 8px;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .revision-mobile-drawer .tgmobile__menu-outer ul li a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 48px;
            padding: 0 14px;
            border-radius: 14px;
            color: #0f172a;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            font-size: 14px;
            font-weight: 700;
        }

        .revision-mobile-drawer .tgmobile__menu-outer ul li a:hover {
            background: rgba(93, 63, 255, 0.08);
            color: #5d3fff;
        }

        .revision-mobile-drawer__socials {
            margin-top: auto;
            padding-top: 4px;
        }

        .revision-mobile-drawer__bottom {
            position: fixed;
            right: 0;
            bottom: 0;
            width: min(94vw, 520px);
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            padding: 14px 16px 16px 24px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(18px);
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 -12px 30px rgba(15, 23, 42, 0.08);
            z-index: 1400;
        }

        .revision-mobile-drawer__bottom-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
        }

        .revision-mobile-drawer__bottom-link--ghost {
            border: 1px solid rgba(93, 63, 255, 0.18);
            background: #fff;
            color: #0f172a;
        }

        .revision-mobile-drawer__bottom-link--primary {
            color: #fff;
            background: linear-gradient(135deg, #5d3fff, #6f39ff);
            box-shadow: 0 12px 26px rgba(93, 63, 255, 0.22);
        }

        @media (max-width: 575.98px) {
            .revision-mobile-drawer .tgmobile__menu-box {
                width: min(100vw, 420px);
            }

            .revision-mobile-drawer .tgmobile__menu-box::before {
                width: 6px;
            }

            .revision-mobile-drawer__shell {
                padding: 0 14px 84px 20px;
            }

            .revision-mobile-drawer__bottom {
                width: 100vw;
                padding: 12px 14px 14px 20px;
            }

            .revision-mobile-drawer__catalog-item {
                grid-template-columns: 42px minmax(0, 1fr) auto;
                gap: 10px;
                min-height: 64px;
            }

            .revision-mobile-drawer__catalog-copy span {
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        }
    </style>
@endpush
