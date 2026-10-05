<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductQuizUpsertRequest;
use App\Models\Product;
use App\Services\ProductIdentityService;
use App\Services\ProductMetadataCatalogService;
use App\Services\ProductQuizBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Course\app\Models\CourseCategory;

class InstructorProductQuizController extends Controller
{
    public function __construct(
        private readonly ProductQuizBuilderService $builderService,
        private readonly ProductMetadataCatalogService $metadataCatalog,
        private readonly ProductIdentityService $identityService
    )
    {
    }

    public function selectTier(): RedirectResponse
    {
        return redirect()->route('instructor.products.create', ['type' => Product::TYPE_QUIZ]);
    }

    public function create(string $tier): View
    {
        abort_unless(in_array($tier, ['short', 'long'], true), 404);

        $sampleTitle = $tier === 'short'
            ? __('KCSE Mathematics - Short Quiz')
            : __('KCSE Mathematics - Long Quiz');

        $sampleDescription = $tier === 'short'
            ? __('Test your knowledge with a focused short quiz designed for quick revision.')
            : __('A longer quiz designed for deeper practice, revision, and exam readiness.');

        return view('frontend.instructor-dashboard.quiz-builder.form', [
            'product' => new Product([
                'type' => Product::TYPE_QUIZ,
                'status' => 'is_draft',
                'price' => $tier === 'short' ? 0 : 20,
                'title' => $sampleTitle,
                'description' => $sampleDescription,
                'discount' => 0,
            ]),
            'quizFormData' => array_merge($this->metadataCatalog->quizDefaults($tier), [
                'topic' => $tier === 'short' ? __('Algebra') : __('Mixed Revision'),
                'tags' => $tier === 'short' ? 'KCSE, Mathematics, Algebra' : 'KCSE, Mathematics, Revision',
            ]),
            'metadataOptions' => $this->metadataCatalog->quizSelectionOptions(),
            'isEditing' => false,
        ]);
    }

    public function store(ProductQuizUpsertRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $isDraftSave = $request->input('save_mode') === 'draft';

        if ($isDraftSave) {
            $validated['status'] = 'is_draft';
        }

        $data = [
            'instructor_id' => userAuth()->id,
            'type' => Product::TYPE_QUIZ,
            'title' => $validated['title'],
            'slug' => generateUniqueSlug(Product::class, $validated['title']),
            'category_id' => $validated['category_id'] ?? null,
            'price' => $validated['price'],
            'discount' => $validated['discount'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_approved' => 'pending',
            'submission_signature' => $this->buildQuizSubmissionSignature($validated),
            'metadata' => $this->identityService->stampMetadata([], [
                'type' => Product::TYPE_QUIZ,
                'education_level' => $validated['education_level'] ?? null,
                'class_grade' => $validated['class_grade'] ?? null,
                'exam_category' => $validated['exam_category'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'topic' => $validated['topic'] ?? null,
                'tags' => $validated['tags'] ?? null,
            ]),
        ];

        if ($existingQuiz = $this->findDuplicateQuizSubmission(userAuth()->id, $data['submission_signature'])) {
            return ($existingQuiz->trashed()
                ? redirect()->route('instructor.products.index', ['type' => Product::TYPE_QUIZ])
                : redirect()->route('instructor.quizzes.edit', $existingQuiz->id))
                ->with('warning', __('You already sent this quiz for review. Please update the existing quiz instead of creating a duplicate.'));
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = file_upload($request->file('thumbnail'), 'uploads/products/', null, true);
        }

        $product = Product::create($data);
        $this->builderService->sync($product, $validated, false);

        $redirect = redirect()->route('instructor.quizzes.edit', $product->id)
            ->with('success', $isDraftSave
                ? __('Quiz draft saved successfully.')
                : __('Quiz created successfully and submitted for approval.'));

        return $redirect;
    }

    public function edit(int $id): View
    {
        $product = $this->ownedQuizProduct($id);
        $product->load(['quiz.questions.options']);

        return view('frontend.instructor-dashboard.quiz-builder.form', [
            'categories' => CourseCategory::active()->get(),
            'product' => $product,
            'quizFormData' => $this->builderService->formData($product),
            'metadataOptions' => $this->metadataCatalog->quizSelectionOptions(),
            'isEditing' => true,
        ]);
    }

    public function update(ProductQuizUpsertRequest $request, int $id): RedirectResponse
    {
        $product = $this->ownedQuizProduct($id);
        $validated = $request->validated();
        $isDraftSave = $request->input('save_mode') === 'draft';

        if ($isDraftSave) {
            $validated['status'] = 'is_draft';
        }

        $data = [
            'title' => $validated['title'],
            'slug' => $product->title === $validated['title'] ? $product->slug : generateUniqueSlug(Product::class, $validated['title']),
            'category_id' => $validated['category_id'] ?? null,
            'price' => $validated['price'],
            'discount' => $validated['discount'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_approved' => 'pending',
            'submission_signature' => $this->buildQuizSubmissionSignature($validated),
            'metadata' => $this->identityService->stampMetadata($product->metadata ?? [], [
                'type' => Product::TYPE_QUIZ,
                'education_level' => $validated['education_level'] ?? null,
                'class_grade' => $validated['class_grade'] ?? null,
                'exam_category' => $validated['exam_category'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'topic' => $validated['topic'] ?? null,
                'tags' => $validated['tags'] ?? null,
            ], $product),
        ];

        if ($existingQuiz = $this->findDuplicateQuizSubmission(userAuth()->id, $data['submission_signature'], $product->id)) {
            return ($existingQuiz->trashed()
                ? redirect()->route('instructor.products.index', ['type' => Product::TYPE_QUIZ])
                : redirect()->route('instructor.quizzes.edit', $existingQuiz->id))
                ->with('warning', __('This quiz already exists. Please update the original submission instead.'));
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = file_upload($request->file('thumbnail'), 'uploads/products/', $product->thumbnail, true);
        }

        $product->update($data);
        $this->builderService->sync($product, $validated, false);

        $redirect = redirect()->route('instructor.quizzes.edit', $product->id)
            ->with('success', $isDraftSave
                ? __('Quiz draft updated successfully.')
                : __('Quiz updated successfully and submitted for re-approval.'));

        return $redirect;
    }

    private function findDuplicateQuizSubmission(int $instructorId, string $signature, ?int $ignoreId = null): ?Product
    {
        $query = Product::withTrashed()
            ->where('instructor_id', $instructorId)
            ->where('type', Product::TYPE_QUIZ)
            ->where('submission_signature', $signature);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->first();
    }

    private function buildQuizSubmissionSignature(array $validated): string
    {
        $payload = $validated;
        unset($payload['thumbnail']);

        return hash('sha256', json_encode($this->normalizeQuizSignaturePayload($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function normalizeQuizSignaturePayload(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        if (array_is_list($payload)) {
            return array_map(fn ($item) => $this->normalizeQuizSignaturePayload($item), $payload);
        }

        ksort($payload);

        foreach ($payload as $key => $value) {
            $payload[$key] = $this->normalizeQuizSignaturePayload($value);
        }

        return $payload;
    }

    private function ownedQuizProduct(int $id): Product
    {
        return Product::instructorOwned(userAuth()->id)
            ->where('type', Product::TYPE_QUIZ)
            ->findOrFail($id);
    }
}
