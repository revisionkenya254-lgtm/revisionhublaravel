<?php

namespace App\Services\Taxonomy;

use App\Models\Product;
use App\Services\ProductIdentityService;
use Illuminate\Support\Str;

class ProductEducationContextService
{
    public function __construct(
        private readonly ProductIdentityService $identities,
        private readonly EducationFamilyService $families
    ) {
    }

    public function forProduct(Product $product): array
    {
        $identity = $this->identities->identityForProduct($product);
        $curriculum = (array) data_get($product->metadata, 'curriculum_context', []);
        $catalog = (array) data_get($product->metadata, 'catalog_identity', []);
        $profile = $curriculum['profile'] ?? null;

        if (filled($profile)) {
            return $this->upperContext((string) $profile, $curriculum, $identity);
        }

        $level = [
            'slug' => $identity['main_category'] ?? $catalog['main_category'] ?? null,
            'label' => $identity['education_level'] ?? $identity['main_category_label']
                ?? $catalog['main_category_label'] ?? null,
        ];
        $description = $this->families->describe($level);

        return [
            'family' => $description['family'],
            'family_label' => $description['family_label'],
            'architecture' => $description['architecture'],
            'level' => $level,
            'profile' => null,
            'grade_or_exam' => $this->namedNode(
                $identity['category'] ?? $catalog['category'] ?? null,
                $identity['class_grade'] ?? $identity['category_label']
                    ?? $catalog['category_label'] ?? null
            ),
            'subject' => $this->namedNode(
                $identity['subject'] ?? $catalog['subject'] ?? null,
                $identity['subject_label'] ?? $catalog['subject_label'] ?? null
            ),
            'owner' => null,
            'department' => null,
            'programme' => null,
            'periods' => [],
            'unit_group' => null,
            'unit' => null,
        ];
    }

    private function upperContext(string $profile, array $context, array $identity): array
    {
        $config = config('higher_education_taxonomy.profiles.'.$profile, []);
        $levelLabel = $config['label'] ?? $identity['education_level'] ?? Str::headline($profile);
        $levelSlug = $identity['main_category'] ?? $this->levelSlug($profile);
        $groupType = $context['group_type'] ?? null;

        return [
            'family' => EducationFamilyService::UPPER,
            'family_label' => $this->families->label(EducationFamilyService::UPPER),
            'architecture' => $this->families->upperArchitecture($profile),
            'level' => ['slug' => $levelSlug, 'label' => $levelLabel],
            'profile' => $profile,
            'grade_or_exam' => null,
            'subject' => null,
            'owner' => $this->idNameNode($context['awarding_body_id'] ?? null, $context['awarding_body'] ?? null),
            'department' => $this->idNameNode($context['department_id'] ?? null, $context['department'] ?? null),
            'programme' => array_filter([
                'id' => isset($context['programme_id']) ? (int) $context['programme_id'] : null,
                'name' => $context['programme'] ?? $context['programme_label'] ?? null,
                'path' => $context['programme_path'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''),
            'periods' => collect($config['period_fields'] ?? [])
                ->filter(fn (string $field) => filled($context[$field] ?? null))
                ->map(fn (string $field) => [
                    'key' => $field,
                    'label' => $config['labels'][$field] ?? Str::headline($field),
                    'value' => $context[$field],
                ])->values()->all(),
            'unit_group' => filled($groupType) ? [
                'key' => $groupType,
                'label' => $config['group_types'][$groupType] ?? Str::headline((string) $groupType),
            ] : null,
            'unit' => array_filter([
                'id' => isset($context['unit_id']) ? (int) $context['unit_id'] : null,
                'code' => $context['unit_code'] ?? null,
                'name' => $context['unit'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    }

    private function namedNode(mixed $slug, mixed $label): ?array
    {
        return filled($slug) || filled($label) ? ['slug' => $slug, 'label' => $label] : null;
    }

    private function idNameNode(mixed $id, mixed $name): ?array
    {
        return filled($id) || filled($name) ? [
            'id' => filled($id) ? (int) $id : null,
            'name' => $name,
        ] : null;
    }

    private function levelSlug(string $profile): string
    {
        return match ($profile) {
            'certificate' => 'certificate-courses',
            'diploma' => 'diploma-courses',
            'professional' => 'professional-courses',
            default => $profile,
        };
    }
}
