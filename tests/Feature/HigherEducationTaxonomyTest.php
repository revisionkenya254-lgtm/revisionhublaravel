<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CurriculumUnit;
use App\Models\Product;
use App\Models\ProgrammeUnitAssignment;
use App\Services\Taxonomy\CurriculumUnitService;
use App\Services\Taxonomy\TaxonomyManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Course\app\Models\CourseCategory;
use Tests\TestCase;

class HigherEducationTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_cdacc_example_is_seeded_as_a_profile_with_canonical_units(): void
    {
        $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', 'cdacc')->firstOrFail();
        $programme = CourseCategory::where('slug', 'agricultural-engineering-level-6')->firstOrFail();

        $this->assertSame(CourseCategory::TYPE_EDUCATION_LEVEL, $root->node_type);
        $this->assertSame(CourseCategory::TYPE_PROGRAMME, $programme->node_type);
        $this->assertSame('Level 6', $programme->taxonomy_metadata['qualification_level']);
        $this->assertDatabaseHas('curriculum_units', [
            'awarding_body_id' => $root->id,
            'slug' => 'communication-skills',
        ]);
        $this->assertDatabaseHas('programme_unit_assignments', [
            'programme_node_id' => $programme->id,
            'group_type' => 'major',
        ]);
    }

    public function test_one_canonical_unit_can_be_shared_and_updated_across_programmes(): void
    {
        $root = CourseCategory::where('taxonomy_profile', 'cdacc')->whereNull('parent_id')->firstOrFail();
        $department = CourseCategory::where('slug', 'agriculture-and-aquaculture-department')->firstOrFail();
        $secondProgramme = app(TaxonomyManagementService::class)->createNode($department->id, [
            'name' => 'Aquaculture Technology – Level 6',
            'slug' => 'aquaculture-technology-level-6',
            'node_type' => CourseCategory::TYPE_PROGRAMME,
            'order' => 1,
        ]);
        $unit = CurriculumUnit::where('awarding_body_id', $root->id)
            ->where('slug', 'communication-skills')->firstOrFail();

        app(CurriculumUnitService::class)->assign($secondProgramme, $unit, ['group_type' => 'common']);
        app(CurriculumUnitService::class)->update($unit, [
            'name' => 'Workplace Communication Skills',
            'slug' => 'communication-skills',
            'curriculum_version' => 'current',
        ]);

        $this->assertSame(2, ProgrammeUnitAssignment::where('curriculum_unit_id', $unit->id)->count());
        $this->assertSame('Workplace Communication Skills', $unit->fresh()->name);
    }

    public function test_global_unit_resource_is_projected_under_assigned_programme(): void
    {
        $unit = CurriculumUnit::where('slug', 'communication-skills')->firstOrFail();
        $product = Product::create([
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'July 2026 Past Paper',
            'slug' => 'cdacc-communication-skills-july-2026',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'approved',
        ]);
        app(CurriculumUnitService::class)->attachResource($product, $unit, [
            'resource_type' => 'past_paper',
            'assessment_series' => 'July',
            'assessment_year' => 2026,
        ]);

        $response = $this->getJson('/api/catalog/taxonomy/profiles/cdacc')->assertOk();
        $encoded = json_encode($response->json('data'));
        $this->assertStringContainsString('July 2026 Past Paper', $encoded);
        $this->assertStringContainsString('Common Units', $encoded);
    }

    public function test_admin_can_open_cdacc_workspace_and_assign_units(): void
    {
        $admin = Admin::create([
            'name' => 'Curriculum Admin',
            'email' => 'curriculum@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin, 'admin')->get('/admin/taxonomy/profiles/cdacc')
            ->assertOk()
            ->assertSee('CDACC Curriculum')
            ->assertSee('Agricultural Engineering')
            ->assertSee('Apply Renewable Energy Technology in Agriculture');
    }

    public function test_assignment_with_programme_specific_resource_cannot_be_removed_accidentally(): void
    {
        $assignment = ProgrammeUnitAssignment::whereHas('unit', fn ($query) => $query
            ->where('slug', 'communication-skills'))->firstOrFail();
        $product = Product::create([
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'November 2025 Past Paper',
            'slug' => 'cdacc-communication-skills-november-2025',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'approved',
        ]);
        app(CurriculumUnitService::class)->attachResource($product, $assignment->unit, [
            'programme_unit_assignment_id' => $assignment->id,
            'resource_type' => 'past_paper',
            'assessment_series' => 'November',
            'assessment_year' => 2025,
        ]);

        $this->expectException(ValidationException::class);
        app(CurriculumUnitService::class)->unassign($assignment);
    }

    public function test_legacy_migration_command_is_a_safe_dry_run_by_default(): void
    {
        $before = CurriculumUnit::count();
        $this->artisan('taxonomy:migrate-higher-education', ['profile' => 'cdacc'])
            ->expectsOutputToContain('Dry run only')
            ->assertSuccessful();
        $this->assertSame($before, CurriculumUnit::count());
    }
}
