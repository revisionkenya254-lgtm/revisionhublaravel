<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomPageResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $slug = data_get($this->resource, 'slug');
        $name = data_get($this->resource, 'translations.0.name');
        $content = data_get($this->resource, 'translations.0.content');

        return [
            'slug'    => (string) $slug,
            'name'    => (string) $name,
            'content' => (string) $content,
        ];
    }
}
