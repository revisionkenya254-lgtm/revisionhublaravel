<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Course\app\Models\CourseCategory;

class ProductIdentityService
{
    public function stampMetadata(array $metadata, array $context = [], ?Product $existing = null): array
    {
        $metadata['catalog_identity'] = $this->buildIdentity($context, $existing);

        return $metadata;
    }

    public function buildIdentity(array $context = [], ?Product $existing = null): array
    {
        $existingMetadata = $existing?->metadata ?? [];

        $type = $this->valueFrom([
            $context['type'] ?? null,
            $existing?->type ?? null,
            data_get($existingMetadata, 'catalog_identity.type'),
        ]);
        $educationLevel = $this->valueFrom([
            $context['education_level'] ?? null,
            data_get($existingMetadata, 'education_level'),
            data_get($existingMetadata, 'catalog_identity.education_level'),
        ]);
        $classGrade = $this->valueFrom([
            $context['class_grade'] ?? null,
            data_get($existingMetadata, 'class_grade'),
            data_get($existingMetadata, 'catalog_identity.category_label'),
        ]);
        $subjectLabel = $this->valueFrom([
            $context['subject'] ?? null,
            $context['course'] ?? null,
            $context['topic'] ?? null,
            $context['paper'] ?? null,
            data_get($existingMetadata, 'subject'),
            data_get($existingMetadata, 'course'),
            data_get($existingMetadata, 'topic'),
            data_get($existingMetadata, 'paper'),
            data_get($existingMetadata, 'catalog_identity.subject_label'),
        ]);

        $mainCategory = $this->valueFrom([
            $context['main_category'] ?? null,
            data_get($existingMetadata, 'catalog_identity.main_category'),
            $this->inferMainCategoryFromEducationLevel($educationLevel),
        ]);
        $category = $this->valueFrom([
            $context['category'] ?? null,
            data_get($existingMetadata, 'catalog_identity.category'),
            $this->inferCategoryFromClassGrade($classGrade),
        ]);
        $subject = $this->normalizeSlugValue($subjectLabel);

        return array_filter([
            'type' => $this->normalizeSlugValue($type),
            'main_category' => $this->normalizeSlugValue($mainCategory),
            'main_category_label' => $educationLevel,
            'category' => $this->normalizeSlugValue($category),
            'category_label' => $classGrade,
            'subject' => $subject,
            'subject_label' => $subjectLabel,
            'education_level' => $educationLevel,
            'class_grade' => $classGrade,
            'exam_category' => $this->valueFrom([
                $context['exam_category'] ?? null,
                data_get($existingMetadata, 'exam_category'),
                data_get($existingMetadata, 'catalog_identity.exam_category'),
            ]),
            'paper' => $this->valueFrom([
                $context['paper'] ?? null,
                data_get($existingMetadata, 'paper'),
                data_get($existingMetadata, 'catalog_identity.paper'),
            ]),
            'year' => $this->valueFrom([
                $context['year'] ?? null,
                data_get($existingMetadata, 'year'),
                data_get($existingMetadata, 'catalog_identity.year'),
            ]),
            'language' => $this->valueFrom([
                $context['language'] ?? null,
                data_get($existingMetadata, 'language'),
                data_get($existingMetadata, 'catalog_identity.language'),
            ]),
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function matchesRequest(Product $product, Request $request): bool
    {
        $requestedType = $this->normalizeTypeValue($request->input('type'));

        if ($requestedType !== null && $requestedType !== '' && $product->type !== $requestedType) {
            return false;
        }

        foreach (['main_category', 'category', 'subject'] as $key) {
            $requested = $this->normalizeRequestSelectionValue($request->input($key));

            if ($requested === null || $requested === '') {
                continue;
            }

            if (! $this->matchesField($product, $key, $requested)) {
                return false;
            }
        }

        return true;
    }

    public function identityForProduct(Product $product): array
    {
        $metadataIdentity = $this->buildIdentity($product->metadata ?? [], $product);
        $categoryIdentity = $this->categoryIdentityForProduct($product);

        return array_filter(
            array_merge($categoryIdentity, $metadataIdentity),
            fn ($value) => $value !== null && $value !== ''
        );
    }

    private function categoryIdentityForProduct(Product $product): array
    {
        $category = $product->relationLoaded('category') ? $product->getRelation('category') : null;

        if (! $category || ! $category->exists) {
            $category = $product->category()->with(['parentCategory.parentCategory'])->first();
        } else {
            $category->loadMissing(['parentCategory.parentCategory']);
        }

        if (! $category || ! $category->exists) {
            return [];
        }

        $path = $this->categoryPath($category);
        $root = $path[0] ?? null;
        $level = $path[1] ?? null;
        $leaf = $path[2] ?? null;

        return array_filter([
            'main_category' => $root?->slug,
            'main_category_label' => $root?->name,
            'category' => $level?->slug ?? $category->slug,
            'category_label' => $level?->name ?? $category->name,
            'subject' => $leaf?->slug ?? $category->slug,
            'subject_label' => $leaf?->name ?? $category->name,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Build an ancestor path from root to leaf.
     *
     * @return array<int, CourseCategory>
     */
    private function categoryPath(CourseCategory $category): array
    {
        $path = [];
        $node = $category;

        while ($node && $node->exists) {
            array_unshift($path, $node);
            $node = $node->parentCategory;
        }

        return $path;
    }

    private function matchesField(Product $product, string $key, string $requested): bool
    {
        $identity = $this->identityForProduct($product);
        $metadata = $product->metadata ?? [];

        $candidates = match ($key) {
            'main_category' => [
                $identity['main_category'] ?? null,
                $identity['main_category_label'] ?? null,
                data_get($metadata, 'education_level'),
            ],
            'category' => [
                $identity['category'] ?? null,
                $identity['category_label'] ?? null,
                $this->normalizeCategoryLabel(data_get($metadata, 'class_grade')),
                data_get($metadata, 'class_grade'),
            ],
            'subject' => [
                $identity['subject'] ?? null,
                $identity['subject_label'] ?? null,
                data_get($metadata, 'subject'),
                data_get($metadata, 'course'),
                data_get($metadata, 'topic'),
                data_get($metadata, 'paper'),
            ],
            default => [],
        };

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeSlugValue($candidate);

            if ($normalized !== null && $normalized === $requested) {
                return true;
            }
        }

        return false;
    }

    private function normalizeRequestSelectionValue(?string $value): ?string
    {
        $normalized = $this->normalizeSlugValue($value);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (ctype_digit($normalized)) {
            $categorySlug = CourseCategory::query()
                ->whereKey((int) $normalized)
                ->value('slug');

            if (filled($categorySlug)) {
                return $this->normalizeSlugValue($categorySlug);
            }
        }

        return $normalized;
    }

    private function inferMainCategoryFromEducationLevel(?string $educationLevel): ?string
    {
        $value = $this->normalizeText($educationLevel);

        if ($value === '') {
            return null;
        }

        return match (true) {
            str_contains($value, 'pre primary') => 'pre-primary',
            str_contains($value, 'lower primary') => 'lower-primary',
            str_contains($value, 'upper primary') => 'upper-primary',
            str_contains($value, 'junior school') => 'junior-school',
            str_contains($value, 'senior school') => 'senior-school-cbc',
            str_contains($value, 'high school') => 'high-school',
            str_contains($value, 'tvet') => 'tvet',
            str_contains($value, 'certificate courses') => 'certificate-courses',
            str_contains($value, 'diploma courses') => 'diploma-courses',
            str_contains($value, 'undergraduate') || str_contains($value, 'university') => 'undergraduate',
            str_contains($value, 'professional courses') => 'professional-courses',
            str_contains($value, 'teacher resources') => 'teacher-resources',
            default => $this->normalizeSlugValue($educationLevel),
        };
    }

    private function inferCategoryFromClassGrade(?string $classGrade): ?string
    {
        return $this->normalizeCategoryLabel($classGrade);
    }

    private function valueFrom(array $values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function normalizeSlugValue(?string $value): ?string
    {
        $normalized = $this->normalizeText($value);

        if ($normalized === '') {
            return null;
        }

        return Str::slug($normalized, '-');
    }

    private function normalizeTypeValue(?string $value): ?string
    {
        $normalized = $this->normalizeText($value);

        if ($normalized === '') {
            return null;
        }

        return preg_replace('/[\s-]+/', '_', Str::lower($normalized)) ?: null;
    }

    private function normalizeCategoryLabel(?string $value): ?string
    {
        $normalized = $this->normalizeText($value);

        if ($normalized === '') {
            return null;
        }

        $stripped = preg_replace('/^school of\s+/i', '', $normalized) ?? $normalized;

        return $this->normalizeSlugValue($stripped);
    }

    private function normalizeText(?string $value): string
    {
        return preg_replace('/\s+/', ' ', trim((string) $value)) ?? '';
    }
}
