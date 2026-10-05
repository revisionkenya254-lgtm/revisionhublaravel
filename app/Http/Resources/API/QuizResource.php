<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $title = data_get($this->resource, 'title');
        $time = data_get($this->resource, 'time');
        $attempt = data_get($this->resource, 'attempt');
        $passMark = data_get($this->resource, 'pass_mark');
        $totalMark = data_get($this->resource, 'total_mark');
        $questionsCount = data_get($this->resource, 'questions_count');
        $questions = data_get($this->resource, 'questions', []);

        $data = [
            'id'    => (int) $id,
            'title' => (string) $title,
        ];
        if ($request->routeIs('api.get-file-info')) {
            $data['time'] = (int) $time;
            $data['attempt'] = (int) $attempt;
            $data['pass_mark'] = (int) $passMark;
            $data['total_mark'] = (int) $totalMark;
            $data['total_questions'] = (int) $questionsCount;
        }

        if ($request->routeIs('api.quiz-index')) {
            $data['time'] = (int) $time;
            $data['attempt'] = (int) $attempt;
            $data['pass_mark'] = (int) $passMark;
            $data['total_mark'] = (int) $totalMark;
            $data['total_questions'] = (int) $questionsCount;
            $data['questions'] = QuizQuestionResource::collection(collect($questions));
        }
        return $data;
    }
}
