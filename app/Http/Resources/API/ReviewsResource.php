<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewsResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $courseTitle = data_get($this->resource, 'course.title');
        $rating = data_get($this->resource, 'rating');
        $review = data_get($this->resource, 'review');
        $createdAt = data_get($this->resource, 'created_at');
        $status = data_get($this->resource, 'status');

        return [
            'id'           => (int) $id,
            'course_title' => (string) $courseTitle,
            'rating'       => (int) $rating,
            'review'       => (string) $review,
            'created_at'   => (string) formatDate($createdAt),
            'status'       => (bool) $status,
        ];
    }
}
