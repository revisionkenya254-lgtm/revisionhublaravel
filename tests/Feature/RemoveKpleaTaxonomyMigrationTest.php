<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RemoveKpleaTaxonomyMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_removes_kplea_and_safely_cleans_legacy_references(): void
    {
        $user = User::factory()->create();
        $lowerPrimaryId = DB::table('course_categories')->insertGetId([
            'slug' => 'lower-primary',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $kpleaId = DB::table('course_categories')->insertGetId([
            'slug' => 'KPLEA',
            'parent_id' => $lowerPrimaryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $legacySubjectId = DB::table('course_categories')->insertGetId([
            'slug' => 'mathematics',
            'parent_id' => $kpleaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('course_category_translations')->insert([
            'course_category_id' => $kpleaId,
            'lang_code' => 'en',
            'name' => 'KPLEA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_skill_topics')->insert([
            'category_id' => $legacySubjectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courseId = DB::table('courses')->insertGetId([
            'instructor_id' => $user->id,
            'category_id' => $legacySubjectId,
            'type' => 'course',
            'title' => 'Legacy course',
            'slug' => 'legacy-kplea-course',
            'demo_video_storage' => 'upload',
            'price' => 0,
            'certificate' => 0,
            'downloadable' => 0,
            'partner_instructor' => 0,
            'qna' => 0,
            'status' => 'is_draft',
            'is_approved' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'instructor_id' => $user->id,
            'category_id' => $kpleaId,
            'type' => 'past_paper',
            'title' => 'Legacy paper',
            'slug' => 'legacy-kplea-paper',
            'price' => 0,
            'status' => 'is_draft',
            'is_approved' => 'pending',
            'metadata' => json_encode([
                'education_level' => 'Lower Primary',
                'class_grade' => 'KPLEA',
                'catalog_identity' => ['category' => 'kplea'],
                'tags' => ['revision', 'KPLEA'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_16_000000_remove_kplea_taxonomy_typo.php');
        $migration->up();

        $this->assertDatabaseMissing('course_categories', ['id' => $kpleaId]);
        $this->assertDatabaseMissing('course_categories', ['id' => $legacySubjectId]);
        $this->assertDatabaseMissing('course_category_translations', ['course_category_id' => $kpleaId]);
        $this->assertDatabaseMissing('user_skill_topics', ['category_id' => $legacySubjectId]);
        $this->assertDatabaseHas('courses', ['id' => $courseId, 'category_id' => null]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'category_id' => null]);

        $metadata = json_decode((string) DB::table('products')->where('id', $productId)->value('metadata'), true);

        $this->assertSame('Lower Primary', $metadata['education_level']);
        $this->assertArrayNotHasKey('class_grade', $metadata);
        $this->assertSame([], $metadata['catalog_identity']);
        $this->assertSame(['revision'], $metadata['tags']);

        // Running the migration again is safe during repeated deployments.
        $migration->up();
        $this->assertDatabaseHas('products', ['id' => $productId, 'category_id' => null]);
    }
}
