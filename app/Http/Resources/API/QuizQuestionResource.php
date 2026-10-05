<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $title = data_get($this->resource, 'title');
        $type = data_get($this->resource, 'type');
        $answers = data_get($this->resource, 'answers', []);

        return [
            'id'    => (int) $id,
            'title' => (string) $title,
            'type' => (string) $type,
            'answers' => QuizQuestionAnswerResource::collection(collect($answers)),
        ];
    }
}
