<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $code = data_get($this->resource, 'code');
        $name = data_get($this->resource, 'name');
        $direction = data_get($this->resource, 'direction');
        $isDefault = data_get($this->resource, 'is_default');
        $status = data_get($this->resource, 'status');

        return [
            'code'       => (string) $code,
            'name'       => (string) $name,
            'direction'  => (string) $direction,
            'is_default' => (bool) $isDefault,
            'status'     => (bool) $status,
        ];
    }
}
