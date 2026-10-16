<?php

namespace Tests\Feature;

use App\Services\ProductMetadataCatalogService;
use App\Services\Taxonomy\TaxonomyManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Course\app\Models\CourseCategory;
use Tests\TestCase;

class TaxonomyManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_subject_creation_and_updates_propagate_to_every_grade_and_exam(): void
    {
        $service = app(TaxonomyManagementService::class);
        $level = $this->level('junior-school');
        $classIds = $this->classIds($level);

        $createdIds = $service->syncSharedLeaf($level, null, [
            'name' => 'Robotics',
            'slug' => 'robotics',
            'node_type' => CourseCategory::TYPE_SUBJECT,
            'order' => 50,
        ]);

        $this->assertCount($classIds->count(), $createdIds);
        $this->assertSame($classIds->count(), CourseCategory::whereIn('parent_id', $classIds)->where('slug', 'robotics')->count());

        $service->syncSharedLeaf($level, 'robotics', [
            'name' => 'Robotics and Automation',
            'slug' => 'robotics-and-automation',
            'node_type' => CourseCategory::TYPE_SUBJECT,
            'order' => 7,
        ]);

        $copies = CourseCategory::with('translation')
            ->whereIn('parent_id', $classIds)
            ->where('slug', 'robotics-and-automation')
            ->get();
        $this->assertCount($classIds->count(), $copies);
        $this->assertTrue($copies->every(fn ($copy) => $copy->order === 7));
        $this->assertTrue($copies->every(fn ($copy) => $copy->translation?->name === 'Robotics and Automation'));

        $kjsea = collect(app(ProductMetadataCatalogService::class)->schoolClassGradeNodes('junior-school'))
            ->firstWhere('slug', 'kjsea');
        $this->assertContains('robotics-and-automation', array_column($kjsea['children'], 'slug'));
    }

    public function test_new_grade_automatically_inherits_the_levels_shared_subjects(): void
    {
        $service = app(TaxonomyManagementService::class);
        $level = $this->level('senior-school-cbc');
        $expected = app(ProductMetadataCatalogService::class)->schoolSubjectsForLevel('senior-school-cbc');

        $grade = $service->createNode($level->id, [
            'name' => 'Grade 13',
            'slug' => 'grade-13',
            'node_type' => CourseCategory::TYPE_GRADE,
            'order' => 99,
        ]);

        $this->assertSame(
            array_column($expected, 'slug'),
            CourseCategory::where('parent_id', $grade->id)->orderBy('order')->pluck('slug')->all()
        );
    }

    public function test_invalid_parent_child_type_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(TaxonomyManagementService::class)->createNode($this->level('junior-school')->id, [
            'name' => 'Invalid Subject',
            'slug' => 'invalid-subject',
            'node_type' => CourseCategory::TYPE_SUBJECT,
            'order' => 1,
        ]);
    }

    public function test_shared_subject_archive_is_blocked_while_content_references_any_copy(): void
    {
        $level = $this->level('junior-school');
        $subject = CourseCategory::whereIn('parent_id', $this->classIds($level))
            ->where('slug', 'mathematics')
            ->firstOrFail();
        DB::table('products')->insert([
            'title' => 'Referenced Mathematics Paper',
            'slug' => 'referenced-mathematics-paper',
            'type' => 'past_paper',
            'category_id' => $subject->id,
            'status' => 1,
            'is_approved' => 1,
            'metadata' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(TaxonomyManagementService::class)->archiveSharedLeaf($level, 'mathematics');
            $this->fail('Expected referenced taxonomy archive to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('node', $exception->errors());
        }

        $this->assertSame(
            $this->classIds($level)->count(),
            CourseCategory::whereIn('parent_id', $this->classIds($level))->where('slug', 'mathematics')->count()
        );
    }

    public function test_shared_subject_archive_and_restore_remain_uniform(): void
    {
        $service = app(TaxonomyManagementService::class);
        $level = $this->level('junior-school');
        $classIds = $this->classIds($level);

        $archived = $service->archiveSharedLeaf($level, 'creative-arts');
        $this->assertSame($classIds->count(), $archived);
        $this->assertSame(0, CourseCategory::whereIn('parent_id', $classIds)->where('slug', 'creative-arts')->count());

        $oneArchivedCopy = CourseCategory::withTrashed()
            ->whereIn('parent_id', $classIds)
            ->where('slug', 'creative-arts')
            ->firstOrFail();
        $service->restoreNode($oneArchivedCopy->id);

        $this->assertSame(
            $classIds->count(),
            CourseCategory::whereIn('parent_id', $classIds)->where('slug', 'creative-arts')->count()
        );
    }

    private function level(string $slug): CourseCategory
    {
        return CourseCategory::whereNull('parent_id')->where('slug', $slug)->firstOrFail();
    }

    private function classIds(CourseCategory $level)
    {
        return CourseCategory::where('parent_id', $level->id)
            ->whereIn('node_type', [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_FORM,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ])
            ->pluck('id');
    }
}
