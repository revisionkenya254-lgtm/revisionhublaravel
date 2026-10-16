<?php

namespace Tests\Feature;

use App\Services\ProductMetadataCatalogService;
use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Models\CourseCategory;
use Modules\Course\app\Models\CourseCategoryTranslation;
use Tests\TestCase;

class DatabaseBackedTaxonomyCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TaxonomyCatalogRepository::class)->clearCache();
    }

    public function test_runtime_catalog_is_read_from_active_database_taxonomy(): void
    {
        $repository = app(TaxonomyCatalogRepository::class);
        $catalog = app(ProductMetadataCatalogService::class);

        $this->assertSame('database', $repository->snapshot()['source']);
        $this->assertSame(
            ['grade-10', 'grade-11', 'grade-12', 'kcbe'],
            array_column($catalog->schoolClassGradeDefinitions()['senior-school-cbc'], 'slug')
        );
        $this->assertSame(
            ['stem', 'social-sciences', 'arts-and-sports', 'languages'],
            array_column($catalog->schoolSubjectsForLevel('senior-school-cbc'), 'slug')
        );
    }

    public function test_database_changes_reach_every_grade_and_exam_after_cache_invalidation(): void
    {
        $juniorSchool = CourseCategory::whereNull('parent_id')
            ->where('slug', 'junior-school')
            ->firstOrFail();
        $classNodes = CourseCategory::where('parent_id', $juniorSchool->id)
            ->whereIn('node_type', [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ])
            ->get();

        foreach ($classNodes as $classNode) {
            $subject = CourseCategory::create([
                'slug' => 'robotics',
                'parent_id' => $classNode->id,
                'node_type' => CourseCategory::TYPE_SUBJECT,
                'status' => true,
                'order' => 99,
            ]);
            CourseCategoryTranslation::create([
                'course_category_id' => $subject->id,
                'lang_code' => 'en',
                'name' => 'Robotics',
            ]);
        }

        $catalog = app(ProductMetadataCatalogService::class);
        $nodes = $catalog->schoolClassGradeNodes('junior-school');

        $this->assertNotEmpty($nodes);
        foreach ($nodes as $node) {
            $this->assertContains('robotics', array_column($node['children'], 'slug'), $node['slug']);
        }
    }

    public function test_translation_updates_automatically_invalidate_runtime_taxonomy_cache(): void
    {
        $catalog = app(ProductMetadataCatalogService::class);
        $kjsea = CourseCategory::where('slug', 'kjsea')
            ->where('node_type', CourseCategory::TYPE_NATIONAL_EXAM)
            ->firstOrFail();

        $this->assertNotSame(
            'Junior National Assessment',
            collect($catalog->schoolClassGradeNodes('junior-school'))->firstWhere('slug', 'kjsea')['label']
        );

        $translation = CourseCategoryTranslation::where('course_category_id', $kjsea->id)
            ->where('lang_code', 'en')
            ->firstOrFail();
        $translation->update(['name' => 'Junior National Assessment']);

        $this->assertSame(
            'Junior National Assessment',
            collect($catalog->schoolClassGradeNodes('junior-school'))->firstWhere('slug', 'kjsea')['label']
        );
    }

    public function test_inactive_and_archived_nodes_are_not_exposed(): void
    {
        $juniorSchool = CourseCategory::whereNull('parent_id')->where('slug', 'junior-school')->firstOrFail();
        $gradeSeven = CourseCategory::where('parent_id', $juniorSchool->id)->where('slug', 'grade-7')->firstOrFail();

        DB::table('course_categories')->where('id', $gradeSeven->id)->update(['status' => 0]);
        app(TaxonomyCatalogRepository::class)->clearCache();

        $this->assertNotContains(
            'grade-7',
            array_column(app(ProductMetadataCatalogService::class)->schoolClassGradeNodes('junior-school'), 'slug')
        );
    }
}
