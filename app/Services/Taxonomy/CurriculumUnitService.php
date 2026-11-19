<?php

namespace App\Services\Taxonomy;

use App\Models\CurriculumUnit;
use App\Models\Product;
use App\Models\ProductCurriculumContext;
use App\Models\ProgrammeUnitAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Course\app\Models\CourseCategory;
use Modules\Course\app\Service\CourseCategoryCacheClear;

class CurriculumUnitService
{
    public function __construct(private readonly TaxonomyAuditService $audit)
    {
    }

    public function create(CourseCategory $awardingBody, array $data): CurriculumUnit
    {
        $this->assertAwardingBody($awardingBody);

        return DB::transaction(function () use ($awardingBody, $data) {
            $slug = Str::slug($data['slug'] ?? $data['name']);
            $version = trim((string) ($data['curriculum_version'] ?? 'current')) ?: 'current';

            if (CurriculumUnit::where('awarding_body_id', $awardingBody->id)
                ->where('slug', $slug)->where('curriculum_version', $version)->exists()) {
                throw ValidationException::withMessages([
                    'slug' => __('This unit already exists for the selected curriculum version.'),
                ]);
            }

            $unit = CurriculumUnit::create([
                'awarding_body_id' => $awardingBody->id,
                'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
                'name' => trim($data['name']),
                'slug' => $slug,
                'curriculum_version' => $version,
                'description' => $data['description'] ?? null,
                'status' => true,
            ]);
            $this->audit->record('curriculum_unit_created', $awardingBody->id, null, $unit->toArray(), [
                'curriculum_unit_id' => $unit->id,
            ]);
            $this->clearCaches();

            return $unit;
        });
    }

    public function update(CurriculumUnit $unit, array $data): CurriculumUnit
    {
        return DB::transaction(function () use ($unit, $data) {
            $unit = CurriculumUnit::lockForUpdate()->findOrFail($unit->id);
            $before = $unit->toArray();
            $slug = Str::slug($data['slug'] ?? $data['name']);
            $version = trim((string) ($data['curriculum_version'] ?? $unit->curriculum_version)) ?: 'current';
            $conflict = CurriculumUnit::where('awarding_body_id', $unit->awarding_body_id)
                ->where('slug', $slug)->where('curriculum_version', $version)
                ->where('id', '!=', $unit->id)->exists();
            if ($conflict) {
                throw ValidationException::withMessages(['slug' => __('That unit identity is already in use.')]);
            }

            $unit->update([
                'code' => filled($data['code'] ?? null) ? trim($data['code']) : null,
                'name' => trim($data['name']),
                'slug' => $slug,
                'curriculum_version' => $version,
                'description' => $data['description'] ?? null,
            ]);
            $this->audit->record('curriculum_unit_updated', $unit->awarding_body_id, $before, $unit->fresh()->toArray(), [
                'curriculum_unit_id' => $unit->id,
            ]);
            $this->clearCaches();

            return $unit;
        });
    }

    public function assign(CourseCategory $programme, CurriculumUnit $unit, array $data): ProgrammeUnitAssignment
    {
        $this->assertAssignmentPair($programme, $unit);

        $assignment = ProgrammeUnitAssignment::updateOrCreate(
            ['programme_node_id' => $programme->id, 'curriculum_unit_id' => $unit->id],
            [
                'group_type' => $data['group_type'],
                'study_year' => $data['study_year'] ?? null,
                'semester' => $data['semester'] ?? null,
                'module' => $data['module'] ?? null,
                'stage' => $data['stage'] ?? null,
                'section' => $data['section'] ?? null,
                'credit_hours' => $data['credit_hours'] ?? null,
                'is_mandatory' => (bool) ($data['is_mandatory'] ?? true),
                'display_order' => (int) ($data['display_order'] ?? 0),
                'status' => true,
            ]
        );
        $this->audit->record('programme_unit_assigned', $programme->id, null, $assignment->toArray(), [
            'curriculum_unit_id' => $unit->id,
        ]);
        $this->clearCaches();

        return $assignment;
    }

    public function updateAssignment(
        ProgrammeUnitAssignment $assignment,
        CourseCategory $programme,
        CurriculumUnit $unit,
        array $data
    ): ProgrammeUnitAssignment {
        return DB::transaction(function () use ($assignment, $programme, $unit, $data) {
            $assignment = ProgrammeUnitAssignment::lockForUpdate()->findOrFail($assignment->id);
            $this->assertAssignmentPair($programme, $unit);
            $identityChanged = (int) $assignment->programme_node_id !== (int) $programme->id
                || (int) $assignment->curriculum_unit_id !== (int) $unit->id;
            if ($identityChanged && $assignment->resourceContexts()->exists()) {
                throw ValidationException::withMessages([
                    'curriculum_unit_id' => __('A linked product uses this assignment. Keep its programme and unit unchanged.'),
                ]);
            }
            $conflict = ProgrammeUnitAssignment::query()
                ->where('programme_node_id', $programme->id)
                ->where('curriculum_unit_id', $unit->id)
                ->where('id', '!=', $assignment->id)
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages([
                    'curriculum_unit_id' => __('That unit is already assigned to the selected programme.'),
                ]);
            }

            $before = $assignment->toArray();
            $assignment->update([
                'programme_node_id' => $programme->id,
                'curriculum_unit_id' => $unit->id,
                'group_type' => $data['group_type'],
                'study_year' => $data['study_year'] ?? null,
                'semester' => $data['semester'] ?? null,
                'module' => $data['module'] ?? null,
                'stage' => $data['stage'] ?? null,
                'section' => $data['section'] ?? null,
                'credit_hours' => $data['credit_hours'] ?? null,
                'is_mandatory' => (bool) ($data['is_mandatory'] ?? true),
                'display_order' => (int) ($data['display_order'] ?? 0),
                'status' => true,
            ]);
            $this->audit->record('programme_unit_assignment_updated', $programme->id, $before, $assignment->fresh()->toArray(), [
                'curriculum_unit_id' => $unit->id,
            ]);
            $this->clearCaches();

            return $assignment->fresh();
        });
    }

    public function unassign(ProgrammeUnitAssignment $assignment): void
    {
        if ($assignment->resourceContexts()->exists()) {
            throw ValidationException::withMessages([
                'assignment' => __('Move or remove programme-specific resources before unassigning this unit.'),
            ]);
        }
        $before = $assignment->toArray();
        $programmeId = $assignment->programme_node_id;
        $assignment->update(['status' => false]);
        $this->audit->record('programme_unit_unassigned', $programmeId, $before, $assignment->fresh()->toArray());
        $this->clearCaches();
    }

    public function restoreAssignment(ProgrammeUnitAssignment $assignment): void
    {
        $assignment->loadMissing(['programme', 'unit']);
        $this->assertAssignmentPair($assignment->programme, $assignment->unit);
        $before = $assignment->toArray();
        $assignment->update(['status' => true]);
        $this->audit->record(
            'programme_unit_assignment_restored',
            $assignment->programme_node_id,
            $before,
            $assignment->fresh()->toArray()
        );
        $this->clearCaches();
    }

    public function archive(CurriculumUnit $unit): void
    {
        $before = $unit->toArray();
        $unit->update(['status' => false]);
        $this->audit->record('curriculum_unit_archived', $unit->awarding_body_id, $before, $unit->fresh()->toArray(), [
            'curriculum_unit_id' => $unit->id,
            'assignments' => $unit->assignments()->count(),
            'resources' => $unit->resourceContexts()->count(),
        ]);
        $this->clearCaches();
    }

    public function restore(CurriculumUnit $unit): void
    {
        $before = $unit->toArray();
        $unit->update(['status' => true]);
        $this->audit->record('curriculum_unit_restored', $unit->awarding_body_id, $before, $unit->fresh()->toArray(), [
            'curriculum_unit_id' => $unit->id,
        ]);
        $this->clearCaches();
    }

    public function attachResource(Product $product, CurriculumUnit $unit, array $data): ProductCurriculumContext
    {
        $assignmentId = $data['programme_unit_assignment_id'] ?? null;
        if ($assignmentId) {
            $valid = ProgrammeUnitAssignment::whereKey($assignmentId)
                ->where('curriculum_unit_id', $unit->id)->exists();
            if (! $valid) {
                throw ValidationException::withMessages(['programme_unit_assignment_id' => __('Invalid programme assignment.')]);
            }
        }

        $context = ProductCurriculumContext::updateOrCreate(
            [
                'product_id' => $product->id,
                'curriculum_unit_id' => $unit->id,
                'programme_unit_assignment_id' => $assignmentId,
            ],
            [
                'resource_type' => $data['resource_type'] ?? $product->type,
                'assessment_series' => $data['assessment_series'] ?? null,
                'assessment_year' => $data['assessment_year'] ?? null,
                'paper_number' => $data['paper_number'] ?? null,
            ]
        );
        $this->audit->record('unit_resource_attached', $unit->awarding_body_id, null, $context->toArray(), [
            'product_id' => $product->id,
            'curriculum_unit_id' => $unit->id,
        ]);

        return $context;
    }

    private function assertAwardingBody(CourseCategory $node): void
    {
        $isProfileRoot = $node->parent_id === null
            && $node->node_type === CourseCategory::TYPE_EDUCATION_LEVEL
            && filled($node->taxonomy_profile);
        $isAwardingBody = $node->node_type === CourseCategory::TYPE_AWARDING_BODY
            && filled($node->taxonomy_profile);

        if (! $isProfileRoot && ! $isAwardingBody) {
            throw ValidationException::withMessages(['awarding_body' => __('Select a higher-education taxonomy root.')]);
        }
    }

    private function assertAssignmentPair(CourseCategory $programme, CurriculumUnit $unit): void
    {
        if ($programme->node_type !== CourseCategory::TYPE_PROGRAMME) {
            throw ValidationException::withMessages(['programme' => __('Units can only be assigned to programme nodes.')]);
        }
        if (! $unit->status) {
            throw ValidationException::withMessages(['unit' => __('Restore the canonical unit before assigning it.')]);
        }
        $owner = $unit->awardingBody()->firstOrFail();
        if ($this->rootOf($programme)->id !== $this->rootOf($owner)->id
            || ! $this->ownerContainsProgramme($owner, $programme)) {
            throw ValidationException::withMessages(['unit' => __('The unit and programme must belong to the same awarding body.')]);
        }
    }

    private function rootOf(CourseCategory $node): CourseCategory
    {
        while ($node->parent_id) {
            $node = CourseCategory::findOrFail($node->parent_id);
        }

        return $node;
    }

    private function ownerContainsProgramme(CourseCategory $owner, CourseCategory $programme): bool
    {
        if ($owner->parent_id === null) {
            return true;
        }

        $current = $programme;
        while ($current->parent_id) {
            if ((int) $current->parent_id === (int) $owner->id) {
                return true;
            }
            $current = CourseCategory::findOrFail($current->parent_id);
        }

        return false;
    }

    private function clearCaches(): void
    {
        CourseCategoryCacheClear::clear();
        app(TaxonomyCatalogRepository::class)->clearCache();
    }
}
