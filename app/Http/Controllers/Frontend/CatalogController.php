<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Product;
use App\Services\CatalogCacheClear;
use App\Services\MenuCacheService;
use App\Services\ProductIdentityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Modules\Course\app\Models\CourseCategory;
use Modules\Order\app\Models\OrderItem;

class CatalogController extends Controller
{
    public function __construct(private readonly ProductIdentityService $identityService)
    {
    }

    public function index(Request $request, MenuCacheService $menuCacheService): View|RedirectResponse
    {
        if (! $request->filled('main_category') && $request->input('view') !== 'levels') {
            return redirect()->route('catalog', array_merge($request->except('view'), ['view' => 'levels']));
        }

        if ($request->filled('main_category')) {
            $normalizedMainCategory = $this->normalizeCatalogSlug((string) $request->main_category);

            if ($normalizedMainCategory !== (string) $request->main_category) {
                return redirect()->route('catalog', array_merge($request->except('main_category'), [
                    'main_category' => $normalizedMainCategory ?? '',
                ]));
            }
        }

        $categoryTree = collect($menuCacheService->getCategoryTree(getSessionLanguage()));
        $categories = collect($menuCacheService->getCategoryLinks(getSessionLanguage()))
            ->map(function (array $category) {
                return (object) [
                    'id' => $category['slug'],
                    'slug' => $category['slug'],
                    'name' => $category['label'],
                    'translation_name' => $category['label'],
                ];
            });
        $currentMainCategory = null;

        if ($request->filled('main_category')) {
            $currentMainCategory = $categoryTree->firstWhere('slug', $this->normalizeCatalogSlug($request->main_category));
        }

        $currentCategoryTitle = (string) data_get($currentMainCategory, 'name', __('Catalog'));
        $selectedCategorySlug = (string) collect(explode(',', (string) $request->category))
            ->filter()
            ->map(fn ($slug) => $this->normalizeCatalogSlug((string) $slug))
            ->filter()
            ->first();
        $curriculumLevels = collect(data_get($currentMainCategory, 'children', []));
        $selectedCurriculumLevel = null;
        $subjectItems = collect();
        $selectedSubject = null;

        if ($selectedCategorySlug !== '') {
            $selectedCurriculumLevel = $curriculumLevels->firstWhere('slug', $selectedCategorySlug);
            $subjectItems = $selectedCurriculumLevel ? collect(data_get($selectedCurriculumLevel, 'children', [])) : collect();
            $selectedSubject = $selectedCurriculumLevel ? $subjectItems->firstWhere('slug', $request->subject) : null;
        }

        $selectedFocus = $this->resolveCatalogFocus(
            $currentMainCategory,
            $selectedCurriculumLevel,
            $selectedSubject,
            $currentCategoryTitle
        );
        $catalogToolbar = $this->resolveCatalogToolbar(
            $request,
            $currentMainCategory,
            $curriculumLevels,
            $selectedCurriculumLevel,
            $subjectItems,
            $selectedSubject
        );

        // Keep the initial HTML light; the AJAX fetch will fill the live totals.
        $catalogSummary = $this->emptyCatalogSummary();

        $catalogBreadcrumbs = [];
        if ($currentMainCategory) {
            $catalogBreadcrumbs[] = [
                'label' => __('Home'),
                'url' => route('home'),
            ];
            $catalogBreadcrumbs[] = [
                'label' => $currentCategoryTitle,
                'url' => route('catalog', ['main_category' => $currentMainCategory['slug'] ?? '']),
            ];
            if (!empty(data_get($selectedCurriculumLevel, 'name'))) {
                $catalogBreadcrumbs[] = [
                    'label' => data_get($selectedCurriculumLevel, 'name'),
                    'url' => route('catalog', [
                        'main_category' => $currentMainCategory['slug'] ?? '',
                        'category' => data_get($selectedCurriculumLevel, 'slug'),
                    ]),
                ];
            } elseif (!empty($currentMainCategory['slug'])) {
                $catalogBreadcrumbs[] = [
                    'label' => __('All'),
                    'url' => route('catalog', [
                        'main_category' => $currentMainCategory['slug'] ?? '',
                    ]),
                ];
            }
            if (!empty(data_get($selectedFocus, 'is_subject')) && !empty(data_get($selectedSubject, 'name'))) {
                $catalogBreadcrumbs[] = [
                    'label' => data_get($selectedSubject, 'name'),
                    'url' => '',
                ];
            }
        }

        return view('frontend.pages.catalog', compact(
            'categories',
            'categoryTree',
            'currentMainCategory',
            'currentCategoryTitle',
            'curriculumLevels',
            'selectedCurriculumLevel',
            'subjectItems',
            'selectedSubject',
            'selectedFocus',
            'selectedCategorySlug',
            'catalogSummary',
            'catalogBreadcrumbs',
            'catalogToolbar'
        ));
    }

    public function fetch(Request $request, MenuCacheService $menuCacheService)
    {
        $cachePayload = Cache::remember(
            $this->catalogCacheKey($request),
            $this->catalogCacheTtl(),
            fn () => $this->buildCatalogCachePayload($request)
        );

        $presentation = $this->resolveCatalogPresentationData($request, $menuCacheService);
        $items = $this->decorateCatalogItems(collect($cachePayload['items'] ?? []));
        $page = (int) ($request->page ?? 1);
        $perPage = $this->itemsPerPage($request->type);
        $gridCols = $this->gridColumns($request->type);
        $paginator = $this->paginateCollection($items, $perPage, $page);
        $summary = $cachePayload['summary'] ?? $this->emptyCatalogSummary();
        $catalogToolbar = $this->resolveCatalogToolbarForRequest($request);

        $data = [
            'items' => view('frontend.partials.catalog-card', ['items' => $paginator])->render(),
            'lastPage' => $paginator->lastPage(),
            'currentPage' => $page,
            'itemCount' => $paginator->total(),
            'summary' => $summary,
            'holderClass' => "catalog-holder row g-1 courses__grid-wrap row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-{$gridCols}",
            'toolbar' => view('frontend.partials.catalog-toolbar', compact('catalogToolbar'))->render(),
            'pageTitle' => $presentation['pageTitle'],
            'focusName' => $presentation['focusName'],
            'currentCategoryTitle' => $presentation['currentCategoryTitle'],
            'selectedLevelName' => $presentation['selectedLevelName'],
            'selectedSubjectName' => $presentation['selectedSubjectName'],
            'catalogBreadcrumbs' => $presentation['catalogBreadcrumbs'],
        ];

        if ($request->filled('main_category')) {
            $subCategories = app(MenuCacheService::class)->getSubCategoriesForMenu($request->main_category, getSessionLanguage());
            $selectedCategorySlugs = $this->selectedCategorySlugs($request);
            $data['sidebar_items'] = view('frontend.partials.course-sidebar-item', compact('subCategories', 'selectedCategorySlugs'))->render();
        } else {
            $data['sidebar_items'] = '';
        }

        return response()->json($data);
    }

    private function itemsPerPage(?string $type): int
    {
        return match ($this->normalizeCatalogType($type)) {
            Product::TYPE_COURSE => 3,
            Product::TYPE_PAST_PAPER => 3,
            Product::TYPE_PREDICTION => 3,
            Product::TYPE_NOTE => 3,
            Product::TYPE_QUIZ => 3,
            default => 3,
        };
    }

    private function gridColumns(?string $type): int
    {
        return match ($this->normalizeCatalogType($type)) {
            Product::TYPE_COURSE => 3,
            Product::TYPE_PAST_PAPER => 3,
            Product::TYPE_PREDICTION => 3,
            Product::TYPE_NOTE => 3,
            Product::TYPE_QUIZ => 3,
            default => 3,
        };
    }

    private function catalogItems(Request $request): Collection
    {
        $type = $this->normalizeCatalogType($request->type);
        $items = collect();

        if (!$type || $type === Product::TYPE_COURSE) {
            $items = $items->merge($this->courseItems($request));
        }

        if (!$type) {
            $items = $items->merge($this->productItems($request));
        } elseif (in_array($type, [Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION, Product::TYPE_NOTE, Product::TYPE_QUIZ], true)) {
            $items = $items->merge($this->productItems($request));
        }

        $direction = $request->order === 'asc' ? 'asc' : 'desc';

        return $items->sortBy('created_at', SORT_REGULAR, $direction === 'desc')->values();
    }

    private function courseItems(Request $request): Collection
    {
        $query = Course::query()
            ->where('is_approved', 'approved')
            ->where('courses.status', 'active')
            ->whereHas('category.parentCategory', fn ($q) => $q->where('status', 1))
            ->whereHas('category', fn ($q) => $q->where('status', 1));

        $query->when($request->search, fn ($q) => $q->where('title', 'like', '%' . $request->search . '%'));
        $query->when($request->main_category, function ($q) use ($request) {
            $q->whereHas('category.parentCategory', fn ($q) => $q->where('slug', $request->main_category));
        });
        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->whereHas('category', fn ($q) => $q->whereIn('slug', $this->selectedCategorySlugs($request)));
        });
        $query->when($request->filled('price'), function ($q) use ($request) {
            if ($request->price === 'paid') {
                $q->where('price', '>', 0);
            } elseif ($request->price === 'free') {
                $q->where(fn ($q) => $q->where('price', 0)->orWhereNull('price'));
            }
        });

        return $query->with(['instructor:id,name'])
            ->withCount('enrollments')
            ->leftJoin('course_categories', 'courses.category_id', '=', 'course_categories.id')
            ->leftJoin('course_category_translations as t', function ($join) {
                $join->on('t.course_category_id', '=', 'course_categories.id')
                    ->where('t.lang_code', getSessionLanguage());
            })
            ->select(
                'courses.id',
                'courses.slug',
                'courses.title',
                'courses.created_at',
                'courses.instructor_id',
                'courses.category_id',
                'course_categories.slug as category_slug',
                'courses.thumbnail',
                'courses.discount',
                'courses.price',
                'courses.capacity',
                't.name as category_translation_name',
                DB::raw('(SELECT AVG(course_reviews.rating) FROM course_reviews WHERE course_reviews.course_id = courses.id AND course_reviews.status = 1) as reviews_avg_rating')
            )
            ->get()
            ->map(function ($course) {
                return (object) [
                    'source' => 'course',
                    'type' => Product::TYPE_COURSE,
                    'type_label' => __('Course'),
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'thumbnail' => $course->thumbnail,
                    'price' => $course->price,
                    'discount' => $course->discount,
                    'category_id' => $course->category_id,
                    'category_slug' => $course->category_slug,
                    'category_translation_name' => $course->category_translation_name,
                    'created_at' => $course->created_at,
                    'url' => route('course.show', $course->slug),
                    'cart_id' => $course->id,
                    'cart_type' => 'course',
                    'meta' => __('By') . ' ' . ($course->instructor?->name ?? __('Instructor')),
                    'badges' => [],
                    'rating' => number_format($course->reviews_avg_rating ?? 0, 1),
                    'is_purchased' => false,
                    'is_full' => $course->capacity != null && $course->enrollments_count >= $course->capacity,
                    'access_label' => __('Enrolled'),
                ];
            });
    }

    private function productItems(Request $request): Collection
    {
        $type = $this->normalizeCatalogType($request->type);

        $query = Product::approved()
            ->whereIn('type', [Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION, Product::TYPE_NOTE, Product::TYPE_QUIZ])
            ->with([
                'category.translation',
                'category.parentCategory.translation',
                'category.parentCategory.parentCategory.translation',
            ])
            ->select([
                'id',
                'category_id',
                'type',
                'title',
                'slug',
                'thumbnail',
                'price',
                'discount',
                'status',
                'is_approved',
                'metadata',
                'created_at',
                'file_type',
            ]);

        $query->when($request->search, fn ($q) => $q->where('title', 'like', '%' . $request->search . '%'));
        $query->when($type, fn ($q) => $q->where('type', $type));
        $hasActiveSubscription = auth('web')->check() && hasActiveSubscription();
        $purchasedProductIds = $hasActiveSubscription ? [] : $this->purchasedProductIds();

        return $query
            ->get()
            ->filter(fn (Product $product) => $this->catalogProductMatchesSelection($product, $request))
            ->map(function (Product $product) use ($purchasedProductIds, $hasActiveSubscription) {
                $metadata = $product->metadata ?? [];

                return (object) [
                    'source' => 'product',
                    'type' => $product->type,
                    'type_label' => $product->type_label,
                    'id' => $product->id,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'thumbnail' => $product->thumbnail,
                    'price' => $product->price,
                    'discount' => $product->discount,
                    'category_id' => $product->category_id,
                    'category_slug' => $product->category?->slug,
                    'category_translation_name' => $product->category?->translation?->name ?? $product->category?->slug,
                    'created_at' => $product->created_at,
                    'url' => route('product.show', $product->slug),
                    'cart_id' => $product->id,
                    'cart_type' => 'product',
                    'meta' => $this->productMeta($product),
                    'paper_subject' => $metadata['subject'] ?? null,
                    'paper_year' => $metadata['year'] ?? null,
                    'paper_exam_category' => $metadata['exam_category'] ?? null,
                    'paper_level' => $metadata['education_level'] ?? null,
                    'paper_class_grade' => $metadata['class_grade'] ?? null,
                    'paper_section' => $metadata['paper'] ?? null,
                    'file_type_label' => strtoupper((string) ($product->file_type ?? 'PDF')),
                    'badges' => $product->type === Product::TYPE_QUIZ ? array_values(array_filter([
                        $metadata['difficulty'] ?? null,
                        $metadata['tier'] ?? null,
                    ])) : [],
                    'rating' => null,
                    'is_purchased' => false,
                    'is_full' => false,
                    'access_label' => $product->access_label,
                ];
            });
    }

    private function productMeta(Product $product): string
    {
        $metadata = $product->metadata ?? [];
        $separator = ' | ';

        return match ($product->type) {
            Product::TYPE_PAST_PAPER => collect([
                $metadata['subject'] ?? null,
                $metadata['year'] ?? null,
                strtoupper((string) ($product->file_type ?? '')),
            ])->filter()->implode($separator),
            Product::TYPE_PREDICTION => collect([
                $metadata['subject'] ?? null,
                $metadata['year'] ?? null,
                strtoupper((string) ($product->file_type ?? '')),
            ])->filter()->implode($separator),
            Product::TYPE_NOTE => collect([
                $metadata['subject'] ?? null,
                $metadata['estimated_read_minutes'] ?? null ? $metadata['estimated_read_minutes'] . ' ' . __('min read') : null,
            ])->filter()->implode($separator),
            Product::TYPE_QUIZ => collect([
                $metadata['question_count'] ?? null ? $metadata['question_count'] . ' ' . __('questions') : null,
                $metadata['duration'] ?? null ? $metadata['duration'] . ' ' . __('min') : null,
            ])->filter()->implode($separator),
            default => '',
        };
    }

    private function purchasedProductIds(): array
    {
        if (!auth('web')->check()) {
            return [];
        }

        if (hasActiveSubscription()) {
            return Product::approved()->pluck('id')->filter()->unique()->values()->all();
        }

        return OrderItem::where('item_type', 'product')
            ->whereHas('order', fn ($q) => $q->where('buyer_id', userAuth()->id)->where('payment_status', 'paid'))
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function paginateCollection(Collection $items, int $perPage, int $page): LengthAwarePaginator
    {
        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function selectedCategorySlugs(Request $request): array
    {
        $category = collect(explode(',', (string) $request->category))
            ->filter()
            ->map(fn ($slug) => $this->normalizeCatalogSlug((string) $slug))
            ->filter()
            ->take(1)
            ->values()
            ->all();

        return $category;
    }

    private function catalogProductMatchesSelection(Product $product, Request $request): bool
    {
        return $this->identityService->matchesRequest($product, $request);
    }

    private function catalogSummaryCountsFromItems(Collection $items): array
    {
        $counts = $items->groupBy('type')->map->count()->all();

        return [
            'total' => $items->count(),
            Product::TYPE_COURSE => (int) ($counts[Product::TYPE_COURSE] ?? 0),
            Product::TYPE_NOTE => (int) ($counts[Product::TYPE_NOTE] ?? 0),
            Product::TYPE_PAST_PAPER => (int) ($counts[Product::TYPE_PAST_PAPER] ?? 0),
            Product::TYPE_PREDICTION => (int) ($counts[Product::TYPE_PREDICTION] ?? 0),
            Product::TYPE_QUIZ => (int) ($counts[Product::TYPE_QUIZ] ?? 0),
        ];
    }

    private function buildCatalogCachePayload(Request $request): array
    {
        $items = $this->catalogItems($request)->values();

        return [
            'items' => $items->all(),
            'summary' => $this->catalogSummaryCountsFromItems($items),
        ];
    }

    private function decorateCatalogItems(Collection $items): Collection
    {
        $sessionEnrollments = collect(session('enrollments') ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $hasActiveSubscription = auth('web')->check() && hasActiveSubscription();
        $purchasedProductIds = $hasActiveSubscription ? [] : $this->purchasedProductIds();

        return $items->map(function ($item) use ($sessionEnrollments, $purchasedProductIds, $hasActiveSubscription) {
            $item->is_purchased = match ($item->source ?? null) {
                'course' => in_array((int) $item->id, $sessionEnrollments, true),
                'product' => $hasActiveSubscription || in_array((int) $item->id, $purchasedProductIds, true),
                default => false,
            };

            return $item;
        });
    }

    private function catalogCacheKey(Request $request): string
    {
        $type = $this->normalizeCatalogType($request->input('type', ''));

        $filters = [
            'version' => CatalogCacheClear::version(),
            'lang' => getSessionLanguage(),
            'search' => (string) $request->input('search', ''),
            'category' => (string) ($this->normalizeCatalogSlug($request->input('category', '')) ?? ''),
            'main_category' => (string) ($this->normalizeCatalogSlug($request->input('main_category', '')) ?? ''),
            'type' => (string) $type,
            'order' => (string) $request->input('order', ''),
            'subject' => (string) $request->input('subject', ''),
        ];

        return 'catalog:v6:' . sha1(json_encode($filters));
    }

    private function catalogCacheTtl()
    {
        return now()->addMinutes(10);
    }

    private function resolveCatalogPresentationData(Request $request, MenuCacheService $menuCacheService): array
    {
        $categoryTree = collect($menuCacheService->getCategoryTree(getSessionLanguage()));
        $currentMainCategory = $request->filled('main_category')
            ? $categoryTree->firstWhere('slug', $this->normalizeCatalogSlug($request->main_category))
            : null;

        $currentCategoryTitle = (string) data_get($currentMainCategory, 'name', __('Catalog'));
        $selectedCategorySlug = (string) collect(explode(',', (string) $request->category))
            ->filter()
            ->map(fn ($slug) => $this->normalizeCatalogSlug((string) $slug))
            ->filter()
            ->first();
        $curriculumLevels = collect(data_get($currentMainCategory, 'children', []));
        $selectedCurriculumLevel = null;
        $subjectItems = collect();
        $selectedSubject = null;

        if ($selectedCategorySlug !== '') {
            $selectedCurriculumLevel = $curriculumLevels->firstWhere('slug', $selectedCategorySlug);
            $subjectItems = $selectedCurriculumLevel ? collect(data_get($selectedCurriculumLevel, 'children', [])) : collect();
            $selectedSubject = $selectedCurriculumLevel ? $subjectItems->firstWhere('slug', $request->subject) : null;
        }

        $selectedFocus = $this->resolveCatalogFocus(
            $currentMainCategory,
            $selectedCurriculumLevel,
            $selectedSubject,
            $currentCategoryTitle
        );

        $catalogBreadcrumbs = [];
        if ($currentMainCategory) {
            $catalogBreadcrumbs[] = [
                'label' => __('Home'),
                'url' => route('home'),
            ];
            $catalogBreadcrumbs[] = [
                'label' => $currentCategoryTitle,
                'url' => route('catalog', ['main_category' => $currentMainCategory['slug'] ?? '']),
            ];
            if (!empty(data_get($selectedCurriculumLevel, 'name'))) {
                $catalogBreadcrumbs[] = [
                    'label' => data_get($selectedCurriculumLevel, 'name'),
                    'url' => route('catalog', [
                        'main_category' => $currentMainCategory['slug'] ?? '',
                        'category' => data_get($selectedCurriculumLevel, 'slug'),
                    ]),
                ];
            } elseif (!empty($currentMainCategory['slug'])) {
                $catalogBreadcrumbs[] = [
                    'label' => __('All'),
                    'url' => route('catalog', [
                        'main_category' => $currentMainCategory['slug'] ?? '',
                    ]),
                ];
            }
            if (!empty(data_get($selectedFocus, 'is_subject')) && !empty(data_get($selectedSubject, 'name'))) {
                $catalogBreadcrumbs[] = [
                    'label' => data_get($selectedSubject, 'name'),
                    'url' => '',
                ];
            }
        }

        return [
            'pageTitle' => (string) data_get($selectedFocus, 'label', trim($currentCategoryTitle . ' - ' . __('All'), ' -')),
            'focusName' => (string) data_get($selectedFocus, 'name', __('All')),
            'currentCategoryTitle' => $currentCategoryTitle,
            'selectedLevelName' => (string) data_get($selectedCurriculumLevel, 'name', __('All')),
            'selectedSubjectName' => (string) data_get($selectedSubject, 'name', ''),
            'catalogBreadcrumbs' => $catalogBreadcrumbs,
        ];
    }

    private function emptyCatalogSummary(): array
    {
        return [
            'total' => 0,
            Product::TYPE_COURSE => 0,
            Product::TYPE_NOTE => 0,
            Product::TYPE_PAST_PAPER => 0,
            Product::TYPE_PREDICTION => 0,
            Product::TYPE_QUIZ => 0,
        ];
    }

    private function normalizeCatalogType(?string $type): ?string
    {
        $value = trim((string) $type);

        if ($value === '' || in_array(strtolower($value), ['all', 'any', 'everything'], true)) {
            return null;
        }

        return preg_replace('/[\s-]+/', '_', strtolower($value)) ?: null;
    }

    private function normalizeCatalogSlug(?string $value): ?string
    {
        $slug = trim((string) $value);

        if ($slug === '') {
            return null;
        }

        if (ctype_digit($slug)) {
            $slug = (string) CourseCategory::query()->whereKey((int) $slug)->value('slug');
        }

        $slug = trim($slug);

        return $slug !== '' ? $slug : null;
    }

    private function resolveCatalogSelection(Request $request, ?array $currentMainCategory): array
    {
        $selectedCategorySlug = (string) collect(explode(',', (string) $request->category))
            ->filter()
            ->map(fn ($slug) => $this->normalizeCatalogSlug((string) $slug))
            ->filter()
            ->first();
        $curriculumLevels = collect(data_get($currentMainCategory, 'children', []));
        $selectedCurriculumLevel = null;
        $subjectItems = collect();
        $selectedSubject = null;

        if ($selectedCategorySlug !== '') {
            $selectedCurriculumLevel = $curriculumLevels->firstWhere('slug', $selectedCategorySlug);
            $subjectItems = $selectedCurriculumLevel ? collect(data_get($selectedCurriculumLevel, 'children', [])) : collect();
            $selectedSubject = $selectedCurriculumLevel ? $subjectItems->firstWhere('slug', $request->subject) : null;
        }

        return compact(
            'selectedCategorySlug',
            'curriculumLevels',
            'selectedCurriculumLevel',
            'subjectItems',
            'selectedSubject'
        );
    }

    private function resolveCatalogToolbarForRequest(Request $request): array
    {
        $categoryTree = collect(app(MenuCacheService::class)->getCategoryTree(getSessionLanguage()));
        $currentMainCategory = $request->filled('main_category')
            ? $categoryTree->firstWhere('slug', $this->normalizeCatalogSlug($request->main_category))
            : null;
        $selection = $this->resolveCatalogSelection($request, $currentMainCategory);

        return $this->resolveCatalogToolbar(
            $request,
            $currentMainCategory,
            $selection['curriculumLevels'],
            $selection['selectedCurriculumLevel'],
            $selection['subjectItems'],
            $selection['selectedSubject']
        );
    }

    private function resolveCatalogToolbar(
        Request $request,
        ?array $currentMainCategory,
        Collection $curriculumLevels,
        $selectedCurriculumLevel,
        Collection $subjectItems,
        $selectedSubject
    ): array {
        $selectedMainSlug = (string) data_get($currentMainCategory, 'slug', '');
        $selectedLevelSlug = (string) data_get($selectedCurriculumLevel, 'slug', '');
        $selectedSubjectSlug = (string) data_get($selectedSubject, 'slug', '');
        $catalogQuery = collect($request->only(['search', 'type', 'order']))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
        $topicSource = collect();
        $topicMode = 'category';
        $topicLabel = __('Browse By');

        if (collect(data_get($selectedSubject, 'children', []))->isNotEmpty()) {
            $topicSource = collect(data_get($selectedSubject, 'children', []));
            $topicMode = 'subject-child';
        } elseif ($subjectItems->isNotEmpty()) {
            $topicSource = $subjectItems;
            $topicMode = 'subject';
        } else {
            $topicSource = $curriculumLevels;
            $topicMode = 'category';
        }

        $items = $topicSource
            ->map(function ($topic) use ($catalogQuery, $selectedMainSlug, $selectedLevelSlug, $selectedSubjectSlug, $topicMode) {
                $topicSlug = (string) data_get($topic, 'slug', '');
                $topicName = (string) data_get($topic, 'name', data_get($topic, 'label', $topicSlug));
                $routeParams = array_merge($catalogQuery, ['main_category' => $selectedMainSlug]);
                $categorySlug = '';
                $subjectSlug = '';

                if ($topicMode === 'category') {
                    $categorySlug = $topicSlug;
                    $routeParams['category'] = $topicSlug;
                } else {
                    $categorySlug = $selectedLevelSlug;
                    $subjectSlug = $topicSlug;
                    $routeParams['category'] = $selectedLevelSlug;
                    $routeParams['subject'] = $topicSlug;
                }

                return [
                    'name' => $topicName,
                    'slug' => $topicSlug,
                    'href' => route('catalog', $routeParams),
                    'category' => $categorySlug,
                    'subject' => $subjectSlug,
                    'is_active' => ($topicMode === 'category' && $selectedLevelSlug === $topicSlug && $selectedSubjectSlug === '')
                        || ($topicMode !== 'category' && $selectedSubjectSlug === $topicSlug),
                ];
            })
            ->filter(fn ($topic) => $topic['slug'] !== '' && $topic['name'] !== '')
            ->values();

        return [
            'label' => $topicLabel,
            'items' => $items,
            'selected_type' => (string) $request->input('type', ''),
            'main_category' => $selectedMainSlug,
        ];
    }

    private function prePrimarySubjectItems(): array
    {
        return [
            ['slug' => 'mathematics', 'name' => 'Mathematics', 'icon' => '123', 'color' => '#5b37ff'],
            ['slug' => 'english', 'name' => 'English', 'icon' => 'AB', 'color' => '#ff8a00'],
            ['slug' => 'environmental-activities', 'name' => 'Environmental Activities', 'icon' => 'EA', 'color' => '#2fb344'],
            ['slug' => 'creative-activities', 'name' => 'Creative Activities', 'icon' => 'CA', 'color' => '#ff5ea8'],
            ['slug' => 'religious-education', 'name' => 'Religious Education', 'icon' => 'RE', 'color' => '#0ca678'],
            ['slug' => 'hygiene-and-nutrition', 'name' => 'Hygiene & Nutrition', 'icon' => 'HN', 'color' => '#13b8a6'],
        ];
    }

    private function resolveCatalogFocus(?array $currentMainCategory, $selectedCurriculumLevel, $selectedSubject, string $currentCategoryTitle): array
    {
        $levelName = (string) data_get($selectedCurriculumLevel, 'name', '');
        $subjectName = (string) data_get($selectedSubject, 'name', '');
        $subjectSlug = (string) data_get($selectedSubject, 'slug', '');
        $levelSlug = (string) data_get($selectedCurriculumLevel, 'slug', '');

        if ($subjectName !== '') {
            return [
                'label' => trim($levelName . ' - ' . $subjectName, ' -'),
                'name' => $subjectName,
                'slug' => $subjectSlug,
                'is_subject' => true,
            ];
        }

        if ($levelName !== '') {
            return [
                'label' => trim($currentCategoryTitle . ' - ' . $levelName, ' -'),
                'name' => $levelName,
                'slug' => $levelSlug,
                'is_subject' => false,
            ];
        }

        return [
            'label' => trim($currentCategoryTitle . ' - ' . __('All'), ' -'),
            'name' => __('All'),
            'slug' => (string) data_get($currentMainCategory, 'slug', ''),
            'is_subject' => false,
        ];
    }
}
