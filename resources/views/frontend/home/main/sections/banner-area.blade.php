@php
    $heroSearchTypes = [
        '' => __('Categories'),
        \App\Models\Product::TYPE_COURSE => __('Videos'),
        \App\Models\Product::TYPE_NOTE => __('Notes'),
        \App\Models\Product::TYPE_PAST_PAPER => __('Papers'),
        \App\Models\Product::TYPE_PREDICTION => __('Predictions'),
        \App\Models\Product::TYPE_QUIZ => __('Quizzes'),
    ];

    $heroImage = $hero?->global_content?->banner_image;
@endphp

<section class="revision-hero">
    <div class="revision-hero__glow revision-hero__glow--one" aria-hidden="true"></div>
    <div class="revision-hero__glow revision-hero__glow--two" aria-hidden="true"></div>

    <div class="container-fluid revision-hero__frame">
        <div class="revision-hero__shell">
            <div class="revision-hero__copy" data-aos="fade-right" data-aos-delay="200">
                <div class="revision-hero__eyebrow">
                    <span class="revision-hero__eyebrow-dot"></span>
                    <span>{{ $setting?->app_name ?? config('app.name') }}</span>
                </div>

                <h1 class="revision-hero__title">
                    {{ __('Your All-in-One Revision Companion') }}
                </h1>

                <p class="revision-hero__subtitle">
                    {{ __('Past papers, notes, videos, predictions and quizzes. Everything you need to learn smarter and excel.') }}
                </p>

                <form action="{{ route('catalog') }}" method="GET" class="revision-hero__search" role="search">
                    <div class="revision-hero__search-category">
                        <i class="fas fa-th-large" aria-hidden="true"></i>
                        <select name="type" class="revision-hero__search-select" aria-label="{{ __('Search category') }}">
                            @foreach ($heroSearchTypes as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" @selected((string) request('type') === (string) $typeValue)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down revision-hero__search-caret" aria-hidden="true"></i>
                    </div>
                    <div class="revision-hero__search-field">
                        <i class="fas fa-search revision-hero__search-icon" aria-hidden="true"></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="{{ __('Search For Course . . .') }}"
                            aria-label="{{ __('Search papers, notes, quizzes') }}"
                        >
                    </div>
                    <button type="submit" class="revision-hero__search-button">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="revision-hero__feature-grid">
                    <div class="revision-hero__feature-card revision-hero__feature-card--purple">
                        <span class="revision-hero__feature-icon"><i class="fas fa-star"></i></span>
                        <strong>{{ __('AI Powered') }}</strong>
                        <span>{{ __('Get instant answers') }}</span>
                    </div>
                    <div class="revision-hero__feature-card revision-hero__feature-card--green">
                        <span class="revision-hero__feature-icon"><i class="fas fa-shield-alt"></i></span>
                        <strong>{{ __('Trusted Content') }}</strong>
                        <span>{{ __('Quality study materials') }}</span>
                    </div>
                    <div class="revision-hero__feature-card revision-hero__feature-card--amber">
                        <span class="revision-hero__feature-icon"><i class="fas fa-graduation-cap"></i></span>
                        <strong>{{ __('For All Levels') }}</strong>
                        <span>{{ __('Primary to University') }}</span>
                    </div>
                    <div class="revision-hero__feature-card revision-hero__feature-card--blue">
                        <span class="revision-hero__feature-icon"><i class="fas fa-mobile-alt"></i></span>
                        <strong>{{ __('Learn Anywhere') }}</strong>
                        <span>{{ __('On any device') }}</span>
                    </div>
                </div>
            </div>

            <div class="revision-hero__visual" data-aos="fade-left" data-aos-delay="300">
                <div class="revision-hero__visual-panel">
                    <div class="revision-hero__image-wrap">
                        @if (!empty($heroImage))
                            <img src="{{ asset($heroImage) }}" alt="{{ $setting?->app_name ?? config('app.name') }} hero image" class="revision-hero__image">
                        @else
                            <div class="revision-hero__image-fallback" aria-hidden="true">
                                <span>{{ __('Revision Hub') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="revision-hero__assistant-card">
                        <div class="revision-hero__assistant-head">
                            <strong>{{ __('AI Study Assistant') }}</strong>
                            <span>{{ __('New') }}</span>
                        </div>
                        <p>{{ __('Ask questions, get explanations, summaries and more.') }}</p>
                        <a href="{{ route('ai-chat') }}" class="revision-hero__assistant-button">
                            <i class="fas fa-robot"></i>
                            <span>{{ __('Chat with AI') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('styles')
    <style>
        .revision-hero {
            position: relative;
            overflow: hidden;
            padding: 12px 0 0;
        }

        .revision-hero__frame {
            position: relative;
            z-index: 1;
            padding-left: clamp(14px, 2vw, 32px);
            padding-right: clamp(14px, 2vw, 32px);
        }

        .revision-hero__shell {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
            gap: clamp(20px, 2.4vw, 36px);
            align-items: center;
            padding: clamp(22px, 3vw, 40px);
            border-radius: 30px;
            background:
                radial-gradient(circle at 10% 10%, rgba(93, 63, 255, 0.08), transparent 30%),
                radial-gradient(circle at 82% 18%, rgba(111, 57, 255, 0.08), transparent 26%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 245, 255, 0.98));
            border: 1px solid rgba(93, 63, 255, 0.08);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        }

        .revision-hero__glow {
            position: absolute;
            border-radius: 999px;
            filter: blur(70px);
            opacity: 0.7;
            pointer-events: none;
        }

        .revision-hero__glow--one {
            top: -30px;
            left: -50px;
            width: 220px;
            height: 220px;
            background: rgba(93, 63, 255, 0.14);
        }

        .revision-hero__glow--two {
            right: -40px;
            bottom: -60px;
            width: 240px;
            height: 240px;
            background: rgba(111, 57, 255, 0.1);
        }

        .revision-hero__copy {
            position: relative;
            z-index: 1;
            max-width: 720px;
        }

        .revision-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(93, 63, 255, 0.08);
            color: #5d3fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .revision-hero__eyebrow-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(135deg, #5d3fff, #7c4dff);
            box-shadow: 0 0 0 6px rgba(93, 63, 255, 0.08);
        }

        .revision-hero__title {
            margin: 0;
            color: #111827;
            font-size: clamp(42px, 5vw, 70px);
            font-weight: 800;
            line-height: 0.98;
            letter-spacing: -0.05em;
            max-width: 11ch;
        }

        .revision-hero__subtitle {
            margin: 18px 0 0;
            max-width: 680px;
            color: #475569;
            font-size: clamp(17px, 1.45vw, 22px);
            line-height: 1.5;
        }

        .revision-hero__search {
            display: flex;
            align-items: stretch;
            width: min(100%, 600px);
            height: 46px;
            margin-top: 20px;
            gap: 0;
            border: 1px solid rgba(93, 63, 255, 0.14);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .revision-hero__search-category {
            position: relative;
            flex: 0 0 132px;
            min-width: 0;
            height: 100%;
            border-right: 1px solid rgba(93, 63, 255, 0.1);
        }

        .revision-hero__search-category > i.fa-th-large {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #5d3fff;
            font-size: 13px;
            pointer-events: none;
        }

        .revision-hero__search-select {
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

        .revision-hero__search-select:focus {
            background: rgba(255, 255, 255, 0.72);
        }

        .revision-hero__search-caret {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #5d3fff;
            font-size: 12px;
            pointer-events: none;
        }

        .revision-hero__search-field {
            position: relative;
            flex: 1 1 auto;
            min-width: 0;
            height: 100%;
        }

        .revision-hero__search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
            pointer-events: none;
        }

        .revision-hero__search input {
            width: 100%;
            height: 100%;
            padding: 0 18px 0 40px;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #0f172a;
            font-size: 14px;
            outline: none;
            transition: background-color 0.2s ease;
        }

        .revision-hero__search input::placeholder {
            color: #94a3b8;
        }

        .revision-hero__search input:focus {
            background: rgba(255, 255, 255, 0.72);
        }

        .revision-hero__search-button {
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

        .revision-hero__feature-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .revision-hero__feature-card {
            display: grid;
            gap: 4px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
        }

        .revision-hero__feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 14px;
            font-size: 16px;
        }

        .revision-hero__feature-card strong {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .revision-hero__feature-card span:last-child {
            color: #64748b;
            font-size: 12px;
            line-height: 1.3;
        }

        .revision-hero__feature-card--purple .revision-hero__feature-icon {
            background: rgba(93, 63, 255, 0.1);
            color: #5d3fff;
        }

        .revision-hero__feature-card--green .revision-hero__feature-icon {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
        }

        .revision-hero__feature-card--amber .revision-hero__feature-icon {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .revision-hero__feature-card--blue .revision-hero__feature-icon {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .revision-hero__visual {
            position: relative;
            min-width: 0;
        }

        .revision-hero__visual-panel {
            position: relative;
            min-height: 440px;
            border-radius: 28px;
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0)),
                linear-gradient(135deg, rgba(93, 63, 255, 0.08), rgba(243, 244, 255, 0.96));
        }

        .revision-hero__image-wrap {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: stretch;
            justify-content: stretch;
        }

        .revision-hero__image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
        }

        .revision-hero__image-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 35% 30%, rgba(93, 63, 255, 0.2), transparent 25%),
                linear-gradient(135deg, rgba(15, 23, 42, 0.08), rgba(255, 255, 255, 0.2));
            color: #5d3fff;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.04em;
            text-transform: uppercase;
        }

        .revision-hero__assistant-card {
            position: absolute;
            right: 18px;
            bottom: 18px;
            z-index: 2;
            width: min(310px, calc(100% - 36px));
            padding: 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 20px 44px rgba(15, 23, 42, 0.16);
            backdrop-filter: blur(14px);
        }

        .revision-hero__assistant-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .revision-hero__assistant-head strong {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .revision-hero__assistant-head span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 26px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(93, 63, 255, 0.1);
            color: #5d3fff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .revision-hero__assistant-card p {
            margin: 12px 0 16px;
            color: #475569;
            font-size: 14px;
            line-height: 1.5;
        }

        .revision-hero__assistant-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            width: 100%;
            padding: 0 16px;
            border-radius: 14px;
            background: linear-gradient(135deg, #5d3fff, #6f39ff);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 14px 26px rgba(93, 63, 255, 0.24);
        }

        .revision-hero__assistant-button:hover {
            color: #fff;
        }

        @media (max-width: 1399.98px) {
            .revision-hero__shell {
                grid-template-columns: minmax(0, 1fr) minmax(300px, 0.88fr);
            }

            .revision-hero__title {
                max-width: 12ch;
            }

            .revision-hero__search {
                width: min(100%, 560px);
            }

            .revision-hero__feature-grid {
                gap: 12px;
            }
        }

        @media (max-width: 1199.98px) {
            .revision-hero {
                padding-top: 10px;
            }

            .revision-hero__shell {
                grid-template-columns: 1fr;
                gap: 18px;
                padding: 22px;
                border-radius: 24px;
            }

            .revision-hero__visual-panel {
                min-height: 360px;
            }

            .revision-hero__copy {
                max-width: none;
            }

            .revision-hero__feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .revision-hero__frame {
                padding-left: 14px;
                padding-right: 14px;
            }

            .revision-hero__shell {
                padding: 18px;
            }

            .revision-hero__eyebrow {
                margin-bottom: 14px;
                padding: 8px 12px;
                font-size: 12px;
            }

            .revision-hero__title {
                font-size: clamp(32px, 10vw, 42px);
                max-width: none;
            }

            .revision-hero__subtitle {
                margin-top: 14px;
                font-size: 16px;
            }

            .revision-hero__search {
                margin-top: 16px;
                width: 100%;
                height: 46px;
            }

            .revision-hero__search-category {
                flex-basis: 106px;
            }

            .revision-hero__search-category > i.fa-th-large {
                left: 10px;
                font-size: 12px;
            }

            .revision-hero__search-select {
                padding: 0 24px 0 30px;
                font-size: 12px;
            }

            .revision-hero__search-caret {
                right: 10px;
                font-size: 11px;
            }

            .revision-hero__search-field {
                height: 100%;
            }

            .revision-hero__search input {
                padding-right: 16px;
                padding-left: 38px;
                font-size: 14px;
            }

            .revision-hero__search-button {
                flex-basis: 40px;
                min-width: 40px;
                margin: 0 4px;
                font-size: 13px;
            }

            .revision-hero__feature-grid {
                grid-template-columns: 1fr;
                margin-top: 16px;
            }

            .revision-hero__visual-panel {
                min-height: 280px;
                border-radius: 22px;
            }

            .revision-hero__assistant-card {
                right: 12px;
                bottom: 12px;
                width: calc(100% - 24px);
                padding: 16px;
                border-radius: 18px;
            }
        }

        .revision-hero + .categories-area {
            padding-top: clamp(48px, 4vw, 72px);
        }
    </style>
@endpush
