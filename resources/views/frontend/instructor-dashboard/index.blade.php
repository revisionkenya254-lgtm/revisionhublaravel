@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $trendLabel = function (float $value, bool $decimal = false): string {
            $prefix = $value > 0 ? '+' : '';
            $formatted = $decimal ? number_format($value, 1) : number_format($value, 0);

            return $prefix . $formatted;
        };

        $metricCards = [
            [
                'title' => __('Total Products'),
                'value' => number_format($totalProducts),
                'icon' => 'fas fa-book-open',
                'tone' => 'violet',
                'trend' => $trendLabel($contentChange),
                'trend_label' => __('this month'),
            ],
            [
                'title' => __('Total Sales'),
                'value' => number_format($totalSales),
                'icon' => 'fas fa-chart-line',
                'tone' => 'green',
                'trend' => $trendLabel($salesChange) . '%',
                'trend_label' => __('this month'),
            ],
            [
                'title' => __('Total Earnings'),
                'value' => defaultCurrency($totalRevenue),
                'icon' => 'fas fa-coins',
                'tone' => 'amber',
                'trend' => $trendLabel($earningsChange) . '%',
                'trend_label' => __('this month'),
            ],
            [
                'title' => __('Total Students'),
                'value' => number_format($totalStudents),
                'icon' => 'fas fa-user-graduate',
                'tone' => 'blue',
                'trend' => $trendLabel($studentChange) . '%',
                'trend_label' => __('this month'),
            ],
            [
                'title' => __('Average Rating'),
                'value' => number_format($averageRating, 1) . '/5',
                'icon' => 'fas fa-star',
                'tone' => 'rose',
                'trend' => ($ratingChange >= 0 ? '+' : '') . number_format($ratingChange, 1),
                'trend_label' => __('this month'),
            ],
        ];

        $summaryTiles = [
            ['label' => __('Total Earnings'), 'value' => defaultCurrency($totalRevenue)],
            ['label' => __('This Month'), 'value' => defaultCurrency($currentMonthEarnings)],
            ['label' => __('Last Month'), 'value' => defaultCurrency($previousMonthEarnings)],
            ['label' => __('Growth'), 'value' => ($earningsChange >= 0 ? '+' : '') . number_format($earningsChange, 1) . '%'],
        ];

        $donutSegments = [];
        $start = 0;
        foreach ($contentBreakdown as $item) {
            $end = $start + $item->percent;
            $donutSegments[] = "{$item->color} {$start}% {$end}%";
            $start = $end;
        }
        $donutBackground = !empty($donutSegments)
            ? 'background: conic-gradient(' . implode(', ', $donutSegments) . ');'
            : 'background: conic-gradient(#5b8def 0 100%);';

        $axisLabels = collect($earningsChart['points'])
            ->map(fn ($point, $index) => [$index, $point])
            ->filter(fn ($pair) => $pair[0] % 5 === 0 || $pair[0] === count($earningsChart['points']) - 1)
            ->values();
    @endphp

    <div class="dashboard-hero">
        <div class="dashboard-hero__copy">
            <p class="dashboard-hero__eyebrow">{{ __('Instructor Overview') }}</p>
            <h2 class="dashboard-hero__title">{{ __('Welcome back, :name!', ['name' => userAuth()->name]) }} <span>&#128075;</span></h2>
            <p class="dashboard-hero__text">{{ __('Here is a live snapshot of your content, students, sales, and earnings.') }}</p>
        </div>

        <div class="dashboard-hero__actions">
            <div class="dropdown">
                <button class="dashboard-create-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-plus"></i>
                    <span>{{ __('Create New Product') }}</span>
                </button>
                <ul class="dropdown-menu dashboard-create-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('instructor.courses.create') }}">{{ __('New Video Lesson') }}</a></li>
                    <li><a class="dropdown-item" href="{{ route('instructor.products.create', ['type' => 'past_paper']) }}">{{ __('New Past Paper') }}</a></li>
                    <li><a class="dropdown-item" href="{{ route('instructor.products.create', ['type' => 'prediction']) }}">{{ __('New Prediction') }}</a></li>
                    <li><a class="dropdown-item" href="{{ route('instructor.products.create', ['type' => 'note']) }}">{{ __('New Note') }}</a></li>
                    <li><a class="dropdown-item" href="{{ route('instructor.products.create', ['type' => 'quiz']) }}">{{ __('New Quiz') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="dashboard-stats-grid">
        @foreach ($metricCards as $card)
            <article class="dashboard-stat-card dashboard-stat-card--{{ $card['tone'] }}">
                <div class="dashboard-stat-card__icon">
                    <i class="{{ $card['icon'] }}"></i>
                </div>
                <div class="dashboard-stat-card__content">
                    <span class="dashboard-stat-card__title">{{ $card['title'] }}</span>
                    <strong class="dashboard-stat-card__value">{{ $card['value'] }}</strong>
                    <span class="dashboard-stat-card__trend">{{ $card['trend'] }} <small>{{ $card['trend_label'] }}</small></span>
                </div>
            </article>
        @endforeach
    </div>

    <div class="dashboard-grid dashboard-grid--analytics">
        <section class="dashboard-panel dashboard-panel--chart">
            <div class="dashboard-panel__header">
                <div>
                    <h3>{{ __('Earnings Overview') }}</h3>
                    <p>{{ __('Daily earnings for this month') }}</p>
                </div>

                <span class="dashboard-chip">
                    {{ __('This Month') }}
                    <i class="fas fa-chevron-down"></i>
                </span>
            </div>

            <div class="dashboard-chart">
                <svg viewBox="0 0 {{ $earningsChart['width'] }} {{ $earningsChart['height'] }}" role="img" aria-label="{{ __('Earnings line chart') }}">
                    <defs>
                        <linearGradient id="earningsGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#5f57d6" stop-opacity="0.28"></stop>
                            <stop offset="100%" stop-color="#5f57d6" stop-opacity="0.02"></stop>
                        </linearGradient>
                    </defs>

                    @foreach ($earningsChart['ticks'] as $tick)
                        <g>
                            <line x1="44" y1="{{ $tick['y'] }}" x2="{{ $earningsChart['width'] - 20 }}" y2="{{ $tick['y'] }}"></line>
                            <text x="0" y="{{ $tick['y'] + 4 }}">{{ defaultCurrency($tick['label']) }}</text>
                        </g>
                    @endforeach

                    @if (!empty($earningsChart['areaPath']))
                        <path class="dashboard-chart__area" d="{{ $earningsChart['areaPath'] }}"></path>
                    @endif

                    @if (!empty($earningsChart['path']))
                        <path class="dashboard-chart__line" d="{{ $earningsChart['path'] }}"></path>
                    @endif

                    @foreach ($earningsChart['points'] as $point)
                        <circle class="dashboard-chart__point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4"></circle>
                    @endforeach

                    @foreach ($axisLabels as [$index, $point])
                        @php
                            $label = \Carbon\Carbon::createFromDate(now()->year, now()->month, $index + 1)->format('j M');
                        @endphp
                        <text class="dashboard-chart__axis-label" x="{{ $point['x'] }}" y="{{ $earningsChart['height'] - 10 }}">{{ $label }}</text>
                    @endforeach
                </svg>
            </div>

            <div class="dashboard-summary-grid">
                @foreach ($summaryTiles as $tile)
                    <div class="dashboard-summary-tile">
                        <span>{{ $tile['label'] }}</span>
                        <strong>{{ $tile['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="dashboard-panel dashboard-panel--products">
            <div class="dashboard-panel__header">
                <div>
                    <h3>{{ __('Top Performing Products') }}</h3>
                    <p>{{ __('Content generating the most sales and earnings') }}</p>
                </div>
                <a href="{{ route('instructor.my-sells.index') }}" class="dashboard-link">{{ __('View all') }}</a>
            </div>

            <div class="dashboard-ranking-list dashboard-scroll-list">
                @forelse ($topProducts as $item)
                    <a href="{{ $item->url }}" class="dashboard-ranking-item">
                        <span class="dashboard-ranking-item__rank">{{ $item->rank }}</span>
                        <span class="dashboard-ranking-item__thumb">
                            <img src="{{ asset($item->thumbnail ?: 'uploads/website-images/course_bundle.svg') }}" alt="{{ $item->title }}">
                        </span>
                        <span class="dashboard-ranking-item__content">
                            <strong>{{ $item->title }}</strong>
                            <small>{{ $item->subtitle }}</small>
                        </span>
                        <span class="dashboard-ranking-item__metric">
                            <small>{{ __('Sales') }}</small>
                            <strong>{{ number_format($item->sales) }}</strong>
                        </span>
                        <span class="dashboard-ranking-item__metric dashboard-ranking-item__metric--right">
                            <small>{{ __('Earnings') }}</small>
                            <strong>{{ defaultCurrency($item->earnings) }}</strong>
                        </span>
                    </a>
                @empty
                    <div class="dashboard-empty-state">
                        {{ __('No paid items yet. Your top performers will appear here once students start buying.') }}
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="dashboard-grid dashboard-grid--bottom">
        <section class="dashboard-panel dashboard-panel--table">
            <div class="dashboard-panel__header">
                <div>
                    <h3>{{ __('Recent Orders') }}</h3>
                    <p>{{ __('Latest paid purchases across your content') }}</p>
                </div>
                <a href="{{ route('instructor.my-sells.index') }}" class="dashboard-link">{{ __('View all') }}</a>
            </div>

            <div class="dashboard-table">
                <div class="dashboard-table__head">
                    <span>{{ __('Invoice') }}</span>
                    <span>{{ __('Student') }}</span>
                    <span>{{ __('Product') }}</span>
                    <span>{{ __('Amount') }}</span>
                    <span>{{ __('Date') }}</span>
                    <span>{{ __('Status') }}</span>
                </div>

                @forelse ($recentOrders as $order)
                    <a href="{{ $order->url }}" class="dashboard-table__row">
                        <span class="dashboard-table__invoice">#{{ $order->invoice }}</span>
                        <span class="dashboard-table__student">
                            <span class="dashboard-table__avatar">
                                <img src="{{ asset($order->student_image ?: 'uploads/website-images/avatar.png') }}" alt="{{ $order->student }}">
                            </span>
                            {{ $order->student }}
                        </span>
                        <span class="dashboard-table__product">
                            <strong>{{ $order->title }}</strong>
                            <small>{{ $order->subtitle }}</small>
                        </span>
                        <span class="dashboard-table__amount">{{ defaultCurrency($order->amount) }}</span>
                        <span class="dashboard-table__date">{{ $order->date?->format('d M Y') }}</span>
                        <span class="dashboard-table__status dashboard-table__status--{{ $order->status }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </a>
                @empty
                    <div class="dashboard-empty-state">
                        {{ __('No recent paid orders yet.') }}
                    </div>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel dashboard-panel--donut">
            <div class="dashboard-panel__header">
                <div>
                    <h3>{{ __('Product Breakdown') }}</h3>
                    <p>{{ __('Courses and product types in your catalog') }}</p>
                </div>
                <a href="{{ route('instructor.courses.index') }}" class="dashboard-link">{{ __('View all') }}</a>
            </div>

            <div class="dashboard-donut">
                <div class="dashboard-donut__ring" style="{{ $donutBackground }}">
                    <div class="dashboard-donut__center">
                        <strong>{{ number_format($totalProducts) }}</strong>
                        <span>{{ __('Total Products') }}</span>
                    </div>
                </div>

                <div class="dashboard-legend dashboard-scroll-list dashboard-scroll-list--legend">
                    @forelse ($contentBreakdown as $item)
                        <div class="dashboard-legend__item">
                            <span class="dashboard-legend__dot" style="background: {{ $item->color }}"></span>
                            <div>
                                <strong>{{ $item->label }}</strong>
                                <small>{{ number_format($item->count) }} ({{ $item->percent }}%)</small>
                            </div>
                        </div>
                    @empty
                        <div class="dashboard-empty-state">
                            {{ __('Add courses or products to populate this chart.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="dashboard-panel dashboard-panel--levels">
            <div class="dashboard-panel__header">
                <div>
                    <h3>{{ __('Students By Level') }}</h3>
                    <p>{{ __('Unique enrolled students grouped by course level') }}</p>
                </div>
                <a href="{{ route('instructor.courses.index') }}" class="dashboard-link">{{ __('View all') }}</a>
            </div>

            <div class="dashboard-level-list">
                @forelse ($studentsByLevel as $item)
                    <div class="dashboard-level-item">
                        <div class="dashboard-level-item__top">
                            <strong>{{ $item->label }}</strong>
                            <span>{{ number_format($item->count) }} ({{ $item->percent }}%)</span>
                        </div>
                        <div class="dashboard-level-item__bar">
                            <span style="width: {{ min(max($item->percent, 6), 100) }}%"></span>
                        </div>
                    </div>
                @empty
                    <div class="dashboard-empty-state">
                        {{ __('Enrolled students will appear here once your courses start collecting enrollments.') }}
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .dashboard-hero__eyebrow {
            margin: 0 0 8px;
            color: #73809b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .dashboard-hero__title {
            margin: 0;
            color: #16213f;
            font-size: clamp(24px, 3vw, 32px);
            line-height: 1.1;
        }

        .dashboard-hero__text {
            max-width: 720px;
            margin: 10px 0 0;
            color: #73809b;
            font-size: 15px;
        }

        .dashboard-create-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border: 0;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(135deg, #5b8def, #6a4cff);
            box-shadow: 0 14px 30px rgba(91, 141, 239, 0.28);
            font-weight: 700;
        }

        .dashboard-create-button::after {
            margin-left: 4px;
        }

        .dashboard-create-menu {
            border: 1px solid #e2e8f3;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(20, 33, 61, 0.12);
            overflow: hidden;
        }

        .dashboard-create-menu .dropdown-item {
            padding: 10px 16px;
        }

        .dashboard-stats-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .dashboard-stat-card {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 18px;
            border: 1px solid rgba(98, 117, 157, 0.14);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 16px 35px rgba(20, 33, 61, 0.05);
        }

        .dashboard-stat-card__icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            font-size: 20px;
        }

        .dashboard-stat-card__content {
            min-width: 0;
        }

        .dashboard-stat-card__title {
            display: block;
            color: #73809b;
            font-size: 13px;
            font-weight: 600;
        }

        .dashboard-stat-card__value {
            display: block;
            margin-top: 4px;
            color: #16213f;
            font-size: 24px;
            line-height: 1.1;
        }

        .dashboard-stat-card__trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 8px;
            color: #16a34a;
            font-size: 13px;
            font-weight: 700;
        }

        .dashboard-stat-card__trend small {
            color: #73809b;
            font-weight: 500;
        }

        .dashboard-stat-card--violet .dashboard-stat-card__icon {
            color: #5b8def;
            background: rgba(91, 141, 239, 0.14);
        }

        .dashboard-stat-card--green .dashboard-stat-card__icon {
            color: #16a34a;
            background: rgba(53, 199, 138, 0.14);
        }

        .dashboard-stat-card--amber .dashboard-stat-card__icon {
            color: #e19a1d;
            background: rgba(246, 185, 59, 0.18);
        }

        .dashboard-stat-card--blue .dashboard-stat-card__icon {
            color: #246bff;
            background: rgba(91, 141, 239, 0.14);
        }

        .dashboard-stat-card--rose .dashboard-stat-card__icon {
            color: #e03d67;
            background: rgba(237, 109, 141, 0.14);
        }

        .dashboard-grid {
            display: grid;
            gap: 18px;
        }

        .dashboard-grid--analytics {
            grid-template-columns: minmax(0, 1.4fr) minmax(0, 0.95fr);
            margin-bottom: 18px;
        }

        .dashboard-grid--bottom {
            grid-template-columns: minmax(0, 1.35fr) minmax(0, 0.85fr) minmax(0, 0.8fr);
        }

        .dashboard-panel {
            padding: 18px;
            border: 1px solid rgba(98, 117, 157, 0.14);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 16px 35px rgba(20, 33, 61, 0.05);
        }

        .dashboard-panel__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .dashboard-panel__header h3 {
            margin: 0;
            color: #16213f;
            font-size: 18px;
            line-height: 1.2;
        }

        .dashboard-panel__header p {
            margin: 6px 0 0;
            color: #73809b;
            font-size: 13px;
        }

        .dashboard-chip,
        .dashboard-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        .dashboard-chip {
            padding: 8px 12px;
            border-radius: 12px;
            border: 1px solid #e2e8f3;
            color: #24304f;
            background: #fff;
        }

        .dashboard-link {
            color: #5b8def;
        }

        .dashboard-chart svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .dashboard-chart line {
            stroke: #e9edf5;
            stroke-width: 1;
        }

        .dashboard-chart text {
            fill: #8a94ad;
            font-size: 12px;
        }

        .dashboard-chart__line {
            fill: none;
            stroke: #5f57d6;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .dashboard-chart__area {
            fill: url(#earningsGradient);
            opacity: 0.8;
        }

        .dashboard-chart__point {
            fill: #5f57d6;
            stroke: #fff;
            stroke-width: 3;
        }

        .dashboard-chart__axis-label {
            text-anchor: middle;
        }

        .dashboard-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .dashboard-summary-tile {
            padding: 14px;
            border-radius: 16px;
            background: linear-gradient(180deg, #fff, #f7f9ff);
            border: 1px solid #e8edf7;
        }

        .dashboard-summary-tile span {
            display: block;
            color: #73809b;
            font-size: 12px;
        }

        .dashboard-summary-tile strong {
            display: block;
            margin-top: 6px;
            color: #16213f;
            font-size: 16px;
        }

        .dashboard-ranking-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .dashboard-scroll-list {
            min-height: 0;
            max-height: clamp(220px, calc(100vh - 520px), 420px);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
            scrollbar-gutter: stable;
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

        .dashboard-scroll-list--legend {
            max-height: clamp(180px, calc(100vh - 640px), 260px);
        }

        .dashboard-ranking-item {
            display: grid;
            grid-template-columns: auto auto minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-top: 1px solid #edf1f7;
            color: inherit;
        }

        .dashboard-ranking-item:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .dashboard-ranking-item__rank {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #f4f6fb;
            color: #5f57d6;
            font-weight: 700;
        }

        .dashboard-ranking-item__thumb {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            overflow: hidden;
            background: #eff3fa;
            flex: 0 0 auto;
        }

        .dashboard-ranking-item__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dashboard-ranking-item__content {
            min-width: 0;
        }

        .dashboard-ranking-item__content strong {
            display: block;
            color: #16213f;
            font-size: 14px;
        }

        .dashboard-ranking-item__content small {
            color: #73809b;
            font-size: 12px;
        }

        .dashboard-ranking-item__metric {
            text-align: right;
        }

        .dashboard-ranking-item__metric small {
            display: block;
            color: #73809b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .dashboard-ranking-item__metric strong {
            display: block;
            margin-top: 2px;
            color: #16213f;
            font-size: 14px;
        }

        .dashboard-empty-state {
            padding: 22px;
            border-radius: 16px;
            background: #f7f9ff;
            color: #73809b;
            font-size: 14px;
        }

        .dashboard-table {
            display: grid;
            gap: 8px;
        }

        .dashboard-table__head,
        .dashboard-table__row {
            display: grid;
            grid-template-columns: 0.75fr 1fr 1fr 0.7fr 0.8fr 0.6fr;
            align-items: center;
            gap: 12px;
        }

        .dashboard-table__head {
            padding: 0 0 10px;
            color: #73809b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .dashboard-table__row {
            padding: 12px 0;
            border-top: 1px solid #edf1f7;
            color: inherit;
        }

        .dashboard-table__invoice {
            color: #5f57d6;
            font-weight: 700;
        }

        .dashboard-table__student {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            color: #24304f;
            font-weight: 600;
        }

        .dashboard-table__avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            overflow: hidden;
            background: #eff3fa;
            flex: 0 0 auto;
        }

        .dashboard-table__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dashboard-table__product strong,
        .dashboard-table__product small {
            display: block;
        }

        .dashboard-table__product strong {
            color: #16213f;
            font-size: 14px;
        }

        .dashboard-table__product small {
            color: #73809b;
            font-size: 12px;
        }

        .dashboard-table__amount,
        .dashboard-table__date {
            color: #24304f;
            font-size: 14px;
        }

        .dashboard-table__status {
            display: inline-flex;
            justify-content: center;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .dashboard-table__status--completed {
            color: #15803d;
            background: #e8f8ef;
        }

        .dashboard-table__status--processing {
            color: #b45309;
            background: #fff4de;
        }

        .dashboard-table__status--pending {
            color: #b45309;
            background: #fff4de;
        }

        .dashboard-table__status--declined {
            color: #b91c1c;
            background: #fdecec;
        }

        .dashboard-donut {
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            gap: 18px;
            align-items: center;
        }

        .dashboard-donut__ring {
            position: relative;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            margin: 0 auto;
            padding: 18px;
            box-shadow: inset 0 0 0 1px #edf1f7;
        }

        .dashboard-donut__ring::before {
            content: "";
            position: absolute;
            inset: 22px;
            border-radius: 50%;
            background: #fff;
            box-shadow: inset 0 0 0 1px #eef2f8;
        }

        .dashboard-donut__center {
            position: absolute;
            inset: 22px;
            z-index: 1;
            display: grid;
            place-items: center;
            text-align: center;
        }

        .dashboard-donut__center strong {
            color: #16213f;
            font-size: 34px;
            line-height: 1;
        }

        .dashboard-donut__center span {
            color: #73809b;
            font-size: 13px;
            font-weight: 600;
        }

        .dashboard-legend {
            display: grid;
            gap: 14px;
        }

        .dashboard-legend__item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dashboard-legend__dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex: 0 0 auto;
        }

        .dashboard-legend__item strong {
            display: block;
            color: #16213f;
            font-size: 14px;
        }

        .dashboard-legend__item small {
            color: #73809b;
            font-size: 12px;
        }

        .dashboard-level-list {
            display: grid;
            gap: 16px;
        }

        .dashboard-level-item__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .dashboard-level-item__top strong {
            color: #16213f;
            font-size: 14px;
        }

        .dashboard-level-item__top span {
            color: #73809b;
            font-size: 12px;
            font-weight: 600;
        }

        .dashboard-level-item__bar {
            height: 10px;
            border-radius: 999px;
            overflow: hidden;
            background: #edf1f7;
        }

        .dashboard-level-item__bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #6a4cff, #5b8def);
        }

        @media (max-width: 1399.98px) {
            .dashboard-stats-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .dashboard-grid--analytics {
                grid-template-columns: 1fr;
            }

            .dashboard-grid--bottom {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-hero {
                flex-direction: column;
            }

            .dashboard-stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-panel__header,
            .dashboard-ranking-item,
            .dashboard-table__head,
            .dashboard-table__row {
                grid-template-columns: 1fr;
            }

            .dashboard-ranking-item {
                gap: 8px;
            }

            .dashboard-ranking-item__metric {
                text-align: left;
            }

            .dashboard-donut {
                grid-template-columns: 1fr;
            }

            .dashboard-donut__ring {
                width: 200px;
                height: 200px;
            }

            .dashboard-scroll-list,
            .dashboard-scroll-list--legend {
                max-height: none;
                padding-right: 0;
                overflow: visible;
            }

            .dashboard-table__head {
                display: none;
            }

            .dashboard-table__row {
                padding: 14px 0;
            }
        }
    </style>
@endpush
