<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiDocument;
use App\Models\AiDocumentQuestion;
use App\Models\AiDocumentQuestionRegion;
use App\Models\Product;
use App\Models\ProductNoteTopic;
use App\Models\ProductReview;
use App\Services\Ai\AiDocumentRetrievalService;
use App\Services\Ai\AiCreditLedgerService;
use App\Models\AiRequest;
use App\Services\Ai\AiProviderRouterService;
use App\Services\Ai\OpenAiChatProvider;
use App\Services\ProductNoteService;
use App\Services\Ai\PdfTextExtractionService;
use App\Services\Ai\AiDocumentQuestionAiService;
use App\Services\Ai\WordTextExtractionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Order\app\Models\OrderItem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductPageController extends Controller
{
    public function __construct(
        private readonly ProductNoteService $noteService,
        private readonly AiDocumentQuestionAiService $documentQuestionAiService,
        private readonly AiDocumentRetrievalService $documentRetrievalService,
        private readonly AiCreditLedgerService $creditLedger,
        private readonly AiProviderRouterService $providerRouterService,
        private readonly OpenAiChatProvider $openAiProvider,
        private readonly PdfTextExtractionService $pdfTextExtractionService,
        private readonly WordTextExtractionService $wordTextExtractionService
    )
    {
    }

    public function show(string $slug): View|RedirectResponse
    {
        $product = Product::approved()
            ->with(['category.translation'])
            ->where('slug', $slug)
            ->firstOrFail();

        if ($product->type === Product::TYPE_NOTE) {
            $product->load(['note.topics', 'note.resources']);
        } elseif ($product->type === Product::TYPE_QUIZ) {
            $product->load(['quiz.questions.options']);
        }

        if ($product->type === Product::TYPE_COURSE && $product->course) {
            return redirect()->route('course.show', $product->course->slug);
        }

        $hasAccess = $this->hasAccess($product);
        $ratingData = $this->ratingData($product);
        $userReview = auth('web')->check()
            ? ProductReview::where('product_id', $product->id)->where('user_id', userAuth()->id)->first()
            : null;

        return view('frontend.pages.product-details', compact('product', 'hasAccess', 'ratingData', 'userReview'));
    }

    public function readNote(string $slug, ?string $topicSlug = null): View
    {
        $product = Product::approved()
            ->with([
                'note.topics',
                'note.resources',
            ])
            ->where('type', Product::TYPE_NOTE)
            ->where('slug', $slug)
            ->firstOrFail();
        abort_unless($this->hasAccess($product), 403);

        $readerData = $this->noteService->readerData($product, $topicSlug ?: request('topic'), userAuth(), false);

        return view('frontend.pages.product-note-viewer', array_merge($readerData, [
            'product' => $product,
            'previewMode' => false,
        ]));
    }

    public function readDocument(string $slug): View
    {
        $product = $this->resolveDocumentProduct($slug);
        abort_unless($this->hasAccess($product), 403);
        abort_unless($this->supportsInlineDocumentReader($product), 404);

        $document = AiDocument::query()
            ->where('product_id', $product->id)
            ->processed()
            ->with([
                'questionRegions' => fn ($query) => $query->orderBy('page_number')->orderBy('question_number'),
                'chunks' => fn ($query) => $query->orderBy('page_start')->orderBy('chunk_index'),
            ])
            ->latest('id')
            ->first();
        $readerMode = $this->documentReaderMode($product, $document);
        $readerPages = $this->documentPages($document);
        $readerText = $this->documentReadableText($readerPages, $document);

        return view('frontend.pages.product-document-viewer', [
            'product' => $product,
            'readerMode' => $readerMode,
            'documentUrl' => $readerMode === 'pdf' ? route('product.document-source', $product->slug) : null,
            'readerPages' => $readerPages,
            'readerText' => $readerText,
            'readerSummary' => [
                'page_count' => (int) ($document?->page_count ?? count($readerPages)),
                'character_count' => (int) ($document?->character_count ?? mb_strlen($readerText)),
                'extraction_method' => (string) data_get($document?->metadata, 'extraction_method', strtoupper((string) $product->file_type)),
                'excerpt' => (string) ($document?->extracted_text_excerpt ?? ''),
            ],
            'questionRegions' => $document?->questionRegions?->values() ?? collect(),
            'aiDocument' => $document,
        ]);
    }

    public function previewDocument(string $slug): View|RedirectResponse
    {
        $product = $this->resolveDocumentProduct($slug);

        if ($this->hasAccess($product) && $this->supportsInlineDocumentReader($product)) {
            return redirect()->route('product.read-document', $product->slug);
        }

        $preview = $this->documentPreviewData($product);
        $ratingData = $this->safeRatingData($product);
        $relatedProducts = $this->safeRelatedProducts($product);
        $hasAccess = $this->hasAccess($product);

        return view('frontend.pages.product-preview', compact(
            'product',
            'preview',
            'ratingData',
            'relatedProducts',
            'hasAccess'
        ));
    }

    public function documentSource(string $slug): BinaryFileResponse|RedirectResponse
    {
        $product = $this->resolveReadableDocumentProduct($slug);
        $source = $this->resolveDocumentSource($product);

        abort_unless($source !== null, 404);

        if ($source['kind'] === 'remote') {
            return redirect()->away($source['url']);
        }

        return response()->file($source['path'], [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Content-Disposition' => 'inline; filename="' . $source['filename'] . '"',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }

    public function documentQuestionRegion(Request $request, string $slug, int $regionId): JsonResponse
    {
        $product = $this->resolveReadableDocumentProduct($slug);
        abort_unless($this->hasAccess($product), 403);

        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:1000'],
        ]);

        $region = AiDocumentQuestionRegion::query()
            ->where('id', $regionId)
            ->whereHas('document', fn ($query) => $query->where('product_id', $product->id)->processed())
            ->firstOrFail();

        $studentPrompt = (string) ($validated['prompt'] ?? '');
        $answer = $this->documentQuestionAiService->answer($region, $studentPrompt);

        $this->recordDocumentQuestionUsage(
            userAuth(),
            $region,
            $studentPrompt,
            $answer,
            false
        );

        return response()->json([
            'status' => 'success',
            'region' => [
                'id' => $region->id,
                'question_number' => $region->question_number,
                'question_label' => $region->question_label,
                'page_number' => $region->page_number,
                'content' => $region->content,
                'bbox' => [
                    'x' => $region->x,
                    'y' => $region->y,
                    'width' => $region->width,
                    'height' => $region->height,
                ],
            ],
            'answer' => $answer,
        ]);
    }

    public function documentQuestionRegionStream(Request $request, string $slug, int $regionId): StreamedResponse|JsonResponse
    {
        $product = $this->resolveReadableDocumentProduct($slug);
        abort_unless($this->hasAccess($product), 403);

        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:1000'],
        ]);

        $region = AiDocumentQuestionRegion::query()
            ->where('id', $regionId)
            ->whereHas('document', fn ($query) => $query->where('product_id', $product->id)->processed())
            ->firstOrFail();

        $studentPrompt = (string) ($validated['prompt'] ?? '');

        return response()->stream(function () use ($region, $studentPrompt) {
            ignore_user_abort(true);

            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            ob_implicit_flush(true);

            $streamedContent = '';

            try {
                $answer = $this->documentQuestionAiService->streamAnswer(
                    $region,
                    $studentPrompt,
                    function (string $token) use (&$streamedContent) {
                        $streamedContent .= $token;
                        echo $this->streamEvent('token', $token);
                        @flush();
                    }
                );

                $this->recordDocumentQuestionUsage(
                    userAuth(),
                    $region,
                    $studentPrompt,
                    $answer,
                    true,
                    $streamedContent
                );

                echo $this->streamEvent('meta', [
                    'provider' => $answer['provider'] ?? null,
                    'model' => $answer['model'] ?? null,
                ]);
                echo $this->streamEvent('done', [
                    'content' => $answer['content'] ?? $streamedContent,
                ]);
            } catch (\Throwable $throwable) {
                echo $this->streamEvent('error', [
                    'message' => $throwable->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function documentAssistantStream(Request $request, string $slug): StreamedResponse|JsonResponse
    {
        $product = $this->resolveReadableDocumentProduct($slug);
        abort_unless($this->hasAccess($product), 403);

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:1000'],
            'mode' => ['nullable', 'string', 'max:32'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['nullable', 'string', 'max:32'],
            'history.*.content' => ['nullable', 'string', 'max:3000'],
            'selected_region' => ['nullable', 'array'],
            'selected_region.id' => ['nullable', 'integer'],
            'selected_region.question_label' => ['nullable', 'string', 'max:255'],
            'selected_region.question_number' => ['nullable', 'integer'],
            'selected_region.page_number' => ['nullable', 'integer'],
            'selected_region.content' => ['nullable', 'string', 'max:3000'],
        ]);

        $document = AiDocument::query()
            ->where('product_id', $product->id)
            ->processed()
            ->with(['chunks' => fn ($query) => $query->orderBy('page_start')->orderBy('chunk_index')])
            ->latest('id')
            ->firstOrFail();

        $prompt = trim((string) $validated['prompt']);
        $mode = strtolower(trim((string) ($validated['mode'] ?? 'ask')));
        $history = collect($validated['history'] ?? [])
            ->take(6)
            ->map(function (array $entry): array {
                $role = strtolower(trim((string) ($entry['role'] ?? 'user')));
                $content = trim((string) ($entry['content'] ?? ''));

                return [
                    'role' => in_array($role, ['user', 'assistant'], true) ? $role : 'user',
                    'content' => $content,
                ];
            })
            ->filter(fn (array $entry) => $entry['content'] !== '')
            ->values()
            ->all();

        $selectedRegion = collect([
            'id' => isset($validated['selected_region']['id']) ? (int) $validated['selected_region']['id'] : null,
            'question_label' => trim((string) ($validated['selected_region']['question_label'] ?? '')),
            'question_number' => isset($validated['selected_region']['question_number']) ? (int) $validated['selected_region']['question_number'] : null,
            'page_number' => isset($validated['selected_region']['page_number']) ? (int) $validated['selected_region']['page_number'] : null,
            'content' => trim((string) ($validated['selected_region']['content'] ?? '')),
        ])->filter(fn ($value) => ! (is_null($value) || $value === ''))->all();

        if (empty($selectedRegion)) {
            $selectedRegion = $this->resolveSelectedQuestionRegionFromPrompt($document, $prompt);
        }

        $resolvedQuestion = $this->resolveSelectedQuestionFromPrompt($document, $prompt, $selectedRegion['question_number'] ?? null);
        if (! empty($resolvedQuestion['content'])) {
            $selectedRegion['content'] = $selectedRegion['content'] ?? $resolvedQuestion['content'];
        }
        if (! empty($resolvedQuestion['question_label']) && empty($selectedRegion['question_label'])) {
            $selectedRegion['question_label'] = $resolvedQuestion['question_label'];
        }
        if (! empty($resolvedQuestion['question_number']) && empty($selectedRegion['question_number'])) {
            $selectedRegion['question_number'] = $resolvedQuestion['question_number'];
        }

        $retrieval = $this->documentRetrievalService->search((int) $document->instructor_id, $prompt, [
            'document_ids' => [$document->id],
            'limit' => 5,
        ]);

        $context = trim((string) data_get($retrieval, 'context', ''));
        if ($context === '') {
            $context = $this->documentReadableText($this->documentPages($document), $document);
        }

        $selectedRegionLabel = ! empty($selectedRegion)
            ? implode(' | ', array_filter([
                $selectedRegion['question_label'] !== '' ? $selectedRegion['question_label'] : null,
                isset($selectedRegion['question_number']) ? 'Question ' . $selectedRegion['question_number'] : null,
                isset($selectedRegion['page_number']) ? 'Page ' . $selectedRegion['page_number'] : null,
            ]))
            : '';

        $userPromptLines = array_filter([
            'Document title: ' . $product->title,
            'Assistant mode: ' . $this->documentAssistantModeLabel($mode),
            $selectedRegionLabel !== '' ? 'Selected region: ' . $selectedRegionLabel : null,
            ! empty($selectedRegion['content']) ? 'Selected region content: ' . $selectedRegion['content'] : null,
            ! empty($resolvedQuestion['content']) ? 'Matched question text: ' . $resolvedQuestion['content'] : null,
            'Student question: ' . $prompt,
            $context !== ''
                ? "Relevant document context:\n" . $context
                : 'Relevant document context: none available.',
        ]);

        $messages = array_merge(
            [
                [
                    'role' => 'system',
                    'content' => implode("\n", array_filter([
                        'You are an in-reader AI assistant for the current study document.',
                        'Use the provided document context first and stay grounded in it.',
                        'If the answer is not supported by the document, say so clearly and keep the answer helpful.',
                        'When relevant, mention page numbers, chunk labels, or exact document wording from the context.',
                        'Match the user mode when possible: explain should simplify, solve should show the solution steps, hint should guide without giving everything away, summary should be concise, related should surface nearby ideas or connections, and ask should answer normally.',
                        'If the user asks to solve a numbered question and the document context includes that question text, answer directly from that text instead of asking the user to repeat it.',
                        'Do not claim the question is missing when the selected question content is already included below.',
                    ])),
                ],
            ],
            $history,
            [
                [
                    'role' => 'user',
                    'content' => trim(implode("\n\n", $userPromptLines)),
                ],
            ]
        );

        $providerOrder = $this->providerRouterService->orderedProviders($prompt, $retrieval);
        $providerName = $providerOrder[0] ?? 'openai';

        return response()->stream(function () use ($providerName, $messages, $retrieval) {
            ignore_user_abort(true);

            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');

            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            ob_implicit_flush(true);

            echo $this->streamEvent('meta', [
                'provider' => $providerName,
                'top_score' => (int) data_get($retrieval, 'results.0.score', 0),
            ]);

            try {
                $result = $this->openAiProvider->streamAnswer($messages, [
                    'temperature' => 0.2,
                    'max_tokens' => 900,
                ], function (string $token) {
                    echo $this->streamEvent('token', $token);
                });

                $this->recordDocumentAssistantUsage(
                    userAuth(),
                    $prompt,
                    $mode,
                    $selectedRegion,
                    $result,
                    $retrieval
                );

                echo $this->streamEvent('done', [
                    'content' => trim((string) ($result['content'] ?? '')),
                    'provider' => $result['provider'] ?? $providerName,
                    'model' => $result['model'] ?? null,
                ]);
            } catch (\Throwable $throwable) {
                echo $this->streamEvent('error', [
                    'message' => $throwable->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function resolveSelectedQuestionRegionFromPrompt(AiDocument $document, string $prompt): array
    {
        $questionNumber = $this->extractQuestionNumberFromPrompt($prompt);
        if ($questionNumber === null) {
            return [];
        }

        $region = $document->questionRegions()
            ->where('question_number', $questionNumber)
            ->orderBy('page_number')
            ->orderBy('id')
            ->first();

        if (! $region) {
            return [];
        }

        return array_filter([
            'id' => $region->id,
            'question_label' => $region->question_label,
            'question_number' => $region->question_number,
            'page_number' => $region->page_number,
            'content' => trim((string) $region->content),
        ], static fn ($value) => ! (is_null($value) || $value === ''));
    }

    private function resolveSelectedQuestionFromPrompt(AiDocument $document, string $prompt, ?int $fallbackQuestionNumber = null): array
    {
        $questionNumber = $fallbackQuestionNumber ?? $this->extractQuestionNumberFromPrompt($prompt);
        if ($questionNumber === null) {
            return [];
        }

        $question = $document->questions()
            ->where('question_number', $questionNumber)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $question) {
            return [];
        }

        return array_filter([
            'question_number' => $question->question_number,
            'question_label' => $question->question_label,
            'content' => trim((string) $question->content),
            'raw_text' => trim((string) $question->raw_text),
        ], static fn ($value) => ! (is_null($value) || $value === ''));
    }

    private function recordDocumentQuestionUsage(
        $user,
        AiDocumentQuestionRegion $region,
        string $studentPrompt,
        array $answer,
        bool $stream,
        ?string $streamedContent = null
    ): void {
        if (! $user || ! Schema::hasTable('ai_requests')) {
            return;
        }

        $content = trim((string) ($answer['content'] ?? $streamedContent ?? ''));
        $creditsUsed = max(1, (int) ($answer['credits_used'] ?? 1));
        $inputTokens = (int) ($answer['input_tokens'] ?? 0);
        $outputTokens = (int) ($answer['output_tokens'] ?? 0);
        $estimatedCost = (float) ($answer['estimated_cost'] ?? 0);
        $latency = (int) ($answer['latency'] ?? 0);

        $request = AiRequest::create([
            'user_id' => $user->id,
            'conversation_id' => null,
            'provider' => (string) ($answer['provider'] ?? 'openai'),
            'model' => $answer['model'] ?? null,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'credits_used' => $creditsUsed,
            'estimated_cost' => $estimatedCost,
            'latency' => $latency,
            'status' => 'success',
            'mode' => 'document_question',
            'metadata' => [
                'stream' => $stream,
                'prompt' => $studentPrompt,
                'region_id' => $region->id,
                'question_number' => $region->question_number,
                'question_label' => $region->question_label,
                'page_number' => $region->page_number,
                'answer_preview' => Str::limit($content, 240),
            ],
            'created_at' => now(),
        ]);

        $this->creditLedger->recordUsage($user, $request);
    }

    private function recordDocumentAssistantUsage(
        $user,
        string $prompt,
        string $mode,
        array $selectedRegion,
        array $answer,
        array $retrieval
    ): void {
        if (! $user || ! Schema::hasTable('ai_requests')) {
            return;
        }

        $content = trim((string) ($answer['content'] ?? ''));
        $creditsUsed = max(1, (int) ($answer['credits_used'] ?? 1));
        $request = AiRequest::create([
            'user_id' => $user->id,
            'conversation_id' => null,
            'provider' => (string) ($answer['provider'] ?? 'openai'),
            'model' => $answer['model'] ?? null,
            'input_tokens' => (int) ($answer['input_tokens'] ?? 0),
            'output_tokens' => (int) ($answer['output_tokens'] ?? 0),
            'credits_used' => $creditsUsed,
            'estimated_cost' => (float) ($answer['estimated_cost'] ?? 0),
            'latency' => (int) ($answer['latency'] ?? 0),
            'status' => 'success',
            'mode' => $mode ?: 'document_assistant',
            'metadata' => [
                'prompt' => $prompt,
                'selected_region' => $selectedRegion,
                'retrieval_top_score' => (int) data_get($retrieval, 'results.0.score', 0),
                'answer_preview' => Str::limit($content, 240),
            ],
            'created_at' => now(),
        ]);

        $this->creditLedger->recordUsage($user, $request);
    }

    private function extractQuestionNumberFromPrompt(string $prompt): ?int
    {
        if (preg_match('/\b(?:question|q|solve)\s*#?\s*(\d{1,3})\b/i', $prompt, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/\b(\d{1,3})\b/', $prompt, $matches) && preg_match('/\b(question|q|solve)\b/i', $prompt)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function updateNoteProgress(Request $request, string $slug): JsonResponse
    {
        $product = Product::approved()->where('type', Product::TYPE_NOTE)->where('slug', $slug)->firstOrFail();
        abort_unless($this->hasAccess($product), 403);

        $validated = $request->validate([
            'topic_id' => ['nullable', 'integer'],
            'node_id' => ['nullable', 'integer'],
            'last_block_id' => ['nullable', 'integer'],
        ]);

        $nodeId = $validated['node_id'] ?? $validated['topic_id'] ?? null;
        abort_unless($nodeId, 422);

        $topic = ProductNoteTopic::whereHas('note', fn ($query) => $query->where('product_id', $product->id))
            ->findOrFail($nodeId);

        $progress = $this->noteService->updateProgress(userAuth(), $product, $topic, $validated['last_block_id'] ?? null);

        return response()->json([
            'status' => 'success',
            'progress' => $progress->completion_percent,
        ]);
    }

    public function toggleNoteBookmark(Request $request, string $slug): JsonResponse
    {
        $product = Product::approved()->where('type', Product::TYPE_NOTE)->where('slug', $slug)->firstOrFail();
        abort_unless($this->hasAccess($product), 403);

        $validated = $request->validate([
            'topic_id' => ['nullable', 'integer'],
            'node_id' => ['nullable', 'integer'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $nodeId = $validated['node_id'] ?? $validated['topic_id'] ?? null;
        abort_unless($nodeId, 422);

        $topic = ProductNoteTopic::whereHas('note', fn ($query) => $query->where('product_id', $product->id))
            ->findOrFail($nodeId);

        return response()->json(array_merge(
            ['status' => 'success'],
            $this->noteService->toggleBookmark(userAuth(), $product, $topic, null, $validated['label'] ?? null)
        ));
    }

    public function downloadFile(string $productType, int $productId)
    {
        $product = Product::approved()
            ->where('type', $productType)
            ->whereKey($productId)
            ->firstOrFail();

        abort_unless($this->hasAccess($product), 403);

        $source = $this->resolveDocumentSource($product);
        abort_unless($source !== null, 404);

        if ($source['kind'] === 'remote') {
            return redirect()->away($source['url']);
        }

        $extension = strtolower((string) pathinfo($source['path'], PATHINFO_EXTENSION));
        $filename = Str::slug($product->title) . ($extension !== '' ? '.' . $extension : '');

        return response()->download($source['path'], $filename);
    }

    public function startQuiz(string $slug)
    {
        $product = Product::approved()->where('type', Product::TYPE_QUIZ)->where('slug', $slug)->firstOrFail();
        abort_unless($this->hasAccess($product), 403);

        return view('frontend.pages.product-quiz-launch', compact('product'));
    }

    public function storeReview(Request $request, string $slug): RedirectResponse
    {
        $product = Product::approved()->where('slug', $slug)->firstOrFail();
        abort_unless($this->hasAccess($product), 403);

        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'max:1000'],
        ], [
            'rating.required' => __('rating filed is required'),
            'rating.integer' => __('rating have to be an integer'),
            'review.required' => __('review filed is required'),
        ]);

        $existingReview = ProductReview::where('product_id', $product->id)
            ->where('user_id', userAuth()->id)
            ->first();

        if ($existingReview) {
            return redirect()->back()->with(['alert-type' => 'error', 'messege' => __('Already added review')]);
        }

        ProductReview::create([
            'product_id' => $product->id,
            'user_id' => userAuth()->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return redirect()->back()->with(['alert-type' => 'success', 'messege' => __('Review added successfully')]);
    }

    private function hasAccess(Product $product): bool
    {
        if (!auth('web')->check()) {
            return false;
        }

        if (hasActiveSubscription()) {
            return true;
        }

        if ((float) $product->effective_price === 0.0) {
            return true;
        }

        return OrderItem::where('item_type', 'product')
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('buyer_id', userAuth()->id)->where('payment_status', 'paid'))
            ->exists();
    }

    private function ratingData(Product $product): array
    {
        $stats = ProductReview::where('product_id', $product->id)
            ->where('status', 1)
            ->whereHas('product')
            ->whereHas('user')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->get();

        $ratingCounts = [];
        $totalReviews = 0;

        foreach ($stats as $stat) {
            $ratingCounts[$stat->rating] = $stat->count;
            $totalReviews += $stat->count;
        }

        $averageRating = ProductReview::where('product_id', $product->id)
            ->where('status', 1)
            ->whereHas('product')
            ->whereHas('user')
            ->avg('rating') ?? 0;

        $reviews = ProductReview::with('user:id,name,image')
            ->where('product_id', $product->id)
            ->where('status', 1)
            ->whereHas('product')
            ->whereHas('user')
            ->latest()
            ->get();

        return [
            'average' => $averageRating,
            'total' => $totalReviews,
            'fiveStar' => $ratingCounts[5] ?? 0,
            'fourStar' => $ratingCounts[4] ?? 0,
            'threeStar' => $ratingCounts[3] ?? 0,
            'twoStar' => $ratingCounts[2] ?? 0,
            'oneStar' => $ratingCounts[1] ?? 0,
            'reviews' => $reviews,
        ];
    }

    private function relatedProducts(Product $product)
    {
        $categoryId = (int) $product->category_id;
        $parentCategoryId = (int) ($product->category?->parent_id ?? 0);

        return Product::approved()
            ->with(['category.translation'])
            ->where('id', '!=', $product->id)
            ->where('type', $product->type)
            ->when($categoryId > 0, function ($query) use ($categoryId, $parentCategoryId) {
                $query->where(function ($nested) use ($categoryId, $parentCategoryId) {
                    $nested->where('category_id', $categoryId);
                    if ($parentCategoryId > 0) {
                        $nested->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('parent_id', $parentCategoryId));
                    }
                });
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Product $item) {
                $metadata = $item->metadata ?? [];

                return (object) [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'title' => $item->title,
                    'thumbnail' => $item->thumbnail,
                    'type' => $item->type,
                    'type_label' => $item->type_label,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'effective_price' => $item->effective_price,
                    'category_translation_name' => $item->category?->translation?->name ?? $item->category?->name,
                    'meta' => collect([
                        $metadata['subject'] ?? null,
                        $metadata['year'] ?? null,
                        strtoupper((string) ($item->file_type ?? '')),
                    ])->filter()->implode(' • '),
                ];
            });
    }

    private function resolveReadableDocumentProduct(string $slug): Product
    {
        $product = $this->resolveDocumentProduct($slug);

        abort_unless($this->hasAccess($product), 403);
        abort_unless($this->supportsInlineDocumentReader($product), 404);

        return $product;
    }

    private function resolveDocumentProduct(string $slug): Product
    {
        return Product::approved()
            ->whereIn('type', [Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function documentPreviewData(Product $product): array
    {
        $cacheKey = sprintf(
            'product_document_preview:%d:%s:%s:%s',
            $product->id,
            (string) $product->updated_at?->timestamp,
            (string) $product->file_type,
            md5((string) $product->file_path)
        );

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($product) {
            try {
                $absolutePath = public_path((string) $product->file_path);
                $previewPagesLimit = $this->previewPageLimit($product);
                $previewLabel = $this->previewLabel($product);

                if (! is_file($absolutePath)) {
                    return $this->fallbackDocumentPreview($product, $previewLabel, $previewPagesLimit);
                }

                $fileType = strtolower((string) $product->file_type);
                $previewPages = [];
                $excerpt = '';
                $pageCount = 0;

                if ($fileType === 'pdf') {
                    $extraction = $this->pdfTextExtractionService->extractFromPath($absolutePath);
                    $pageCount = (int) ($extraction['page_count'] ?? 0);
                    $previewPages = collect($extraction['pages'] ?? [])
                        ->take(max(1, $previewPagesLimit))
                        ->values()
                        ->map(function (array $page) {
                            return [
                                'page_number' => (int) ($page['page_number'] ?? 0),
                                'text' => trim((string) ($page['text'] ?? '')),
                            ];
                        })
                        ->filter(fn (array $page) => $page['text'] !== '')
                        ->values()
                        ->all();
                    $excerpt = trim(implode("\n\n", array_map(fn (array $page) => $page['text'], $previewPages)));
                    $excerpt = $excerpt !== '' ? $excerpt : (string) ($extraction['excerpt'] ?? '');
                } elseif ($fileType === 'docx' || $fileType === 'doc') {
                    $extraction = $this->wordTextExtractionService->extractFromPath($absolutePath);
                    $pageCount = (int) ($extraction['page_count'] ?? 0);
                    $excerpt = trim((string) ($extraction['excerpt'] ?? $extraction['text'] ?? ''));
                    $previewPages = collect($extraction['pages'] ?? [])
                        ->take(1)
                        ->values()
                        ->map(function (array $page) {
                            return [
                                'page_number' => (int) ($page['page_number'] ?? 1),
                                'text' => trim((string) ($page['text'] ?? '')),
                            ];
                        })
                        ->filter(fn (array $page) => $page['text'] !== '')
                        ->values()
                        ->all();
                } else {
                    $excerpt = __('Preview is not available for this file type.');
                }

                if ($excerpt === '') {
                    $excerpt = __('Preview content is loading, but the source text could not be extracted cleanly.');
                }

                if (empty($previewPages) && $excerpt !== '') {
                    $previewPages = [[
                        'page_number' => 1,
                        'text' => $excerpt,
                    ]];
                }

                return [
                    'available' => true,
                    'label' => $previewLabel,
                    'pages' => $previewPages,
                    'excerpt' => $excerpt,
                    'page_count' => $pageCount,
                    'preview_pages_limit' => $previewPagesLimit,
                    'source_type' => strtoupper((string) $product->file_type),
                ];
            } catch (\Throwable $throwable) {
                report($throwable);

                $previewLabel = $this->previewLabel($product);
                $previewPagesLimit = $this->previewPageLimit($product);

                return $this->fallbackDocumentPreview($product, $previewLabel, $previewPagesLimit);
            }
        });
    }

    private function fallbackDocumentPreview(Product $product, string $previewLabel, int $previewPagesLimit): array
    {
        return [
            'available' => false,
            'label' => $previewLabel,
            'pages' => [],
            'excerpt' => __('This file is not available for preview yet.'),
            'page_count' => 0,
            'preview_pages_limit' => $previewPagesLimit,
            'source_type' => strtoupper((string) $product->file_type),
        ];
    }

    private function safeRatingData(Product $product): array
    {
        try {
            return $this->ratingData($product);
        } catch (\Throwable $throwable) {
            report($throwable);

            return [
                'average' => 0,
                'total' => 0,
                'fiveStar' => 0,
                'fourStar' => 0,
                'threeStar' => 0,
                'twoStar' => 0,
                'oneStar' => 0,
                'reviews' => collect(),
            ];
        }
    }

    private function safeRelatedProducts(Product $product): Collection
    {
        try {
            return $this->relatedProducts($product);
        } catch (\Throwable $throwable) {
            report($throwable);

            return collect();
        }
    }

    private function previewPageLimit(Product $product): int
    {
        $raw = strtolower(trim((string) data_get($product->metadata, 'preview_pages', '')));

        if ($raw === '' || $raw === 'no preview') {
            return 1;
        }

        if (preg_match('/(\d+)/', $raw, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    }

    private function previewLabel(Product $product): string
    {
        $raw = trim((string) data_get($product->metadata, 'preview_pages', ''));

        return $raw !== '' && strtolower($raw) !== 'no preview'
            ? $raw
            : __('Sample preview');
    }

    private function supportsInlineDocumentReader(Product $product): bool
    {
        if (! in_array($product->file_type, ['pdf', 'docx', 'doc'], true)) {
            return false;
        }

        if ($product->file_type === 'pdf') {
            return $this->resolveDocumentSource($product) !== null || $this->hasDocumentTextFallback($product);
        }

        return true;
    }

    private function resolveDocumentSource(Product $product): ?array
    {
        $filePath = trim((string) $product->file_path);

        if ($filePath !== '') {
            if (filter_var($filePath, FILTER_VALIDATE_URL)) {
                return [
                    'kind' => 'remote',
                    'url' => $filePath,
                ];
            }

            // Bunny Storage uploads are persisted as relative paths, while the
            // reader requires a browser-accessible source URL.
            $storageCdnUrl = rtrim((string) config('bunny.storage_cdn_url'), '/');
            if ($storageCdnUrl !== '' && Str::startsWith($filePath, ['instructors/', 'admins/'])) {
                return [
                    'kind' => 'remote',
                    'url' => $storageCdnUrl . '/' . ltrim($filePath, '/'),
                ];
            }

            $absolutePath = public_path($filePath);

            if (is_file($absolutePath)) {
                return [
                    'kind' => 'local',
                    'path' => $absolutePath,
                    'filename' => basename($absolutePath),
                ];
            }
        }

        $latestDocument = AiDocument::query()
            ->select(['metadata'])
            ->where('product_id', $product->id)
            ->processed()
            ->latest('id')
            ->first();

        $publicUrl = trim((string) data_get($latestDocument?->metadata, 'public_url', ''));

        if ($publicUrl !== '') {
            return [
                'kind' => 'remote',
                'url' => $publicUrl,
            ];
        }

        return null;
    }

    private function documentReaderMode(Product $product, ?AiDocument $document = null): string
    {
        if ($product->file_type === 'pdf' && $this->resolveDocumentSource($product) !== null) {
            return 'pdf';
        }

        if ($product->file_type === 'pdf' && $this->hasDocumentTextFallback($product)) {
            return 'text';
        }

        return in_array($product->file_type, ['doc', 'docx'], true) ? 'text' : 'pdf';
    }

    private function hasDocumentTextFallback(Product $product): bool
    {
        return AiDocument::query()
            ->where('product_id', $product->id)
            ->processed()
            ->where(function ($query) {
                $query->whereHas('chunks')
                    ->orWhereNotNull('extracted_text_excerpt');
            })
            ->exists();
    }

    private function documentPages(?AiDocument $document): array
    {
        if (! $document) {
            return [];
        }

        $chunks = $document->relationLoaded('chunks')
            ? $document->chunks
            : $document->chunks()->orderBy('page_start')->orderBy('chunk_index')->get();

        $pages = collect($chunks)
            ->groupBy(function ($chunk) {
                $pageNumber = (int) ($chunk->page_start ?: $chunk->page_end ?: 0);

                return $pageNumber > 0 ? $pageNumber : ((int) ($chunk->chunk_index ?? 0) + 1);
            })
            ->map(function ($pageChunks, $pageNumber) use ($document) {
                $content = collect($pageChunks)
                    ->pluck('content')
                    ->map(fn ($content) => trim((string) $content))
                    ->filter()
                    ->implode("\n\n");

                if ($content === '') {
                    $content = trim((string) $document->extracted_text_excerpt);
                }

                $heading = collect($pageChunks)
                    ->pluck('heading')
                    ->map(fn ($heading) => trim((string) $heading))
                    ->first(fn ($heading) => $heading !== '');

                return [
                    'page_number' => (int) $pageNumber,
                    'heading' => $heading !== '' ? $heading : null,
                    'content' => $content,
                    'chunk_count' => count($pageChunks),
                ];
            })
            ->filter(fn (array $page) => trim((string) $page['content']) !== '')
            ->sortBy('page_number')
            ->values()
            ->all();

        if (! empty($pages)) {
            return $pages;
        }

        $excerpt = trim((string) $document->extracted_text_excerpt);

        return $excerpt !== '' ? [[
            'page_number' => 1,
            'heading' => $document->source_name ?: null,
            'content' => $excerpt,
            'chunk_count' => 0,
        ]] : [];
    }

    private function documentReadableText(array $pages, ?AiDocument $document = null): string
    {
        $text = trim(implode("\n\n", array_values(array_filter(array_map(
            fn (array $page) => trim((string) ($page['content'] ?? '')),
            $pages
        )))));

        if ($text !== '') {
            return $text;
        }

        return trim((string) ($document?->extracted_text_excerpt ?? ''));
    }

    private function streamEvent(string $event, mixed $data): string
    {
        $payload = is_string($data)
            ? $data
            : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return 'event: ' . $event . "\n"
            . 'data: ' . $payload . "\n\n";
    }

    private function documentAssistantModeLabel(string $mode): string
    {
        return match ($mode) {
            'explain' => __('Explain'),
            'solve' => __('Solve'),
            'hint' => __('Hint'),
            'related' => __('Related'),
            'summary' => __('Summary'),
            default => __('Ask AI'),
        };
    }
}
