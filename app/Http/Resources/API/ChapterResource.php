<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use App\Http\Resources\API\ChapterItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $title = data_get($this->resource, 'title');
        $chapterItems = data_get($this->resource, 'chapterItems', []);

        return [
            'id'        => (int) $id,
            'title'     => (string) $title,
            'chapters'  => ChapterItemResource::collection(collect($chapterItems)),
        ];
    }
}
