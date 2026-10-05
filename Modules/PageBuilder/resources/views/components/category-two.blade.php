@foreach (getCategories() as $category)
    <div class="col-lg-3 col-sm-6">
        <div class="categories__item-three">
            <a href="{{ route('courses', ['main_category' => $category->slug]) }}">
                <div class="icon">
                    <img src="{{ asset($category->icon) }}" alt="">
                </div>
                <span class="name">{{ $category?->name }}</span>
                <span class="courses">{{ $category->subCategories->sum('courses_count') }}
                    {{ __('Courses') }}</span>
            </a>
        </div>
    </div>
@endforeach
