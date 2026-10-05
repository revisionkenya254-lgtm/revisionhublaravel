<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resource->product;

        return [
            'id' => (int) $product->id,
            'slug' => (string) $product->slug,
            'type' => (string) $product->type,
            'type_label' => (string) $product->type_label,
            'title' => (string) $product->title,
            'thumbnail' => $product->thumbnail,
            'description' => $product->description,
            'access_source' => $this->resource->access_source,
            'access_label' => (string) $product->access_label,
            'progress_percent' => (float) $this->resource->progress_percent,
            'progress_label' => $this->resource->progress_label,
            'last_activity_at' => $this->resource->last_activity_at?->toISOString(),
            'category' => $product->category?->translation?->name ?? $product->category?->name,
        ];
    }
}
