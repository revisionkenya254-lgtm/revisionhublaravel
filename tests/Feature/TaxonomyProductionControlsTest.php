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

    public function test_education_taxonomy_api_exposes_unfiltered_managed_nodes_and_revision(): void
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
        app(TaxonomyManagementService::class)->createNode(null, [
            'name' => 'Adult Learning',
            'slug' => 'adult-learning',
            'node_type' => CourseCategory::TYPE_EDUCATION_LEVEL,
            'order' => 99,
        ]);

        $response = $this->getJson('/api/bootstrap/education-taxonomy');
        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('taxonomy_revision', $revision->current())
            ->assertHeader('X-Taxonomy-Revision', (string) $revision->current());

        $taxonomy = $response->json('data');
        $this->assertSame('database', $taxonomy['source']);
        $this->assertTrue(collect($taxonomy['subjects_by_level']['junior-school'])
            ->contains('slug', 'robotics'));
        $this->assertTrue(collect($taxonomy['education_levels'])
            ->contains('slug', 'adult-learning'));
        $this->assertTrue(collect($taxonomy['tree'])->contains('slug', 'adult-learning'));
        $gradeSeven = collect($taxonomy['tree'])
            ->firstWhere('slug', 'junior-school')['children'];
        $gradeSeven = collect($gradeSeven)->firstWhere('slug', 'grade-7');
        $this->assertTrue(collect($gradeSeven['children'])->contains('slug', 'robotics'));
        $this->assertGreaterThan($before, $revision->current());
    }

    public function test_main_category_api_refreshes_cached_taxonomy_labels_after_updates(): void
    {
        $level = CourseCategory::whereNull('parent_id')->where('slug', 'junior-school')->firstOrFail();
        $this->getJson('/api/bootstrap/categories?language=en')->assertOk();

        app(TaxonomyManagementService::class)->updateNode($level, [
            'name' => 'Junior Academy',
            'slug' => 'junior-school',
            'node_type' => CourseCategory::TYPE_EDUCATION_LEVEL,
            'order' => $level->order,
        ]);

        $response = $this->getJson('/api/bootstrap/categories?language=en')->assertOk();
        $juniorSchool = collect($response->json('data'))->firstWhere('slug', 'junior-school');

        $this->assertSame('Junior Academy', $juniorSchool['name']);
        $response->assertJsonPath('taxonomy_revision', app(TaxonomyRevisionService::class)->current());
    }

    public function test_main_category_api_uses_admin_taxonomy_as_its_source_of_truth(): void
    {
        $level = app(TaxonomyManagementService::class)->createNode(null, [
            'name' => 'Adult Learning',
            'slug' => 'adult-learning',
            'node_type' => CourseCategory::TYPE_EDUCATION_LEVEL,
            'order' => 99,
        ]);

        $response = $this->getJson('/api/bootstrap/categories?language=en')->assertOk();

        $this->assertTrue(collect($response->json('data'))->contains('slug', 'adult-learning'));

        app(TaxonomyManagementService::class)->archiveNode($level);

        $response = $this->getJson('/api/bootstrap/categories?language=en')->assertOk();

        $this->assertFalse(collect($response->json('data'))->contains('slug', 'adult-learning'));
    }

    public function test_main_category_api_returns_the_complete_nested_taxonomy(): void
    {
        $response = $this->getJson('/api/bootstrap/categories?language=en')->assertOk();
        $juniorSchool = collect($response->json('data'))->firstWhere('slug', 'junior-school');

        $this->assertNotNull($juniorSchool);
        $this->assertNull($juniorSchool['parent_id']);
        $this->assertSame(CourseCategory::TYPE_EDUCATION_LEVEL, $juniorSchool['node_type']);

        $gradeSeven = collect($juniorSchool['children'])->firstWhere('slug', 'grade-7');

        $this->assertNotNull($gradeSeven);
        $this->assertSame($juniorSchool['id'], $gradeSeven['parent_id']);
        $this->assertTrue(collect($gradeSeven['children'])->contains('slug', 'mathematics'));
    }
}
