<?php

namespace Tests\Feature;

use App\Services\Taxonomy\TaxonomyHierarchyUnifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Models\CourseCategory;
use Tests\TestCase;

class TaxonomyHierarchyUnifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_grade_and_exam_nodes_are_active_directly_under_senior_school(): void
    {
        $seniorSchool = CourseCategory::whereNull('parent_id')
            ->where('slug', 'senior-school-cbc')
            ->firstOrFail();

        $children = CourseCategory::where('parent_id', $seniorSchool->id)
            ->orderBy('order')
            ->get();

        $this->assertSame(
            ['grade-10', 'grade-11', 'grade-12', 'kcbe'],
            $children->pluck('slug')->all()
        );
        $this->assertTrue($children->every(
            fn (CourseCategory $node) => in_array($node->node_type, [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ], true)
        ));

        foreach ($children as $child) {
            $this->assertSame(
                ['stem', 'social-sciences', 'arts-and-sports', 'languages'],
                CourseCategory::where('parent_id', $child->id)
                    ->orderBy('order')
                    ->pluck('slug')
                    ->all()
            );
        }
    }

    public function test_it_remaps_legacy_senior_leaf_references_before_archiving(): void
    {
        [$legacyStem, $legacyGradeTen, $canonicalStem] = $this->restoreLegacySeniorStemGradeTen();

        $productId = DB::table('products')->insertGetId([
            'title' => 'Legacy Senior Paper',
            'slug' => 'legacy-senior-paper',
            'type' => 'past_paper',
            'category_id' => $legacyGradeTen->id,
            'metadata' => json_encode(['education_level' => 'senior-school-cbc']),
            'status' => 1,
            'is_approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(TaxonomyHierarchyUnifier::class)->unify();

        $this->assertSame($canonicalStem->id, DB::table('products')->where('id', $productId)->value('category_id'));
        $this->assertGreaterThanOrEqual(1, $report['references_remapped']);
        $this->assertSame([], $report['conflicts']);
        $this->assertSoftDeleted('course_categories', ['id' => $legacyStem->id]);
        $this->assertSoftDeleted('course_categories', ['id' => $legacyGradeTen->id]);
    }

    public function test_it_resolves_a_direct_legacy_pathway_reference_from_product_metadata(): void
    {
        [$legacyStem, , $canonicalStem] = $this->restoreLegacySeniorStemGradeTen();

        $productId = DB::table('products')->insertGetId([
            'title' => 'Legacy Pathway Paper',
            'slug' => 'legacy-pathway-paper',
            'type' => 'past_paper',
            'category_id' => $legacyStem->id,
            'metadata' => json_encode(['class_grade' => 'Grade 10']),
            'status' => 1,
            'is_approved' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(TaxonomyHierarchyUnifier::class)->unify();

        $this->assertSame($canonicalStem->id, DB::table('products')->where('id', $productId)->value('category_id'));
    }

    public function test_unification_is_idempotent(): void
    {
        $before = DB::table('course_categories')->whereNull('deleted_at')->orderBy('id')->pluck('id')->all();

        $report = app(TaxonomyHierarchyUnifier::class)->unify();

        $after = DB::table('course_categories')->whereNull('deleted_at')->orderBy('id')->pluck('id')->all();
        $this->assertSame($before, $after);
        $this->assertSame(0, $report['references_remapped']);
        $this->assertSame(0, $report['legacy_nodes_archived']);
        $this->assertSame([], $report['conflicts']);
    }

    public function test_high_school_alias_subjects_are_archived(): void
    {
        $highSchool = CourseCategory::whereNull('parent_id')->where('slug', 'high-school')->firstOrFail();
        $formThree = CourseCategory::where('parent_id', $highSchool->id)->where('slug', 'form-3')->firstOrFail();

        $history = CourseCategory::withTrashed()
            ->where('parent_id', $formThree->id)
            ->where('slug', 'history')
            ->firstOrFail();
        $canonical = CourseCategory::where('parent_id', $formThree->id)
            ->where('slug', 'history-and-government')
            ->firstOrFail();

        $this->assertTrue($history->trashed());
        $this->assertSame(CourseCategory::TYPE_LEGACY, $history->node_type);
        $this->assertFalse($canonical->trashed());
        $this->assertSame(CourseCategory::TYPE_SUBJECT, $canonical->node_type);
    }

    private function restoreLegacySeniorStemGradeTen(): array
    {
        $seniorSchool = CourseCategory::whereNull('parent_id')
            ->where('slug', 'senior-school-cbc')
            ->firstOrFail();
        $legacyStem = CourseCategory::withTrashed()
            ->where('parent_id', $seniorSchool->id)
            ->where('slug', 'stem')
            ->firstOrFail();
        $legacyGradeTen = CourseCategory::withTrashed()
            ->where('parent_id', $legacyStem->id)
            ->where('slug', 'grade-10')
            ->firstOrFail();
        $canonicalGradeTen = CourseCategory::where('parent_id', $seniorSchool->id)
            ->where('slug', 'grade-10')
            ->firstOrFail();
        $canonicalStem = CourseCategory::where('parent_id', $canonicalGradeTen->id)
            ->where('slug', 'stem')
            ->firstOrFail();

        DB::table('course_categories')->whereIn('id', [$legacyStem->id, $legacyGradeTen->id])->update([
            'deleted_at' => null,
            'status' => 1,
        ]);
        DB::table('course_categories')->where('id', $legacyStem->id)->update([
            'node_type' => CourseCategory::TYPE_PATHWAY,
        ]);
        DB::table('course_categories')->where('id', $legacyGradeTen->id)->update([
            'node_type' => CourseCategory::TYPE_GRADE,
        ]);

        return [$legacyStem, $legacyGradeTen, $canonicalStem];
    }
}
