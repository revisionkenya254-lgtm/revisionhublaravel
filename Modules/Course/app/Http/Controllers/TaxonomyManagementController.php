<?php

namespace Modules\Course\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TaxonomyChangeLog;
use App\Models\CurriculumUnit;
use App\Models\ProgrammeUnitAssignment;
use App\Services\Taxonomy\CurriculumUnitService;
use App\Services\Taxonomy\HigherEducationTaxonomyRepository;
use App\Services\Taxonomy\TaxonomyManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Modules\Course\app\Models\CourseCategory;

class TaxonomyManagementController extends Controller
{
    public function __construct(
        private readonly TaxonomyManagementService $taxonomy,
        private readonly CurriculumUnitService $curriculumUnits,
        private readonly HigherEducationTaxonomyRepository $higherEducation
    )
    {
    }

    public function profile(Request $request, string $profile): View
    {
        $profileConfig = config("higher_education_taxonomy.profiles.{$profile}");
        abort_unless(is_array($profileConfig), 404);
        $tree = $this->higherEducation->profile($profile, false);
        $root = CourseCategory::findOrFail($tree['id']);
        $programmes = CourseCategory::with('translation')->where('taxonomy_profile', $profile)
            ->where('node_type', CourseCategory::TYPE_PROGRAMME)->orderBy('order')->get();
        $awardingBodies = CourseCategory::with('translation')
            ->where('taxonomy_profile', $profile)
            ->where(function ($query) {
                $query->where('node_type', CourseCategory::TYPE_AWARDING_BODY)
                    ->orWhere(fn ($rootQuery) => $rootQuery->whereNull('parent_id')
                        ->where('node_type', CourseCategory::TYPE_EDUCATION_LEVEL));
            })->orderBy('order')->get();
        $programmeOwnerIds = $programmes->mapWithKeys(fn (CourseCategory $programme) => [
            $programme->id => $this->curriculumOwnerId($programme, $root),
        ]);
        $units = CurriculumUnit::withCount(['assignments' => fn ($query) => $query->where('status', true)])
            ->with('awardingBody.translation')
            ->whereIn('awarding_body_id', $awardingBodies->pluck('id'))->orderBy('name')->get();
        $editingAssignment = $request->integer('edit_assignment')
            ? ProgrammeUnitAssignment::with(['programme', 'unit'])->findOrFail($request->integer('edit_assignment'))
            : null;
        abort_unless(! $editingAssignment || $editingAssignment->programme?->taxonomy_profile === $profile, 404);
        $archivedNodes = CourseCategory::onlyTrashed()->with(['translation', 'parentCategory'])
            ->where('taxonomy_profile', $profile)->latest('deleted_at')->limit(50)->get();

        return view('course::taxonomy.profile', compact(
            'profile', 'profileConfig', 'tree', 'root', 'programmes', 'programmeOwnerIds', 'awardingBodies', 'units',
            'editingAssignment', 'archivedNodes'
        ));
    }

    public function storeUnit(Request $request, string $profile): RedirectResponse
    {
        $data = $this->validateUnit($request);
        $owner = filled($data['awarding_body_id'] ?? null)
            ? CourseCategory::findOrFail($data['awarding_body_id'])
            : $this->profileRoot($profile);
        abort_unless($owner->taxonomy_profile === $profile, 404);
        $this->curriculumUnits->create($owner, $data);
        return $this->profileSuccess($profile, __('Canonical unit created.'));
    }

    public function updateUnit(Request $request, string $profile, CurriculumUnit $unit): RedirectResponse
    {
        $this->ensureUnitProfile($unit, $profile);
        $this->curriculumUnits->update($unit, $this->validateUnit($request));
        return $this->profileSuccess($profile, __('Canonical unit updated everywhere it is assigned.'));
    }

    public function archiveUnit(string $profile, CurriculumUnit $unit): RedirectResponse
    {
        $this->ensureUnitProfile($unit, $profile);
        $this->curriculumUnits->archive($unit);
        return $this->profileSuccess($profile, __('Canonical unit archived without deleting its assignments or resources.'));
    }

    public function restoreUnit(string $profile, CurriculumUnit $unit): RedirectResponse
    {
        $this->ensureUnitProfile($unit, $profile);
        $this->curriculumUnits->restore($unit);

        return $this->profileSuccess($profile, __('Canonical unit restored.'));
    }

    public function storeAssignment(Request $request, string $profile, CourseCategory $programme): RedirectResponse
    {
        abort_unless($programme->taxonomy_profile === $profile, 404);
        $data = $this->validateAssignment($request, $profile);
        $this->curriculumUnits->assign(
            $programme, CurriculumUnit::findOrFail($data['curriculum_unit_id']), $data
        );
        return $this->profileSuccess($profile, __('Unit assigned to programme.'));
    }

    public function updateAssignment(
        Request $request,
        string $profile,
        ProgrammeUnitAssignment $assignment
    ): RedirectResponse {
        abort_unless($assignment->programme?->taxonomy_profile === $profile, 404);
        $data = $this->validateAssignment($request, $profile, true);
        $programme = CourseCategory::findOrFail($data['programme_node_id']);
        abort_unless($programme->taxonomy_profile === $profile, 404);
        $this->curriculumUnits->updateAssignment(
            $assignment,
            $programme,
            CurriculumUnit::findOrFail($data['curriculum_unit_id']),
            $data
        );

        return $this->profileSuccess($profile, __('Programme unit assignment updated.'));
    }

    public function destroyAssignment(string $profile, ProgrammeUnitAssignment $assignment): RedirectResponse
    {
        abort_unless($assignment->programme?->taxonomy_profile === $profile, 404);
        $this->curriculumUnits->unassign($assignment);
        return $this->profileSuccess($profile, __('Unit removed from programme.'));
    }

    public function restoreAssignment(string $profile, ProgrammeUnitAssignment $assignment): RedirectResponse
    {
        abort_unless($assignment->programme?->taxonomy_profile === $profile, 404);
        $this->curriculumUnits->restoreAssignment($assignment);

        return $this->profileSuccess($profile, __('Programme unit assignment restored.'));
    }

    public function index(): View
    {
        $levels = CourseCategory::with([
            'translation',
            'subCategories' => fn ($query) => $query->with(['translation', 'subCategories.translation'])
                ->orderBy('order')
                ->orderBy('id'),
        ])
            ->whereNull('parent_id')
            ->where('node_type', CourseCategory::TYPE_EDUCATION_LEVEL)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $sharedLeaves = $levels->mapWithKeys(function (CourseCategory $level) {
            $classNodes = $level->subCategories->whereIn('node_type', [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_FORM,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ]);

            $leaves = $classNodes
                ->flatMap->subCategories
                ->whereIn('node_type', [CourseCategory::TYPE_SUBJECT, CourseCategory::TYPE_PATHWAY])
                ->groupBy('slug')
                ->map(function ($copies, string $slug) use ($classNodes) {
                    $first = $copies->sortBy('order')->first();

                    return [
                        'slug' => $slug,
                        'name' => $first?->translation?->name ?? $slug,
                        'node_type' => $first?->node_type,
                        'order' => $first?->order ?? 0,
                        'coverage' => $copies->pluck('parent_id')->unique()->count(),
                        'expected' => $classNodes->count(),
                    ];
                })
                ->sortBy('order')
                ->values();

            return [$level->id => $leaves];
        });

        $archivedNodes = CourseCategory::onlyTrashed()
            ->with([
                'translation',
                'parentCategory' => fn ($query) => $query->withTrashed()->with([
                    'translation',
                    'parentCategory' => fn ($parentQuery) => $parentQuery->withTrashed(),
                ]),
            ])
            ->orderByDesc('deleted_at')
            ->limit(200)
            ->get()
            ->filter(fn (CourseCategory $node) => ! $node->parentCategory || ! $node->parentCategory->trashed())
            ->unique(function (CourseCategory $node) {
                $parent = $node->parentCategory;
                if (in_array($node->node_type, [CourseCategory::TYPE_SUBJECT, CourseCategory::TYPE_PATHWAY], true)
                    && $parent
                    && in_array($parent->node_type, [
                        CourseCategory::TYPE_GRADE,
                        CourseCategory::TYPE_FORM,
                        CourseCategory::TYPE_NATIONAL_EXAM,
                    ], true)) {
                    return 'shared:'.$parent->parent_id.':'.$node->slug;
                }

                return 'node:'.$node->id;
            })
            ->take(50)
            ->values();

        $recentChanges = Schema::hasTable('taxonomy_change_logs')
            ? TaxonomyChangeLog::with('admin')->latest()->limit(20)->get()
            : collect();
        $profileSummaries = collect(config('higher_education_taxonomy.profiles', []))
            ->map(function (array $definition, string $profile) use ($levels) {
                $root = $levels->firstWhere('taxonomy_profile', $profile);
                if (! $root) {
                    return null;
                }
                $nodes = CourseCategory::where('taxonomy_profile', $profile)->where('status', true)->get();
                $ownerIds = $nodes->where('node_type', CourseCategory::TYPE_AWARDING_BODY)->pluck('id')
                    ->push($root->id)->unique();

                return [
                    'profile' => $profile,
                    'definition' => $definition,
                    'root' => $root,
                    'owners' => $nodes->where('node_type', CourseCategory::TYPE_AWARDING_BODY)->count(),
                    'departments' => $nodes->where('node_type', CourseCategory::TYPE_DEPARTMENT)->count(),
                    'programmes' => $nodes->where('node_type', CourseCategory::TYPE_PROGRAMME)->count(),
                    'units' => CurriculumUnit::whereIn('awarding_body_id', $ownerIds)->where('status', true)->count(),
                    'assignments' => ProgrammeUnitAssignment::where('status', true)
                        ->whereHas('programme', fn ($query) => $query->where('taxonomy_profile', $profile))
                        ->count(),
                ];
            })->filter()->values();

        return view('course::taxonomy.index', compact(
            'levels', 'sharedLeaves', 'archivedNodes', 'recentChanges', 'profileSummaries'
        ));
    }

    public function createNode(Request $request): View|RedirectResponse
    {
        $parent = $request->integer('parent_id')
            ? CourseCategory::findOrFail($request->integer('parent_id'))
            : null;

        if ($parent && in_array($parent->node_type, [
            CourseCategory::TYPE_GRADE,
            CourseCategory::TYPE_FORM,
            CourseCategory::TYPE_NATIONAL_EXAM,
        ], true)) {
            return redirect()->route('admin.taxonomy.shared.create', [
                'education_level' => $parent->parent_id,
            ]);
        }

        $allowedTypes = $this->taxonomy->allowedChildTypes($parent);

        return view('course::taxonomy.node-form', compact('parent', 'allowedTypes'));
    }

    public function storeNode(Request $request): RedirectResponse
    {
        $data = $this->validateNode($request);
        $this->taxonomy->createNode($request->integer('parent_id') ?: null, $data);

        return $this->success(__('Taxonomy node created.'), $request->input('return_profile'));
    }

    public function editNode(CourseCategory $node): View|RedirectResponse
    {
        $parent = $node->parentCategory;
        if ($parent && in_array($node->node_type, [
            CourseCategory::TYPE_SUBJECT,
            CourseCategory::TYPE_PATHWAY,
        ], true) && in_array($parent->node_type, [
            CourseCategory::TYPE_GRADE,
            CourseCategory::TYPE_FORM,
            CourseCategory::TYPE_NATIONAL_EXAM,
        ], true)) {
            return redirect()->route('admin.taxonomy.shared.edit', [
                'education_level' => $parent->parent_id,
                'slug' => $node->slug,
            ]);
        }

        $allowedTypes = $this->taxonomy->allowedChildTypes($parent);

        return view('course::taxonomy.node-form', compact('node', 'parent', 'allowedTypes'));
    }

    public function updateNode(Request $request, CourseCategory $node): RedirectResponse
    {
        $this->taxonomy->updateNode($node, $this->validateNode($request));

        return $this->success(__('Taxonomy node updated.'), $request->input('return_profile'));
    }

    public function archiveNode(Request $request, CourseCategory $node): RedirectResponse
    {
        $this->taxonomy->archiveNode($node);

        return $this->success(__('Taxonomy branch archived.'), $request->input('return_profile'));
    }

    public function restoreNode(Request $request, int $node): RedirectResponse
    {
        $this->taxonomy->restoreNode($node);

        return $this->success(__('Taxonomy branch restored.'), $request->input('return_profile'));
    }

    public function createShared(CourseCategory $educationLevel): View
    {
        $this->ensureEducationLevel($educationLevel);

        return view('course::taxonomy.shared-form', compact('educationLevel'));
    }

    public function storeShared(Request $request, CourseCategory $educationLevel): RedirectResponse
    {
        $this->taxonomy->syncSharedLeaf($educationLevel, null, $this->validateShared($request));

        return $this->success(__('Shared subject or pathway created for every grade and assessment.'));
    }

    public function editShared(CourseCategory $educationLevel, string $slug): View
    {
        $this->ensureEducationLevel($educationLevel);
        $classIds = CourseCategory::where('parent_id', $educationLevel->id)
            ->whereIn('node_type', [
                CourseCategory::TYPE_GRADE,
                CourseCategory::TYPE_FORM,
                CourseCategory::TYPE_NATIONAL_EXAM,
            ])
            ->pluck('id');
        $leaf = CourseCategory::with('translation')
            ->whereIn('parent_id', $classIds)
            ->where('slug', $slug)
            ->firstOrFail();

        return view('course::taxonomy.shared-form', compact('educationLevel', 'leaf', 'slug'));
    }

    public function updateShared(Request $request, CourseCategory $educationLevel, string $slug): RedirectResponse
    {
        $this->taxonomy->syncSharedLeaf($educationLevel, $slug, $this->validateShared($request));

        return $this->success(__('Shared subject or pathway updated everywhere.'));
    }

    public function archiveShared(CourseCategory $educationLevel, string $slug): RedirectResponse
    {
        $this->taxonomy->archiveSharedLeaf($educationLevel, $slug);

        return $this->success(__('Shared subject or pathway archived everywhere.'));
    }

    private function validateNode(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'node_type' => ['required', 'string', 'in:'.implode(',', CourseCategory::NODE_TYPES)],
            'order' => ['required', 'integer', 'min:0', 'max:65535'],
            'parent_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'taxonomy_profile' => ['nullable', 'string', 'max:40'],
            'taxonomy_metadata' => ['nullable', 'array'],
        ]);
    }

    private function validateUnit(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'code' => ['nullable', 'string', 'max:100'],
            'curriculum_version' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'awarding_body_id' => ['nullable', 'integer', 'exists:course_categories,id'],
        ]);
    }

    private function validateAssignment(Request $request, string $profile, bool $updating = false): array
    {
        return $request->validate([
            'programme_node_id' => [$updating ? 'required' : 'nullable', 'integer', 'exists:course_categories,id'],
            'curriculum_unit_id' => ['required', 'integer', 'exists:curriculum_units,id'],
            'group_type' => ['required', Rule::in(array_keys(
                config("higher_education_taxonomy.profiles.{$profile}.group_types", [])
            ))],
            'study_year' => ['nullable', 'string', 'max:40'],
            'semester' => ['nullable', 'string', 'max:80'],
            'module' => ['nullable', 'string', 'max:80'],
            'stage' => ['nullable', 'string', 'max:80'],
            'section' => ['nullable', 'string', 'max:80'],
            'credit_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'is_mandatory' => ['nullable', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);
    }

    private function profileRoot(string $profile): CourseCategory
    {
        abort_unless(array_key_exists($profile, config('higher_education_taxonomy.profiles', [])), 404);
        return CourseCategory::whereNull('parent_id')->where('taxonomy_profile', $profile)->firstOrFail();
    }

    private function curriculumOwnerId(CourseCategory $programme, CourseCategory $fallback): int
    {
        $current = $programme;
        while ($current->parent_id) {
            $current = CourseCategory::findOrFail($current->parent_id);
            if ($current->node_type === CourseCategory::TYPE_AWARDING_BODY) {
                return (int) $current->id;
            }
        }

        return (int) $fallback->id;
    }

    private function ensureUnitProfile(CurriculumUnit $unit, string $profile): void
    {
        $owner = $unit->awardingBody()->firstOrFail();
        $current = $owner;
        while ($current->parent_id) {
            $current = CourseCategory::findOrFail($current->parent_id);
        }
        abort_unless($current->taxonomy_profile === $profile, 404);
    }

    private function profileSuccess(string $profile, string $message): RedirectResponse
    {
        return redirect()->route('admin.taxonomy.profiles.show', $profile)->with([
            'messege' => $message,
            'alert-type' => 'success',
        ]);
    }

    private function validateShared(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'node_type' => ['required', 'in:subject,pathway'],
            'order' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);
    }

    private function ensureEducationLevel(CourseCategory $node): void
    {
        abort_unless(
            $node->parent_id === null && $node->node_type === CourseCategory::TYPE_EDUCATION_LEVEL,
            404
        );
    }

    private function success(string $message, ?string $profile = null): RedirectResponse
    {
        $target = $profile && array_key_exists($profile, config('higher_education_taxonomy.profiles', []))
            ? redirect()->route('admin.taxonomy.profiles.show', $profile)
            : redirect()->route('admin.taxonomy.index');

        return $target->with([
            'messege' => $message,
            'alert-type' => 'success',
        ]);
    }
}
