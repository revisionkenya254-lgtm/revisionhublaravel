@php
    $isAdminLoggedIn = auth('admin')->check();
    $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
    $headerUnreadNotifications = collect();
    $headerUnreadNotificationCount = 0;
    $headerSearchTypes = [
        '' => __('Categories'),
        \App\Models\Product::TYPE_COURSE => __('Videos'),
        \App\Models\Product::TYPE_NOTE => __('Notes'),
        \App\Models\Product::TYPE_PAST_PAPER => __('Papers'),
        \App\Models\Product::TYPE_PREDICTION => __('Predictions'),
        \App\Models\Product::TYPE_QUIZ => __('Quizzes'),
    ];

    if (auth('web')->check() && $hasNotificationsTable) {
        $headerUnreadNotifications = auth('web')->user()
            ->unreadNotifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => data_get($notification->data, 'title', __('Notification')),
                    'message' => data_get($notification->data, 'message', __('You have a new notification.')),
                    'url' => data_get($notification->data, 'url'),
                    'created_at' => optional($notification->created_at)->diffForHumans(),
                ];
            });

        $headerUnreadNotificationCount = auth('web')->user()->unreadNotifications()->count();
    }

    $navItems = [
        [
            'label' => __('Past Papers'),
            'href' => route('catalog', ['type' => 'past_paper']),
            'active' => request()->routeIs('catalog') && request('type') === 'past_paper',
        ],
        [
            'label' => __('Notes'),
            'href' => route('catalog', ['type' => 'note']),
            'active' => request()->routeIs('catalog') && request('type') === 'note',
        ],
        [
            'label' => __('Videos'),
            'href' => route('catalog', ['type' => 'course']),
            'active' => request()->routeIs('catalog') && request('type') === 'course',
        ],
        [
            'label' => __('Predictions'),
            'href' => route('catalog', ['type' => 'prediction']),
            'active' => request()->routeIs('catalog') && request('type') === 'prediction',
        ],
        [
            'label' => __('Quizzes'),
            'href' => route('catalog', ['type' => 'quiz']),
            'active' => request()->routeIs('catalog') && request('type') === 'quiz',
        ],
        [
            'label' => __('Pricing'),
            'href' => route('subscriptions'),
            'active' => request()->routeIs('subscriptions'),
            'class' => 'site-header__nav-item--pricing',
        ],
    ];

    $hamburgerLinks = array_slice($navItems, 0, 5);
@endphp

<div id="sticky-header" class="tg-header__area revision-header__bar">
    <div class="container-fluid revision-header__frame">
        <div class="revision-header__inner">
            <div class="revision-header__menu">
                <button type="button" class="revision-header__menu-toggle" aria-label="{{ __('Open menu') }}" aria-controls="revision-header-menu-dropdown" aria-expanded="false">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>

                <div class="revision-header__menu-dropdown" id="revision-header-menu-dropdown" hidden>
                    <div class="revision-header__menu-dropdown-head">
                        <span>{{ __('Browse') }}</span>
                        <strong>{{ __('Study materials') }}</strong>
                    </div>
                    <div class="revision-header__menu-dropdown-list">
                        @foreach ($hamburgerLinks as $hamburgerLink)
                            <a
                                href="{{ $hamburgerLink['href'] }}"
                                class="revision-header__menu-dropdown-link {{ $hamburgerLink['active'] ? 'is-active' : '' }}"
                            >
                                <span>{{ $hamburgerLink['label'] }}</span>
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <a class="revision-header__brand" href="{{ route('home') }}" aria-label="{{ $setting?->app_name ?? config('app.name') }}">
                <span class="revision-header__brand-mark">
                    <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name ?? config('app.name') }} logo">
                </span>
                <span class="revision-header__brand-copy">
                    <span class="revision-header__brand-name">{{ $setting?->app_name ?? config('app.name') }}</span>
                    <span class="revision-header__brand-tagline">{{ __('Learn. Revise. Excel.') }}</span>
                </span>
            </a>

            <div class="revision-header__center">
                <form action="{{ route('catalog') }}" method="GET" class="revision-header__search d-none d-xl-flex" role="search">
                    <div class="revision-header__search-category">
                        <i class="fas fa-th-large" aria-hidden="true"></i>
                        <select name="type" class="revision-header__search-select" aria-label="{{ __('Search category') }}">
                            @foreach ($headerSearchTypes as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" @selected((string) request('type') === (string) $typeValue)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down revision-header__search-caret" aria-hidden="true"></i>
                    </div>
                    <div class="revision-header__search-field">
                        <i class="fas fa-search revision-header__search-icon" aria-hidden="true"></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="{{ __('Search For Course . . .') }}"
                            aria-label="{{ __('Search papers, notes, quizzes') }}"
                        >
                    </div>
                    <button type="submit" class="revision-header__search-button">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </button>
                </form>
            </div>

            <div class="d-none">
                <nav class="site-header__desktop-menu tgmenu__wrap" aria-label="{{ __('Primary navigation') }}">
                    <ul class="tgmenu__main-menu navigation list-wrap">
                        @foreach ($navItems as $navItem)
                            <li class="site-header__nav-item {{ $navItem['class'] ?? '' }}">
                                <a
                                    class="site-header__nav-link {{ $navItem['active'] ? 'is-active' : '' }}"
                                    href="{{ $navItem['href'] }}"
                                    @if (($navItem['class'] ?? '') === 'site-header__nav-item--pricing') aria-label="{{ $navItem['label'] }}" title="{{ $navItem['label'] }}" @endif
                                >
                                    @if (($navItem['class'] ?? '') === 'site-header__nav-item--pricing')
                                        <i class="fas fa-tag site-header__nav-icon" aria-hidden="true"></i>
                                        <span class="site-header__nav-label">{{ $navItem['label'] }}</span>
                                    @else
                                        {{ $navItem['label'] }}
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>

            <div class="revision-header__actions">
                <a href="{{ route('cart') }}" class="revision-header__cart" aria-label="{{ __('Cart') }}">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="revision-header__cart-badge" aria-label="{{ __('Cart items') }}">
                        @auth('web')
                            {{ userAuth()->cart_count }}
                        @else
                            {{ Cart::content()->count() }}
                        @endauth
                    </span>
                </a>

                @auth('web')
                    <div class="revision-header__notifications">
                        <button type="button" class="revision-header__button revision-header__button--ghost revision-header__notifications-trigger" aria-haspopup="true" aria-expanded="false">
                            <i class="far fa-bell"></i>
                            <span class="revision-header__profile-label">{{ __('Notifications') }}</span>
                            @if ($headerUnreadNotificationCount > 0)
                                <span class="revision-header__notifications-badge">{{ $headerUnreadNotificationCount > 99 ? '99+' : $headerUnreadNotificationCount }}</span>
                            @endif
                        </button>
                        <div class="revision-header__notifications-panel">
                            <div class="revision-header__notifications-head">
                                <strong>{{ __('Notifications') }}</strong>
                                @if ($headerUnreadNotificationCount > 0)
                                    <span>{{ trans_choice(':count unread', $headerUnreadNotificationCount, ['count' => $headerUnreadNotificationCount]) }}</span>
                                @else
                                    <span>{{ __('All caught up') }}</span>
                                @endif
                            </div>

                            <div class="revision-header__notifications-list">
                                @forelse ($headerUnreadNotifications as $notification)
                                    <a
                                        href="{{ $notification['url'] ?: route(isInstructorAccount() ? 'instructor.dashboard' : 'student.dashboard') }}"
                                        class="revision-header__notification-item"
                                        data-notification-id="{{ $notification['id'] }}"
                                        data-notification-read-url="{{ route('notifications.read', ['notification' => $notification['id']]) }}"
                                        data-notification-url="{{ $notification['url'] ?: route(isInstructorAccount() ? 'instructor.dashboard' : 'student.dashboard') }}"
                                    >
                                        <div class="revision-header__notification-item-title">{{ $notification['title'] }}</div>
                                        <div class="revision-header__notification-item-message">{{ $notification['message'] }}</div>
                                        @if (!empty($notification['created_at']))
                                            <div class="revision-header__notification-item-time">{{ $notification['created_at'] }}</div>
                                        @endif
                                    </a>
                                @empty
                                    <div class="revision-header__notifications-empty">
                                        <i class="far fa-bell-slash"></i>
                                        <span>{{ __('No new notifications') }}</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endauth

                @auth('web')
                    <div class="revision-header__profile">
                        <button type="button" class="revision-header__button revision-header__button--ghost revision-header__profile-trigger" aria-haspopup="true" aria-expanded="false">
                            <img src="{{ asset('frontend/img/icons/menu_user.svg') }}" alt="{{ __('Profile') }}">
                            <span class="revision-header__profile-label">{{ __('Profile') }}</span>
                            <i class="fas fa-chevron-down revision-header__profile-caret"></i>
                        </button>
                        <ul class="revision-header__profile-menu">
                            @if ($isAdminLoggedIn)
                                <li><a href="{{ route('admin.dashboard') }}">{{ __('Admin Dashboard') }}</a></li>
                            @endif
                            @if (Auth::guard('web')->user())
                                @if (isInstructorAccount())
                                    <li><a href="{{ route('instructor.dashboard') }}">{{ __('Instructor Dashboard') }}</a></li>
                                @endif
                                <li><a href="{{ route('student.dashboard') }}">{{ __('Student Dashboard') }}</a></li>
                                <li><a href="{{ userAuth()->role == 'instructor' ? route('instructor.setting.index') : route('student.setting.index') }}">{{ __('Profile') }}</a></li>
                                <li><a href="{{ userAuth()->role == 'instructor' ? route('instructor.courses.index') : route('student.enrolled-courses') }}">{{ __('Courses') }}</a></li>
                                <li><a href="" class="text-danger logout-btn">{{ __('Logout') }}</a></li>
                            @endif
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="revision-header__button revision-header__button--ghost">
                        {{ __('Login') }}
                    </a>
                    <a href="{{ route('register') }}" class="revision-header__button revision-header__button--primary">
                        {{ __('Sign Up') }}
                    </a>
                @endauth

            </div>
        </div>

        <div class="revision-header__mobile-search d-xl-none">
            @include('frontend.layouts.partials.header-mobile-search')
        </div>
    </div>
</div>

@push('styles')
    <style>
        .revision-header {
            position: relative;
            z-index: 30;
        }

        .revision-header__bar {
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 100;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(30, 41, 59, 0.08);
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: box-shadow 0.28s ease;
        }

        .revision-header__frame {
            padding-left: clamp(14px, 2vw, 32px);
            padding-right: clamp(14px, 2vw, 32px);
        }

        .revision-header__inner {
            display: flex;
            align-items: center;
            gap: 22px;
            min-height: 92px;
            padding: 14px 0;
            width: 100%;
        }

        .revision-header__menu-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 46px;
            height: 46px;
            padding: 0;
            border-radius: 14px;
            border: 1px solid rgba(93, 63, 255, 0.14);
            color: #5d3fff;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .revision-header__menu-toggle:hover {
            transform: translateY(-1px);
            border-color: rgba(93, 63, 255, 0.24);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            color: #4328d8;
        }

        .revision-header__menu-toggle i {
            font-size: 18px;
        }

        .revision-header__menu {
            position: relative;
            flex: 0 0 auto;
            z-index: 70;
        }

        .revision-header__menu-dropdown {
            position: absolute;
            top: calc(100% + 12px);
            left: 0;
            width: min(320px, calc(100vw - 24px));
            padding: 10px;
            border-radius: 22px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(18px);
            box-shadow: 0 24px 44px rgba(15, 23, 42, 0.18);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px) scale(0.98);
            transform-origin: top left;
            transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease;
        }

        .revision-header__menu.is-open .revision-header__menu-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .revision-header__menu-dropdown::before {
            content: "";
            position: absolute;
            top: -7px;
            left: 18px;
            width: 14px;
            height: 14px;
            background: inherit;
            border-left: 1px solid rgba(15, 23, 42, 0.08);
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            transform: rotate(45deg);
        }

        .revision-header__menu-dropdown-head {
            display: grid;
            gap: 2px;
            padding: 10px 12px 12px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(93, 63, 255, 0.1), rgba(111, 57, 255, 0.04));
            margin-bottom: 10px;
        }

        .revision-header__menu-dropdown-head span {
            color: #5d3fff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .revision-header__menu-dropdown-head strong {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .revision-header__menu-dropdown-list {
            display: grid;
            gap: 6px;
        }

        .revision-header__menu-dropdown-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 48px;
            padding: 0 14px;
            border-radius: 14px;
            color: #0f172a;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.06);
            font-size: 14px;
            font-weight: 700;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
        }

        .revision-header__menu-dropdown-link i {
            color: #cbd5e1;
            font-size: 11px;
        }

        .revision-header__menu-dropdown-link:hover,
        .revision-header__menu-dropdown-link.is-active {
            border-color: rgba(93, 63, 255, 0.18);
            background: rgba(93, 63, 255, 0.08);
            color: #5d3fff;
            transform: translateX(1px);
        }

        .revision-header__menu-dropdown-link:hover i,
        .revision-header__menu-dropdown-link.is-active i {
            color: #5d3fff;
        }

        .revision-header__brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex: 0 0 auto;
            min-width: 0;
        }

        .revision-header__brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(93, 63, 255, 0.14), rgba(136, 106, 255, 0.08));
            box-shadow: inset 0 0 0 1px rgba(93, 63, 255, 0.08);
            overflow: hidden;
        }

        .revision-header__brand-mark img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .revision-header__brand-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            max-width: 155px;
        }

        .revision-header__brand-name {
            color: #5d3fff;
            font-size: 16px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.03em;
            text-transform: uppercase;
        }

        .revision-header__brand-tagline {
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.1;
        }

        .site-header__desktop-menu {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            flex: 0 1 auto;
            min-width: 0;
        }

        .revision-header__center {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 18px;
            flex: 1 1 auto;
            min-width: 0;
            margin: 0 8px;
        }

        .revision-header__search {
            display: flex;
            align-items: stretch;
            width: min(100%, 720px);
            max-width: 100%;
            height: 46px;
            flex: 1 1 auto;
            min-width: 0;
            max-width: 720px;
            gap: 0;
            border: 1px solid rgba(93, 63, 255, 0.14);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .revision-header__search-category {
            position: relative;
            flex: 0 0 150px;
            min-width: 0;
            height: 100%;
            border-right: 1px solid rgba(93, 63, 255, 0.1);
            background: transparent;
        }

        .revision-header__search-category > i.fa-th-large {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #5d3fff;
            font-size: 13px;
            pointer-events: none;
        }

        .revision-header__search-select {
            width: 100%;
            height: 100%;
            padding: 0 28px 0 34px;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            outline: none;
            appearance: none;
            transition: background-color 0.2s ease;
        }

        .revision-header__search-select:focus {
            background: rgba(255, 255, 255, 0.72);
        }

        .revision-header__search-caret {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #5d3fff;
            font-size: 12px;
            pointer-events: none;
        }

        .revision-header__search-field {
            position: relative;
            flex: 1 1 auto;
            min-width: 0;
            height: 100%;
        }

        .revision-header__search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
            pointer-events: none;
        }

        .revision-header__search input {
            width: 100%;
            height: 100%;
            padding: 0 18px 0 42px;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #0f172a;
            font-size: 14px;
            outline: none;
            transition: background-color 0.2s ease;
        }

        .revision-header__search input::placeholder {
            color: #94a3b8;
        }

        .revision-header__search input:focus {
            background: rgba(255, 255, 255, 0.72);
        }

        .revision-header__search-button {
            position: static;
            flex: 0 0 46px;
            min-width: 46px;
            height: 100%;
            padding: 0;
            border: 0;
            border-radius: 50%;
            margin: 0 4px 0 6px;
            background: linear-gradient(135deg, #5d3fff, #6f39ff);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 10px 22px rgba(93, 63, 255, 0.24);
        }

        .site-header__desktop-menu .tgmenu__main-menu {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: nowrap;
            white-space: nowrap;
            gap: clamp(14px, 1.2vw, 24px);
            margin: 0;
            padding: 0;
        }

        .site-header__nav-item {
            list-style: none;
        }

        .site-header__nav-item--pricing .site-header__nav-link {
            gap: 6px;
        }

        .site-header__nav-icon {
            display: none;
            font-size: 13px;
            line-height: 1;
        }

        .site-header__nav-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            min-height: 44px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: -0.01em;
            transition: color 0.2s ease;
        }

        .site-header__nav-link::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 4px;
            width: 100%;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, #5d3fff, #7c4dff);
            transform: scaleX(0);
            transform-origin: center;
            transition: transform 0.2s ease;
        }

        .site-header__nav-link:hover,
        .site-header__nav-link.is-active {
            color: #5d3fff;
        }

        .site-header__nav-link:hover::after,
        .site-header__nav-link.is-active::after {
            transform: scaleX(1);
        }

        .revision-header__actions {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex: 0 0 auto;
            margin-left: auto;
        }

        .revision-header__notifications {
            position: relative;
        }

        .revision-header__cart {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 999px;
            color: #0f172a;
            background: #fff;
            border: 1px solid transparent;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .revision-header__cart:hover {
            transform: translateY(-1px);
            border-color: rgba(93, 63, 255, 0.16);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .revision-header__cart i {
            font-size: 18px;
        }

        .revision-header__cart-badge {
            position: absolute;
            top: -4px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #5d3fff;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            line-height: 18px;
            text-align: center;
            box-shadow: 0 6px 16px rgba(93, 63, 255, 0.28);
        }

        .revision-header__notifications-trigger {
            position: relative;
            gap: 8px;
        }

        .revision-header__notifications-trigger i {
            font-size: 16px;
        }

        .revision-header__notifications-badge {
            position: absolute;
            top: -5px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            line-height: 18px;
            text-align: center;
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.22);
        }

        .revision-header__notifications-panel {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 60;
            width: min(360px, calc(100vw - 32px));
            padding: 12px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.14);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
        }

        .revision-header__notifications:hover .revision-header__notifications-panel,
        .revision-header__notifications:focus-within .revision-header__notifications-panel,
        .revision-header__notifications.is-open .revision-header__notifications-panel {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .revision-header__notifications-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            padding: 4px 4px 10px;
        }

        .revision-header__notifications-head strong {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .revision-header__notifications-head span {
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
        }

        .revision-header__notifications-list {
            display: grid;
            gap: 8px;
            max-height: 360px;
            overflow: auto;
        }

        .revision-header__notification-item {
            display: grid;
            gap: 4px;
            padding: 12px 12px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, 0.05);
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .revision-header__notification-item:hover {
            background: rgba(93, 63, 255, 0.07);
            transform: translateY(-1px);
        }

        .revision-header__notification-item-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
        }

        .revision-header__notification-item-message {
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        .revision-header__notification-item-time {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 500;
        }

        .revision-header__notifications-empty {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 12px;
            border-radius: 14px;
            background: #f8fafc;
            color: #64748b;
            font-size: 13px;
        }

        .revision-header__notifications-empty i {
            color: #94a3b8;
        }

        .revision-header__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: -0.01em;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            white-space: nowrap;
        }

        .revision-header__button:hover {
            transform: translateY(-1px);
        }

        .revision-header__button--ghost {
            background: #fff;
            color: #0f172a;
            border: 1px solid rgba(93, 63, 255, 0.18);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .revision-header__button--ghost:hover {
            border-color: rgba(93, 63, 255, 0.3);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            color: #5d3fff;
        }

        .revision-header__button--primary {
            background: linear-gradient(135deg, #5d3fff, #6f39ff);
            color: #fff;
            border: 1px solid rgba(93, 63, 255, 0.16);
            box-shadow: 0 12px 26px rgba(93, 63, 255, 0.24);
        }

        .revision-header__button--primary:hover {
            color: #fff;
            box-shadow: 0 14px 30px rgba(93, 63, 255, 0.3);
        }

        .revision-header__profile {
            position: relative;
        }

        .revision-header__profile-trigger {
            padding-right: 14px;
        }

        .revision-header__profile-trigger img {
            width: 18px;
            height: 18px;
        }

        .revision-header__profile-caret {
            font-size: 11px;
            color: #64748b;
        }

        .revision-header__profile-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 220px;
            margin: 0;
            padding: 10px 0;
            list-style: none;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 40;
        }

        .revision-header__profile:hover .revision-header__profile-menu,
        .revision-header__profile:focus-within .revision-header__profile-menu,
        .revision-header__profile.is-open .revision-header__profile-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .revision-header__profile-menu li a {
            display: block;
            padding: 11px 16px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 500;
        }

        .revision-header__profile-menu li a:hover {
            background: rgba(93, 63, 255, 0.08);
            color: #5d3fff;
        }

        .revision-header__mobile-search {
            display: none;
            padding-bottom: 14px;
        }

        @media (max-width: 1399.98px) {
            .site-header__desktop-menu .tgmenu__main-menu {
                gap: 14px;
            }

            .revision-header__search {
                width: min(100%, 620px);
                max-width: 620px;
            }

            .revision-header__search-category {
                flex-basis: 132px;
            }

            .revision-header__brand-name {
                font-size: 16px;
            }

            .site-header__nav-item--pricing .site-header__nav-link {
                min-height: 40px;
                padding: 0 10px;
            }

            .site-header__nav-item--pricing .site-header__nav-label {
                display: none;
            }

            .site-header__nav-item--pricing .site-header__nav-icon {
                display: inline-flex;
            }
        }

        @media (max-width: 1199.98px) {
            .revision-header__bar {
                position: sticky;
                top: 0;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
            }

            .revision-header__inner {
                gap: 16px;
                min-height: 68px;
                padding-bottom: 8px;
            }

            .revision-header__center {
                display: none;
            }

            .revision-header__actions {
                gap: 10px;
            }

            .revision-header__button,
            .revision-header__cart {
                min-height: 42px;
            }

            .revision-header__button {
                padding: 0 14px;
            }

            .revision-header__notifications-panel {
                width: min(340px, calc(100vw - 24px));
            }

            .revision-header__mobile-search {
                display: block;
                padding-bottom: 10px;
            }

            .revision-header__mobile-search .tgmobile__search {
                display: block;
            }

            .revision-header__mobile-search .tgmobile__search form {
                display: flex;
                align-items: stretch;
                gap: 0;
                width: 100%;
                height: 50px;
                border: 1px solid rgba(93, 63, 255, 0.14);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.98);
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
                overflow: hidden;
            }

            .revision-header__mobile-search .tgmobile__search-category {
                position: relative;
                flex: 0 0 96px;
                min-width: 0;
                height: 100%;
                border-right: 1px solid rgba(93, 63, 255, 0.1);
                background: rgba(93, 63, 255, 0.04);
            }

            .revision-header__mobile-search .tgmobile__search-category > i.fa-th-large {
                position: absolute;
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                color: #5d3fff;
                font-size: 11px;
                pointer-events: none;
            }

            .revision-header__mobile-search .tgmobile__search select {
                width: 100%;
                height: 100%;
                padding: 0 22px 0 28px;
                border: 0;
                border-radius: 0;
                background: transparent;
                color: #0f172a;
                font-size: 11px;
                font-weight: 700;
                outline: none;
                appearance: none;
                transition: background-color 0.2s ease;
            }

            .revision-header__mobile-search .tgmobile__search-caret {
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                color: #5d3fff;
                font-size: 10px;
                pointer-events: none;
            }

            .revision-header__mobile-search .tgmobile__search-field {
                position: relative;
                flex: 1 1 auto;
                width: 100%;
                height: 100%;
            }

            .revision-header__mobile-search .tgmobile__search input {
                width: 100%;
                height: 100%;
                padding: 0 14px 0 36px;
                border: 0;
                border-radius: 0;
                background: transparent;
                color: #0f172a;
                font-size: 14px;
                outline: none;
            }

            .revision-header__mobile-search .tgmobile__search input::placeholder {
                color: #94a3b8;
            }

            .revision-header__mobile-search .tgmobile__search input:focus,
            .revision-header__mobile-search .tgmobile__search select:focus {
                background: rgba(255, 255, 255, 0.72);
            }

            .revision-header__mobile-search .tgmobile__search-field i {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #64748b;
                font-size: 12px;
                pointer-events: none;
            }

            .revision-header__mobile-search .tgmobile__search button {
                flex: 0 0 46px;
                width: 46px;
                height: 100%;
                border: 0;
                border-radius: 0 18px 18px 0;
                margin: 0;
                background: linear-gradient(135deg, #5d3fff, #6f39ff);
                color: #fff;
                font-size: 13px;
                font-weight: 700;
                box-shadow: 0 10px 22px rgba(93, 63, 255, 0.2);
            }
        }

        @media (max-width: 575.98px) {
            .revision-header__brand-tagline {
                display: none;
            }

            .revision-header__menu-dropdown {
                width: min(100vw - 20px, 320px);
            }

            .revision-header__brand-mark {
                width: 46px;
                height: 46px;
                border-radius: 14px;
            }

            .revision-header__mobile-search {
                padding-left: 2px;
                padding-right: 2px;
            }

            .revision-header__mobile-search .tgmobile__search form {
                height: 48px;
            }

            .revision-header__mobile-search .tgmobile__search-category {
                flex-basis: 92px;
            }

            .revision-header__mobile-search .tgmobile__search input {
                font-size: 13px;
            }

            .revision-header__profile-label {
                display: none;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const header = document.querySelector('.revision-header');
            const menu = document.querySelector('.revision-header__menu');
            const menuToggle = menu?.querySelector('.revision-header__menu-toggle');
            const menuDropdown = menu?.querySelector('.revision-header__menu-dropdown');
            const profile = document.querySelector('.revision-header__profile');
            const notifications = document.querySelector('.revision-header__notifications');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const closeMenu = () => {
                menu?.classList.remove('is-open');
                menuToggle?.setAttribute('aria-expanded', 'false');
                if (menuDropdown) {
                    menuDropdown.hidden = true;
                }
            };

            if (menu && menuToggle && menuDropdown) {
                menuToggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    const isOpen = menu.classList.toggle('is-open');
                    menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    menuDropdown.hidden = !isOpen;
                });
            }

            const profileTrigger = profile?.querySelector('.revision-header__profile-trigger');
            const closeProfile = () => {
                profile?.classList.remove('is-open');
                profileTrigger?.setAttribute('aria-expanded', 'false');
            };

            if (profile && profileTrigger) {
                profileTrigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    profile.classList.toggle('is-open');
                    profileTrigger.setAttribute('aria-expanded', profile.classList.contains('is-open') ? 'true' : 'false');
                });
            }

            if (notifications) {
                const notificationsTrigger = notifications.querySelector('.revision-header__notifications-trigger');
                notificationsTrigger?.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    notifications.classList.toggle('is-open');
                    notificationsTrigger.setAttribute('aria-expanded', notifications.classList.contains('is-open') ? 'true' : 'false');
                });

                notifications.addEventListener('click', function (event) {
                    const item = event.target.closest('.revision-header__notification-item');

                    if (!item || !notifications.contains(item)) {
                        return;
                    }

                    const readUrl = item.getAttribute('data-notification-read-url');
                    const redirectUrl = item.getAttribute('data-notification-url') || item.href;

                    if (!readUrl || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                        return;
                    }

                    event.preventDefault();
                    closeProfile();
                    notifications.classList.remove('is-open');
                    notifications.querySelector('.revision-header__notifications-trigger')?.setAttribute('aria-expanded', 'false');

                    if (!csrfToken) {
                        window.location.assign(redirectUrl);
                        return;
                    }

                    fetch(readUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({}),
                    })
                        .catch(() => {})
                        .finally(() => {
                            window.location.assign(redirectUrl);
                        });
                });
            }

            document.addEventListener('click', function (event) {
                if (menu && !menu.contains(event.target)) {
                    closeMenu();
                }

                if (profile && !profile.contains(event.target)) {
                    closeProfile();
                }

                if (notifications && !notifications.contains(event.target)) {
                    notifications.classList.remove('is-open');
                    notifications.querySelector('.revision-header__notifications-trigger')?.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenu();
                    closeProfile();

                    if (notifications) {
                        notifications.classList.remove('is-open');
                        notifications.querySelector('.revision-header__notifications-trigger')?.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        });
    </script>
@endpush
