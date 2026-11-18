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
        $report = ['profile' => $profile, 'programmes' => $programmes->count(), 'candidates' => 0, 'created_units' => 0, 'assigned_units' => 0, 'linked_resources' => 0, 'warnings' => []];

        foreach ($programmes as $programme) {
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
                if (! $apply) {
                    continue;
                }

                $slug = Str::slug($unitName);
                $unit = CurriculumUnit::where('awarding_body_id', $root->id)
                    ->where('slug', $slug)->where('curriculum_version', 'legacy-import')->first();
                if (! $unit) {
                    $unit = $this->units->create($root, [
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
}
