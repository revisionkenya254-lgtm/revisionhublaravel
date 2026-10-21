<?php

namespace App\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Product;
use Modules\Course\app\Models\CourseCategory;

class MenuCacheService
{
    const CACHE_VERSION = 'v19';

    /**
     * Cache TTL for menu data (24 hours)
     */
    const TTL_MENU = 86400;

    protected array $educationRootSlugs = [
        'pre-primary',
        'lower-primary',
        'upper-primary',
        'junior-school',
        'senior-school-cbc',
        'high-school',
        'tvet',
        'certificate-courses',
        'diploma-courses',
        'undergraduate',
        'professional-courses',
        'teacher-resources',
    ];

    /** @var array<int, string> */
    protected array $deprecatedCategorySlugs = ['kplea'];

    protected array $metadataEducationLevelByRootSlug = [
        'tvet' => 'TVET',
        'certificate-courses' => 'Certificate Courses',
        'diploma-courses' => 'Diploma Courses',
        'undergraduate' => 'Undergraduate',
        'professional-courses' => 'Professional Courses',
    ];

    protected array $metadataSchoolChildrenByLanguage = [];

    /**
     * Canonical category menu used for navigation.
     * This keeps the header and mobile drawer consistent without DB lookups.
     */
    protected function categoryMenuBlueprint(): array
    {
        $metadataCatalog = app(ProductMetadataCatalogService::class);
        $educationLevelLabels = collect($metadataCatalog->educationLevelDefinitions())
            ->pluck('label', 'slug');
        $levelName = fn (string $slug, string $fallback): string =>
            (string) ($educationLevelLabels[$slug] ?? $fallback);
        $buildGradeTree = function (array $grades, array $subjects): array {
            return collect($grades)
                ->map(function (array $grade) use ($subjects) {
                    return [
                        'slug' => $grade['slug'],
                        'name' => $grade['name'],
                        'children' => collect($subjects)
                            ->map(fn (array $subject) => [
                                'slug' => $subject['slug'],
                                'name' => $subject['name'],
                                'children' => [],
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all();
        };

        $buildCanonicalSchoolTree = function (string $levelSlug) use ($metadataCatalog, $buildGradeTree): array {
            $nodes = collect($metadataCatalog->schoolClassGradeDefinitions()[$levelSlug] ?? [])
                ->map(fn (array $node) => [
                    'slug' => $node['slug'],
                    'name' => $node['label'],
                ])
                ->all();

            return $buildGradeTree($nodes, $metadataCatalog->schoolSubjectsForLevel($levelSlug));
        };

        $buildSchoolTree = function (string $level): array {
            $schools = app(ProductMetadataCatalogService::class)->classGradesByLevel()[$level] ?? [];
            $coursesBySchoolSlug = app(ProductMetadataCatalogService::class)->coursesBySchoolSlug();

            return collect($schools)
                ->map(function (string $school) {
                    $slugBase = preg_replace('/^school of /i', '', $school) ?: $school;

                    return [
                        'slug' => Str::slug($slugBase),
                        'name' => $school,
                        'children' => [],
                    ];
                })
                ->map(function (array $school) use ($coursesBySchoolSlug) {
                    $school['children'] = collect($coursesBySchoolSlug[$school['slug']] ?? [])
                        ->map(fn (array $course) => $course + ['children' => []])
                        ->values()
                        ->all();

                    return $school;
                })
                ->values()
                ->all();
        };

        return [
            [
                'slug' => 'pre-primary',
                'name' => $levelName('pre-primary', 'Pre-Primary'),
                'children' => $buildCanonicalSchoolTree('pre-primary'),
            ],
            [
                'slug' => 'lower-primary',
                'name' => $levelName('lower-primary', 'Lower Primary'),
                'children' => $buildCanonicalSchoolTree('lower-primary'),
            ],
            [
                'slug' => 'upper-primary',
                'name' => $levelName('upper-primary', 'Upper Primary'),
                'children' => $buildCanonicalSchoolTree('upper-primary'),
            ],
            [
                'slug' => 'junior-school',
                'name' => $levelName('junior-school', 'Junior School'),
                'children' => $buildCanonicalSchoolTree('junior-school'),
            ],
            [
                'slug' => 'senior-school-cbc',
                'name' => $levelName('senior-school-cbc', 'Senior School (CBC)'),
                'children' => $buildCanonicalSchoolTree('senior-school-cbc'),
            ],
            [
                'slug' => 'high-school',
                'name' => $levelName('high-school', 'High School'),
                'children' => $buildCanonicalSchoolTree('high-school'),
            ],
            [
                'slug' => 'tvet',
                'name' => 'TVET',
                'children' => $buildSchoolTree('TVET'),
            ],
            [
                'slug' => 'certificate-courses',
                'name' => 'Certificate Courses',
                'children' => $buildSchoolTree('Certificate Courses'),
            ],
            [
                'slug' => 'diploma-courses',
                'name' => 'Diploma Courses',
                'children' => $buildSchoolTree('Diploma Courses'),
            ],
            [
                'slug' => 'undergraduate',
                'name' => 'Undergraduate',
                'children' => $buildSchoolTree('Undergraduate'),
            ],
            [
                'slug' => 'professional-courses',
                'name' => 'Professional Courses',
                'children' => collect([
                    ['slug' => 'icdl', 'name' => 'ICDL'],
                    ['slug' => 'cisco', 'name' => 'CISCO'],
                    ['slug' => 'aws', 'name' => 'AWS'],
                    ['slug' => 'microsoft', 'name' => 'Microsoft'],
                    ['slug' => 'google-certifications', 'name' => 'Google Certifications'],
                ])->map(fn (array $item) => $item + ['children' => []])->values()->all(),
            ],
            [
                'slug' => 'teacher-resources',
                'name' => 'Teacher Resources',
                'children' => collect([
                    ['slug' => 'lesson-plans', 'name' => 'Lesson Plans'],
                    ['slug' => 'schemes-of-work', 'name' => 'Schemes of Work'],
                    ['slug' => 'cbc-assessments', 'name' => 'CBC Assessments'],
                    ['slug' => 'teaching-notes', 'name' => 'Teaching Notes'],
                    ['slug' => 'marking-schemes', 'name' => 'Marking Schemes'],
                ])->map(fn (array $item) => $item + ['children' => []])->values()->all(),
            ],
        ];
    }

    protected function normalizeCategoryNode(array $node, array $ancestors = [], $languageCode = 'en'): array
    {
        $slug = (string) ($node['slug'] ?? '');
        $name = (string) __((string) ($node['name'] ?? $slug));
        $rootSlug = $ancestors[0] ?? $slug;

        $routeParams = ['main_category' => $rootSlug];
        if (count($ancestors) > 1) {
            $routeParams['category'] = $ancestors[1];
            $routeParams['subject'] = $slug;
        } elseif (!empty($ancestors)) {
            $routeParams['category'] = $slug;
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'label' => $name,
            'translation_name' => $name,
            'href' => route('catalog', $routeParams),
            'icon' => (string) ($node['icon'] ?? ''),
            'icon_url' => $this->resolveIconUrl($node['icon'] ?? ''),
            'children' => collect($node['children'] ?? [])
                ->map(fn (array $child) => $this->normalizeCategoryNode($child, array_merge($ancestors, [$slug]), $languageCode))
                ->values()
                ->all(),
        ];
    }

    /**
     * Convert a stored icon path into a public URL for API clients.
     *
     * Category icons are uploaded files, so the API should expose a full
     * browser-friendly URL while keeping the raw path available in `icon`.
     */
    protected function resolveIconUrl(string|null $icon): string
    {
        $icon = trim((string) $icon);

        if ($icon === '') {
            return '';
        }

        if (Str::startsWith($icon, ['http://', 'https://', '//'])) {
            return $icon;
        }

        return asset($icon);
    }

    protected function categoryMenuFromDatabase(string $languageCode = 'en'): array
    {
        $categories = CourseCategory::active()
            ->with(['translations' => fn ($query) => $query->where('lang_code', $languageCode)])
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        if ($categories->isEmpty()) {
            return [];
        }

        $byParent = $categories->groupBy(fn ($category) => $category->parent_id ? (string) $category->parent_id : 'root');
        $rootOrder = array_flip($this->educationRootSlugs);

        $buildNode = function ($category) use (&$buildNode, $byParent, $languageCode) {
            $children = collect($byParent[(string) $category->id] ?? [])
                ->map(fn ($child) => $buildNode($child))
                ->values()
                ->all();

            return [
                'slug' => (string) $category->slug,
                'name' => (string) ($category->translations->first()?->name ?? $category->slug),
                'icon' => (string) ($category->icon ?? ''),
                'children' => $children,
            ];
        };

        return collect($byParent['root'] ?? [])
            ->filter(fn ($category) => in_array((string) $category->slug, $this->educationRootSlugs, true))
            ->sortBy(fn ($category) => $rootOrder[(string) $category->slug] ?? 999)
            ->map(function ($category) use ($buildNode, $languageCode) {
                $node = $buildNode($category);
                $metadataChildren = $this->metadataSchoolChildrenForRoot((string) $category->slug, $languageCode);

                if (!empty($metadataChildren)) {
                    $node['children'] = $metadataChildren;
                }

                return $node;
            })
            ->values()
            ->all();
    }

    /**
     * Merge the canonical blueprint with database-backed nodes.
     *
     * The blueprint guarantees the full navigation structure is present.
     * Database nodes then override labels/icons and contribute any extra
     * children that were created dynamically in the admin.
     */
    protected function mergeCategoryTrees(array $baseTree, array $overlayTree): array
    {
        $findNode = function (array $nodes, string $slug) use (&$findNode): ?array {
            foreach ($nodes as $node) {
                if ((string) ($node['slug'] ?? '') === $slug) {
                    return $node;
                }

                $children = $node['children'] ?? [];
                if (!empty($children)) {
                    $match = $findNode($children, $slug);
                    if ($match !== null) {
                        return $match;
                    }
                }
            }

            return null;
        };

        $mergeNode = function (array $baseNode, ?array $overlayNode) use (&$mergeNode, $findNode): array {
            if ($overlayNode === null) {
                return $baseNode;
            }

            $baseNode['name'] = (string) ($overlayNode['name'] ?? $overlayNode['label'] ?? $baseNode['name'] ?? $baseNode['label'] ?? $baseNode['slug'] ?? '');
            $baseNode['label'] = (string) ($overlayNode['label'] ?? $overlayNode['name'] ?? $baseNode['label'] ?? $baseNode['name'] ?? $baseNode['slug'] ?? '');
            $baseNode['icon'] = (string) ($overlayNode['icon'] ?? $baseNode['icon'] ?? '');
            $baseNode['href'] = (string) ($overlayNode['href'] ?? $baseNode['href'] ?? '');

            if (array_key_exists('translation_name', $overlayNode)) {
                $baseNode['translation_name'] = $overlayNode['translation_name'];
            }

            $baseChildren = collect($baseNode['children'] ?? []);
            $overlayChildren = collect($overlayNode['children'] ?? []);
            $mergedChildren = [];
            $baseChildSlugs = [];

            foreach ($baseChildren as $baseChild) {
                $baseChildSlug = (string) data_get($baseChild, 'slug', '');
                if ($baseChildSlug === '') {
                    continue;
                }

                $baseChildSlugs[$baseChildSlug] = true;
                $mergedChildren[] = $mergeNode($baseChild, $findNode($overlayChildren->all(), $baseChildSlug));
            }

            foreach ($overlayChildren as $overlayChild) {
                $overlayChildSlug = (string) data_get($overlayChild, 'slug', '');
                if ($overlayChildSlug === '' || isset($baseChildSlugs[$overlayChildSlug])) {
                    continue;
                }

                $mergedChildren[] = $overlayChild;
            }

            $baseNode['children'] = $this->dedupeCategoryChildren($mergedChildren);

            return $baseNode;
        };

        $merged = [];
        $baseSlugs = [];

        foreach ($baseTree as $baseNode) {
            $baseSlug = (string) ($baseNode['slug'] ?? '');
            if ($baseSlug === '') {
                continue;
            }

            $baseSlugs[$baseSlug] = true;
            $merged[] = $mergeNode($baseNode, $findNode($overlayTree, $baseSlug));
        }

        foreach ($overlayTree as $overlayNode) {
            $overlaySlug = (string) ($overlayNode['slug'] ?? '');
            if ($overlaySlug === '' || isset($baseSlugs[$overlaySlug])) {
                continue;
            }

            $merged[] = $overlayNode;
        }

        return $merged;
    }

    /**
     * Keep structural curriculum rules identical across browser forms and API
     * menus. The stored Senior School tree uses pathways above grades, while
     * product selection uses grades above pathways/subjects.
     */
    protected function enforceCanonicalEducationHierarchy(array $tree, array $canonicalTree): array
    {
        $canonicalSeniorSchool = collect($canonicalTree)
            ->first(fn (array $node) => ($node['slug'] ?? null) === 'senior-school-cbc');

        if (! $canonicalSeniorSchool) {
            return $tree;
        }

        return collect($tree)
            ->map(function (array $node) use ($canonicalSeniorSchool) {
                if (($node['slug'] ?? null) === 'senior-school-cbc') {
                    $node['children'] = $canonicalSeniorSchool['children'] ?? [];
                }

                return $node;
            })
            ->values()
            ->all();
    }

    /**
     * Prevent removed taxonomy entries from being restored by legacy database
     * rows when the canonical and stored category trees are merged.
     */
    protected function removeDeprecatedCategoryNodes(array $nodes): array
    {
        return collect($nodes)
            ->reject(fn (array $node) => in_array(
                Str::lower(trim((string) ($node['slug'] ?? ''))),
                $this->deprecatedCategorySlugs,
                true
            ))
            ->map(function (array $node) {
                $node['children'] = $this->removeDeprecatedCategoryNodes($node['children'] ?? []);

                return $node;
            })
            ->values()
            ->all();
    }

    /**
     * Remove duplicate category children within the same branch.
     *
     * We keep the first matching node and skip later nodes that share the
     * same slug or the same display name.
     */
    protected function dedupeCategoryChildren(array $children): array
    {
        $seen = [];
        $deduped = [];

        foreach ($children as $child) {
            $slug = Str::lower(trim((string) data_get($child, 'slug', '')));
            $name = Str::lower(trim((string) data_get($child, 'name', data_get($child, 'label', ''))));

            $slugKey = $slug !== '' ? 'slug:' . $slug : null;
            $nameKey = $name !== '' ? 'name:' . $name : null;

            if (($slugKey !== null && isset($seen[$slugKey])) || ($nameKey !== null && isset($seen[$nameKey]))) {
                continue;
            }

            if ($slugKey !== null) {
                $seen[$slugKey] = true;
            }

            if ($nameKey !== null) {
                $seen[$nameKey] = true;
            }

            $deduped[] = $child;
        }

        return $deduped;
    }

    protected function metadataSchoolChildrenForRoot(string $rootSlug, string $languageCode): array
    {
        if (! isset($this->metadataEducationLevelByRootSlug[$rootSlug])) {
            return [];
        }

        if (isset($this->metadataSchoolChildrenByLanguage[$languageCode])) {
            return $this->metadataSchoolChildrenByLanguage[$languageCode][$rootSlug] ?? [];
        }

        $rootsByEducationLevel = array_flip($this->metadataEducationLevelByRootSlug);
        $schoolsByRoot = array_fill_keys(array_keys($this->metadataEducationLevelByRootSlug), []);
        $products = Product::active()
            ->where(function ($query) {
                $query->whereIn('metadata->education_level', array_values($this->metadataEducationLevelByRootSlug))
                    ->orWhereHas(
                        'category.parentCategory.parentCategory',
                        fn ($categoryQuery) => $categoryQuery->whereIn('slug', array_keys($this->metadataEducationLevelByRootSlug))
                    );
            })
            ->with([
                'category.translations' => fn ($query) => $query->where('lang_code', $languageCode),
                'category.parentCategory.translations' => fn ($query) => $query->where('lang_code', $languageCode),
                'category.parentCategory.parentCategory.translations' => fn ($query) => $query->where('lang_code', $languageCode),
            ])
            ->get(['id', 'category_id', 'metadata']);

        foreach ($products as $product) {
            $metadata = $product->metadata ?? [];
            $categoryPath = $this->productCategoryPath($product);
            $categoryRootSlug = (string) data_get($categoryPath, '0.slug', '');
            $categorySchoolNode = $categoryPath[1] ?? null;
            $categoryCourseNode = $categoryPath[2] ?? null;
            $metadataEducationLevel = (string) data_get($metadata, 'education_level', '');
            $rootSlug = isset($schoolsByRoot[$categoryRootSlug])
                ? $categoryRootSlug
                : ($rootsByEducationLevel[$metadataEducationLevel] ?? null);

            if ($rootSlug === null) {
                continue;
            }

            $schoolName = $this->categoryNodeName($categorySchoolNode);
            if ($schoolName === '') {
                $schoolName = trim((string) data_get($metadata, 'class_grade'));
            }
            if ($schoolName === '') {
                continue;
            }

            $schoolSlug = Str::slug(preg_replace('/^school of /i', '', $schoolName) ?: $schoolName);
            $courseName = $this->categoryNodeName($categoryCourseNode);
            if ($courseName === '') {
                $courseName = trim((string) (data_get($metadata, 'course') ?: data_get($metadata, 'subject')));
            }

            $schoolsByRoot[$rootSlug][$schoolSlug] ??= [
                'slug' => $schoolSlug,
                'name' => $schoolName,
                'children' => [],
                '_course_slugs' => [],
            ];

            if ($courseName !== '') {
                $courseSlug = Str::slug($courseName);
                if (!in_array($courseSlug, $schoolsByRoot[$rootSlug][$schoolSlug]['_course_slugs'], true)) {
                    $schoolsByRoot[$rootSlug][$schoolSlug]['children'][] = [
                        'slug' => $courseSlug,
                        'name' => $courseName,
                        'children' => [],
                    ];
                    $schoolsByRoot[$rootSlug][$schoolSlug]['_course_slugs'][] = $courseSlug;
                }
            }
        }

        $this->metadataSchoolChildrenByLanguage[$languageCode] = collect($schoolsByRoot)
            ->map(fn (array $schools) => collect($schools)
                ->map(function (array $school) {
                    unset($school['_course_slugs']);

                    return $school;
                })
                ->sortBy('name')
                ->values()
                ->all())
            ->all();

        return $this->metadataSchoolChildrenByLanguage[$languageCode][$rootSlug] ?? [];
    }

    protected function categoryNodeName(?array $category): string
    {
        return trim((string) data_get(
            $category,
            'translations.0.name',
            data_get($category, 'name', data_get($category, 'slug', ''))
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function productCategoryPath(Product $product): array
    {
        $category = $product->relationLoaded('category') ? $product->getRelation('category') : null;

        if (! $category || ! $category->exists) {
            $category = $product->category()->with(['parentCategory.parentCategory'])->first();
        } else {
            $category->loadMissing(['parentCategory.parentCategory']);
        }

        if (! $category || ! $category->exists) {
            return [];
        }

        $path = [];
        $node = $category;

        while ($node && $node->exists) {
            $translatedName = $node->relationLoaded('translations')
                ? $node->translations->first()?->name
                : null;

            array_unshift($path, $node->toArray() + [
                'slug' => (string) $node->slug,
                'name' => (string) ($translatedName ?? $node->slug),
            ]);
            $node = $node->parentCategory;
        }

        return $path;
    }

    protected function findCategoryNode(array $nodes, string $slug): ?array
    {
        foreach ($nodes as $node) {
            if (($node['slug'] ?? null) === $slug) {
                return $node;
            }

            $children = $node['children'] ?? [];
            if (!empty($children)) {
                $match = $this->findCategoryNode($children, $slug);
                if ($match !== null) {
                    return $match;
                }
            }
        }

        return null;
    }

    /**
     * Get the category links used by the main header, homepage and AI chat.
     */
    public function getCategoryLinks($languageCode = 'en')
    {
        $cacheKey = "menu_category_links_".self::CACHE_VERSION."_{$languageCode}";

        return Cache::rememberForever($cacheKey, function () use ($languageCode) {
            return collect($this->getCategoryTree($languageCode))
                ->map(function (array $category) {
                    return [
                        'label' => (string) ($category['label'] ?? $category['name'] ?? ''),
                        'href' => (string) ($category['href'] ?? ''),
                        'slug' => (string) ($category['slug'] ?? ''),
                    ];
                })
                ->values()
                ->all();
        });
    }

    /**
     * Get the full cached category tree used by menus and sidebars.
     */
    public function getCategoryTree($languageCode = 'en')
    {
        $cacheKey = "menu_category_tree_".self::CACHE_VERSION."_{$languageCode}";

        return $this->rememberForeverWithLock($cacheKey, function () use ($languageCode) {
            $canonicalTree = $this->categoryMenuBlueprint();
            $databaseTree = $this->categoryMenuFromDatabase($languageCode);
            $sourceTree = !empty($databaseTree)
                ? $this->mergeCategoryTrees($canonicalTree, $databaseTree)
                : $canonicalTree;

            $sourceTree = $this->removeDeprecatedCategoryNodes($sourceTree);
            $sourceTree = $this->enforceCanonicalEducationHierarchy($sourceTree, $canonicalTree);

            $sourceTree = $this->filterGrandchildrenWithoutContent($sourceTree, $languageCode);

            return collect($sourceTree)
                ->map(fn (array $category) => $this->normalizeCategoryNode($category, [], $languageCode))
                ->values()
                ->all();
        });
    }

    /**
     * Prevent concurrent cache misses from rebuilding the same category tree.
     */
    protected function rememberForeverWithLock(string $cacheKey, Closure $callback): mixed
    {
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            return Cache::lock("{$cacheKey}:build", 30)->block(10, function () use ($cacheKey, $callback) {
                if (Cache::has($cacheKey)) {
                    return Cache::get($cacheKey);
                }

                $value = $callback();
                Cache::forever($cacheKey, $value);

                return $value;
            });
        } catch (LockTimeoutException) {
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            return Cache::rememberForever($cacheKey, $callback);
        }
    }

    /**
     * Keep the predefined category tree, but expose only grandchild categories
     * that lead to at least one active, approved product on that exact path.
     */
    protected function filterGrandchildrenWithoutContent(array $tree, string $languageCode): array
    {
        $contentPaths = $this->activeProductCategoryPaths($languageCode);

        return collect($tree)
            ->map(function (array $root) use ($contentPaths) {
                $rootSlug = Str::slug((string) ($root['slug'] ?? ''));

                $root['children'] = collect($root['children'] ?? [])
                    ->map(function (array $child) use ($contentPaths, $rootSlug) {
                        $grandchildren = $child['children'] ?? [];

                        if (empty($grandchildren)) {
                            return $child;
                        }

                        $childSlug = Str::slug((string) ($child['slug'] ?? ''));
                        $pathPrefix = "{$rootSlug}/{$childSlug}/";
                        $grandchildrenWithContent = collect($contentPaths)
                            ->filter(fn (array $grandchild, string $path) => Str::startsWith($path, $pathPrefix));
                        $includedSlugs = [];

                        $filteredGrandchildren = collect($grandchildren)
                            ->filter(function (array $grandchild) use ($grandchildrenWithContent) {
                                $grandchildSlug = Str::slug((string) ($grandchild['slug'] ?? ''));

                                return $grandchildrenWithContent->contains(
                                    fn (array $contentGrandchild) => $contentGrandchild['slug'] === $grandchildSlug
                                );
                            })
                            ->each(function (array $grandchild) use (&$includedSlugs) {
                                $includedSlugs[] = Str::slug((string) ($grandchild['slug'] ?? ''));
                            });

                        $contentOnlyGrandchildren = $grandchildrenWithContent
                            ->reject(fn (array $grandchild) => in_array($grandchild['slug'], $includedSlugs, true))
                            ->sortBy('name');

                        $child['children'] = $filteredGrandchildren
                            ->concat($contentOnlyGrandchildren)
                            ->values()
                            ->all();

                        return $child;
                    })
                    ->values()
                    ->all();

                return $root;
            })
            ->values()
            ->all();
    }

    /**
     * Build the content-bearing taxonomy with two compact queries:
     * one DISTINCT join for real category paths and one metadata fallback for
     * products that do not have a complete three-level database path.
     *
     * @return array<string, array{slug: string, name: string, children: array}>
     */
    protected function activeProductCategoryPaths(string $languageCode): array
    {
        $paths = [];

        $databasePaths = DB::table('products as products')
            ->join('course_categories as grandchild', 'grandchild.id', '=', 'products.category_id')
            ->join('course_categories as child', 'child.id', '=', 'grandchild.parent_id')
            ->join('course_categories as root', 'root.id', '=', 'child.parent_id')
            ->leftJoin('course_category_translations as grandchild_translation', function ($join) use ($languageCode) {
                $join->on('grandchild_translation.course_category_id', '=', 'grandchild.id')
                    ->where('grandchild_translation.lang_code', '=', $languageCode);
            })
            ->where('products.status', 'active')
            ->where('products.is_approved', 'approved')
            ->whereNull('products.deleted_at')
            ->where('root.status', 1)
            ->where('child.status', 1)
            ->where('grandchild.status', 1)
            ->whereNotNull('root.slug')
            ->whereNotNull('child.slug')
            ->whereNotNull('grandchild.slug')
            ->select([
                'root.slug as root_slug',
                'child.slug as child_slug',
                'grandchild.slug as grandchild_slug',
                'grandchild_translation.name as grandchild_name',
            ])
            ->distinct()
            ->get();

        foreach ($databasePaths as $path) {
            $segments = collect([$path->root_slug, $path->child_slug, $path->grandchild_slug])
                ->map(fn ($slug) => Str::slug((string) $slug));

            if ($segments->contains('')) {
                continue;
            }

            $paths[$segments->implode('/')] = [
                'slug' => $segments->get(2),
                'name' => (string) ($path->grandchild_name ?: $path->grandchild_slug),
                'children' => [],
            ];
        }

        $metadataProducts = DB::table('products as products')
            ->leftJoin('course_categories as grandchild', 'grandchild.id', '=', 'products.category_id')
            ->leftJoin('course_categories as child', 'child.id', '=', 'grandchild.parent_id')
            ->leftJoin('course_categories as root', 'root.id', '=', 'child.parent_id')
            ->where('products.status', 'active')
            ->where('products.is_approved', 'approved')
            ->whereNull('products.deleted_at')
            ->whereNull('root.id')
            ->whereNotNull('products.metadata')
            ->select(['products.metadata'])
            ->distinct()
            ->get();

        $identityService = app(ProductIdentityService::class);

        foreach ($metadataProducts as $metadataProduct) {
            $metadata = json_decode((string) $metadataProduct->metadata, true);
            if (! is_array($metadata)) {
                continue;
            }

            $identity = $identityService->buildIdentity($metadata);
            $segments = collect(['main_category', 'category', 'subject'])
                ->map(fn (string $key) => Str::slug((string) ($identity[$key] ?? '')));

            if ($segments->contains('')) {
                continue;
            }

            $paths[$segments->implode('/')] = [
                'slug' => $segments->get(2),
                'name' => (string) ($identity['subject_label'] ?? $segments->get(2)),
                'children' => [],
            ];
        }

        return $paths;
    }

    /**
     * Get complete menu structure with caching
     * This is optimized for navigation menus that rarely change
     */
    public function getMenuStructure($languageCode = 'en')
    {
        $cacheKey = "menu_structure_".self::CACHE_VERSION."_{$languageCode}";

        return Cache::rememberForever($cacheKey, function () use ($languageCode) {
            return [
                'categories' => $this->getCategoryTree($languageCode),
                'languages' => $this->getMenuLanguages(),
                'levels' => $this->getMenuLevels($languageCode),
                'taxonomy_revision' => app(\App\Services\Taxonomy\TaxonomyRevisionService::class)->current(),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get subcategories for a specific parent category (for menu)
     */
    public function getSubCategoriesForMenu($parentSlug, $languageCode = 'en')
    {
        $tree = $this->getCategoryTree($languageCode);
        $node = $this->findCategoryNode($tree, (string) $parentSlug);

        return $node['children'] ?? [];
    }

    /**
     * Get languages for menu dropdown
     */
    protected function getMenuLanguages()
    {
        return \Modules\Language\app\Models\Language::select('code', 'name', 'direction', 'is_default', 'status')
            ->where('status', 1)
            ->get()
            ->map(function ($language) {
                return [
                    'code' => $language->code,
                    'name' => $language->name,
                    'direction' => $language->direction,
                    'is_default' => (bool) $language->is_default,
                ];
            })
            ->toArray();
    }

    /**
     * Get levels for menu dropdown
     */
    protected function getMenuLevels($languageCode)
    {
        return \Modules\Course\app\Models\CourseLevel::select('id', 'slug')
            ->with(['translations' => function ($q) use ($languageCode) {
                $q->where('lang_code', $languageCode)->select('course_level_id', 'name');
            }])
            ->where('status', 1)
            ->get()
            ->map(function ($level) use ($languageCode) {
                return [
                    'slug' => (string) $level->slug,
                    'name' => (string) ($level->translations->first()->name ?? $level->slug),
                ];
            })
            ->toArray();
    }

    /**
     * Get simplified menu for mobile apps (lighter payload)
     */
    public function getMobileMenu($languageCode = 'en')
    {
        $cacheKey = "mobile_menu_".self::CACHE_VERSION."_{$languageCode}";

        return Cache::rememberForever($cacheKey, function () use ($languageCode) {
            $mainCategories = collect($this->getCategoryTree($languageCode))
                ->map(function (array $category) {
                    return [
                        'slug' => (string) ($category['slug'] ?? ''),
                        'name' => (string) ($category['name'] ?? ''),
                        'icon' => (string) ($category['icon'] ?? ''),
                        'icon_url' => (string) ($category['icon_url'] ?? ''),
                    ];
                })
                ->values();

            return [
                'categories' => $mainCategories,
                'version' => '1.0',
                'taxonomy_revision' => app(\App\Services\Taxonomy\TaxonomyRevisionService::class)->current(),
            ];
        });
    }

    /**
     * Clear menu cache
     */
    public function clearMenuCache()
    {
        $this->metadataSchoolChildrenByLanguage = [];

        $languages = ['en', 'sw', config('app.locale'), function_exists('getSessionLanguage') ? getSessionLanguage() : null];

        try {
            $languages = array_merge(
                $languages,
                \Modules\Language\app\Models\Language::query()->pluck('code')->all()
            );
        } catch (\Throwable) {
            // Language tables may not exist yet while migrations are running.
        }

        foreach (array_unique(array_filter($languages)) as $language) {
            if (blank($language)) {
                continue;
            }

            Cache::forget('menu_category_links_'.self::CACHE_VERSION."_{$language}");
            Cache::forget('menu_category_tree_'.self::CACHE_VERSION."_{$language}");
            Cache::forget('menu_structure_'.self::CACHE_VERSION."_{$language}");
            Cache::forget('mobile_menu_'.self::CACHE_VERSION."_{$language}");
            Cache::forget('main_categories_'.self::CACHE_VERSION."_{$language}");
        }

        Cache::forget('menu_category_links_v3_en');
        Cache::forget('menu_category_links_v3_sw');
        Cache::forget('menu_category_tree_v3_en');
        Cache::forget('menu_category_tree_v3_sw');
        Cache::forget('menu_structure_v3_en');
        Cache::forget('menu_structure_v3_sw');
        Cache::forget('mobile_menu_v3_en');
        Cache::forget('mobile_menu_v3_sw');
        Cache::forget('main_categories_v3_en');
        Cache::forget('main_categories_v3_sw');
        Cache::forget('menu_category_links_v2_en');
        Cache::forget('menu_category_links_v2_sw');
        Cache::forget('menu_structure_v2_en');
        Cache::forget('menu_structure_v2_sw');
        Cache::forget('mobile_menu_v2_en');
        Cache::forget('mobile_menu_v2_sw');
        Cache::forget('menu_structure_en');
        Cache::forget('menu_structure_sw');
        Cache::forget('mobile_menu_en');
        Cache::forget('mobile_menu_sw');
    }

    /**
     * Get menu cache info
     */
    public function getMenuCacheInfo()
    {
        return [
            'cache_key' => "menu_structure_".self::CACHE_VERSION."_en",
            'ttl' => self::TTL_MENU,
            'ttl_human' => '24 hours',
            'tags' => ['menu', 'categories'],
        ];
    }
}
