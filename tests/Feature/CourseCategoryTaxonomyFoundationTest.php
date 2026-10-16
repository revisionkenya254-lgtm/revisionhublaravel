<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Course\app\Models\CourseCategory;
use Tests\TestCase;

class CourseCategoryTaxonomyFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_categories_support_dynamic_taxonomy_fields_and_soft_deletion(): void
    {
        $this->assertTrue(Schema::hasColumns('course_categories', [
            'node_type',
            'deleted_at',
        ]));

        $level = CourseCategory::create([
            'slug' => 'test-school-level',
            'node_type' => CourseCategory::TYPE_EDUCATION_LEVEL,
            'status' => true,
            'order' => 1,
        ]);

        $this->assertTrue(
            CourseCategory::ofType(CourseCategory::TYPE_EDUCATION_LEVEL)
                ->whereKey($level->id)
                ->exists()
        );

        $level->delete();

        $this->assertNull(CourseCategory::find($level->id));
        $this->assertNotNull(CourseCategory::withTrashed()->find($level->id));
        $this->assertNotNull($level->fresh()->deleted_at);
    }

    public function test_node_types_are_explicit_and_stable(): void
    {
        $this->assertSame([
            'education_level',
            'grade',
            'form',
            'national_exam',
            'pathway',
            'subject',
            'school',
            'course',
            'resource',
            'legacy',
        ], CourseCategory::NODE_TYPES);
    }

    public function test_relationships_prevent_unsafe_taxonomy_archival(): void
    {
        $level = CourseCategory::create([
            'slug' => 'dynamic-level',
            'node_type' => CourseCategory::TYPE_EDUCATION_LEVEL,
            'status' => true,
        ]);
        CourseCategory::create([
            'slug' => 'dynamic-grade',
            'parent_id' => $level->id,
            'node_type' => CourseCategory::TYPE_GRADE,
            'status' => true,
        ]);

        $this->assertTrue($level->hasDependents());
    }

    public function test_soft_deleting_a_node_preserves_its_translation_for_recovery(): void
    {
        $category = CourseCategory::create([
            'slug' => 'recoverable-category',
            'node_type' => CourseCategory::TYPE_SUBJECT,
            'status' => true,
        ]);
        DB::table('course_category_translations')->insert([
            'course_category_id' => $category->id,
            'lang_code' => 'en',
            'name' => 'Recoverable Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category->delete();

        $this->assertDatabaseHas('course_category_translations', [
            'course_category_id' => $category->id,
            'name' => 'Recoverable Category',
        ]);
    }
}
