<?php

namespace App\Services\Ai;

use App\Jobs\Ai\ProcessAiDocumentJob;
use App\Jobs\Ai\RetryAiDocumentJob;
use App\Models\AiDocument;
use App\Models\AiDocumentChunk;
use App\Models\AiDocumentQuestion;
use App\Models\AiDocumentQuestionRegion;
use App\Models\AiDocumentProcessingLog;
use App\Models\User;
use App\Notifications\AiDocumentProcessedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiDocumentService
{
    public function __construct(
        private readonly BunnyDocumentStorageService $storageService
    ) {
    }

    public function storeUploadedDocument(UploadedFile $file, User|int $instructor, array $context = [], string $folder = 'ai-documents'): AiDocument
    {
        $instructorId = $instructor instanceof User ? $instructor->id : (int) $instructor;
        $upload = isset($context['product_id']) || isset($context['product_type'])
            ? $this->storageService->uploadAsset($file, array_merge([
                'actor_role' => 'instructor',
                'actor_id' => $instructorId,
                'product_type' => $context['product_type'] ?? 'ai-documents',
                'product_id' => $context['product_id'] ?? null,
                'asset_kind' => $context['asset_kind'] ?? $folder,
            ], $context))
            : $this->storageService->uploadInstructorFile($file, $instructorId, $folder);

        return $this->registerUpload($instructorId, $upload, $context);
    }

    public function registerUpload(int $instructorId, array $upload, array $context = [], bool $dispatchProcessing = true): AiDocument
    {
        $document = AiDocument::create([
            'instructor_id' => $instructorId,
            'product_id' => $context['product_id'] ?? null,
            'product_note_id' => $context['product_note_id'] ?? null,
            'source_type' => $context['source_type'] ?? 'manual_upload',
            'source_name' => $context['source_name'] ?? ($upload['original_name'] ?? 'Document'),
            'original_path' => $upload['path'],
            'storage_disk' => 'bunny',
            'bunny_folder_path' => $upload['folder_path'] ?? null,
            'mime_type' => $upload['mime_type'] ?? null,
            'file_extension' => $upload['extension'] ?? null,
            'file_hash' => $upload['hash'] ?? hash('sha256', $upload['path']),
            'status' => 'pending',
            'progress' => 0,
            'metadata' => array_filter(array_merge(
                $context['metadata'] ?? [],
                [
                    'product_type' => $context['product_type'] ?? null,
                    'asset_kind' => $context['asset_kind'] ?? null,
                    'public_url' => $upload['url'] ?? null,
                    'original_name' => $upload['original_name'] ?? null,
                    'size' => $upload['size'] ?? null,
                ]
            )),
        ]);

        $this->addLog($document, 'upload', $dispatchProcessing
            ? __('Document registered for processing.')
            : __('Document registered and waiting for admin review.'), [
            'path' => $upload['path'] ?? null,
            'source_type' => $document->source_type,
            'auto_process' => $dispatchProcessing,
        ]);

        if ($dispatchProcessing) {
            $this->dispatchProcessing($document, $context['queue_mode'] ?? null);
        }

        return $document->fresh(['chunks', 'processingLogs']);
    }

    public function reprocess(AiDocument $document, ?string $queueMode = null, array $context = []): AiDocument
    {
        $document->update([
            'status' => 'pending',
            'progress' => 0,
            'failed_at' => null,
            'failure_reason' => null,
            'processed_at' => null,
            'metadata' => array_filter(array_merge($document->metadata ?? [], $context['metadata'] ?? [])),
        ]);

        AiDocumentChunk::where('ai_document_id', $document->id)->delete();
        AiDocumentQuestion::where('ai_document_id', $document->id)->delete();
        AiDocumentQuestionRegion::where('ai_document_id', $document->id)->delete();
        AiDocumentProcessingLog::where('ai_document_id', $document->id)->delete();

        $this->addLog($document, 'reprocess', $context['message'] ?? __('Document reprocessing has been queued.'), [
            'review_mode' => $context['review_mode'] ?? null,
        ]);
        $this->dispatchProcessing($document, $queueMode);

        return $document->fresh(['chunks', 'processingLogs']);
    }

    public function deleteDocument(AiDocument $document): void
    {
        DB::transaction(function () use ($document) {
            $this->storageService->deletePath($document->original_path);

            if ($document->extracted_text_path && Str::startsWith($document->extracted_text_path, ['instructors/', 'admins/'])) {
                $this->storageService->deletePath($document->extracted_text_path);
            }

            AiDocumentChunk::where('ai_document_id', $document->id)->delete();
            AiDocumentQuestion::where('ai_document_id', $document->id)->delete();
            AiDocumentQuestionRegion::where('ai_document_id', $document->id)->delete();
            AiDocumentProcessingLog::where('ai_document_id', $document->id)->delete();
            $document->delete();
        });
    }

    public function markFailed(AiDocument $document, string $reason): void
    {
        $document->update([
            'status' => 'failed',
            'progress' => 100,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        $this->addLog($document, 'failed', $reason);
    }

    public function markRequiresOcr(AiDocument $document, string $reason): void
    {
        $document->update([
            'status' => 'requires_ocr',
            'progress' => 100,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        $this->addLog($document, 'requires_ocr', $reason);
    }

    public function markProcessed(AiDocument $document, array $payload): void
    {
        $document->update([
            'status' => 'processed',
            'progress' => 100,
            'page_count' => $payload['page_count'] ?? $document->page_count,
            'character_count' => $payload['character_count'] ?? $document->character_count,
            'extracted_text_excerpt' => $payload['excerpt'] ?? $document->extracted_text_excerpt,
            'processed_at' => now(),
            'failure_reason' => null,
            'failed_at' => null,
            'metadata' => array_filter(array_merge($document->metadata ?? [], $payload['metadata'] ?? [])),
        ]);

        $this->addLog($document, 'processed', __('Document processed successfully.'), $payload);

        try {
            $document->loadMissing('instructor');
            $document->instructor?->notify(new AiDocumentProcessedNotification($document));
        } catch (\Throwable $throwable) {
            $this->addLog($document, 'notification_failed', $throwable->getMessage());
        }
    }

    public function updateProgress(AiDocument $document, int $progress, ?string $stage = null, ?string $message = null, array $context = []): void
    {
        $progress = max(0, min(100, $progress));

        $document->update([
            'progress' => $progress,
        ]);

        if ($stage || $message) {
            $this->addLog(
                $document,
                $stage ?: 'progress',
                $message ?: __('Document processing progress updated.'),
                array_merge($context, ['progress' => $progress])
            );
        }
    }

    public function saveChunks(AiDocument $document, array $chunks): void
    {
        AiDocumentChunk::where('ai_document_id', $document->id)->delete();

        foreach ($chunks as $chunk) {
            $document->chunks()->create($chunk);
        }
    }

    public function saveQuestions(AiDocument $document, array $questions): void
    {
        AiDocumentQuestion::where('ai_document_id', $document->id)->delete();

        foreach ($questions as $question) {
            $document->questions()->create($question);
        }
    }

    public function dispatchProcessing(AiDocument $document, ?string $queueMode = null): void
    {
        $queueConnection = $this->resolveProcessingQueueConnection($queueMode);

        if ($this->resolveProcessingQueueMode($queueMode) === 'local') {
            ProcessAiDocumentJob::dispatchSync($document->id);
            return;
        }

        ProcessAiDocumentJob::dispatch($document->id)
            ->onConnection($queueConnection)
            ->onQueue($this->processingQueueName());
    }

    public function dispatchRetry(AiDocument $document): void
    {
        RetryAiDocumentJob::dispatch($document->id);
    }

    public function addLog(AiDocument $document, string $stage, string $message, array $context = []): void
    {
        $document->processingLogs()->create([
            'stage' => $stage,
            'message' => $message,
            'context' => $context ?: null,
        ]);
    }

    public function resolveProcessingQueueMode(?string $queueMode = null): string
    {
        $mode = strtolower(trim((string) ($queueMode ?: config('ai.document_processing.default_mode', 'local'))));

        return in_array($mode, ['local', 'redis'], true) ? $mode : 'local';
    }

    public function resolveProcessingQueueConnection(?string $queueMode = null): string
    {
        $mode = $this->resolveProcessingQueueMode($queueMode);

        return match ($mode) {
            'redis' => (string) config('ai.document_processing.redis_connection', 'redis'),
            default => 'sync',
        };
    }

    public function processingQueueName(): string
    {
        return (string) config('ai.document_processing.queue_name', 'ai-processing');
    }
}
