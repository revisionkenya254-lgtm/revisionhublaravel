<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseListResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $currency = strtoupper($request->query('currency', getSessionCurrency()));
        $slug = data_get($this->resource, 'slug');
        $title = data_get($this->resource, 'title');
        $thumbnail = data_get($this->resource, 'thumbnail');
        $priceValue = data_get($this->resource, 'price', 0);
        $discountValue = data_get($this->resource, 'discount', 0);
        $instructor = data_get($this->resource, 'instructor');
        $students = data_get($this->resource, 'enrollments_count', 0);
        $averageRating = data_get($this->resource, 'average_rating', 0);
        $price = $priceValue == 0 ? (int) $priceValue : (string) apiCurrency($priceValue, $currency);
        $discount = $discountValue == 0 ? (int) $discountValue : (string) apiCurrency($discountValue, $currency);

        return [
            'slug'           => (string) $slug,
            'title'          => (string) $title,
            'thumbnail'      => (string) $thumbnail,
            'price'          => $price,
            'discount'       => $discount,
            'instructor'     => $instructor ? new InstructorResource($instructor) : null,
            'students'       => (int) $students,
            'average_rating' => (float) $averageRating,
        ];
    }
}
