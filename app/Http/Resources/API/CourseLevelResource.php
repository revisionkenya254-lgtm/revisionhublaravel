<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseLevelResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $slug = data_get($this->resource, 'slug');
        $translations = data_get($this->resource, 'translations', []);
        $name = data_get($translations, '0.name', '');

        return [
            'slug' => (string) $slug,
            'name' => (string) $name,
        ];
    }
}
