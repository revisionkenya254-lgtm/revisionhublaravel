<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QnaResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $questionTitle = data_get($this->resource, 'question_title');
        $questionDescription = data_get($this->resource, 'question_description');
        $seen = data_get($this->resource, 'seen');
        $createdAt = data_get($this->resource, 'created_at');
        $user = data_get($this->resource, 'user');
        $replies = data_get($this->resource, 'replies', []);
        $repliesCount = data_get($this->resource, 'replies_count', 0);

        if($request->routeIs('api.questions-create')){
            return [
                'id'            => (int) $id,
                'question'      => (string) $questionTitle,
                'description'   => (string) $questionDescription,
                'seen'          => (bool) $seen,
                'created_at'   => (string) formattedDateTime($createdAt),
                'user'          => $user ? new InstructorResource($user) : null,
            ];
        }
        return [
            'id'            => (int) $id,
            'question'      => (string) $questionTitle,
            'description'   => (string) $questionDescription,
            'replies_count' => (int) $repliesCount,
            'seen'          => (bool) $seen,
            'created_at'   => (string) formattedDateTime($createdAt),
            'user'          => $user ? new InstructorResource($user) : null,
            'replies'       => QnaReplyResource::collection(collect($replies)),
        ];
    }
}
