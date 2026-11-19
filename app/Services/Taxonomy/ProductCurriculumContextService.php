<?php

namespace App\Services\Taxonomy;

use App\Models\Product;
use App\Models\CurriculumUnit;
use App\Models\ProgrammeUnitAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Course\app\Models\CourseCategory;

class ProductCurriculumContextService
{
    public function __construct(private readonly CurriculumUnitService $curriculumUnits)
    {
    }

    public function formOptions(?Product $product = null): array
    {
        $selected = $product?->curriculumContexts()
            ->with(['assignment.programme', 'unit.awardingBody'])
            ->first();
        $nodes = CourseCategory::query()
            ->with('translation')
            ->whereNotNull('taxonomy_profile')
            ->where('status', true)
            ->get()
            ->keyBy('id');

        $assignments = ProgrammeUnitAssignment::query()
            ->with(['unit.awardingBody.translation', 'programme.translation'])
            ->where('status', true)
            ->whereHas('programme', fn ($query) => $query->where('status', true))
            ->whereHas('unit', fn ($query) => $query->where('status', true))
            ->whereHas('unit.awardingBody', fn ($query) => $query->where('status', true))
            ->orderBy('programme_node_id')
            ->orderBy('group_type')
            ->orderBy('display_order')
            ->get()
            ->map(fn (ProgrammeUnitAssignment $assignment) => $this->assignmentOption($assignment, $nodes))
            ->values()
            ->all();

        $units = collect($assignments)->unique('unit_id')->map(fn (array $option) => [
            'id' => $option['unit_id'],
            'profile' => $option['profile'],
            'label' => collect([$option['owner'], $option['unit']])->filter()->implode(' / '),
        ])->values()->all();

        $profiles = collect(config('higher_education_taxonomy.profiles', []))
            ->map(fn (array $profile, string $key) => [
                'key' => $key,
                'label' => $profile['label'] ?? Str::headline($key),
                'accent' => $profile['accent'] ?? '#334155',
                'labels' => $profile['labels'] ?? [],
                'creation_fields' => $profile['creation_fields'] ?? ['owner', 'department', 'programme', 'assignment'],
                'group_types' => $profile['group_types'] ?? [],
            ])->values()->all();

        return [
            'profiles' => $profiles,
            'assignments' => $assignments,
            'units' => $units,
            'selected' => [
                'profile' => old('curriculum_profile', $selected?->assignment?->programme?->taxonomy_profile
                    ?? $selected?->unit?->awardingBody?->taxonomy_profile),
                'assignment_id' => old('programme_unit_assignment_id', $selected?->programme_unit_assignment_id),
                'unit_id' => old('curriculum_unit_id', $selected?->curriculum_unit_id),
                'assessment_series' => old('assessment_series', $selected?->assessment_series),
                'paper_number' => old('paper_number', $selected?->paper_number),
            ],
        ];
    }

    public function validateRequest(Request $request, bool $admin = false): void
    {
        $profile = $this->requestedProfile($request);
        $publishing = $request->input('status') !== 'is_draft';
        $approving = $admin && $request->input('is_approved') === 'approved';

        if (! $profile) {
            return;
        }

        $assignmentId = $request->integer('programme_unit_assignment_id');
        $unitId = $request->integer('curriculum_unit_id');
        if (($publishing || $approving) && ! $assignmentId && ! ($admin && $unitId)) {
            throw ValidationException::withMessages([
                'programme_unit_assignment_id' => __('Select the programme unit before submitting this higher-education product.'),
            ]);
        }

        if ($assignmentId) {
            $assignment = ProgrammeUnitAssignment::query()->with(['unit.awardingBody', 'programme'])
                ->where('status', true)->find($assignmentId);
            if (! $assignment || ! $assignment->unit?->status
                || $assignment->programme?->taxonomy_profile !== $profile
                || $assignment->unit?->awardingBody?->taxonomy_profile !== $profile) {
                throw ValidationException::withMessages([
                    'programme_unit_assignment_id' => __('The selected unit does not belong to this education profile.'),
                ]);
            }
        } elseif ($admin && $unitId) {
            $unit = CurriculumUnit::query()->with('awardingBody')->where('status', true)->find($unitId);
            if (! $unit || $unit->awardingBody?->taxonomy_profile !== $profile) {
                throw ValidationException::withMessages([
                    'curriculum_unit_id' => __('The selected shared unit does not belong to this education profile.'),
                ]);
            }
        }
    }

    public function sync(Request $request, Product $product, bool $admin = false): void
    {
        $assignmentId = $request->integer('programme_unit_assignment_id');
        $unitId = $request->integer('curriculum_unit_id');
        $assignment = $assignmentId
            ? ProgrammeUnitAssignment::query()->with('unit')->findOrFail($assignmentId)
            : null;
        $unit = $assignment?->unit ?? ($admin && $unitId ? CurriculumUnit::findOrFail($unitId) : null);

        if (! $unit) {
            $product->curriculumContexts()->delete();
            return;
        }

        $product->curriculumContexts()->delete();
        $this->curriculumUnits->attachResource($product, $unit, [
            'programme_unit_assignment_id' => $assignment?->id,
            'resource_type' => $product->type,
            'assessment_series' => $request->input('assessment_series'),
            'assessment_year' => $request->filled('year') ? (int) $request->input('year') : null,
            'paper_number' => $request->input('paper_number'),
        ]);
    }

    public function metadataSnapshot(Request $request): array
    {
        $assignment = $request->integer('programme_unit_assignment_id')
            ? ProgrammeUnitAssignment::query()->with(['unit.awardingBody.translation', 'programme.translation'])
                ->find($request->integer('programme_unit_assignment_id'))
            : null;

        if (! $assignment) {
            return [];
        }

        $department = $this->ancestorOfType($assignment->programme, CourseCategory::TYPE_DEPARTMENT);

        return [
            'curriculum_context' => [
                'profile' => $assignment->programme?->taxonomy_profile,
                'awarding_body_id' => $assignment->unit?->awarding_body_id,
                'awarding_body' => $assignment->unit?->awardingBody?->name,
                'department_id' => $department?->id,
                'department' => $department?->name,
                'programme_id' => $assignment->programme_node_id,
                'programme' => $assignment->programme?->name,
                'assignment_id' => $assignment->id,
                'unit_id' => $assignment->curriculum_unit_id,
                'unit' => $assignment->unit?->name,
                'unit_code' => $assignment->unit?->code,
                'group_type' => $assignment->group_type,
                'study_year' => $assignment->study_year,
                'semester' => $assignment->semester,
                'module' => $assignment->module,
                'stage' => $assignment->stage,
                'section' => $assignment->section,
            ],
        ];
    }

    public function approvalIssues(Product $product): array
    {
        $product->loadMissing(['curriculumContexts.assignment', 'curriculumContexts.unit']);
        $profile = data_get($product->metadata, 'curriculum_context.profile')
            ?: $this->profileFromEducationLevel(data_get($product->metadata, 'education_level'));
        if (! $profile) {
            return [];
        }

        $context = $product->curriculumContexts->first();
        if (! $context?->unit) {
            return [__('A higher-education curriculum unit has not been selected.')];
        }

        if (! $context->unit->status) {
            return [__('The selected curriculum unit is archived.')];
        }

        return [];
    }

    private function requestedProfile(Request $request): ?string
    {
        $profile = trim((string) $request->input('curriculum_profile'));
        return $profile !== '' ? $profile : $this->profileFromEducationLevel($request->input('education_level'));
    }

    private function profileFromEducationLevel(mixed $value): ?string
    {
        $value = Str::lower(trim((string) $value));
        foreach (array_keys(config('higher_education_taxonomy.profiles', [])) as $profile) {
            $aliases = match ($profile) {
                'undergraduate' => ['undergraduate', 'university'],
                'cdacc' => ['cdacc'],
                default => [$profile],
            };
            if (collect($aliases)->contains(fn ($alias) => str_contains($value, $alias))) {
                return $profile;
            }
        }

        return null;
    }

    private function assignmentOption(ProgrammeUnitAssignment $assignment, Collection $nodes): array
    {
        $programme = $assignment->programme;
        $ancestors = collect();
        $current = $programme;
        while ($current?->parent_id && $nodes->has($current->parent_id)) {
            $current = $nodes->get($current->parent_id);
            $ancestors->prepend($current);
        }
        $department = $ancestors->firstWhere('node_type', CourseCategory::TYPE_DEPARTMENT);
        $owner = $assignment->unit->awardingBody;
        $period = collect(['study_year', 'semester', 'module', 'stage', 'section'])
            ->filter(fn ($field) => filled($assignment->{$field}))
            ->map(fn ($field) => Str::headline($field).': '.$assignment->{$field})
            ->implode(' / ');
        $parts = collect([
            $owner?->name,
            $department?->name,
            $programme?->name,
            $period ?: null,
            Str::headline($assignment->group_type),
            $assignment->unit->code ? $assignment->unit->code.' - '.$assignment->unit->name : $assignment->unit->name,
        ])->filter()->values();

        return [
            'id' => $assignment->id,
            'profile' => $programme?->taxonomy_profile,
            'unit_id' => $assignment->curriculum_unit_id,
            'owner_id' => $owner?->id,
            'owner' => $owner?->name,
            'department_id' => $department?->id,
            'department' => $department?->name,
            'programme_id' => $programme?->id,
            'programme' => $programme?->name,
            'group' => $assignment->group_type,
            'period' => $period,
            'study_year' => $assignment->study_year,
            'semester' => $assignment->semester,
            'module' => $assignment->module,
            'stage' => $assignment->stage,
            'section' => $assignment->section,
            'unit' => $assignment->unit->name,
            'unit_code' => $assignment->unit->code,
            'unit_label' => $assignment->unit->code
                ? $assignment->unit->code.' - '.$assignment->unit->name
                : $assignment->unit->name,
            'label' => $parts->implode(' / '),
        ];
    }

    private function ancestorOfType(?CourseCategory $node, string $type): ?CourseCategory
    {
        $current = $node;
        while ($current?->parent_id) {
            $current = CourseCategory::query()->with('translation')->find($current->parent_id);
            if ($current?->node_type === $type) {
                return $current;
            }
        }

        return null;
    }
}
