<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiDocument;
use App\Services\Ai\AiDocumentRetrievalService;
use App\Services\Ai\AiDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorAiDocumentController extends Controller
{
    public function __construct(private readonly AiDocumentService $documentService)
    {
    }

    public function index(Request $request): JsonResponse|View
    {
        $documents = AiDocument::query()
            ->where('instructor_id', userAuth()->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->when($request->filled('source_type'), fn ($query) => $query->where('source_type', (string) $request->input('source_type')))
            ->with(['product:id,title,slug', 'productNote:id,product_id'])
            ->withCount('chunks')
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        $stats = [
            'total' => AiDocument::where('instructor_id', userAuth()->id)->count(),
            'pending' => AiDocument::where('instructor_id', userAuth()->id)->pending()->count(),
            'processing' => AiDocument::where('instructor_id', userAuth()->id)->processing()->count(),
            'processed' => AiDocument::where('instructor_id', userAuth()->id)->processed()->count(),
            'failed' => AiDocument::where('instructor_id', userAuth()->id)->failed()->count(),
            'requires_ocr' => AiDocument::where('instructor_id', userAuth()->id)->where('status', 'requires_ocr')->count(),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'documents' => $documents,
                'stats' => $stats,
            ]);
        }

        return view('frontend.instructor-dashboard.ai-documents.index', [
            'documents' => $documents,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse|View
    {
        $document = $this->ownedDocument($id);
        $document->load(['chunks', 'processingLogs', 'product:id,title,slug', 'productNote:id,product_id']);

        if ($request->expectsJson()) {
            return response()->json($document);
        }

        return view('frontend.instructor-dashboard.ai-documents.show', [
            'document' => $document,
        ]);
    }

    public function search(Request $request, AiDocumentRetrievalService $retrievalService): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:500'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['integer'],
        ]);

        $results = $retrievalService->search(userAuth()->id, $validated['q'], [
            'limit' => (int) ($validated['limit'] ?? 5),
            'document_ids' => $validated['document_ids'] ?? [],
        ]);

        return response()->json([
            'status' => 'success',
            'query' => $validated['q'],
            'results' => $results['results'],
            'context' => $results['context'],
        ]);
    }

    public function upload(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:51200'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'exists:products,id'],
            'product_note_id' => ['nullable', 'exists:product_notes,id'],
        ]);

        $document = $this->documentService->storeUploadedDocument(
            $request->file('file'),
            userAuth(),
            [
                'source_type' => $validated['source_type'] ?? 'manual_upload',
                'source_name' => $validated['source_name'] ?? $request->file('file')->getClientOriginalName(),
                'product_id' => $validated['product_id'] ?? null,
                'product_note_id' => $validated['product_note_id'] ?? null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Document uploaded and queued for processing.'),
                'document' => $document,
            ]);
        }

        return redirect()
            ->route('instructor.ai-documents.index')
            ->with('success', __('Document uploaded and queued for processing.'));
    }

    public function reprocess(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $document = $this->ownedDocument($id);
        $document = $this->documentService->reprocess($document);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Document reprocessing has been queued.'),
                'document' => $document,
            ]);
        }

        return back()->with('success', __('Document reprocessing has been queued.'));
    }

    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $document = $this->ownedDocument($id);
        $this->documentService->deleteDocument($document);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Document deleted successfully.'),
            ]);
        }

        return redirect()
            ->route('instructor.ai-documents.index')
            ->with('success', __('Document deleted successfully.'));
    }

    private function ownedDocument(int $id): AiDocument
    {
        return AiDocument::where('instructor_id', userAuth()->id)->findOrFail($id);
    }
}
