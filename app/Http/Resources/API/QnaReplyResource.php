<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QnaReplyResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $id = data_get($this->resource, 'id');
        $questionId = data_get($this->resource, 'question_id');
        $reply = data_get($this->resource, 'reply');
        $isAi = data_get($this->resource, 'is_ai');
        $aiProvider = data_get($this->resource, 'ai_provider', '');
        $aiModel = data_get($this->resource, 'ai_model', '');
        $createdAt = data_get($this->resource, 'created_at');
        $user = data_get($this->resource, 'user');
        $retrieval = (array) data_get($this->resource, 'metadata.retrieval', []);
        $citations = collect(data_get($retrieval, 'results', []))
            ->take(3)
            ->map(function (array $result): array {
                $document = $result['document'] ?? [];

                return [
                    'source_name' => (string) ($document['source_name'] ?? __('Unknown source')),
                    'source_type' => (string) ($document['source_type'] ?? ''),
                    'page_start' => isset($result['page_start']) ? (int) $result['page_start'] : null,
                    'page_end' => isset($result['page_end']) ? (int) $result['page_end'] : null,
                    'chunk_index' => isset($result['chunk_index']) ? (int) $result['chunk_index'] : null,
                    'score' => isset($result['score']) ? (int) $result['score'] : 0,
                    'snippet' => (string) ($result['snippet'] ?? ''),
                ];
            })
            ->values()
            ->all();

        return [
            'id'          => (int) $id,
            'question_id' => (int) $questionId,
            'reply'       => (string) $reply,
            'is_ai'       => (bool) $isAi,
            'ai_provider' => (string) $aiProvider,
            'ai_model'    => (string) $aiModel,
            'retrieval'   => [
                'used' => (bool) data_get($retrieval, 'used', false),
                'threshold' => (int) data_get($retrieval, 'threshold', 0),
                'top_score' => (int) data_get($retrieval, 'top_score', 0),
                'citations' => $citations,
            ],
            'created_at'   => (string) formattedDateTime($createdAt),
            'user'        => $user ? new InstructorResource($user) : null,
        ];
    }
}
