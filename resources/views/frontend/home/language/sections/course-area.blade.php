<section class="courses-area-five section-py-140 courses__bg-four"
    data-background="{{ asset('frontend/img/bg/h6_courses_bg.jpg') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8 col-xxl-6">
                <div class="section__title text-center mb-50">
                    <span class="sub-title">{{ __('Top Class Courses') }}</span>
                    <h2 class="title bold">{{ __('Get The Best Exciting Class Experience With us') }}</h2>
                    <p class="desc">{{ __('Check out the most demanding courses right now') }}</p>
                </div>
            </div>
            <div class="col-xxl-10 col-xl-9 col-lg-10">
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
                <div class="row justify-content-center">
                    @foreach ($coursesByCategory['all'] ?? [] as $course)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="courses__item-seven">
                                <div class="courses__item-thumb-six">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="{{ $course->title }}"></a>
                                    <a href="javascript:;" class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                        data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                    <a href="{{ route('courses', ['category' => $course->category_id]) }}"
                                        class="courses__item-tag-three">{{ $course->category_translation_name }}</a>
                                    <div class="courses__review">
                                        <div class="rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= floor($course->avg_rating))
                                                    <i class="fas fa-solid fa-star"></i>
                                                @elseif ($i - 0.5 <= $course->avg_rating)
                                                    <i class="fas fa-solid fa-star-half-alt"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <span>({{ number_format($course->avg_rating, 1) ?? 0 }})</span>
                                    </div>
                                </div>
                                <div class="courses__item-content-six">
                                    <div class="courses__item-content-six-top">
                                        <h2 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ $course?->instructor?->name }}</a>
                                        </h2>
                                        @if ($course->price == 0)
                                            <h3 class="price">{{ __('Free') }}</h3>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <h3 class="price">{{ defaultCurrency($course->discount) }}</h3>
                                        @else
                                            <h3 class="price">{{ defaultCurrency($course->price) }}</h3>
                                        @endif
                                    </div>
                                    <span>{{ __('Professional Tutor') }}</span>
                                    <ul class="courses__item-meta-two list-wrap">
                                        @foreach ($course->languages as $item)
                                            <li>{{ $item?->language?->name }} <img
                                                    src="{{ asset('frontend/img/icons/graph.svg') }}" alt=""
                                                    class="injectable"></li>
                                        @endforeach
                                    </ul>
                                    <p>{{ truncate($course->title, 50) }}</p>
                                </div>
                                <div class="courses__item-bottom-three courses__item-bottom-four">
                                    <ul class="list-wrap">
                                        <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                            {{ $course?->lessons_count }}</li>
                                        <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                            {{ $course?->enrollments_count }}</li>
                                    </ul>
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
                            <div class="courses__item-seven">
                                <div class="courses__item-thumb-six">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="{{ $course->title }}"></a>
                                    <a href="javascript:;"
                                        class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                        data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                    <a href="{{ route('courses', ['category' => $course->category_id]) }}"
                                        class="courses__item-tag-three">{{ $course->category_translation_name }}</a>
                                    <div class="courses__review">
                                        <div class="rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= floor($course->avg_rating))
                                                    <i class="fas fa-solid fa-star"></i>
                                                @elseif ($i - 0.5 <= $course->avg_rating)
                                                    <i class="fas fa-solid fa-star-half-alt"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <span>({{ number_format($course->avg_rating, 1) ?? 0 }})</span>
                                    </div>
                                </div>
                                <div class="courses__item-content-six">
                                    <div class="courses__item-content-six-top">
                                        <h2 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ $course?->instructor?->name }}</a>
                                        </h2>
                                        @if ($course->price == 0)
                                            <h3 class="price">{{ __('Free') }}</h3>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <h3 class="price">{{ defaultCurrency($course->discount) }}</h3>
                                        @else
                                            <h3 class="price">{{ defaultCurrency($course->price) }}</h3>
                                        @endif
                                    </div>
                                    <span>{{ __('Professional Tutor') }}</span>
                                    <ul class="courses__item-meta-two list-wrap">
                                        @foreach ($course->languages as $item)
                                            <li>{{ $item?->language?->name }} <img
                                                    src="{{ asset('frontend/img/icons/graph.svg') }}" alt=""
                                                    class="injectable"></li>
                                        @endforeach
                                    </ul>
                                    <p>{{ truncate($course->title, 50) }}</p>
                                </div>
                                <div class="courses__item-bottom-three courses__item-bottom-four">
                                    <ul class="list-wrap">
                                        <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                            {{ $course?->lessons_count }}</li>
                                        <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                            {{ $course?->enrollments_count }}</li>
                                    </ul>
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
                            <div class="courses__item-seven">
                                <div class="courses__item-thumb-six">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="{{ $course->title }}"></a>
                                    <a href="javascript:;"
                                        class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                        data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                    <a href="{{ route('courses', ['category' => $course->category_id]) }}"
                                        class="courses__item-tag-three">{{ $course->category_translation_name }}</a>
                                    <div class="courses__review">
                                        <div class="rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= floor($course->avg_rating))
                                                    <i class="fas fa-solid fa-star"></i>
                                                @elseif ($i - 0.5 <= $course->avg_rating)
                                                    <i class="fas fa-solid fa-star-half-alt"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <span>({{ number_format($course->avg_rating, 1) ?? 0 }})</span>
                                    </div>
                                </div>
                                <div class="courses__item-content-six">
                                    <div class="courses__item-content-six-top">
                                        <h2 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ $course?->instructor?->name }}</a>
                                        </h2>
                                        @if ($course->price == 0)
                                            <h3 class="price">{{ __('Free') }}</h3>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <h3 class="price">{{ defaultCurrency($course->discount) }}</h3>
                                        @else
                                            <h3 class="price">{{ defaultCurrency($course->price) }}</h3>
                                        @endif
                                    </div>
                                    <span>{{ __('Professional Tutor') }}</span>
                                    <ul class="courses__item-meta-two list-wrap">
                                        @foreach ($course->languages as $item)
                                            <li>{{ $item?->language?->name }} <img
                                                    src="{{ asset('frontend/img/icons/graph.svg') }}" alt=""
                                                    class="injectable"></li>
                                        @endforeach
                                    </ul>
                                    <p>{{ truncate($course->title, 50) }}</p>
                                </div>
                                <div class="courses__item-bottom-three courses__item-bottom-four">
                                    <ul class="list-wrap">
                                        <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                            {{ $course?->lessons_count }}</li>
                                        <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                            {{ $course?->enrollments_count }}</li>
                                    </ul>
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
                            <div class="courses__item-seven">
                                <div class="courses__item-thumb-six">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="{{ $course->title }}"></a>
                                    <a href="javascript:;"
                                        class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                        data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                    <a href="{{ route('courses', ['category' => $course->category_id]) }}"
                                        class="courses__item-tag-three">{{ $course->category_translation_name }}</a>
                                    <div class="courses__review">
                                        <div class="rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= floor($course->avg_rating))
                                                    <i class="fas fa-solid fa-star"></i>
                                                @elseif ($i - 0.5 <= $course->avg_rating)
                                                    <i class="fas fa-solid fa-star-half-alt"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <span>({{ number_format($course->avg_rating, 1) ?? 0 }})</span>
                                    </div>
                                </div>
                                <div class="courses__item-content-six">
                                    <div class="courses__item-content-six-top">
                                        <h2 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ $course?->instructor?->name }}</a>
                                        </h2>
                                        @if ($course->price == 0)
                                            <h3 class="price">{{ __('Free') }}</h3>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <h3 class="price">{{ defaultCurrency($course->discount) }}</h3>
                                        @else
                                            <h3 class="price">{{ defaultCurrency($course->price) }}</h3>
                                        @endif
                                    </div>
                                    <span>{{ __('Professional Tutor') }}</span>
                                    <ul class="courses__item-meta-two list-wrap">
                                        @foreach ($course->languages as $item)
                                            <li>{{ $item?->language?->name }} <img
                                                    src="{{ asset('frontend/img/icons/graph.svg') }}" alt=""
                                                    class="injectable"></li>
                                        @endforeach
                                    </ul>
                                    <p>{{ truncate($course->title, 50) }}</p>
                                </div>
                                <div class="courses__item-bottom-three courses__item-bottom-four">
                                    <ul class="list-wrap">
                                        <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                            {{ $course?->lessons_count }}</li>
                                        <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                            {{ $course?->enrollments_count }}</li>
                                    </ul>
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
                            <div class="courses__item-seven">
                                <div class="courses__item-thumb-six">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="{{ $course->title }}"></a>
                                    <a href="javascript:;"
                                        class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                        data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                    <a href="{{ route('courses', ['category' => $course->category_id]) }}"
                                        class="courses__item-tag-three">{{ $course->category_translation_name }}</a>
                                    <div class="courses__review">
                                        <div class="rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= floor($course->avg_rating))
                                                    <i class="fas fa-solid fa-star"></i>
                                                @elseif ($i - 0.5 <= $course->avg_rating)
                                                    <i class="fas fa-solid fa-star-half-alt"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <span>({{ number_format($course->avg_rating, 1) ?? 0 }})</span>
                                    </div>
                                </div>
                                <div class="courses__item-content-six">
                                    <div class="courses__item-content-six-top">
                                        <h2 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ $course?->instructor?->name }}</a>
                                        </h2>
                                        @if ($course->price == 0)
                                            <h3 class="price">{{ __('Free') }}</h3>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <h3 class="price">{{ defaultCurrency($course->discount) }}</h3>
                                        @else
                                            <h3 class="price">{{ defaultCurrency($course->price) }}</h3>
                                        @endif
                                    </div>
                                    <span>{{ __('Professional Tutor') }}</span>
                                    <ul class="courses__item-meta-two list-wrap">
                                        @foreach ($course->languages as $item)
                                            <li>{{ $item?->language?->name }} <img
                                                    src="{{ asset('frontend/img/icons/graph.svg') }}" alt=""
                                                    class="injectable"></li>
                                        @endforeach
                                    </ul>
                                    <p>{{ truncate($course->title, 50) }}</p>
                                </div>
                                <div class="courses__item-bottom-three courses__item-bottom-four">
                                    <ul class="list-wrap">
                                        <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                            {{ $course?->lessons_count }}</li>
                                        <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                            {{ $course?->enrollments_count }}</li>
                                    </ul>
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
                            <div class="courses__item-seven">
                                <div class="courses__item-thumb-six">
                                    <a href="{{ route('course.show', $course->slug) }}"><img
                                            src="{{ asset($course->thumbnail) }}" alt="{{ $course->title }}"></a>
                                    <a href="javascript:;"
                                        class="wsus-wishlist-btn common-white courses__wishlist-two"  aria-label="WishList"
                                        data-slug="{{ $course?->slug }}">
                                        <i class="{{ $course?->favorite_by_client ? 'fas' : 'far' }} fa-heart"></i>
                                    </a>
                                    <a href="{{ route('courses', ['category' => $course->category_id]) }}"
                                        class="courses__item-tag-three">{{ $course->category_translation_name }}</a>
                                    <div class="courses__review">
                                        <div class="rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= floor($course->avg_rating))
                                                    <i class="fas fa-solid fa-star"></i>
                                                @elseif ($i - 0.5 <= $course->avg_rating)
                                                    <i class="fas fa-solid fa-star-half-alt"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                        </div>
                                        <span>({{ number_format($course->avg_rating, 1) ?? 0 }})</span>
                                    </div>
                                </div>
                                <div class="courses__item-content-six">
                                    <div class="courses__item-content-six-top">
                                        <h2 class="title"><a
                                                href="{{ route('course.show', $course->slug) }}">{{ $course?->instructor?->name }}</a>
                                        </h2>
                                        @if ($course->price == 0)
                                            <h3 class="price">{{ __('Free') }}</h3>
                                        @elseif ($course->price > 0 && $course->discount > 0)
                                            <h3 class="price">{{ defaultCurrency($course->discount) }}</h3>
                                        @else
                                            <h3 class="price">{{ defaultCurrency($course->price) }}</h3>
                                        @endif
                                    </div>
                                    <span>{{ __('Professional Tutor') }}</span>
                                    <ul class="courses__item-meta-two list-wrap">
                                        @foreach ($course->languages as $item)
                                            <li>{{ $item?->language?->name }} <img
                                                    src="{{ asset('frontend/img/icons/graph.svg') }}" alt=""
                                                    class="injectable"></li>
                                        @endforeach
                                    </ul>
                                    <p>{{ truncate($course->title, 50) }}</p>
                                </div>
                                <div class="courses__item-bottom-three courses__item-bottom-four">
                                    <ul class="list-wrap">
                                        <li><i class="flaticon-book"></i>{{ __('Lessons') }}
                                            {{ $course?->lessons_count }}</li>
                                        <li><i class="flaticon-mortarboard"></i>{{ __('Students') }}
                                            {{ $course?->enrollments_count }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="view-all-btn view-all-categories">
            <a href="{{ route('courses') }}"><span>{{ __('See All Courses') }}</span><img
                    src="{{ asset('frontend/img/icons/right_arrow.svg') }}" alt="" class="injectable"></a>
        </div>
    </div>
</section>
