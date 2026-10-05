@php
    $subCategories = collect($subCategories ?? []);
    $selectedCategorySlugs = collect($selectedCategorySlugs ?? $categoriesIds ?? [])->filter()->map(fn ($slug) => (string) $slug)->all();
@endphp

@if ($subCategories->count() > 0)
    <div class="courses-widget mb-4 pb-1">
        <h4 class="widget-title">{{ __('Sub Categories') }}</h4>
        <div class="subcategory-card-list">
            @foreach ($subCategories->sortBy(fn ($category) => data_get($category, 'translation_name', data_get($category, 'name'))) as $category)
                @include('frontend.partials.course-sidebar-item-node', [
                    'category' => $category,
                    'selectedCategorySlugs' => $selectedCategorySlugs,
                    'depth' => 0,
                ])
            @endforeach
        </div>
    </div>
@endif
