<?php

namespace App\Http\Resources\API;

use App\Services\ProductIdentityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $identity = app(ProductIdentityService::class)->identityForProduct($this->resource);
        $user = auth('sanctum')->user();
        $subscriptionActive = $user && hasActiveSubscription($user);
        $purchased = (bool) ($this->getAttribute('is_purchased') ?? false);
        $free = (float) $this->effective_price === 0.0;
        $accessSource = $subscriptionActive ? 'subscription' : ($purchased ? 'purchase' : ($free ? 'free' : null));

        return [
            'id' => (int) $this->id,
            'slug' => (string) $this->slug,
            'type' => (string) $this->type,
            'type_label' => (string) $this->type_label,
            'title' => (string) $this->title,
            'thumbnail' => $this->thumbnail,
            'description' => $this->when(
                array_key_exists('description', $this->resource->getAttributes()),
                fn () => $this->description
            ),
            'price' => (float) $this->price,
            'discount' => $this->discount !== null ? (float) $this->discount : null,
            'effective_price' => (float) $this->effective_price,
            'is_purchased' => $purchased,
            'has_access' => $accessSource !== null,
            'access_source' => $accessSource,
            'payment_required' => $accessSource === null,
            'educational_level' => $identity['education_level'] ?? $identity['main_category_label'] ?? null,
            'class_grade' => $identity['class_grade'] ?? $identity['category_label'] ?? null,
            'catalog_main_category' => $identity['main_category'] ?? null,
            'catalog_main_category_label' => $identity['main_category_label'] ?? null,
            'catalog_category' => $identity['category'] ?? null,
            'catalog_category_label' => $identity['category_label'] ?? null,
            'catalog_subject' => $identity['subject'] ?? null,
            'catalog_subject_label' => $identity['subject_label'] ?? null,
            'catalog_identity' => [
                'main_category' => $identity['main_category'] ?? null,
                'main_category_label' => $identity['main_category_label'] ?? null,
                'category' => $identity['category'] ?? null,
                'category_label' => $identity['category_label'] ?? null,
                'subject' => $identity['subject'] ?? null,
                'subject_label' => $identity['subject_label'] ?? null,
            ],
            'category' => $this->categoryPath(),
            'access_label' => (string) $this->access_label,
            'instructor' => $this->whenLoaded('instructor', fn () => [
                'name' => $this->instructor->name,
                'image' => $this->instructor->image,
            ]),
        ];
    }

    private function categoryPath(): ?array
    {
        $category = $this->relationLoaded('category') ? $this->getRelation('category') : null;

        if (! $category || ! $category->exists) {
            return null;
        }

        $parent = $category->relationLoaded('parentCategory')
            ? $category->getRelation('parentCategory')
            : null;
        $grandparent = $parent && $parent->relationLoaded('parentCategory')
            ? $parent->getRelation('parentCategory')
            : null;

        return [
            'slug' => (string) $category->slug,
            'name' => (string) $category->name,
            'parent' => $parent && $parent->exists ? [
                'slug' => (string) $parent->slug,
                'name' => (string) $parent->name,
                'grandparent' => $grandparent && $grandparent->exists ? [
                    'slug' => (string) $grandparent->slug,
                    'name' => (string) $grandparent->name,
                ] : null,
            ] : null,
        ];
    }
}
