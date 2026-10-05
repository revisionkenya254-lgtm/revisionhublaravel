@extends('frontend.student-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title">
            <h4 class="title">{{ __('Library') }}</h4>
        </div>
        <div class="row">
            @forelse ($items as $item)
                @php
                    $product = $item->product;
                    $actionUrl = $item->action_url ?? match ($product?->type) {
                        'past_paper' => route('product.read-document', $product?->slug),
                        'prediction' => route('product.read-document', $product?->slug),
                        'note' => route('product.read-note', $product?->slug),
                        'quiz' => route('product.start-quiz', $product?->slug),
                        default => route('product.show', $product?->slug),
                    };
                @endphp
                <div class="col-xl-4 col-md-6">
                    <div class="courses__item shine__animate-item mb-4">
                        <div class="courses__item-thumb">
                            <a href="{{ route('product.show', $product?->slug) }}" class="shine__animate-link">
                                <img src="{{ asset($product?->thumbnail ?: 'uploads/website-images/empty-cart.png') }}" alt="{{ $product?->title }}">
                            </a>
                        </div>
                        <div class="courses__item-content">
                            <ul class="courses__item-meta list-wrap">
                                <li class="courses__item-tag"><a href="javascript:;">{{ $product?->category?->translation?->name ?? $product?->category?->name }}</a></li>
                                <li><span class="badge bg-info">{{ $product?->type_label }}</span></li>
                                <li><span class="badge bg-secondary">{{ $item->access_source_label ?? __('Open') }}</span></li>
                                @if ($product?->type === \App\Models\Product::TYPE_QUIZ)
                                    @if (!empty($product?->metadata['difficulty']))
                                        <li><span class="badge bg-light text-dark text-capitalize">{{ $product->metadata['difficulty'] }}</span></li>
                                    @endif
                                    @if (!empty($product?->metadata['tier']))
                                        <li><span class="badge bg-light text-dark text-capitalize">{{ $product->metadata['tier'] }}</span></li>
                                    @endif
                                @endif
                            </ul>
                            <h5 class="title"><a href="{{ route('product.show', $product?->slug) }}">{{ truncate($product?->title, 50) }}</a></h5>
                            @if (!empty($item->progress_percent))
                                <div class="progress mt-3" role="progressbar" aria-valuenow="{{ number_format($item->progress_percent, 1) }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: {{ number_format($item->progress_percent, 1) }}%"></div>
                                </div>
                            @endif
                            <div class="courses__item-bottom">
                                <div class="button">
                                    <a href="{{ $actionUrl }}" class="already-enrolled-btn">
                                        <span class="text">{{ $product?->access_label }}</span>
                                        <i class="flaticon-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center">
                    <h6>{{ __('No library resources found.') }}</h6>
                </div>
            @endforelse
        </div>
        {{ $items->links() }}
    </div>
@endsection
