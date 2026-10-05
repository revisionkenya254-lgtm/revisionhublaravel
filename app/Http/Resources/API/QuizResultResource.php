<?php

namespace App\Http\Resources\API;

use App\Models\QuizQuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResultResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $result = $this->resource;
        if (is_string($result)) {
            $result = json_decode($result, true);
        }
        $result = is_array($result) ? $result : [];

        return array_map(function ($data) {
            $answer = isset($data['answer']) ? QuizQuestionAnswer::select('id', 'question_id', 'title')
                ->with('question:id,title')
                ->find($data['answer']) : null;

            return [
                'question' => $answer && $answer?->question ? (string) $answer?->question?->title : 'N/A',
                'answer'   => $answer ? (string) $answer?->title : 'N/A',
                'correct'  => (bool) $data['correct'],
            ];
        }, $result);
    }
}
