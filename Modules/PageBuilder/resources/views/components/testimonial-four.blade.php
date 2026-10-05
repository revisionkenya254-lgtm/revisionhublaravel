@props(['total' => 4])
@foreach (getTestimonials((int) $total) as $testimonial)
    <div class="swiper-slide">
        <div class="testimonial__item-two testimonial__item-five">
            <div class="testimonial__content-two">
                <h2 class="title">{{ __('Great Quality') }}</h2>
                <div class="rating">
                    @for ($i = 0; $i < $testimonial->rating; $i++)
                        <i class="fas fa-star"></i>
                    @endfor
                </div>
                <p>“ {{ $testimonial?->comment }} ”</p>
            </div>
            <div class="testimonial__author testimonial__author-two">
                <div class="testimonial__author-thumb testimonial__author-thumb-two">
                    <img src="{{ asset($testimonial?->image) }}" alt="img">
                </div>
                <div class="testimonial__author-content testimonial__author-content-two">
                    <h2 class="title">{{ $testimonial?->name }}</h2>
                    <span>{{ $testimonial?->designation }}</span>
                </div>
            </div>
        </div>
    </div>
@endforeach
