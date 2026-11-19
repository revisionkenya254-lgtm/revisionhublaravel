<?php

namespace App\Services\Taxonomy;

use App\Models\ProgrammeUnitAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Course\app\Models\CourseCategory;

class HigherEducationTaxonomyRepository
{
    public function profile(string $profile, bool $publicOnly = true): array
    {
        $root = CourseCategory::query()->with('translation')
            ->whereNull('parent_id')->where('taxonomy_profile', $profile)->firstOrFail();
        $nodes = CourseCategory::query()->with('translation')->where('id', '!=', $root->id)
            ->where('taxonomy_profile', $profile)
            ->when($publicOnly, fn ($query) => $query->where('status', true))
            ->orderBy('order')->orderBy('id')->get();
        $byParent = $nodes->groupBy(fn (CourseCategory $node) => (string) $node->parent_id);
        $programmeIds = $nodes->where('node_type', CourseCategory::TYPE_PROGRAMME)->pluck('id');
        $assignments = ProgrammeUnitAssignment::query()
            ->with(['unit.assignments', 'unit.resourceContexts.product', 'resourceContexts.product'])->whereIn('programme_node_id', $programmeIds)
            ->when($publicOnly, fn ($query) => $query->where('status', true)
                ->whereHas('unit', fn ($unitQuery) => $unitQuery->where('status', true)))
            ->orderBy('group_type')->orderBy('display_order')->orderBy('id')->get()
            ->groupBy('programme_node_id');

        return $this->node($root, $byParent, $assignments, $publicOnly);
    }

    private function node(CourseCategory $node, Collection $byParent, Collection $assignments, bool $publicOnly): array
    {
        $children = collect($byParent->get((string) $node->id, collect()))
            ->map(fn (CourseCategory $child) => $this->node($child, $byParent, $assignments, $publicOnly))
            ->values()->all();

        if ($node->node_type === CourseCategory::TYPE_PROGRAMME) {
            $programmeAssignments = collect($assignments->get($node->id, collect()));
            $config = config('higher_education_taxonomy.profiles.'.$node->taxonomy_profile, []);
            $children = array_merge($children, $this->assignmentTree(
                $node,
                $programmeAssignments,
                $config['period_fields'] ?? [],
                $config['group_types'] ?? [],
                $publicOnly
            ));
        }

        return [
            'id' => $node->id,
            'parent_id' => $node->parent_id,
            'slug' => $node->slug,
            'label' => $node->translation?->name ?? $node->slug,
            'node_type' => $node->node_type,
            'profile' => $node->taxonomy_profile,
            'metadata' => $node->taxonomy_metadata ?? (object) [],
            'children' => $children,
        ];
    }

    private function assignmentTree(
        CourseCategory $programme,
        Collection $assignments,
        array $periodFields,
        array $groupTypes,
        bool $publicOnly,
        array $path = []
    ): array {
        if ($periodFields !== []) {
            $field = array_shift($periodFields);
            $hasPeriodValues = $assignments->contains(fn (ProgrammeUnitAssignment $assignment) => filled($assignment->{$field}));
            if ($hasPeriodValues) {
                return $assignments
                    ->groupBy(fn (ProgrammeUnitAssignment $assignment) => filled($assignment->{$field})
                        ? (string) $assignment->{$field}
                        : 'Unspecified')
                    ->map(function (Collection $periodAssignments, string $value) use (
                        $programme, $periodFields, $groupTypes, $publicOnly, $path, $field
                    ) {
                        $periodPath = [...$path, $field.'-'.$value];
                        return [
                            'id' => 'period-'.$programme->id.'-'.Str::slug(implode('-', $periodPath)),
                            'slug' => Str::slug($value),
                            'label' => $value,
                            'node_type' => 'study_period',
                            'period_type' => $field,
                            'children' => $this->assignmentTree(
                                $programme,
                                $periodAssignments,
                                $periodFields,
                                $groupTypes,
                                $publicOnly,
                                $periodPath
                            ),
                        ];
                    })->values()->all();
            }

            return $this->assignmentTree(
                $programme, $assignments, $periodFields, $groupTypes, $publicOnly, $path
            );
        }

        $groups = [];
        foreach ($groupTypes as $group => $label) {
            $groupAssignments = $assignments->where('group_type', $group)->values();
            if ($groupAssignments->isEmpty() && $publicOnly) {
                continue;
            }
            $groups[] = [
                'id' => 'group-'.$programme->id.'-'.Str::slug(implode('-', [...$path, $group])),
                'slug' => Str::slug($label),
                'label' => $label,
                'node_type' => 'unit_group',
                'group_type' => $group,
                'children' => $groupAssignments->map(
                    fn (ProgrammeUnitAssignment $assignment) => $this->unitNode($assignment, $publicOnly)
                )->all(),
            ];
        }

        return $groups;
    }

    private function unitNode(ProgrammeUnitAssignment $assignment, bool $publicOnly): array
    {
        return [
            'id' => 'unit-'.$assignment->unit->id,
            'assignment_id' => $assignment->id,
            'slug' => $assignment->unit->slug,
            'label' => $assignment->unit->name,
            'code' => $assignment->unit->code,
            'curriculum_version' => $assignment->unit->curriculum_version,
            'node_type' => 'unit',
            'active' => (bool) $assignment->status,
            'shared_count' => $assignment->unit->assignments->where('status', true)->count(),
            'children' => $assignment->unit->resourceContexts
                ->whereNull('programme_unit_assignment_id')
                ->merge($assignment->resourceContexts)
                ->unique('id')
                ->filter(fn ($context) => ! $publicOnly || ($context->product?->status === 'active'
                    && $context->product?->is_approved === 'approved'))
                ->map(fn ($context) => [
                    'id' => 'resource-'.$context->product_id,
                    'slug' => $context->product?->slug,
                    'label' => $context->product?->title,
                    'node_type' => 'resource',
                    'resource_type' => $context->resource_type,
                    'assessment_series' => $context->assessment_series,
                    'assessment_year' => $context->assessment_year,
                ])->filter(fn ($resource) => filled($resource['slug']))->values()->all(),
        ];
    }
}
