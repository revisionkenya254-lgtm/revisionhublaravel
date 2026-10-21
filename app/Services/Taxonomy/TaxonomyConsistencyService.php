<?php

namespace App\Services\Taxonomy;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Models\CourseCategory;

class TaxonomyConsistencyService
{
    private const CLASS_TYPES = ['grade', 'form', 'national_exam'];
    private const LEAF_TYPES = ['subject', 'pathway'];

    public function __construct(private readonly TaxonomyManagementService $management)
    {
    }

    public function inspect(bool $repair = false): array
    {
        $before = $this->issues();
        $repairs = [];

        if ($repair) {
            foreach ($before->where('repairable', true) as $issue) {
                $level = CourseCategory::find($issue['education_level_id']);
                if (! $level) {
                    continue;
                }

                $this->management->syncSharedLeaf($level, $issue['slug'], $issue['canonical']);
                $repairs[] = [
                    'type' => $issue['type'],
                    'education_level' => $level->slug,
                    'slug' => $issue['slug'],
                ];
            }
        }

        $after = $repair ? $this->issues() : $before;

        return [
            'healthy' => $after->isEmpty(),
            'issues_before' => $before->values()->all(),
            'repairs' => $repairs,
            'issues_after' => $after->values()->all(),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function issues(): Collection
    {
        $nodes = CourseCategory::with('translation')->orderBy('order')->orderBy('id')->get();
        $nodesById = $nodes->keyBy('id');
        $issues = collect();

        foreach ($nodes->groupBy(fn ($node) => ($node->parent_id ?? 'root').'|'.$node->slug) as $siblings) {
            if ($siblings->count() > 1) {
                $issues->push([
                    'type' => 'duplicate_sibling_slug',
                    'message' => "Duplicate active sibling slug {$siblings->first()->slug}.",
                    'ids' => $siblings->pluck('id')->all(),
                    'repairable' => false,
                ]);
            }
        }

        foreach ($nodes as $node) {
            if (! $node->node_type) {
                $issues->push([
                    'type' => 'missing_node_type',
                    'message' => "Active taxonomy node {$node->id} has no node type.",
                    'ids' => [$node->id],
                    'repairable' => false,
                ]);
                continue;
            }

            $parent = $node->parent_id ? $nodesById->get($node->parent_id) : null;
            if ($node->parent_id && ! $parent) {
                $issues->push([
                    'type' => 'missing_active_parent',
                    'message' => "Active taxonomy node {$node->id} has no active parent.",
                    'ids' => [$node->id],
                    'repairable' => false,
                ]);
                continue;
            }

            if (! in_array($node->node_type, $this->management->allowedChildTypes($parent), true)) {
                $issues->push([
                    'type' => 'invalid_parent_child_type',
                    'message' => "Node {$node->id} ({$node->node_type}) is invalid beneath ".($parent?->node_type ?? 'root').'.',
                    'ids' => [$node->id],
                    'repairable' => false,
                ]);
            }
        }

        $roots = $nodes->whereNull('parent_id')->where('node_type', CourseCategory::TYPE_EDUCATION_LEVEL);
        foreach ($roots as $root) {
            $classes = $nodes->where('parent_id', $root->id)->whereIn('node_type', self::CLASS_TYPES);
            if ($classes->isEmpty()) {
                continue;
            }

            $leaves = $nodes->whereIn('parent_id', $classes->pluck('id'))->whereIn('node_type', self::LEAF_TYPES);
            foreach ($leaves->groupBy('slug') as $slug => $copies) {
                $signatureFor = fn ($copy) => implode('|', [
                    $copy->node_type,
                    $copy->order,
                    $copy->translation?->name ?? $copy->slug,
                ]);
                $signatureGroups = $copies->groupBy($signatureFor);
                $canonicalSignature = $signatureGroups->sortByDesc(fn ($group) => $group->count())->keys()->first();
                $canonical = $signatureGroups[$canonicalSignature]->sortBy('parent_id')->first();
                $coverage = $copies->pluck('parent_id')->unique()->count();

                if ($coverage !== $classes->count() || $signatureGroups->count() !== 1) {
                    $issues->push([
                        'type' => 'shared_leaf_drift',
                        'message' => "{$root->slug}/{$slug} covers {$coverage} of {$classes->count()} class nodes or differs between copies.",
                        'education_level_id' => $root->id,
                        'slug' => $slug,
                        'ids' => $copies->pluck('id')->all(),
                        'canonical' => [
                            'name' => $canonical->translation?->name ?? $canonical->slug,
                            'slug' => $canonical->slug,
                            'node_type' => $canonical->node_type,
                            'order' => $canonical->order,
                        ],
                        'repairable' => true,
                    ]);
                }
            }
        }

        return $issues;
    }
}
