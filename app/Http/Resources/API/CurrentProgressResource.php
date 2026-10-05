<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentProgressResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $type = data_get($this->resource, 'type');
        $chapterId = data_get($this->resource, 'chapter_id');
        $lessonId = data_get($this->resource, 'lesson_id');
        $watched = data_get($this->resource, 'watched');
        $current = data_get($this->resource, 'current');

        return [
            'type'       => (string) $type,
            'chapter_id' => (int) $chapterId,
            'lesson_id'  => (int) $lessonId,
            'watched'    => (int) $watched,
            'current'    => (int) $current,
        ];
    }
}
