@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    @php
        $editRoute = function ($product) {
            if ($product->type === \App\Models\Product::TYPE_QUIZ) {
                return route('instructor.quizzes.edit', $product->id);
            }

            return $product->type === \App\Models\Product::TYPE_NOTE
                ? route('product.show', $product->slug)
                : route('instructor.products.edit', $product->id);
        };

        $createRoute = route('instructor.products.create', $type ? ['type' => $type] : []);
        $paperTypes = [\App\Models\Product::TYPE_PAST_PAPER, \App\Models\Product::TYPE_PREDICTION];
        $documentPreviewMeta = function ($product) {
            return match (strtolower((string) ($product->file_type ?? ''))) {
                'doc', 'docx' => [
                    'icon' => 'fas fa-file-word',
                    'label' => __('Word document'),
                ],
                'pdf' => [
                    'icon' => 'fas fa-file-pdf',
                    'label' => __('PDF document'),
                ],
                default => [
                    'icon' => 'fas fa-file-alt',
                    'label' => __('Document'),
                ],
            };
        };
        $paperSummary = function ($product) {
            $metadata = $product->metadata ?? [];
            $parts = array_filter([
                $metadata['education_level'] ?? null,
                $metadata['class_grade'] ?? null,
                $metadata['exam_category'] ?? null,
                $metadata['course'] ?? $metadata['subject'] ?? null,
                $metadata['year'] ?? null,
            ]);

            return implode(' • ', $parts);
        };
    @endphp
    <div class="dashboard__content-wrap pb-0 instructor-product-list">
        <div class="dashboard__content-title d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h4 class="title">
                {{ $typeMeta ? $typeMeta['plural'] : __('All Products') }}
            </h4>
            <a href="{{ $createRoute }}"
                class="btn btn-primary btn-hight-basic">
                {{ $typeMeta ? __('Add ' . $typeMeta['name']) : __('Add Product') }}
            </a>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <div class="dashboard__review-table dash_instructor_course instructor-product-list__table">
                    <div class="instructor-product-list__items" id="productList">
                        @forelse($products as $product)
                            @php
                                $isPaperType = in_array($product->type, $paperTypes, true);
                                $previewMeta = $isPaperType ? $documentPreviewMeta($product) : null;
                                $summary = $isPaperType ? $paperSummary($product) : '';
                            @endphp
                            <article class="instructor-product-row">
                                <a href="{{ $editRoute($product) }}" class="instructor-product-row__thumb shine__animate-link {{ $isPaperType ? 'instructor-product-row__thumb--document' : '' }}">
                                    @if ($isPaperType)
                                        <div class="instructor-product-row__document">
                                            <i class="{{ $previewMeta['icon'] }}"></i>
                                            <strong>{{ $previewMeta['label'] }}</strong>
                                            <span>{{ $product->file_path ? __('File attached') : __('Awaiting upload') }}</span>
                                        </div>
                                    @else
                                        <img src="{{ $product->thumbnail ? asset($product->thumbnail) : asset('uploads/website-images/placeholder.png') }}" alt="img">
                                    @endif
                                    @if ($product->is_approved == 'pending')
                                        <span class="instructor-product-row__badge instructor-product-row__badge--warning">{{ __('Pending') }}</span>
                                    @elseif($product->is_approved == 'rejected')
                                        <span class="instructor-product-row__badge instructor-product-row__badge--danger">{{ __('Rejected') }}</span>
                                    @elseif($product->status == 'active')
                                        <span class="instructor-product-row__badge instructor-product-row__badge--success">{{ __('Published') }}</span>
                                    @elseif($product->status == 'inactive')
                                        <span class="instructor-product-row__badge instructor-product-row__badge--danger">{{ __('Unpublished') }}</span>
                                    @else
                                        <span class="instructor-product-row__badge instructor-product-row__badge--danger">{{ __('Draft') }}</span>
                                    @endif
                                </a>

                                <div class="instructor-product-row__body">
                                    <div class="instructor-product-row__top">
                                        <div class="instructor-product-row__meta">
                                            <span>{{ $product->type_label }}</span>
                                            @if ($product->category && @$product->category->translation->name)
                                                <span>{{ @$product->category->translation->name }}</span>
                                            @endif
                                            @if ($summary)
                                                <span>{{ $summary }}</span>
                                            @endif
                                        </div>
                                        <div class="instructor-product-row__actions">
                                            <a href="{{ $editRoute($product) }}" aria-label="{{ __('Edit') }}">
                                                <i class="far fa-edit"></i>
                                            </a>
                                            <a href="{{ route('instructor.products.destroy', $product->id) }}"
                                                class="dashboard-delete-item"
                                                data-delete-title="{{ $product->title }}"
                                                aria-label="{{ __('Delete') }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="instructor-product-row__middle">
                                        <h5 class="title"><a href="{{ $editRoute($product) }}">{{ $product->title }}</a></h5>
                                        <div class="instructor-product-row__bottom">
                                            <div class="author-two">
                                                <a href="javascript:;"><img src="{{ asset($product->instructor->image ?? 'uploads/default-avatar.png') }}" alt="img">{{ $product->instructor->name ?? 'N/A' }}</a>
                                            </div>
                                            <div class="avg-rating">
                                                <span class="price">{{ defaultCurrency($product->price) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-4">
                                    <h6>{{ __('No Product Found') }}</h6>
                                </div>
                            </div>
                        @endforelse
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .instructor-product-list .dashboard__content-title {
            margin-bottom: 14px;
        }

        .instructor-product-list .dashboard__content-title .title {
            margin-bottom: 0;
            font-size: 22px;
            line-height: 1.15;
        }

        .instructor-product-list__table {
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: visible;
        }

        .instructor-product-list__items {
            display: grid;
            gap: 10px;
            min-width: 0;
        }

        .instructor-product-row {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr);
            gap: 14px;
            align-items: center;
            padding: 12px;
            border: 1px solid rgba(216, 222, 234, 0.95);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(20, 33, 61, 0.04);
        }

        .instructor-product-row__thumb {
            position: relative;
            display: block;
            width: 88px;
            height: 88px;
            border-radius: 12px;
            overflow: hidden;
            background: #f4f7fb;
            flex: 0 0 auto;
        }

        .instructor-product-row__thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .instructor-product-row__thumb--document {
            background: linear-gradient(180deg, rgba(91, 87, 214, 0.08), rgba(91, 87, 214, 0.02));
            border: 1px solid rgba(91, 87, 214, 0.12);
        }

        .instructor-product-row__document {
            display: grid;
            place-items: center;
            gap: 4px;
            width: 100%;
            height: 100%;
            padding: 10px 8px;
            text-align: center;
            color: #4e5e77;
        }

        .instructor-product-row__document i {
            font-size: 22px;
            color: #5b57d6;
        }

        .instructor-product-row__document strong {
            font-size: 11px;
            font-weight: 800;
            line-height: 1.1;
            color: #1f2a44;
        }

        .instructor-product-row__document span {
            font-size: 10px;
            line-height: 1.15;
            color: #667085;
        }

        .instructor-product-row__badge {
            position: absolute;
            left: 8px;
            bottom: 8px;
            padding: 4px 8px;
            border-radius: 999px;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
        }

        .instructor-product-row__badge--warning {
            background: #f59e0b;
        }

        .instructor-product-row__badge--success {
            background: #16a34a;
        }

        .instructor-product-row__badge--danger {
            background: #ef4444;
        }

        .instructor-product-row__body {
            min-width: 0;
        }

        .instructor-product-row__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .instructor-product-row__meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .instructor-product-row__meta span {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(91, 87, 214, 0.08);
            color: #5b57d6;
            font-size: 11px;
            font-weight: 700;
        }

        .instructor-product-row__actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }

        .instructor-product-row__actions a {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border: 1px solid #e3e8f2;
            border-radius: 10px;
            color: #5a6788;
            background: #fff;
        }

        .instructor-product-row__middle .title {
            margin: 0 0 8px;
            font-size: 17px;
            line-height: 1.25;
        }

        .instructor-product-row__middle .title a {
            color: #16213f;
        }

        .instructor-product-row__bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .instructor-product-row .author-two a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #5a6788;
            font-size: 13px;
            font-weight: 600;
        }

        .instructor-product-row .author-two img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }

        .instructor-product-row .avg-rating {
            font-size: 14px;
            font-weight: 700;
            color: #16213f;
        }

        .instructor-product-list__table .pagination {
            margin-top: 8px;
        }

        @media (max-width: 575.98px) {
            .instructor-product-list__table {
                overflow: visible;
            }

            .instructor-product-row {
                grid-template-columns: 72px minmax(0, 1fr);
                gap: 12px;
                padding: 10px;
            }

            .instructor-product-row__thumb {
                width: 72px;
                height: 72px;
            }

            .instructor-product-row__middle .title {
                font-size: 15px;
            }
        }
    </style>
@endpush
