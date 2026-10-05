<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CourseLessonResource;
use App\Services\Ai\BunnyDocumentStorageService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonResourceDownloadController extends Controller
{
    public function __invoke(
        CourseLessonResource $resource,
        BunnyDocumentStorageService $storage
    ): BinaryFileResponse {
        abort_unless($resource->status === 'active', 404);

        $temporaryPath = $storage->downloadToTemporaryPath($resource->file_path);

        return response()
            ->download($temporaryPath, $resource->name, [
                'Content-Type' => $resource->mime_type ?: 'application/octet-stream',
            ])
            ->deleteFileAfterSend(true);
    }
}
