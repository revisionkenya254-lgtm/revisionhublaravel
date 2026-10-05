<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseReviewsResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $rating = data_get($this->resource, 'rating');
        $review = data_get($this->resource, 'review');
        $userName = data_get($this->resource, 'user.name');
        $userImage = data_get($this->resource, 'user.image');

        return [
            'rating' => (float) $rating,
            'review' => (string) $review,
            'name'   => (string) $userName,
            'avatar' => (string) $userImage,
        ];
    }
}
