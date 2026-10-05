<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $title = data_get($this->resource, 'title');
        $description = data_get($this->resource, 'description');
        $filePath = data_get($this->resource, 'file_path');
        $storage = data_get($this->resource, 'storage');
        $fileType = data_get($this->resource, 'file_type');
        $duration = data_get($this->resource, 'duration');
        $downloadable = data_get($this->resource, 'downloadable');
        $isFree = data_get($this->resource, 'is_free');
        $lectureNumber = data_get($this->resource, 'lecture_number');
        $includeInCurriculum = data_get($this->resource, 'include_in_curriculum', true);
        $qnaEnabled = data_get($this->resource, 'qna_enabled', true);
        $isDetailedLessonRequest = $request->routeIs('api.get-file-info')
            || $request->routeIs('api.free-lesson')
            || $request->route('lesson_id') !== null;

        if ($isDetailedLessonRequest) {
            return [
                'id'              => (int) $id,
                'lecture_number'  => (string) $lectureNumber,
                'title'           => (string) $title,
                'description'     => (string) $description,
                'overview'        => (string) $description,
                'file_path'       => (string) generateVideoEmbedUrl($filePath, $storage, $fileType),
                'video_url'       => (string) generateVideoEmbedUrl($filePath, $storage, $fileType),
                'storage'         => (string) $storage,
                'file_type'       => (string) $fileType,
                'duration'        => (string) convertMinutesToHoursAndMinutes($duration),
                'is_downloadable' => (bool) $downloadable,
                'is_free'         => (bool) $isFree,
                'include_in_curriculum' => (bool) $includeInCurriculum,
                'resources'       => LessonAttachmentResource::collection($this->whenLoaded('resources')),
                'qna'             => [
                    'enabled' => (bool) $qnaEnabled,
                    'allow_questions' => (bool) data_get($this->resource, 'qna_allow_questions', true),
                    'allow_replies' => (bool) data_get($this->resource, 'qna_allow_replies', true),
                    'instructions' => (string) data_get($this->resource, 'qna_instructions'),
                ],
            ];
        }
        return [
            'id'                    => (int) $id,
            'lecture_number'        => (string) $lectureNumber,
            'title'                 => (string) $title,
            'file_type'             => (string) $fileType,
            'duration'              => (string) convertMinutesToHoursAndMinutes($duration),
            'is_free'               => (bool) $isFree,
            'include_in_curriculum' => (bool) $includeInCurriculum,
            'qna_enabled'           => (bool) $qnaEnabled,
        ];
    }
}
