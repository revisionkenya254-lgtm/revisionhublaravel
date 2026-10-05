@extends('frontend.student-dashboard.layouts.master')

@push('styles')
    <style>
        .student-dashboard-hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 28px;
            background:
                radial-gradient(circle at top right, rgba(97, 140, 255, 0.22), transparent 35%),
                radial-gradient(circle at bottom left, rgba(244, 114, 182, 0.18), transparent 30%),
                linear-gradient(135deg, #0f172a 0%, #111827 55%, #1f2937 100%);
            color: #f8fafc;
            margin-bottom: 24px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.16);
        }

        .student-dashboard-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 16px;
        }

        .student-dashboard-hero__title {
            font-size: clamp(28px, 3vw, 40px);
            line-height: 1.08;
            margin-bottom: 12px;
            color: #fff;
        }

        .student-dashboard-hero__text {
            max-width: 760px;
            color: rgba(255, 255, 255, 0.82);
            margin-bottom: 22px;
        }

        .student-dashboard-hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .student-dashboard-hero__type-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .student-dashboard-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .student-dashboard-btn:hover {
            transform: translateY(-1px);
        }

        .student-dashboard-btn--solid {
            background: #fff;
            color: #0f172a;
            box-shadow: 0 16px 28px rgba(255, 255, 255, 0.12);
        }

        .student-dashboard-btn--ghost {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.14);
        }

        .student-dashboard-btn--chip {
            padding: 9px 13px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.075);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.01em;
            box-shadow: none;
        }

        .student-dashboard-btn--chip:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-1px);
        }

        .student-dashboard-btn--chip i {
            font-size: 13px;
            opacity: 0.95;
        }

        .student-dashboard-btn--chip--pink {
            background: rgba(244, 114, 182, 0.14);
            border-color: rgba(244, 114, 182, 0.22);
            color: #ffd0e6;
        }

        .student-dashboard-btn--chip--green {
            background: rgba(22, 163, 74, 0.14);
            border-color: rgba(22, 163, 74, 0.22);
            color: #d8fce4;
        }

        .student-dashboard-btn--chip--orange {
            background: rgba(249, 115, 22, 0.16);
            border-color: rgba(249, 115, 22, 0.24);
            color: #ffe3c9;
        }

        .student-dashboard-btn--chip--violet {
            background: rgba(124, 58, 237, 0.16);
            border-color: rgba(124, 58, 237, 0.24);
            color: #e5d7ff;
        }

        .student-dashboard-btn--chip--pink:hover {
            background: rgba(244, 114, 182, 0.2);
        }

        .student-dashboard-btn--chip--green:hover {
            background: rgba(22, 163, 74, 0.2);
        }

        .student-dashboard-btn--chip--orange:hover {
            background: rgba(249, 115, 22, 0.22);
        }

        .student-dashboard-btn--chip--violet:hover {
            background: rgba(124, 58, 237, 0.22);
        }

        .student-dashboard-mini-grid,
        .student-dashboard-types-grid,
        .student-dashboard-learning-grid,
        .student-dashboard-actions-grid {
            display: grid;
            gap: 16px;
        }

        .student-dashboard-mini-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 24px;
        }

        .student-dashboard-types-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 24px;
        }

        .student-dashboard-learning-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .student-dashboard-actions-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 16px;
        }

        .student-dashboard-stat,
        .student-dashboard-type-card,
        .student-dashboard-learning-card,
        .student-dashboard-action-card,
        .student-dashboard-panel {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
        }

        .student-dashboard-stat {
            padding: 20px;
        }

        .student-dashboard-stat__icon,
        .student-dashboard-type-card__icon,
        .student-dashboard-action-card__icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }

        .student-dashboard-stat__icon--blue,
        .student-dashboard-type-card__icon--blue,
        .student-dashboard-action-card__icon--blue {
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
        }

        .student-dashboard-stat__icon--green,
        .student-dashboard-type-card__icon--green,
        .student-dashboard-action-card__icon--green {
            background: rgba(22, 163, 74, 0.12);
            color: #16a34a;
        }

        .student-dashboard-stat__icon--amber,
        .student-dashboard-type-card__icon--amber,
        .student-dashboard-action-card__icon--amber {
            background: rgba(245, 158, 11, 0.14);
            color: #f59e0b;
        }

        .student-dashboard-stat__icon--rose,
        .student-dashboard-type-card__icon--rose,
        .student-dashboard-action-card__icon--rose {
            background: rgba(244, 114, 182, 0.14);
            color: #db2777;
        }

        .student-dashboard-stat__icon--violet,
        .student-dashboard-type-card__icon--violet,
        .student-dashboard-action-card__icon--violet {
            background: rgba(124, 58, 237, 0.14);
            color: #7c3aed;
        }

        .student-dashboard-stat__icon--slate,
        .student-dashboard-type-card__icon--slate,
        .student-dashboard-action-card__icon--slate {
            background: rgba(100, 116, 139, 0.14);
            color: #475569;
        }

        .student-dashboard-stat__value {
            font-size: 30px;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .student-dashboard-stat__label,
        .student-dashboard-type-card__eyebrow,
        .student-dashboard-learning-card__eyebrow,
        .student-dashboard-action-card__label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
        }

        .student-dashboard-stat__hint,
        .student-dashboard-type-card__text,
        .student-dashboard-learning-card__text,
        .student-dashboard-action-card__text {
            color: #64748b;
            margin-bottom: 0;
        }

        .student-dashboard-section {
            margin-bottom: 24px;
        }

        .student-dashboard-section__head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .student-dashboard-section__title {
            margin-bottom: 4px;
        }

        .student-dashboard-section__subtitle {
            color: #64748b;
            margin-bottom: 0;
        }

        .student-dashboard-type-card {
            padding: 18px;
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }

        .student-dashboard-type-card__head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 16px;
        }

        .student-dashboard-type-card__count {
            font-size: 34px;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }

        .student-dashboard-type-card__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: auto;
            padding-top: 16px;
        }

        .student-dashboard-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            font-weight: 600;
            font-size: 13px;
        }

        .student-dashboard-panel {
            padding: 20px;
        }

        .student-dashboard-learning-card {
            overflow: hidden;
        }

        .student-dashboard-learning-card__thumb {
            height: 170px;
            overflow: hidden;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .student-dashboard-learning-card__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-dashboard-learning-card__body {
            padding: 18px;
        }

        .student-dashboard-learning-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .student-dashboard-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #f8fafc;
            color: #0f172a;
        }

        .student-dashboard-badge--blue { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
        .student-dashboard-badge--green { background: rgba(22, 163, 74, 0.1); color: #16a34a; }
        .student-dashboard-badge--amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .student-dashboard-badge--rose { background: rgba(244, 114, 182, 0.12); color: #db2777; }
        .student-dashboard-badge--violet { background: rgba(124, 58, 237, 0.12); color: #7c3aed; }
        .student-dashboard-badge--slate { background: rgba(100, 116, 139, 0.12); color: #475569; }

        .student-dashboard-progress {
            margin-top: 16px;
        }

        .student-dashboard-progress .progress {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .student-dashboard-progress .progress-bar {
            border-radius: inherit;
        }

        .student-dashboard-list {
            display: grid;
            gap: 14px;
        }

        .student-dashboard-list__item {
            display: flex;
            gap: 14px;
            padding: 16px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .student-dashboard-list__thumb {
            width: 76px;
            height: 76px;
            flex: 0 0 76px;
            border-radius: 16px;
            overflow: hidden;
            background: #f8fafc;
        }

        .student-dashboard-list__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-dashboard-list__content {
            flex: 1;
            min-width: 0;
        }

        .student-dashboard-list__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .student-dashboard-list__title {
            margin-bottom: 8px;
        }

        .student-dashboard-list__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 12px;
        }

        .student-dashboard-list__time {
            color: #64748b;
            font-size: 13px;
        }

        .student-dashboard-empty {
            padding: 18px;
            border-radius: 18px;
            background: #f8fafc;
            color: #64748b;
        }

        @media (max-width: 1199px) {
            .student-dashboard-mini-grid,
            .student-dashboard-types-grid,
            .student-dashboard-learning-grid,
            .student-dashboard-actions-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .student-dashboard-hero {
                padding: 22px;
            }

            .student-dashboard-mini-grid,
            .student-dashboard-types-grid,
            .student-dashboard-learning-grid,
            .student-dashboard-actions-grid {
                grid-template-columns: 1fr;
            }

            .student-dashboard-section__head {
                align-items: start;
                flex-direction: column;
            }

            .student-dashboard-list__item {
                flex-direction: column;
            }

            .student-dashboard-list__thumb {
                width: 100%;
                height: 180px;
                flex-basis: auto;
            }
        }
    </style>
@endpush

@section('dashboard-contents')
    @if (instructorStatus() == 'pending')
        <div class="alert alert-primary d-flex align-items-center" role="alert">
            <svg 0 16 xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 16" role="img" aria-label="Warning:">
                <path 0 1 2 8
                    d="M8.982 1.566a1.13 1.13 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 .954.462.9.995l-.35 3.507a.552.552 1-1.1 0L7.1 5.995A.905.905 5zm.002 6a1 0-2z" />
                </path>
            </svg>
            <div>
                <span class="site-header__role-badge {{ instructorRequestBadgeClass() }} mb-2">
                    <i class="fas {{ instructorRequestBadgeIcon() }}"></i>
                    {{ instructorRequestBadgeLabel() }}
                </span>
                <div>
                    {{ __('We received your request to become instructor') }}. {{ __('Please wait for admin approval') }}!
                    <a href="{{ route('become-instructor.review') }}">{{ __('Review submitted details') }}</a>
                </div>
            </div>
        </div>
    @elseif (instructorStatus() == 'rejected')
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <svg 0 16 xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 16" role="img"
                aria-label="Warning:">
                <path 0 1 2 8
                    d="M8.982 1.566a1.13 1.13 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 .954.462.9.995l-.35 3.507a.552.552 1-1.1 0L7.1 5.995A.905.905 5zm.002 6a1 0-2z" />
                </path>
            </svg>
            <div>
                <span class="site-header__role-badge {{ instructorRequestBadgeClass() }} mb-2">
                    <i class="fas {{ instructorRequestBadgeIcon() }}"></i>
                    {{ instructorRequestBadgeLabel() }}
                </span>
                <div>
                    {{ __('Your request to become instructor has been rejected. Please resubmit your request with valid information') }}
                    <a href="{{ route('become-instructor.review') }}">{{ __('here') }}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="student-dashboard-hero">
        <div class="student-dashboard-hero__eyebrow">
            <i class="fas fa-layer-group"></i>
            <span>{{ __('Student Dashboard') }}</span>
        </div>
        <h1 class="student-dashboard-hero__title">{{ __('Your learning hub for every product type') }}</h1>
        <p class="student-dashboard-hero__text">
            {{ __('Track courses, notes, past papers, predictions, and quizzes from one place. Open what you bought, resume what you started, and jump back into your revision flow quickly.') }}
        </p>
        <div class="student-dashboard-hero__actions">
            @foreach ($quickLinks as $quickLink)
                <a href="{{ $quickLink['url'] }}" class="student-dashboard-btn {{ $quickLink['tone'] === 'slate' ? 'student-dashboard-btn--ghost' : 'student-dashboard-btn--solid' }}">
                    <i class="fas {{ $quickLink['icon'] }}"></i>
                    <span>{{ $quickLink['label'] }}</span>
                </a>
            @endforeach
        </div>
        <div class="student-dashboard-hero__type-actions">
            @foreach ($productTypeQuickLinks as $quickLink)
                <a href="{{ $quickLink['url'] }}" class="student-dashboard-btn student-dashboard-btn--chip student-dashboard-btn--chip--{{ $quickLink['tone'] }}">
                    <i class="fas {{ $quickLink['icon'] }}"></i>
                    <span>{{ $quickLink['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="student-dashboard-mini-grid">
        @foreach ($dashboardStats as $stat)
            <div class="student-dashboard-stat">
                <div class="student-dashboard-stat__icon student-dashboard-stat__icon--{{ $stat['tone'] }}">
                    <i class="{{ $stat['icon'] }}"></i>
                </div>
                <div class="student-dashboard-stat__value odometer" data-count="{{ $stat['value'] }}"></div>
                <div class="student-dashboard-stat__label">{{ $stat['label'] }}</div>
                <p class="student-dashboard-stat__hint">{{ $stat['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="student-dashboard-section">
        <div class="student-dashboard-section__head">
            <div>
                <h4 class="student-dashboard-section__title">{{ __('Product Coverage') }}</h4>
                <p class="student-dashboard-section__subtitle">{{ __('Every product type gets a visible place on the dashboard.') }}</p>
            </div>
            <a href="{{ route('student.library') }}" class="student-dashboard-pill">
                <i class="fas fa-book-open"></i>
                <span>{{ __('Open Library') }}</span>
            </a>
        </div>

        <div class="student-dashboard-types-grid">
            @foreach ($productTypeCards as $card)
                <article class="student-dashboard-type-card">
                    <div class="student-dashboard-type-card__head">
                        <div>
                            <div class="student-dashboard-type-card__eyebrow">{{ $card['label'] }}</div>
                            <div class="student-dashboard-type-card__count">{{ $card['count'] }}</div>
                        </div>
                        <div class="student-dashboard-type-card__icon student-dashboard-type-card__icon--{{ $card['tone'] }}">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                    </div>
                    <p class="student-dashboard-type-card__text">{{ $card['description'] }}</p>
                    <div class="student-dashboard-type-card__footer">
                        <span class="student-dashboard-pill">
                            <i class="fas fa-arrow-right"></i>
                            <span>{{ __('View') }}</span>
                        </span>
                        <a href="{{ $card['url'] }}" class="student-dashboard-pill">
                            <i class="fas fa-folder-open"></i>
                            <span>{{ __('Open') }}</span>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <div class="student-dashboard-section">
        <div class="student-dashboard-section__head">
            <div>
                <h4 class="student-dashboard-section__title">{{ __('AI Workspace') }}</h4>
                <p class="student-dashboard-section__subtitle">{{ __('Manage chat, credits, and AI-powered revision from the same dashboard.') }}</p>
            </div>
        </div>

        <div class="student-dashboard-types-grid">
            @foreach ($aiWorkspaceCards as $card)
                <article class="student-dashboard-type-card">
                    <div class="student-dashboard-type-card__head">
                        <div>
                            <div class="student-dashboard-type-card__eyebrow">{{ $card['label'] }}</div>
                            <div class="student-dashboard-type-card__count">{{ $card['count'] }}</div>
                        </div>
                        <div class="student-dashboard-type-card__icon student-dashboard-type-card__icon--{{ $card['tone'] }}">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                    </div>
                    <p class="student-dashboard-type-card__text">{{ $card['description'] }}</p>
                    <div class="student-dashboard-type-card__footer">
                        <span class="student-dashboard-pill">
                            <i class="fas fa-arrow-right"></i>
                            <span>{{ __('View') }}</span>
                        </span>
                        <a href="{{ $card['url'] }}" class="student-dashboard-pill">
                            <i class="fas fa-folder-open"></i>
                            <span>{{ __('Open') }}</span>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <div class="student-dashboard-section">
        <div class="student-dashboard-section__head">
            <div>
                <h4 class="student-dashboard-section__title">{{ __('Continue Learning') }}</h4>
                <p class="student-dashboard-section__subtitle">{{ __('Resume the most recent course or product you interacted with.') }}</p>
            </div>
        </div>

        <div class="student-dashboard-learning-grid">
            @forelse ($continueLearning as $item)
                <article class="student-dashboard-learning-card">
                    <div class="student-dashboard-learning-card__thumb">
                        <img src="{{ asset($item->thumbnail ?: 'uploads/website-images/empty-cart.png') }}" alt="{{ $item->title }}">
                    </div>
                    <div class="student-dashboard-learning-card__body">
                        <div class="student-dashboard-learning-card__head">
                            <span class="student-dashboard-badge student-dashboard-badge--{{ $item->badge_tone }}">
                                <i class="fas fa-star"></i>
                                <span>{{ $item->badge }}</span>
                            </span>
                            @if (!empty($item->progress_label))
                                <span class="student-dashboard-pill">{{ $item->progress_label }}</span>
                            @endif
                        </div>
                        <h5 class="title mb-2">{{ truncate($item->title, 55) }}</h5>
                        <p class="student-dashboard-learning-card__text mb-2">{{ $item->subtitle }}</p>

                        @if (!empty($item->meta))
                            <div class="student-dashboard-list__meta">
                                @foreach ($item->meta as $metaItem)
                                    <span class="student-dashboard-pill">
                                        <strong class="me-1">{{ $metaItem['label'] }}:</strong>
                                        <span>{{ $metaItem['value'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if (isset($item->progress_percent))
                            <div class="student-dashboard-progress">
                                <div class="progress" role="progressbar" aria-valuenow="{{ number_format($item->progress_percent, 1) }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: {{ number_format($item->progress_percent, 1) }}%"></div>
                                </div>
                            </div>
                        @endif

                        <div class="student-dashboard-type-card__footer mt-3">
                            <span class="student-dashboard-list__time">
                                {{ !empty($item->last_activity_at) ? formattedDateTime($item->last_activity_at) : __('Recently added') }}
                            </span>
                            <a href="{{ $item->action_url }}" class="student-dashboard-pill">
                                <i class="fas fa-arrow-right"></i>
                                <span>{{ __('Open') }}</span>
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="student-dashboard-empty">
                    {{ __('No recent items yet. Open the library to start reading or taking a quiz.') }}
                </div>
            @endforelse
        </div>
    </div>

    <div class="student-dashboard-section">
        <div class="student-dashboard-section__head">
            <div>
                <h4 class="student-dashboard-section__title">{{ __('Recent Resources') }}</h4>
                <p class="student-dashboard-section__subtitle">{{ __('The newest accessible products across your library.') }}</p>
            </div>
        </div>

        <div class="student-dashboard-list">
            @forelse ($recentResources as $item)
                @php
                    $product = $item->product;
                @endphp
                <div class="student-dashboard-list__item">
                    <div class="student-dashboard-list__thumb">
                        <img src="{{ asset($product?->thumbnail ?: 'uploads/website-images/empty-cart.png') }}" alt="{{ $product?->title }}">
                    </div>
                    <div class="student-dashboard-list__content">
                        <div class="student-dashboard-list__meta">
                            <span class="student-dashboard-badge student-dashboard-badge--{{ $item->tone }}">
                                <i class="{{ $item->icon }}"></i>
                                <span>{{ $product?->type_label }}</span>
                            </span>
                            <span class="student-dashboard-badge student-dashboard-badge--slate">
                                <i class="fas fa-lock-open"></i>
                                <span>{{ $item->access_source_label }}</span>
                            </span>
                            @if (!empty($item->progress_label))
                                <span class="student-dashboard-badge student-dashboard-badge--amber">
                                    <i class="fas fa-chart-line"></i>
                                    <span>{{ $item->progress_label }}</span>
                                </span>
                            @endif
                        </div>
                        <h5 class="student-dashboard-list__title title">
                            <a href="{{ $item->action_url }}">{{ $product?->title }}</a>
                        </h5>
                        <p class="student-dashboard-stat__hint mb-0">
                            {{ $product?->category?->translation?->name ?? $product?->category?->name ?? __('Revision resource') }}
                        </p>
                    </div>
                    <div class="student-dashboard-list__footer">
                        <span class="student-dashboard-list__time">
                            {{ !empty($item->last_activity_at) ? formattedDateTime($item->last_activity_at) : __('Recently added') }}
                        </span>
                        <a href="{{ $item->action_url }}" class="student-dashboard-pill">
                            <i class="fas fa-arrow-right"></i>
                            <span>{{ $product?->access_label }}</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="student-dashboard-empty">
                    {{ __('No accessible resources found yet.') }}
                </div>
            @endforelse
        </div>
    </div>

    <div class="student-dashboard-section">
        <div class="student-dashboard-section__head">
            <div>
                <h4 class="student-dashboard-section__title">{{ __('Order History') }}</h4>
                <p class="student-dashboard-section__subtitle">{{ __('Recent purchases and payment status.') }}</p>
            </div>
            <a href="{{ route('student.orders.index') }}" class="student-dashboard-pill">
                <i class="fas fa-receipt"></i>
                <span>{{ __('All Orders') }}</span>
            </a>
        </div>

        <div class="dashboard__review-table table-responsive student-dashboard-panel p-0">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th>{{ __('No') }}</th>
                        <th>{{ __('Invoice') }}</th>
                        <th>{{ __('Paid') }}</th>
                        <th>{{ __('Gateway') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Payment') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $index => $order)
                        <tr>
                            <td>{{ ++$index }}</td>
                            <td>#{{ $order->invoice_id }}</td>
                            <td>{{ $order->paid_amount }} {{ $order->payable_currency }}</td>
                            <td class="text-capitalize">
                                {{ str_replace('_', ' ', $order->payment_method) }}
                            </td>
                            <td>
                                @if ($order->status == 'completed')
                                    <div class="badge bg-success">{{ __('Completed') }}</div>
                                @elseif($order->status == 'processing')
                                    <div class="badge bg-warning">{{ __('Processing') }}</div>
                                @elseif($order->status == 'declined')
                                    <div class="badge bg-danger">{{ __('Declined') }}</div>
                                @else
                                    <div class="badge bg-warning">{{ __('Pending') }}</div>
                                @endif
                            </td>

                            <td>
                                @if ($order->payment_status == 'paid')
                                    <div class="badge bg-success">{{ __('Paid') }}</div>
                                @elseif ($order->payment_status == 'cancelled')
                                    <div class="badge bg-danger">{{ __('Cancelled') }}</div>
                                @else
                                    <div class="badge bg-danger">{{ __('Pending') }}</div>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('student.order.show', $order->id) }}" class=""><i class="fa fa-eye"></i></a>
                                @if ($setting?->is_refundable == 'active' && $order->payment_status == 'paid' && $order->instructorEarningsHolds->where('status', 'pending')->isNotEmpty())
                                    <a href="{{ route('refund-request.create', $order->invoice_id) }}" class="bg-dark" title="Refund"><i class="fas fa-undo"></i></a>
                                @endif
                                @if ($order->payment_method !== 'Free' && !in_array($order->payment_status, ['paid', 'refunded']) && app(\Modules\BasicPayment\app\Services\PaymentMethodService::class)->isActive($order->payment_method))
                                    <a target="_blank" href="{{ route('payment', ['invoice_id' => $order->invoice_id]) }}" class="bg-info"><i class="fa fa-credit-card"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">{{ __('No orders found!') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
