<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Product;
use Modules\Course\app\Models\CourseCategory;

class MenuCacheService
{
    const CACHE_VERSION = 'v13';

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

    protected array $metadataEducationLevelByRootSlug = [
        'tvet' => 'TVET',
        'certificate-courses' => 'Certificate Courses',
        'diploma-courses' => 'Diploma Courses',
        'undergraduate' => 'Undergraduate',
        'professional-courses' => 'Professional Courses',
    ];

    /**
     * Canonical category menu used for navigation.
     * This keeps the header and mobile drawer consistent without DB lookups.
     */
    protected function categoryMenuBlueprint(): array
    {
        $lowerPrimarySubjects = [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'environmental-activities', 'name' => 'Environmental Activities'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
            ['slug' => 'religious-education', 'name' => 'Religious Education'],
        ];

        $upperPrimarySubjects = [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'science-and-technology', 'name' => 'Science & Technology'],
            ['slug' => 'social-studies', 'name' => 'Social Studies'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
            ['slug' => 'religious-education', 'name' => 'Religious Education'],
        ];

        $juniorSchoolSubjects = [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'integrated-science', 'name' => 'Integrated Science'],
            ['slug' => 'social-studies', 'name' => 'Social Studies'],
            ['slug' => 'pre-technical-studies', 'name' => 'Pre-Technical Studies'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'computer-studies', 'name' => 'Computer Studies'],
            ['slug' => 'cre-ire-hre', 'name' => 'CRE/IRE/HRE'],
            ['slug' => 'business-studies', 'name' => 'Business Studies'],
            ['slug' => 'creative-arts', 'name' => 'Creative Arts'],
        ];

        $highSchoolSubjects = [
            ['slug' => 'mathematics', 'name' => 'Mathematics'],
            ['slug' => 'english', 'name' => 'English'],
            ['slug' => 'kiswahili', 'name' => 'Kiswahili'],
            ['slug' => 'biology', 'name' => 'Biology'],
            ['slug' => 'chemistry', 'name' => 'Chemistry'],
            ['slug' => 'physics', 'name' => 'Physics'],
            ['slug' => 'geography', 'name' => 'Geography'],
            ['slug' => 'history', 'name' => 'History'],
            ['slug' => 'cre', 'name' => 'CRE'],
            ['slug' => 'ire', 'name' => 'IRE'],
            ['slug' => 'hre', 'name' => 'HRE'],
            ['slug' => 'agriculture', 'name' => 'Agriculture'],
            ['slug' => 'business-studies', 'name' => 'Business Studies'],
            ['slug' => 'computer-studies', 'name' => 'Computer Studies'],
            ['slug' => 'french', 'name' => 'French'],
            ['slug' => 'german', 'name' => 'German'],
            ['slug' => 'music', 'name' => 'Music'],
            ['slug' => 'home-science', 'name' => 'Home Science'],
        ];

        $kcseSubjects = app(ProductMetadataCatalogService::class)->quizSelectionOptions()['subjects'];
        $buildAssessmentNode = function (string $slug, string $name, array $subjects): array {
            return [
                'slug' => $slug,
                'name' => $name,
                'children' => collect($subjects)
                    ->map(fn (array $subject) => [
                        'slug' => $subject['slug'],
                        'name' => $subject['name'],
                        'children' => [],
                    ])
                    ->values()
                    ->all(),
            ];
        };
        $kcseNode = [
            'slug' => 'kcse',
            'name' => 'KCSE',
            'children' => collect($kcseSubjects)
                ->map(fn (string $subject) => [
                    'slug' => Str::slug($subject),
                    'name' => $subject,
                    'children' => [],
                ])
                ->values()
                ->all(),
        ];
        $kpseaNode = $buildAssessmentNode('kpsea', 'KPSEA', $upperPrimarySubjects);
        $kpleaNode = $buildAssessmentNode('kplea', 'KPLEA', $lowerPrimarySubjects);
        $kjseaNode = $buildAssessmentNode('kjsea', 'KJSEA', $juniorSchoolSubjects);
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

        $buildPathwayTree = function (array $pathways): array {
            $grades = [
                ['slug' => 'grade-10', 'name' => 'Grade 10'],
                ['slug' => 'grade-11', 'name' => 'Grade 11'],
                ['slug' => 'grade-12', 'name' => 'Grade 12'],
            ];

            return collect($pathways)
                ->map(function (array $pathway) use ($grades) {
                    return [
                        'slug' => $pathway['slug'],
                        'name' => $pathway['name'],
                        'children' => collect($grades)
                            ->map(fn (array $grade) => [
                                'slug' => $grade['slug'],
                                'name' => $grade['name'],
                                'children' => [],
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all();
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
                'name' => 'Pre-Primary',
                'children' => [
                    ['slug' => 'pp1', 'name' => 'PP1', 'children' => []],
                    ['slug' => 'pp2', 'name' => 'PP2', 'children' => []],
                ],
            ],
            [
                'slug' => 'lower-primary',
                'name' => 'Lower Primary',
                'children' => array_merge($buildGradeTree([
                    ['slug' => 'grade-1', 'name' => 'Grade 1'],
                    ['slug' => 'grade-2', 'name' => 'Grade 2'],
                    ['slug' => 'grade-3', 'name' => 'Grade 3'],
                ], $lowerPrimarySubjects), [$kpleaNode]),
            ],
            [
                'slug' => 'upper-primary',
                'name' => 'Upper Primary',
                'children' => array_merge($buildGradeTree([
                    ['slug' => 'grade-4', 'name' => 'Grade 4'],
                    ['slug' => 'grade-5', 'name' => 'Grade 5'],
                    ['slug' => 'grade-6', 'name' => 'Grade 6'],
                ], $upperPrimarySubjects), [$kpseaNode]),
            ],
            [
                'slug' => 'junior-school',
                'name' => 'Junior School',
                'children' => array_merge($buildGradeTree([
                    ['slug' => 'grade-7', 'name' => 'Grade 7'],
                    ['slug' => 'grade-8', 'name' => 'Grade 8'],
                    ['slug' => 'grade-9', 'name' => 'Grade 9'],
                ], $juniorSchoolSubjects), [$kjseaNode]),
            ],
            [
                'slug' => 'senior-school-cbc',
                'name' => 'Senior School (CBC)',
                'children' => $buildGradeTree([
                    ['slug' => 'grade-10', 'name' => 'Grade 10'],
                    ['slug' => 'grade-11', 'name' => 'Grade 11'],
                    ['slug' => 'grade-12', 'name' => 'Grade 12'],
                ], $highSchoolSubjects),
            ],
            [
                'slug' => 'high-school',
                'name' => 'High School',
                'children' => array_merge([$kcseNode], $buildGradeTree([
                    ['slug' => 'form-3', 'name' => 'Form 3'],
                    ['slug' => 'form-4', 'name' => 'Form 4'],
                ], $highSchoolSubjects)),
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
                'name' => (string) ($category->translations->first()?->name ?? $category->translation?->name ?? $category->name ?? $category->slug),
                'icon' => (string) ($category->icon ?? ''),
                'children' => $children,
            ];
        };

        return collect($byParent['root'] ?? [])
            ->filter(fn ($category) => in_array((string) $category->slug, $this->educationRootSlugs, true))
            ->sortBy(fn ($category) => $rootOrder[(string) $category->slug] ?? 999)
            ->map(function ($category) use ($buildNode) {
                $node = $buildNode($category);
                $metadataChildren = $this->metadataSchoolChildrenForRoot((string) $category->slug);

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

    protected function metadataSchoolChildrenForRoot(string $rootSlug): array
    {
        $educationLevel = $this->metadataEducationLevelByRootSlug[$rootSlug] ?? null;
        if ($educationLevel === null) {
            return [];
        }

        $products = Product::query()
            ->with([
                'category.parentCategory.parentCategory.translation',
                'category.parentCategory.translation',
                'category.translation',
            ])
            ->get(['id', 'category_id', 'metadata']);

        $schools = [];

        foreach ($products as $product) {
            $metadata = $product->metadata ?? [];
            $categoryPath = $this->productCategoryPath($product);
            $categoryRootSlug = (string) data_get($categoryPath, '0.slug', '');
            $categorySchoolNode = $categoryPath[1] ?? null;
            $categoryCourseNode = $categoryPath[2] ?? null;

            if ((string) data_get($metadata, 'education_level') !== $educationLevel && $categoryRootSlug !== $rootSlug) {
                continue;
            }

            $schoolName = trim((string) data_get($metadata, 'class_grade'));
            if ($schoolName === '') {
                $schoolName = (string) data_get($categorySchoolNode, 'name', data_get($categorySchoolNode, 'slug', ''));
            }
            if ($schoolName === '') {
                continue;
            }

            $schoolSlug = Str::slug(preg_replace('/^school of /i', '', $schoolName) ?: $schoolName);
            $courseName = trim((string) (data_get($metadata, 'course') ?: data_get($metadata, 'subject')));
            if ($courseName === '') {
                $courseName = (string) data_get($categoryCourseNode, 'name', data_get($categoryCourseNode, 'slug', ''));
            }

            $schools[$schoolSlug] ??= [
                'slug' => $schoolSlug,
                'name' => $schoolName,
                'children' => [],
                '_course_slugs' => [],
            ];

            if ($courseName !== '') {
                $courseSlug = Str::slug($courseName);
                if (!in_array($courseSlug, $schools[$schoolSlug]['_course_slugs'], true)) {
                    $schools[$schoolSlug]['children'][] = [
                        'slug' => $courseSlug,
                        'name' => $courseName,
                        'children' => [],
                    ];
                    $schools[$schoolSlug]['_course_slugs'][] = $courseSlug;
                }
            }
        }

        return collect($schools)
            ->map(function (array $school) {
                unset($school['_course_slugs']);

                return $school;
            })
            ->sortBy('name')
            ->values()
            ->all();
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
            array_unshift($path, $node->toArray() + [
                'slug' => (string) $node->slug,
                'name' => (string) ($node->name ?? $node->slug),
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

        return Cache::rememberForever($cacheKey, function () use ($languageCode) {
            $databaseTree = $this->categoryMenuFromDatabase($languageCode);
            $sourceTree = !empty($databaseTree)
                ? $this->mergeCategoryTrees($this->categoryMenuBlueprint(), $databaseTree)
                : $this->categoryMenuBlueprint();

            $sourceTree = $this->filterGrandchildrenWithoutContent($sourceTree, $languageCode);

            return collect($sourceTree)
                ->map(fn (array $category) => $this->normalizeCategoryNode($category, [], $languageCode))
                ->values()
                ->all();
        });
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
     * @return array<string, true>
     */
    protected function activeProductCategoryPaths(string $languageCode): array
    {
        $identityService = app(ProductIdentityService::class);

        return Product::active()
            ->with([
                'category.translations' => fn ($query) => $query->where('lang_code', $languageCode),
                'category.parentCategory.translations' => fn ($query) => $query->where('lang_code', $languageCode),
                'category.parentCategory.parentCategory.translations' => fn ($query) => $query->where('lang_code', $languageCode),
            ])
            ->get(['id', 'category_id', 'metadata'])
            ->reduce(function (array $paths, Product $product) use ($identityService) {
                $categoryPath = $this->productCategoryPath($product);

                if (count($categoryPath) >= 3) {
                    $segments = collect(array_slice($categoryPath, 0, 3))
                        ->map(fn (array $category) => Str::slug((string) ($category['slug'] ?? '')));
                    $grandchild = $categoryPath[2];
                    $grandchildName = (string) data_get(
                        $grandchild,
                        'translations.0.name',
                        data_get($grandchild, 'name', $segments->get(2))
                    );
                } else {
                    $identity = $identityService->identityForProduct($product);
                    $segments = collect(['main_category', 'category', 'subject'])
                        ->map(fn (string $key) => Str::slug((string) ($identity[$key] ?? '')));
                    $grandchildName = (string) ($identity['subject_label'] ?? $segments->get(2, ''));
                }

                if ($segments->every(fn (string $segment) => $segment !== '')) {
                    $paths[$segments->implode('/')] = [
                        'slug' => $segments->get(2),
                        'name' => $grandchildName,
                        'children' => [],
                    ];
                }

                return $paths;
            }, []);
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
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get subcategories for a specific parent category (for menu)
     */
    public function getSubCategoriesForMenu($parentSlug, $languageCode = 'en')
    {
        $catalogVersion = CatalogCacheClear::version();
        $cacheKey = "menu_subcategories_".self::CACHE_VERSION."_{$catalogVersion}_{$languageCode}_{$parentSlug}";

        return Cache::rememberForever($cacheKey, function () use ($parentSlug, $languageCode) {
            $tree = $this->getCategoryTree($languageCode);
            $node = $this->findCategoryNode($tree, (string) $parentSlug);

            return $node['children'] ?? [];
        });
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
            ];
        });
    }

    /**
     * Clear menu cache
     */
    public function clearMenuCache()
    {
        foreach (['en', 'sw', config('app.locale'), function_exists('getSessionLanguage') ? getSessionLanguage() : null] as $language) {
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
