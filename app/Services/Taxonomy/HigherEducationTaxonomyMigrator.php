<?php

namespace App\Services\Taxonomy;

use App\Models\CurriculumUnit;
use App\Models\Product;
use App\Models\ProgrammeUnitAssignment;
use Illuminate\Support\Str;
use Modules\Course\app\Models\CourseCategory;

class HigherEducationTaxonomyMigrator
{
    public function __construct(private readonly CurriculumUnitService $units)
    {
    }

    public function migrate(string $profile, bool $apply = false): array
    {
        $root = CourseCategory::whereNull('parent_id')->where('taxonomy_profile', $profile)->firstOrFail();
        $programmes = CourseCategory::where('taxonomy_profile', $profile)
            ->where('node_type', CourseCategory::TYPE_PROGRAMME)->get();
        $legacyNodes = CourseCategory::where('taxonomy_profile', $profile)
            ->whereIn('node_type', [CourseCategory::TYPE_SCHOOL, CourseCategory::TYPE_COURSE])
            ->with('translation')->get();
        $report = ['profile' => $profile, 'programmes' => $programmes->count(), 'legacy_nodes' => $legacyNodes->count(), 'candidates' => 0, 'created_units' => 0, 'assigned_units' => 0, 'linked_resources' => 0, 'warnings' => [], 'inventory' => []];

        foreach ($legacyNodes as $legacyNode) {
            $report['inventory'][] = [
                'profile' => $profile,
                'curriculum_owner' => '',
                'programme' => $legacyNode->name,
                'programme_slug' => $legacyNode->slug,
                'legacy_unit_name' => '',
                'proposed_unit_slug' => '',
                'product_count' => Product::where('category_id', $legacyNode->id)->count(),
                'action' => 'structure_review',
            ];
        }
        if ($legacyNodes->isNotEmpty()) {
            $report['warnings'][] = "{$legacyNodes->count()} legacy school/course nodes require an institution and programme mapping before conversion.";
        }

        foreach ($programmes as $programme) {
            $owner = $this->curriculumOwner($programme, $root);
            $products = Product::query()->where(function ($query) use ($programme) {
                $query->where('category_id', $programme->id)
                    ->orWhere('metadata->catalog_identity->class_grade', $programme->slug)
                    ->orWhere('metadata->class_grade', $programme->name);
            })->get();

            foreach ($products->groupBy(fn (Product $product) => trim((string) data_get(
                $product->metadata,
                'course',
                data_get($product->metadata, 'subject', '')
            ))) as $unitName => $unitProducts) {
                if ($unitName === '') {
                    $report['warnings'][] = "Programme {$programme->slug} has resources without a unit name.";
                    continue;
                }
                $report['candidates']++;
                $report['inventory'][] = [
                    'profile' => $profile,
                    'curriculum_owner' => $owner->name,
                    'programme' => $programme->name,
                    'programme_slug' => $programme->slug,
                    'legacy_unit_name' => $unitName,
                    'proposed_unit_slug' => Str::slug($unitName),
                    'product_count' => $unitProducts->count(),
                    'action' => $apply ? 'applied' : 'review',
                ];
                if (! $apply) {
                    continue;
                }

                $slug = Str::slug($unitName);
                $unit = CurriculumUnit::where('awarding_body_id', $owner->id)
                    ->where('slug', $slug)->where('curriculum_version', 'legacy-import')->first();
                if (! $unit) {
                    $unit = $this->units->create($owner, [
                        'name' => $unitName,
                        'slug' => $slug,
                        'curriculum_version' => 'legacy-import',
                        'description' => 'Imported from legacy product metadata.',
                    ]);
                    $report['created_units']++;
                }
                $assignment = ProgrammeUnitAssignment::where('programme_node_id', $programme->id)
                    ->where('curriculum_unit_id', $unit->id)->first();
                if (! $assignment) {
                    $assignment = $this->units->assign($programme, $unit, ['group_type' => 'major']);
                    $report['assigned_units']++;
                }
                foreach ($unitProducts as $product) {
                    $this->units->attachResource($product, $unit, [
                        'programme_unit_assignment_id' => $assignment->id,
                        'resource_type' => $product->type,
                        'assessment_series' => data_get($product->metadata, 'exam_category'),
                        'assessment_year' => is_numeric(data_get($product->metadata, 'year'))
                            ? (int) data_get($product->metadata, 'year') : null,
                        'paper_number' => data_get($product->metadata, 'paper'),
                    ]);
                    $report['linked_resources']++;
                }
            }
        }

        return $report;
    }

    private function curriculumOwner(CourseCategory $programme, CourseCategory $fallback): CourseCategory
    {
        $current = $programme;
        while ($current->parent_id) {
            $current = CourseCategory::findOrFail($current->parent_id);
            if ($current->node_type === CourseCategory::TYPE_AWARDING_BODY) {
                return $current;
            }
        }

        return $fallback;
    }
}
