<?php

namespace App\Services\Taxonomy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Course\app\Models\CourseCategory;
use Modules\Course\app\Service\CourseCategoryCacheClear;
use RuntimeException;
use Throwable;

class TaxonomyHierarchyUnifier
{
    private const HIGH_SCHOOL_ALIASES = [
        'history' => 'history-and-government',
        'cre' => 'christian-religious-education-cre',
        'ire' => 'islamic-religious-education-ire',
        'hre' => 'hindu-religious-education-hre',
    ];

    public function unify(bool $dryRun = false, bool $failOnConflicts = false): array
    {
        $report = [
            'dry_run' => $dryRun,
            'references_remapped' => 0,
            'legacy_nodes_archived' => 0,
            'conflicts' => [],
        ];

        DB::beginTransaction();

        try {
            $this->unifySeniorSchool($report);
            $this->unifyHighSchoolAliases($report);

            if (($failOnConflicts || ! $dryRun) && $report['conflicts'] !== []) {
                throw new RuntimeException(
                    'Taxonomy hierarchy unification stopped: '.implode(' | ', $report['conflicts'])
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

    private function unifySeniorSchool(array &$report): void
    {
        $conflictCountBefore = count($report['conflicts']);
        $root = DB::table('course_categories')
            ->whereNull('parent_id')
            ->where('slug', 'senior-school-cbc')
            ->whereNull('deleted_at')
            ->first();

        if (! $root) {
            $report['conflicts'][] = 'Senior School root is missing.';

            return;
        }

        $legacyPathways = DB::table('course_categories')
            ->where('parent_id', $root->id)
            ->where('node_type', CourseCategory::TYPE_PATHWAY)
            ->whereNull('deleted_at')
            ->get();

        foreach ($legacyPathways as $legacyPathway) {
            $legacyGrades = DB::table('course_categories')
                ->where('parent_id', $legacyPathway->id)
                ->whereIn('node_type', [CourseCategory::TYPE_GRADE, CourseCategory::TYPE_NATIONAL_EXAM])
                ->whereNull('deleted_at')
                ->get();

            foreach ($legacyGrades as $legacyGrade) {
                $target = $this->canonicalSeniorLeaf(
                    (int) $root->id,
                    (string) $legacyGrade->slug,
                    (string) $legacyPathway->slug
                );

                if (! $target) {
                    $report['conflicts'][] = sprintf(
                        'Missing canonical Senior School target for %s/%s.',
                        $legacyGrade->slug,
                        $legacyPathway->slug
                    );
                    continue;
                }

                $this->remapSimpleReferences((int) $legacyGrade->id, (int) $target->id, $report);
            }

            $this->remapDirectSeniorPathwayReferences((int) $root->id, $legacyPathway, $report);
        }

        if (count($report['conflicts']) > $conflictCountBefore) {
            return;
        }

        foreach ($legacyPathways as $legacyPathway) {
            $legacyGradeIds = DB::table('course_categories')
                ->where('parent_id', $legacyPathway->id)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $this->archiveNodes($legacyGradeIds, $report);
            $this->archiveNodes([(int) $legacyPathway->id], $report);
        }
    }

    private function unifyHighSchoolAliases(array &$report): void
    {
        $rootId = DB::table('course_categories')
            ->whereNull('parent_id')
            ->where('slug', 'high-school')
            ->whereNull('deleted_at')
            ->value('id');

        if (! $rootId) {
            return;
        }

        $classIds = DB::table('course_categories')
            ->where('parent_id', $rootId)
            ->whereIn('node_type', [
                CourseCategory::TYPE_FORM,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ])
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($classIds as $classId) {
            foreach (self::HIGH_SCHOOL_ALIASES as $legacySlug => $canonicalSlug) {
                $conflictCountBefore = count($report['conflicts']);
                $legacy = DB::table('course_categories')
                    ->where('parent_id', $classId)
                    ->where('slug', $legacySlug)
                    ->whereNull('deleted_at')
                    ->first();

                if (! $legacy) {
                    continue;
                }

                $canonical = DB::table('course_categories')
                    ->where('parent_id', $classId)
                    ->where('slug', $canonicalSlug)
                    ->whereNull('deleted_at')
                    ->first();

                if (! $canonical) {
                    $report['conflicts'][] = sprintf(
                        'Missing canonical High School subject %s under category %s.',
                        $canonicalSlug,
                        $classId
                    );
                    continue;
                }

                $this->remapSimpleReferences((int) $legacy->id, (int) $canonical->id, $report);

                if (count($report['conflicts']) === $conflictCountBefore) {
                    $this->archiveNodes([(int) $legacy->id], $report);
                }
            }
        }
    }

    private function remapDirectSeniorPathwayReferences(int $rootId, object $legacyPathway, array &$report): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            $products = DB::table('products')->where('category_id', $legacyPathway->id)->get(['id', 'metadata']);

            foreach ($products as $product) {
                $metadata = json_decode((string) $product->metadata, true) ?: [];
                $gradeSlug = $this->gradeSlugFromValues([
                    data_get($metadata, 'class_grade'),
                    data_get($metadata, 'catalog_identity.category'),
                    data_get($metadata, 'catalog_identity.category_label'),
                ]);
                $target = $gradeSlug
                    ? $this->canonicalSeniorLeaf($rootId, $gradeSlug, (string) $legacyPathway->slug)
                    : null;

                if (! $target) {
                    $report['conflicts'][] = "Product {$product->id} references legacy pathway {$legacyPathway->slug} without a resolvable grade.";
                    continue;
                }

                DB::table('products')->where('id', $product->id)->update(['category_id' => $target->id]);
                $report['references_remapped']++;
            }
        }

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'category_id')) {
            $courses = DB::table('courses')
                ->where('category_id', $legacyPathway->id)
                ->get(['id', 'class_grade']);

            foreach ($courses as $course) {
                $gradeSlug = $this->gradeSlugFromValues([$course->class_grade]);
                $target = $gradeSlug
                    ? $this->canonicalSeniorLeaf($rootId, $gradeSlug, (string) $legacyPathway->slug)
                    : null;

                if (! $target) {
                    $report['conflicts'][] = "Course {$course->id} references legacy pathway {$legacyPathway->slug} without a resolvable grade.";
                    continue;
                }

                DB::table('courses')->where('id', $course->id)->update(['category_id' => $target->id]);
                $report['references_remapped']++;
            }
        }

        foreach ($this->genericReferenceColumns() as [$table, $column]) {
            $count = DB::table($table)->where($column, $legacyPathway->id)->count();

            if ($count > 0) {
                $report['conflicts'][] = sprintf(
                    '%s.%s has %d ambiguous references to legacy pathway %s.',
                    $table,
                    $column,
                    $count,
                    $legacyPathway->slug
                );
            }
        }
    }

    private function remapSimpleReferences(int $fromId, int $toId, array &$report): void
    {
        foreach ($this->allReferenceColumns() as [$table, $column]) {
            $updated = DB::table($table)->where($column, $fromId)->update([$column => $toId]);
            $report['references_remapped'] += $updated;
        }
    }

    private function allReferenceColumns(): array
    {
        return array_merge(
            $this->existingReferenceColumns([
                ['products', 'category_id'],
                ['courses', 'category_id'],
            ]),
            $this->genericReferenceColumns()
        );
    }

    private function genericReferenceColumns(): array
    {
        return $this->existingReferenceColumns([
            ['user_skill_topics', 'category_id'],
            ['course_filters', 'course_category_id'],
        ]);
    }

    private function existingReferenceColumns(array $references): array
    {
        return array_values(array_filter(
            $references,
            fn (array $reference) => Schema::hasTable($reference[0])
                && Schema::hasColumn($reference[0], $reference[1])
        ));
    }

    private function canonicalSeniorLeaf(int $rootId, string $gradeSlug, string $pathwaySlug): ?object
    {
        return DB::table('course_categories as pathway')
            ->join('course_categories as grade', 'grade.id', '=', 'pathway.parent_id')
            ->where('grade.parent_id', $rootId)
            ->where('grade.slug', $gradeSlug)
            ->whereIn('grade.node_type', [CourseCategory::TYPE_GRADE, CourseCategory::TYPE_NATIONAL_EXAM])
            ->where('pathway.slug', $pathwaySlug)
            ->where('pathway.node_type', CourseCategory::TYPE_PATHWAY)
            ->whereNull('grade.deleted_at')
            ->whereNull('pathway.deleted_at')
            ->select('pathway.id')
            ->first();
    }

    private function gradeSlugFromValues(array $values): ?string
    {
        $allowed = collect(config('product_taxonomy.class_grades_by_level.senior-school-cbc', []))
            ->pluck('slug')
            ->all();

        foreach ($values as $value) {
            $slug = Str::slug((string) $value);

            if (in_array($slug, $allowed, true)) {
                return $slug;
            }
        }

        return null;
    }

    private function archiveNodes(array $ids, array &$report): void
    {
        if ($ids === []) {
            return;
        }

        $archived = DB::table('course_categories')
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->update([
                'node_type' => CourseCategory::TYPE_LEGACY,
                'status' => 0,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        $report['legacy_nodes_archived'] += $archived;
    }
}
