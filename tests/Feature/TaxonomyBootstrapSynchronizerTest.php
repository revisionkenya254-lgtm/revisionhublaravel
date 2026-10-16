<?php

namespace Tests\Feature;

use App\Services\Taxonomy\TaxonomyBootstrapSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Models\CourseCategory;
use Tests\TestCase;

class TaxonomyBootstrapSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_canonical_school_trees_from_configuration(): void
    {
        foreach (config('product_taxonomy.class_grades_by_level') as $levelSlug => $expectedNodes) {
            $root = CourseCategory::whereNull('parent_id')->where('slug', $levelSlug)->firstOrFail();
            $actualNodes = CourseCategory::where('parent_id', $root->id)
                ->whereIn('node_type', [
                    CourseCategory::TYPE_GRADE,
                    CourseCategory::TYPE_FORM,
                    CourseCategory::TYPE_NATIONAL_EXAM,
                ])
                ->orderBy('order')
                ->get();

            $this->assertSame(
                array_column($expectedNodes, 'slug'),
                $actualNodes->pluck('slug')->all(),
                $levelSlug
            );

            foreach ($actualNodes as $node) {
                $this->assertSame(
                    array_column(config("product_taxonomy.subjects_by_level.{$levelSlug}"), 'slug'),
                    CourseCategory::where('parent_id', $node->id)
                        ->whereIn('node_type', [
                            CourseCategory::TYPE_SUBJECT,
                            CourseCategory::TYPE_PATHWAY,
                        ])
                        ->orderBy('order')
                        ->pluck('slug')
                        ->all(),
                    $levelSlug.'/'.$node->slug
                );
            }
        }
    }

    public function test_it_preserves_legacy_senior_ids_as_archived_rows_after_unification(): void
    {
        $seniorSchool = CourseCategory::whereNull('parent_id')
            ->where('slug', 'senior-school-cbc')
            ->firstOrFail();

        $legacyStem = CourseCategory::withTrashed()->where('parent_id', $seniorSchool->id)
            ->where('slug', 'stem')
            ->firstOrFail();
        $legacyGradeTen = CourseCategory::withTrashed()->where('parent_id', $legacyStem->id)
            ->where('slug', 'grade-10')
            ->firstOrFail();

        $canonicalGradeTen = CourseCategory::where('parent_id', $seniorSchool->id)
            ->where('slug', 'grade-10')
            ->firstOrFail();
        $canonicalStem = CourseCategory::where('parent_id', $canonicalGradeTen->id)
            ->where('slug', 'stem')
            ->firstOrFail();

        $this->assertNotSame($legacyGradeTen->id, $canonicalGradeTen->id);
        $this->assertTrue($legacyStem->trashed());
        $this->assertTrue($legacyGradeTen->trashed());
        $this->assertSame(CourseCategory::TYPE_LEGACY, $legacyStem->node_type);
        $this->assertSame(CourseCategory::TYPE_LEGACY, $legacyGradeTen->node_type);
        $this->assertSame(CourseCategory::TYPE_GRADE, $canonicalGradeTen->node_type);
        $this->assertSame(CourseCategory::TYPE_PATHWAY, $canonicalStem->node_type);
    }

    public function test_sync_is_idempotent_and_preserves_existing_ids(): void
    {
        $before = DB::table('course_categories')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('id', 'id')
            ->all();

        $report = app(TaxonomyBootstrapSynchronizer::class)->sync();

        $after = DB::table('course_categories')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('id', 'id')
            ->all();

        $this->assertSame($before, $after);
        $this->assertSame(0, $report['created']);
        $this->assertSame(0, $report['updated']);
        $this->assertSame(0, $report['restored']);
        $this->assertSame(0, $report['translations_created']);
        $this->assertSame([], $report['conflicts']);
    }

    public function test_dry_run_reports_duplicate_sibling_conflicts_without_additional_writes(): void
    {
        $juniorSchool = CourseCategory::whereNull('parent_id')
            ->where('slug', 'junior-school')
            ->firstOrFail();
        CourseCategory::create([
            'slug' => 'grade-7',
            'parent_id' => $juniorSchool->id,
            'node_type' => CourseCategory::TYPE_GRADE,
            'status' => true,
        ]);
        $beforeCount = CourseCategory::count();

        $report = app(TaxonomyBootstrapSynchronizer::class)->sync(true);

        $this->assertCount(1, $report['conflicts']);
        $this->assertStringContainsString('grade-7', $report['conflicts'][0]);
        $this->assertSame($beforeCount, CourseCategory::count());
    }
}
