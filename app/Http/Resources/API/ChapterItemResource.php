<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterItemResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $type = data_get($this->resource, 'type');
        $lesson = data_get($this->resource, 'lesson');
        $quiz = data_get($this->resource, 'quiz');
        $data = ['type' => (string) $type];

        if (in_array($type, ['lesson', 'document', 'live'], true)) {
            $data['item'] = $lesson ? new LessonResource($lesson) : [];
        } elseif ($type === 'quiz') {
            $data['item'] = $quiz ? new QuizResource($quiz) : [];
        }
        return $data;
    }
}
