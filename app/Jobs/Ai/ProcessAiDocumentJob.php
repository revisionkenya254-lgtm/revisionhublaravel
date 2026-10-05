<?php

namespace App\Jobs\Ai;

use App\Models\AiDocument;
use App\Services\Ai\AiDocumentChunkingService;
use App\Services\Ai\AiDocumentQuestionExtractionService;
use App\Services\Ai\AiDocumentQuestionRegionService;
use App\Services\Ai\AiDocumentService;
use App\Services\Ai\BunnyDocumentStorageService;
use App\Services\Ai\ImageTranscriptionService;
use App\Services\Ai\LegacyDocRenderingService;
use App\Services\Ai\OcrPdfExtractionService;
use App\Services\Ai\OpenAiDocumentReviewService;
use App\Services\Ai\OpenAiPdfTranscriptionService;
use App\Services\Ai\PdfTextExtractionService;
use App\Services\Ai\WordTextExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessAiDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public readonly int $documentId)
    {
    }

    public function handle(
        BunnyDocumentStorageService $storageService,
        PdfTextExtractionService $extractionService,
        WordTextExtractionService $wordExtractionService,
        ImageTranscriptionService $imageTranscriptionService,
        LegacyDocRenderingService $docRenderingService,
        OcrPdfExtractionService $ocrService,
        OpenAiPdfTranscriptionService $transcriptionService,
        OpenAiDocumentReviewService $documentReviewService,
        AiDocumentChunkingService $chunkingService,
        AiDocumentQuestionExtractionService $questionExtractionService,
        AiDocumentQuestionRegionService $questionRegionService,
        AiDocumentService $documentService
    ): void {
        $document = AiDocument::find($this->documentId);

        if (! $document) {
            return;
        }

        $reviewMode = strtolower(trim((string) data_get($document->metadata, 'review_mode', '')));
        $preferOpenAiReview = $reviewMode === 'openai';
        $result = [
            'embedded_images' => [
                'temp_dir' => null,
                'items' => [],
            ],
        ];
        $document->update(['status' => 'processing']);
        $documentService->updateProgress($document, 8, 'processing', __('Document processing has started.'));
        $documentService->addLog($document, 'processing', __('Document processing has started.'));

        $tempPath = null;
        $renderedImagePayload = [
            'temp_dir' => null,
            'items' => [],
        ];

        try {
            $documentService->updateProgress($document, 18, 'download', __('Downloading source file for processing.'));
            $tempPath = $storageService->downloadToTemporaryPath($document->original_path);
            $extension = strtolower((string) ($document->file_extension ?: pathinfo($document->original_path, PATHINFO_EXTENSION)));
            $isWordDocument = in_array($extension, ['doc', 'docx'], true);

            $documentService->updateProgress($document, 30, 'openai_review', __('Running OpenAI direct file review.'));
            $documentService->addLog($document, 'openai_review', __('Attempting direct OpenAI review from the original file.'), [
                'review_mode' => $reviewMode ?: null,
                'file_extension' => $extension,
            ]);

            $result = [];
            $usedOpenAiDirectReview = false;

            if ($documentReviewService->supports($extension)) {
                try {
                    $openAiDirectReview = $documentReviewService->review($tempPath, basename($document->original_path));

                    if (trim((string) ($openAiDirectReview['text'] ?? '')) !== '' || ! empty($openAiDirectReview['questions'])) {
                        $result = $openAiDirectReview;
                        $usedOpenAiDirectReview = true;
                        $documentService->updateProgress($document, 42, 'openai_review', __('OpenAI direct file review completed.'));
                        $documentService->addLog($document, 'openai_review', __('OpenAI direct file review completed successfully.'), [
                            'method' => $openAiDirectReview['method'] ?? 'openai_direct_review',
                            'question_count' => $openAiDirectReview['question_count'] ?? count($openAiDirectReview['questions'] ?? []),
                        ]);
                    }
                } catch (Throwable $openAiThrowable) {
                    $documentService->addLog($document, 'openai_review_failed', $openAiThrowable->getMessage());
                }
            }

            if (! $usedOpenAiDirectReview) {
                if ($extension === 'pdf') {
                    $documentService->updateProgress($document, 30, 'openai_review', __('Running OpenAI-assisted PDF extraction.'));
                    $documentService->addLog($document, 'openai_review', __('OpenAI-assisted extraction is the preferred PDF path.'), [
                        'review_mode' => $reviewMode ?: null,
                    ]);

                    try {
                        $openAiResult = $this->transcribePdfLikeFile($tempPath, $ocrService, $transcriptionService, true);

                        if (trim((string) ($openAiResult['text'] ?? '')) !== '') {
                            $result = array_merge($result, $openAiResult, [
                                'requires_transcription' => false,
                                'method' => $openAiResult['method'] ?? 'openai_vision',
                            ]);

                            $documentService->updateProgress($document, 42, 'openai_review', __('OpenAI-assisted PDF extraction completed.'));
                            $documentService->addLog($document, 'openai_review', __('OpenAI-assisted PDF extraction completed successfully.'), [
                                'method' => $openAiResult['method'] ?? 'openai_vision',
                            ]);
                        }
                    } catch (Throwable $openAiThrowable) {
                        $documentService->addLog($document, 'openai_review_failed', $openAiThrowable->getMessage());
                    }
                }

                if (trim((string) ($result['text'] ?? '')) === '') {
                    $documentService->updateProgress($document, 30, 'extract', __('Extracting text from the document.'));
                    $result = $isWordDocument
                        ? $wordExtractionService->extractFromPath($tempPath)
                        : $extractionService->extractFromPath($tempPath);

                    if ($preferOpenAiReview && $isWordDocument) {
                        $documentService->updateProgress($document, 42, 'openai_review', __('Running OpenAI-assisted review.'));
                        $documentService->addLog($document, 'openai_review', __('OpenAI-assisted review has been requested for this document.'), [
                            'review_mode' => $reviewMode,
                        ]);

                        $openAiResult = $this->transcribeWordLikeFileForOpenAiReview($result, $imageTranscriptionService, $docRenderingService);

                        if (trim((string) ($openAiResult['text'] ?? '')) !== '') {
                            $result = array_merge($result, $openAiResult, [
                                'requires_transcription' => false,
                                'method' => $openAiResult['method'] ?? 'openai_vision_image',
                            ]);
                        }
                    }
                }
            }

            if ($isWordDocument && ! empty($result['requires_transcription'])) {
                $documentService->updateProgress($document, 48, 'transcription', __('The Word file looks low quality. OpenAI-assisted OCR review may be required.'));
                $embeddedImagePayload = $result['embedded_images'] ?? ['items' => []];
                $embeddedImages = $embeddedImagePayload['items'] ?? [];
                $embeddedImagePaths = array_values(array_filter(array_map(
                    fn (array $image) => $image['path'] ?? null,
                    $embeddedImages
                )));
                $renderedImagePayload = $docRenderingService->renderFromExtractionResult($result, (int) config('services.ai.scanned_pdf_max_pages', 10));
                $renderedImagePaths = array_values(array_filter(array_map(
                    fn (array $item) => $item['path'] ?? null,
                    $renderedImagePayload['items'] ?? []
                )));

                if (! empty($renderedImagePaths)) {
                    $documentService->updateProgress($document, 58, 'render', __('Rendering legacy Word pages for OCR.'));
                    $documentService->addLog($document, 'render', __('Legacy Word pages were rendered for OCR review.'), [
                        'image_count' => count($renderedImagePaths),
                        'render_method' => $renderedImagePayload['method'] ?? null,
                    ]);

                    try {
                        $ocrResult = $imageTranscriptionService->transcribeImagePaths(
                            $renderedImagePaths,
                            (int) config('services.ai.scanned_pdf_max_pages', 10)
                        );

                        if (trim((string) ($ocrResult['text'] ?? '')) !== '') {
                            $result = [
                                'text' => $ocrResult['text'],
                                'excerpt' => mb_substr((string) $ocrResult['text'], 0, 1000),
                                'page_count' => $ocrResult['page_count'] ?? $result['page_count'],
                                'character_count' => $ocrResult['character_count'] ?? mb_strlen((string) $ocrResult['text']),
                                'requires_transcription' => false,
                                'method' => $ocrResult['method'] ?? 'openai_vision_image',
                                'pages' => $ocrResult['pages'] ?? [],
                                'embedded_images' => $embeddedImages,
                            ];
                        }
                    } catch (Throwable $imageThrowable) {
                        $documentService->addLog($document, 'image_ocr_failed', $imageThrowable->getMessage());
                    }
                }

                if (trim((string) ($result['text'] ?? '')) === '' && ! empty($embeddedImagePaths)) {
                    $documentService->addLog($document, 'convert', __('Legacy Word images were extracted for OCR review.'), [
                        'image_count' => count($embeddedImagePaths),
                    ]);

                    try {
                        $ocrResult = $imageTranscriptionService->transcribeImagePaths(
                            $embeddedImagePaths,
                            (int) config('services.ai.scanned_pdf_max_pages', 10)
                        );

                        if (trim((string) ($ocrResult['text'] ?? '')) !== '') {
                            $result = [
                                'text' => $ocrResult['text'],
                                'excerpt' => mb_substr((string) $ocrResult['text'], 0, 1000),
                                'page_count' => $ocrResult['page_count'] ?? $result['page_count'],
                                'character_count' => $ocrResult['character_count'] ?? mb_strlen((string) $ocrResult['text']),
                                'requires_transcription' => false,
                                'method' => $ocrResult['method'] ?? 'openai_vision_image',
                                'pages' => $ocrResult['pages'] ?? [],
                                'embedded_images' => $embeddedImages,
                            ];
                        }
                    } catch (Throwable $imageThrowable) {
                        $documentService->addLog($document, 'image_ocr_failed', $imageThrowable->getMessage());
                    }
                }

                if (trim((string) ($result['text'] ?? '')) === '') {
                    $documentService->markRequiresOcr($document, __('This Word document could not be extracted locally. Ask an admin to retry it with OpenAI-assisted OCR review.'));
                    return;
                }
            }

            if (! $isWordDocument && $result['requires_transcription']) {
                $documentService->updateProgress($document, 48, 'transcription', __('Text was limited, attempting OCR fallback.'));
                $documentService->addLog($document, 'transcription', __('Text extraction returned little or no text, attempting OCR fallback.'));

                try {
                    $documentService->updateProgress($document, 58, 'ocr', __('Running OCR fallback.'));
                    $fallback = $this->transcribePdfLikeFile($tempPath, $ocrService, $transcriptionService);
                    if (trim((string) ($fallback['text'] ?? '')) !== '') {
                        $result = [
                            'text' => $fallback['text'],
                            'excerpt' => mb_substr((string) $fallback['text'], 0, 1000),
                            'page_count' => $fallback['page_count'] ?? ($result['page_count'] ?? 0),
                            'character_count' => $fallback['character_count'] ?? mb_strlen((string) $fallback['text']),
                            'requires_transcription' => false,
                            'method' => $fallback['method'] ?? 'ocr',
                            'pages' => $fallback['pages'] ?? [],
                        ];
                    }
                } catch (Throwable $fallbackThrowable) {
                    $documentService->addLog($document, 'transcription_failed', $fallbackThrowable->getMessage());
                }
            }

            if (trim((string) $result['text']) === '') {
                if ($isWordDocument) {
                    $documentService->markFailed($document, __('This Word document could not be read or did not contain extractable text.'));
                    return;
                }

                if ($result['requires_transcription']) {
                    $documentService->markRequiresOcr($document, __('This PDF appears to be scanned or image-based and could not be transcribed.'));
                    return;
                }
            }

            if ($isWordDocument) {
                $result['pages'] = $result['pages'] ?? [];
                $result['requires_transcription'] = false;

                if (($result['page_count'] ?? 0) <= 0 && trim((string) $result['text']) !== '') {
                    $result['page_count'] = 1;
                }
            }

            $documentService->updateProgress($document, 68, 'chunking', __('Building searchable chunks.'));
            $chunks = $chunkingService->buildChunks($result['pages'] ?? $result['text'], [
                'source_type' => $document->source_type,
                'source_name' => $document->source_name,
            ]);

            $documentService->updateProgress($document, 82, 'question_regions', __('Building question regions.'));
            $documentService->saveChunks($document, $chunks);
            $questionRegionService->rebuild($document, $result['pages'] ?? []);
            $documentService->updateProgress($document, 86, 'question_extraction', __('Extracting questions from the document.'));
            $questionExtraction = $questionExtractionService->extract(
                $result['text'] ?: $result['pages'],
                false
            );

            if (empty($questionExtraction['questions']) && ! empty($result['questions'])) {
                $questionExtraction = [
                    'questions' => $result['questions'],
                    'question_count' => $result['question_count'] ?? count($result['questions']),
                    'section_count' => $result['section_count'] ?? 0,
                    'sections' => $result['sections'] ?? [],
                    'method' => $result['question_extraction_method'] ?? 'openai',
                ];
            }
            $documentService->saveQuestions($document, $questionExtraction['questions'] ?? []);
            $documentService->updateProgress($document, 92, 'finalizing', __('Finalizing processed document.'));
            $documentService->markProcessed($document, [
                'page_count' => $result['page_count'],
                'character_count' => $result['character_count'],
                'excerpt' => $result['excerpt'],
                'metadata' => [
                    'extraction_method' => $result['method'],
                    'requires_transcription' => false,
                    'question_count' => $questionExtraction['question_count'] ?? 0,
                    'question_extraction_method' => $questionExtraction['method'] ?? null,
                    'question_regions' => count($document->questionRegions()->get()),
                ],
            ]);
        } catch (Throwable $throwable) {
            $documentService->updateProgress($document, 100, 'failed', __('Document processing failed.'));
            $documentService->markFailed($document, $throwable->getMessage());
        } finally {
            if ($tempPath && is_file($tempPath)) {
                @unlink($tempPath);
            }

            $embeddedImagePayload = $result['embedded_images'] ?? [];
            $embeddedImages = $embeddedImagePayload['items'] ?? [];

            foreach ($embeddedImages as $embeddedImage) {
                $embeddedPath = $embeddedImage['path'] ?? null;
                if ($embeddedPath && is_file($embeddedPath)) {
                    @unlink($embeddedPath);
                }
            }

            if (! empty($renderedImagePayload['temp_dir'])) {
                $this->deleteDirectory((string) $renderedImagePayload['temp_dir']);
            }

            $this->deleteDirectory((string) ($embeddedImagePayload['temp_dir'] ?? ''));
        }
    }

    private function deleteDirectory(string $directory): void
    {
        if ($directory === '' || ! is_dir($directory)) {
            return;
        }

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory($file);
                continue;
            }

            @unlink($file);
        }

        @rmdir($directory);
    }

    private function transcribePdfLikeFile(
        string $pdfPath,
        OcrPdfExtractionService $ocrService,
        OpenAiPdfTranscriptionService $transcriptionService,
        bool $preferOpenAi = false
    ): array {
        if (filled(config('services.openai_vision.api_key'))) {
            $fallback = $transcriptionService->transcribe($pdfPath, (int) config('services.ai.scanned_pdf_max_pages', 10));
            if (trim((string) ($fallback['text'] ?? '')) !== '') {
                return [
                    'text' => $fallback['text'],
                    'excerpt' => mb_substr((string) $fallback['text'], 0, 1000),
                    'page_count' => $fallback['page_count'] ?? 0,
                    'character_count' => $fallback['character_count'] ?? mb_strlen((string) $fallback['text']),
                    'method' => $fallback['method'] ?? 'openai_vision',
                    'pages' => $fallback['pages'] ?? [],
                ];
            }
        }

        if ($ocrService->canRun()) {
            $fallback = $ocrService->extractFromPdf($pdfPath, (int) config('services.ai.scanned_pdf_max_pages', 10));
            if (trim((string) ($fallback['text'] ?? '')) !== '') {
                return [
                    'text' => $fallback['text'],
                    'excerpt' => mb_substr((string) $fallback['text'], 0, 1000),
                    'page_count' => $fallback['page_count'] ?? 0,
                    'character_count' => $fallback['character_count'] ?? mb_strlen((string) $fallback['text']),
                    'method' => $fallback['method'] ?? 'tesseract',
                    'pages' => $fallback['pages'] ?? [],
                ];
            }
        }

        return [
            'text' => '',
            'excerpt' => '',
            'page_count' => 0,
            'character_count' => 0,
            'method' => 'unavailable',
            'pages' => [],
        ];
    }

    private function transcribeWordLikeFileForOpenAiReview(
        array $extractionResult,
        ImageTranscriptionService $imageTranscriptionService,
        LegacyDocRenderingService $docRenderingService
    ): array {
        $renderedImagePayload = $docRenderingService->renderFromExtractionResult($extractionResult, (int) config('services.ai.scanned_pdf_max_pages', 10));
        $renderedImagePaths = array_values(array_filter(array_map(
            fn (array $item) => $item['path'] ?? null,
            $renderedImagePayload['items'] ?? []
        )));

        if (! empty($renderedImagePaths)) {
            $openAiResult = $imageTranscriptionService->transcribeImagePaths(
                $renderedImagePaths,
                (int) config('services.ai.scanned_pdf_max_pages', 10)
            );

            if (trim((string) ($openAiResult['text'] ?? '')) !== '') {
                return [
                    'text' => $openAiResult['text'],
                    'excerpt' => mb_substr((string) $openAiResult['text'], 0, 1000),
                    'page_count' => $openAiResult['page_count'] ?? 0,
                    'character_count' => $openAiResult['character_count'] ?? mb_strlen((string) $openAiResult['text']),
                    'method' => $openAiResult['method'] ?? 'openai_vision_image',
                    'pages' => $openAiResult['pages'] ?? [],
                ];
            }
        }

        $embeddedImagePayload = $extractionResult['embedded_images'] ?? ['items' => []];
        $embeddedImagePaths = array_values(array_filter(array_map(
            fn (array $image) => $image['path'] ?? null,
            $embeddedImagePayload['items'] ?? []
        )));

        if (! empty($embeddedImagePaths)) {
            $openAiResult = $imageTranscriptionService->transcribeImagePaths(
                $embeddedImagePaths,
                (int) config('services.ai.scanned_pdf_max_pages', 10)
            );

            if (trim((string) ($openAiResult['text'] ?? '')) !== '') {
                return [
                    'text' => $openAiResult['text'],
                    'excerpt' => mb_substr((string) $openAiResult['text'], 0, 1000),
                    'page_count' => $openAiResult['page_count'] ?? 0,
                    'character_count' => $openAiResult['character_count'] ?? mb_strlen((string) $openAiResult['text']),
                    'method' => $openAiResult['method'] ?? 'openai_vision_image',
                    'pages' => $openAiResult['pages'] ?? [],
                ];
            }
        }

        return [
            'text' => '',
            'excerpt' => '',
            'page_count' => 0,
            'character_count' => 0,
            'method' => 'openai_review_unavailable',
            'pages' => [],
        ];
    }
}
