@props(['total'=> 4])
@foreach (getTestimonials((int) $total) as $testimonial)
    <div class="swiper-slide">
        <div class="testimonial__item">
            <div class="testimonial__item-top">
                <div class="testimonial__author">
                    <div class="testimonial__author-thumb">
                        <img src="{{ asset($testimonial->image) }}" alt="img">
                    </div>
                    <div class="testimonial__author-content">
                        <div class="rating">
                            @for ($i = 0; $i < $testimonial->rating; $i++)
                                <i class="fas fa-star"></i>
                            @endfor
                        </div>
                        <h2 class="title">{{ $testimonial?->name }}</h2>
                    </div>
                </div>
            </div>
            <div class="testimonial__content">
                <p>“ {{ $testimonial?->comment }} ”</p>
            </div>
        </div>
    </div>
@endforeach