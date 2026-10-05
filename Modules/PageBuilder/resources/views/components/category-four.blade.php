@foreach (getCategories() as $category)
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="categories__item-two">
            <a href="{{ route('courses', ['main_category' => $category->slug]) }}">
                <div class="content">
                    <img src="{{ asset($category?->icon) }}" alt="img">
                    <span
                        class="name"><strong>{{ $category?->translation?->name }}</strong>{{ $category->subCategories->sum('courses_count') }}
                        {{ __('Courses') }}</span>
                </div>
                <div class="icon">
                    <i class="skillgro-next-2"></i>
                </div>
            </a>
        </div>
    </div>
@endforeach
