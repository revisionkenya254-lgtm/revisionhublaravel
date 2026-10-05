@php
    $slug = (string) data_get($category, 'slug', '');
    $name = (string) data_get($category, 'translation_name', data_get($category, 'name', $slug));
    $children = collect(data_get($category, 'children', []));
    $inputId = 'cat_' . $slug;
    $indentClass = $depth > 0 ? 'ms-3 ps-2 border-start' : '';
@endphp

<div class="subcategory-card {{ $indentClass }}">
    <input @checked(in_array($slug, $selectedCategorySlugs, true)) class="subcategory-card-input category-checkbox" type="radio" name="subcategory" value="{{ $slug }}" id="{{ $inputId }}">
    <label class="subcategory-card-label" for="{{ $inputId }}">
        <span class="subcategory-card-check" aria-hidden="true"></span>
        <span class="subcategory-card-text">{{ $name }}</span>
    </label>
</div>

@if ($children->isNotEmpty())
    <div class="subcategory-card-children">
        @foreach ($children->sortBy(fn ($child) => data_get($child, 'translation_name', data_get($child, 'name'))) as $child)
            @include('frontend.partials.course-sidebar-item-node', [
                'category' => $child,
                'selectedCategorySlugs' => $selectedCategorySlugs,
                'depth' => $depth + 1,
            ])
        @endforeach
    </div>
@endif
