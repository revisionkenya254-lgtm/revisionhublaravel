@php
    $node = is_array($node ?? null) ? $node : [];
    $slug = (string) data_get($node, 'slug', '');
    $name = (string) data_get($node, 'name', data_get($node, 'label', $slug));
    $icon = (string) data_get($node, 'icon', '');
    $href = (string) data_get($node, 'href', route('catalog', ['main_category' => $slug]));
    $children = collect(data_get($node, 'children', []));
    $accent = (string) data_get($node, 'accent', '#5b37ff');

    $rootSlug = (string) ($rootSlug ?? $slug);
    $parentCategorySlug = ($parentCategorySlug ?? null) !== null ? (string) $parentCategorySlug : null;
    $selectedMainSlug = (string) ($selectedMainSlug ?? '');
    $selectedLevelSlug = (string) ($selectedLevelSlug ?? '');
    $selectedSubjectSlug = (string) ($selectedSubjectSlug ?? '');
    $catalogQuery = $catalogQuery ?? [];
    $depth = (int) ($depth ?? 0);
    $expandDescendants = (bool) ($expandDescendants ?? false);

    $categorySlug = $depth === 0 ? '' : ($depth === 1 ? $slug : $parentCategorySlug);
    $subjectSlug = $depth >= 2 ? $slug : '';

    $hasSelectedDescendant = function (array $candidate) use (&$hasSelectedDescendant, $selectedLevelSlug, $selectedSubjectSlug): bool {
        return collect(data_get($candidate, 'children', []))->contains(function ($child) use (&$hasSelectedDescendant, $selectedLevelSlug, $selectedSubjectSlug) {
            $childSlug = (string) data_get($child, 'slug', '');

            return $childSlug !== ''
                && (
                    $childSlug === $selectedLevelSlug
                    || $childSlug === $selectedSubjectSlug
                    || $hasSelectedDescendant($child)
                );
        });
    };

    $isRootNode = $depth === 0;
    $isCategoryActive = $depth === 1 && $selectedLevelSlug === $slug && $selectedSubjectSlug === '';
    $isSubjectActive = $depth >= 2 && $selectedLevelSlug === (string) $parentCategorySlug && $selectedSubjectSlug === $slug;
    $isRootActive = $isRootNode && $selectedMainSlug === $slug;
    $isActive = $isRootActive || $isCategoryActive || $isSubjectActive;
    $shouldOpenDescendants = $expandDescendants || $isRootActive;
    $isOpen = $children->isNotEmpty() && ($shouldOpenDescendants || $isCategoryActive || $isSubjectActive || $hasSelectedDescendant($node));

    $dataMainCategory = $rootSlug !== '' ? $rootSlug : $slug;
    $dataCategory = $categorySlug !== null ? (string) $categorySlug : '';
    $dataSubject = (string) $subjectSlug;

    $nodeLabelClass = $depth === 0 ? 'catalog-tree__summary-text catalog-tree__item' : 'catalog-tree__item';
    $levelClass = 'catalog-tree__item--level-' . $depth;
@endphp

@if ($children->isNotEmpty())
    <details class="catalog-tree__group catalog-tree__group--depth-{{ $depth }} {{ $isActive ? 'catalog-tree__group--selected' : '' }}" @if ($isOpen) open @endif>
        <summary class="catalog-tree__summary">
            <a class="{{ $nodeLabelClass }} catalog-tree__summary-link {{ $levelClass }} {{ $isActive ? 'is-active' : '' }}"
                href="{{ $href }}"
                data-catalog-depth="{{ $depth }}"
                data-catalog-main-category="{{ $dataMainCategory }}"
                @if ($dataCategory !== '') data-catalog-category="{{ $dataCategory }}" @endif
                @if ($dataSubject !== '') data-catalog-subject="{{ $dataSubject }}" @endif>
                <span class="catalog-tree__summary-icon" style="--tree-accent: {{ $accent }};">
                    @if ($icon !== '')
                        <i class="fas {{ $icon }}"></i>
                    @else
                        <span class="catalog-tree__item-mark"></span>
                    @endif
                </span>
                <span>{{ $name }}</span>
            </a>
            <i class="fas fa-chevron-down catalog-tree__summary-caret"></i>
        </summary>
        <div class="catalog-tree__children {{ $depth > 0 ? 'catalog-tree__children--nested' : '' }}">
            @foreach ($children->sortBy(fn ($child) => data_get($child, 'name', data_get($child, 'label', data_get($child, 'slug', '')))) as $child)
                @include('frontend.partials.catalog-tree-node', [
                    'node' => $child,
                    'rootSlug' => $rootSlug,
                    'parentCategorySlug' => $slug,
                    'selectedMainSlug' => $selectedMainSlug,
                    'selectedLevelSlug' => $selectedLevelSlug,
                    'selectedSubjectSlug' => $selectedSubjectSlug,
                    'catalogQuery' => $catalogQuery,
                    'depth' => $depth + 1,
                    'expandDescendants' => $shouldOpenDescendants,
                ])
            @endforeach
        </div>
    </details>
@else
    <a class="catalog-tree__item {{ $levelClass }} {{ $isActive ? 'is-active' : '' }}"
        href="{{ $href }}"
        data-catalog-depth="{{ $depth }}"
        data-catalog-main-category="{{ $dataMainCategory }}"
        @if ($dataCategory !== '') data-catalog-category="{{ $dataCategory }}" @endif
        @if ($dataSubject !== '') data-catalog-subject="{{ $dataSubject }}" @endif>
        <span class="catalog-tree__item-mark"></span>
        <span>{{ $name }}</span>
    </a>
@endif
