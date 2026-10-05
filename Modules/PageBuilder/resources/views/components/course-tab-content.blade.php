<div class="tab-content" id="courseTabContent">
    <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab" tabindex="0">
        <div class="swiper courses-swiper-active">
            <div class="swiper-wrapper">
                @foreach ($allCourses ?? [] as $course)
                    <div class="swiper-slide">
                        <div class="courses__item shine__animate-item">
                            <div class="courses__item-thumb">
                                <a href="{{ route('course.show', $course->slug) }}" class="shine__animate-link">
                                    <img src="{{ asset($course->thumbnail) }}" alt="img">
                                </a>
                                <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"
                                    aria-label="WishList" data-slug="{{ $course?->slug }}">
                                    <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                </a>
                            </div>
                            <div class="courses__item-content">
                                <ul class="courses__item-meta list-wrap">
                                    <li class="courses__item-tag">
                                        <a
                                            href="{{ route('courses', ['category' => $course->category->id]) }}">{{ $course->category?->name }}</a>
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
                                            <a href="javascript:;" class="" data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Booked') }}</span>
                                                <i class="flaticon-arrow-right"></i>
                                            </a>
                                        </div>
                                    @else
                                        <div class="button">
                                            <a href="javascript:;" class="add-to-cart" data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Add To Cart') }}</span>
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

    <div class="tab-pane fade" id="design-tab-pane" role="tabpanel" aria-labelledby="design-tab-pane" tabindex="0">
        <div class="swiper courses-swiper-active">
            <div class="swiper-wrapper">
                @foreach ($categoryOneCourses ?? [] as $course)
                    <div class="swiper-slide">
                        <div class="courses__item shine__animate-item">
                            <div class="courses__item-thumb">
                                <a href="{{ route('course.show', $course->slug) }}" class="shine__animate-link">
                                    <img src="{{ asset($course->thumbnail) }}" alt="img">
                                </a>
                                <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"
                                    aria-label="WishList" data-slug="{{ $course?->slug }}">
                                    <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                </a>
                            </div>
                            <div class="courses__item-content">
                                <ul class="courses__item-meta list-wrap">
                                    <li class="courses__item-tag">
                                        <a
                                            href="{{ route('courses', ['category' => $course->category->id]) }}">{{ $course->category?->name }}</a>
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
                                            <a href="javascript:;" class="" data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Booked') }}</span>
                                                <i class="flaticon-arrow-right"></i>
                                            </a>
                                        </div>
                                    @else
                                        <div class="button">
                                            <a href="javascript:;" class="add-to-cart"
                                                data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Add To Cart') }}</span>
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
                @foreach ($categoryTwoCourses ?? [] as $course)
                    <div class="swiper-slide">
                        <div class="courses__item shine__animate-item">
                            <div class="courses__item-thumb">
                                <a href="{{ route('course.show', $course->slug) }}" class="shine__animate-link">
                                    <img src="{{ asset($course->thumbnail) }}" alt="img">
                                </a>
                                <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"
                                    aria-label="WishList" data-slug="{{ $course?->slug }}">
                                    <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                </a>
                            </div>
                            <div class="courses__item-content">
                                <ul class="courses__item-meta list-wrap">
                                    <li class="courses__item-tag">
                                        <a
                                            href="{{ route('courses', ['category' => $course->category->id]) }}">{{ $course->category?->name }}</a>
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
                                            <a href="javascript:;" class="" data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Booked') }}</span>
                                                <i class="flaticon-arrow-right"></i>
                                            </a>
                                        </div>
                                    @else
                                        <div class="button">
                                            <a href="javascript:;" class="add-to-cart"
                                                data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Add To Cart') }}</span>
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

    <div class="tab-pane fade" id="development-tab-pane" role="tabpanel" aria-labelledby="development-tab-pane"
        tabindex="0">
        <div class="swiper courses-swiper-active">
            <div class="swiper-wrapper">
                @foreach ($categoryThreeCourses ?? [] as $course)
                    <div class="swiper-slide">
                        <div class="courses__item shine__animate-item">
                            <div class="courses__item-thumb">
                                <a href="{{ route('course.show', $course->slug) }}" class="shine__animate-link">
                                    <img src="{{ asset($course->thumbnail) }}" alt="img">
                                </a>
                                <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"
                                    aria-label="WishList" data-slug="{{ $course?->slug }}">
                                    <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                </a>
                            </div>
                            <div class="courses__item-content">
                                <ul class="courses__item-meta list-wrap">
                                    <li class="courses__item-tag">
                                        <a
                                            href="{{ route('courses', ['category' => $course->category->id]) }}">{{ $course->category?->name }}</a>
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
                                            <a href="javascript:;" class="" data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Booked') }}</span>
                                                <i class="flaticon-arrow-right"></i>
                                            </a>
                                        </div>
                                    @else
                                        <div class="button">
                                            <a href="javascript:;" class="add-to-cart"
                                                data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Add To Cart') }}</span>
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

    <div class="tab-pane fade" id="categoryFour-tab-pane" role="tabpanel" aria-labelledby="categoryFour-tab-pane"
        tabindex="0">
        <div class="swiper courses-swiper-active">
            <div class="swiper-wrapper">
                @foreach ($categoryFourCourses ?? [] as $course)
                    <div class="swiper-slide">
                        <div class="courses__item shine__animate-item">
                            <div class="courses__item-thumb">
                                <a href="{{ route('course.show', $course->slug) }}" class="shine__animate-link">
                                    <img src="{{ asset($course->thumbnail) }}" alt="img">
                                </a>
                                <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"
                                    aria-label="WishList" data-slug="{{ $course?->slug }}">
                                    <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                </a>
                            </div>
                            <div class="courses__item-content">
                                <ul class="courses__item-meta list-wrap">
                                    <li class="courses__item-tag">
                                        <a
                                            href="{{ route('courses', ['category' => $course->category->id]) }}">{{ $course->category?->name }}</a>
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
                                            <a href="javascript:;" class="" data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Booked') }}</span>
                                                <i class="flaticon-arrow-right"></i>
                                            </a>
                                        </div>
                                    @else
                                        <div class="button">
                                            <a href="javascript:;" class="add-to-cart"
                                                data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Add To Cart') }}</span>
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

    <div class="tab-pane fade" id="categoryFive-tab-pane" role="tabpanel" aria-labelledby="categoryFive-tab-pane"
        tabindex="0">
        <div class="swiper courses-swiper-active">
            <div class="swiper-wrapper">
                @foreach ($categoryFiveCourses ?? [] as $course)
                    <div class="swiper-slide">
                        <div class="courses__item shine__animate-item">
                            <div class="courses__item-thumb">
                                <a href="{{ route('course.show', $course->slug) }}" class="shine__animate-link">
                                    <img src="{{ asset($course->thumbnail) }}" alt="img">
                                </a>
                                <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"
                                    aria-label="WishList" data-slug="{{ $course?->slug }}">
                                    <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                </a>
                            </div>
                            <div class="courses__item-content">
                                <ul class="courses__item-meta list-wrap">
                                    <li class="courses__item-tag">
                                        <a
                                            href="{{ route('courses', ['category' => $course->category->id]) }}">{{ $course->category?->name }}</a>
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
                                            <a href="javascript:;" class="" data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Booked') }}</span>
                                                <i class="flaticon-arrow-right"></i>
                                            </a>
                                        </div>
                                    @else
                                        <div class="button">
                                            <a href="javascript:;" class="add-to-cart"
                                                data-id="{{ $course->id }}">
                                                <span class="text">{{ __('Add To Cart') }}</span>
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
