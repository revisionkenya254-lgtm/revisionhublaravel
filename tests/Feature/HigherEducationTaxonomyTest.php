<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CurriculumUnit;
use App\Models\Product;
use App\Models\ProgrammeUnitAssignment;
use App\Models\User;
use App\Services\Taxonomy\CurriculumUnitService;
use App\Services\Taxonomy\TaxonomyManagementService;
use App\Services\Taxonomy\HigherEducationTaxonomyRepository;
use App\Services\Taxonomy\ProductCurriculumContextService;
use App\Services\ProductMetadataCatalogService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'study_year' => 'Level 6',
        ]);

        $options = collect(app(ProductCurriculumContextService::class)->formOptions()['assignments'])
            ->where('profile', 'cdacc');
        $this->assertCount(2, $options);
        $this->assertSame(['common', 'major'], $options->pluck('group')->sort()->values()->all());
        $this->assertSame(['Level 6'], $options->pluck('study_year')->unique()->values()->all());
        $this->assertSame(
            ['Agriculture and Aquaculture Department'],
            $options->pluck('department')->unique()->values()->all()
        );
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

    public function test_restoring_an_assignment_also_restores_its_archived_canonical_unit(): void
    {
        $admin = Admin::create([
            'name' => 'Assignment Restore Admin',
            'email' => 'assignment-restore@example.com',
            'password' => Hash::make('password'),
        ]);
        $assignment = ProgrammeUnitAssignment::query()
            ->whereHas('programme', fn ($query) => $query->where('taxonomy_profile', 'cdacc'))
            ->with('unit')
            ->firstOrFail();
        $service = app(CurriculumUnitService::class);
        $service->unassign($assignment);
        $service->archive($assignment->unit);

        $this->actingAs($admin, 'admin')->post(
            route('admin.taxonomy.assignments.restore', ['cdacc', $assignment])
        )->assertRedirect(route('admin.taxonomy.profiles.show', 'cdacc'));

        $this->assertTrue((bool) $assignment->fresh()->status);
        $this->assertTrue((bool) $assignment->unit->fresh()->status);
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
            ->assertSee('Apply Renewable Energy Technology in Agriculture')
            ->assertSee('assignment-modal')
            ->assertSee('unit-creation-modal')
            ->assertSee('unit-create-department')
            ->assertSee('unit-create-programme')
            ->assertSee('Create and assign unit')
            ->assertSee('assignment-department')
            ->assertSee('data-department-id')
            ->assertSee('data-department-name')
            ->assertSee('assignment-context-modal')
            ->assertSee('js-open-assignment-modal')
            ->assertSee('js-manage-assignment-context')
            ->assertSee('js-programme-toggle')
            ->assertSee('data-programme-children')
            ->assertSee('Expand all')
            ->assertSee('Collapse all')
            ->assertSee('Assign or edit units directly')
            ->assertSee('Only units assigned to this unit group are shown.')
            ->assertSee('document.body.appendChild(modal)', false)
            ->assertSee('taxonomy-programme-tree-cdacc', false)
            ->assertSee('body>.modal-backdrop', false);
    }

    public function test_cdacc_level_and_unit_group_contexts_only_contain_relevant_assignments(): void
    {
        $tree = app(HigherEducationTaxonomyRepository::class)->profile('cdacc', false);
        $nodes = collect();
        $walk = function (array $node) use (&$walk, $nodes): void {
            $nodes->push($node);
            foreach ($node['children'] ?? [] as $child) {
                $walk($child);
            }
        };
        $walk($tree);

        $level = $nodes->first(fn (array $node) => ($node['node_type'] ?? null) === 'study_period'
            && ($node['label'] ?? null) === 'Level 6');
        $common = $nodes->first(fn (array $node) => ($node['node_type'] ?? null) === 'unit_group'
            && ($node['group_type'] ?? null) === 'common');

        $this->assertNotNull($level);
        $this->assertNotEmpty($level['assignments']);
        $this->assertTrue(collect($level['assignments'])->every(fn (array $assignment) => $assignment['study_year'] === 'Level 6'));
        $this->assertNotNull($common);
        $this->assertTrue(collect($common['assignments'])->every(fn (array $assignment) => $assignment['group_type'] === 'common'));
    }

    public function test_assignment_can_be_edited_inline_over_json(): void
    {
        $admin = Admin::create([
            'name' => 'Inline Assignment Admin',
            'email' => 'inline-assignment@example.com',
            'password' => Hash::make('password'),
        ]);
        $assignment = ProgrammeUnitAssignment::whereHas('programme', fn ($query) => $query
            ->where('taxonomy_profile', 'cdacc'))->firstOrFail();

        $this->actingAs($admin, 'admin')->putJson(
            route('admin.taxonomy.assignments.update', ['cdacc', $assignment]),
            [
                'programme_node_id' => $assignment->programme_node_id,
                'curriculum_unit_id' => $assignment->curriculum_unit_id,
                'group_type' => 'elective',
                'study_year' => 'Level 5',
                'display_order' => 9,
                'credit_hours' => 3,
                'is_mandatory' => 0,
            ]
        )->assertOk()
            ->assertJsonPath('assignment.id', $assignment->id)
            ->assertJsonPath('assignment.group_type', 'elective');

        $this->assertDatabaseHas('programme_unit_assignments', [
            'id' => $assignment->id,
            'group_type' => 'elective',
            'study_year' => 'Level 5',
            'display_order' => 9,
            'is_mandatory' => 0,
        ]);
    }

    public function test_unit_can_be_assigned_inline_over_json(): void
    {
        $admin = Admin::create([
            'name' => 'Inline Assignment Creator',
            'email' => 'inline-assignment-create@example.com',
            'password' => Hash::make('password'),
        ]);
        $root = CourseCategory::where('taxonomy_profile', 'cdacc')->whereNull('parent_id')->firstOrFail();
        $programme = CourseCategory::where('taxonomy_profile', 'cdacc')
            ->where('node_type', CourseCategory::TYPE_PROGRAMME)->firstOrFail();
        $unit = app(CurriculumUnitService::class)->create($root, [
            'name' => 'Inline Assignment Test Unit',
            'slug' => 'inline-assignment-test-unit',
            'curriculum_version' => '2026',
        ]);

        $this->actingAs($admin, 'admin')->postJson(
            route('admin.taxonomy.assignments.store', ['cdacc', $programme]),
            [
                'curriculum_unit_id' => $unit->id,
                'group_type' => 'common',
                'study_year' => 'Level 4',
                'display_order' => 3,
                'is_mandatory' => 1,
            ]
        )->assertOk()
            ->assertJsonPath('assignment.programme_node_id', $programme->id)
            ->assertJsonPath('assignment.curriculum_unit_id', $unit->id);

        $this->assertDatabaseHas('programme_unit_assignments', [
            'programme_node_id' => $programme->id,
            'curriculum_unit_id' => $unit->id,
            'group_type' => 'common',
            'study_year' => 'Level 4',
        ]);
    }

    public function test_admin_can_permanently_delete_an_unused_archived_canonical_unit(): void
    {
        $admin = Admin::create([
            'name' => 'Canonical Unit Delete Admin',
            'email' => 'canonical-unit-delete@example.com',
            'password' => Hash::make('password'),
        ]);
        $root = CourseCategory::where('taxonomy_profile', 'cdacc')->whereNull('parent_id')->firstOrFail();
        $service = app(CurriculumUnitService::class);
        $unit = $service->create($root, [
            'name' => 'Disposable Canonical Unit',
            'slug' => 'disposable-canonical-unit',
            'curriculum_version' => '2026',
        ]);
        $service->archive($unit);

        $this->actingAs($admin, 'admin')->get(route('admin.taxonomy.profiles.show', 'cdacc'))
            ->assertOk()
            ->assertSee('Delete permanently')
            ->assertSee(route('admin.taxonomy.units.destroy', ['cdacc', $unit]), false);
        $this->actingAs($admin, 'admin')->delete(
            route('admin.taxonomy.units.destroy', ['cdacc', $unit])
        )->assertRedirect(route('admin.taxonomy.profiles.show', 'cdacc'));

        $this->assertNull(CurriculumUnit::withTrashed()->find($unit->id));
    }

    public function test_referenced_canonical_unit_cannot_be_permanently_deleted(): void
    {
        $root = CourseCategory::where('taxonomy_profile', 'cdacc')->whereNull('parent_id')->firstOrFail();
        $programme = CourseCategory::where('taxonomy_profile', 'cdacc')
            ->where('node_type', CourseCategory::TYPE_PROGRAMME)->firstOrFail();
        $service = app(CurriculumUnitService::class);
        $unit = $service->create($root, [
            'name' => 'Referenced Canonical Unit',
            'slug' => 'referenced-canonical-unit',
            'curriculum_version' => '2026',
        ]);
        $service->assign($programme, $unit, ['group_type' => 'common']);
        $service->archive($unit);

        try {
            $service->permanentlyDelete($unit);
            $this->fail('Expected permanent deletion of a referenced unit to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('unit', $exception->errors());
        }

        $this->assertNotNull(CurriculumUnit::find($unit->id));
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
                'periods' => ['module' => 'Module 1'],
                'expected' => ['Module 1', 'Common Units'],
            ],
            'diploma' => [
                'group_type' => 'major',
                'periods' => ['module' => 'Module 1'],
                'expected' => ['Module 1', 'Major Units'],
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

    public function test_higher_education_child_rules_are_driven_by_each_profile(): void
    {
        $management = app(TaxonomyManagementService::class);
        $expectedRootChildren = [
            'cdacc' => [CourseCategory::TYPE_DEPARTMENT],
            'tvet' => [CourseCategory::TYPE_AWARDING_BODY],
            'certificate' => [CourseCategory::TYPE_AWARDING_BODY],
            'diploma' => [CourseCategory::TYPE_AWARDING_BODY],
            'undergraduate' => [CourseCategory::TYPE_AWARDING_BODY],
            'professional' => [CourseCategory::TYPE_AWARDING_BODY],
        ];

        foreach ($expectedRootChildren as $profile => $types) {
            $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', $profile)->firstOrFail();
            $this->assertSame($types, $management->allowedChildTypes($root), $profile);
        }

        $professionalOwner = CourseCategory::where('taxonomy_profile', 'professional')
            ->where('node_type', CourseCategory::TYPE_AWARDING_BODY)->firstOrFail();
        $this->assertContains(
            CourseCategory::TYPE_PROGRAMME,
            $management->allowedChildTypes($professionalOwner)
        );
    }

    public function test_main_taxonomy_page_uses_higher_education_workspace_cards(): void
    {
        $admin = Admin::create([
            'name' => 'Workspace Admin',
            'email' => 'workspace-admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin, 'admin')->get(route('admin.taxonomy.index'))
            ->assertOk()
            ->assertSee('Higher Education Workspaces')
            ->assertSee('Manage CDACC Curriculum')
            ->assertSee('Department')
            ->assertSee('Programme and Level');
    }

    public function test_legacy_cdacc_depth_nodes_are_normalized_without_changing_ids(): void
    {
        $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', 'cdacc')->firstOrFail();
        $legacyDepartment = CourseCategory::create([
            'parent_id' => $root->id,
            'slug' => 'legacy-health-science-department',
            'node_type' => CourseCategory::TYPE_GRADE,
            'taxonomy_profile' => 'cdacc',
            'status' => true,
        ]);
        $legacyProgramme = CourseCategory::create([
            'parent_id' => $legacyDepartment->id,
            'slug' => 'legacy-health-support-level-6',
            'node_type' => CourseCategory::TYPE_SUBJECT,
            'taxonomy_profile' => 'cdacc',
            'status' => true,
        ]);

        $migration = require database_path('migrations/2026_11_19_000009_normalize_legacy_cdacc_node_types.php');
        $migration->up();

        $this->assertSame(CourseCategory::TYPE_DEPARTMENT, $legacyDepartment->fresh()->node_type);
        $this->assertSame(CourseCategory::TYPE_PROGRAMME, $legacyProgramme->fresh()->node_type);
        $this->assertSame($root->id, $legacyDepartment->fresh()->parent_id);
        $this->assertSame($legacyDepartment->id, $legacyProgramme->fresh()->parent_id);
    }

    public function test_admin_can_manage_an_institution_taxonomy_unit_and_assignment_end_to_end(): void
    {
        $admin = Admin::create([
            'name' => 'Taxonomy Builder',
            'email' => 'taxonomy-builder@example.com',
            'password' => Hash::make('password'),
        ]);
        $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', 'certificate')->firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.taxonomy.nodes.store'), [
            'parent_id' => $root->id,
            'return_profile' => 'certificate',
            'name' => 'Admin Test College',
            'slug' => 'admin-test-college',
            'node_type' => CourseCategory::TYPE_AWARDING_BODY,
            'order' => 80,
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $owner = CourseCategory::where('parent_id', $root->id)->where('slug', 'admin-test-college')->firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.taxonomy.nodes.store'), [
            'parent_id' => $owner->id,
            'return_profile' => 'certificate',
            'name' => 'School of Business',
            'slug' => 'admin-test-school-business',
            'node_type' => CourseCategory::TYPE_DEPARTMENT,
            'order' => 0,
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $department = CourseCategory::where('parent_id', $owner->id)->firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.taxonomy.nodes.store'), [
            'parent_id' => $department->id,
            'return_profile' => 'certificate',
            'name' => 'Certificate in Business Administration',
            'slug' => 'admin-test-certificate-business-administration',
            'node_type' => CourseCategory::TYPE_PROGRAMME,
            'taxonomy_metadata' => ['qualification_level' => 'Certificate'],
            'order' => 0,
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $programme = CourseCategory::where('parent_id', $department->id)->firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.taxonomy.units.store', 'certificate'), [
            '_create_and_assign' => 1,
            'department_id' => $department->id,
            'programme_node_id' => $programme->id,
            'group_type' => 'common',
            'module' => 'Module 1',
            'display_order' => 3,
            'is_mandatory' => 1,
            'name' => 'Business Communication',
            'slug' => 'admin-test-business-communication',
            'code' => 'CBA-101',
            'curriculum_version' => '2026',
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $unit = CurriculumUnit::where('awarding_body_id', $owner->id)
            ->where('slug', 'admin-test-business-communication')->firstOrFail();
        $assignment = ProgrammeUnitAssignment::where('programme_node_id', $programme->id)
            ->where('curriculum_unit_id', $unit->id)->firstOrFail();
        $this->assertSame('common', $assignment->group_type);
        $this->assertSame('Module 1', $assignment->module);

        $this->actingAs($admin, 'admin')->put(
            route('admin.taxonomy.assignments.update', ['certificate', $assignment]),
            [
                'programme_node_id' => $programme->id,
                'curriculum_unit_id' => $unit->id,
                'group_type' => 'major',
                'module' => 'Module 2',
                'display_order' => 4,
                'is_mandatory' => 1,
            ]
        )->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertSame('Module 2', $assignment->fresh()->module);
        $this->assertSame('major', $assignment->fresh()->group_type);

        $this->actingAs($admin, 'admin')->delete(
            route('admin.taxonomy.assignments.destroy', ['certificate', $assignment])
        )->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertFalse((bool) $assignment->fresh()->status);
        $this->actingAs($admin, 'admin')->delete(route('admin.taxonomy.nodes.archive', $programme), [
            'return_profile' => 'certificate',
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertNotNull(CourseCategory::withTrashed()->findOrFail($programme->id)->deleted_at);
        $this->actingAs($admin, 'admin')->post(route('admin.taxonomy.nodes.restore', $programme->id), [
            'return_profile' => 'certificate',
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertNull(CourseCategory::withTrashed()->findOrFail($programme->id)->deleted_at);
        $this->actingAs($admin, 'admin')->post(
            route('admin.taxonomy.assignments.restore', ['certificate', $assignment])
        )->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertTrue((bool) $assignment->fresh()->status);

        $this->actingAs($admin, 'admin')->put(route('admin.taxonomy.units.update', ['certificate', $unit]), [
            'name' => 'Advanced Business Communication',
            'slug' => $unit->slug,
            'code' => 'CBA-102',
            'curriculum_version' => '2026',
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertSame('Advanced Business Communication', $unit->fresh()->name);

        $this->actingAs($admin, 'admin')->delete(route('admin.taxonomy.units.archive', ['certificate', $unit]))
            ->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertFalse((bool) $unit->fresh()->status);
        $this->actingAs($admin, 'admin')->post(route('admin.taxonomy.units.restore', ['certificate', $unit]))
            ->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertTrue((bool) $unit->fresh()->status);
    }

    public function test_create_and_assign_unit_rejects_a_programme_from_another_department_atomically(): void
    {
        $admin = Admin::create([
            'name' => 'Unit Path Admin',
            'email' => 'unit-path-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $programme = CourseCategory::where('slug', 'agricultural-engineering-level-6')->firstOrFail();
        $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', 'cdacc')->firstOrFail();
        $wrongDepartment = app(TaxonomyManagementService::class)->createNode($root->id, [
            'name' => 'Test Health Science Department',
            'slug' => 'test-health-science-department',
            'node_type' => CourseCategory::TYPE_DEPARTMENT,
            'order' => 99,
        ]);

        $this->actingAs($admin, 'admin')->from(route('admin.taxonomy.profiles.show', 'cdacc'))
            ->post(route('admin.taxonomy.units.store', 'cdacc'), [
                '_create_and_assign' => 1,
                'department_id' => $wrongDepartment->id,
                'programme_node_id' => $programme->id,
                'study_year' => 'Level 6',
                'group_type' => 'major',
                'name' => 'Invalid Cross Department Unit',
                'curriculum_version' => 'current',
                'display_order' => 0,
                'is_mandatory' => 1,
            ])->assertRedirect(route('admin.taxonomy.profiles.show', 'cdacc'))
            ->assertSessionHasErrors('programme_node_id');

        $this->assertDatabaseMissing('curriculum_units', ['slug' => 'invalid-cross-department-unit']);
    }

    public function test_product_forms_receive_every_upper_education_architecture(): void
    {
        $options = app(ProductCurriculumContextService::class)->formOptions();

        $this->assertEqualsCanonicalizing(
            ['cdacc', 'tvet', 'certificate', 'diploma', 'undergraduate', 'professional'],
            collect($options['profiles'])->pluck('key')->all()
        );
        $expectedFields = [
            'cdacc' => ['department', 'programme', 'study_year', 'group', 'assignment'],
            'certificate' => ['owner', 'department', 'programme', 'module', 'group', 'assignment'],
            'diploma' => ['owner', 'department', 'programme', 'module', 'group', 'assignment'],
            'undergraduate' => ['owner', 'department', 'programme', 'study_year', 'semester', 'group', 'assignment'],
            'professional' => ['owner', 'programme', 'stage', 'section', 'group', 'assignment'],
        ];
        $profiles = collect($options['profiles'])->keyBy('key');
        foreach ($expectedFields as $profile => $fields) {
            $this->assertSame($fields, $profiles->get($profile)['creation_fields']);
        }
        $assignmentProfiles = collect($options['assignments'])->groupBy('profile');
        foreach (['cdacc', 'tvet', 'certificate', 'diploma', 'undergraduate', 'professional'] as $profile) {
            $this->assertGreaterThanOrEqual(2, $assignmentProfiles->get($profile, collect())->count(), $profile);
        }
        $this->assertTrue(collect($options['assignments'])->contains(
            fn (array $assignment) => $assignment['profile'] === 'cdacc'
                && str_contains($assignment['label'], 'Agricultural Engineering')
                && str_contains($assignment['programme_label'], 'Agriculture and Aquaculture Department')
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
        $this->assertStringContainsString('data-curriculum-architecture', $html);
        $this->assertStringContainsString("text:'programme_label'", $html);
        $this->assertStringContainsString('let pathComplete = true', $html);
    }

    public function test_product_selectors_include_active_taxonomy_departments_and_programmes_without_units(): void
    {
        $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', 'cdacc')->firstOrFail();
        $management = app(TaxonomyManagementService::class);
        $department = $management->createNode($root->id, [
            'name' => 'Water Engineering Department',
            'slug' => 'water-engineering-department',
            'node_type' => CourseCategory::TYPE_DEPARTMENT,
            'order' => 80,
        ]);
        $programme = $management->createNode($department->id, [
            'name' => 'Water Engineering Level 6',
            'slug' => 'water-engineering-level-6',
            'node_type' => CourseCategory::TYPE_PROGRAMME,
            'order' => 0,
        ]);

        $options = app(ProductCurriculumContextService::class)->formOptions();
        $paths = collect($options['taxonomy_paths']);
        $this->assertTrue($paths->contains(fn (array $path) => $path['department_id'] === $department->id));
        $this->assertTrue($paths->contains(fn (array $path) => $path['programme_id'] === $programme->id));
        $this->assertFalse(collect($options['assignments'])->contains(
            fn (array $assignment) => $assignment['programme_id'] === $programme->id
        ));

        $html = view('partials.product-curriculum-context-fields', [
            'curriculumOptions' => $options,
            'curriculumAdmin' => false,
        ])->render();
        $this->assertStringContainsString('Water Engineering Department', $html);
        $this->assertStringContainsString('Water Engineering Level 6', $html);
    }

    public function test_instructor_product_creation_uses_and_persists_the_taxonomy_architecture(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => 'active']);
        $assignment = ProgrammeUnitAssignment::query()
            ->whereHas('programme', fn ($query) => $query->where('taxonomy_profile', 'cdacc'))
            ->where('status', true)
            ->with(['programme', 'unit'])
            ->firstOrFail();

        foreach ([Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION, Product::TYPE_NOTE, Product::TYPE_QUIZ] as $type) {
            $this->actingAs($instructor)->get(route('instructor.products.create', ['type' => $type]))
                ->assertOk()
                ->assertSee('data-curriculum-context', false)
                ->assertSee('data-curriculum-architecture', false)
                ->assertSee('programme_unit_assignment_id', false);
        }

        $this->actingAs($instructor)->post(route('instructor.products.store'), [
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Taxonomy Synced CDACC Paper',
            'description' => 'A CDACC paper classified through the taxonomy architecture.',
            'price' => 0,
            'status' => 'active',
            'education_level' => 'CDACC',
            'curriculum_profile' => 'cdacc',
            'programme_unit_assignment_id' => $assignment->id,
            'assessment_series' => 'July',
            'year' => '2026',
        ])->assertRedirect(route('instructor.products.index', ['type' => Product::TYPE_PAST_PAPER]));

        $product = Product::where('title', 'Taxonomy Synced CDACC Paper')->firstOrFail();
        $this->assertDatabaseHas('product_curriculum_contexts', [
            'product_id' => $product->id,
            'programme_unit_assignment_id' => $assignment->id,
            'curriculum_unit_id' => $assignment->curriculum_unit_id,
        ]);
        $this->assertSame('cdacc', data_get($product->metadata, 'curriculum_context.profile'));
        $this->assertSame('Level 6', data_get($product->metadata, 'curriculum_context.periods.Level'));
    }

    public function test_admin_can_permanently_delete_archived_nodes_individually_and_in_bulk(): void
    {
        $admin = Admin::create([
            'name' => 'Taxonomy Purge Admin',
            'email' => 'taxonomy-purge@example.com',
            'password' => Hash::make('password'),
        ]);
        $service = app(TaxonomyManagementService::class);
        $root = CourseCategory::where('taxonomy_profile', 'certificate')->whereNull('parent_id')->firstOrFail();
        $owner = $service->createNode($root->id, [
            'name' => 'Archived Test Institution',
            'slug' => 'archived-test-institution',
            'node_type' => CourseCategory::TYPE_AWARDING_BODY,
            'order' => 90,
        ]);
        $department = $service->createNode($owner->id, [
            'name' => 'Archived Test School',
            'slug' => 'archived-test-school',
            'node_type' => CourseCategory::TYPE_DEPARTMENT,
            'order' => 0,
        ]);
        $service->archiveNode($owner);

        $this->actingAs($admin, 'admin')->get(route('admin.taxonomy.profiles.show', 'certificate'))
            ->assertOk()
            ->assertSee('Delete selected permanently')
            ->assertSee('Delete permanently');
        $this->actingAs($admin, 'admin')->delete(route('admin.taxonomy.nodes.destroy', $owner->id), [
            'return_profile' => 'certificate',
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        $this->assertDatabaseMissing('course_categories', ['id' => $owner->id]);
        $this->assertDatabaseMissing('course_categories', ['id' => $department->id]);
        $this->assertDatabaseMissing('course_category_translations', ['course_category_id' => $owner->id]);

        $bulkNodes = collect(['bulk-archived-institution-a', 'bulk-archived-institution-b'])
            ->map(function (string $slug, int $order) use ($service, $root) {
                $node = $service->createNode($root->id, [
                    'name' => str($slug)->headline()->toString(),
                    'slug' => $slug,
                    'node_type' => CourseCategory::TYPE_AWARDING_BODY,
                    'order' => 91 + $order,
                ]);
                $service->archiveNode($node);

                return $node;
            });

        $this->actingAs($admin, 'admin')->delete(route('admin.taxonomy.nodes.bulk-destroy'), [
            'nodes' => $bulkNodes->pluck('id')->all(),
            'return_profile' => 'certificate',
        ])->assertRedirect(route('admin.taxonomy.profiles.show', 'certificate'));
        foreach ($bulkNodes as $node) {
            $this->assertDatabaseMissing('course_categories', ['id' => $node->id]);
        }
    }

    public function test_permanent_delete_rejects_archived_nodes_that_are_still_referenced(): void
    {
        $service = app(TaxonomyManagementService::class);
        $root = CourseCategory::where('taxonomy_profile', 'certificate')->whereNull('parent_id')->firstOrFail();
        $owner = $service->createNode($root->id, [
            'name' => 'Referenced Archived Institution',
            'slug' => 'referenced-archived-institution',
            'node_type' => CourseCategory::TYPE_AWARDING_BODY,
            'order' => 95,
        ]);
        $service->archiveNode($owner);
        Product::create([
            'type' => Product::TYPE_PAST_PAPER,
            'title' => 'Referenced Taxonomy Product',
            'slug' => 'referenced-taxonomy-product',
            'price' => 0,
            'status' => 'active',
            'is_approved' => 'approved',
            'category_id' => $owner->id,
        ]);

        try {
            $service->permanentlyDeleteNodes([$owner->id]);
            $this->fail('Expected referenced taxonomy node deletion to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('node', $exception->errors());
        }

        $this->assertDatabaseHas('course_categories', ['id' => $owner->id]);
    }

    public function test_upper_education_sample_roots_have_clean_labels_and_no_cdacc_courses_duplicate(): void
    {
        $labels = CourseCategory::query()->with('translation')
            ->whereNull('parent_id')->whereNotNull('taxonomy_profile')
            ->get()->pluck('name', 'taxonomy_profile');

        $this->assertSame('CDACC', $labels['cdacc']);
        $this->assertSame('Certificate', $labels['certificate']);
        $this->assertSame('Diploma', $labels['diploma']);
        $this->assertSame('Undergraduate', $labels['undergraduate']);
        $this->assertSame('Professional', $labels['professional']);
        $this->assertSame(1, CourseCategory::query()->whereNull('parent_id')->where('taxonomy_profile', 'cdacc')->count());
        $this->assertDatabaseMissing('course_category_translations', ['name' => 'CDACC Courses']);

        $educationLabels = collect(app(ProductMetadataCatalogService::class)->educationLevelDefinitions())->pluck('label');
        foreach (['CDACC', 'TVET', 'Certificate', 'Diploma', 'Undergraduate', 'Professional'] as $label) {
            $this->assertTrue($educationLabels->contains($label), $label);
        }
        $this->assertFalse($educationLabels->contains('CDACC Courses'));
    }

    public function test_sample_seed_merges_a_legacy_cdacc_courses_root_into_cdacc(): void
    {
        $duplicate = CourseCategory::create([
            'slug' => 'cdacc-courses',
            'node_type' => CourseCategory::TYPE_EDUCATION_LEVEL,
            'taxonomy_profile' => 'cdacc',
            'status' => true,
            'order' => 99,
        ]);
        DB::table('course_category_translations')->insert([
            'course_category_id' => $duplicate->id,
            'lang_code' => 'en',
            'name' => 'CDACC Courses',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $child = CourseCategory::create([
            'parent_id' => $duplicate->id,
            'slug' => 'legacy-cdacc-department',
            'node_type' => CourseCategory::TYPE_DEPARTMENT,
            'taxonomy_profile' => 'cdacc',
            'status' => true,
        ]);

        $migration = require database_path('migrations/2026_11_19_000005_seed_upper_education_product_taxonomy_samples.php');
        $migration->up();

        $canonical = CourseCategory::whereNull('parent_id')->where('slug', 'cdacc')->firstOrFail();
        $this->assertSame($canonical->id, $child->fresh()->parent_id);
        $this->assertNotNull($duplicate->fresh()->deleted_at);
        $this->assertFalse((bool) $duplicate->fresh()->status);
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
        $this->assertStringContainsString(
            (string) data_get($snapshot, 'curriculum_context.department'),
            (string) data_get($snapshot, 'curriculum_context.programme_label')
        );
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
