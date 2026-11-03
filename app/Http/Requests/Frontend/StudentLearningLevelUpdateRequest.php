<?php

namespace App\Http\Requests\Frontend;

use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentLearningLevelUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $educationLevelSlugs = collect(app(TaxonomyCatalogRepository::class)->educationLevels())
            ->pluck('slug')
            ->filter()
            ->all();

        return [
            'education_level' => ['required', 'string', Rule::in($educationLevelSlugs)],
        ];
    }
}
