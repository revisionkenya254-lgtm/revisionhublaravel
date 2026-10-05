<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialLinkResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $link = data_get($this->resource, 'link');
        $icon = data_get($this->resource, 'icon');

        return [
            'link' => (string) $link,
            'icon' => (string) $icon,
        ];
    }
}
