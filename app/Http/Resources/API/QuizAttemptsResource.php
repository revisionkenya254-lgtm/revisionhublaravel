<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptsResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $quiz = data_get($this->resource, 'quiz');
        $userGrade = data_get($this->resource, 'user_grade');
        $status = data_get($this->resource, 'status');
        $createdAt = data_get($this->resource, 'created_at');
        $result = data_get($this->resource, 'result');

        return [
            'id'           => (int) $id,
            'course_title' => (string) data_get($quiz, 'course.title'),
            'quiz'         => (string) data_get($quiz, 'title'),
            'attempt'      => (int) data_get($quiz, 'attempt'),
            'total_marks'  => (int) data_get($quiz, 'total_mark'),
            'pass_marks'   => (int) data_get($quiz, 'pass_mark'),
            'your_marks'   => (int) $userGrade,
            'status'       => (string) $status,
            'created_at'   => (string) formatDate($createdAt),
            'results'      => new QuizResultResource($result),
        ];
    }
}
