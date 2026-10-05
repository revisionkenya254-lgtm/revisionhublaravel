<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $title = data_get($this->resource, 'title');
        $announcement = data_get($this->resource, 'announcement');
        $createdAt = data_get($this->resource, 'created_at');
        $instructor = data_get($this->resource, 'instructor');

        return [
            'title'        => (string) $title,
            'announcement' => (string) $announcement,
            'created_at'   => (string) formatDate($createdAt),
            'instructor'   => $instructor ? new InstructorResource($instructor) : null,
        ];
    }
}
