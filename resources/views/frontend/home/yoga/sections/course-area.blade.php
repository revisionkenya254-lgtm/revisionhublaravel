<section class="courses-area-three youga_course_area section-pt-140 section-pb-110 courses__bg-two"
    data-background="{{ asset('frontend/img/bg/h4_courses_bg.jpg') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-8 col-lg-10 col-md-12">
                <div class="section__title text-center mb-50">
                    <span class="sub-title">{{ __('Top Class Courses') }}</span>
                    <h2 class="title">{{ __('My special yoga classes to improve yourself') }}</h2>
                </div>
                <div class="courses__nav-two mb-50">
                    <ul class="nav nav-tabs" id="courseTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab"
                                data-bs-target="#all-tab-pane" type="button" role="tab"
                                aria-controls="all-tab-pane" aria-selected="true">
                                {{ __('All') }}
                            </button>
                        </li>
                        @if ($featuredCourse?->category_one_status == 1 && isset($categoryData[$featuredCourse->category_one]))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="chinese-tab" data-bs-toggle="tab"
                                    data-bs-target="#chinese-tab-pane" type="button" role="tab"
                                    aria-controls="chinese-tab-pane" aria-selected="false">
                                    {{ $categoryData[$featuredCourse->category_one]->translation_name ?? $categoryData[$featuredCourse->category_one]->name ?? '' }}
                                </button>
                            </li>
                        @endif
                        @if ($featuredCourse?->category_two_status == 1 && isset($categoryData[$featuredCourse->category_two]))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="dessert-tab" data-bs-toggle="tab"
                                    data-bs-target="#dessert-tab-pane" type="button" role="tab"
                                    aria-controls="dessert-tab-pane" aria-selected="false">
                                    {{ $categoryData[$featuredCourse->category_two]->translation_name ?? $categoryData[$featuredCourse->category_two]->name ?? '' }}
                                </button>
                            </li>
                        @endif
                        @if ($featuredCourse?->category_three_status == 1 && isset($categoryData[$featuredCourse->category_three]))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="italian-tab" data-bs-toggle="tab"
                                    data-bs-target="#italian-tab-pane" type="button" role="tab"
                                    aria-controls="italian-tab-pane" aria-selected="false">
                                    {{ $categoryData[$featuredCourse->category_three]->translation_name ?? $categoryData[$featuredCourse->category_three]->name ?? '' }}
                                </button>
                            </li>
                        @endif
                        @if ($featuredCourse?->category_four_status == 1 && isset($categoryData[$featuredCourse->category_four]))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pizza-tab" data-bs-toggle="tab"
                                    data-bs-target="#pizza-tab-pane" type="button" role="tab"
                                    aria-controls="pizza-tab-pane" aria-selected="false">
                                    {{ $categoryData[$featuredCourse->category_four]->translation_name ?? $categoryData[$featuredCourse->category_four]->name ?? '' }}
                                </button>
                            </li>
                        @endif
                        @if ($featuredCourse?->category_five_status == 1 && isset($categoryData[$featuredCourse->category_five]))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="development-tab" data-bs-toggle="tab"
                                    data-bs-target="#development-tab-pane" type="button" role="tab"
                                    aria-controls="development-tab-pane" aria-selected="false">
                                    {{ $categoryData[$featuredCourse->category_five]->translation_name ?? $categoryData[$featuredCourse->category_five]->name ?? '' }}
                                </button>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                tabindex="0">
                <div class="row gutter-24 justify-content-center">
                    @foreach ($coursesByCategory['all'] ?? [] as $course)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="courses__item-five shine__animate-item">
                                <div class="courses__item-thumb-four shine__animate-link">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                    @if ($course->price == 0)
                                        <span class="courses__price-two">{{ __('Free') }}</span>
                                    @elseif ($course->price > 0 && $course->discount > 0)
                                        <span class="courses__price-two">{{ defaultCurrency($course->discount) }}</span>
                                    @else
                                        <span class="courses__price-two">{{ defaultCurrency($course->price) }}</span>
                                    @endif
                                    <a  href="javascript:;" class="wsus-wishlist-btn courses__wishlist-two"  aria-label="WishList" data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                </div>
                                <div class="courses__item-content-four">
                                    <ul class="courses__item-meta list-wrap">
                                        <li class="courses__item-tag courses__item-tag-two">
                                            <a
                                                href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                        </li>
                                        <li class="avg-rating"><i class="fas fa-star"></i>
                                            ({{ number_format($course->avg_rating, 1) ?? 0 }}
                                            {{ __('Reviews') }})
                                        </li>
                                    </ul>
                                    <h2 class="title"><a
                                            href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                    </h2>
                                    <div class="courses__item-bottom-three">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                                {{ $course?->lessons_count }}</li>
                                            <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                                {{ $course?->enrollments_count }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="tab-pane fade" id="chinese-tab-pane" role="tabpanel" aria-labelledby="chinese-tab"
                tabindex="0">
                <div class="row justify-content-center">
                    @foreach ($coursesByCategory['categoryOne'] ?? [] as $course)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="courses__item-five shine__animate-item">
                                <div class="courses__item-thumb-four shine__animate-link">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                    @if ($course->price == 0)
                                        <span class="courses__price-two">{{ __('Free') }}</span>
                                    @elseif ($course->price > 0 && $course->discount > 0)
                                        <span class="courses__price-two">{{ defaultCurrency($course->discount) }}</span>
                                    @else
                                        <span class="courses__price-two">{{ defaultCurrency($course->price) }}</span>
                                    @endif
                                    <a  href="javascript:;" class="wsus-wishlist-btn courses__wishlist-two"  aria-label="WishList" data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                </div>
                                <div class="courses__item-content-four">
                                    <ul class="courses__item-meta list-wrap">
                                        <li class="courses__item-tag courses__item-tag-two">
                                            <a
                                                href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                        </li>
                                        <li class="avg-rating"><i class="fas fa-star"></i>
                                            ({{ number_format($course->avg_rating, 1) ?? 0 }}
                                            {{ __('Reviews') }})
                                        </li>
                                    </ul>
                                    <h2 class="title"><a
                                            href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                    </h2>
                                    <div class="courses__item-bottom-three">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                                {{ $course?->lessons_count }}</li>
                                            <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                                {{ $course?->enrollments_count }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="tab-pane fade" id="dessert-tab-pane" role="tabpanel" aria-labelledby="dessert-tab"
                tabindex="0">
                <div class="row justify-content-center">
                    @foreach ($coursesByCategory['categoryTwo'] ?? [] as $course)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="courses__item-five shine__animate-item">
                                <div class="courses__item-thumb-four shine__animate-link">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                    @if ($course->price == 0)
                                        <span class="courses__price-two">{{ __('Free') }}</span>
                                    @elseif ($course->price > 0 && $course->discount > 0)
                                        <span class="courses__price-two">{{ defaultCurrency($course->discount) }}</span>
                                    @else
                                        <span class="courses__price-two">{{ defaultCurrency($course->price) }}</span>
                                    @endif
                                    <a  href="javascript:;" class="wsus-wishlist-btn courses__wishlist-two"  aria-label="WishList" data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                </div>
                                <div class="courses__item-content-four">
                                    <ul class="courses__item-meta list-wrap">
                                        <li class="courses__item-tag courses__item-tag-two">
                                            <a
                                                href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                        </li>
                                        <li class="avg-rating"><i class="fas fa-star"></i>
                                            ({{ number_format($course->avg_rating, 1) ?? 0 }}
                                            {{ __('Reviews') }})
                                        </li>
                                    </ul>
                                    <h2 class="title"><a
                                            href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                    </h2>
                                    <div class="courses__item-bottom-three">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                                {{ $course?->lessons_count }}</li>
                                            <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                                {{ $course?->enrollments_count }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="tab-pane fade" id="italian-tab-pane" role="tabpanel" aria-labelledby="italian-tab"
                tabindex="0">
                <div class="row justify-content-center">
                    @foreach ($coursesByCategory['categoryThree'] ?? [] as $course)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="courses__item-five shine__animate-item">
                                <div class="courses__item-thumb-four shine__animate-link">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                    @if ($course->price == 0)
                                        <span class="courses__price-two">{{ __('Free') }}</span>
                                    @elseif ($course->price > 0 && $course->discount > 0)
                                        <span class="courses__price-two">{{ defaultCurrency($course->discount) }}</span>
                                    @else
                                        <span class="courses__price-two">{{ defaultCurrency($course->price) }}</span>
                                    @endif
                                    <a  href="javascript:;" class="wsus-wishlist-btn courses__wishlist-two"  aria-label="WishList" data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                </div>
                                <div class="courses__item-content-four">
                                    <ul class="courses__item-meta list-wrap">
                                        <li class="courses__item-tag courses__item-tag-two">
                                            <a
                                                href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                        </li>
                                        <li class="avg-rating"><i class="fas fa-star"></i>
                                            ({{ number_format($course->avg_rating, 1) ?? 0 }}
                                            {{ __('Reviews') }})
                                        </li>
                                    </ul>
                                    <h2 class="title"><a
                                            href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                    </h2>
                                    <div class="courses__item-bottom-three">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                                {{ $course?->lessons_count }}</li>
                                            <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                                {{ $course?->enrollments_count }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="tab-pane fade" id="pizza-tab-pane" role="tabpanel" aria-labelledby="pizza-tab"
                tabindex="0">
                <div class="row justify-content-center">
                    @foreach ($coursesByCategory['categoryFour'] ?? [] as $course)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="courses__item-five shine__animate-item">
                                <div class="courses__item-thumb-four shine__animate-link">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                    @if ($course->price == 0)
                                        <span class="courses__price-two">{{ __('Free') }}</span>
                                    @elseif ($course->price > 0 && $course->discount > 0)
                                        <span class="courses__price-two">{{ defaultCurrency($course->discount) }}</span>
                                    @else
                                        <span class="courses__price-two">{{ defaultCurrency($course->price) }}</span>
                                    @endif
                                    <a  href="javascript:;" class="wsus-wishlist-btn courses__wishlist-two"  aria-label="WishList" data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                </div>
                                <div class="courses__item-content-four">
                                    <ul class="courses__item-meta list-wrap">
                                        <li class="courses__item-tag courses__item-tag-two">
                                            <a
                                                href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                        </li>
                                        <li class="avg-rating"><i class="fas fa-star"></i>
                                            ({{ number_format($course->avg_rating, 1) ?? 0 }}
                                            {{ __('Reviews') }})
                                        </li>
                                    </ul>
                                    <h2 class="title"><a
                                            href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                    </h2>
                                    <div class="courses__item-bottom-three">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                                {{ $course?->lessons_count }}</li>
                                            <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                                {{ $course?->enrollments_count }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="tab-pane fade" id="development-tab-pane" role="tabpanel" aria-labelledby="development-tab"
                tabindex="0">
                <div class="row justify-content-center">
                    @foreach ($coursesByCategory['categoryFive'] ?? [] as $course)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="courses__item-five shine__animate-item">
                                <div class="courses__item-thumb-four shine__animate-link">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="img"></a>
                                    @if ($course->price == 0)
                                        <span class="courses__price-two">{{ __('Free') }}</span>
                                    @elseif ($course->price > 0 && $course->discount > 0)
                                        <span class="courses__price-two">{{ defaultCurrency($course->discount) }}</span>
                                    @else
                                        <span class="courses__price-two">{{ defaultCurrency($course->price) }}</span>
                                    @endif
                                    <a  href="javascript:;" class="wsus-wishlist-btn courses__wishlist-two"  aria-label="WishList" data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                </div>
                                <div class="courses__item-content-four">
                                    <ul class="courses__item-meta list-wrap">
                                        <li class="courses__item-tag courses__item-tag-two">
                                            <a
                                                href="{{ route('courses', ['category' => $course->category_id]) }}">{{ $course->category_translation_name }}</a>
                                        </li>
                                        <li class="avg-rating"><i class="fas fa-star"></i>
                                            ({{ number_format($course->avg_rating, 1) ?? 0 }}
                                            {{ __('Reviews') }})
                                        </li>
                                    </ul>
                                    <h2 class="title"><a
                                            href="{{ route('course.show', $course->slug) }}">{{ truncate($course->title, 50) }}</a>
                                    </h2>
                                    <div class="courses__item-bottom-three">
                                        <ul class="list-wrap">
                                            <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                                {{ $course?->lessons_count }}</li>
                                            <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                                {{ $course?->enrollments_count }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="discover-courses-btn-two text-center mt-30">
                <a href="{{ route('courses') }}" class="btn arrow-btn btn-four">{{ __('Discover All Class') }} <img
                        src="{{ asset('frontend/img/icons/right_arrow.svg') }}" alt=""
                        class="injectable"></a>
            </div>
        </div>
    </div>
    <div class="courses__shape-wrap-two">
        <img src="{{ asset('frontend/img/others/h4_course_shape.svg') }}" alt="shape" class="rotateme">
        <img src="{{ asset('frontend/img/others/h4_course_shape.svg') }}" alt="shape" class="rotateme">
    </div>
</section>
