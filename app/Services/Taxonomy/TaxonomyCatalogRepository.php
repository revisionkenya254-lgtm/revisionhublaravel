<?php

namespace App\Services\Taxonomy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Course\app\Models\CourseCategory;
use Throwable;

class TaxonomyCatalogRepository
{
    private const CACHE_VERSION = 'v3';

    public function __construct(private readonly EducationFamilyService $families)
    {
    }

    public function snapshot(?string $languageCode = null): array
    {
        $languageCode ??= function_exists('getSessionLanguage')
            ? getSessionLanguage()
            : config('app.locale', 'en');

        return Cache::rememberForever($this->cacheKey($languageCode), function () use ($languageCode) {
            return $this->readDatabaseSnapshot($languageCode) ?? $this->configurationSnapshot();
        });
    }

    public function educationLevels(?string $languageCode = null): array
    {
        return collect($this->snapshot($languageCode)['education_levels'])
            ->map(fn (array $level) => array_merge($level, $this->families->describe($level)))
            ->unique(fn (array $level) => strtolower(trim((string) ($level['label'] ?? $level['slug'] ?? ''))))
            ->values()
            ->all();
    }

    public function groupedEducationLevels(?string $languageCode = null): array
    {
        return $this->families->group($this->educationLevels($languageCode));
    }

    public function taxonomy(?string $languageCode = null): array
    {
        return $this->snapshot($languageCode);
    }

    public function classGradesByLevel(?string $languageCode = null): array
    {
        return $this->snapshot($languageCode)['class_grades_by_level'];
    }

    public function subjectsForLevel(string $levelSlug, ?string $languageCode = null): array
    {
        return $this->snapshot($languageCode)['subjects_by_level'][$levelSlug] ?? [];
    }

    public function classGradeNodes(string $levelSlug, ?string $languageCode = null): array
    {
        return $this->snapshot($languageCode)['nodes_by_level'][$levelSlug] ?? [];
    }

    public function clearCache(): void
    {
        $languages = ['en', 'sw', config('app.locale')];

        try {
            if (function_exists('allLanguages')) {
                $languages = array_merge($languages, allLanguages()?->pluck('code')->filter()->all() ?? []);
            }
        } catch (Throwable) {
            // Language tables may be unavailable while migrations run.
        }

        if (function_exists('getSessionLanguage')) {
            $languages[] = getSessionLanguage();
        }

        foreach (array_unique(array_filter($languages)) as $language) {
            Cache::forget($this->cacheKey((string) $language));
        }
    }

    private function readDatabaseSnapshot(string $languageCode): ?array
    {
        try {
            if (! Schema::hasTable('course_categories')
                || ! Schema::hasTable('course_category_translations')
                || ! Schema::hasColumn('course_categories', 'node_type')
                || ! Schema::hasColumn('course_categories', 'deleted_at')) {
                return null;
            }

            $columns = [
                'category.id',
                'category.parent_id',
                'category.slug',
                'category.node_type',
                'category.order',
                DB::raw('COALESCE(translation.name, fallback_translation.name) as translation_name'),
            ];
            if (Schema::hasColumn('course_categories', 'taxonomy_profile')) {
                $columns[] = 'category.taxonomy_profile';
            }

            $nodes = DB::table('course_categories as category')
                ->leftJoin('course_category_translations as translation', function ($join) use ($languageCode) {
                    $join->on('translation.course_category_id', '=', 'category.id')
                        ->where('translation.lang_code', $languageCode);
                })
                ->leftJoin('course_category_translations as fallback_translation', function ($join) {
                    $join->on('fallback_translation.course_category_id', '=', 'category.id')
                        ->where('fallback_translation.lang_code', config('app.locale', 'en'));
                })
                ->whereNull('category.deleted_at')
                ->where('category.status', 1)
                ->orderBy('category.order')
                ->orderBy('category.id')
                ->get($columns)
                // Legacy databases did not enforce one translation per language.
                // Keep the catalogue stable while duplicate rows are being repaired.
                ->unique('id')
                ->values();

            $roots = $nodes
                ->whereNull('parent_id')
                ->where('node_type', CourseCategory::TYPE_EDUCATION_LEVEL)
                ->values();

            if ($roots->isEmpty()) {
                return null;
            }

            $childrenByParent = $nodes->groupBy(fn ($node) => (string) $node->parent_id);
            $educationLevels = [];
            $classGradesByLevel = [];
            $subjectsByLevel = [];
            $nodesByLevel = [];
            $buildTree = function ($node) use (&$buildTree, $childrenByParent): array {
                return [
                    'id' => (int) $node->id,
                    'parent_id' => $node->parent_id ? (int) $node->parent_id : null,
                    'slug' => (string) $node->slug,
                    'label' => $this->label($node),
                    'node_type' => (string) $node->node_type,
                    'profile' => $node->taxonomy_profile ?? null,
                    'order' => (int) $node->order,
                    'children' => collect($childrenByParent[(string) $node->id] ?? [])
                        ->map(fn ($child) => $buildTree($child))
                        ->all(),
                ];
            };
            $tree = $roots->map(fn ($root) => $buildTree($root))->all();

            foreach ($roots as $root) {
                $levelSlug = (string) $root->slug;
                $educationLevels[] = [
                    'id' => (int) $root->id,
                    'slug' => $levelSlug,
                    'label' => $this->label($root),
                    'source_slugs' => [$levelSlug],
                    'taxonomy_profile' => $root->taxonomy_profile ?? null,
                ];

                $classNodes = collect($childrenByParent[(string) $root->id] ?? [])
                    ->whereIn('node_type', [
                        CourseCategory::TYPE_GRADE,
                        CourseCategory::TYPE_FORM,
                        CourseCategory::TYPE_NATIONAL_EXAM,
                    ])
                    ->values();

                if ($classNodes->isEmpty()) {
                    continue;
                }

                $classGradesByLevel[$levelSlug] = $classNodes
                    ->map(fn ($node) => [
                        'id' => (int) $node->id,
                        'slug' => (string) $node->slug,
                        'label' => $this->label($node),
                        'node_type' => (string) $node->node_type,
                    ])
                    ->all();

                $subjects = $classNodes
                    ->flatMap(fn ($classNode) => collect($childrenByParent[(string) $classNode->id] ?? [])
                        ->whereIn('node_type', [
                            CourseCategory::TYPE_SUBJECT,
                            CourseCategory::TYPE_PATHWAY,
                        ]))
                    ->unique('slug')
                    ->values()
                    ->map(fn ($subject) => [
                        'slug' => (string) $subject->slug,
                        'name' => $this->label($subject),
                        'node_type' => (string) $subject->node_type,
                    ])
                    ->all();

                $subjectsByLevel[$levelSlug] = $subjects;
                $nodesByLevel[$levelSlug] = $classNodes
                    ->map(function ($classNode) use ($childrenByParent, $subjects, $levelSlug) {
                        $actualSubjects = collect($childrenByParent[(string) $classNode->id] ?? [])->keyBy('slug');

                        return [
                            'id' => (int) $classNode->id,
                            'slug' => (string) $classNode->slug,
                            'label' => $this->label($classNode),
                            'node_type' => (string) $classNode->node_type,
                            'children' => collect($subjects)
                                ->map(function (array $subject) use ($actualSubjects, $classNode, $levelSlug) {
                                    $actual = $actualSubjects->get($subject['slug']);

                                    return [
                                        'id' => $actual?->id
                                            ? (int) $actual->id
                                            : $levelSlug.'-'.$classNode->slug.'-'.$subject['slug'],
                                        'slug' => $subject['slug'],
                                        'label' => $actual ? $this->label($actual) : $subject['name'],
                                        'node_type' => $subject['node_type'],
                                        'children' => [],
                                    ];
                                })
                                ->all(),
                        ];
                    })
                    ->all();
            }

            return [
                'education_levels' => $educationLevels,
                'class_grades_by_level' => $classGradesByLevel,
                'subjects_by_level' => $subjectsByLevel,
                'nodes_by_level' => $nodesByLevel,
                'tree' => $tree,
                'source' => 'database',
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function configurationSnapshot(): array
    {
        $educationLevels = config('product_taxonomy.education_levels', []);
        $classGradesByLevel = config('product_taxonomy.class_grades_by_level', []);
        $subjectsByLevel = config('product_taxonomy.subjects_by_level', []);
        $nodesByLevel = [];

        foreach ($classGradesByLevel as $levelSlug => $classes) {
            $subjects = $subjectsByLevel[$levelSlug] ?? [];
            $nodesByLevel[$levelSlug] = collect($classes)
                ->map(fn (array $class) => [
                    'id' => $levelSlug.'-'.$class['slug'],
                    'slug' => $class['slug'],
                    'label' => $class['label'],
                    'children' => collect($subjects)
                        ->map(fn (array $subject) => [
                            'id' => $levelSlug.'-'.$class['slug'].'-'.$subject['slug'],
                            'slug' => $subject['slug'],
                            'label' => $subject['name'],
                            'children' => [],
                        ])
                        ->all(),
                ])
                ->all();
        }
        $tree = collect($educationLevels)
            ->map(function (array $level) use ($classGradesByLevel, $subjectsByLevel): array {
                $levelSlug = (string) $level['slug'];
                $subjects = collect($subjectsByLevel[$levelSlug] ?? []);

                return [
                    'id' => $level['id'] ?? $levelSlug,
                    'slug' => $levelSlug,
                    'label' => (string) ($level['label'] ?? $levelSlug),
                    'node_type' => CourseCategory::TYPE_EDUCATION_LEVEL,
                    'order' => (int) ($level['order'] ?? 0),
                    'children' => collect($classGradesByLevel[$levelSlug] ?? [])
                        ->map(function (array $class, int $index) use ($levelSlug, $subjects): array {
                            return [
                                'id' => $class['id'] ?? $levelSlug.'-'.$class['slug'],
                                'slug' => (string) $class['slug'],
                                'label' => (string) ($class['label'] ?? $class['slug']),
                                'node_type' => (string) ($class['node_type'] ?? CourseCategory::TYPE_GRADE),
                                'order' => (int) ($class['order'] ?? $index),
                                'children' => $subjects
                                    ->values()
                                    ->map(fn (array $subject, int $subjectIndex) => [
                                        'id' => $subject['id'] ?? $levelSlug.'-'.$class['slug'].'-'.$subject['slug'],
                                        'slug' => (string) $subject['slug'],
                                        'label' => (string) ($subject['name'] ?? $subject['slug']),
                                        'node_type' => (string) ($subject['node_type'] ?? CourseCategory::TYPE_SUBJECT),
                                        'order' => (int) ($subject['order'] ?? $subjectIndex),
                                        'children' => [],
                                    ])
                                    ->all(),
                            ];
                        })
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'education_levels' => $educationLevels,
            'class_grades_by_level' => $classGradesByLevel,
            'subjects_by_level' => $subjectsByLevel,
            'nodes_by_level' => $nodesByLevel,
            'tree' => $tree,
            'source' => 'configuration',
        ];
    }

    private function label(object $node): string
    {
        return (string) ($node->translation_name ?: $node->slug);
    }

    private function cacheKey(string $languageCode): string
    {
        return 'product_taxonomy_catalog_'.self::CACHE_VERSION.'_'.strtolower($languageCode);
    }
}
