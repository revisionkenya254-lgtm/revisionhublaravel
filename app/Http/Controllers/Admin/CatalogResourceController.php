<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiDocument;
use App\Models\Product;
use App\Models\AiSetting;
use App\Services\Ai\AiDocumentService;
use App\Services\Ai\BunnyDocumentStorageService;
use App\Services\ProductIdentityService;
use App\Services\ProductMetadataCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Course\app\Helper\CourseCategoryHelper;
use Modules\Course\app\Models\CourseCategory;
use Illuminate\Support\Str;
use Throwable;

class CatalogResourceController extends Controller
{
    public function __construct(
        private readonly ProductMetadataCatalogService $metadataCatalog,
        private readonly ProductIdentityService $identityService
    )
    {
    }

    private array $types = [
        Product::TYPE_PAST_PAPER => [
            'route' => 'admin.past-papers',
            'title' => 'Past Papers',
            'singular' => 'Past Paper',
        ],
        Product::TYPE_PREDICTION => [
            'route' => 'admin.predictions',
            'title' => 'Predictions',
            'singular' => 'Prediction',
        ],
        Product::TYPE_NOTE => [
            'route' => 'admin.notes',
            'title' => 'Notes',
            'singular' => 'Note',
        ],
        Product::TYPE_QUIZ => [
            'route' => 'admin.quizzes',
            'title' => 'Quizzes',
            'singular' => 'Quiz',
        ],
    ];

    public function index(Request $request, string $type): View
    {
        $meta = $this->typeMeta($type);

        $query = Product::query()->where('type', $type)->with([
            'category.translation',
            'latestAiDocument.processingLogs',
        ]);
        $query->when($request->keyword, fn($q) => $q->where('title', 'like', '%' . $request->keyword . '%'));
        $query->when($request->category, fn($q) => $q->where('category_id', $request->category));
        $query->when($request->date, fn($q) => $q->whereDate('created_at', $request->date));
        $query->when($request->approve_status, fn($q) => $q->where('is_approved', $request->approve_status));
        $query->when($request->status, fn($q) => $q->where('status', $request->status));

        $orderBy = $request->order_by == 1 ? 'asc' : 'desc';
        $resources = $query->orderBy('id', $orderBy)
            ->paginate($request->par_page ?? 20)
            ->withQueryString();

        $categories = CourseCategoryHelper::getTree();
        $metadataOptions = $this->metadataCatalog->paperSelectionOptions();

        return view('admin.catalog-resources.index', compact('resources', 'categories', 'type', 'meta', 'metadataOptions'));
    }

    public function create(string $type): View
    {
        $meta = $this->typeMeta($type);
        $categories = CourseCategoryHelper::getTree();
        $educationCategories = $this->educationRootCategories();
        $paperEducationCategories = $this->buildPaperEducationRootCategories();
        $educationTree = $this->buildPaperEducationTreeForForm($categories);
        $metadataOptions = $this->metadataCatalog->paperSelectionOptions();
        $paperForm = $this->resolvePaperFormDefaults(request(), $type);

        return view('admin.catalog-resources.form', compact('categories', 'educationCategories', 'paperEducationCategories', 'educationTree', 'paperForm', 'type', 'meta', 'metadataOptions'));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $meta = $this->typeMeta($type);
        $data = $this->validatedData($request, $type);
        $data['type'] = $type;
        $data['slug'] = generateUniqueSlug(Product::class, $data['title']);
        $data['discount'] = $data['discount'] ?? 0;
        $data['metadata'] = $this->buildMetadata($request, $type);

        Product::create($data);

        return redirect()->route($meta['route'] . '.index')->with([
            'messege' => __($meta['singular'] . ' created successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function edit(Product $resource, string $type): View
    {
        $this->ensureType($type, $resource);
        $meta = $this->typeMeta($type);
        $categories = CourseCategoryHelper::getTree();
        $educationCategories = $this->educationRootCategories();
        $paperEducationCategories = $this->buildPaperEducationRootCategories();
        $educationTree = $this->buildPaperEducationTreeForForm($categories);
        $metadataOptions = $this->metadataCatalog->paperSelectionOptions();
        $paperForm = $this->productToPaperForm($resource);
        $aiSettings = AiSetting::query()->first();
        $latestAiDocument = $resource->aiDocuments()
            ->with(['processingLogs', 'questions'])
            ->latest()
            ->first();
        $latestAiDocumentPayload = $latestAiDocument ? $this->normalizeAiDocument($latestAiDocument) : null;

        return view('admin.catalog-resources.form', compact('categories', 'educationCategories', 'paperEducationCategories', 'educationTree', 'paperForm', 'type', 'meta', 'resource', 'metadataOptions', 'latestAiDocument', 'latestAiDocumentPayload', 'aiSettings'));
    }

    public function update(Request $request, Product $resource, string $type): RedirectResponse
    {
        $this->ensureType($type, $resource);
        $meta = $this->typeMeta($type);
        $data = $this->validatedData($request, $type);

        $data['discount'] = $data['discount'] ?? 0;
        $data['metadata'] = $this->buildMetadata($request, $type, $resource);

        if ($resource->title !== $data['title']) {
            $data['slug'] = generateUniqueSlug(Product::class, $data['title']);
        }

        $resource->update($data);

        return redirect()->route($meta['route'] . '.index', $request->query())->with([
            'messege' => __($meta['singular'] . ' updated successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Product $resource, string $type): RedirectResponse
    {
        $this->ensureType($type, $resource);
        $meta = $this->typeMeta($type);

        $this->deleteProductAssets($resource);
        $resource->forceDelete();

        return redirect()->route($meta['route'] . '.index')->with([
            'messege' => __($meta['singular'] . ' deleted successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function statusUpdate(Request $request, Product $resource, string $type): RedirectResponse
    {
        $this->ensureType($type, $resource);
        $meta = $this->typeMeta($type);
        $request->validate([
            'is_approved' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $resource->update(['is_approved' => $request->is_approved]);

        return redirect()->route($meta['route'] . '.index')->with([
            'messege' => __('Approval status updated successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function reprocessAiDocument(Product $resource, string $type): JsonResponse|RedirectResponse
    {
        $this->ensureType($type, $resource);
        $meta = $this->typeMeta($type);
        $queueMode = $this->normalizeQueueMode(request()->input('queue_mode'));
        $reviewMode = strtolower(trim((string) request()->input('review_mode', '')));

        if ($reviewMode === '' && strtolower((string) $resource->file_type) === 'pdf') {
            $reviewMode = 'openai';
        }

        $document = $this->resolveProcessableAiDocument($resource, $queueMode);

        if (! $document) {
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => __('No processable source file is attached to this paper yet. Upload or attach a PDF, DOC, or DOCX first.'),
                ], 422);
            }

            return redirect()->route($meta['route'] . '.edit', $resource->id)->with([
                'messege' => __('No processable source file is attached to this paper yet.'),
                'alert-type' => 'warning',
            ]);
        }

        if (! $document->wasRecentlyCreated) {
            $document = $this->aiDocumentService()->reprocess($document, $queueMode, [
                'review_mode' => $reviewMode ?: null,
                'metadata' => array_filter([
                    'review_mode' => $reviewMode ?: null,
                ]),
                'message' => $reviewMode === 'openai'
                    ? __('OpenAI-assisted review has been queued.')
                    : __('Document reprocessing has been queued.'),
            ]);
        }

        $freshDocument = $document->fresh(['processingLogs', 'questions']) ?? $document;
        $methodLabel = $this->processingMethodLabel($freshDocument);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $reviewMode === 'openai'
                    ? ($queueMode === 'local'
                        ? __('OpenAI-assisted review processed immediately using :method.', ['method' => $methodLabel])
                        : __('OpenAI-assisted review has been queued.'))
                    : ($queueMode === 'local'
                    ? ($document->wasRecentlyCreated
                        ? __('Document processed immediately using :method.', ['method' => $methodLabel])
                        : __('AI document reprocessed immediately using :method.', ['method' => $methodLabel]))
                    : ($document->wasRecentlyCreated
                        ? __('Document processing has been queued.')
                        : __('AI reprocessing has been queued.'))),
                'document' => $this->normalizeAiDocument($freshDocument),
                'queue_mode' => $queueMode,
                'review_mode' => $reviewMode ?: null,
                'queue_connection' => $this->aiDocumentService()->resolveProcessingQueueConnection($queueMode),
            ]);
        }

        return redirect()->route($meta['route'] . '.edit', $resource->id)->with([
            'messege' => $reviewMode === 'openai'
                ? ($queueMode === 'local'
                    ? __('OpenAI-assisted review processed immediately using :method.', ['method' => $methodLabel])
                    : __('OpenAI-assisted review has been queued.'))
                : ($queueMode === 'local'
                ? ($document->wasRecentlyCreated
                    ? __('Document processed immediately using :method.', ['method' => $methodLabel])
                    : __('AI document reprocessed immediately using :method.', ['method' => $methodLabel]))
                : ($document->wasRecentlyCreated
                    ? __('Document processing has been queued.')
                    : __('AI reprocessing has been queued.'))),
            'alert-type' => 'success',
        ]);
    }

    public function aiDocument(Product $resource, string $type): JsonResponse
    {
        $this->ensureType($type, $resource);

        $document = $resource->latestAiDocument()
            ->with(['processingLogs', 'questions'])
            ->latest()
            ->first();

        if (! $document) {
            return response()->json([
                'status' => 'success',
                'document' => null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'document' => $this->normalizeAiDocument($document),
        ]);
    }

    private function validatedData(Request $request, string $type): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive', 'is_draft'])],
            'is_approved' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'file_path' => ['nullable', 'string', 'max:255'],
            'file_type' => ['nullable', 'string', 'max:100'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'class_grade' => ['nullable', 'string', 'max:255'],
            'exam_category' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'paper' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'string', 'max:20'],
            'language' => ['nullable', 'string', 'max:255'],
            'access_type' => ['nullable', Rule::in(['paid', 'free'])],
            'preview_pages' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:255'],
        ];

        if ($type === Product::TYPE_NOTE) {
            $rules['note_visibility'] = ['nullable', Rule::in(['public', 'private'])];
            $rules['topic'] = ['nullable', 'string', 'max:255'];
            $rules['sub_topic'] = ['nullable', 'string', 'max:255'];
        }

        return $request->validate($rules);
    }

    private function buildMetadata(Request $request, string $type, ?Product $existing = null): array
    {
        $defaults = $this->metadataCatalog->paperDefaults();
        $existingMetadata = $existing?->metadata ?? [];
        $valueOrExisting = fn (string $key, mixed $default = null) => filled($request->input($key))
            ? $request->input($key)
            : ($existingMetadata[$key] ?? $default);

        $metadata = [
            'education_level' => $valueOrExisting('education_level', $defaults['education_level']),
            'class_grade' => $valueOrExisting('class_grade', $defaults['class_grade']),
            'exam_category' => $valueOrExisting('exam_category', $defaults['exam_category']),
            'subject' => filled($request->input('subject'))
                ? $request->input('subject')
                : ($request->input('course') ?: ($existingMetadata['subject'] ?? $defaults['subject'])),
            'paper' => $valueOrExisting('paper', $defaults['paper']),
            'year' => $valueOrExisting('year', $defaults['year']),
            'language' => $valueOrExisting('language', $defaults['language']),
            'tags' => collect(explode(',', (string) $request->input('tags', isset($existingMetadata['tags']) ? implode(', ', (array) $existingMetadata['tags']) : '')))
                ->map(fn ($tag) => trim($tag))
                ->filter()
                ->values()
                ->all(),
            'access_type' => $request->input('access_type', $existingMetadata['access_type'] ?? $defaults['access_type']),
            'preview_pages' => $request->input('preview_pages', $existingMetadata['preview_pages'] ?? $defaults['preview_pages']),
            'extraction_confirmed' => $request->boolean('extraction_confirmed'),
        ];

        if ($type === Product::TYPE_NOTE) {
            $metadata['note_visibility'] = $request->input('note_visibility', $existingMetadata['note_visibility'] ?? 'public');
            $metadata['topic'] = $request->input('topic', $existingMetadata['topic'] ?? null);
            $metadata['sub_topic'] = $request->input('sub_topic', $existingMetadata['sub_topic'] ?? null);
        }

        $metadata = array_filter($metadata, fn ($value) => $value !== null && $value !== '');

        return $this->identityService->stampMetadata($metadata, [
            'type' => $type,
            'education_level' => $metadata['education_level'] ?? null,
            'class_grade' => $metadata['class_grade'] ?? null,
            'exam_category' => $metadata['exam_category'] ?? null,
            'subject' => $metadata['subject'] ?? null,
            'course' => $metadata['course'] ?? null,
            'paper' => $metadata['paper'] ?? null,
            'year' => $metadata['year'] ?? null,
            'language' => $metadata['language'] ?? null,
        ], $existing);
    }

    private function typeMeta(string $type): array
    {
        abort_unless(isset($this->types[$type]), 404);

        return $this->types[$type];
    }

    private function ensureType(string $type, Product $resource): void
    {
        abort_unless($resource->type === $type, 404);
    }

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

    private function educationRootCategories(?Collection $categories = null): Collection
    {
        $source = $categories ?? CourseCategory::active()->get();
        $allowedSlugs = $this->educationRootCategorySlugs();

        return collect($source)
            ->filter(fn ($category) => blank($category->parent_id) && in_array($category->slug, $allowedSlugs, true))
            ->sortBy(fn ($category) => array_search($category->slug, $allowedSlugs, true))
            ->values();
    }

    private function buildPaperEducationRootCategories(?Collection $categories = null): array
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
            ['id' => 'certificate-ecde-ecde', 'slug' => 'ecde', 'label' => 'ECDE'],
            ['id' => 'certificate-ecde-early-childhood-care', 'slug' => 'early-childhood-care', 'label' => 'Early Childhood Care'],
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
            'professional-courses' => [
                ['id' => 'professional-courses-cfa', 'slug' => 'cfa', 'label' => 'CFA', 'children' => []],
                ['id' => 'professional-courses-comptia', 'slug' => 'comptia', 'label' => 'CompTIA', 'children' => []],
            ],
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
            ->map(function (array $definition) use ($categories, $buildNode, $commonSubjectsNode, $schoolOnlyNodesByLevel, $kcseNode, $kpleaNode, $kpseaNode, $kjseaNode) {
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
                    $children[] = $commonSubjectsNode;
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

    private function resolvePaperFormDefaults(Request $request, ?string $type = null): array
    {
        $defaults = $this->metadataCatalog->paperDefaults();
        $educationLevel = old('education_level', $request->input('education_level', $defaults['education_level']));
        $subjectValue = old('subject', old('course', $request->input('subject', $request->input('course', $defaults['subject']))));

        return [
            'education_level' => $educationLevel,
            'class_grade' => old('class_grade', $request->input('class_grade', $defaults['class_grade'])),
            'exam_category' => old('exam_category', $request->input('exam_category', $defaults['exam_category'])),
            'subject' => $subjectValue,
            'course' => $subjectValue,
            'paper' => old('paper', $request->input('paper', $type === Product::TYPE_PREDICTION ? __('Prediction Pack') : $defaults['paper'])),
            'year' => old('year', $request->input('year', $defaults['year'])),
            'language' => old('language', $request->input('language', $defaults['language'])),
            'tags' => old('tags', $request->input('tags', $type === Product::TYPE_PREDICTION ? 'KCSE, Mathematics, Prediction Pack, ' . now()->year : 'KCSE, Mathematics, ' . now()->year)),
            'access_type' => old('access_type', $request->input('access_type', 'paid')),
            'preview_pages' => old('preview_pages', $request->input('preview_pages', $defaults['preview_pages'])),
            'file_path' => old('file_path', ''),
        ];
    }

    private function productToPaperForm(Product $product): array
    {
        $metadata = $product->metadata ?? [];
        $defaults = $this->metadataCatalog->paperDefaults();
        $educationLevel = old('education_level', $metadata['education_level'] ?? $defaults['education_level']);
        $subjectValue = old('subject', old('course', $this->resolvePaperSubjectValue($metadata, $educationLevel, $defaults['subject'])));
        $isPrediction = $product->type === Product::TYPE_PREDICTION;

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

    private function resolvePaperSubjectValue(array $metadata, ?string $educationLevel, ?string $fallback = null): ?string
    {
        if ($this->usesPaperCourseField($educationLevel)) {
            return $metadata['course'] ?? $metadata['subject'] ?? $fallback;
        }

        return $metadata['subject'] ?? $metadata['course'] ?? $fallback;
    }

    private function usesPaperCourseField(?string $educationLevel): bool
    {
        $level = strtolower(trim((string) $educationLevel));

        return preg_match('/tvet|university|college|certificate|diploma|undergraduate|professional|tertiary|higher education/', $level) === 1;
    }

    private function resolveProcessableAiDocument(Product $resource, ?string $queueMode = null): ?\App\Models\AiDocument
    {
        $upload = $this->buildProductResourceUploadFromProduct($resource);
        $instructorId = (int) ($resource->instructor_id ?? 0);

        if (! $upload || $instructorId <= 0) {
            return null;
        }

        $sourceType = 'product_resource';
        $matchingDocument = $resource->aiDocuments()
            ->where('source_type', $sourceType)
            ->where('original_path', $upload['path'])
            ->where('file_extension', $upload['extension'])
            ->latest()
            ->first();

        if ($matchingDocument) {
            return $matchingDocument;
        }

        return $this->aiDocumentService()->registerUpload($instructorId, $upload, [
            'product_id' => $resource->id,
            'product_type' => $resource->type,
            'asset_kind' => 'source',
            'source_type' => $sourceType,
            'source_name' => $resource->title,
            'queue_mode' => $queueMode,
        ]);
    }

    private function buildProductResourceUploadFromProduct(Product $product): ?array
    {
        $path = trim((string) $product->file_path);

        if ($path === '') {
            return null;
        }

        $normalizedPath = $this->normalizeResourcePath($path);
        $extension = strtolower(pathinfo(parse_url($normalizedPath, PHP_URL_PATH) ?: $normalizedPath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            return null;
        }

        $originalName = basename(parse_url($normalizedPath, PHP_URL_PATH) ?: $normalizedPath);
        if ($originalName === '') {
            $originalName = $product->title . '.' . $extension;
        }

        return [
            'path' => $normalizedPath,
            'folder_path' => dirname($normalizedPath),
            'url' => $this->storageService()->publicUrl($normalizedPath),
            'original_name' => $originalName,
            'mime_type' => $this->guessMimeTypeForExtension($extension),
            'extension' => $extension,
            'size' => null,
            'hash' => hash('sha256', $normalizedPath),
            'context' => [
                'product_id' => $product->id,
                'product_type' => $product->type,
                'source_type' => 'product_resource',
            ],
        ];
    }

    private function normalizeResourcePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        $cdnBase = trim((string) config('bunny.storage_cdn_url'));
        if ($cdnBase !== '' && str_starts_with($path, $cdnBase)) {
            $path = substr($path, strlen($cdnBase));
        }

        return ltrim($path, '/');
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

    private function normalizeQueueMode(mixed $queueMode): string
    {
        $mode = strtolower(trim((string) $queueMode));

        return in_array($mode, ['local', 'redis'], true)
            ? $mode
            : (string) (AiSetting::query()->value('document_queue_mode') ?: config('ai.document_processing.default_mode', 'local'));
    }

    private function deleteProductAssets(Product $resource): void
    {
        $deletedPaths = [];

        $resource->loadMissing('aiDocuments');

        $resource->aiDocuments->each(function (AiDocument $document) use (&$deletedPaths) {
            $deletedPaths[] = $document->original_path;

            if (filled($document->extracted_text_path)) {
                $deletedPaths[] = $document->extracted_text_path;
            }

            try {
                $this->aiDocumentService()->deleteDocument($document);
                $document->forceDelete();
            } catch (Throwable $throwable) {
                report($throwable);
            }
        });

        $this->deleteBunnyPathIfNeeded($resource->file_path, $deletedPaths);
    }

    private function deleteBunnyPathIfNeeded(?string $path, array $excludedPaths = []): void
    {
        $normalizedPath = $this->normalizeResourcePath((string) $path);

        if ($normalizedPath === '' || ! Str::startsWith($normalizedPath, ['instructors/', 'admins/'])) {
            return;
        }

        $normalizedExcludedPaths = collect($excludedPaths)
            ->filter()
            ->map(fn ($excludedPath) => $this->normalizeResourcePath((string) $excludedPath))
            ->filter()
            ->all();

        if (in_array($normalizedPath, $normalizedExcludedPaths, true)) {
            return;
        }

        try {
            $this->storageService()->deletePath($normalizedPath);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    private function aiDocumentService(): AiDocumentService
    {
        return app(AiDocumentService::class);
    }

    private function storageService(): BunnyDocumentStorageService
    {
        return app(BunnyDocumentStorageService::class);
    }

    private function normalizeAiDocument(\App\Models\AiDocument $document): array
    {
        return [
            'id' => $document->id,
            'status' => $document->status,
            'source_type' => $document->source_type,
            'source_name' => $document->source_name,
            'file_extension' => $document->file_extension,
            'progress' => $document->progress ?? 0,
            'page_count' => $document->page_count,
            'character_count' => $document->character_count,
            'extracted_text_excerpt' => $document->extracted_text_excerpt,
            'extraction_method' => data_get($document->metadata, 'extraction_method'),
            'question_extraction_method' => data_get($document->metadata, 'question_extraction_method'),
            'openai_retry_ready' => $this->isOpenAiRetryReady($document),
            'metadata' => $document->metadata ?? [],
            'question_count' => $document->questions->count(),
            'questions' => $document->questions->map(function ($question) {
                return [
                    'sort_order' => $question->sort_order,
                    'section_label' => $question->section_label,
                    'question_number' => $question->question_number,
                    'question_label' => $question->question_label,
                    'part_label' => $question->part_label,
                    'marks' => $question->marks,
                    'marks_label' => $question->marks_label,
                    'content' => $question->content,
                    'raw_text' => $question->raw_text,
                    'metadata' => $question->metadata ?? [],
                ];
            })->values()->all(),
            'processed_at' => optional($document->processed_at)?->toIso8601String(),
            'failed_at' => optional($document->failed_at)?->toIso8601String(),
            'failure_reason' => $document->failure_reason,
            'created_at' => optional($document->created_at)?->toIso8601String(),
            'updated_at' => optional($document->updated_at)?->toIso8601String(),
            'processing_logs' => $document->processingLogs->map(function ($log) {
                return [
                    'stage' => $log->stage,
                    'message' => $log->message,
                    'created_at' => optional($log->created_at)?->toIso8601String(),
                ];
            })->values()->all(),
        ];
    }

    private function isOpenAiRetryReady(\App\Models\AiDocument $document): bool
    {
        $status = (string) $document->status;
        $metadata = $document->metadata ?? [];
        $extractionMethod = strtolower(trim((string) data_get($metadata, 'extraction_method', '')));
        $questionExtractionMethod = strtolower(trim((string) data_get($metadata, 'question_extraction_method', '')));
        $requiresTranscription = filter_var(data_get($metadata, 'requires_transcription', false), FILTER_VALIDATE_BOOL);

        if (in_array($status, ['failed', 'requires_ocr'], true)) {
            return true;
        }

        if ($status !== 'processed') {
            return false;
        }

        $lowQualityMethods = [
            'msdoc',
            'legacy-ole-low-confidence',
            'legacy-ole',
            'word2007-xml',
            'word2007',
            'prinsfrank',
            'tesseract',
            'ocr',
        ];

        return $requiresTranscription
            || in_array($extractionMethod, $lowQualityMethods, true)
            || in_array($questionExtractionMethod, ['heuristic', 'heuristic-fallback'], true);
    }

    private function processingMethodLabel(AiDocument $document): string
    {
        $metadata = $document->metadata ?? [];
        $extractionMethod = strtolower(trim((string) data_get($metadata, 'extraction_method', '')));
        $questionMethod = strtolower(trim((string) data_get($metadata, 'question_extraction_method', '')));

        $methodMap = [
            'word2007' => __('DOCX text extraction'),
            'word2007-xml' => __('DOCX XML text extraction'),
            'msdoc' => __('legacy DOC extraction'),
            'legacy-ole' => __('legacy DOC extraction'),
            'legacy-ole-low-confidence' => __('legacy DOC extraction with low-confidence fallback'),
            'legacy-doc-render-text' => __('legacy DOC rendering'),
            'legacy-doc-render-images' => __('legacy DOC image rendering'),
            'prinsfrank' => __('PDF text extraction'),
            'tesseract' => __('OCR fallback'),
            'ocr' => __('OCR fallback'),
            'openai_vision' => __('OpenAI vision OCR'),
            'openai_vision_image' => __('OpenAI vision image OCR'),
            'openai_direct_review' => __('OpenAI direct review'),
            'heuristic' => __('question parsing heuristics'),
            'openai' => __('OpenAI question extraction'),
        ];

        $labels = [];

        if ($extractionMethod !== '') {
            $labels[] = $methodMap[$extractionMethod] ?? str_replace('_', ' ', $extractionMethod);
        }

        if ($questionMethod !== '' && $questionMethod !== $extractionMethod) {
            $labels[] = $methodMap[$questionMethod] ?? str_replace('_', ' ', $questionMethod);
        }

        return $labels ? implode(' + ', $labels) : __('local processing');
    }
}
