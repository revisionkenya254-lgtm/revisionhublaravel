@props(['total' => 4])
@foreach (getTestimonials((int) $total) as $testimonial)
    <div class="swiper-slide">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-6 col-md-10">
                <div class="testimonial__img">
                    <img src="{{ asset($testimonial?->image) }}" alt="img">
                    <div class="shape">
                        <img src="{{ asset('frontend/img/others/testimonial_img_shape.svg') }}" alt="shape"
                            class="rotateme">
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="testimonial__item-three">
                    <div class="icon">
                        <img src="{{ asset('frontend/img/icons/testi_icon.svg') }}" alt="" class="injectable">
                    </div>
                    <div class="rating">
                        @for ($i = 0; $i < $testimonial->rating; $i++)
                            <i class="fas fa-star"></i>
                        @endfor
                    </div>
                    <p>“ {{ $testimonial?->comment }} “
                    </p>
                    <div class="testimonial__bottom">
                        <h4 class="title">{{ $testimonial?->name }}</h4>
                        <span>{{ $testimonial?->designation }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
