@php
    $children = collect($children ?? []);
    $rootSlug = (string) ($rootSlug ?? '');
    $parentCategorySlug = ($parentCategorySlug ?? null) !== null ? (string) $parentCategorySlug : null;
    $selectedLevelSlug = (string) ($selectedLevelSlug ?? '');
    $selectedSubjectSlug = (string) ($selectedSubjectSlug ?? '');
    $catalogQuery = $catalogQuery ?? [];
    $level = (int) ($level ?? 0);
    $expandDescendants = (bool) ($expandDescendants ?? false);

    $hasSelectedDescendant = function ($node) use (&$hasSelectedDescendant, $selectedLevelSlug, $selectedSubjectSlug) {
        return collect(data_get($node, 'children', []))->contains(function ($child) use (&$hasSelectedDescendant, $selectedLevelSlug, $selectedSubjectSlug) {
            $childSlug = (string) data_get($child, 'slug', '');

            return $childSlug !== ''
                && ($childSlug === $selectedLevelSlug || $childSlug === $selectedSubjectSlug || $hasSelectedDescendant($child));
        });
    };
@endphp

@foreach ($children as $child)
    @php
        $childSlug = (string) data_get($child, 'slug', '');
        $childName = (string) data_get($child, 'name', data_get($child, 'label', $childSlug));
        $grandChildren = collect(data_get($child, 'children', []));
        $categorySlug = $level === 0 ? $childSlug : (string) $parentCategorySlug;
        $subjectSlug = $level === 0 ? null : $childSlug;
        $isCategoryActive = $level === 0 && $selectedLevelSlug === $childSlug && $selectedSubjectSlug === '';
        $isSubjectActive = $level > 0 && $selectedLevelSlug === $categorySlug && $selectedSubjectSlug === $childSlug;
        $shouldOpenDescendants = $expandDescendants || $isCategoryActive;
        $isOpen = $shouldOpenDescendants || $isSubjectActive || $hasSelectedDescendant($child);
        $routeParams = array_merge($catalogQuery, [
            'main_category' => $rootSlug,
            'category' => $categorySlug,
        ]);

        if ($subjectSlug !== null && $subjectSlug !== '') {
            $routeParams['subject'] = $subjectSlug;
        }
    @endphp

    <a class="catalog-tree__item catalog-tree__item--level-{{ $level }} {{ ($isCategoryActive || $isSubjectActive) ? 'is-active' : '' }}"
        href="{{ route('catalog', $routeParams) }}"
        data-catalog-main-category="{{ $rootSlug }}"
        data-catalog-category="{{ $categorySlug }}"
        @if ($subjectSlug !== null && $subjectSlug !== '') data-catalog-subject="{{ $subjectSlug }}" @endif>
        <span class="catalog-tree__item-mark"></span>
        <span>{{ $childName }}</span>
    </a>

    @if ($grandChildren->isNotEmpty() && $isOpen)
        <div class="catalog-tree__children catalog-tree__children--nested">
            @include('frontend.partials.catalog-tree-children', [
                'children' => $grandChildren,
                'rootSlug' => $rootSlug,
                'parentCategorySlug' => $categorySlug,
                'selectedLevelSlug' => $selectedLevelSlug,
                'selectedSubjectSlug' => $selectedSubjectSlug,
                'catalogQuery' => $catalogQuery,
                'level' => $level + 1,
                'expandDescendants' => $shouldOpenDescendants,
            ])
        </div>
    @endif
@endforeach
