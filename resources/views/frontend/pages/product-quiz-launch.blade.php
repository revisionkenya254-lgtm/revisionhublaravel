@extends('frontend.layouts.master')
@section('meta_title', $product->title . ' || ' . $setting->app_name)

@section('contents')
    <x-frontend.breadcrumb :title="$product->title" :links="[['url' => route('home'), 'text' => __('Home')], ['url' => route('catalog'), 'text' => __('Catalog')], ['url' => route('product.show', $product->slug), 'text' => $product->title], ['url' => '', 'text' => __('Quiz')]]" />

    <section class="section-py-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="catalog-product-badge">{{ __('Quiz') }}</span>
                                <span class="badge bg-secondary">{{ ucfirst($product->quiz->tier) }}</span>
                                <span class="badge bg-info text-dark">{{ ucfirst($product->quiz->difficulty) }}</span>
                            </div>
                            <h2>{{ $product->title }}</h2>
                            <p>{!! clean($product->description ?? '') !!}</p>
                            <ul class="list-wrap mt-4 mb-4">
                                <li>{{ __('Questions') }}: <strong>{{ $product->quiz->question_count_cache }}</strong></li>
                                <li>{{ __('Duration') }}: <strong>{{ $product->quiz->duration_minutes ? $product->quiz->duration_minutes . ' ' . __('minutes') : __('Not timed') }}</strong></li>
                                <li>{{ __('Attempt Limit') }}: <strong>{{ $product->quiz->attempt_limit }}</strong></li>
                                <li>{{ __('Pass Mark') }}: <strong>{{ $product->quiz->pass_mark }} / {{ $product->quiz->total_marks }}</strong></li>
                                <li>{{ __('Attempts Used') }}: <strong>{{ $attemptsUsed }}</strong></li>
                            </ul>
                            @if ($attemptsUsed >= $product->quiz->attempt_limit)
                                <div class="alert alert-warning mb-0">
                                    {{ __('You have already used all allowed attempts for this quiz.') }}
                                </div>
                            @else
                                <a href="{{ route('product.quiz.attempt', $product->slug) }}" class="btn btn-two arrow-btn">{{ __('Start Attempt') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
