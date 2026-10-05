@php
    $educationLevels = [
        [
            'label' => __('Primary School'),
            'meta' => __('PP1 - Grade 6'),
            'icon' => 'fa-school',
            'color' => '#5d3fff',
            'href' => route('catalog', ['main_category' => 'upper-primary']),
        ],
        [
            'label' => __('Junior Secondary'),
            'meta' => __('Grade 7 - Grade 9'),
            'icon' => 'fa-book-reader',
            'color' => '#16a34a',
            'href' => route('catalog', ['main_category' => 'junior-school']),
        ],
        [
            'label' => __('Senior Secondary'),
            'meta' => __('Form 1 - Form 4'),
            'icon' => 'fa-graduation-cap',
            'color' => '#f59e0b',
            'href' => route('catalog', ['main_category' => 'senior-school-cbc']),
        ],
        [
            'label' => __('KCSE'),
            'meta' => __('Exam Preparation'),
            'icon' => 'fa-file-alt',
            'color' => '#ef4444',
            'href' => route('catalog', ['main_category' => 'high-school']),
        ],
        [
            'label' => __('Tertiary'),
            'meta' => __('Colleges & University'),
            'icon' => 'fa-university',
            'color' => '#3b82f6',
            'href' => route('catalog', ['main_category' => 'undergraduate']),
        ],
        [
            'label' => __('Professional'),
            'meta' => __('KASNEB, CPA, ACCA'),
            'icon' => 'fa-briefcase',
            'color' => '#8b5cf6',
            'href' => route('catalog', ['main_category' => 'professional-courses']),
        ],
    ];

    $popularSubjects = [
        [
            'label' => __('Mathematics'),
            'count' => __('12,458 resources'),
            'icon' => 'fa-calculator',
            'color' => '#8b5cf6',
            'href' => route('catalog', ['search' => 'Mathematics']),
        ],
        [
            'label' => __('Biology'),
            'count' => __('9,842 resources'),
            'icon' => 'fa-leaf',
            'color' => '#22c55e',
            'href' => route('catalog', ['search' => 'Biology']),
        ],
        [
            'label' => __('Chemistry'),
            'count' => __('8,739 resources'),
            'icon' => 'fa-flask',
            'color' => '#ec4899',
            'href' => route('catalog', ['search' => 'Chemistry']),
        ],
        [
            'label' => __('Physics'),
            'count' => __('7,654 resources'),
            'icon' => 'fa-atom',
            'color' => '#3b82f6',
            'href' => route('catalog', ['search' => 'Physics']),
        ],
        [
            'label' => __('English'),
            'count' => __('6,987 resources'),
            'icon' => 'fa-book-open',
            'color' => '#f59e0b',
            'href' => route('catalog', ['search' => 'English']),
        ],
        [
            'label' => __('Kiswahili'),
            'count' => __('5,432 resources'),
            'icon' => 'fa-comment-dots',
            'color' => '#14b8a6',
            'href' => route('catalog', ['search' => 'Kiswahili']),
        ],
    ];

@endphp

<section class="revision-discovery">
    <div class="container">
        <div class="revision-discovery__panel">
            <div class="revision-discovery__header">
                <div>
                    <h2 class="revision-discovery__title">{{ __('Explore by Category') }}</h2>
                    <p class="revision-discovery__subtitle">{{ __('Browse all resources by curriculum level') }}</p>
                </div>

                <a href="{{ route('catalog', ['view' => 'levels']) }}" class="revision-discovery__view-all">
                    <span>{{ __('View all categories') }}</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="revision-discovery__levels">
                @foreach ($educationLevels as $level)
                    <a href="{{ $level['href'] }}" class="revision-discovery__level-card">
                        <span class="revision-discovery__level-icon" style="--level-accent: {{ $level['color'] }};">
                            <i class="fas {{ $level['icon'] }}"></i>
                        </span>
                        <span class="revision-discovery__level-copy">
                            <strong>{{ $level['label'] }}</strong>
                            <span>{{ $level['meta'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="revision-discovery__section">
                <div class="revision-discovery__section-head">
                    <div>
                        <h3 class="revision-discovery__section-title">{{ __('Popular Subjects') }}</h3>
                        <p class="revision-discovery__section-subtitle">{{ __('Top subjects students are studying') }}</p>
                    </div>

                    <a href="{{ route('catalog') }}" class="revision-discovery__text-link">
                        <span>{{ __('View all subjects') }}</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="revision-discovery__subjects">
                    @foreach ($popularSubjects as $subject)
                        <a href="{{ $subject['href'] }}" class="revision-discovery__subject-card">
                            <span class="revision-discovery__subject-icon" style="--subject-accent: {{ $subject['color'] }};">
                                <i class="fas {{ $subject['icon'] }}"></i>
                            </span>
                            <span class="revision-discovery__subject-copy">
                                <strong>{{ $subject['label'] }}</strong>
                                <span>{{ $subject['count'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>

@push('styles')
    <style>
        .revision-discovery {
            padding: 10px 0 22px;
        }

        .revision-discovery__panel {
            padding: clamp(22px, 3vw, 34px);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.06);
        }

        .revision-discovery__header,
        .revision-discovery__section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .revision-discovery__section-head {
            margin-top: 28px;
        }

        .revision-discovery__title {
            margin: 0;
            color: #111827;
            font-size: clamp(24px, 2.3vw, 34px);
            font-weight: 800;
            letter-spacing: -0.04em;
        }

        .revision-discovery__subtitle,
        .revision-discovery__section-subtitle {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.55;
        }

        .revision-discovery__view-all,
        .revision-discovery__text-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex: 0 0 auto;
            color: #5d3fff;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        .revision-discovery__levels {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }

        .revision-discovery__level-card {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 92px;
            padding: 14px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .revision-discovery__level-card:hover {
            transform: translateY(-2px);
            border-color: rgba(93, 63, 255, 0.18);
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
        }

        .revision-discovery__level-icon,
        .revision-discovery__subject-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: color-mix(in srgb, var(--level-accent, #5d3fff) 14%, #ffffff);
            color: var(--level-accent, #5d3fff);
            font-size: 18px;
        }

        .revision-discovery__level-copy,
        .revision-discovery__subject-copy {
            display: grid;
            gap: 3px;
            min-width: 0;
        }

        .revision-discovery__level-copy strong,
        .revision-discovery__subject-copy strong {
            color: #111827;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.2;
        }

        .revision-discovery__level-copy span,
        .revision-discovery__subject-copy span,
        .revision-discovery__recommend-meta {
            color: #64748b;
            font-size: 12px;
            line-height: 1.35;
        }

        .revision-discovery__section {
            margin-top: 26px;
        }

        .revision-discovery__section-title {
            margin: 0;
            color: #111827;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .revision-discovery__section-title--spark::before {
            content: "✦";
            margin-right: 8px;
            color: #5d3fff;
            font-size: 20px;
        }

        .revision-discovery__subjects {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .revision-discovery__subject-card {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 88px;
            padding: 14px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .revision-discovery__subject-card:hover {
            transform: translateY(-2px);
            border-color: rgba(93, 63, 255, 0.18);
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
        }

        .revision-discovery__subject-icon {
            background: color-mix(in srgb, var(--subject-accent, #5d3fff) 12%, #ffffff);
            color: var(--subject-accent, #5d3fff);
        }

        .revision-discovery__recommend-nav {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex: 0 0 auto;
        }

        .revision-discovery__arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 999px;
            background: rgba(93, 63, 255, 0.08);
            color: #5d3fff;
            font-size: 14px;
            transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }

        .revision-discovery__arrow--next {
            background: linear-gradient(135deg, #5d3fff, #6f39ff);
            color: #fff;
        }

        .revision-discovery__arrow:hover {
            transform: translateY(-1px);
        }

        .revision-discovery__recommend-rail {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(240px, 1fr);
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 6px;
            margin-top: 18px;
            scroll-snap-type: x proximity;
            scrollbar-width: none;
        }

        .revision-discovery__recommend-rail::-webkit-scrollbar {
            display: none;
        }

        .revision-discovery__recommend-card {
            display: grid;
            gap: 12px;
            padding: 14px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.04);
            scroll-snap-align: start;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .revision-discovery__recommend-card:hover {
            transform: translateY(-2px);
            border-color: rgba(93, 63, 255, 0.18);
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
        }

        .revision-discovery__recommend-thumb {
            overflow: hidden;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(93, 63, 255, 0.08), rgba(255, 255, 255, 0.96));
            aspect-ratio: 16 / 9;
        }

        .revision-discovery__recommend-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .revision-discovery__recommend-thumb-fallback {
            display: grid;
            place-items: center;
            width: 100%;
            height: 100%;
            color: #5d3fff;
            font-size: 28px;
        }

        .revision-discovery__recommend-copy {
            display: grid;
            gap: 4px;
        }

        .revision-discovery__recommend-tag {
            color: #5d3fff;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .revision-discovery__recommend-copy strong {
            color: #111827;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.35;
        }

        .revision-discovery__recommend-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .revision-discovery__recommend-rating {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #f59e0b;
            font-size: 13px;
            font-weight: 800;
        }

        .revision-discovery__recommend-cta {
            color: #5d3fff;
            font-size: 13px;
            font-weight: 800;
        }

        .revision-discovery__recommend-empty {
            display: grid;
            gap: 6px;
            padding: 22px;
            border-radius: 18px;
            background: rgba(93, 63, 255, 0.05);
            border: 1px dashed rgba(93, 63, 255, 0.18);
            color: #334155;
        }

        @media (max-width: 1399.98px) {
            .revision-discovery__levels,
            .revision-discovery__subjects {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 1199.98px) {
            .revision-discovery__header,
            .revision-discovery__section-head {
                flex-direction: column;
            }

            .revision-discovery__levels,
            .revision-discovery__subjects {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .revision-discovery__recommend-rail {
                grid-auto-columns: minmax(220px, 78vw);
            }
        }

        @media (max-width: 767.98px) {
            .revision-discovery {
                padding: 8px 0 18px;
            }

            .revision-discovery__panel {
                padding: 18px;
                border-radius: 22px;
            }

            .revision-discovery__levels,
            .revision-discovery__subjects {
                grid-template-columns: 1fr;
            }

            .revision-discovery__level-card,
            .revision-discovery__subject-card {
                min-height: 82px;
            }

            .revision-discovery__section {
                margin-top: 22px;
            }

            .revision-discovery__recommend-rail {
                grid-auto-columns: minmax(220px, 82vw);
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rail = document.querySelector('[data-recommended-rail]');
            const prev = document.querySelector('[data-recommended-prev]');
            const next = document.querySelector('[data-recommended-next]');

            if (!rail || !prev || !next) {
                return;
            }

            const step = () => Math.max(rail.clientWidth * 0.8, 260);

            prev.addEventListener('click', function () {
                rail.scrollBy({ left: -step(), behavior: 'smooth' });
            });

            next.addEventListener('click', function () {
                rail.scrollBy({ left: step(), behavior: 'smooth' });
            });
        });
    </script>
@endpush
