@props(['total' => 4])
@foreach (getTestimonials((int) $total) as $testimonial)
    <div class="swiper-slide">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-7 order-0 order-lg-2">
                    <div class="testimonial__item-four">
                        <div class="rating">
                            @for ($i = 0; $i < $testimonial->rating; $i++)
                                <i class="fas fa-star"></i>
                            @endfor
                        </div>
                        <p>“ {{ $testimonial?->comment }} ”
                        </p>
                        <div class="testimonial__bottom-two">
                            <h4 class="title">{{ $testimonial?->name }}</h4>
                            <span>{{ $testimonial?->designation }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 col-md-7">
                    <div class="testimonial__img-two">
                        <img src="{{ asset($testimonial?->image) }}" alt="img">
                        <div class="shape">
                            <img src="{{ asset('frontend/img/others/h5_testimonial_shape01.svg') }}" alt="shape"
                                class="alltuchtopdown">
                            <img src="{{ asset('frontend/img/others/h5_testimonial_shape02.svg') }}" alt="shape"
                                class="tg-motion-effects4">
                            <img src="{{ asset('frontend/img/others/h5_testimonial_shape03.svg') }}" alt="shape"
                                class="tg-motion-effects3">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endforeach
