@extends('frontend.layouts.master')

@section('meta_title', __('Instructor Dashboard'))
@section('body_class', 'instructor-dashboard-page')

@section('contents')
    @php
        $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
        $currentType = Route::is('instructor.quizzes.*')
            ? 'quiz'
            : (request('type')
                ?? ($selectedType ?? null)
                ?? old('type')
                ?? (isset($product) ? $product->type : null));

        $productTypeTitles = [
            'past_paper' => __('Past Papers'),
            'prediction' => __('Predictions'),
            'note' => __('Notes'),
            'quiz' => __('Quizzes'),
        ];

    $currentDashboardTitle = match (true) {
            Route::is('instructor.dashboard') => __('Dashboard'),
            Route::is('instructor.courses.*') => __('Courses'),
            Route::is('instructor.products.*') => $productTypeTitles[$currentType] ?? __('Products'),
            Route::is('instructor.quizzes.*') => __('Quizzes'),
            Route::is('instructor.course.bundle.*') => __('Course Bundle'),
            Route::is('instructor.lesson-questions.*') => __('Lesson Questions'),
            Route::is('instructor.payout.*') => __('Request Payout'),
            Route::is('instructor.announcements.*') => __('Announcements'),
            Route::is('instructor.my-sells.*') => __('My Sales'),
            Route::is('instructor.ai-documents.*') => __('AI Documents'),
            Route::is('instructor.live-chat.*') => __('Live Chat'),
            Route::is('instructor.zoom-setting.*') => __('Zoom Live Setting'),
            Route::is('instructor.jitsi-setting.*') => __('Jitsi Live Setting'),
            Route::is('instructor.google-calendar.*') => __('Calendar'),
            Route::is('instructor.setting.*') => __('Profile Settings'),
            default => __('Instructor Dashboard'),
        };

        $isPaperEditor = Route::is('instructor.products.create') || Route::is('instructor.products.edit');
        $pendingBadge = isset($pendingContent) ? min((int) $pendingContent, 99) : 0;
        $orderBadge = isset($totalPendingOrders) ? min((int) $totalPendingOrders, 99) : 0;
        $aiDocumentNotificationSeed = auth()->check() && $hasNotificationsTable
            ? auth()->user()
                ->unreadNotifications()
                ->where('type', \App\Notifications\AiDocumentProcessedNotification::class)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => data_get($notification->data, 'title', __('AI document processed')),
                        'message' => data_get($notification->data, 'message', __('Your AI document is ready.')),
                        'url' => data_get($notification->data, 'url'),
                    ];
                })
            : collect();
    @endphp

    <section class="instructor-dashboard-shell" data-dashboard-shell>
        <aside class="instructor-dashboard-sidebar" data-dashboard-sidebar>
            <button type="button" class="dashboard-drawer-close" data-dashboard-sidebar-close aria-label="{{ __('Close sidebar') }}">
                <i class="fas fa-times"></i>
            </button>
            @include('frontend.instructor-dashboard.layouts.sidebar')
        </aside>

        <button type="button" class="instructor-dashboard-backdrop" data-dashboard-backdrop aria-label="{{ __('Close sidebar overlay') }}"></button>

        <div class="instructor-dashboard-main">
            <header class="instructor-dashboard-topbar">
                <div class="instructor-dashboard-topbar__left">
                    <button type="button" class="dashboard-icon-button dashboard-sidebar-toggle" data-dashboard-sidebar-toggle aria-label="{{ __('Toggle sidebar') }}">
                        <i class="fas fa-bars"></i>
                    </button>

                    @unless ($isPaperEditor)
                        <form class="dashboard-search" action="{{ route('instructor.dashboard') }}" method="get">
                            <i class="fas fa-search"></i>
                            <input type="search" name="q" placeholder="{{ __('Search for students, products, orders...') }}" aria-label="{{ __('Search dashboard') }}">
                            <kbd>Ctrl + /</kbd>
                        </form>
                    @endunless
                </div>

                <div class="instructor-dashboard-topbar__right">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="dashboard-pill-link">
                        {{ __('View Site') }}
                        <i class="fas fa-external-link-alt"></i>
                    </a>

                    <button type="button" class="dashboard-icon-button dashboard-notification-button" aria-label="{{ __('Notifications') }}">
                        <i class="far fa-bell"></i>
                        <span class="dashboard-badge dashboard-badge--danger">{{ $pendingBadge }}</span>
                    </button>

                    <button type="button" class="dashboard-icon-button dashboard-notification-button" aria-label="{{ __('Messages') }}">
                        <i class="far fa-comment-dots"></i>
                        <span class="dashboard-badge dashboard-badge--danger">{{ $orderBadge }}</span>
                    </button>

                </div>
            </header>

            <div class="instructor-dashboard-content">
                <div class="instructor-dashboard-content__header">
                    <div>
                        <p class="instructor-dashboard-content__eyebrow">{{ __('Current Section') }}</p>
                        <h1 class="instructor-dashboard-content__title">{{ $currentDashboardTitle }}</h1>
                    </div>
                </div>

                @yield('dashboard-contents')
            </div>
        </div>
    </section>

    <form id="instructor-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <div class="modal fade dashboard-delete-modal" id="dashboardDeleteModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content dashboard-delete-modal__content">
                <div class="dashboard-delete-modal__header">
                    <div class="dashboard-delete-modal__icon" aria-hidden="true">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div class="dashboard-delete-modal__heading">
                        <p class="dashboard-delete-modal__eyebrow">{{ __('Confirmation needed') }}</p>
                        <h5 class="dashboard-delete-modal__title">{{ __('Delete item?') }}</h5>
                        <p class="dashboard-delete-modal__subtitle">
                            <span>{{ __('You are about to delete') }}</span>
                            <strong data-dashboard-delete-name>{{ __('this item') }}</strong>
                        </p>
                    </div>
                </div>
                <div class="dashboard-delete-modal__body">
                    <p>{{ __('This action cannot be undone. Please confirm before removing it from the dashboard.') }}</p>
                </div>
                <div class="dashboard-delete-modal__footer">
                    <button type="button" class="dashboard-delete-modal__button dashboard-delete-modal__button--cancel" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <form method="POST" action="" data-dashboard-delete-form class="dashboard-delete-modal__form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dashboard-delete-modal__button dashboard-delete-modal__button--danger">
                            {{ __('Yes, delete it') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        body.instructor-dashboard-page .tg-header__area,
        body.instructor-dashboard-page .footer__area,
        body.instructor-dashboard-page .scroll__top {
            display: none !important;
        }

        body.instructor-dashboard-page {
            background:
                radial-gradient(circle at top left, rgba(91, 141, 239, 0.12), transparent 28%),
                radial-gradient(circle at top right, rgba(53, 199, 138, 0.08), transparent 18%),
                #f4f7fb;
            overflow: hidden;
        }

        body.instructor-dashboard-page .main-area {
            height: 100vh;
            padding: 0 !important;
            background: #f4f7fb;
            overflow: hidden;
        }

        .instructor-dashboard-shell {
            display: grid;
            grid-template-columns: 256px minmax(0, 1fr);
            height: 100vh;
            min-height: 0;
            background:
                linear-gradient(135deg, rgba(91, 141, 239, 0.06), transparent 34%),
                #f4f7fb;
            overflow: hidden;
        }

        .instructor-dashboard-sidebar {
            position: relative;
            height: 100vh;
            overflow: auto;
            border-right: 1px solid rgba(98, 117, 157, 0.12);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(18px);
        }

        .dashboard-drawer-close {
            display: none;
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 2;
            width: 38px;
            height: 38px;
            border: 1px solid #e2e8f3;
            border-radius: 12px;
            background: #fff;
            color: #384462;
            box-shadow: 0 10px 20px rgba(20, 33, 61, 0.06);
        }

        .instructor-dashboard-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 70;
            border: 0;
            background: rgba(15, 23, 42, 0.42);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.24s ease;
        }

        .dashboard-sidebar-shell {
            display: flex;
            flex-direction: column;
            min-height: 100%;
            padding: 16px 14px 20px;
            gap: 16px;
        }

        .dashboard-sidebar-shell__brand {
            padding: 2px 4px 8px;
        }

        .dashboard-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: inherit;
        }

        .dashboard-brand__mark {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(91, 141, 239, 0.18), rgba(106, 76, 255, 0.18));
            box-shadow: inset 0 0 0 1px rgba(91, 141, 239, 0.08);
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            padding: 8px;
        }

        .dashboard-brand__mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .dashboard-brand__text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .dashboard-brand__text strong {
            color: #16213f;
            font-size: 17px;
            font-weight: 800;
        }

        .dashboard-brand__text small {
            color: #5b8def;
            font-size: 12px;
            font-weight: 700;
        }

        .dashboard-sidebar-shell__welcome {
            display: grid;
            gap: 4px;
            padding: 14px 15px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(91, 141, 239, 0.08), rgba(53, 199, 138, 0.08));
            color: #16213f;
        }

        .dashboard-sidebar-shell__welcome span {
            color: #73809b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dashboard-sidebar-shell__welcome strong {
            font-size: 17px;
            font-weight: 800;
        }

        .dashboard-sidebar-shell__section {
            display: grid;
            gap: 8px;
        }

        .dashboard-sidebar-shell__section--spacer {
            margin-top: auto;
        }

        .dashboard-sidebar-shell__section > p {
            margin: 0;
            padding: 0 7px;
            color: #8a94ad;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dashboard-nav .list-wrap {
            display: grid;
            gap: 5px;
        }

        .dashboard-nav li a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 13px;
            border-radius: 13px;
            color: #334162;
            font-size: 13px;
            font-weight: 600;
        }

        .dashboard-nav li a i {
            width: 20px;
            color: #73809b;
            text-align: center;
            font-size: 14px;
        }

        .dashboard-nav li.active a,
        .dashboard-nav li a:hover {
            background: linear-gradient(135deg, rgba(91, 141, 239, 0.1), rgba(106, 76, 255, 0.08));
            color: #5f57d6;
        }

        .dashboard-nav li.active a i,
        .dashboard-nav li a:hover i {
            color: #5f57d6;
        }

        .dashboard-nav__group {
            border-radius: 13px;
        }

        .dashboard-nav__group-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 11px;
            padding: 11px 13px;
            border: 0;
            border-radius: 13px;
            background: transparent;
            color: #334162;
            font-size: 13px;
            font-weight: 600;
            text-align: left;
        }

        .dashboard-nav__trigger-left {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
        }

        .dashboard-nav__group-trigger i {
            width: 20px;
            color: #73809b;
            text-align: center;
            font-size: 14px;
        }

        .dashboard-nav__group.active .dashboard-nav__group-trigger,
        .dashboard-nav__group.open .dashboard-nav__group-trigger,
        .dashboard-nav__group-trigger:hover {
            background: linear-gradient(135deg, rgba(91, 141, 239, 0.1), rgba(106, 76, 255, 0.08));
            color: #5f57d6;
        }

        .dashboard-nav__group.active .dashboard-nav__group-trigger i,
        .dashboard-nav__group.open .dashboard-nav__group-trigger i,
        .dashboard-nav__group-trigger:hover i {
            color: #5f57d6;
        }

        .dashboard-nav__chevron {
            margin-left: auto;
            width: auto !important;
            font-size: 12px !important;
        }

        .dashboard-nav__submenu {
            display: none;
            margin: 5px 0 0 38px;
            padding-left: 12px;
            border-left: 1px dashed rgba(115, 128, 155, 0.2);
        }

        .dashboard-nav__group.open .dashboard-nav__submenu,
        .dashboard-nav__group.active .dashboard-nav__submenu {
            display: grid;
            gap: 6px;
        }

        .dashboard-nav__submenu a {
            display: block;
            padding: 8px 10px;
            border-radius: 9px;
            color: #667085;
            font-size: 12px;
            font-weight: 600;
        }

        .dashboard-nav__submenu li.active a,
        .dashboard-nav__submenu a:hover {
            color: #5f57d6;
            background: rgba(91, 141, 239, 0.08);
        }

        .instructor-dashboard-main {
            min-width: 0;
            height: 100vh;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .instructor-dashboard-topbar {
            position: relative;
            z-index: 40;
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 12px 26px;
            border-bottom: 1px solid rgba(98, 117, 157, 0.12);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(18px);
        }

        .instructor-dashboard-topbar__left,
        .instructor-dashboard-topbar__right {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .dashboard-search {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: min(100%, 500px);
            padding: 0 15px;
            border: 1px solid rgba(91, 141, 239, 0.14);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(20, 33, 61, 0.06);
        }

        .dashboard-search i {
            color: #8a94ad;
            font-size: 15px;
        }

        .dashboard-search input {
            width: 100%;
            height: 44px;
            border: 0;
            background: transparent;
            font-size: 13px;
            color: #16213f;
        }

        .dashboard-search input::placeholder {
            color: #8a94ad;
        }

        .dashboard-search kbd {
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px solid #d9e0eb;
            background: #f7f9fc;
            color: #5b6581;
            font-size: 12px;
            line-height: 1;
            white-space: nowrap;
        }

        .dashboard-pill-link,
        .dashboard-icon-button,
        .dashboard-profile-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px solid transparent;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .dashboard-pill-link {
            padding: 10px 15px;
            border-color: #d9e0eb;
            border-radius: 13px;
            background: #fff;
            color: #24304f;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 10px 20px rgba(20, 33, 61, 0.05);
        }

        .dashboard-pill-link:hover,
        .dashboard-icon-button:hover,
        .dashboard-profile-link:hover {
            transform: translateY(-1px);
        }

        .dashboard-icon-button {
            position: relative;
            width: 40px;
            height: 42px;
            border-radius: 11px;
            background: #fff;
            color: #384462;
            box-shadow: 0 10px 20px rgba(20, 33, 61, 0.05);
        }

        .dashboard-sidebar-toggle {
            display: none;
        }

        .paper-editor-toolbar {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
        }

        .dashboard-scroll-list {
            display: grid;
            gap: 10px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 4px 28px 0;
            overscroll-behavior: contain;
            scroll-padding-bottom: 28px;
            scrollbar-gutter: stable both-edges;
            scrollbar-width: thin;
            scrollbar-color: rgba(91, 87, 214, 0.5) rgba(91, 141, 239, 0.08);
        }

        .dashboard-scroll-list::-webkit-scrollbar {
            width: 10px;
        }

        .dashboard-scroll-list::-webkit-scrollbar-track {
            background: rgba(91, 141, 239, 0.08);
            border-radius: 999px;
        }

        .dashboard-scroll-list::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.78), rgba(109, 79, 255, 0.86));
            border-radius: 999px;
            border: 2px solid rgba(91, 141, 239, 0.08);
        }

        .dashboard-scroll-list::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.94), rgba(109, 79, 255, 1));
        }

        .dashboard-delete-modal__content {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.24);
            background: linear-gradient(180deg, #ffffff 0%, #fcfcff 100%);
        }

        .dashboard-delete-modal__header {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            padding: 24px 24px 16px;
            background: linear-gradient(145deg, rgba(239, 68, 68, 0.08), rgba(255, 255, 255, 0.96));
            border-bottom: 1px solid rgba(226, 232, 240, 0.95);
        }

        .dashboard-delete-modal__icon {
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 14px 28px rgba(239, 68, 68, 0.28);
            flex: 0 0 auto;
        }

        .dashboard-delete-modal__icon i {
            font-size: 20px;
        }

        .dashboard-delete-modal__heading {
            min-width: 0;
        }

        .dashboard-delete-modal__eyebrow {
            margin: 0 0 4px;
            color: #ef4444;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dashboard-delete-modal__title {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
            font-weight: 900;
        }

        .dashboard-delete-modal__subtitle {
            margin: 6px 0 0;
            color: #475569;
            font-size: 14px;
            line-height: 1.45;
        }

        .dashboard-delete-modal__subtitle strong {
            color: #0f172a;
            font-weight: 800;
        }

        .dashboard-delete-modal__body {
            padding: 18px 24px 10px;
            color: #475569;
            font-size: 14px;
            line-height: 1.7;
        }

        .dashboard-delete-modal__footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 18px 24px 24px;
        }

        .dashboard-delete-modal__form {
            margin: 0;
        }

        .dashboard-delete-modal__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, color 0.18s ease;
        }

        .dashboard-delete-modal__button:hover {
            transform: translateY(-1px);
        }

        .dashboard-delete-modal__button--cancel {
            border: 1px solid #dbe3f1;
            background: #fff;
            color: #334155;
        }

        .dashboard-delete-modal__button--cancel:hover {
            background: #f8fafc;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
        }

        .dashboard-delete-modal__button--danger {
            border: 0;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 12px 24px rgba(239, 68, 68, 0.22);
        }

        .dashboard-delete-modal__button--danger:hover {
            background: linear-gradient(135deg, #f05252, #c81e1e);
            box-shadow: 0 16px 28px rgba(239, 68, 68, 0.28);
        }

        @media (max-width: 575.98px) {
            .dashboard-delete-modal__header,
            .dashboard-delete-modal__body,
            .dashboard-delete-modal__footer {
                padding-left: 16px;
                padding-right: 16px;
            }

            .dashboard-delete-modal__footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .dashboard-delete-modal__button {
                width: 100%;
            }
        }

        .dashboard-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 999px;
            border: 2px solid #fff;
            font-size: 11px;
            font-weight: 700;
            line-height: 14px;
            text-align: center;
        }

        .dashboard-badge--danger {
            background: #ef4444;
            color: #fff;
        }

        .dashboard-profile-link {
            padding: 5px 9px 5px 5px;
            border-color: #d9e0eb;
            border-radius: 17px;
            background: #fff;
            color: #24304f;
            box-shadow: 0 10px 20px rgba(20, 33, 61, 0.05);
        }

        .instructor-dashboard-content {
            flex: 1 1 auto;
            min-height: 0;
            height: 0;
            overflow-y: scroll;
            overflow-x: hidden;
            padding: 22px 24px 32px;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
            scrollbar-width: thin;
            scrollbar-color: rgba(91, 87, 214, 0.5) rgba(91, 141, 239, 0.08);
        }

        .instructor-dashboard-content::-webkit-scrollbar {
            width: 10px;
        }

        .instructor-dashboard-content::-webkit-scrollbar-track {
            background: rgba(91, 141, 239, 0.08);
            border-radius: 999px;
        }

        .instructor-dashboard-content::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.78), rgba(109, 79, 255, 0.86));
            border-radius: 999px;
            border: 2px solid rgba(91, 141, 239, 0.08);
        }

        .instructor-dashboard-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.94), rgba(109, 79, 255, 1));
        }

        .instructor-dashboard-content__header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .instructor-dashboard-content__eyebrow {
            margin: 0 0 6px;
            color: #73809b;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .instructor-dashboard-content__title {
            margin: 0;
            color: #16213f;
            font-size: 26px;
            line-height: 1.1;
        }

        @media (max-width: 1199.98px) {
            .instructor-dashboard-shell {
                grid-template-columns: 240px minmax(0, 1fr);
            }

            .dashboard-search {
                min-width: min(100%, 380px);
            }
        }

        @media (max-width: 991.98px) {
            .instructor-dashboard-shell {
                grid-template-columns: 1fr;
                height: auto;
                min-height: 100vh;
            }

            .instructor-dashboard-sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                width: 286px;
                max-width: 88vw;
                transform: translateX(-102%);
                transition: transform 0.24s ease;
                z-index: 80;
                box-shadow: 24px 0 60px rgba(20, 33, 61, 0.18);
            }

            .instructor-dashboard-shell.sidebar-open .instructor-dashboard-sidebar {
                transform: translateX(0);
            }

            .instructor-dashboard-shell.sidebar-open .instructor-dashboard-backdrop {
                display: block;
                opacity: 1;
                pointer-events: auto;
            }

            .dashboard-drawer-close {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .dashboard-sidebar-toggle {
                display: inline-flex;
            }

            .instructor-dashboard-topbar {
                padding: 12px 16px;
            }

            .dashboard-search {
                min-width: 0;
            }

            .dashboard-search kbd {
                display: none;
            }

            .instructor-dashboard-content {
                min-height: 0;
                padding: 20px 16px 28px;
            }
        }

        @media (max-width: 767.98px) {
            .instructor-dashboard-topbar {
                flex-wrap: wrap;
            }

            .instructor-dashboard-topbar__left,
            .instructor-dashboard-topbar__right {
                width: 100%;
            }

            .dashboard-search {
                min-width: 0;
                width: 100%;
            }

            .instructor-dashboard-content__header {
                align-items: flex-start;
            }

            .instructor-dashboard-content__title {
                font-size: 23px;
            }

            .dashboard-scroll-list {
                padding-right: 0;
                padding-bottom: 20px;
                scroll-padding-bottom: 20px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/tinymce/js/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('frontend/js/custom-tinymce.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteFallbackLabel = @json(__('this item'));
            const deleteActionLabel = @json(__('Delete'));
            const shell = document.querySelector('[data-dashboard-shell]');
            const toggles = document.querySelectorAll('[data-dashboard-sidebar-toggle]');
            const groupToggles = document.querySelectorAll('[data-dashboard-group-toggle]');
            const closeButton = document.querySelector('[data-dashboard-sidebar-close]');
            const backdrop = document.querySelector('[data-dashboard-backdrop]');
            const deleteModalEl = document.getElementById('dashboardDeleteModal');
            const deleteModalName = deleteModalEl?.querySelector('[data-dashboard-delete-name]');
            const deleteModalForm = deleteModalEl?.querySelector('[data-dashboard-delete-form]');
            const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

            if (shell) {
                const closeSidebar = () => {
                    shell.classList.remove('sidebar-open');
                };

                const openSidebar = () => {
                    shell.classList.add('sidebar-open');
                    syncActiveGroups();
                };

                const syncActiveGroups = () => {
                    shell.querySelectorAll('.dashboard-nav__group').forEach((group) => {
                        const shouldOpen = group.classList.contains('active') || group.querySelector('li.active');
                        group.classList.toggle('open', shouldOpen);

                        const trigger = group.querySelector('[data-dashboard-group-toggle]');
                        if (trigger) {
                            trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                        }
                    });
                };

                syncActiveGroups();

                if (toggles.length) {
                    toggles.forEach((toggle) => {
                        toggle.addEventListener('click', function () {
                            shell.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
                        });
                    });
                }

                groupToggles.forEach((toggle) => {
                    toggle.addEventListener('click', function () {
                        const group = this.closest('.dashboard-nav__group');
                        if (!group) {
                            return;
                        }

                        group.classList.toggle('open');
                        this.setAttribute('aria-expanded', group.classList.contains('open') ? 'true' : 'false');
                    });
                });

                closeButton?.addEventListener('click', closeSidebar);
                backdrop?.addEventListener('click', closeSidebar);

                shell.querySelectorAll('a[href]').forEach((link) => {
                    link.addEventListener('click', function () {
                        if (window.innerWidth < 992) {
                            closeSidebar();
                        }
                    });
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeSidebar();
                    }
                });
            }

            const resolveDeleteAction = (trigger) => {
                const directUrl = trigger.getAttribute('data-delete-url') || trigger.getAttribute('href');
                if (directUrl && !['#', 'javascript:;', 'javascript:void(0)', 'javascript:void(0);'].includes(directUrl.trim().toLowerCase())) {
                    return directUrl;
                }

                const localForm = trigger.querySelector('form[action]');
                if (localForm?.getAttribute('action')) {
                    return localForm.getAttribute('action');
                }

                const nearbyForm = trigger.closest('tr, .card, .accordion-item, .dashboard__review-item, .dashboard__review-row, .wsus_lesson_qna_list, .wsus_reply_item, .instructor-product-row, .instructor-course-row')?.querySelector('form[action]');
                if (nearbyForm?.getAttribute('action')) {
                    return nearbyForm.getAttribute('action');
                }

                return null;
            };

            const resolveDeleteLabel = (trigger) => {
                const explicitLabel =
                    trigger.getAttribute('data-delete-title') ||
                    trigger.getAttribute('data-title') ||
                    trigger.getAttribute('title') ||
                    trigger.getAttribute('aria-label');

                if (explicitLabel && !['delete', deleteActionLabel.toLowerCase()].includes(explicitLabel.toLowerCase())) {
                    return explicitLabel;
                }

                const row = trigger.closest('tr, .card, .accordion-item, .dashboard__review-item, .dashboard__review-row, .wsus_lesson_qna_list, .wsus_reply_item, .instructor-product-row, .instructor-course-row, .course-section-item');
                const candidate = row?.querySelector('h1, h2, h3, h4, h5, h6, strong, .title, .bold-text, .wsus_qna_question a, .wsus_reply_header a, .instructor-product-row__middle .title a, .instructor-course-row__middle .title a, td:nth-child(3) p');

                return (candidate?.textContent || '').trim() || deleteFallbackLabel;
            };

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('.dashboard-delete-item');
                if (!trigger) {
                    return;
                }

                const action = resolveDeleteAction(trigger);
                if (!action || !deleteModal || !deleteModalForm) {
                    return;
                }

                event.preventDefault();
                deleteModalForm.setAttribute('action', action);
                if (deleteModalName) {
                    deleteModalName.textContent = resolveDeleteLabel(trigger);
                }
                deleteModal.show();
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const seedNotifications = @json($aiDocumentNotificationSeed->values());
            const fetchUrl = @json(route('instructor.notifications.ai-documents.index'));
            const markReadUrlTemplate = @json(route('instructor.notifications.ai-documents.read', ['notification' => '__NOTIFICATION_ID__']));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const seenIds = new Set(seedNotifications.map((notification) => notification.id));

            const markRead = (notificationId) => {
                if (!csrfToken || !notificationId) {
                    return;
                }

                const url = markReadUrlTemplate.replace('__NOTIFICATION_ID__', encodeURIComponent(notificationId));

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                }).catch(() => {});
            };

            const showNotification = (notification) => {
                if (!notification || !notification.id || seenIds.has(notification.id) || !window.revisionHubToast) {
                    return;
                }

                seenIds.add(notification.id);
                window.revisionHubToast('success', notification.message || @json(__('Your AI document is ready.')), {
                    title: notification.title || @json(__('AI document processed')),
                    timeOut: 8000,
                });
                markRead(notification.id);
            };

            const syncNotifications = () => {
                if (!fetchUrl) {
                    return;
                }

                fetch(fetchUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => response.ok ? response.json() : Promise.reject(response))
                    .then((payload) => {
                        (payload.notifications || []).forEach(showNotification);
                    })
                    .catch(() => {});
            };

            seedNotifications.forEach(showNotification);
            syncNotifications();
            window.setInterval(syncNotifications, 30000);

            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    syncNotifications();
                }
            });
        });
    </script>
@endpush
