<section class="home_business courses-area-six grey-bg-two">
    <div class="container">
        <div class="section__title-wrap">
            <div class="row justify-content-center">
                <div class="section__title text-center mb-50">
                    <span class="sub-title">{{ __('10,000+ unique online courses') }}</span>
                    <h2 class="title bold">{{ __('Our Most Popular Courses') }}</h2>
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
        <div class="tab-content " id="courseTabContent">
            <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                tabindex="0">
                <div class="swiper courses-swiper-active">
                    <div class="swiper-wrapper">
                        @foreach ($coursesByCategory['all'] ?? [] as $course)
                            <div class="swiper-slide">
                                <div class="courses__item-eight shine__animate-item">
                                    <div class="courses__item-thumb-seven shine__animate-link">
                                        <a href="{{ route('course.show', $course->slug) }}"><img src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                        <a href="{{ route('courses', ['category' => $course->category_id]) }}" class="courses__item-tag-three">{{ $course->category?->translation_name }}</a>
                                    </div>
                                    <div class="courses__item-content-seven">
                                        <ul class="courses__item-meta list-wrap">
                                            @if ($course->price == 0)
                                            <li class="price">{{ __('Free') }}</li>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <li class="price">{{ defaultCurrency($course->discount) }}</li>
                                        @else
                                        <li class="price">{{ defaultCurrency($course->price) }}</li>
                                        @endif
                                        <li class="courses__wishlist">
                                            <a  href="javascript:;" class="wsus-wishlist-btn" aria-label="WishList" data-slug="{{ $course?->slug }}">
                                                <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                            </a>
                                        </li>
                                        </ul>
                                        <h2 class="title"><a href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a></h2>
                                        <div class="courses__review">
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <span>({{ number_format($course->avg_rating, 1) ?? 0 }} {{__('Reviews')}})</span>
                                        </div>
                                    </div>
                                    <div class="courses__item-bottom-three courses__item-bottom-five">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{__('Lessons')}} {{ $course?->lessons_count }}</li>
                                            <li><i class="skillgro-group"></i>{{__('Students')}} {{ $course?->enrollments_count }}</li>
                                        </ul>
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
                                <div class="courses__item-eight shine__animate-item">
                                    <div class="courses__item-thumb-seven shine__animate-link">
                                        <a href="{{ route('course.show', $course->slug) }}"><img src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                        <a href="{{ route('courses', ['category' => $course->category_id]) }}" class="courses__item-tag-three">{{ $course->category?->translation_name }}</a>
                                    </div>
                                    <div class="courses__item-content-seven">
                                        <ul class="courses__item-meta list-wrap">
                                            @if ($course->price == 0)
                                            <li class="price">{{ __('Free') }}</li>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <li class="price">{{ defaultCurrency($course->discount) }}</li>
                                        @else
                                        <li class="price">{{ defaultCurrency($course->price) }}</li>
                                        @endif
                                        <li class="courses__wishlist">
                                            <a  href="javascript:;" class="wsus-wishlist-btn" aria-label="WishList" data-slug="{{ $course?->slug }}">
                                                <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                            </a>
                                        </li>
                                        </ul>
                                        <h2 class="title"><a href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a></h2>
                                        <div class="courses__review">
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <span>({{ number_format($course->avg_rating, 1) ?? 0 }} {{__('Reviews')}})</span>
                                        </div>
                                    </div>
                                    <div class="courses__item-bottom-three courses__item-bottom-five">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{__('Lessons')}} {{ $course?->lessons_count }}</li>
                                            <li><i class="skillgro-group"></i>{{__('Students')}} {{ $course?->enrollments_count }}</li>
                                        </ul>
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
                                <div class="courses__item-eight shine__animate-item">
                                    <div class="courses__item-thumb-seven shine__animate-link">
                                        <a href="{{ route('course.show', $course->slug) }}"><img src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                        <a href="{{ route('courses', ['category' => $course->category_id]) }}" class="courses__item-tag-three">{{ $course->category?->translation_name }}</a>
                                    </div>
                                    <div class="courses__item-content-seven">
                                        <ul class="courses__item-meta list-wrap">
                                            @if ($course->price == 0)
                                            <li class="price">{{ __('Free') }}</li>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <li class="price">{{ defaultCurrency($course->discount) }}</li>
                                        @else
                                        <li class="price">{{ defaultCurrency($course->price) }}</li>
                                        @endif
                                        <li class="courses__wishlist">
                                            <a  href="javascript:;" class="wsus-wishlist-btn" aria-label="WishList" data-slug="{{ $course?->slug }}">
                                                <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                            </a>
                                        </li>
                                        </ul>
                                        <h2 class="title"><a href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a></h2>
                                        <div class="courses__review">
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <span>({{ number_format($course->avg_rating, 1) ?? 0 }} {{__('Reviews')}})</span>
                                        </div>
                                    </div>
                                    <div class="courses__item-bottom-three courses__item-bottom-five">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{__('Lessons')}} {{ $course?->lessons_count }}</li>
                                            <li><i class="skillgro-group"></i>{{__('Students')}} {{ $course?->enrollments_count }}</li>
                                        </ul>
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
                                <div class="courses__item-eight shine__animate-item">
                                    <div class="courses__item-thumb-seven shine__animate-link">
                                        <a href="{{ route('course.show', $course->slug) }}"><img src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                        <a href="{{ route('courses', ['category' => $course->category_id]) }}" class="courses__item-tag-three">{{ $course->category?->translation_name }}</a>
                                    </div>
                                    <div class="courses__item-content-seven">
                                        <ul class="courses__item-meta list-wrap">
                                            @if ($course->price == 0)
                                            <li class="price">{{ __('Free') }}</li>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <li class="price">{{ defaultCurrency($course->discount) }}</li>
                                        @else
                                        <li class="price">{{ defaultCurrency($course->price) }}</li>
                                        @endif
                                        <li class="courses__wishlist">
                                            <a  href="javascript:;" class="wsus-wishlist-btn" aria-label="WishList" data-slug="{{ $course?->slug }}">
                                                <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                            </a>
                                        </li>
                                        </ul>
                                        <h2 class="title"><a href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a></h2>
                                        <div class="courses__review">
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <span>({{ number_format($course->avg_rating, 1) ?? 0 }} {{__('Reviews')}})</span>
                                        </div>
                                    </div>
                                    <div class="courses__item-bottom-three courses__item-bottom-five">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{__('Lessons')}} {{ $course?->lessons_count }}</li>
                                            <li><i class="skillgro-group"></i>{{__('Students')}} {{ $course?->enrollments_count }}</li>
                                        </ul>
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
                                <div class="courses__item-eight shine__animate-item">
                                    <div class="courses__item-thumb-seven shine__animate-link">
                                        <a href="{{ route('course.show', $course->slug) }}"><img src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                        <a href="{{ route('courses', ['category' => $course->category_id]) }}" class="courses__item-tag-three">{{ $course->category?->translation_name }}</a>
                                    </div>
                                    <div class="courses__item-content-seven">
                                        <ul class="courses__item-meta list-wrap">
                                            @if ($course->price == 0)
                                            <li class="price">{{ __('Free') }}</li>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <li class="price">{{ defaultCurrency($course->discount) }}</li>
                                        @else
                                        <li class="price">{{ defaultCurrency($course->price) }}</li>
                                        @endif
                                        <li class="courses__wishlist">
                                            <a  href="javascript:;" class="wsus-wishlist-btn" aria-label="WishList" data-slug="{{ $course?->slug }}">
                                                <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                            </a>
                                        </li>
                                        </ul>
                                        <h2 class="title"><a href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a></h2>
                                        <div class="courses__review">
                                            <div class="rating">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <span>({{ number_format($course->avg_rating, 1) ?? 0 }} {{__('Reviews')}})</span>
                                        </div>
                                    </div>
                                    <div class="courses__item-bottom-three courses__item-bottom-five">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{__('Lessons')}} {{ $course?->lessons_count }}</li>
                                            <li><i class="skillgro-group"></i>{{__('Students')}} {{ $course?->enrollments_count }}</li>
                                        </ul>
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
                            <div class="courses__item-eight shine__animate-item">
                                <div class="courses__item-thumb-seven shine__animate-link">
                                    <a href="{{ route('course.show', $course->slug) }}"><img src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                    <a href="{{ route('courses', ['category' => $course->category_id]) }}" class="courses__item-tag-three">{{ $course->category?->translation_name }}</a>
                                </div>
                                <div class="courses__item-content-seven">
                                    <ul class="courses__item-meta list-wrap">
                                        @if ($course->price == 0)
                                        <li class="price">{{ __('Free') }}</li>
                                    @elseif ($course->price > 0 && $course->discount > 0)
                                        <li class="price">{{ defaultCurrency($course->discount) }}</li>
                                    @else
                                    <li class="price">{{ defaultCurrency($course->price) }}</li>
                                    @endif
                                    <li class="courses__wishlist">
                                        <a  href="javascript:;" class="wsus-wishlist-btn" aria-label="WishList" data-slug="{{ $course?->slug }}">
                                            <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                        </a>
                                    </li>
                                    </ul>
                                    <h2 class="title"><a href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a></h2>
                                    <div class="courses__review">
                                        <div class="rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <span>({{ number_format($course->avg_rating, 1) ?? 0 }} {{__('Reviews')}})</span>
                                    </div>
                                </div>
                                <div class="courses__item-bottom-three courses__item-bottom-five">
                                    <ul class="list-wrap">
                                        <li><i class="flaticon-book"></i>{{__('Lessons')}} {{ $course?->lessons_count }}</li>
                                        <li><i class="skillgro-group"></i>{{__('Students')}} {{ $course?->enrollments_count }}</li>
                                    </ul>
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
        <div class="discover-courses-btn text-center mt-30">
            <a href="{{ route('courses') }}" class="btn arrow-btn">{{ __('See All Courses') }} <img src="{{ asset('frontend/img/icons/right_arrow.svg') }}" alt="" class="injectable"></a>
        </div>
    </div>
</section>