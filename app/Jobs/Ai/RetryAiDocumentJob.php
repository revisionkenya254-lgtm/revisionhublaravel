<?php

namespace App\Jobs\Ai;

use App\Models\AiDocument;
use App\Services\Ai\AiDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryAiDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $documentId)
    {
    }

    public function handle(AiDocumentService $documentService): void
    {
        $document = AiDocument::find($this->documentId);

        if (! $document) {
            return;
        }

        $documentService->reprocess($document);
    }
}
