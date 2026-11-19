<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CurriculumUnit;
use App\Models\Product;
use App\Models\ProgrammeUnitAssignment;
use App\Services\Taxonomy\CurriculumUnitService;
use App\Services\Taxonomy\TaxonomyManagementService;
use App\Services\Taxonomy\HigherEducationTaxonomyRepository;
use App\Services\Taxonomy\ProductCurriculumContextService;
use Illuminate\Http\Request;
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

    public function test_each_upper_profile_projects_its_own_periods_and_unit_groups(): void
    {
        $definitions = [
            'certificate' => [
                'group_type' => 'common',
                'periods' => ['module' => 'Module 1', 'semester' => 'Semester 1'],
                'expected' => ['Module 1', 'Semester 1', 'Common Units'],
            ],
            'diploma' => [
                'group_type' => 'major',
                'periods' => ['study_year' => 'Year 1', 'semester' => 'Semester 1'],
                'expected' => ['Year 1', 'Semester 1', 'Major Units'],
            ],
            'undergraduate' => [
                'group_type' => 'university_common',
                'periods' => ['study_year' => 'Year 1', 'semester' => 'Semester 1'],
                'expected' => ['Year 1', 'Semester 1', 'University Common Units'],
            ],
            'professional' => [
                'group_type' => 'paper',
                'periods' => ['stage' => 'Intermediate Level', 'section' => 'Section 2'],
                'expected' => ['Intermediate Level', 'Section 2', 'Papers'],
            ],
        ];

        foreach ($definitions as $profile => $definition) {
            $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', $profile)->firstOrFail();
            $owner = app(TaxonomyManagementService::class)->createNode($root->id, [
                'name' => ucfirst($profile).' Owner',
                'slug' => $profile.'-owner',
                'node_type' => CourseCategory::TYPE_AWARDING_BODY,
                'order' => 90,
            ]);
            $department = app(TaxonomyManagementService::class)->createNode($owner->id, [
                'name' => ucfirst($profile).' Department',
                'slug' => $profile.'-department',
                'node_type' => CourseCategory::TYPE_DEPARTMENT,
                'order' => 0,
            ]);
            $programme = app(TaxonomyManagementService::class)->createNode($department->id, [
                'name' => ucfirst($profile).' Programme',
                'slug' => $profile.'-programme',
                'node_type' => CourseCategory::TYPE_PROGRAMME,
                'order' => 0,
            ]);
            $unit = app(CurriculumUnitService::class)->create($owner, [
                'name' => ucfirst($profile).' Shared Unit',
                'slug' => $profile.'-shared-unit',
                'curriculum_version' => '2026',
            ]);
            app(CurriculumUnitService::class)->assign($programme, $unit, array_merge(
                ['group_type' => $definition['group_type']],
                $definition['periods']
            ));

            $tree = json_encode(app(HigherEducationTaxonomyRepository::class)->profile($profile));
            foreach ($definition['expected'] as $expected) {
                $this->assertStringContainsString($expected, $tree, $profile.' projection missing '.$expected);
            }
        }
    }

    public function test_institution_owned_unit_cannot_be_assigned_to_another_institution(): void
    {
        $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', 'diploma')->firstOrFail();
        $management = app(TaxonomyManagementService::class);
        $ownerA = $management->createNode($root->id, ['name' => 'Institution A', 'slug' => 'institution-a', 'node_type' => CourseCategory::TYPE_AWARDING_BODY, 'order' => 90]);
        $ownerB = $management->createNode($root->id, ['name' => 'Institution B', 'slug' => 'institution-b', 'node_type' => CourseCategory::TYPE_AWARDING_BODY, 'order' => 91]);
        $department = $management->createNode($ownerB->id, ['name' => 'Engineering', 'slug' => 'institution-b-engineering', 'node_type' => CourseCategory::TYPE_DEPARTMENT, 'order' => 0]);
        $programme = $management->createNode($department->id, ['name' => 'Diploma Programme', 'slug' => 'institution-b-diploma', 'node_type' => CourseCategory::TYPE_PROGRAMME, 'order' => 0]);
        $unit = app(CurriculumUnitService::class)->create($ownerA, ['name' => 'Institution A Unit', 'slug' => 'institution-a-unit', 'curriculum_version' => '2026']);

        $this->expectException(ValidationException::class);
        app(CurriculumUnitService::class)->assign($programme, $unit, ['group_type' => 'major']);
    }

    public function test_admin_can_open_every_upper_education_workspace(): void
    {
        $admin = Admin::create([
            'name' => 'Upper Education Admin',
            'email' => 'upper-education@example.com',
            'password' => Hash::make('password'),
        ]);
        $expected = [
            'certificate' => 'Certificate Curriculum',
            'diploma' => 'Diploma Curriculum',
            'undergraduate' => 'Undergraduate Curriculum',
            'professional' => 'Professional Curriculum',
        ];

        foreach ($expected as $profile => $heading) {
            $this->actingAs($admin, 'admin')
                ->get('/admin/taxonomy/profiles/'.$profile)
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('Profile architecture');
        }
    }

    public function test_product_forms_receive_every_upper_education_architecture(): void
    {
        $options = app(ProductCurriculumContextService::class)->formOptions();

        $this->assertEqualsCanonicalizing(
            ['cdacc', 'tvet', 'certificate', 'diploma', 'undergraduate', 'professional'],
            collect($options['profiles'])->pluck('key')->all()
        );
        $expectedFields = [
            'cdacc' => ['department', 'programme', 'module', 'group', 'assignment'],
            'certificate' => ['owner', 'department', 'programme', 'module', 'semester', 'assignment'],
            'diploma' => ['owner', 'department', 'programme', 'study_year', 'semester', 'assignment'],
            'undergraduate' => ['owner', 'department', 'programme', 'study_year', 'semester', 'assignment'],
            'professional' => ['owner', 'department', 'stage', 'section', 'assignment'],
        ];
        $profiles = collect($options['profiles'])->keyBy('key');
        foreach ($expectedFields as $profile => $fields) {
            $this->assertSame($fields, $profiles->get($profile)['creation_fields']);
        }
        $this->assertTrue(collect($options['assignments'])->contains(
            fn (array $assignment) => $assignment['profile'] === 'cdacc'
                && str_contains($assignment['label'], 'Agricultural Engineering')
                && filled($assignment['department_id'])
                && filled($assignment['programme_id'])
                && array_key_exists('module', $assignment)
        ));

        $html = view('partials.product-curriculum-context-fields', [
            'curriculumOptions' => $options,
            'curriculumAdmin' => false,
        ])->render();
        $this->assertStringContainsString('data-curriculum-select="department"', $html);
        $this->assertStringContainsString('data-curriculum-select="study_year"', $html);
        $this->assertStringContainsString('data-curriculum-select="stage"', $html);
        $this->assertStringContainsString('name="programme_unit_assignment_id"', $html);
    }

    public function test_active_instructor_product_requires_a_programme_unit_for_upper_education(): void
    {
        $request = Request::create('/', 'POST', [
            'status' => 'active',
            'education_level' => 'CDACC',
            'curriculum_profile' => 'cdacc',
        ]);

        $this->expectException(ValidationException::class);
        app(ProductCurriculumContextService::class)->validateRequest($request);
    }

    public function test_product_context_sync_uses_stable_assignment_and_unit_ids(): void
    {
        $assignment = ProgrammeUnitAssignment::query()
            ->whereHas('programme', fn ($query) => $query->where('taxonomy_profile', 'cdacc'))
            ->with('unit')->firstOrFail();
        $product = Product::create([
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'July 2026 Renewable Energy Paper',
            'slug' => 'july-2026-renewable-energy-paper',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'pending',
        ]);
        $request = Request::create('/', 'POST', [
            'status' => 'active',
            'curriculum_profile' => 'cdacc',
            'programme_unit_assignment_id' => $assignment->id,
            'assessment_series' => 'July',
            'year' => '2026',
        ]);

        $service = app(ProductCurriculumContextService::class);
        $service->validateRequest($request);
        $service->sync($request, $product);

        $this->assertDatabaseHas('product_curriculum_contexts', [
            'product_id' => $product->id,
            'curriculum_unit_id' => $assignment->curriculum_unit_id,
            'programme_unit_assignment_id' => $assignment->id,
            'assessment_series' => 'July',
            'assessment_year' => 2026,
        ]);
        $snapshot = $service->metadataSnapshot($request);
        $this->assertSame('cdacc', data_get($snapshot, 'curriculum_context.profile'));
        $this->assertNotNull(data_get($snapshot, 'curriculum_context.department_id'));
        $this->assertNotNull(data_get($snapshot, 'curriculum_context.programme_id'));
    }

    public function test_upper_education_product_without_context_is_not_approval_ready(): void
    {
        $product = Product::create([
            'type' => Product::TYPE_NOTE,
            'title' => 'Unclassified Diploma Note',
            'slug' => 'unclassified-diploma-note',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'pending',
            'metadata' => ['education_level' => 'Diploma'],
        ]);

        $this->assertNotEmpty(app(ProductCurriculumContextService::class)->approvalIssues($product));
    }

    public function test_admin_cannot_approve_unclassified_upper_education_product(): void
    {
        $admin = Admin::create([
            'name' => 'Approval Admin',
            'email' => 'approval-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $product = Product::create([
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Unclassified CDACC Paper',
            'slug' => 'unclassified-cdacc-paper',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'pending',
            'metadata' => ['education_level' => 'CDACC'],
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.past-papers.status-update', $product), ['is_approved' => 'approved'])
            ->assertSessionHasErrors('is_approved');

        $this->assertSame('pending', $product->fresh()->is_approved);
    }

    public function test_admin_approval_records_taxonomy_verification_for_classified_product(): void
    {
        $admin = Admin::create([
            'name' => 'Taxonomy Verifier',
            'email' => 'taxonomy-verifier@example.com',
            'password' => Hash::make('password'),
        ]);
        $assignment = ProgrammeUnitAssignment::query()
            ->whereHas('programme', fn ($query) => $query->where('taxonomy_profile', 'cdacc'))
            ->with('unit')->firstOrFail();
        $product = Product::create([
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Classified CDACC Paper',
            'slug' => 'classified-cdacc-paper',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'pending',
            'metadata' => ['education_level' => 'CDACC'],
        ]);
        app(CurriculumUnitService::class)->attachResource($product, $assignment->unit, [
            'programme_unit_assignment_id' => $assignment->id,
            'resource_type' => Product::TYPE_PAST_PAPER,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.past-papers.status-update', $product), ['is_approved' => 'approved'])
            ->assertRedirect(route('admin.past-papers.index'));

        $product->refresh();
        $this->assertSame('approved', $product->is_approved);
        $this->assertSame($admin->id, $product->taxonomy_verified_by);
        $this->assertNotNull($product->taxonomy_verified_at);
    }
}
