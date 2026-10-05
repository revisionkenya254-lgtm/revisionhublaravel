<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $question = data_get($this->resource, 'translations.0.question');
        $answer = data_get($this->resource, 'translations.0.answer');

        return [
            'question' => (string) $question,
            'answer'   => (string) $answer,
        ];
    }
}
