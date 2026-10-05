<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class LessonAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
                now()->addMinutes(30),
                ['resource' => $this->id]
            ),
        ];
    }

    private function formattedSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }
}
