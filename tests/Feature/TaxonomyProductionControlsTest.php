<?php

namespace Tests\Feature;

use App\Models\TaxonomyChangeLog;
use App\Services\Taxonomy\TaxonomyConsistencyService;
use App\Services\Taxonomy\TaxonomyManagementService;
use App\Services\Taxonomy\TaxonomyRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Models\CourseCategory;
use Tests\TestCase;

class TaxonomyProductionControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_managed_changes_are_audited_and_increment_the_taxonomy_revision(): void
    {
        $revision = app(TaxonomyRevisionService::class);
        $before = $revision->current();
        $level = CourseCategory::whereNull('parent_id')->where('slug', 'junior-school')->firstOrFail();

        app(TaxonomyManagementService::class)->syncSharedLeaf($level, null, [
            'name' => 'Robotics',
            'slug' => 'robotics',
            'node_type' => CourseCategory::TYPE_SUBJECT,
            'order' => 90,
        ]);

        $this->assertSame($before + 1, $revision->current());
        $log = TaxonomyChangeLog::latest()->firstOrFail();
        $this->assertSame('shared_leaf_created', $log->action);
        $this->assertSame($level->id, $log->context['education_level_id']);
        $this->assertNotEmpty($log->after);
    }

    public function test_consistency_check_detects_and_repairs_shared_subject_drift(): void
    {
        $level = CourseCategory::whereNull('parent_id')->where('slug', 'junior-school')->firstOrFail();
        $classIds = CourseCategory::where('parent_id', $level->id)
            ->whereIn('node_type', [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ])
            ->pluck('id');
        $copy = CourseCategory::whereIn('parent_id', $classIds)
            ->where('slug', 'mathematics')
            ->firstOrFail();
        DB::table('course_categories')->where('id', $copy->id)->update(['order' => 777]);

        $before = app(TaxonomyConsistencyService::class)->inspect();
        $this->assertFalse($before['healthy']);
        $this->assertContains('shared_leaf_drift', array_column($before['issues_after'], 'type'));

        $after = app(TaxonomyConsistencyService::class)->inspect(true);
        $this->assertTrue($after['healthy']);
        $this->assertNotEmpty($after['repairs']);
    }

    public function test_menu_and_category_apis_expose_the_taxonomy_revision(): void
    {
        $revision = app(TaxonomyRevisionService::class)->current();

        $menu = $this->getJson('/api/bootstrap/menu');
        $menu->assertOk()
            ->assertJsonPath('data.taxonomy_revision', $revision)
            ->assertHeader('X-Taxonomy-Revision', (string) $revision);

        $categories = $this->getJson('/api/catalog/categories/junior-school/subcategories');
        $categories->assertOk()
            ->assertJsonPath('taxonomy_revision', $revision)
            ->assertHeader('X-Taxonomy-Revision', (string) $revision);
    }
}
