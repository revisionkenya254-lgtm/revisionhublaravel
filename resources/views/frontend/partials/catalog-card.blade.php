@forelse ($items as $item)
    @php
        $cardType = (string) ($item->type ?? '');
        $typeMap = [
            'course' => ['label' => __('Video'), 'tone' => 'blue', 'icon' => 'fa-video', 'action' => __('Preview'), 'accent' => '#2563eb'],
            'note' => ['label' => __('Notes'), 'tone' => 'pink', 'icon' => 'fa-sticky-note', 'action' => __('Continue Reading'), 'accent' => '#f43f8c'],
            'past_paper' => ['label' => __('Past Paper'), 'tone' => 'green', 'icon' => 'fa-file-alt', 'action' => __('Add To Cart'), 'accent' => '#16a34a'],
            'prediction' => ['label' => __('Predictions'), 'tone' => 'orange', 'icon' => 'fa-bullseye', 'action' => __('Add To Cart'), 'accent' => '#f97316'],
            'quiz' => ['label' => __('Quiz'), 'tone' => 'violet', 'icon' => 'fa-poll', 'action' => __('Start Quiz'), 'accent' => '#6d4cff'],
        ];
        $cardConfig = $typeMap[$cardType] ?? ['label' => $item->type_label ?? __('Resource'), 'tone' => 'slate', 'icon' => 'fa-layer-group', 'action' => __('Open'), 'accent' => '#475569'];
        $cardTitle = (string) $item->title;
        $cardImage = asset($item->thumbnail ?: 'uploads/website-images/empty-cart.png');
        $cardUrl = match ($cardType) {
            'course' => $item->url,
            'quiz' => route('product.start-quiz', $item->slug),
            default => route('product.preview', $item->slug),
        };
        $cardRating = $item->rating !== null ? (string) $item->rating : null;
    $cardPriceValue = (float) (($item->discount !== null && (float) $item->discount > 0) ? $item->discount : $item->price);
    $cardActionLabel = $item->is_purchased ? ($item->access_label ?? __('Open')) : $cardConfig['action'];
    $cardCategory = (string) ($item->category_translation_name ?? '');
    $cardCategorySlug = (string) ($item->category_slug ?? '');
    @endphp

    <div class="catalog-grid-item">
        <article class="catalog-listing-card catalog-listing-card--{{ $cardConfig['tone'] }}" style="--catalog-card-accent: {{ $cardConfig['accent'] }};">
            <div class="catalog-listing-card__media">
                <span class="catalog-listing-card__badge">
                    <i class="fas {{ $cardConfig['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $cardConfig['label'] }}</span>
                </span>

                <a href="{{ $cardUrl }}" class="catalog-listing-card__image-link shine__animate-link" aria-label="{{ $cardTitle }}">
                    <img src="{{ $cardImage }}" alt="{{ $cardTitle }}">
                </a>

                <button type="button" class="catalog-listing-card__bookmark" aria-label="{{ __('Save resource') }}">
                    <i class="far fa-bookmark" aria-hidden="true"></i>
                </button>
            </div>

            <div class="catalog-listing-card__body">
                <h5 class="catalog-listing-card__title">
                    <a href="{{ $cardUrl }}">{{ truncate($cardTitle, 48) }}</a>
                </h5>

                <div class="catalog-listing-card__meta-line">
                    @if ($cardCategory !== '')
                        <a href="{{ route('catalog', ['category' => $cardCategorySlug !== '' ? $cardCategorySlug : $item->category_id]) }}" class="catalog-listing-card__category">
                            {{ $cardCategory }}
                        </a>
                    @endif

                    @if ($cardRating !== null)
                        <span class="catalog-listing-card__rating">
                            <i class="fas fa-star" aria-hidden="true"></i>
                            {{ $cardRating }}
                        </span>
                    @endif
                </div>

                @if (filled($item->meta))
                    <p class="catalog-listing-card__meta">{{ $item->meta }}</p>
                @endif

                <div class="catalog-listing-card__footer">
                    <div class="catalog-listing-card__price">
                        @if ($cardPriceValue == 0)
                            <span class="catalog-listing-card__price-value">{{ __('Free') }}</span>
                        @else
                            <span class="catalog-listing-card__price-label">{{ __('KSh') }}</span>
                            <span class="catalog-listing-card__price-value">{{ number_format($cardPriceValue, 0) }}</span>
                        @endif
                    </div>

                    @if ($cardType === 'past_paper' || $cardType === 'prediction')
                        <div class="catalog-listing-card__actions catalog-listing-card__actions--dual">
                            @if (!$item->is_purchased)
                                <a href="javascript:;" class="catalog-listing-card__button add-to-cart purchase-btn purchase-btn--cart" data-id="{{ $item->cart_id }}" data-product-type="{{ $item->cart_type }}">
                                    <span>{{ __('Add To Cart') }}</span>
                                    <i class="fas fa-cart-shopping" aria-hidden="true"></i>
                                </a>
                                <button type="button" class="catalog-listing-card__button catalog-listing-card__button--solid catalog-listing-card__button--buy-now buy-now purchase-btn purchase-btn--buy" data-id="{{ $item->cart_id }}" data-product-type="{{ $item->cart_type }}">
                                    <span class="text">{{ __('Buy Now') }}</span>
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            @else
                                <a href="{{ $cardUrl }}" class="catalog-listing-card__button catalog-listing-card__button--solid">
                                    <span>{{ $cardActionLabel }}</span>
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    @elseif ($cardType === 'quiz')
                        <div class="catalog-listing-card__actions">
                            <a href="{{ $cardUrl }}" class="catalog-listing-card__button catalog-listing-card__button--solid">
                                <span>{{ $cardActionLabel }}</span>
                                <i class="fas fa-play" aria-hidden="true"></i>
                            </a>
                        </div>
                    @else
                        <div class="catalog-listing-card__actions">
                            <a href="{{ $cardUrl }}" class="catalog-listing-card__button catalog-listing-card__button--solid">
                                <span>{{ $cardActionLabel }}</span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </article>
    </div>
@empty
    <div class="w-100">
        <div class="catalog-empty-card">
            <div class="catalog-empty-card__icon">
                <i class="fas fa-folder-open"></i>
            </div>
            <h6>{{ __('No resources found yet.') }}</h6>
            <p>{{ __('Try another filter, category, or come back later when new resources are published.') }}</p>
        </div>
    </div>
@endforelse
