@foreach (getCategories() as $category)
    <div class="col-lg-3 col-sm-6">
        <div class="categories__item-four shine__animate-item">
            <a href="{{ route('courses', ['main_category' => $category->slug]) }}" class="shine__animate-link">
                <img src="{{ asset($category->icon) }}" alt="img">
                <span class="name">{{ $category?->name }}
                    <strong>({{ $category->subCategories->sum('courses_count') }})</strong></span>
            </a>
        </div>
    </div>
@endforeach
