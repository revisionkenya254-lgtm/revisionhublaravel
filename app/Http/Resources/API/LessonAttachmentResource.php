<?php

namespace App\Http\Resources\API;

use App\Services\SabbathDownloadTagService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class LessonAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tagService = app(SabbathDownloadTagService::class);
        $policy = data_get($this->resource, 'download_access_source') === 'sabbath'
            && $request->user()
            ? $tagService->temporaryPolicy($request->user(), 'lesson_attachment', (int) $this->id)
            : $tagService->permanentPolicy();
        $expiresAt = now()->addMinutes(30);
        $parameters = ['resource' => $this->id];

        if ($policy['temporary']) {
            $expiresAt = $expiresAt->min(Carbon::parse($policy['delete_after']));
            $parameters['sabbath_tag'] = $policy['tag'];
        }

        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'filename' => (string) $this->name,
            'file_size' => (int) $this->file_size,
            'size' => $this->formattedSize((int) $this->file_size),
            'file_type' => strtoupper((string) ($this->extension ?: 'FILE')),
            'mime_type' => (string) $this->mime_type,
            'download_url' => URL::temporarySignedRoute(
                'api.lesson-resources.download',
                $expiresAt,
                $parameters
            ),
            'sabbath_download' => $policy['sabbath_download'],
            'sabbath_download_tag' => $policy['tag'],
            'delete_after' => $policy['delete_after'],
            'download_policy' => $policy,
        ];
    }

    private function formattedSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 1).' MB';
    }
}
