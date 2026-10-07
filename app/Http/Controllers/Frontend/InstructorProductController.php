<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiDocument;
use App\Models\Product;
use App\Services\Ai\AiDocumentService;
use App\Services\Ai\BunnyDocumentStorageService;
use App\Services\ProductIdentityService;
use App\Services\ProductMetadataCatalogService;
use App\Services\ProductNoteService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Course\app\Models\CourseCategory;

class InstructorProductController extends Controller
{
    public function __construct(
        private readonly ProductNoteService $noteService,
        private readonly AiDocumentService $aiDocumentService,
        private readonly BunnyDocumentStorageService $storageService,
        private readonly ProductMetadataCatalogService $metadataCatalog,
        private readonly ProductIdentityService $identityService
    )
    {
    }

    /**
     * Product type definitions for instructors.
     */
    private array $typeDefinitions = [
        Product::TYPE_PAST_PAPER => [
            'name' => 'Past Paper',
            'plural' => 'Past Papers',
            'icon' => 'flaticon-book',
        ],
        Product::TYPE_PREDICTION => [
            'name' => 'Prediction Pack',
            'plural' => 'Prediction Packs',
            'icon' => 'flaticon-book',
        ],
        Product::TYPE_NOTE => [
            'name' => 'Note',
            'plural' => 'Notes',
            'icon' => 'flaticon-document',
        ],
        Product::TYPE_QUIZ => [
            'name' => 'Quiz',
            'plural' => 'Quizzes',
            'icon' => 'flaticon-quiz',
        ],
    ];

    /**
     * Display a listing of instructor's products, optionally filtered by type.
     */
    public function index(Request $request): View
    {
        $instructorId = userAuth()->id;
        $type = $request->input('type');

        $query = Product::instructorOwned($instructorId)->nonCourse();

        // Filter by specific type if provided
        if ($type && isset($this->typeDefinitions[$type])) {
            $query->where('type', $type);
        }

        $products = $query->orderBy('id', 'desc')->paginate(20);

        // Get type label for page title
        $typeMeta = $type && isset($this->typeDefinitions[$type])
            ? $this->typeDefinitions[$type]
            : null;

        return view('frontend.instructor-dashboard.product.index', compact('products', 'type', 'typeMeta'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $categories = CourseCategory::active()->with('translation')->get();
        $educationCategories = $this->educationRootCategories($categories);
        $paperEducationCategories = $this->buildPaperEducationRootCategories($categories);
        $educationTree = $this->buildPaperEducationTreeForForm($categories);
        $productTypes = $this->getInstructorProductTypes();
        $metadataOptions = $this->metadataCatalog->paperSelectionOptions();
        $currentStep = (int) $request->input('step', 1);

        // Pre-select the current type from the URL or the filtered product page the instructor came from.
        $selectedType = $this->resolveSelectedType($request);

        if ($selectedType === Product::TYPE_NOTE) {
            if ($currentStep === 2) {
                return $this->createNoteContentView();
            }

            if ($currentStep === 3) {
                return $this->createNoteAttachmentsView();
            }

            if ($currentStep === 4) {
                return $this->createNoteSettingsView();
            }

            if ($currentStep === 5) {
                return $this->createNoteReviewView();
            }

            return $this->createNoteView($categories, $educationCategories, $paperEducationCategories, $educationTree, $metadataOptions);
        }

        if ($selectedType === Product::TYPE_QUIZ) {
            return $this->createQuizView();
        }

        $paperForm = $this->resolvePaperFormDefaults($request, $selectedType);

        return view('frontend.instructor-dashboard.product.create', compact('categories', 'educationCategories', 'paperEducationCategories', 'educationTree', 'productTypes', 'selectedType', 'paperForm', 'metadataOptions'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $notePayload = null;
        $resourceUpload = null;

        if (($validated['type'] ?? null) === Product::TYPE_NOTE && $request->filled('note_payload')) {
            $notePayload = $this->noteService->parseBuilderPayload($request->input('note_payload'));
            $this->noteService->validatePayloadForStatus($notePayload, $validated['status']);
        }

        $data = $validated;
        $data['instructor_id'] = userAuth()->id;
        $data['slug'] = generateUniqueSlug(Product::class, $validated['title']);
        $data['is_approved'] = 'pending';
        $data['metadata'] = $this->buildPaperMetadata($request);
        $data['file_path'] = $validated['file_path'] ?? null;
        $data['file_type'] = $this->inferProductFileType($validated['type'], $data['file_path']);
        $data['submission_signature'] = $this->buildProductSubmissionSignature($request, $validated);

        if ($existingSubmission = $this->findDuplicateSubmission(userAuth()->id, $validated['type'], $data['submission_signature'])) {
            return $this->redirectForDuplicateSubmission($existingSubmission)
                ->with('warning', __('You already sent this item for review. Please update the existing item instead of creating a duplicate.'));
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->uploadThumbnail($request);
        }

        $product = $this->createProductWithSlugRetry($data, $validated['title']);

        if ($request->hasFile('resource_file')) {
            $resourceUpload = $this->storeProductResourceFile(
                $request->file('resource_file'),
                $product,
                $validated['title']
            );

            $product->update([
                'file_path' => $resourceUpload['path'],
                'file_type' => $this->inferProductFileType($product->type, $resourceUpload['path']),
            ]);
        }

        $this->syncProductResourceAiDocument($product, $resourceUpload);

        if ($notePayload) {
            $this->noteService->sync($product, $notePayload);
        }

        return redirect()->route('instructor.products.index', ['type' => $product->type])
            ->with('success', __('Product created successfully and sent for review.'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(int $id): View|RedirectResponse
    {
        $product = $this->getOwnedProduct($id);

        if ($product->type === Product::TYPE_NOTE) {
            return redirect()
                ->route('product.show', $product->slug)
                ->with('info', __('The dedicated note builder has been removed.'));
        }

        if ($product->type === Product::TYPE_QUIZ) {
            return redirect()->route('instructor.quizzes.edit', $product->id);
        }

        $categories = CourseCategory::active()->with('translation')->get();
        $educationCategories = $this->educationRootCategories($categories);
        $paperEducationCategories = $this->buildPaperEducationRootCategories($categories);
        $educationTree = $this->buildPaperEducationTreeForForm($categories);
        $productTypes = $this->getInstructorProductTypes();
        $metadataOptions = $this->metadataCatalog->paperSelectionOptions();

        $paperForm = $this->productToPaperForm($product);

        return view('frontend.instructor-dashboard.product.edit', compact('product', 'categories', 'educationCategories', 'paperEducationCategories', 'educationTree', 'productTypes', 'paperForm', 'metadataOptions'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $product = $this->getOwnedProduct($id);
        $oldFilePath = $product->file_path;
        $oldThumbnail = $product->thumbnail;
        $resourceUpload = null;

        $validated = $this->validateProduct($request);

        $data = $validated;
        $data['slug'] = generateUniqueSlug(Product::class, $validated['title'], $product->id);
        $data['metadata'] = $this->buildPaperMetadata($request);
        $data['file_path'] = $validated['file_path'] ?? $product->file_path;
        $data['file_type'] = $this->inferProductFileType($validated['type'], $data['file_path']);
        $data['submission_signature'] = $this->buildProductSubmissionSignature($request, $validated);

        // Reset approval status to pending on meaningful changes
        $data['is_approved'] = 'pending';

        if ($existingSubmission = $this->findDuplicateSubmission(userAuth()->id, $validated['type'], $data['submission_signature'], $product->id)) {
            return $this->redirectForDuplicateSubmission($existingSubmission)
                ->with('warning', __('This item already exists. Please update the original submission instead.'));
        }

        $this->updateProductWithSlugRetry($product, $data, $validated['title']);

        if ($request->hasFile('resource_file')) {
            $resourceUpload = $this->storeProductResourceFile(
                $request->file('resource_file'),
                $product,
                $validated['title']
            );

            $product->update([
                'file_path' => $resourceUpload['path'],
                'file_type' => $this->inferProductFileType($product->type, $resourceUpload['path']),
            ]);
        }

        $this->syncProductResourceAiDocument($product, $resourceUpload);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->uploadThumbnail($request);
            $product->update(['thumbnail' => $data['thumbnail']]);
        }

        if (filled($oldFilePath) && $request->hasFile('resource_file') && $oldFilePath !== $product->file_path) {
            $this->removeStoredFile($oldFilePath);
        }

        if (filled($oldThumbnail) && $request->hasFile('thumbnail') && $oldThumbnail !== $product->thumbnail) {
            $this->removeStoredFile($oldThumbnail);
        }

        return redirect()->route('instructor.products.index', ['type' => $product->type])
            ->with('success', __('Product updated successfully and sent for review.'));
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $product = $this->getOwnedProduct($id);
        $productType = $product->type;
        $product->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Product deleted successfully.'),
                'redirect_url' => route('instructor.products.index', ['type' => $productType]),
            ]);
        }

        return redirect()->route('instructor.products.index', ['type' => $productType])
            ->with('success', __('Product deleted successfully.'));
    }

    /**
     * Get only the product types that instructors can manage (non-course).
     */
    private function getInstructorProductTypes(): array
    {
        return [
            Product::TYPE_PAST_PAPER => __('Past Paper'),
            Product::TYPE_PREDICTION => __('Prediction Pack'),
            Product::TYPE_NOTE => __('Note'),
            Product::TYPE_QUIZ => __('Quiz'),
        ];
    }

    private function findDuplicateSubmission(int $instructorId, string $type, string $signature, ?int $ignoreId = null): ?Product
    {
        $query = Product::withTrashed()
            ->where('instructor_id', $instructorId)
            ->where('type', $type)
            ->where('submission_signature', $signature);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first();
    }

    private function redirectForDuplicateSubmission(Product $product): RedirectResponse
    {
        if ($product->trashed()) {
            return redirect()->route('instructor.products.index', ['type' => $product->type]);
        }

        return match ($product->type) {
            Product::TYPE_QUIZ => redirect()->route('instructor.quizzes.edit', $product->id),
            Product::TYPE_NOTE => redirect()->route('instructor.products.index', ['type' => Product::TYPE_NOTE]),
            default => redirect()->route('instructor.products.edit', $product->id),
        };
    }

    private function buildProductSubmissionSignature(Request $request, array $validated, ?array $notePayload = null): string
    {
        $payload = [
            'type' => $validated['type'] ?? null,
            'title' => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? null,
            'discount' => $validated['discount'] ?? null,
            'status' => $validated['status'] ?? null,
            'metadata' => $this->normalizeSignaturePayload($this->buildPaperMetadata($request)),
            'note_payload' => $notePayload ? $this->normalizeSignaturePayload($notePayload) : null,
        ];

        if ($request->hasFile('resource_file')) {
            $payload['resource_file_hash'] = hash_file('sha256', $request->file('resource_file')->getRealPath());
        }

        if ($request->hasFile('thumbnail')) {
            $payload['thumbnail_hash'] = hash_file('sha256', $request->file('thumbnail')->getRealPath());
        }

        return hash('sha256', json_encode($this->normalizeSignaturePayload($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function normalizeSignaturePayload(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        if (array_is_list($payload)) {
            return array_map(fn ($item) => $this->normalizeSignaturePayload($item), $payload);
        }

        ksort($payload);

        foreach ($payload as $key => $value) {
            $payload[$key] = $this->normalizeSignaturePayload($value);
        }

        return $payload;
    }

    private function createNoteView($categories, $educationCategories, $paperEducationCategories, $educationTree, array $metadataOptions): View
    {
        $wizardState = $this->buildNoteWizardState();
        $defaults = $wizardState['note'];

        return view('frontend.instructor-dashboard.note-step-one', [
            'product' => new Product([
                'type' => Product::TYPE_NOTE,
                'status' => 'is_draft',
                'price' => $defaults['price'],
                'title' => $defaults['title'],
                'description' => $defaults['description'],
            ]),
            'categories' => $categories,
            'educationCategories' => $educationCategories,
            'paperEducationCategories' => $paperEducationCategories,
            'educationTree' => $educationTree,
            'noteForm' => $defaults,
            'metadataOptions' => $metadataOptions,
            'wizardState' => $wizardState,
            'isEditing' => false,
        ]);
    }

    private function buildNoteWizardState(): array
    {
        $readingNode = fn (string $title = 'Reading Text') => [
            'type' => 'reading',
            'title' => __($title),
            'is_published' => true,
            'blocks' => [
                [
                    'type' => 'rich_text',
                    'content' => ['html' => '<p>' . __('Reading Text') . '</p>'],
                ],
            ],
        ];

        $topicNode = fn (string $title) => [
            'type' => 'topic',
            'title' => __($title),
            'is_published' => true,
            'children' => [
                $readingNode(),
            ],
        ];

        $chapterNode = fn (string $title, int $pages, string $topicTitle) => [
            'type' => 'chapter',
            'title' => __($title),
            'pages' => $pages,
            'is_published' => true,
            'children' => [
                $topicNode($topicTitle),
            ],
        ];

        return [
            'note' => [
                'title' => __('Quadratic Equations - Complete Notes'),
                'description' => __('Detailed notes covering quadratic equations, methods of solving, examples and past exam questions.'),
                'education_level' => 'Senior School (CBC)',
                'class_grade' => 'Grade 10',
                'exam_category' => 'KCSE',
                'subject' => 'STEM',
                'year' => (string) now()->year,
                'topic' => 'Algebra',
                'sub_topic' => __('Quadratic Equations'),
                'tags' => ['KCSE', 'Mathematics', 'Algebra', 'Grade 10'],
                'access_type' => 'paid',
                'price' => '120.00',
                'preview_pages' => 'No preview',
                'language' => 'English',
                'status' => 'is_draft',
                'note_visibility' => 'public',
            ],
            'curriculum' => [],
            'resources' => [],
            'settings' => [
                'allow_download' => true,
                'add_to_bundle' => true,
                'featured_note' => true,
                'allow_comments' => true,
                'visibility' => 'public',
                'meta_title' => __('Quadratic Equations Notes - Grade 10 Mathematics'),
                'meta_description' => __('Comprehensive notes on quadratic equations for Grade 10 KCSE students. Includes formulas, methods, examples, and past questions with solutions.'),
                'keywords' => __('quadratic equations, grade 10, algebra, kcse, math notes'),
                'sort_order' => 10,
            ],
        ];
    }

    private function createNoteContentView(): View
    {
        $wizardState = $this->buildNoteWizardState();
        $chapters = $wizardState['curriculum'];

        return view('frontend.instructor-dashboard.note-step-two', [
            'chapters' => $chapters,
            'wizardState' => $wizardState,
        ]);
    }

    private function createNoteAttachmentsView(): View
    {
        $wizardState = $this->buildNoteWizardState();
        $files = $wizardState['resources'];

        return view('frontend.instructor-dashboard.note-step-three', [
            'files' => $files,
            'wizardState' => $wizardState,
        ]);
    }

    public function uploadNoteAttachments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:pdf,doc,docx,ppt,pptx,zip,mp4,mov,webm', 'max:51200'],
        ]);

        $uploaded = collect($validated['files'] ?? [])
            ->map(fn (UploadedFile $file) => $this->storeNoteAttachmentFile($file))
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'message' => __('Files uploaded successfully.'),
            'files' => $uploaded,
        ]);
    }

    public function deleteNoteAttachment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
        ]);

        $path = ltrim($validated['path'], '/');
        $bunnyPrefix = 'instructors/' . userAuth()->id . '/';
        $legacyPrefix = 'uploads/note-builder/attachments/';

        if (! Str::startsWith($path, [$bunnyPrefix, $legacyPrefix])) {
            abort(403, __('You cannot remove that file.'));
        }

        if (Str::startsWith($path, $bunnyPrefix)) {
            $this->storageService->deletePath($path);
        } else {
            $absolutePath = public_path($path);

            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('File removed successfully.'),
        ]);
    }

    private function createNoteSettingsView(): View
    {
        $wizardState = $this->buildNoteWizardState();

        return view('frontend.instructor-dashboard.note-step-four', [
            'settings' => $wizardState['settings'],
            'wizardState' => $wizardState,
        ]);
    }

    private function createNoteReviewView(): View
    {
        $wizardState = $this->buildNoteWizardState();
        $summary = $this->summarizeNoteWizardCurriculum($wizardState['curriculum'] ?? []);

        return view('frontend.instructor-dashboard.note-step-five', [
            'note' => $wizardState['note'],
            'summary' => $summary,
            'files' => $wizardState['resources'],
            'settings' => $wizardState['settings'],
            'wizardState' => $wizardState,
        ]);
    }

    private function summarizeNoteWizardCurriculum(array $curriculum): array
    {
        $chapters = collect($curriculum)->filter(fn (array $node) => ($node['type'] ?? null) === 'chapter');
        $pages = $chapters->sum(fn (array $node) => (int) ($node['pages'] ?? 0));
        $estimatedWords = $pages > 0 ? number_format($pages * 300) : '0';

        return [
            'chapters' => $chapters->count(),
            'pages' => $pages,
            'words' => $estimatedWords,
            'includes_examples' => $chapters->isNotEmpty(),
            'includes_past_questions' => $chapters->isNotEmpty(),
            'includes_formulas' => $chapters->isNotEmpty(),
            'includes_diagrams' => $chapters->isNotEmpty(),
        ];
    }

    private function createQuizView(): View
    {
        $quizFormData = $this->metadataCatalog->quizDefaults('short');

        return view('frontend.instructor-dashboard.quiz-builder.form', [
            'product' => new Product([
                'type' => Product::TYPE_QUIZ,
                'status' => 'is_draft',
                'price' => 0,
                'title' => __('KCSE Mathematics - Short Quiz'),
                'description' => __('Test your knowledge with a focused short quiz designed for quick revision.'),
                'discount' => 0,
            ]),
            'quizFormData' => $quizFormData,
            'metadataOptions' => $this->metadataCatalog->quizSelectionOptions(),
            'isEditing' => false,
        ]);
    }

    /**
     * Resolve the product type from the current request or previous filtered product page.
     */
    private function resolveSelectedType(Request $request): ?string
    {
        $type = $request->input('type');

        if ($type && isset($this->typeDefinitions[$type])) {
            return $type;
        }

        $previousUrl = url()->previous();

        if (! $previousUrl) {
            return null;
        }

        $previousQuery = parse_url($previousUrl, PHP_URL_QUERY);

        if (! $previousQuery) {
            return null;
        }

        parse_str($previousQuery, $queryParams);
        $previousType = $queryParams['type'] ?? null;

        return $previousType && isset($this->typeDefinitions[$previousType]) ? $previousType : null;
    }

    /**
     * Infer file type from the stored file path so instructors don't have to provide it manually.
     */
    private function inferProductFileType(string $productType, ?string $filePath): ?string
    {
        if (! $filePath) {
            return null;
        }

        $path = parse_url($filePath, PHP_URL_PATH) ?: $filePath;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['doc', 'docx'], true)) {
            return 'docx';
        }

        if ($extension === 'pdf') {
            return 'pdf';
        }

        if (in_array($productType, [Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION], true) && $extension === '') {
            return 'file';
        }

        return $extension !== '' ? $extension : null;
    }

    private function createProductWithSlugRetry(array $data, string $title): Product
    {
        $attempts = 0;

        while (true) {
            try {
                return Product::create($data);
            } catch (QueryException $e) {
                if (! $this->isDuplicateSlugException($e) || $attempts >= 1) {
                    throw $e;
                }

                $data['slug'] = $this->buildCollisionSafeProductSlug($title);
                $attempts++;
            }
        }
    }

    private function updateProductWithSlugRetry(Product $product, array $data, string $title): void
    {
        $attempts = 0;

        while (true) {
            try {
                $product->update($data);
                return;
            } catch (QueryException $e) {
                if (! $this->isDuplicateSlugException($e) || $attempts >= 1) {
                    throw $e;
                }

                $data['slug'] = $this->buildCollisionSafeProductSlug($title, $product->id);
                $attempts++;
            }
        }
    }

    private function isDuplicateSlugException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'products.products_slug_unique')
            || str_contains($e->getMessage(), 'Duplicate entry');
    }

    private function buildCollisionSafeProductSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = generateUniqueSlug(Product::class, $title, $ignoreId);

        if (! Product::where('slug', $slug)->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            return $slug;
        }

        return $slug . '-' . Str::lower(Str::random(6));
    }

    /**
     * Validate product request data.
     */
    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:past_paper,prediction,note,quiz'],
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'resource_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:51200'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', 'in:active,inactive,is_draft'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'class_grade' => ['nullable', 'string', 'max:255'],
            'exam_category' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'paper' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'string', 'max:20'],
            'language' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:255'],
            'access_type' => ['nullable', 'in:paid,free'],
            'preview_pages' => ['nullable', 'string', 'max:255'],
        ], [
            'resource_file.mimes' => __('Unsupported file type. Please upload PDF, DOC, or DOCX.'),
            'resource_file.max' => __('The uploaded file is too large. Maximum size is 50MB.'),
        ]);
    }

    /**
     * Get a product owned by the current instructor.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function getOwnedProduct(int $id): Product
    {
        $product = Product::instructorOwned(userAuth()->id)->findOrFail($id);

        // Verify it's a non-course product
        if ($product->type === 'course') {
            abort(403, __('Instructors cannot manage course-type products through this interface.'));
        }

        return $product;
    }

    /**
     * Upload thumbnail image.
     */
    private function uploadThumbnail(Request $request): string
    {
        $image = $request->file('thumbnail');
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('uploads/products/'), $imageName);

        return 'uploads/products/' . $imageName;
    }

    private function resolveProductFilePath(Request $request, ?string $existingPath = null, ?string $fallbackPath = null): ?string
    {
        if (! empty($fallbackPath)) {
            return $fallbackPath;
        }

        return $existingPath;
    }

    private function storeNoteAttachmentFile(UploadedFile $file): array
    {
        $upload = $this->storageService->uploadAsset($file, [
            'actor_role' => 'instructor',
            'actor_id' => userAuth()->id,
            'product_type' => Product::TYPE_NOTE,
            'product_id' => null,
            'asset_kind' => 'attachments',
        ]);

        return array_merge([
            'title' => $upload['original_name'],
            'resource_type' => $this->inferNoteAttachmentType($upload['extension'] ?? '', $upload['mime_type'] ?? null),
            'url_or_path' => $upload['path'],
            'download_url' => $upload['url'],
            'size' => $this->formatFileSize((int) ($upload['size'] ?? 0)),
            'size_bytes' => (int) ($upload['size'] ?? 0),
            'mime_type' => $upload['mime_type'],
        ], $this->maybeRegisterAiDocument($upload, 'note_attachment'));
    }

    private function inferNoteAttachmentType(string $extension, ?string $mimeType = null): string
    {
        $extension = strtolower($extension);
        $mimeType = strtolower((string) $mimeType);

        if ($extension === 'pdf' || str_contains($mimeType, 'pdf')) {
            return 'pdf';
        }

        if (in_array($extension, ['doc', 'docx'], true) || str_contains($mimeType, 'word')) {
            return 'docx';
        }

        if (in_array($extension, ['ppt', 'pptx'], true) || str_contains($mimeType, 'presentation')) {
            return 'pptx';
        }

        if (in_array($extension, ['zip', 'rar', '7z'], true)) {
            return 'archive';
        }

        if (in_array($extension, ['mp4', 'mov', 'webm'], true) || str_contains($mimeType, 'video')) {
            return 'video';
        }

        return 'file';
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 KB';
        }

        $megabytes = $bytes / (1024 * 1024);

        if ($megabytes >= 1) {
            return number_format($megabytes, $megabytes >= 10 ? 1 : 2) . ' MB';
        }

        return max(1, (int) ceil($bytes / 1024)) . ' KB';
    }

    private function removeStoredFile(string $path): void
    {
        $path = ltrim($path, '/');

        if (Str::startsWith($path, ['instructors/', 'admins/'])) {
            $this->storageService->deletePath($path);

            return;
        }

        $absolutePath = public_path($path);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function maybeRegisterAiDocument(array $upload, string $sourceType): array
    {
        if (strtolower((string) ($upload['extension'] ?? '')) !== 'pdf') {
            return [];
        }

        $document = $this->aiDocumentService->registerUpload(userAuth()->id, $upload, [
            'source_type' => $sourceType,
            'source_name' => $upload['original_name'] ?? 'Document',
            'product_id' => request()->input('product_id') ?: null,
            'product_note_id' => request()->input('product_note_id') ?: null,
        ], false);

        return [
            'ai_document_id' => $document->id,
            'ai_document_status' => $document->status,
        ];
    }

    private function storeProductResourceFile(UploadedFile $file, Product $product, string $sourceName): array
    {
        $upload = $this->storageService->uploadAsset($file, [
            'actor_role' => 'instructor',
            'actor_id' => (int) $product->instructor_id,
            'product_type' => $product->type,
            'product_id' => $product->id,
            'asset_kind' => 'source',
            'source_name' => $sourceName,
        ]);

        return $upload;
    }

    private function syncProductResourceAiDocument(Product $product, ?array $upload = null): void
    {
        if (! in_array($product->type, [Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION], true)) {
            return;
        }

        $resolvedUpload = $upload ?: $this->buildProductResourceUploadFromProduct($product);

        if (! $resolvedUpload) {
            return;
        }

        $extension = strtolower((string) ($resolvedUpload['extension'] ?? ''));
        if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            return;
        }

        $sourceType = 'product_resource';
        $existingDocuments = AiDocument::query()
            ->where('product_id', $product->id)
            ->where('source_type', $sourceType)
            ->get();

        $matchingDocument = $existingDocuments->first(function (AiDocument $document) use ($resolvedUpload, $extension) {
            return $document->original_path === ($resolvedUpload['path'] ?? null)
                && strtolower((string) $document->file_extension) === $extension;
        });

        if ($matchingDocument) {
            return;
        }

        $existingDocuments->each(function (AiDocument $document) {
            $this->aiDocumentService->deleteDocument($document);
        });

        $this->aiDocumentService->registerUpload((int) $product->instructor_id, $resolvedUpload, [
            'product_id' => $product->id,
            'product_type' => $product->type,
            'asset_kind' => 'source',
            'source_type' => $sourceType,
            'source_name' => $product->title,
        ], false);
    }

    private function buildProductResourceUploadFromProduct(Product $product): ?array
    {
        $path = trim((string) $product->file_path);
        if ($path === '') {
            return null;
        }

        $extension = $this->resolveFileExtensionFromPath($path);
        if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            return null;
        }

        $originalName = basename(parse_url($path, PHP_URL_PATH) ?: $path);
        if ($originalName === '') {
            $originalName = $product->title . '.' . $extension;
        }

        return [
            'path' => $path,
            'folder_path' => dirname($path),
            'url' => $this->storageService->publicUrl($path),
            'original_name' => $originalName,
            'mime_type' => $this->guessMimeTypeForExtension($extension),
            'extension' => $extension,
            'size' => null,
            'hash' => hash('sha256', $path),
            'context' => [
                'product_id' => $product->id,
                'product_type' => $product->type,
                'source_type' => 'product_resource',
            ],
        ];
    }

    private function resolveFileExtensionFromPath(string $path): string
    {
        $normalizedPath = parse_url($path, PHP_URL_PATH) ?: $path;

        return strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
    }

    private function guessMimeTypeForExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }

    private function buildPaperMetadata(Request $request): array
    {
        $productType = $request->input('type');
        $defaults = $this->metadataCatalog->sharedDefaults();
        $tags = [];
        $educationLevel = (string) $request->input('education_level', $defaults['education_level']);
        $subjectKey = $this->paperSubjectMetadataKey($educationLevel);
        $subjectValue = $request->input($subjectKey, $request->input($subjectKey === 'subject' ? 'course' : 'subject', $defaults['subject']));

        if (! in_array($productType, [Product::TYPE_PAST_PAPER], true)) {
            $tags = collect(explode(',', (string) $request->input('tags', '')))
                ->map(fn ($tag) => trim($tag))
                ->filter()
                ->values()
                ->all();
        }

        $paper = in_array($productType, [Product::TYPE_PAST_PAPER], true) ? null : $request->input('paper');

        $metadata = array_filter([
            'education_level' => $educationLevel,
            'class_grade' => $request->input('class_grade', $defaults['class_grade']),
            'exam_category' => $request->input('exam_category', $defaults['exam_category']),
            'paper' => $paper,
            'year' => $request->input('year', $defaults['year']),
            'language' => $request->input('language', $defaults['language']),
            'tags' => $tags,
            'access_type' => $request->input('access_type', 'paid'),
            'preview_pages' => $request->input('preview_pages'),
            'topic' => $request->input('topic'),
            'sub_topic' => $request->input('sub_topic'),
            'note_visibility' => $request->input('note_visibility', $request->input('visibility', 'draft')),
            $subjectKey => $subjectValue,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->identityService->stampMetadata($metadata, [
            'type' => $productType,
            'education_level' => $metadata['education_level'] ?? null,
            'class_grade' => $metadata['class_grade'] ?? null,
            'exam_category' => $metadata['exam_category'] ?? null,
            'subject' => $metadata['subject'] ?? null,
            'course' => $metadata['course'] ?? null,
            'paper' => $metadata['paper'] ?? null,
            'year' => $metadata['year'] ?? null,
            'language' => $metadata['language'] ?? null,
        ]);
    }

    private function resolvePaperFormDefaults(Request $request, ?string $type = null): array
    {
        $isPrediction = $type === Product::TYPE_PREDICTION;
        $defaults = $this->metadataCatalog->paperDefaults();
        $educationLevel = old('education_level', $request->input('education_level', $defaults['education_level']));
        $subjectValue = old('subject', old('course', $request->input('subject', $request->input('course', $defaults['subject']))));

        return [
            'education_level' => $educationLevel,
            'class_grade' => old('class_grade', $request->input('class_grade', $defaults['class_grade'])),
            'exam_category' => old('exam_category', $request->input('exam_category', $defaults['exam_category'])),
            'subject' => $subjectValue,
            'course' => $subjectValue,
            'paper' => old('paper', $request->input('paper', $isPrediction ? __('Prediction Pack') : $defaults['paper'])),
            'year' => old('year', $request->input('year', $defaults['year'])),
            'language' => old('language', $request->input('language', $defaults['language'])),
            'tags' => old('tags', $request->input('tags', $isPrediction ? 'KCSE, Mathematics, Prediction Pack, ' . now()->year : 'KCSE, Mathematics, ' . now()->year)),
            'access_type' => old('access_type', $request->input('access_type', 'paid')),
            'preview_pages' => old('preview_pages', $request->input('preview_pages', $defaults['preview_pages'])),
            'file_path' => old('file_path', ''),
        ];
    }

    private function productToPaperForm(Product $product): array
    {
        $metadata = $product->metadata ?? [];
        $isPrediction = $product->type === Product::TYPE_PREDICTION;
        $defaults = $this->metadataCatalog->paperDefaults();
        $educationLevel = old('education_level', $metadata['education_level'] ?? $defaults['education_level']);
        $subjectValue = old('subject', old('course', $this->resolvePaperSubjectValue($metadata, $educationLevel, $defaults['subject'])));

        return [
            'education_level' => $educationLevel,
            'class_grade' => old('class_grade', $metadata['class_grade'] ?? $defaults['class_grade']),
            'exam_category' => old('exam_category', $metadata['exam_category'] ?? $defaults['exam_category']),
            'subject' => $subjectValue,
            'course' => $subjectValue,
            'paper' => old('paper', $metadata['paper'] ?? ($isPrediction ? __('Prediction Pack') : $defaults['paper'])),
            'year' => old('year', $metadata['year'] ?? $defaults['year']),
            'language' => old('language', $metadata['language'] ?? $defaults['language']),
            'tags' => old('tags', isset($metadata['tags']) ? implode(', ', $metadata['tags']) : ($isPrediction ? 'KCSE, Mathematics, Prediction Pack, ' . now()->year : 'KCSE, Mathematics, ' . now()->year)),
            'access_type' => old('access_type', $metadata['access_type'] ?? $defaults['access_type']),
            'preview_pages' => old('preview_pages', $metadata['preview_pages'] ?? $defaults['preview_pages']),
            'file_path' => old('file_path', $product->file_path ?? ''),
        ];
    }

    private function paperSubjectMetadataKey(?string $educationLevel): string
    {
        return $this->usesPaperCourseField($educationLevel) ? 'course' : 'subject';
    }

    private function usesPaperCourseField(?string $educationLevel): bool
    {
        $level = strtolower(trim((string) $educationLevel));

        return preg_match('/tvet|university|college|certificate|diploma|undergraduate|professional|tertiary|higher education/', $level) === 1;
    }

    private function resolvePaperSubjectValue(array $metadata, ?string $educationLevel, ?string $fallback = null): ?string
    {
        if ($this->usesPaperCourseField($educationLevel)) {
            return $metadata['course'] ?? $metadata['subject'] ?? $fallback;
        }

        return $metadata['subject'] ?? $metadata['course'] ?? $fallback;
    }

    /**
     * Education root categories shown in the instructor paper form.
     */
    private function educationRootCategorySlugs(): array
    {
        return [
            'pre-primary',
            'lower-primary',
            'upper-primary',
            'junior-school',
            'senior-school-cbc',
            'high-school',
            'tvet',
            'certificate-courses',
            'diploma-courses',
            'undergraduate',
            'professional-courses',
            'teacher-resources',
        ];
    }

    private function paperEducationLevelDefinitions(): array
    {
        return $this->metadataCatalog->educationLevelDefinitions();
    }

    private function buildPaperEducationRootCategories(Collection $categories): array
    {
        $allRoots = $this->educationRootCategories($categories)->keyBy('slug');

        return collect($this->paperEducationLevelDefinitions())
            ->map(function (array $definition) use ($allRoots) {
                $sourceCategory = $allRoots->first(fn ($category) => in_array($category->slug, $definition['source_slugs'], true));

                return (object) [
                    'slug' => $definition['slug'],
                    'label' => $definition['label'],
                    'source_slugs' => $definition['source_slugs'],
                    'translation' => (object) ['name' => $definition['label']],
                    'name' => $definition['label'],
                    'parent_id' => null,
                    'id' => $definition['slug'],
                    'category_id' => $sourceCategory?->category_id,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Filter the active categories down to the education roots only.
     */
    private function educationRootCategories(?Collection $categories = null): Collection
    {
        $source = $categories ?? CourseCategory::active()->get();
        $allowedSlugs = $this->educationRootCategorySlugs();

        return collect($source)
            ->filter(fn ($category) => blank($category->parent_id) && in_array($category->slug, $allowedSlugs, true))
            ->sortBy(fn ($category) => array_search($category->slug, $allowedSlugs, true))
            ->values();
    }

    /**
     * Build a lightweight nested tree for the paper form's dependent selects.
     */
    private function buildPaperEducationTreeForForm(Collection $categories): array
    {
        $byParent = $categories->groupBy(fn ($category) => $category->parent_id ? (string) $category->parent_id : 'root');
        $rootDefinitions = $this->paperEducationLevelDefinitions();
        $labelOverrides = [
            'human-resource' => 'HR',
            'early-childhood-education' => 'ECE',
            'nursing' => 'Health',
            'microsoft' => 'Microsoft Azure',
        ];
        $commonSubjectsNode = [
            'id' => 'senior-school-cbc-common-subjects',
            'slug' => 'common-subjects',
            'label' => 'Common Subjects',
            'children' => [],
        ];
        $ictAndComputingCourseChildren = [
            ['id' => 'certificate-ict-computing-ict', 'slug' => 'information-communication-technology-ict', 'label' => 'Certificate in Information Communication Technology (ICT)'],
            ['id' => 'certificate-ict-computing-computer-science', 'slug' => 'computer-science', 'label' => 'Certificate in Computer Science'],
            ['id' => 'certificate-ict-computing-software-development', 'slug' => 'software-development', 'label' => 'Certificate in Software Development'],
            ['id' => 'certificate-ict-computing-computer-packages', 'slug' => 'computer-packages', 'label' => 'Certificate in Computer Packages'],
            ['id' => 'certificate-ict-computing-networking', 'slug' => 'networking', 'label' => 'Certificate in Networking'],
            ['id' => 'certificate-ict-computing-cyber-security', 'slug' => 'cyber-security', 'label' => 'Certificate in Cyber Security'],
            ['id' => 'certificate-ict-computing-web-development', 'slug' => 'web-development', 'label' => 'Certificate in Web Development'],
        ];
        $businessCourseChildren = [
            ['id' => 'certificate-business-accounting', 'slug' => 'accounting', 'label' => 'Certificate in Accounting'],
            ['id' => 'certificate-business-business-management', 'slug' => 'business-management', 'label' => 'Certificate in Business Management'],
            ['id' => 'certificate-business-human-resource-management', 'slug' => 'human-resource-management', 'label' => 'Certificate in Human Resource Management'],
            ['id' => 'certificate-business-procurement-supply-chain', 'slug' => 'procurement-supply-chain', 'label' => 'Certificate in Procurement & Supply Chain'],
            ['id' => 'certificate-business-banking-finance', 'slug' => 'banking-finance', 'label' => 'Certificate in Banking & Finance'],
            ['id' => 'certificate-business-marketing', 'slug' => 'marketing', 'label' => 'Certificate in Marketing'],
            ['id' => 'certificate-business-secretarial-studies', 'slug' => 'secretarial-studies', 'label' => 'Certificate in Secretarial Studies'],
            ['id' => 'certificate-business-office-administration', 'slug' => 'office-administration', 'label' => 'Certificate in Office Administration'],
            ['id' => 'certificate-business-entrepreneurship', 'slug' => 'entrepreneurship', 'label' => 'Certificate in Entrepreneurship'],
        ];
        $engineeringCourseChildren = [
            ['id' => 'certificate-engineering-electrical-installation', 'slug' => 'electrical-installation', 'label' => 'Electrical Installation'],
            ['id' => 'certificate-engineering-electrical-electronics-engineering', 'slug' => 'electrical-electronics-engineering', 'label' => 'Electrical & Electronics Engineering'],
            ['id' => 'certificate-engineering-power-engineering', 'slug' => 'power-engineering', 'label' => 'Power Engineering'],
            ['id' => 'certificate-engineering-mechanical-engineering', 'slug' => 'mechanical-engineering', 'label' => 'Mechanical Engineering'],
            ['id' => 'certificate-engineering-automotive-engineering', 'slug' => 'automotive-engineering', 'label' => 'Automotive Engineering'],
            ['id' => 'certificate-engineering-plant-engineering', 'slug' => 'plant-engineering', 'label' => 'Plant Engineering'],
            ['id' => 'certificate-engineering-production-engineering', 'slug' => 'production-engineering', 'label' => 'Production Engineering'],
            ['id' => 'certificate-engineering-building-technology', 'slug' => 'building-technology', 'label' => 'Building Technology'],
            ['id' => 'certificate-engineering-civil-engineering', 'slug' => 'civil-engineering', 'label' => 'Civil Engineering'],
            ['id' => 'certificate-engineering-construction-technology', 'slug' => 'construction-technology', 'label' => 'Construction Technology'],
            ['id' => 'certificate-engineering-masonry-plumbing', 'slug' => 'masonry-plumbing', 'label' => 'Masonry Plumbing'],
            ['id' => 'certificate-engineering-welding-fabrication', 'slug' => 'welding-fabrication', 'label' => 'Welding & Fabrication'],
            ['id' => 'certificate-engineering-refrigeration-air-conditioning', 'slug' => 'refrigeration-air-conditioning', 'label' => 'Refrigeration & Air Conditioning'],
            ['id' => 'certificate-engineering-mechatronics', 'slug' => 'mechatronics', 'label' => 'Mechatronics'],
            ['id' => 'certificate-engineering-water-engineering', 'slug' => 'water-engineering', 'label' => 'Water Engineering'],
        ];
        $agricultureCourseChildren = [
            ['id' => 'certificate-agriculture-general-agriculture', 'slug' => 'general-agriculture', 'label' => 'General Agriculture'],
            ['id' => 'certificate-agriculture-agribusiness', 'slug' => 'agribusiness', 'label' => 'Agribusiness'],
            ['id' => 'certificate-agriculture-animal-production', 'slug' => 'animal-production', 'label' => 'Animal Production'],
            ['id' => 'certificate-agriculture-dairy-technology', 'slug' => 'dairy-technology', 'label' => 'Dairy Technology'],
            ['id' => 'certificate-agriculture-horticulture', 'slug' => 'horticulture', 'label' => 'Horticulture'],
            ['id' => 'certificate-agriculture-crop-production', 'slug' => 'crop-production', 'label' => 'Crop Production'],
            ['id' => 'certificate-agriculture-irrigation-technology', 'slug' => 'irrigation-technology', 'label' => 'Irrigation Technology'],
            ['id' => 'certificate-agriculture-environmental-conservation', 'slug' => 'environmental-conservation', 'label' => 'Environmental Conservation'],
        ];
        $healthSciencesCourseChildren = [
            ['id' => 'certificate-health-community-health', 'slug' => 'community-health', 'label' => 'Community Health'],
            ['id' => 'certificate-health-health-records-information', 'slug' => 'health-records-information', 'label' => 'Health Records & Information'],
            ['id' => 'certificate-health-nutrition-dietetics', 'slug' => 'nutrition-dietetics', 'label' => 'Nutrition & Dietetics'],
            ['id' => 'certificate-health-public-health', 'slug' => 'public-health', 'label' => 'Public Health'],
            ['id' => 'certificate-health-medical-laboratory-assistant', 'slug' => 'medical-laboratory-assistant', 'label' => 'Medical Laboratory Assistant'],
            ['id' => 'certificate-health-orthopaedic-trauma-technology', 'slug' => 'orthopaedic-trauma-technology', 'label' => 'Orthopaedic & Trauma Technology'],
            ['id' => 'certificate-health-perioperative-theatre-technology', 'slug' => 'perioperative-theatre-technology', 'label' => 'Perioperative Theatre Technology'],
            ['id' => 'certificate-health-emergency-medical-technician', 'slug' => 'emergency-medical-technician', 'label' => 'Emergency Medical Technician (EMT)'],
        ];
        $hospitalityTourismCourseChildren = [
            ['id' => 'certificate-hospitality-food-production', 'slug' => 'food-production', 'label' => 'Food Production'],
            ['id' => 'certificate-hospitality-catering', 'slug' => 'catering', 'label' => 'Catering'],
            ['id' => 'certificate-hospitality-hospitality-management', 'slug' => 'hospitality-management', 'label' => 'Hospitality Management'],
            ['id' => 'certificate-hospitality-hotel-management', 'slug' => 'hotel-management', 'label' => 'Hotel Management'],
            ['id' => 'certificate-hospitality-housekeeping', 'slug' => 'housekeeping', 'label' => 'Housekeeping'],
            ['id' => 'certificate-hospitality-front-office-operations', 'slug' => 'front-office-operations', 'label' => 'Front Office Operations'],
            ['id' => 'certificate-hospitality-travel-tourism', 'slug' => 'travel-tourism', 'label' => 'Travel & Tourism'],
            ['id' => 'certificate-hospitality-tour-guiding', 'slug' => 'tour-guiding', 'label' => 'Tour Guiding'],
        ];
        $fashionDesignBeautyCourseChildren = [
            ['id' => 'certificate-fashion-design-beauty-fashion-design', 'slug' => 'fashion-design', 'label' => 'Fashion Design'],
            ['id' => 'certificate-fashion-design-beauty-garment-making', 'slug' => 'garment-making', 'label' => 'Garment Making'],
            ['id' => 'certificate-fashion-design-beauty-tailoring', 'slug' => 'tailoring', 'label' => 'Tailoring'],
            ['id' => 'certificate-fashion-design-beauty-hair-dressing', 'slug' => 'hair-dressing', 'label' => 'Hair Dressing'],
            ['id' => 'certificate-fashion-design-beauty-beauty-therapy', 'slug' => 'beauty-therapy', 'label' => 'Beauty Therapy'],
            ['id' => 'certificate-fashion-design-beauty-cosmetology', 'slug' => 'cosmetology', 'label' => 'Cosmetology'],
            ['id' => 'certificate-fashion-design-beauty-barbering', 'slug' => 'barbering', 'label' => 'Barbering'],
        ];
        $buildingConstructionCourseChildren = [
            ['id' => 'certificate-building-construction-building-technology', 'slug' => 'building-technology', 'label' => 'Building Technology'],
            ['id' => 'certificate-building-construction-quantity-survey-assistance', 'slug' => 'quantity-survey-assistance', 'label' => 'Quantity Survey Assistance'],
            ['id' => 'certificate-building-construction-carpentry-joinery', 'slug' => 'carpentry-joinery', 'label' => 'Carpentry & Joinery'],
            ['id' => 'certificate-building-construction-masonry', 'slug' => 'masonry', 'label' => 'Masonry'],
            ['id' => 'certificate-building-construction-painting-decoration', 'slug' => 'painting-decoration', 'label' => 'Painting & Decoration'],
            ['id' => 'certificate-building-construction-tiling', 'slug' => 'tiling', 'label' => 'Tiling'],
            ['id' => 'certificate-building-construction-plumbing', 'slug' => 'plumbing', 'label' => 'Plumbing'],
        ];
        $journalismMediaCommunicationCourseChildren = [
            ['id' => 'certificate-journalism-journalism', 'slug' => 'journalism', 'label' => 'Journalism'],
            ['id' => 'certificate-journalism-mass-communication', 'slug' => 'mass-communication', 'label' => 'Mass Communication'],
            ['id' => 'certificate-journalism-public-relations', 'slug' => 'public-relations', 'label' => 'Public Relations'],
            ['id' => 'certificate-journalism-digital-media', 'slug' => 'digital-media', 'label' => 'Digital Media'],
            ['id' => 'certificate-journalism-photography', 'slug' => 'photography', 'label' => 'Photography'],
            ['id' => 'certificate-journalism-videography', 'slug' => 'videography', 'label' => 'Videography'],
            ['id' => 'certificate-journalism-film-production', 'slug' => 'film-production', 'label' => 'Film Production'],
        ];
        $educationEcdeCourseChildren = [
            ['id' => 'certificate-education-ecde-ecde', 'slug' => 'ecde', 'label' => 'Early Childhood Development Education (ECDE)'],
            ['id' => 'certificate-education-ecde-teacher-education', 'slug' => 'teacher-education', 'label' => 'Teacher Education (where offered)'],
        ];
        $socialSciencesCourseChildren = [
            ['id' => 'certificate-social-sciences-community-development', 'slug' => 'community-development', 'label' => 'Community Development'],
            ['id' => 'certificate-social-sciences-social-work', 'slug' => 'social-work', 'label' => 'Social Work'],
            ['id' => 'certificate-social-sciences-counselling-psychology', 'slug' => 'counselling-psychology', 'label' => 'Counselling Psychology'],
            ['id' => 'certificate-social-sciences-criminology', 'slug' => 'criminology', 'label' => 'Criminology'],
            ['id' => 'certificate-social-sciences-peace-conflict-studies', 'slug' => 'peace-conflict-studies', 'label' => 'Peace & Conflict Studies'],
        ];
        $languagesCourseChildren = [
            ['id' => 'certificate-languages-english', 'slug' => 'english', 'label' => 'English'],
            ['id' => 'certificate-languages-kiswahili', 'slug' => 'kiswahili', 'label' => 'Kiswahili'],
            ['id' => 'certificate-languages-french', 'slug' => 'french', 'label' => 'French'],
            ['id' => 'certificate-languages-german', 'slug' => 'german', 'label' => 'German'],
            ['id' => 'certificate-languages-arabic', 'slug' => 'arabic', 'label' => 'Arabic'],
            ['id' => 'certificate-languages-chinese', 'slug' => 'chinese', 'label' => 'Chinese'],
        ];
        $liberalStudiesCourseChildren = [
            ['id' => 'certificate-liberal-studies-communication-skills', 'slug' => 'communication-skills', 'label' => 'Communication Skills'],
            ['id' => 'certificate-liberal-studies-life-skills', 'slug' => 'life-skills', 'label' => 'Life Skills'],
            ['id' => 'certificate-liberal-studies-leadership', 'slug' => 'leadership', 'label' => 'Leadership'],
            ['id' => 'certificate-liberal-studies-ethics', 'slug' => 'ethics', 'label' => 'Ethics'],
            ['id' => 'certificate-liberal-studies-entrepreneurship', 'slug' => 'entrepreneurship', 'label' => 'Entrepreneurship'],
        ];
        $creativeArtsCourseChildren = [
            ['id' => 'certificate-creative-arts-fine-art', 'slug' => 'fine-art', 'label' => 'Fine Art'],
            ['id' => 'certificate-creative-arts-graphic-design', 'slug' => 'graphic-design', 'label' => 'Graphic Design'],
            ['id' => 'certificate-creative-arts-music', 'slug' => 'music', 'label' => 'Music'],
            ['id' => 'certificate-creative-arts-theatre-arts', 'slug' => 'theatre-arts', 'label' => 'Theatre Arts'],
            ['id' => 'certificate-creative-arts-performing-arts', 'slug' => 'performing-arts', 'label' => 'Performing Arts'],
            ['id' => 'certificate-creative-arts-interior-design', 'slug' => 'interior-design', 'label' => 'Interior Design'],
        ];
        $transportLogisticsCourseChildren = [
            ['id' => 'certificate-transport-logistics-driving-instruction', 'slug' => 'driving-instruction', 'label' => 'Driving Instruction'],
            ['id' => 'certificate-transport-logistics-logistics-management', 'slug' => 'logistics-management', 'label' => 'Logistics Management'],
            ['id' => 'certificate-transport-logistics-clearing-forwarding', 'slug' => 'clearing-forwarding', 'label' => 'Clearing & Forwarding'],
            ['id' => 'certificate-transport-logistics-warehousing', 'slug' => 'warehousing', 'label' => 'Warehousing'],
            ['id' => 'certificate-transport-logistics-fleet-management', 'slug' => 'fleet-management', 'label' => 'Fleet Management'],
        ];
        $maritimeStudiesCourseChildren = [
            ['id' => 'certificate-maritime-studies-maritime-transport', 'slug' => 'maritime-transport', 'label' => 'Maritime Transport'],
            ['id' => 'certificate-maritime-studies-port-operations', 'slug' => 'port-operations', 'label' => 'Port Operations'],
            ['id' => 'certificate-maritime-studies-shipping-logistics', 'slug' => 'shipping-logistics', 'label' => 'Shipping & Logistics'],
            ['id' => 'certificate-maritime-studies-marine-engineering-basics', 'slug' => 'marine-engineering-basics', 'label' => 'Marine Engineering Basics'],
        ];
        $schoolOnlyNodesByLevel = [
            'tvet' => [
                ['id' => 'tvet-school-business', 'slug' => 'business', 'label' => 'School of Business'],
                ['id' => 'tvet-school-computing', 'slug' => 'computing', 'label' => 'School of Computing'],
                ['id' => 'tvet-school-engineering', 'slug' => 'engineering', 'label' => 'School of Engineering'],
                ['id' => 'tvet-school-health', 'slug' => 'health', 'label' => 'School of Health'],
                ['id' => 'tvet-school-hospitality', 'slug' => 'hospitality', 'label' => 'School of Hospitality'],
                ['id' => 'tvet-school-arts', 'slug' => 'arts', 'label' => 'School of Arts'],
                ['id' => 'tvet-school-social-sciences', 'slug' => 'social-sciences', 'label' => 'School of Social Sciences'],
            ],
            'certificate-courses' => [
                ['id' => 'certificate-courses-ict-computing', 'slug' => 'ict-computing', 'label' => 'School of ICT & Computing', 'children' => $ictAndComputingCourseChildren],
                ['id' => 'certificate-courses-business-studies', 'slug' => 'business-studies', 'label' => 'School of Business Studies', 'children' => $businessCourseChildren],
                ['id' => 'certificate-courses-engineering', 'slug' => 'engineering', 'label' => 'School of Engineering', 'children' => $engineeringCourseChildren],
                ['id' => 'certificate-courses-agriculture-environmental-studies', 'slug' => 'agriculture-environmental-studies', 'label' => 'School of Agriculture & Environmental Studies', 'children' => $agricultureCourseChildren],
                ['id' => 'certificate-courses-health-sciences', 'slug' => 'health-sciences', 'label' => 'School of Health Sciences', 'children' => $healthSciencesCourseChildren],
                ['id' => 'certificate-courses-hospitality-tourism', 'slug' => 'hospitality-tourism', 'label' => 'School of Hospitality & Tourism', 'children' => $hospitalityTourismCourseChildren],
                ['id' => 'certificate-courses-fashion-design-beauty', 'slug' => 'fashion-design-beauty', 'label' => 'School of Fashion Design & Beauty', 'children' => $fashionDesignBeautyCourseChildren],
                ['id' => 'certificate-courses-building-construction', 'slug' => 'building-construction', 'label' => 'School of Building & Construction', 'children' => $buildingConstructionCourseChildren],
                ['id' => 'certificate-courses-journalism-media-communication', 'slug' => 'journalism-media-communication', 'label' => 'School of Journalism, Media & Communication', 'children' => $journalismMediaCommunicationCourseChildren],
                ['id' => 'certificate-courses-education-ecde', 'slug' => 'education-ecde', 'label' => 'School of Education (ECDE)', 'children' => $educationEcdeCourseChildren],
                ['id' => 'certificate-courses-social-sciences', 'slug' => 'social-sciences', 'label' => 'School of Social Sciences', 'children' => $socialSciencesCourseChildren],
                ['id' => 'certificate-courses-languages', 'slug' => 'languages', 'label' => 'School of Languages', 'children' => $languagesCourseChildren],
                ['id' => 'certificate-courses-liberal-studies', 'slug' => 'liberal-studies', 'label' => 'School of Liberal Studies', 'children' => $liberalStudiesCourseChildren],
                ['id' => 'certificate-courses-creative-arts', 'slug' => 'creative-arts', 'label' => 'School of Creative Arts', 'children' => $creativeArtsCourseChildren],
                ['id' => 'certificate-courses-transport-logistics', 'slug' => 'transport-logistics', 'label' => 'School of Transport & Logistics', 'children' => $transportLogisticsCourseChildren],
                ['id' => 'certificate-courses-maritime-studies', 'slug' => 'maritime-studies', 'label' => 'School of Maritime Studies', 'children' => $maritimeStudiesCourseChildren],
            ],
            'diploma-courses' => [
                ['id' => 'diploma-courses-ict-computing', 'slug' => 'ict-computing', 'label' => 'School of ICT & Computing', 'children' => $ictAndComputingCourseChildren],
                ['id' => 'diploma-courses-business-studies', 'slug' => 'business-studies', 'label' => 'School of Business Studies', 'children' => $businessCourseChildren],
                ['id' => 'diploma-courses-engineering', 'slug' => 'engineering', 'label' => 'School of Engineering', 'children' => $engineeringCourseChildren],
                ['id' => 'diploma-courses-agriculture-environmental-studies', 'slug' => 'agriculture-environmental-studies', 'label' => 'School of Agriculture & Environmental Studies', 'children' => $agricultureCourseChildren],
                ['id' => 'diploma-courses-health-sciences', 'slug' => 'health-sciences', 'label' => 'School of Health Sciences', 'children' => $healthSciencesCourseChildren],
                ['id' => 'diploma-courses-hospitality-tourism', 'slug' => 'hospitality-tourism', 'label' => 'School of Hospitality & Tourism', 'children' => $hospitalityTourismCourseChildren],
                ['id' => 'diploma-courses-fashion-design-beauty', 'slug' => 'fashion-design-beauty', 'label' => 'School of Fashion Design & Beauty', 'children' => $fashionDesignBeautyCourseChildren],
                ['id' => 'diploma-courses-building-construction', 'slug' => 'building-construction', 'label' => 'School of Building & Construction', 'children' => $buildingConstructionCourseChildren],
                ['id' => 'diploma-courses-journalism-media-communication', 'slug' => 'journalism-media-communication', 'label' => 'School of Journalism, Media & Communication', 'children' => $journalismMediaCommunicationCourseChildren],
                ['id' => 'diploma-courses-education-ecde', 'slug' => 'education-ecde', 'label' => 'School of Education (ECDE)', 'children' => $educationEcdeCourseChildren],
                ['id' => 'diploma-courses-social-sciences', 'slug' => 'social-sciences', 'label' => 'School of Social Sciences', 'children' => $socialSciencesCourseChildren],
                ['id' => 'diploma-courses-languages', 'slug' => 'languages', 'label' => 'School of Languages', 'children' => $languagesCourseChildren],
                ['id' => 'diploma-courses-liberal-studies', 'slug' => 'liberal-studies', 'label' => 'School of Liberal Studies', 'children' => $liberalStudiesCourseChildren],
                ['id' => 'diploma-courses-creative-arts', 'slug' => 'creative-arts', 'label' => 'School of Creative Arts', 'children' => $creativeArtsCourseChildren],
                ['id' => 'diploma-courses-transport-logistics', 'slug' => 'transport-logistics', 'label' => 'School of Transport & Logistics', 'children' => $transportLogisticsCourseChildren],
                ['id' => 'diploma-courses-maritime-studies', 'slug' => 'maritime-studies', 'label' => 'School of Maritime Studies', 'children' => $maritimeStudiesCourseChildren],
            ],
            'undergraduate' => [
                ['id' => 'undergraduate-ict-computing', 'slug' => 'ict-computing', 'label' => 'School of ICT & Computing', 'children' => $ictAndComputingCourseChildren],
                ['id' => 'undergraduate-business-studies', 'slug' => 'business-studies', 'label' => 'School of Business Studies', 'children' => $businessCourseChildren],
                ['id' => 'undergraduate-engineering', 'slug' => 'engineering', 'label' => 'School of Engineering', 'children' => $engineeringCourseChildren],
                ['id' => 'undergraduate-agriculture-environmental-studies', 'slug' => 'agriculture-environmental-studies', 'label' => 'School of Agriculture & Environmental Studies', 'children' => $agricultureCourseChildren],
                ['id' => 'undergraduate-health-sciences', 'slug' => 'health-sciences', 'label' => 'School of Health Sciences', 'children' => $healthSciencesCourseChildren],
                ['id' => 'undergraduate-hospitality-tourism', 'slug' => 'hospitality-tourism', 'label' => 'School of Hospitality & Tourism', 'children' => $hospitalityTourismCourseChildren],
                ['id' => 'undergraduate-fashion-design-beauty', 'slug' => 'fashion-design-beauty', 'label' => 'School of Fashion Design & Beauty', 'children' => $fashionDesignBeautyCourseChildren],
                ['id' => 'undergraduate-building-construction', 'slug' => 'building-construction', 'label' => 'School of Building & Construction', 'children' => $buildingConstructionCourseChildren],
                ['id' => 'undergraduate-journalism-media-communication', 'slug' => 'journalism-media-communication', 'label' => 'School of Journalism, Media & Communication', 'children' => $journalismMediaCommunicationCourseChildren],
                ['id' => 'undergraduate-education-ecde', 'slug' => 'education-ecde', 'label' => 'School of Education (ECDE)', 'children' => $educationEcdeCourseChildren],
                ['id' => 'undergraduate-social-sciences', 'slug' => 'social-sciences', 'label' => 'School of Social Sciences', 'children' => $socialSciencesCourseChildren],
                ['id' => 'undergraduate-languages', 'slug' => 'languages', 'label' => 'School of Languages', 'children' => $languagesCourseChildren],
                ['id' => 'undergraduate-liberal-studies', 'slug' => 'liberal-studies', 'label' => 'School of Liberal Studies', 'children' => $liberalStudiesCourseChildren],
                ['id' => 'undergraduate-creative-arts', 'slug' => 'creative-arts', 'label' => 'School of Creative Arts', 'children' => $creativeArtsCourseChildren],
                ['id' => 'undergraduate-transport-logistics', 'slug' => 'transport-logistics', 'label' => 'School of Transport & Logistics', 'children' => $transportLogisticsCourseChildren],
                ['id' => 'undergraduate-maritime-studies', 'slug' => 'maritime-studies', 'label' => 'School of Maritime Studies', 'children' => $maritimeStudiesCourseChildren],
            ],
        ];
        $seniorSchoolSubjects = [
            ['slug' => 'stem', 'name' => 'STEM'],
            ['slug' => 'social-sciences', 'name' => 'Social Sciences'],
            ['slug' => 'arts-and-sports', 'name' => 'Arts & Sports'],
            ['slug' => 'languages', 'name' => 'Languages'],
        ];
        $seniorSchoolGrades = collect([
            ['slug' => 'grade-10', 'name' => 'Grade 10'],
            ['slug' => 'grade-11', 'name' => 'Grade 11'],
            ['slug' => 'grade-12', 'name' => 'Grade 12'],
        ])
            ->map(function (array $grade) use ($seniorSchoolSubjects) {
                return [
                    'id' => $grade['slug'],
                    'slug' => $grade['slug'],
                    'label' => $grade['name'],
                    'children' => collect($seniorSchoolSubjects)
                        ->map(fn (array $subject) => [
                            'id' => $grade['slug'] . '-' . $subject['slug'],
                            'slug' => $subject['slug'],
                            'label' => $subject['name'],
                            'children' => [],
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
        $professionalCoursesCfaNode = [
            'id' => 'professional-courses-cfa',
            'slug' => 'cfa',
            'label' => 'CFA',
            'children' => [],
        ];
        $professionalCoursesComptiaNode = [
            'id' => 'professional-courses-comptia',
            'slug' => 'comptia',
            'label' => 'CompTIA',
            'children' => [],
        ];
        $kcseSubjects = $this->metadataCatalog->quizSelectionOptions()['subjects'];
        $kcseNode = [
            'id' => 'high-school-kcse',
            'slug' => 'kcse',
            'label' => 'KCSE',
            'children' => array_map(
                fn (string $subject) => [
                    'id' => 'high-school-kcse-' . Str::slug($subject),
                    'slug' => Str::slug($subject),
                    'label' => $subject,
                    'children' => [],
                ],
                $kcseSubjects
            ),
        ];
        $lowerPrimarySubjects = collect(['PP1', 'PP2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4'])
            ->map(fn (string $subject) => [
                'name' => $subject,
                'slug' => Str::slug($subject),
            ])
            ->values()
            ->all();
        $upperPrimarySubjects = collect(['Grade 5', 'Grade 6'])
            ->map(fn (string $subject) => [
                'name' => $subject,
                'slug' => Str::slug($subject),
            ])
            ->values()
            ->all();
        $juniorSchoolSubjects = collect(['Grade 7', 'Grade 8', 'Grade 9'])
            ->map(fn (string $subject) => [
                'name' => $subject,
                'slug' => Str::slug($subject),
            ])
            ->values()
            ->all();
        $kpleaNode = [
            'id' => 'lower-primary-kplea',
            'slug' => 'kplea',
            'label' => 'KPLEA',
            'children' => collect($lowerPrimarySubjects)
                ->map(fn (array $subject) => [
                    'id' => 'lower-primary-kplea-' . Str::slug($subject['name']),
                    'slug' => $subject['slug'],
                    'label' => $subject['name'],
                    'children' => [],
                ])
                ->values()
                ->all(),
        ];
        $kpseaNode = [
            'id' => 'upper-primary-kpsea',
            'slug' => 'kpsea',
            'label' => 'KPSEA',
            'children' => collect($upperPrimarySubjects)
                ->map(fn (array $subject) => [
                    'id' => 'upper-primary-kpsea-' . Str::slug($subject['name']),
                    'slug' => $subject['slug'],
                    'label' => $subject['name'],
                    'children' => [],
                ])
                ->values()
                ->all(),
        ];
        $kjseaNode = [
            'id' => 'junior-school-kjsea',
            'slug' => 'kjsea',
            'label' => 'KJSEA',
            'children' => collect($juniorSchoolSubjects)
                ->map(fn (array $subject) => [
                    'id' => 'junior-school-kjsea-' . Str::slug($subject['name']),
                    'slug' => $subject['slug'],
                    'label' => $subject['name'],
                    'children' => [],
                ])
                ->values()
                ->all(),
        ];
        $buildNode = function ($category) use (&$buildNode, $byParent, $labelOverrides) {
            $label = $labelOverrides[$category->slug] ?? $category->translation?->name ?? $category->name ?? $category->slug;
            $children = collect($byParent[(string) $category->id] ?? [])
                ->map(fn ($child) => $buildNode($child))
                ->values()
                ->all();

            return [
                'id' => $category->id,
                'slug' => $category->slug,
                'label' => $label,
                'children' => $children,
            ];
        };

        return collect($rootDefinitions)
            ->map(function (array $definition) use ($categories, $buildNode, $commonSubjectsNode, $schoolOnlyNodesByLevel, $professionalCoursesCfaNode, $professionalCoursesComptiaNode, $kcseNode, $kpleaNode, $kpseaNode, $kjseaNode, $ictAndComputingCourseChildren, $seniorSchoolGrades) {
                $sourceSlugs = $definition['source_slugs'];
                $rootNodes = $categories
                    ->filter(fn ($category) => blank($category->parent_id) && in_array($category->slug, $sourceSlugs, true))
                    ->sortBy(fn ($category) => array_search($category->slug, $sourceSlugs, true))
                    ->values();

                $children = $rootNodes
                    ->flatMap(fn ($root) => $buildNode($root)['children'] ?? [])
                    ->values()
                    ->all();

                if ($definition['slug'] === 'senior-school-cbc') {
                    $children = $seniorSchoolGrades;
                }

                if (array_key_exists($definition['slug'], $schoolOnlyNodesByLevel)) {
                    $children = $schoolOnlyNodesByLevel[$definition['slug']];
                }

                if ($definition['slug'] === 'primary') {
                    $children[] = $kpleaNode;
                    $children[] = $kpseaNode;
                }

                if ($definition['slug'] === 'junior-school') {
                    $children[] = $kjseaNode;
                }

                if ($definition['slug'] === 'professional-courses') {
                    $children[] = $professionalCoursesCfaNode;
                    $children[] = $professionalCoursesComptiaNode;
                }

                if ($definition['slug'] === 'high-school') {
                    $children[] = $kcseNode;
                }

                return [
                    'id' => $definition['slug'],
                    'slug' => $definition['slug'],
                    'label' => $definition['label'],
                    'children' => $children,
                ];
            })
            ->values()
            ->all();
    }
}
