<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolledCourseResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $course = data_get($this->resource, 'course');

        return [
            'slug'       => (string) data_get($course, 'slug'),
            'title'      => (string) data_get($course, 'title'),
            'thumbnail'  => (string) data_get($course, 'thumbnail'),
            'instructor' => data_get($course, 'instructor') ? new InstructorResource(data_get($course, 'instructor')) : null,
            'students'   => (int) data_get($course, 'enrollments_count', 0),
            'progress'   => (int) data_get($course, 'completed_percent', 0),
            'access_source' => (string) ($this->getAttribute('access_source') ?? 'purchase'),
        ];
    }
}
