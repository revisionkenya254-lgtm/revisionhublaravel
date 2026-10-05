<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionAnswerResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $title = data_get($this->resource, 'title');
        $correct = data_get($this->resource, 'correct');

        return [
            'id'      => (int) $id,
            'title'   => (string) $title,
            'correct' => (bool) $correct,
        ];
    }
}
