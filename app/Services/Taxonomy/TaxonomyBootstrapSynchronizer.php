<?php

namespace App\Services\Taxonomy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Course\app\Models\CourseCategory;
use Modules\Course\app\Service\CourseCategoryCacheClear;
use RuntimeException;
use Throwable;

class TaxonomyBootstrapSynchronizer
{
    /**
     * Classify legacy rows and add missing canonical school branches.
     * Existing nodes are updated in place whenever their parent path matches.
     */
    public function sync(bool $dryRun = false, bool $failOnConflicts = false): array
    {
        $report = [
            'dry_run' => $dryRun,
            'created' => 0,
            'updated' => 0,
            'restored' => 0,
            'unchanged' => 0,
            'translations_created' => 0,
            'legacy_paths_preserved' => [],
            'conflicts' => [],
        ];

        if (! Schema::hasTable('course_categories')
            || ! Schema::hasColumn('course_categories', 'node_type')) {
            $report['conflicts'][] = 'Dynamic taxonomy columns have not been migrated.';

            return $report;
        }

        DB::beginTransaction();

        try {
            $this->classifyExistingNodes($report);
            $this->syncCanonicalSchoolTrees($report);

            if ($failOnConflicts && $report['conflicts'] !== []) {
                throw new RuntimeException(
                    'Taxonomy bootstrap stopped because conflicts were found: '
                    .implode(' | ', $report['conflicts'])
                );
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                CourseCategoryCacheClear::clear();
            }
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $exception;
        }

        return $report;
    }

    private function classifyExistingNodes(array &$report): void
    {
        $nodes = DB::table('course_categories')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'slug', 'parent_id', 'node_type'])
            ->keyBy('id');

        foreach ($nodes as $node) {
            $expectedType = $this->inferNodeType($node, $nodes);

            if ($node->node_type === $expectedType) {
                $report['unchanged']++;
                continue;
            }

            DB::table('course_categories')
                ->where('id', $node->id)
                ->update([
                    'node_type' => $expectedType,
                    'updated_at' => now(),
                ]);

            $report['updated']++;
        }
    }

    private function inferNodeType(object $node, $nodes): string
    {
        if ($node->parent_id === null) {
            return CourseCategory::TYPE_EDUCATION_LEVEL;
        }

        $slug = strtolower((string) $node->slug);

        if (in_array($slug, ['kpsea', 'kjsea', 'kcbe', 'kcse'], true)) {
            return CourseCategory::TYPE_NATIONAL_EXAM;
        }

        if (preg_match('/^grade-[0-9]+$/', $slug) || preg_match('/^pp[0-9]+$/', $slug)) {
            return CourseCategory::TYPE_GRADE;
        }

        if (preg_match('/^form-[0-9]+$/', $slug)) {
            return CourseCategory::TYPE_FORM;
        }

        $rootSlug = $this->rootSlugFor($node, $nodes);

        if ($rootSlug === 'high-school' && in_array($slug, ['history', 'cre', 'ire', 'hre'], true)) {
            return CourseCategory::TYPE_LEGACY;
        }

        if ($rootSlug === 'senior-school-cbc'
            && in_array($slug, ['stem', 'social-sciences', 'arts-and-sports', 'languages'], true)) {
            return CourseCategory::TYPE_PATHWAY;
        }

        if ($rootSlug === 'teacher-resources') {
            return CourseCategory::TYPE_RESOURCE;
        }

        if ($rootSlug === 'undergraduate') {
            $parent = $nodes->get($node->parent_id);

            return $parent?->parent_id === null
                ? CourseCategory::TYPE_SCHOOL
                : CourseCategory::TYPE_COURSE;
        }

        if (in_array($rootSlug, [
            'pre-primary',
            'lower-primary',
            'upper-primary',
            'junior-school',
            'senior-school-cbc',
            'high-school',
        ], true)) {
            return CourseCategory::TYPE_SUBJECT;
        }

        return CourseCategory::TYPE_COURSE;
    }

    private function rootSlugFor(object $node, $nodes): string
    {
        $current = $node;
        $visited = [];

        while ($current->parent_id !== null) {
            if (in_array((int) $current->id, $visited, true)) {
                return '';
            }

            $visited[] = (int) $current->id;
            $parent = $nodes->get($current->parent_id);

            if (! $parent) {
                return '';
            }

            $current = $parent;
        }

        return strtolower((string) $current->slug);
    }

    private function syncCanonicalSchoolTrees(array &$report): void
    {
        $educationLevels = collect(config('product_taxonomy.education_levels', []))->keyBy('slug');
        $classGradesByLevel = config('product_taxonomy.class_grades_by_level', []);
        $subjectsByLevel = config('product_taxonomy.subjects_by_level', []);

        foreach ($classGradesByLevel as $levelOrder => $levelNodes) {
            $level = $educationLevels->get($levelOrder, [
                'slug' => $levelOrder,
                'label' => $levelOrder,
            ]);
            $rootId = $this->ensureNode(
                null,
                (string) $level['slug'],
                (string) $level['label'],
                CourseCategory::TYPE_EDUCATION_LEVEL,
                ((int) $educationLevels->keys()->search($levelOrder)) + 1,
                $report
            );

            foreach ($levelNodes as $nodeOrder => $node) {
                $nodeType = $this->typeForClassGradeSlug((string) $node['slug']);
                $this->recordLegacyPaths($rootId, (string) $node['slug'], $report);

                $nodeId = $this->ensureNode(
                    $rootId,
                    (string) $node['slug'],
                    (string) $node['label'],
                    $nodeType,
                    $nodeOrder + 1,
                    $report
                );

                foreach (($subjectsByLevel[$levelOrder] ?? []) as $subjectOrder => $subject) {
                    $subjectType = $levelOrder === 'senior-school-cbc'
                        ? CourseCategory::TYPE_PATHWAY
                        : CourseCategory::TYPE_SUBJECT;

                    $this->ensureNode(
                        $nodeId,
                        (string) $subject['slug'],
                        (string) $subject['name'],
                        $subjectType,
                        $subjectOrder + 1,
                        $report
                    );
                }
            }
        }
    }

    private function ensureNode(
        ?int $parentId,
        string $slug,
        string $label,
        string $nodeType,
        int $order,
        array &$report
    ): int {
        $matches = DB::table('course_categories')
            ->where('slug', $slug)
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId)
            )
            ->orderByRaw('deleted_at IS NULL DESC')
            ->orderBy('id')
            ->get();

        $activeMatches = $matches->whereNull('deleted_at');

        if ($activeMatches->count() > 1) {
            $report['conflicts'][] = sprintf(
                'Duplicate active sibling slug "%s" under parent %s (IDs: %s).',
                $slug,
                $parentId ?? 'root',
                $activeMatches->pluck('id')->implode(', ')
            );
        }

        $existing = $activeMatches->first() ?? $matches->first();

        if (! $existing) {
            $categoryId = (int) DB::table('course_categories')->insertGetId([
                'slug' => $slug,
                'parent_id' => $parentId,
                'node_type' => $nodeType,
                'order' => $order,
                'show_at_trending' => $parentId === null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $report['created']++;
        } else {
            $categoryId = (int) $existing->id;
            $updates = [];

            if ($existing->node_type !== $nodeType) {
                $updates['node_type'] = $nodeType;
            }
            if ((int) $existing->order !== $order) {
                $updates['order'] = $order;
            }
            if ($existing->deleted_at !== null) {
                $updates['deleted_at'] = null;
                $report['restored']++;
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('course_categories')->where('id', $categoryId)->update($updates);
                $report['updated']++;
            } else {
                $report['unchanged']++;
            }
        }

        $hasEnglishTranslation = DB::table('course_category_translations')
            ->where('course_category_id', $categoryId)
            ->where('lang_code', 'en')
            ->exists();

        if (! $hasEnglishTranslation) {
            DB::table('course_category_translations')->insert([
                'course_category_id' => $categoryId,
                'lang_code' => 'en',
                'name' => $label,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $report['translations_created']++;
        }

        return $categoryId;
    }

    private function typeForClassGradeSlug(string $slug): string
    {
        if (in_array($slug, ['kpsea', 'kjsea', 'kcbe', 'kcse'], true)) {
            return CourseCategory::TYPE_NATIONAL_EXAM;
        }

        return str_starts_with($slug, 'form-')
            ? CourseCategory::TYPE_FORM
            : CourseCategory::TYPE_GRADE;
    }

    private function recordLegacyPaths(int $rootId, string $slug, array &$report): void
    {
        $directMatchExists = DB::table('course_categories')
            ->where('parent_id', $rootId)
            ->where('slug', $slug)
            ->whereNull('deleted_at')
            ->exists();

        if ($directMatchExists) {
            return;
        }

        $legacyMatches = DB::table('course_categories as node')
            ->join('course_categories as parent', 'parent.id', '=', 'node.parent_id')
            ->where('node.slug', $slug)
            ->where('parent.parent_id', $rootId)
            ->whereNull('node.deleted_at')
            ->pluck('node.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($legacyMatches !== []) {
            $report['legacy_paths_preserved'][] = [
                'slug' => $slug,
                'ids' => $legacyMatches,
            ];
        }
    }
}
