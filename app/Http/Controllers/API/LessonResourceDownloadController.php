<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CourseLessonResource;
use App\Services\Ai\BunnyDocumentStorageService;
use App\Services\SabbathDownloadTagService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonResourceDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        CourseLessonResource $resource,
        BunnyDocumentStorageService $storage,
        SabbathDownloadTagService $tags
    ): BinaryFileResponse {
        abort_unless($resource->status === 'active', 404);

        $headers = ['Content-Type' => $resource->mime_type ?: 'application/octet-stream'];
        if ($request->filled('sabbath_tag')) {
            $tag = (string) $request->query('sabbath_tag');
            $payload = $tags->validate($tag, 'lesson_attachment', $resource->id);
            abort_unless($payload !== null, 403);
            $headers = array_merge($headers, $tags->responseHeaders([
                'temporary' => true,
                'tag' => $tag,
                'delete_after' => Carbon::createFromTimestampUTC((int) $payload['delete_after'])->toIso8601String(),
            ]));
        }

        $temporaryPath = $storage->downloadToTemporaryPath($resource->file_path);

        return response()
            ->download($temporaryPath, $resource->name, $headers)
            ->deleteFileAfterSend(true);
    }
}
