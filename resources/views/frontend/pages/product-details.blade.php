@extends('frontend.layouts.master')
@section('meta_title', $product->title . ' || ' . $setting->app_name)

@push('styles')
    @include('partials.auth-button-styles')
@endpush

@section('contents')
    <x-frontend.breadcrumb :title="$product->title" :links="[['url' => route('home'), 'text' => __('Home')], ['url' => route('catalog'), 'text' => __('Catalog')], ['url' => '', 'text' => $product->title]]" />

    <section class="course-details-area section-py-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <span class="catalog-product-badge mb-3">{{ $product->type_label }}</span>
                    <h2>{{ $product->title }}</h2>
                    <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                        <span class="avg-rating"><i class="fas fa-star"></i> {{ number_format($ratingData['average'] ?? 0, 1) }}</span>
                        <span>{{ $ratingData['total'] ?? 0 }} {{ __('Reviews') }}</span>
                        @if ($product->type === \App\Models\Product::TYPE_NOTE)
                            @if ($product->topic_count)
                                <span><i class="fas fa-list-ul"></i> {{ $product->topic_count }} {{ __('Topics') }}</span>
                            @endif
                            @if ($product->estimated_read_minutes)
                                <span><i class="far fa-clock"></i> {{ $product->estimated_read_minutes }} {{ __('Min Read') }}</span>
                            @endif
                        @elseif ($product->type === \App\Models\Product::TYPE_QUIZ && $product->quiz)
                            <span class="badge bg-light text-dark text-capitalize">{{ $product->quiz->tier }}</span>
                            <span class="badge bg-info text-dark text-capitalize">{{ $product->quiz->difficulty }}</span>
                            <span><i class="fas fa-list-ol"></i> {{ $product->quiz->question_count_cache }} {{ __('Questions') }}</span>
                            @if ($product->quiz->duration_minutes)
                                <span><i class="far fa-clock"></i> {{ $product->quiz->duration_minutes }} {{ __('Min') }}</span>
                            @endif
                        @endif
                    </div>
                    <p>{!! clean($product->description ?? '') !!}</p>
                </div>
                <div class="col-lg-4">
                    <div class="courses__details-sidebar">
                        <img src="{{ asset($product->thumbnail ?: 'uploads/website-images/empty-cart.png') }}" alt="{{ $product->title }}" class="img-fluid mb-3">
                        <h4>
                            @if ($product->effective_price == 0)
                                {{ __('Free') }}
                            @elseif ($product->discount > 0)
                                {{ defaultCurrency($product->discount) }}
                            @else
                                {{ defaultCurrency($product->price) }}
                            @endif
                        </h4>

                        @if ($hasAccess)
                            @if (in_array($product->type, ['past_paper', 'prediction'], true))
                                <a href="{{ route('product.read-document', $product->slug) }}" class="btn btn-two arrow-btn">{{ __('Read online') }}</a>
                            @elseif ($product->type === 'note')
                                <a href="{{ route('product.read-note', $product->slug) }}" class="btn btn-two arrow-btn">{{ __('Read online') }}</a>
                            @elseif ($product->type === 'quiz')
                                <a href="{{ route('product.start-quiz', $product->slug) }}" class="btn btn-two arrow-btn">{{ __('Start quiz') }}</a>
                            @endif
                        @else
                            @if (in_array($product->type, [\App\Models\Product::TYPE_PAST_PAPER, \App\Models\Product::TYPE_PREDICTION], true))
                                <a href="{{ route('product.preview', $product->slug) }}" class="btn btn-outline-primary arrow-btn mb-3">
                                    {{ __('Preview') }}
                                </a>
                            @endif
                            <a href="javascript:;" class="btn btn-two arrow-btn add-to-cart purchase-btn purchase-btn--cart" data-id="{{ $product->id }}" data-product-type="product">
                                <span class="text">{{ __('Add To Cart') }}</span>
                                <i class="flaticon-arrow-right"></i>
                            </a>
                            <button type="button" class="btn btn-border arrow-btn buy-now purchase-btn purchase-btn--buy auth-submit-btn mt-3" data-id="{{ $product->id }}" data-product-type="product" data-loading-button>
                                <span class="auth-submit-btn__content">
                                    <span class="auth-submit-btn__label">{{ __('Buy Now') }}</span>
                                </span>
                                <i class="flaticon-arrow-right"></i>
                                <span class="auth-submit-btn__loading" aria-hidden="true">
                                    <span class="auth-submit-btn__spinner"></span>
                                    <span class="auth-submit-btn__progress">
                                        <span class="auth-submit-btn__bar"></span>
                                    </span>
                                </span>
                            </button>
                        @endif

                        @if ($product->type === \App\Models\Product::TYPE_NOTE)
                            <div class="mt-4 pt-4 border-top">
                                <ul class="list-wrap">
                                    @if ($product->topic_count)
                                        <li class="d-flex justify-content-between mb-2">
                                            <span>{{ __('Topics') }}</span>
                                            <strong>{{ $product->topic_count }}</strong>
                                        </li>
                                    @endif
                                    @if ($product->estimated_read_minutes)
                                        <li class="d-flex justify-content-between mb-2">
                                            <span>{{ __('Estimated Read') }}</span>
                                            <strong>{{ $product->estimated_read_minutes }} {{ __('min') }}</strong>
                                        </li>
                                    @endif
                                    @if ($product->category?->translation?->name || $product->category?->name)
                                        <li class="d-flex justify-content-between">
                                            <span>{{ __('Subject') }}</span>
                                            <strong>{{ $product->category?->translation?->name ?? $product->category?->name }}</strong>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @elseif ($product->type === \App\Models\Product::TYPE_QUIZ && $product->quiz)
                            <div class="mt-4 pt-4 border-top">
                                <ul class="list-wrap">
                                    <li class="d-flex justify-content-between mb-2">
                                        <span>{{ __('Tier') }}</span>
                                        <strong class="text-capitalize">{{ $product->quiz->tier }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between mb-2">
                                        <span>{{ __('Difficulty') }}</span>
                                        <strong class="text-capitalize">{{ $product->quiz->difficulty }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between mb-2">
                                        <span>{{ __('Questions') }}</span>
                                        <strong>{{ $product->quiz->question_count_cache }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between mb-2">
                                        <span>{{ __('Duration') }}</span>
                                        <strong>{{ $product->quiz->duration_minutes ? $product->quiz->duration_minutes . ' ' . __('min') : __('Not timed') }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between mb-2">
                                        <span>{{ __('Attempt Limit') }}</span>
                                        <strong>{{ $product->quiz->attempt_limit }}</strong>
                                    </li>
                                    <li class="d-flex justify-content-between">
                                        <span>{{ __('Pass Mark') }}</span>
                                        <strong>{{ $product->quiz->pass_mark }} / {{ $product->quiz->total_marks }}</strong>
                                    </li>
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-lg-8">
                    <div class="courses__rating-wrap">
                        <h3 class="title mb-4">{{ __('Reviews') }}</h3>
                        <div class="course-rate">
                            <div class="course-rate__summary">
                                <div class="course-rate__summary-value">{{ number_format($ratingData['average'] ?? 0, 1) }}</div>
                                <div class="course-rate__summary-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="course-rate__summary-text">{{ $ratingData['total'] ?? 0 }} {{ __('Ratings') }}</div>
                            </div>
                            @php
                                $totalRating = ($ratingData['fiveStar'] ?? 0) + ($ratingData['fourStar'] ?? 0) + ($ratingData['threeStar'] ?? 0) + ($ratingData['twoStar'] ?? 0) + ($ratingData['oneStar'] ?? 0);
                                $fivePercentage = $totalRating > 0 ? (($ratingData['fiveStar'] ?? 0) / $totalRating) * 100 : 0;
                                $fourPercentage = $totalRating > 0 ? (($ratingData['fourStar'] ?? 0) / $totalRating) * 100 : 0;
                                $threePercentage = $totalRating > 0 ? (($ratingData['threeStar'] ?? 0) / $totalRating) * 100 : 0;
                                $twoPercentage = $totalRating > 0 ? (($ratingData['twoStar'] ?? 0) / $totalRating) * 100 : 0;
                                $onePercentage = $totalRating > 0 ? (($ratingData['oneStar'] ?? 0) / $totalRating) * 100 : 0;
                            @endphp
                            <div class="course-rate__details">
                                @foreach ([5 => $fivePercentage, 4 => $fourPercentage, 3 => $threePercentage, 2 => $twoPercentage, 1 => $onePercentage] as $star => $percentage)
                                    <div class="course-rate__details-row">
                                        <div class="course-rate__details-row-star">
                                            {{ $star }}
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="course-rate__details-row-value">
                                            <div class="rating-gray"></div>
                                            <div class="rating" style="width: {{ $percentage }}%;" title="{{ $percentage }}%"></div>
                                            <span class="rating-count">{{ $ratingData[match ($star) {5 => 'fiveStar', 4 => 'fourStar', 3 => 'threeStar', 2 => 'twoStar', default => 'oneStar'}] ?? 0 }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @forelse (($ratingData['reviews'] ?? collect()) as $review)
                            <div class="course-review-head">
                                <div class="review-author-thumb">
                                    <img src="{{ asset($review?->user?->image) }}" alt="img">
                                </div>
                                <div class="review-author-content">
                                    <div class="author-name">
                                        <h5 class="name">{{ $review?->user?->name }} <span>{{ formatDate($review->created_at) }}</span></h5>
                                        <div class="author-rating">
                                            @for ($i = 1; $i <= $review->rating; $i++)
                                                <i class="fas fa-star"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    <p>{{ $review->review }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="mb-0">{{ __('No reviews yet.') }}</p>
                        @endforelse
                    </div>
                </div>
                <div class="col-lg-4">
                    @auth
                        @if ($hasAccess)
                            <div class="courses__details-sidebar">
                                <h4 class="mb-3">{{ __('Write a review') }}</h4>
                                @if ($userReview)
                                    <p class="mb-0">{{ __('You have already submitted a review for this item.') }}</p>
                                @else
                                    <form action="{{ route('product.review.store', $product->slug) }}" method="POST">
                                        @csrf
                                        <div class="form-group mb-3">
                                            <label for="rating">{{ __('Rating') }}</label>
                                            <select id="rating" name="rating" class="form-control">
                                                <option value="">{{ __('Select rating') }}</option>
                                                @for ($i = 5; $i >= 1; $i--)
                                                    <option value="{{ $i }}" @selected(old('rating') == $i)>{{ $i }}</option>
                                                @endfor
                                            </select>
                                            @error('rating')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="review">{{ __('Review') }}</label>
                                            <textarea id="review" name="review" class="form-control" rows="5">{{ old('review') }}</textarea>
                                            @error('review')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <button type="submit" class="btn btn-two arrow-btn">{{ __('Submit Review') }}</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </section>
@endsection
