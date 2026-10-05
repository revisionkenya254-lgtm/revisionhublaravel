<section class="features__area">
  <div class="container">
      <div class="row justify-content-center">
          <div class="col-xl-6">
              <div class="section__title white-title text-center mb-50">
                  <span class="sub-title">{{ __('How We Start Journey') }}</span>
                  <h2 class="title">{{ __('Start your Learning Journey Today!') }}</h2>
                  <p>{{ __('Discover a World of Knowledge and Skills at Your Fingertips – Unlock Your Potential and Achieve Your Dreams with Our Comprehensive Learning Resources!') }}</p>
              </div>
          </div>
      </div>
      <div class="row justify-content-center">
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="features__item">
                <div class="features__icon">
                    <img src="{{ asset($ourFeatures?->global_content?->image_one) }}" alt="img">
                </div>
                <div class="features__content">
                    <p class="title lh-base">{{ $ourFeatures?->translation_content?->title_one }}</p>
                    <p>{{ $ourFeatures?->translation_content?->sub_title_one }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="features__item">
                <div class="features__icon">
                    <img src="{{ asset($ourFeatures?->global_content?->image_two) }}" alt="img">
                </div>
                <div class="features__content">
                    <p class="title lh-base">{{ $ourFeatures?->translation_content?->title_two }}</p>
                    <p>{{ $ourFeatures?->translation_content?->sub_title_two }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="features__item">
                <div class="features__icon">
                    <img src="{{ asset($ourFeatures?->global_content?->image_three) }}" alt="img">
                </div>
                <div class="features__content">
                    <p class="title lh-base">{{ $ourFeatures?->translation_content?->title_three }}</p>
                    <p>{{ $ourFeatures?->translation_content?->sub_title_three }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="features__item">
                <div class="features__icon">
                    <img src="{{ asset($ourFeatures?->global_content?->image_four) }}" alt="img">
                </div>
                <div class="features__content">
                    <p class="title lh-base">{{ $ourFeatures?->translation_content?->title_four }}</p>
                    <p>{{ $ourFeatures?->translation_content?->sub_title_four }}</p>
                </div>
            </div>
        </div>
    </div>
    
  </div>
</section>