@php
    $depth = (int) ($depth ?? 0);
    $children = collect(data_get($category, 'children', []));
    $hasChildren = $children->isNotEmpty();
    $slug = (string) data_get($category, 'slug', '');
    $name = (string) data_get($category, 'translation_name', data_get($category, 'name', $slug));
    $href = (string) data_get($category, 'href', route('courses'));
@endphp

<li class="site-header__category-item {{ $depth === 0 ? 'site-header__category-item--root' : 'site-header__category-item--child' }}">
    <a href="{{ $href }}" class="site-header__category-link">
        <span>{{ $name }}</span>
        @if ($hasChildren)
            <i class="fas {{ $depth === 0 ? 'fa-chevron-down' : 'fa-chevron-right' }} site-header__category-chevron"></i>
        @endif
    </a>

    @if ($hasChildren)
        <ul class="site-header__category-children">
            @foreach ($children as $child)
                @include('frontend.layouts.partials.header-category-node', [
                    'category' => $child,
                    'depth' => $depth + 1,
                ])
            @endforeach
        </ul>
    @endif
</li>
