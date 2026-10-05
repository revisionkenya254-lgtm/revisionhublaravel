<?php

namespace App\Services\Storage;

use App\Services\Ai\BunnyDocumentStorageService;
use Illuminate\Http\UploadedFile;

class CourseMediaStorageService
{
    public function __construct(
        private readonly BunnyDocumentStorageService $documentStorage,
        private readonly BunnyStreamService $streamStorage
    ) {
    }

    public function storeThumbnail(UploadedFile $file, string $actorRole, int $actorId, string $productType = 'course', ?int $productId = null): array
    {
        return $this->documentStorage->uploadAsset($file, [
            'actor_role' => $actorRole,
            'actor_id' => $actorId,
            'product_type' => $productType,
            'product_id' => $productId,
            'asset_kind' => 'thumbnail',
        ]);
    }

    public function storeDemoVideo(UploadedFile $file, string $title, string $actorRole, int $actorId, string $productType = 'course', ?int $productId = null): array
    {
        return $this->streamStorage->uploadVideo($file, $title, [
            'actor_role' => $actorRole,
            'actor_id' => $actorId,
            'product_type' => $productType,
            'product_id' => $productId,
            'asset_kind' => 'video',
        ]);
    }

    public function storeLessonVideo(UploadedFile $file, string $title, string $actorRole, int $actorId, int $courseId, ?int $lessonId = null): array
    {
        return $this->streamStorage->uploadVideo($file, $title, [
            'actor_role' => $actorRole,
            'actor_id' => $actorId,
            'product_type' => 'course',
            'product_id' => $courseId,
            'lesson_id' => $lessonId,
            'asset_kind' => 'lesson_video',
        ]);
    }

    /** Store course notes separately from other course media in Bunny storage. */
    public function storeNoteAttachment(UploadedFile $file, string $actorRole, int $actorId, int $courseId): array
    {
        return $this->documentStorage->uploadAsset($file, [
            'actor_role' => $actorRole,
            'actor_id' => $actorId,
            'product_type' => 'notes',
            'product_id' => $courseId,
            'asset_kind' => 'source',
        ]);
    }

    public function storeLessonResource(UploadedFile $file, string $actorRole, int $actorId, int $courseId): array
    {
        return $this->documentStorage->uploadAsset($file, [
            'actor_role' => $actorRole,
            'actor_id' => $actorId,
            'product_type' => 'course',
            'product_id' => $courseId,
            'asset_kind' => 'attachments',
        ]);
    }

    public function deleteThumbnail(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        return $this->documentStorage->deletePath($path);
    }

    public function deleteDocument(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        return $this->documentStorage->deletePath($path);
    }

    public function deleteDemoVideo(?string $videoId): bool
    {
        if (blank($videoId)) {
            return false;
        }

        return $this->streamStorage->deleteVideo($videoId);
    }

    public function deleteLessonVideo(?string $videoId): bool
    {
        if (blank($videoId)) {
            return false;
        }

        return $this->streamStorage->deleteVideo($videoId);
    }
}
