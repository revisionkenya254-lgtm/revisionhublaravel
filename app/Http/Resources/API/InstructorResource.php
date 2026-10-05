<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $name = data_get($this->resource, 'name');
        $image = data_get($this->resource, 'image');

        return [
            'id'   => (int) $id,
            'name' => (string) $name,
            'image' => (string) $image,
        ];
    }
}
