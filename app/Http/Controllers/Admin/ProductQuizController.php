<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductQuizUpsertRequest;
use App\Models\Product;
use App\Services\ProductIdentityService;
use App\Services\ProductMetadataCatalogService;
use App\Services\ProductQuizBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Course\app\Helper\CourseCategoryHelper;

class ProductQuizController extends Controller
{
    public function __construct(
        private readonly ProductQuizBuilderService $builderService,
        private readonly ProductMetadataCatalogService $metadataCatalog,
        private readonly ProductIdentityService $identityService
    )
    {
    }

    public function index(Request $request): View
    {
        $meta = [
            'route' => 'admin.quizzes',
            'title' => 'Quizzes',
            'singular' => 'Quiz',
        ];

        $query = Product::query()->where('type', Product::TYPE_QUIZ)->with(['category.translation', 'quiz']);
        $query->when($request->keyword, fn ($q) => $q->where('title', 'like', '%' . $request->keyword . '%'));
        $query->when($request->category, fn ($q) => $q->where('category_id', $request->category));
        $query->when($request->date, fn ($q) => $q->whereDate('created_at', $request->date));
        $query->when($request->approve_status, fn ($q) => $q->where('is_approved', $request->approve_status));
        $query->when($request->status, fn ($q) => $q->where('status', $request->status));

        $orderBy = $request->order_by == 1 ? 'asc' : 'desc';
        $resources = $query->orderBy('id', $orderBy)
            ->paginate($request->par_page ?? 20)
            ->withQueryString();
        $categories = CourseCategoryHelper::getTree();
        $type = Product::TYPE_QUIZ;

        return view('admin.catalog-resources.index', compact('resources', 'categories', 'type', 'meta'));
    }

    public function selectTier(): View
    {
        return view('admin.product-quizzes.tier-select');
    }

    public function create(string $tier): View
    {
        abort_unless(in_array($tier, ['short', 'long'], true), 404);

        return view('admin.product-quizzes.form', [
            'categories' => CourseCategoryHelper::getTree(),
            'product' => new Product([
                'type' => Product::TYPE_QUIZ,
                'status' => 'is_draft',
                'price' => $tier === 'short' ? 0 : 20,
                'is_approved' => 'approved',
            ]),
            'quizFormData' => [
                'tier' => $tier,
                'difficulty' => 'intermediate',
                'duration_minutes' => null,
                'attempt_limit' => 1,
                'pass_mark' => 0,
                'questions' => [],
            ],
            'metadataOptions' => $this->metadataCatalog->quizSelectionOptions(),
            'isEditing' => false,
            'showReviewSnapshot' => true,
        ]);
    }

    public function store(ProductQuizUpsertRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'type' => Product::TYPE_QUIZ,
            'title' => $validated['title'],
            'slug' => generateUniqueSlug(Product::class, $validated['title']),
            'category_id' => $validated['category_id'] ?? null,
            'price' => $validated['price'],
            'discount' => $validated['discount'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_approved' => $request->input('is_approved', 'approved'),
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

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = file_upload($request->file('thumbnail'), 'uploads/products/', null, true);
        }

        $product = Product::create($data);
        $this->builderService->sync($product, $validated, $data['is_approved'] === 'approved');

        return redirect()->route('admin.quizzes.edit', $product->id)
            ->with([
                'messege' => __('Quiz created successfully'),
                'alert-type' => 'success',
            ]);
    }

    public function edit(Product $resource): View
    {
        $this->ensureQuiz($resource);
        $resource->load(['quiz.questions.options']);

        return view('admin.product-quizzes.form', [
            'categories' => CourseCategoryHelper::getTree(),
            'product' => $resource,
            'quizFormData' => $this->builderService->formData($resource),
            'metadataOptions' => $this->metadataCatalog->quizSelectionOptions(),
            'isEditing' => true,
            'showReviewSnapshot' => true,
        ]);
    }

    public function update(ProductQuizUpsertRequest $request, Product $resource): RedirectResponse
    {
        $this->ensureQuiz($resource);
        $validated = $request->validated();

        $data = [
            'title' => $validated['title'],
            'slug' => $resource->title === $validated['title'] ? $resource->slug : generateUniqueSlug(Product::class, $validated['title']),
            'category_id' => $validated['category_id'] ?? null,
            'price' => $validated['price'],
            'discount' => $validated['discount'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_approved' => $request->input('is_approved', $resource->is_approved),
            'metadata' => $this->identityService->stampMetadata($resource->metadata ?? [], [
                'type' => Product::TYPE_QUIZ,
                'education_level' => $validated['education_level'] ?? null,
                'class_grade' => $validated['class_grade'] ?? null,
                'exam_category' => $validated['exam_category'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'topic' => $validated['topic'] ?? null,
                'tags' => $validated['tags'] ?? null,
            ], $resource),
        ];

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = file_upload($request->file('thumbnail'), 'uploads/products/', $resource->thumbnail, true);
        }

        $resource->update($data);
        $this->builderService->sync($resource, $validated, $data['is_approved'] === 'approved');

        return redirect()->route('admin.quizzes.edit', $resource->id)->with([
            'messege' => __('Quiz updated successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Product $resource): RedirectResponse
    {
        $this->ensureQuiz($resource);
        $resource->delete();

        return redirect()->route('admin.quizzes.index')->with([
            'messege' => __('Quiz deleted successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function statusUpdate(Request $request, Product $resource): RedirectResponse
    {
        $this->ensureQuiz($resource);
        $request->validate([
            'is_approved' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        if ($request->is_approved === 'approved') {
            $resource->load('quiz.questions.options');
            abort_unless($resource->quiz, 422);
            $this->builderService->ensurePublishable($resource, $resource->quiz);
        }

        $resource->update(['is_approved' => $request->is_approved]);

        return redirect()->route('admin.quizzes.index')->with([
            'messege' => __('Approval status updated successfully'),
            'alert-type' => 'success',
        ]);
    }

    private function ensureQuiz(Product $resource): void
    {
        abort_unless($resource->type === Product::TYPE_QUIZ, 404);
    }
}
