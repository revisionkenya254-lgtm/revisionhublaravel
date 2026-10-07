<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Modules\Course\app\Helper\CourseCategoryHelper;

class CourseCategorySelectionService
{
    public function __construct(private readonly ProductMetadataCatalogService $metadataCatalog)
    {
    }

    public function formData(): array
    {
        $categories = CourseCategoryHelper::getAll();
        $catalog = $this->metadataCatalog->paperSelectionOptions();
        $tree = $this->buildTree($categories);

        return [
            'tree' => $tree,
            'education_levels' => collect($tree)->pluck('label')->filter()->values()->all()
                ?: ($catalog['education_levels'] ?? []),
            'class_grades_by_level' => $catalog['class_grades_by_level'] ?? [],
            'subjects_by_education_level' => $catalog['subjects_by_education_level'] ?? [],
            'subjects' => $catalog['subjects'] ?? [],
            'exam_categories_by_level' => $catalog['exam_categories_by_level'] ?? [],
            'years' => $catalog['years'] ?? [],
            'defaults' => $this->metadataCatalog->sharedDefaults(),
        ];
    }

    private function buildTree(Collection $categories): array
    {
        $byParent = $categories->groupBy(fn ($category) => $category->parent_id ? (string) $category->parent_id : 'root');

        $buildNode = function ($category) use (&$buildNode, $byParent): array {
            return [
                'id' => (int) $category->id,
                'slug' => (string) $category->slug,
                'label' => (string) $category->name,
                'children' => collect($byParent[(string) $category->id] ?? [])
                    ->map(fn ($child) => $buildNode($child))
                    ->values()
                    ->all(),
            ];
        };

        return collect($byParent['root'] ?? [])
            ->map(fn ($category) => $buildNode($category))
            ->values()
            ->all();
    }
}
