@extends('frontend.layouts.master')
@section('meta_title', $product->title . ' || ' . $setting->app_name)
@section('body_class', 'product-preview-page')

@php
    use Illuminate\Support\Str;

    $formatPreviewPrice = static function (float|int|string $price): string {
        $formatted = number_format((float) $price, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return 'KSh ' . $formatted;
    };

    $priceValue = (float) $product->effective_price;
    $displayPrice = $priceValue <= 0
        ? __('Free')
        : ($product->discount !== null && (float) $product->discount > 0
            ? $formatPreviewPrice($product->discount)
            : $formatPreviewPrice($product->price));
    $reviewCount = (int) ($ratingData['total'] ?? 0);
    $averageRating = (float) ($ratingData['average'] ?? 0);
    $relatedItems = collect($relatedProducts ?? []);
    $previewPages = collect($preview['pages'] ?? []);
    $coverTitle = strtoupper(str_replace('_', ' ', (string) $product->type_label));
    $metadata = $product->metadata ?? [];
    $previewBadge = $preview['label'] ?? __('Sample preview');
    $actionPreviewUrl = route('product.preview', $product->slug);
@endphp

@push('styles')
    <style>
        .product-preview-page {
            background: #ffffff;
        }

        .product-preview-hero {
            padding: 8px 0 0;
        }

        .product-preview-breadcrumb {
            margin-bottom: 10px;
        }

        .product-preview-shell {
            display: grid;
            grid-template-columns: minmax(0, 1.9fr) minmax(320px, 0.78fr);
            gap: 18px;
            align-items: start;
        }

        .product-preview-content {
            min-width: 0;
        }

        .product-preview-main,
        .product-preview-sidebar,
        .product-preview-panel,
        .product-preview-section {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .product-preview-main {
            padding: 16px;
        }

        .product-preview-hero-grid {
            display: grid;
            grid-template-columns: 210px minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }

        .product-preview-cover {
            min-height: 300px;
            border-radius: 18px;
            padding: 16px;
            color: #fff;
            background:
                radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.16), transparent 26%),
                radial-gradient(circle at 80% 85%, rgba(255, 255, 255, 0.12), transparent 20%),
                linear-gradient(160deg, #7e3af2 0%, #6038f6 56%, #4c35e3 100%);
            position: relative;
            overflow: hidden;
        }

        .product-preview-cover::after {
            content: '';
            position: absolute;
            inset: auto -22% -22% auto;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.26);
            opacity: 0.8;
        }

        .product-preview-cover__tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .product-preview-cover__title {
            margin: 52px 0 0;
            font-size: 27px;
            line-height: 1.02;
            letter-spacing: -0.04em;
            font-weight: 800;
        }

        .product-preview-cover__year {
            margin-top: 10px;
            font-size: 14px;
            font-weight: 700;
            opacity: 0.98;
        }

        .product-preview-cover__icon {
            margin-top: 22px;
            display: grid;
            place-items: center;
            width: 68px;
            height: 68px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
            font-size: 24px;
        }

        .product-preview-copy {
            padding-top: 4px;
        }

        .product-preview-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(91, 55, 255, 0.1);
            color: #5b37ff;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .product-preview-title {
            margin: 8px 0 0;
            font-size: clamp(24px, 2.6vw, 34px);
            line-height: 1.05;
            letter-spacing: -0.04em;
            color: #111827;
        }

        .product-preview-rating-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            color: #6b7280;
            font-weight: 500;
            font-size: 12px;
        }

        .product-preview-stars {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            color: #f59e0b;
        }

        .product-preview-stars .is-muted {
            color: #fbbf24;
            opacity: 0.28;
        }

        .product-preview-desc {
            margin: 10px 0 0;
            color: #374151;
            font-size: 14px;
            line-height: 1.65;
            max-width: 72ch;
        }

        .product-preview-metrics {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .product-preview-metric {
            padding: 12px 12px 11px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
        }

        .product-preview-metric span {
            display: block;
        }

        .product-preview-metric__label {
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 700;
        }

        .product-preview-metric__value {
            margin-top: 6px;
            color: #111827;
            font-size: 15px;
            font-weight: 800;
        }

        .product-preview-assurance-grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .product-preview-assurance-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        .product-preview-assurance-card__icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(16, 185, 129, 0.12);
            color: #15803d;
            flex: 0 0 auto;
        }

        .product-preview-assurance-card__icon--purple {
            background: rgba(91, 55, 255, 0.12);
            color: #5b37ff;
        }

        .product-preview-assurance-card__title {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            color: #111827;
        }

        .product-preview-assurance-card__text {
            margin: 2px 0 0;
            font-size: 13px;
            color: #6b7280;
        }

        .product-preview-actions {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .product-preview-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 16px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .product-preview-action-btn--primary {
            background: linear-gradient(135deg, #6d4cff, #5b37ff);
            color: #fff;
            box-shadow: 0 14px 24px rgba(93, 59, 255, 0.2);
        }

        .product-preview-action-btn--outline {
            background: #fff;
            color: #5b37ff;
            border: 1px solid #d6d3ff;
        }

        .product-preview-action-btn--buy-now {
            width: 100%;
        }

        .product-preview-chip-row {
            display: none;
        }

        .product-preview-grid {
            margin-top: 16px;
            display: block;
        }

        .product-preview-panel {
            padding: 18px;
        }

        .product-preview-panel__tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .product-preview-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #111827;
            font-weight: 700;
            text-decoration: none;
        }

        .product-preview-tab.is-active {
            background: rgba(91, 55, 255, 0.08);
            color: #5b37ff;
            border-color: #d6d3ff;
        }

        .product-preview-overview {
            display: grid;
            gap: 14px;
        }

        .product-preview-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(290px, 0.9fr);
            gap: 16px;
            align-items: start;
        }

        .product-preview-copy-block {
            padding: 16px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
        }

        .product-preview-copy-block h3 {
            margin: 0 0 10px;
            font-size: 17px;
            color: #111827;
        }

        .product-preview-copy-block p {
            margin: 0;
            color: #374151;
            line-height: 1.7;
        }

        .product-preview-previewbox {
            padding: 16px;
            border-radius: 16px;
            background:
                radial-gradient(circle at top left, rgba(91, 55, 255, 0.06), transparent 32%),
                linear-gradient(180deg, #ffffff, #fbfbfd);
            border: 1px solid #e5e7eb;
        }

        .product-preview-previewbox__label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(91, 55, 255, 0.08);
            color: #5b37ff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .product-preview-previewbox__text {
            margin-top: 14px;
            color: #111827;
            line-height: 1.8;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .product-preview-outline-card {
            padding: 16px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: #fff;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
        }

        .product-preview-outline-card__title {
            margin: 0 0 14px;
            font-size: 16px;
            font-weight: 800;
            color: #111827;
        }

        .product-preview-outline-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
        }

        .product-preview-outline-item {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            color: #374151;
            font-size: 13px;
        }

        .product-preview-outline-item strong {
            color: #111827;
            font-size: 14px;
        }

        .product-preview-outline-total {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #374151;
            font-size: 13px;
            font-weight: 700;
        }

        .product-preview-section {
            margin-top: 16px;
            padding: 18px;
        }

        .product-preview-section__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .product-preview-section__head h3 {
            margin: 0;
            font-size: 18px;
            color: #111827;
        }

        .product-preview-related {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .product-preview-related-card {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 13px;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
            text-decoration: none;
            color: inherit;
            display: grid;
            gap: 10px;
        }

        .product-preview-related-card__media {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .product-preview-related-card__thumb {
            width: 46px;
            height: 66px;
            border-radius: 12px;
            object-fit: cover;
            background: #f8fafc;
            flex: 0 0 auto;
        }

        .product-preview-related-card__title {
            margin: 0;
            font-size: 15px;
            line-height: 1.35;
        }

        .product-preview-related-card__meta {
            color: #6b7280;
            font-size: 12px;
            margin-top: 4px;
        }

        .product-preview-related-card__footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .product-preview-related-card__price {
            color: #15803d;
            font-weight: 800;
        }

        .product-preview-sidebar {
            padding: 0;
            position: sticky;
            top: 0;
            align-self: start;
            width: 100%;
        }

        .product-preview-box {
            border-radius: 16px;
            padding: 15px;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        .product-preview-box + .product-preview-box {
            margin-top: 12px;
        }

        .product-preview-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 14px;
            border-radius: 14px;
            background: #f0fdf4;
            border: 1px solid #d1fae5;
            color: #166534;
            font-weight: 700;
        }

        .product-preview-status--locked {
            background: #fff;
            color: #111827;
            border-color: #e5e7eb;
        }

        .product-preview-status__price {
            margin: 12px 0 12px;
            color: #15803d;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .product-preview-feature-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .product-preview-feature-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: #374151;
            line-height: 1.6;
        }

        .product-preview-feature-list i {
            margin-top: 3px;
            color: #5b37ff;
        }

        .product-preview-review-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .product-preview-review-card {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: #fff;
            padding: 14px;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
            height: 100%;
        }

        .product-preview-review-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .product-preview-review-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            background: #f8fafc;
        }

        .product-preview-review-name {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
        }

        .product-preview-review-role {
            margin: 2px 0 0;
            color: #6b7280;
            font-size: 12px;
        }

        .product-preview-review-text {
            color: #374151;
            line-height: 1.72;
            margin: 8px 0 0;
        }

        .product-preview-footer-note {
            margin-top: 12px;
            color: #6b7280;
            font-size: 14px;
        }

        @media (max-width: 1199px) {
            .product-preview-related {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .product-preview-review-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991px) {
            .product-preview-shell,
            .product-preview-grid,
            .product-preview-hero-grid {
                grid-template-columns: 1fr;
            }

            .product-preview-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .product-preview-sidebar {
                position: static;
            }
        }

        @media (max-width: 767px) {
            .product-preview-page .tg-header__top {
                display: none;
            }

            .product-preview-page .revision-header {
                box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
            }

            .product-preview-page .revision-header__frame {
                padding-top: 8px;
                padding-bottom: 8px;
            }

            .product-preview-page .breadcrumb__bg {
                padding: 12px 0 10px;
                min-height: 0;
            }

            .product-preview-page .breadcrumb__bg-three {
                min-height: 0;
            }

            .product-preview-page .breadcrumb__content .title {
                font-size: 15px;
                line-height: 1.28;
                margin-bottom: 4px;
            }

            .product-preview-page .breadcrumb__content nav,
            .product-preview-page .breadcrumb__content .breadcrumb {
                margin-top: 0;
            }

            .product-preview-page .breadcrumb__menu,
            .product-preview-page .breadcrumb__content ul,
            .product-preview-page .breadcrumb__content ol {
                gap: 6px;
                flex-wrap: wrap;
                font-size: 12px;
            }

            .product-preview-page .breadcrumb__content li {
                line-height: 1.4;
            }

            .product-preview-hero {
                padding-top: 4px;
            }

            .product-preview-shell {
                gap: 12px;
            }

            .product-preview-main,
            .product-preview-sidebar,
            .product-preview-panel,
            .product-preview-section {
                padding: 12px;
                border-radius: 16px;
            }

            .product-preview-main {
                padding: 12px;
            }

            .product-preview-sidebar {
                margin-top: 0;
                padding: 0;
            }

            .product-preview-hero-grid {
                gap: 14px;
            }

            .product-preview-cover {
                min-height: 220px;
                padding: 14px;
                border-radius: 16px;
            }

            .product-preview-cover__title {
                margin-top: 40px;
                font-size: 22px;
            }

            .product-preview-cover__year {
                font-size: 12px;
            }

            .product-preview-cover__icon {
                width: 58px;
                height: 58px;
                border-radius: 16px;
                font-size: 20px;
            }

            .product-preview-copy {
                padding-top: 0;
            }

            .product-preview-title {
                font-size: clamp(21px, 7vw, 28px);
                line-height: 1.08;
            }

            .product-preview-rating-row {
                gap: 8px 10px;
                font-size: 11px;
            }

            .product-preview-desc {
                font-size: 13px;
                line-height: 1.6;
            }

            .product-preview-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .product-preview-metric {
                padding: 10px 10px 9px;
                border-radius: 12px;
            }

            .product-preview-metric__label {
                font-size: 10px;
            }

            .product-preview-metric__value {
                font-size: 14px;
            }

            .product-preview-actions {
                gap: 10px;
            }

            .product-preview-action-btn {
                width: 100%;
                min-height: 44px;
                padding: 0 14px;
                border-radius: 12px;
                font-size: 14px;
            }

            .product-preview-action-btn--primary {
                box-shadow: 0 12px 22px rgba(93, 59, 255, 0.18);
            }

            .product-preview-status {
                padding: 11px 12px;
                font-size: 14px;
            }

            .product-preview-status__price {
                margin: 10px 0 10px;
                font-size: 22px;
            }

            .product-preview-feature-list {
                gap: 8px;
            }

            .product-preview-feature-list li {
                font-size: 13px;
                line-height: 1.55;
            }

            .product-preview-box {
                padding: 12px;
                border-radius: 14px;
            }

            .product-preview-box h4 {
                font-size: 15px;
            }

            .product-preview-review-grid {
                grid-template-columns: 1fr;
            }

            .product-preview-related,
            .product-preview-review-grid {
                grid-template-columns: 1fr;
            }

            .product-preview-outline-card,
            .product-preview-copy-block {
                padding: 12px;
                border-radius: 14px;
            }

            .product-preview-detail-grid {
                gap: 12px;
            }
        }

        @media (max-width: 575px) {
            .product-preview-metrics {
                grid-template-columns: 1fr;
            }

            .product-preview-cover {
                min-height: 200px;
            }

            .product-preview-cover__title {
                font-size: 20px;
            }

            .product-preview-title {
                font-size: 24px;
            }
        }
    </style>
    @include('partials.auth-button-styles')
@endpush

@section('contents')
    <x-frontend.breadcrumb
        :title="$product->title"
        :links="[
            ['url' => route('home'), 'text' => __('Home')],
            ['url' => route('catalog'), 'text' => __('Catalog')],
            ['url' => route('product.show', $product->slug), 'text' => $product->title],
            ['url' => '', 'text' => __('Preview')],
        ]"
    />

    <section class="product-preview-hero">
        <div class="container">
            <div class="product-preview-shell">
                <div class="product-preview-content">
                    <div class="product-preview-main">
                        <div class="product-preview-hero-grid">
                            <div>
                                <div class="product-preview-cover">
                                    <div class="product-preview-cover__tag">
                                        <i class="far fa-file-pdf"></i>
                                        <span>{{ strtoupper((string) $product->file_type) }}</span>
                                    </div>
                                    <div class="product-preview-cover__title">{{ strtoupper(Str::limit(str_replace('-', ' ', $product->title), 26)) }}</div>
                                    <div class="product-preview-cover__year">{{ $metadata['year'] ?? now()->year }} {{ strtoupper((string) ($metadata['exam_category'] ?? __('Past Paper'))) }}</div>
                                    <div class="product-preview-cover__icon">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                </div>

                                <div class="product-preview-actions mt-3">
                                    <a href="#preview-sample" class="product-preview-action-btn product-preview-action-btn--outline">
                                        <i class="far fa-eye"></i>
                                        <span>{{ __('Preview Sample') }}</span>
                                    </a>
                                    @if ($hasAccess)
                                        <a href="{{ route('product.read-document', $product->slug) }}" class="product-preview-action-btn product-preview-action-btn--primary">
                                            <i class="fas fa-unlock"></i>
                                            <span>{{ __('Open Full Reader') }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="product-preview-copy">
                                <span class="product-preview-kicker">{{ $product->type_label }}</span>
                                <h1 class="product-preview-title">{{ $product->title }}</h1>

                                <div class="product-preview-rating-row">
                                    <span class="product-preview-stars" aria-label="{{ number_format($averageRating, 1) }} out of 5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i > round($averageRating) ? 'is-muted' : '' }}"></i>
                                        @endfor
                                    </span>
                                    <span>{{ number_format($averageRating, 1) }} ({{ $reviewCount }} {{ __('reviews') }})</span>
                                    <span><i class="fas fa-download"></i> {{ $previewPages->count() }} {{ __('preview pages') }}</span>
                                </div>

                                <p class="product-preview-desc">
                                    {{ $product->description ?: __('Prepare effectively with this past paper preview. The sample is public, while the full reader stays behind purchase or subscription access.') }}
                                </p>

                                <div class="product-preview-metrics">
                                    <div class="product-preview-metric">
                                        <span class="product-preview-metric__label">{{ __('Exam') }}</span>
                                        <span class="product-preview-metric__value">{{ $metadata['exam_category'] ?? __('KCSE') }}</span>
                                    </div>
                                    <div class="product-preview-metric">
                                        <span class="product-preview-metric__label">{{ __('Year') }}</span>
                                        <span class="product-preview-metric__value">{{ $metadata['year'] ?? __('N/A') }}</span>
                                    </div>
                                    <div class="product-preview-metric">
                                        <span class="product-preview-metric__label">{{ __('Subject') }}</span>
                                        <span class="product-preview-metric__value">{{ $metadata['subject'] ?? $product->category?->translation?->name ?? __('General') }}</span>
                                    </div>
                                    <div class="product-preview-metric">
                                        <span class="product-preview-metric__label">{{ __('Format') }}</span>
                                        <span class="product-preview-metric__value">{{ strtoupper((string) $product->file_type) }}</span>
                                    </div>
                                </div>

                                <div class="product-preview-chip-row">
                                    <span class="product-preview-chip"><i class="fas fa-shield-alt"></i> {{ __('Verified content') }}</span>
                                    <span class="product-preview-chip"><i class="fas fa-lock-open"></i> {{ __('Public preview enabled') }}</span>
                                    <span class="product-preview-chip"><i class="fas fa-bolt"></i> {{ $previewBadge }}</span>
                                    <span class="product-preview-chip"><i class="fas fa-tag"></i> {{ $displayPrice }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="product-preview-grid">
                            <div class="product-preview-panel" id="preview-sample">
                                <div class="product-preview-panel__tabs">
                                    <a href="#overview" class="product-preview-tab is-active">{{ __('Overview') }}</a>
                                    <a href="#reviews" class="product-preview-tab">{{ __('Reviews') }} ({{ $reviewCount }})</a>
                                </div>

                                <div class="product-preview-detail-grid" id="overview">
                                    <div class="product-preview-overview">
                                        <div class="product-preview-copy-block">
                                            <h3>{{ __('Overview') }}</h3>
                                            <p>
                                                {{ __('This is the official paper preview. Use it to check the paper style, subject focus, and the year before opening the full reader or buying access.') }}
                                            </p>
                                            <ul class="product-preview-feature-list mt-3">
                                                <li><i class="fas fa-check"></i> <span>{{ __('All questions included as per the official exam') }}</span></li>
                                                <li><i class="fas fa-check"></i> <span>{{ __('Ideal for revision and timed practice') }}</span></li>
                                                <li><i class="fas fa-check"></i> <span>{{ __('Suitable for both classroom and self-study') }}</span></li>
                                                <li><i class="fas fa-check"></i> <span>{{ __('Curated for KCSE candidates') }}</span></li>
                                            </ul>
                                        </div>

                                        <div class="product-preview-assurance-grid">
                                            <div class="product-preview-assurance-card">
                                                <div class="product-preview-assurance-card__icon">
                                                    <i class="fas fa-shield-check"></i>
                                                </div>
                                                <div>
                                                    <p class="product-preview-assurance-card__title">{{ __('Verified Content') }}</p>
                                                    <p class="product-preview-assurance-card__text">{{ __('Official past paper') }}</p>
                                                </div>
                                            </div>
                                            <div class="product-preview-assurance-card">
                                                <div class="product-preview-assurance-card__icon product-preview-assurance-card__icon--purple">
                                                    <i class="fas fa-shield-halved"></i>
                                                </div>
                                                <div>
                                                    <p class="product-preview-assurance-card__title">{{ __('Secure & Safe') }}</p>
                                                    <p class="product-preview-assurance-card__text">{{ __('Scanned and virus-free') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="product-preview-outline-card">
                                        <h4 class="product-preview-outline-card__title">{{ __('Chapters / Sections') }}</h4>
                                        <ul class="product-preview-outline-list">
                                            @forelse ($previewPages->take(7) as $page)
                                                <li class="product-preview-outline-item">
                                                    <span>{{ $loop->iteration }}. {{ __('Preview page') }} {{ $page['page_number'] ?? $loop->iteration }}</span>
                                                    <strong>{{ __('Page') }}</strong>
                                                </li>
                                            @empty
                                                <li class="product-preview-outline-item">
                                                    <span>{{ __('Preview sample') }}</span>
                                                    <strong>{{ __('1') }}</strong>
                                                </li>
                                            @endforelse
                                        </ul>
                                        <div class="product-preview-outline-total">
                                            <span>{{ __('Total') }}: {{ max(1, $previewPages->count()) }} {{ __('Pages') }}</span>
                                            <span>{{ __('Questions') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <aside class="product-preview-sidebar">
                    @if ($hasAccess)
                        <div class="product-preview-status">
                            <i class="fas fa-shield-check"></i>
                            <span>{{ __('You own this item') }}</span>
                        </div>
                        <div class="product-preview-status__price">{{ __('Purchased') }}</div>
                        <div class="product-preview-actions">
                            <a href="{{ route('product.read-document', $product->slug) }}" class="product-preview-action-btn product-preview-action-btn--primary w-100">
                                <i class="fas fa-book-open-reader"></i>
                                <span>{{ __('View in My Library') }}</span>
                            </a>
                        </div>
                    @else
                        <div class="product-preview-status product-preview-status--locked">
                            <i class="fas fa-lock"></i>
                            <span>{{ __('One-time Access') }}</span>
                        </div>
                        <div class="product-preview-status__price">{{ $displayPrice }}</div>
                        <ul class="product-preview-feature-list">
                            <li><i class="fas fa-check"></i> <span>{{ __('Full PDF access') }}</span></li>
                            <li><i class="fas fa-check"></i> <span>{{ __('Download and read offline') }}</span></li>
                            <li><i class="fas fa-check"></i> <span>{{ __('Access on all devices') }}</span></li>
                            <li><i class="fas fa-check"></i> <span>{{ __('Lifetime access') }}</span></li>
                        </ul>
                        <div class="product-preview-actions mt-3">
                            <button type="button" class="product-preview-action-btn product-preview-action-btn--buy-now buy-now purchase-btn purchase-btn--buy auth-submit-btn w-100" data-id="{{ $product->id }}" data-product-type="product" data-loading-button>
                                <span class="auth-submit-btn__content">
                                    <span class="auth-submit-btn__label">{{ __('Buy Now') }}</span>
                                </span>
                                <i class="fas fa-check" aria-hidden="true"></i>
                                <span class="auth-submit-btn__loading" aria-hidden="true">
                                    <span class="auth-submit-btn__spinner"></span>
                                    <span class="auth-submit-btn__progress">
                                        <span class="auth-submit-btn__bar"></span>
                                    </span>
                                </span>
                            </button>
                        </div>
                    @endif

                    <div class="text-center my-2">
                        <span class="badge bg-white text-uppercase border text-muted px-3 py-2">{{ __('or') }}</span>
                    </div>

                    <div class="product-preview-box">
                        <h4 class="mb-2">{{ __('Public Access') }}</h4>
                        <p class="mb-3 text-muted">{{ __('This paper includes a public sample preview before purchase.') }}</p>
                        <div class="product-preview-actions">
                            <a href="#preview-sample" class="product-preview-action-btn product-preview-action-btn--outline w-100">
                                <span>{{ __('Preview Sample') }}</span>
                            </a>
                        </div>
                    </div>

                    <div class="product-preview-box">
                        <h4 class="mb-2">{{ __('Share this paper') }}</h4>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="https://wa.me/?text={{ urlencode(route('product.preview', $product->slug)) }}" class="product-preview-chip text-decoration-none"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('product.preview', $product->slug)) }}" class="product-preview-chip text-decoration-none"><i class="fab fa-facebook"></i> Facebook</a>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="product-preview-section" id="reviews">
                        <div class="product-preview-section__head">
                            <h3>{{ __('What other students say') }}</h3>
                            <a href="javascript:;" class="text-decoration-none">{{ __('View all reviews') }}</a>
                        </div>

                        <div class="product-preview-review-grid">
                            @forelse ($ratingData['reviews']->take(4) as $review)
                                <article class="product-preview-review-card">
                                    <div class="product-preview-review-head">
                                        <img class="product-preview-review-avatar" src="{{ asset($review?->user?->image ?: 'uploads/website-images/empty-cart.png') }}" alt="{{ $review?->user?->name }}">
                                        <div>
                                            <h4 class="product-preview-review-name">{{ $review?->user?->name }}</h4>
                                            <p class="product-preview-review-role">{{ formatDate($review->created_at) }}</p>
                                        </div>
                                    </div>
                                    <div class="product-preview-stars mb-2">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i > (int) $review->rating ? 'is-muted' : '' }}"></i>
                                        @endfor
                                    </div>
                                    <p class="product-preview-review-text">{{ $review->review }}</p>
                                </article>
                            @empty
                                <article class="product-preview-copy-block">
                                    <h3>{{ __('No reviews yet') }}</h3>
                                    <p>{{ __('Be the first to review this resource once you have used it.') }}</p>
                                </article>
                            @endforelse
                        </div>
                    </div>

                    <div class="product-preview-section">
                        <div class="product-preview-section__head">
                            <h3>{{ __('More Past Papers You Might Like') }}</h3>
                            <a href="{{ route('catalog', ['main_category' => $product->category?->parentCategory?->slug ?? 'certificate-courses']) }}" class="text-decoration-none">{{ __('View all') }}</a>
                        </div>

                        <div class="product-preview-related">
                            @forelse ($relatedItems as $related)
                                <a href="{{ route('product.preview', $related->slug) }}" class="product-preview-related-card">
                                    <div class="product-preview-related-card__media">
                                        <img class="product-preview-related-card__thumb" src="{{ asset($related->thumbnail ?: 'uploads/website-images/empty-cart.png') }}" alt="{{ $related->title }}">
                                        <div>
                                            <p class="product-preview-related-card__title">{{ \Illuminate\Support\Str::limit($related->title, 42) }}</p>
                                            <div class="product-preview-related-card__meta">
                                                {{ $related->meta ?: $related->type_label }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="product-preview-related-card__footer">
                                        <strong class="product-preview-related-card__price">
                                            {{ $related->effective_price > 0 ? $formatPreviewPrice($related->effective_price) : __('Free') }}
                                        </strong>
                                        <span class="btn btn-sm btn-outline-secondary">{{ __('View') }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="product-preview-copy-block">
                                    <h3>{{ __('No related papers yet') }}</h3>
                                    <p>{{ __('Once more papers are published in this category, they will appear here automatically.') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <p class="product-preview-footer-note">
                {{ __('Public preview is open to everyone. The full reader and downloads remain available only after purchase or active access.') }}
            </p>
        </div>
    </section>
@endsection
