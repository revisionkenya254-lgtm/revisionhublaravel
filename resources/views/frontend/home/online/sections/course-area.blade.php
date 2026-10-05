<section class="courses-area section-pt-120 section-pb-90"
    data-background="{{ asset('frontend/img/bg/courses_bg.jpg') }}">
    <div class="container">
        <div class="section__title-wrap">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="section__title text-center mb-40">
                        <span class="sub-title">{{ __('Top Class Courses') }}</span>
                        <h2 class="title">{{ __('Explore Our Worlds Featured Courses') }}</h2>
                        <p class="desc">{{ __('Check out the most demanding courses right now') }}</p>
                    </div>
                    <div class="courses__nav">
                        <ul class="nav nav-tabs" id="courseTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab"
                                    data-bs-target="#all-tab-pane" type="button" role="tab"
                                    aria-controls="all-tab-pane" aria-selected="true">
                                    {{ __('All Courses') }}
                                </button>
                            </li>
                            @if ($featuredCourse?->category_one_status == 1 && isset($categoryData[$featuredCourse->category_one]))
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="design-tab" data-bs-toggle="tab"
                                        data-bs-target="#design-tab-pane" type="button" role="tab"
                                        aria-controls="design-tab-pane" aria-selected="false">
                                        {{ $categoryData[$featuredCourse->category_one]->translation_name ?? $categoryData[$featuredCourse->category_one]->name ?? '' }}
                                    </button>
                                </li>
                            @endif
                            @if ($featuredCourse?->category_two_status == 1 && isset($categoryData[$featuredCourse->category_two]))
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="business-tab" data-bs-toggle="tab"
                                        data-bs-target="#business-tab-pane" type="button" role="tab"
                                        aria-controls="business-tab-pane" aria-selected="false">
                                        {{ $categoryData[$featuredCourse->category_two]->translation_name ?? $categoryData[$featuredCourse->category_two]->name ?? '' }}
                                    </button>
                                </li>
                            @endif
                            @if ($featuredCourse?->category_three_status == 1 && isset($categoryData[$featuredCourse->category_three]))
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="development-tab" data-bs-toggle="tab"
                                        data-bs-target="#development-tab-pane" type="button" role="tab"
                                        aria-controls="development-tab-pane" aria-selected="false">
                                        {{ $categoryData[$featuredCourse->category_three]->translation_name ?? $categoryData[$featuredCourse->category_three]->name ?? '' }}
                                    </button>
                                </li>
                            @endif
                            @if ($featuredCourse?->category_four_status == 1 && isset($categoryData[$featuredCourse->category_four]))
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="categoryFour-tab" data-bs-toggle="tab"
                                        data-bs-target="#categoryFour-tab-pane" type="button" role="tab"
                                        aria-controls="categoryFour-tab-pane" aria-selected="false">
                                        {{ $categoryData[$featuredCourse->category_four]->translation_name ?? $categoryData[$featuredCourse->category_four]->name ?? '' }}
                                    </button>
                                </li>
                            @endif
                            @if ($featuredCourse?->category_five_status == 1 && isset($categoryData[$featuredCourse->category_five]))
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="categoryFive-tab" data-bs-toggle="tab"
                                        data-bs-target="#categoryFive-tab-pane" type="button" role="tab"
                                        aria-controls="categoryFive-tab-pane" aria-selected="false">
                                        {{ $categoryData[$featuredCourse->category_five]->translation_name ?? $categoryData[$featuredCourse->category_five]->name ?? '' }}
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-content" id="courseTabContent">
            <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                tabindex="0">
                <div class="swiper courses-swiper-active">
                    <div class="swiper-wrapper">
                        @foreach ($coursesByCategory['all'] ?? [] as $course)
                            <div class="swiper-slide">
                                <div class="courses__item shine__animate-item">
                                    <div class="courses__item-thumb">
                                        <a href="{{ route('course.show', $course->slug) }}"
                                            class="shine__animate-link">
                                            <img src="{{ asset($course->thumbnail) }}" alt="img">
                                        </a>
                                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                            data-slug="{{ $course?->slug }}">
                                            <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="courses__item-content">
                                        <ul class="courses__item-meta list-wrap">
                                            <li class="courses__item-tag">
                                                <a
                                                    href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                            </li>
                                            <li class="avg-rating"><i class="fas fa-star"></i>
                                                {{ number_format($course->avg_rating, 1) ?? 0 }}
                                            </li>
                                        </ul>
                                        <h3 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                        </h3>
                                        <p class="author">{{ __('By') }} <a
                                                href="{{ route('instructor-details', ['id' => $course->instructor->id, 'slug' => Str::slug($course->instructor->name)]) }}">{{ $course->instructor->name }}</a>
                                        </p>
                                        <div class="courses__item-bottom">
                                            @if (in_array($course->id, session('enrollments') ?? []))
                                                <div class="button">
                                                    <a href="{{ route('student.enrolled-courses') }}"
                                                        class="already-enrolled-btn" data-id="">
                                                        <span class="text">{{ __('Enrolled') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @elseif ($course->enrollments_count >= $course->capacity && $course->capacity != null)
                                                <div class="button">
                                                    <a href="javascript:;" class=""
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Booked') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="button d-flex flex-column gap-2">
                                                    <a href="javascript:;" class="add-to-cart purchase-btn purchase-btn--cart"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Add To Cart') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                    <a href="javascript:;" class="buy-now purchase-btn purchase-btn--buy"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Buy Now') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @endif

                                            @if ($course->price == 0)
                                                <h4 class="price">{{ __('Free') }}</h4>
                                            @elseif ($course->price > 0 && $course->discount > 0)
                                                <h4 class="price">{{ currency($course->discount) }}</h4>
                                            @else
                                                <h4 class="price">{{ currency($course->price) }}</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="courses__nav">
                    <div class="courses-button-prev"><i class="flaticon-arrow-right"></i></div>
                    <div class="courses-button-next"><i class="flaticon-arrow-right"></i></div>
                </div>
            </div>

            <div class="tab-pane fade" id="design-tab-pane" role="tabpanel" aria-labelledby="design-tab-pane"
                tabindex="0">
                <div class="swiper courses-swiper-active">
                    <div class="swiper-wrapper">
                        @foreach ($coursesByCategory['categoryOne'] ?? [] as $course)
                            <div class="swiper-slide">
                                <div class="courses__item shine__animate-item">
                                    <div class="courses__item-thumb">
                                        <a href="{{ route('course.show', $course->slug) }}"
                                            class="shine__animate-link">
                                            <img src="{{ asset($course->thumbnail) }}" alt="img">
                                        </a>
                                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                            data-slug="{{ $course?->slug }}">
                                            <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="courses__item-content">
                                        <ul class="courses__item-meta list-wrap">
                                            <li class="courses__item-tag">
                                                <a
                                                    href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                            </li>
                                            <li class="avg-rating"><i class="fas fa-star"></i>
                                                {{ number_format($course->avg_rating, 1) ?? 0 }}
                                            </li>
                                        </ul>
                                        <h3 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                        </h3>
                                        <p class="author">{{ __('By') }} <a
                                                href="{{ route('instructor-details', ['id' => $course->instructor->id, 'slug' => Str::slug($course->instructor->name)]) }}">{{ $course->instructor->name }}</a>
                                        </p>
                                        <div class="courses__item-bottom">
                                            @if (in_array($course->id, session('enrollments') ?? []))
                                                <div class="button">
                                                    <a href="{{ route('student.enrolled-courses') }}"
                                                        class="already-enrolled-btn" data-id="">
                                                        <span class="text">{{ __('Enrolled') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @elseif ($course->enrollments_count >= $course->capacity && $course->capacity != null)
                                                <div class="button">
                                                    <a href="javascript:;" class=""
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Booked') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="button d-flex flex-column gap-2">
                                                    <a href="javascript:;" class="add-to-cart purchase-btn purchase-btn--cart"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Add To Cart') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                    <a href="javascript:;" class="buy-now purchase-btn purchase-btn--buy"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Buy Now') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            @if ($course->price == 0)
                                                <h4 class="price">{{ __('Free') }}</h4>
                                            @elseif ($course->price > 0 && $course->discount > 0)
                                                <h4 class="price">{{ currency($course->discount) }}</h4>
                                            @else
                                                <h4 class="price">{{ currency($course->price) }}</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="courses__nav">
                    <div class="courses-button-prev"><i class="flaticon-arrow-right"></i></div>
                    <div class="courses-button-next"><i class="flaticon-arrow-right"></i></div>
                </div>
            </div>

            <div class="tab-pane fade" id="business-tab-pane" role="tabpanel" aria-labelledby="business-tab-pane"
                tabindex="0">
                <div class="swiper courses-swiper-active">
                    <div class="swiper-wrapper">
                        @foreach ($coursesByCategory['categoryTwo'] ?? [] as $course)
                            <div class="swiper-slide">
                                <div class="courses__item shine__animate-item">
                                    <div class="courses__item-thumb">
                                        <a href="{{ route('course.show', $course->slug) }}"
                                            class="shine__animate-link">
                                            <img src="{{ asset($course->thumbnail) }}" alt="img">
                                        </a>
                                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                            data-slug="{{ $course?->slug }}">
                                            <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="courses__item-content">
                                        <ul class="courses__item-meta list-wrap">
                                            <li class="courses__item-tag">
                                                <a
                                                    href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                            </li>
                                            <li class="avg-rating"><i class="fas fa-star"></i>
                                                {{ number_format($course->avg_rating, 1) ?? 0 }}
                                            </li>
                                        </ul>
                                        <h3 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                        </h3>
                                        <p class="author">{{ __('By') }} <a
                                                href="{{ route('instructor-details', ['id' => $course->instructor->id, 'slug' => Str::slug($course->instructor->name)]) }}">{{ $course->instructor->name }}</a>
                                        </p>
                                        <div class="courses__item-bottom">
                                            @if (in_array($course->id, session('enrollments') ?? []))
                                                <div class="button">
                                                    <a href="{{ route('student.enrolled-courses') }}"
                                                        class="already-enrolled-btn" data-id="">
                                                        <span class="text">{{ __('Enrolled') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @elseif ($course->enrollments_count >= $course->capacity && $course->capacity != null)
                                                <div class="button">
                                                    <a href="javascript:;" class=""
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Booked') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="button d-flex flex-column gap-2">
                                                    <a href="javascript:;" class="add-to-cart purchase-btn purchase-btn--cart"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Add To Cart') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                    <a href="javascript:;" class="buy-now purchase-btn purchase-btn--buy"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Buy Now') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            @if ($course->price == 0)
                                                <h4 class="price">{{ __('Free') }}</h4>
                                            @elseif ($course->price > 0 && $course->discount > 0)
                                                <h4 class="price">{{ currency($course->discount) }}</h4>
                                            @else
                                                <h4 class="price">{{ currency($course->price) }}</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="courses__nav">
                    <div class="courses-button-prev"><i class="flaticon-arrow-right"></i></div>
                    <div class="courses-button-next"><i class="flaticon-arrow-right"></i></div>
                </div>
            </div>

            <div class="tab-pane fade" id="development-tab-pane" role="tabpanel"
                aria-labelledby="development-tab-pane" tabindex="0">
                <div class="swiper courses-swiper-active">
                    <div class="swiper-wrapper">
                        @foreach ($coursesByCategory['categoryThree'] ?? [] as $course)
                            <div class="swiper-slide">
                                <div class="courses__item shine__animate-item">
                                    <div class="courses__item-thumb">
                                        <a href="{{ route('course.show', $course->slug) }}"
                                            class="shine__animate-link">
                                            <img src="{{ asset($course->thumbnail) }}" alt="img">
                                        </a>
                                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                            data-slug="{{ $course?->slug }}">
                                            <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="courses__item-content">
                                        <ul class="courses__item-meta list-wrap">
                                            <li class="courses__item-tag">
                                                <a
                                                    href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                            </li>
                                            <li class="avg-rating"><i class="fas fa-star"></i>
                                                {{ number_format($course->avg_rating, 1) ?? 0 }}
                                            </li>
                                        </ul>
                                        <h3 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                        </h3>
                                        <p class="author">{{ __('By') }} <a
                                                href="{{ route('instructor-details', ['id' => $course->instructor->id, 'slug' => Str::slug($course->instructor->name)]) }}">{{ $course->instructor->name }}</a>
                                        </p>
                                        <div class="courses__item-bottom">
                                            @if (in_array($course->id, session('enrollments') ?? []))
                                                <div class="button">
                                                    <a href="{{ route('student.enrolled-courses') }}"
                                                        class="already-enrolled-btn" data-id="">
                                                        <span class="text">{{ __('Enrolled') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @elseif ($course->enrollments_count >= $course->capacity && $course->capacity != null)
                                                <div class="button">
                                                    <a href="javascript:;" class=""
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Booked') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="button d-flex flex-column gap-2">
                                                    <a href="javascript:;" class="add-to-cart purchase-btn purchase-btn--cart"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Add To Cart') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                    <a href="javascript:;" class="buy-now purchase-btn purchase-btn--buy"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Buy Now') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            @if ($course->price == 0)
                                                <h4 class="price">{{ __('Free') }}</h4>
                                            @elseif ($course->price > 0 && $course->discount > 0)
                                                <h4 class="price">{{ currency($course->discount) }}</h4>
                                            @else
                                                <h4 class="price">{{ currency($course->price) }}</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="courses__nav">
                    <div class="courses-button-prev"><i class="flaticon-arrow-right"></i></div>
                    <div class="courses-button-next"><i class="flaticon-arrow-right"></i></div>
                </div>
            </div>

            <div class="tab-pane fade" id="categoryFour-tab-pane" role="tabpanel"
                aria-labelledby="categoryFour-tab-pane" tabindex="0">
                <div class="swiper courses-swiper-active">
                    <div class="swiper-wrapper">
                        @foreach ($coursesByCategory['categoryFour'] ?? [] as $course)
                            <div class="swiper-slide">
                                <div class="courses__item shine__animate-item">
                                    <div class="courses__item-thumb">
                                        <a href="{{ route('course.show', $course->slug) }}"
                                            class="shine__animate-link">
                                            <img src="{{ asset($course->thumbnail) }}" alt="img">
                                        </a>
                                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                            data-slug="{{ $course?->slug }}">
                                            <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="courses__item-content">
                                        <ul class="courses__item-meta list-wrap">
                                            <li class="courses__item-tag">
                                                <a
                                                    href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                            </li>
                                            <li class="avg-rating"><i class="fas fa-star"></i>
                                                {{ number_format($course->avg_rating, 1) ?? 0 }}
                                            </li>
                                        </ul>
                                        <h3 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                        </h3>
                                        <p class="author">{{ __('By') }} <a
                                                href="{{ route('instructor-details', ['id' => $course->instructor->id, 'slug' => Str::slug($course->instructor->name)]) }}">{{ $course->instructor->name }}</a>
                                        </p>
                                        <div class="courses__item-bottom">
                                            @if (in_array($course->id, session('enrollments') ?? []))
                                                <div class="button">
                                                    <a href="{{ route('student.enrolled-courses') }}"
                                                        class="already-enrolled-btn" data-id="">
                                                        <span class="text">{{ __('Enrolled') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @elseif ($course->enrollments_count >= $course->capacity && $course->capacity != null)
                                                <div class="button">
                                                    <a href="javascript:;" class=""
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Booked') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="button d-flex flex-column gap-2">
                                                    <a href="javascript:;" class="add-to-cart purchase-btn purchase-btn--cart"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Add To Cart') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                    <a href="javascript:;" class="buy-now purchase-btn purchase-btn--buy"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Buy Now') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            @if ($course->price == 0)
                                                <h4 class="price">{{ __('Free') }}</h4>
                                            @elseif ($course->price > 0 && $course->discount > 0)
                                                <h4 class="price">{{ currency($course->discount) }}</h4>
                                            @else
                                                <h4 class="price">{{ currency($course->price) }}</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="courses__nav">
                    <div class="courses-button-prev"><i class="flaticon-arrow-right"></i></div>
                    <div class="courses-button-next"><i class="flaticon-arrow-right"></i></div>
                </div>
            </div>

            <div class="tab-pane fade" id="categoryFive-tab-pane" role="tabpanel"
                aria-labelledby="categoryFive-tab-pane" tabindex="0">
                <div class="swiper courses-swiper-active">
                    <div class="swiper-wrapper">
                        @foreach ($coursesByCategory['categoryFive'] ?? [] as $course)
                            <div class="swiper-slide">
                                <div class="courses__item shine__animate-item">
                                    <div class="courses__item-thumb">
                                        <a href="{{ route('course.show', $course->slug) }}"
                                            class="shine__animate-link">
                                            <img src="{{ asset($course->thumbnail) }}" alt="img">
                                        </a>
                                        <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                            data-slug="{{ $course?->slug }}">
                                            <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                        </a>
                                    </div>
                                    <div class="courses__item-content">
                                        <ul class="courses__item-meta list-wrap">
                                            <li class="courses__item-tag">
                                                <a
                                                    href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                            </li>
                                            <li class="avg-rating"><i class="fas fa-star"></i>
                                                {{ number_format($course->avg_rating, 1) ?? 0 }}
                                            </li>
                                        </ul>
                                        <h3 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                        </h3>
                                        <p class="author">{{ __('By') }} <a
                                                href="{{ route('instructor-details', ['id' => $course->instructor->id, 'slug' => Str::slug($course->instructor->name)]) }}">{{ $course->instructor->name }}</a>
                                        </p>
                                        <div class="courses__item-bottom">
                                            @if (in_array($course->id, session('enrollments') ?? []))
                                                <div class="button">
                                                    <a href="{{ route('student.enrolled-courses') }}"
                                                        class="already-enrolled-btn" data-id="">
                                                        <span class="text">{{ __('Enrolled') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @elseif ($course->enrollments_count >= $course->capacity && $course->capacity != null)
                                                <div class="button">
                                                    <a href="javascript:;" class=""
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Booked') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="button d-flex flex-column gap-2">
                                                    <a href="javascript:;" class="add-to-cart purchase-btn purchase-btn--cart"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Add To Cart') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                    <a href="javascript:;" class="buy-now purchase-btn purchase-btn--buy"
                                                        data-id="{{ $course->id }}">
                                                        <span class="text">{{ __('Buy Now') }}</span>
                                                        <i class="flaticon-arrow-right"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            @if ($course->price == 0)
                                                <h4 class="price">{{ __('Free') }}</h4>
                                            @elseif ($course->price > 0 && $course->discount > 0)
                                                <h4 class="price">{{ currency($course->discount) }}</h4>
                                            @else
                                                <h4 class="price">{{ currency($course->price) }}</h4>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="courses__nav">
                    <div class="courses-button-prev"><i class="flaticon-arrow-right"></i></div>
                    <div class="courses-button-next"><i class="flaticon-arrow-right"></i></div>
                </div>
            </div>

        </div>
    </div>
</section>
