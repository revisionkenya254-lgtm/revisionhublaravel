<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Course\app\Models\CourseCategory;
use Tests\TestCase;

class EducationTaxonomyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_education_levels_are_grouped_without_removing_the_flat_contract(): void
    {
        $this->root('lower-primary');
        $this->root('cdacc', 'cdacc');
        $this->root('teacher-resources');
        Cache::flush();

        $response = $this->getJson('/api/bootstrap/education-levels');

        $response->assertOk()->assertHeader('X-Taxonomy-Revision');
        $this->assertContains('lower-primary', collect($response->json('groups.lower_education.levels'))->pluck('slug'));
        $this->assertContains('cdacc', collect($response->json('groups.upper_education.levels'))->pluck('profile'));
        $this->assertContains('teacher-resources', collect($response->json('groups.supporting_resources.levels'))->pluck('slug'));
    }

    public function test_family_and_level_endpoints_expose_the_correct_taxonomy_branch(): void
    {
        $lower = $this->root('lower-primary');
        CourseCategory::create([
            'parent_id' => $lower->id,
            'slug' => 'grade-1',
            'node_type' => CourseCategory::TYPE_GRADE,
            'status' => 1,
        ]);
        $this->root('cdacc', 'cdacc');
        Cache::flush();

        $familyResponse = $this->getJson('/api/catalog/taxonomy/families/lower');
        $familyResponse->assertOk()->assertJsonPath('data.key', 'lower_education');
        $lowerTree = collect($familyResponse->json('data.tree'))->firstWhere('slug', 'lower-primary');
        $this->assertNotNull($lowerTree);
        $this->assertContains('grade-1', collect($lowerTree['children'])->pluck('slug'));

        $this->getJson('/api/catalog/taxonomy/levels/lower-primary')
            ->assertOk()
            ->assertJsonPath('data.level.family', 'lower_education')
            ->assertJsonPath('data.tree.slug', 'lower-primary');
    }

    public function test_products_expose_and_filter_the_canonical_education_context(): void
    {
        $instructor = User::factory()->create();
        $upper = Product::create([
            'instructor_id' => $instructor->id,
            'type' => Product::TYPE_NOTE,
            'title' => 'Renewable Energy',
            'slug' => 'renewable-energy',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'approved',
            'metadata' => [
                'curriculum_context' => [
                    'profile' => 'cdacc',
                    'department_id' => 10,
                    'department' => 'Agriculture and Aquaculture Department',
                    'programme_id' => 20,
                    'programme' => 'Agricultural Engineering Level 6',
                    'unit_id' => 30,
                    'unit' => 'Apply Renewable Energy Technology in Agriculture',
                    'group_type' => 'major',
                    'study_year' => 'Level 6',
                ],
            ],
        ]);
        Product::create([
            'instructor_id' => $instructor->id,
            'type' => Product::TYPE_NOTE,
            'title' => 'Primary Mathematics',
            'slug' => 'primary-mathematics',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'approved',
            'metadata' => ['catalog_identity' => [
                'main_category' => 'lower-primary',
                'category' => 'grade-2',
                'subject' => 'mathematics',
            ]],
        ]);

        $this->getJson('/api/catalog/products/note?education_family=upper&taxonomy_profile=cdacc&department_id=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $upper->id)
            ->assertJsonPath('data.0.education_context.family', 'upper_education')
            ->assertJsonPath('data.0.education_context.department.id', 10)
            ->assertJsonPath('data.0.education_context.periods.0.key', 'study_year')
            ->assertJsonPath('data.0.education_context.unit_group.key', 'major');

        $this->getJson('/api/catalog/products/note?education_family=lower&grade=grade-2&subject=mathematics')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.education_context.family', 'lower_education')
            ->assertJsonPath('data.0.education_context.grade_or_exam.slug', 'grade-2')
            ->assertJsonPath('data.0.education_context.subject.slug', 'mathematics');
    }

    private function root(string $slug, ?string $profile = null): CourseCategory
    {
        return CourseCategory::create([
            'slug' => $slug,
            'node_type' => CourseCategory::TYPE_EDUCATION_LEVEL,
            'taxonomy_profile' => $profile,
            'status' => 1,
        ]);
    }
}
