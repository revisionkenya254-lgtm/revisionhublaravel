<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\CourseDetailsCollection;
use App\Http\Resources\API\CourseLanguageResource;
use App\Http\Resources\API\CourseLevelResource;
use App\Http\Resources\API\CourseListResource;
use App\Http\Resources\API\CourseReviewsResource;
use App\Http\Resources\API\CustomPageResource;
use App\Http\Resources\API\FaqResource;
use App\Http\Resources\API\LanguageResource;
use App\Http\Resources\API\LessonResource;
use App\Http\Resources\API\MultiCurrencyResource;
use App\Http\Resources\API\OnBoardingScreenResource;
use App\Http\Resources\API\ProductListResource;
use App\Http\Resources\API\SocialLinkResource;
use App\Models\Course;
use App\Models\CourseChapterLesson;
use App\Models\CourseReview;
use App\Models\Product;
use App\Services\CacheService;
use App\Services\ProductIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Models\CourseLanguage;
use Modules\Course\app\Models\CourseCategory;
use Modules\Course\app\Models\CourseLevel;
use Modules\Currency\app\Models\MultiCurrency;
use Modules\Faq\app\Models\Faq;
use Modules\GlobalSetting\app\Models\Setting;
use Modules\Language\app\Models\Language;
use Modules\Location\app\Models\Country;
use Modules\PageBuilder\app\Models\CustomPage;
use Modules\SocialLink\app\Models\SocialLink;

class FrontendController extends Controller {
    
    public function __construct(
        protected CacheService $cacheService,
        protected \App\Services\MenuCacheService $menuCacheService,
        protected ProductIdentityService $productIdentityService
    ) {}

    public function settings(): JsonResponse {
        $setting_list = ['app_name', 'logo', 'timezone', 'primary_color', 'secondary_color'];
        $settings = $this->cacheService->getSettings($setting_list);

        $data = [
            'app_name'        => (string) ($settings['app_name'] ?? ''),
            'logo'            => (string) ($settings['logo'] ?? ''),
            'timezone'        => (string) ($settings['timezone'] ?? ''),
            'primary_color'   => (string) ($settings['primary_color'] ?? ''),
            'secondary_color' => (string) ($settings['secondary_color'] ?? ''),
        ];
        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ], 200);
    }

    public function mainCategories(Request $request): JsonResponse
    {
        $code = strtolower($request->query('language', 'en'));
        $limit = $request->filled('limit') && is_numeric($request->limit) ? (int) $request->limit : -1;
        $categories = $this->cacheService->getMainCategories($code, $limit);

        if (! empty($categories)) {
            return response()->json(['status' => 'success', 'data' => $categories], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function allLanguages(): JsonResponse {
        $languages = $this->cacheService->getLanguages();
        
        if (!empty($languages)) {
            // Convert cached array back to collection for resource
            $languagesCollection = collect($languages);
            $data = LanguageResource::collection($languagesCollection);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function allCurrency(): JsonResponse {
        $currencies = $this->cacheService->getCurrencies();
        
        if (!empty($currencies)) {
            $currenciesCollection = collect($currencies);
            $data = MultiCurrencyResource::collection($currenciesCollection);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function course_languages(Request $request): JsonResponse {
        $limit = $request->filled('limit') && is_numeric($request->limit) ? (int) $request->limit : -1;
        $languages = $this->cacheService->getCourseLanguages($limit);
        
        if (!empty($languages)) {
            $languagesCollection = collect($languages);
            $data = CourseLanguageResource::collection($languagesCollection);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function course_levels(Request $request): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));
        $limit = $request->filled('limit') && is_numeric($request->limit) ? (int) $request->limit : -1;

        $levels = $this->cacheService->getCourseLevels($code, $limit);

        if (!empty($levels)) {
            $levelsCollection = collect($levels);
            $data = CourseLevelResource::collection($levelsCollection);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function sub_categories(Request $request, string $slug): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));
        $limit = $request->filled('limit') && is_numeric($request->limit) ? (int) $request->limit : -1;
        $categories = $this->cacheService->getSubCategories($slug, $code, $limit);

        if (!empty($categories)) {
            return response()->json(['status' => 'success', 'data' => $categories], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function popular_courses(Request $request): JsonResponse {
        $limit = $request->filled('limit') && is_numeric($request->limit) ? (int) $request->limit : 2;
        $courses = $this->cacheService->getPopularCourses($limit);

        if (!empty($courses)) {
            $coursesCollection = collect($courses);
            $data = CourseListResource::collection($coursesCollection);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function fresh_courses(Request $request): JsonResponse {
        $limit = $request->filled('limit') && is_numeric($request->limit) ? (int) $request->limit : 2;
        $courses = $this->cacheService->getFreshCourses($limit);

        if (!empty($courses)) {
            $coursesCollection = collect($courses);
            $data = CourseListResource::collection($coursesCollection);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function products(Request $request, ?string $type = null): JsonResponse
    {
        $categorySlugs = collect(explode(',', (string) $request->query('category')))
            ->map(fn (string $slug) => trim($slug))
            ->filter()
            ->values();
        $categoryPath = collect(explode('/', trim((string) $request->query('category_path'), '/')))
            ->map(fn (string $slug) => trim($slug))
            ->filter()
            ->values();

        $selectionRequest = clone $request;
        if ($categoryPath->isNotEmpty()) {
            $selectionRequest->merge([
                'main_category' => $categoryPath->get(0),
                'category' => $categoryPath->get(1),
                'subject' => $categoryPath->get(2),
            ]);
        }

        $products = Product::active()
            ->select([
                'id', 'instructor_id', 'category_id', 'type', 'title', 'slug', 'thumbnail',
                'description', 'price', 'discount', 'metadata', 'created_at',
            ])
            ->when(auth('sanctum')->check(), fn ($query) => $query->withExists([
                'orderItems as is_purchased' => fn ($orderItems) => $orderItems
                    ->where('item_type', 'product')
                    ->whereHas('order', fn ($order) => $order
                        ->where('buyer_id', auth('sanctum')->id())
                        ->where('status', 'completed')
                        ->where('payment_status', 'paid')),
            ]))
            ->with([
                'instructor:id,name,image',
                'category:id,slug,parent_id',
                'category.translation:course_category_id,lang_code,name',
                'category.parentCategory:id,slug,parent_id',
                'category.parentCategory.parentCategory:id,slug',
            ])
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->when($categoryPath->isEmpty() && $categorySlugs->isNotEmpty(), fn ($query) => $query->whereHas(
                'category',
                fn ($categoryQuery) => $categoryQuery->where('status', 1)->whereIn('slug', $categorySlugs)
            ))
            ->latest()
            ->get()
            ->when(
                $categoryPath->isNotEmpty() || $request->filled('main_category') || $request->filled('subject'),
                fn ($items) => $items->filter(fn (Product $product) => $this->productIdentityService->matchesRequest($product, $selectionRequest))->values()
            );

        if ($type !== null) {
            return response()->json([
                'status' => 'success',
                'data' => ProductListResource::collection($products),
            ]);
        }

        $types = [
            Product::TYPE_COURSE,
            Product::TYPE_PAST_PAPER,
            Product::TYPE_PREDICTION,
            Product::TYPE_NOTE,
            Product::TYPE_QUIZ,
        ];

        $data = [];
        foreach ($types as $productType) {
            $data[$productType] = ProductListResource::collection(
                $products->where('type', $productType)->values()
            );
        }

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function product(Request $request, string $type, string $slug): JsonResponse
    {
        $categoryPath = collect(explode('/', trim((string) $request->query('category_path'), '/')))
            ->map(fn (string $categorySlug) => trim($categorySlug))
            ->filter()
            ->values();

        $selectionRequest = clone $request;
        $selectionRequest->merge([
            'main_category' => $categoryPath->get(0),
            'category' => $categoryPath->get(1),
            'subject' => $categoryPath->get(2),
        ]);

        $product = Product::active()
            ->select([
                'id', 'instructor_id', 'category_id', 'type', 'title', 'slug', 'thumbnail',
                'description', 'price', 'discount', 'metadata', 'created_at', 'updated_at',
            ])
            ->when(auth('sanctum')->check(), fn ($query) => $query->withExists([
                'orderItems as is_purchased' => fn ($orderItems) => $orderItems
                    ->where('item_type', 'product')
                    ->whereHas('order', fn ($order) => $order
                        ->where('buyer_id', auth('sanctum')->id())
                        ->where('status', 'completed')
                        ->where('payment_status', 'paid')),
            ]))
            ->with([
                'instructor:id,name,image',
                'category:id,slug,parent_id',
                'category.translation:course_category_id,lang_code,name',
                'category.parentCategory:id,slug,parent_id',
                'category.parentCategory.parentCategory:id,slug',
            ])
            ->where('type', $type)
            ->where('slug', $slug)
            ->first();

        if ($product !== null && ! $this->productIdentityService->matchesRequest($product, $selectionRequest)) {
            $product = null;
        }

        if ($product === null) {
            return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new ProductListResource($product),
        ]);
    }

    public function search_courses(Request $request): JsonResponse {
        $limit = $request->filled('limit') && is_numeric($request->limit) ? min(max((int) $request->limit, 1), 100) : 6;
        $allowedTypes = [
            Product::TYPE_COURSE,
            Product::TYPE_PAST_PAPER,
            Product::TYPE_PREDICTION,
            Product::TYPE_NOTE,
            Product::TYPE_QUIZ,
        ];

        $query = Product::active()
            ->select([
                'id', 'instructor_id', 'category_id', 'type', 'title', 'slug', 'thumbnail',
                'description', 'price', 'discount', 'metadata', 'created_at',
            ])
            ->with([
                'instructor:id,name,image',
                'category:id,slug,parent_id',
                'category.translation:course_category_id,lang_code,name',
                'category.parentCategory:id,slug,parent_id',
                'category.parentCategory.translation:course_category_id,lang_code,name',
                'category.parentCategory.parentCategory:id,slug,parent_id',
                'category.parentCategory.parentCategory.translation:course_category_id,lang_code,name',
            ])
            ->whereHas('category', fn($q) => $q->where('status', 1));

        if (auth('sanctum')->check()) {
            $query->withExists(['orderItems as is_purchased' => fn($orderItems) => $orderItems
                ->where('item_type', 'product')
                ->whereHas('order', fn($order) => $order
                    ->where('buyer_id', auth('sanctum')->id())
                    ->where('status', 'completed')
                    ->where('payment_status', 'paid')),
            ]);
        }

        $query->when($request->filled('search'), fn($q) => $q->where(function ($q) use ($request) {
            $q->where('title', 'like', "%{$request->search}%")
                ->orWhere('slug', 'like', "%{$request->search}%")
                ->orWhere('type', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%")
                ->orWhere('price', 'like', "%{$request->search}%")
                ->orWhere('discount', 'like', "%{$request->search}%");
        }));

        $types = collect(explode(',', (string) $request->query('type', $request->query('types', ''))))
            ->map(fn (string $type) => trim(strtolower($type)))
            ->filter(fn (string $type) => in_array($type, $allowedTypes, true))
            ->values();
        $query->when($types->isNotEmpty(), fn ($q) => $q->whereIn('type', $types));

        $query->when($request->filled('languages'), function ($q) use ($request) {
            $languages_names = explode(',', $request->languages);
            $q->whereHas('course.languages.language', function ($q) use ($languages_names) {
                $q->whereIn('name', $languages_names);
            });
        });

        $query->when($request->filled('levels'), function ($q) use ($request) {
            $levelSlugs = explode(',', $request->levels);
            $q->whereHas('course.levels.level', function ($q) use ($levelSlugs) {
                $q->whereIn('slug', $levelSlugs);
            });
        });

        $query->when($request->filled('price'), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                if ($request->price == 'paid') {
                    $q->where('price', '>', 0);
                } elseif ($request->price == 'free') {
                    $q->where('price', 0)->orWhereNull('price');
                }
            });
        });

        $query->when($request->filled('rating'), function ($q) use ($request) {
            $rating = (int) $request->rating;
            $q->whereHas('reviews', function ($q) use ($rating) {
                $q->where('status', 1)
                    ->groupBy('product_id')
                    ->havingRaw('coalesce(avg(rating), 0) >= ?', [$rating]);
            });
        });

        $selectionRequest = clone $request;
        $categoryPath = collect(explode('/', trim((string) $request->query('category_path'), '/')))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values();

        if ($categoryPath->isNotEmpty()) {
            $selectionRequest->merge([
                'main_category' => $categoryPath->get(0),
                'category' => $categoryPath->get(1),
                'subject' => $categoryPath->get(2),
            ]);
        } else {
            $selectionRequest->merge([
                'main_category' => $request->query('main_category', $request->query('education_level')),
                'category' => $request->query('category', $request->query('sub_category')),
            ]);
        }

        $hasCatalogFilter = $selectionRequest->filled('main_category')
            || $selectionRequest->filled('category')
            || $selectionRequest->filled('subject');

        if ($hasCatalogFilter) {
            $allProducts = $query->latest()->get()
                ->filter(fn (Product $product) => $this->productIdentityService->matchesRequest($product, $selectionRequest))
                ->values();
            $page = LengthAwarePaginator::resolveCurrentPage();
            $courses = new LengthAwarePaginator(
                $allProducts->forPage($page, $limit)->values(),
                $allProducts->count(),
                $limit,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $courses = $query->latest()->paginate($limit);
        }

        if ($courses->isNotEmpty()) {
            $data = ProductListResource::collection($courses);
            return response()->json(['status' => 'success',
                'data'                            => $data,
                'pagination'                      => [
                    'current_page' => $courses->currentPage(),
                    'per_page'     => $courses->perPage(),
                    'total'        => $courses->total(),
                    'last_page'    => $courses->lastPage(),
                    'links'        => [
                        'first' => $courses->url(1),
                        'prev'  => $courses->previousPageUrl(),
                        'next'  => $courses->nextPageUrl(),
                        'last'  => $courses->url($courses->lastPage()),
                    ],
                ],
            ], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function course_details(string $slug): JsonResponse {
        $user_id = request('user_id', 0);
        $course = Course::active()->where('slug', $slug)->select('id', 'instructor_id', 'demo_video_source', 'demo_video_storage', 'thumbnail', 'title', 'slug', 'price', 'discount', 'description', 'updated_at')->with([
            'instructor:id,name,image',
            'chapters'                           => function ($query) {
                $query->where('status', 'active')->select('id', 'course_id', 'title')->orderBy('order', 'asc')->with([
                    'chapterItems' => fn($q) => $q->select('id', 'chapter_id', 'type')
                        ->where(fn($items) => $items->where('type', '!=', 'lesson')
                            ->orWhereHas('lesson', fn($lesson) => $lesson->where('include_in_curriculum', true))),
                    'chapterItems.quiz'   => fn($q)   => $q->select('id', 'chapter_item_id', 'title')->where('status', 'active'),
                    'chapterItems.lesson' => fn($q) => $q->select('id', 'chapter_item_id', 'title', 'lecture_number', 'file_path', 'storage', 'file_type', 'duration', 'is_free', 'include_in_curriculum', 'qna_enabled')->where('status', 'active'),
                ]);
            },
            'languages:id,course_id,language_id' => ['language:id,name'],
        ])->whereHas('category.parentCategory', fn($q) => $q->where('status', 1))
            ->whereHas('category', fn($q) => $q->where('status', 1))->withCount([
            'reviews as average_rating' => fn($q) => $q->select(DB::raw('coalesce(avg(rating), 0)'))->where('status', 1),
            'reviews'                   => fn($q)                   => $q->where('status', 1),
            'lessons', 'quizzes', 'enrollments',
            'favoriteBy as is_wishlist' => function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            },
        ])->first();

        if ($course) {
            $data = new CourseDetailsCollection($course);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function get_lesson_info(int $lesson_id): JsonResponse {
        // Fetch lesson details
        $lesson = CourseChapterLesson::where('is_free', 1)
            ->with(['resources' => fn($query) => $query->where('status', 'active')])
            ->select('id', 'course_id', 'chapter_id', 'chapter_item_id', 'title', 'lecture_number', 'description', 'downloadable', 'file_path', 'storage', 'file_type', 'duration', 'is_free', 'include_in_curriculum', 'qna_enabled', 'qna_allow_questions', 'qna_allow_replies', 'qna_instructions')
            ->find($lesson_id);

        if (!$lesson) {
            return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
        }
        $data = new LessonResource($lesson);
        return response()->json(['status' => 'success', 'data' => $data], 200);

    }
    public function course_reviews(Request $request, string $slug): JsonResponse {
        $limit = $request->filled('limit') && is_numeric($request->limit) ? (int) $request->limit : 5;

        $reviews = CourseReview::whereHas('course', fn($q) => $q->where('slug', $slug))->where('status', 1)
            ->whereHas('user')
            ->with('user')->orderBy('created_at', 'desc')->paginate($limit);

        if ($reviews) {
            $data = CourseReviewsResource::collection($reviews);
            return response()->json(['status' => 'success',
                'data'                            => $data,
                'pagination'                      => [
                    'current_page' => $reviews->currentPage(),
                    'per_page'     => $reviews->perPage(),
                    'total'        => $reviews->total(),
                    'last_page'    => $reviews->lastPage(),
                    'links'        => [
                        'first' => $reviews->url(1),
                        'prev'  => $reviews->previousPageUrl(),
                        'next'  => $reviews->nextPageUrl(),
                        'last'  => $reviews->url($reviews->lastPage()),
                    ],
                ],

            ], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function privacy_policy(): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));

        $page = CustomPage::select('id', 'slug')->whereSlug('privacy-policy')->with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code)->select('custom_page_id', 'name', 'content');
        }])->first();

        if ($page) {
            $data = new CustomPageResource($page);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function page(string $slug): JsonResponse
    {
        $code = strtolower(request()->query('language', 'en'));
        $page = CustomPage::select('id', 'slug')->whereSlug($slug)->with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code)->select('custom_page_id', 'name', 'content');
        }])->first();

        if ($page) {
            $data = new CustomPageResource($page);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }

        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function terms_and_conditions(): JsonResponse {
        $code = strtolower(request()->query('language', 'en'));
        $page = CustomPage::select('id', 'slug')->whereSlug('terms-and-conditions')->with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code)->select('custom_page_id', 'name', 'content');
        }])->first();
        if ($page) {
            $data = new CustomPageResource($page);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function faqs(Request $request): JsonResponse {
        $limit = $request->filled('limit') && is_numeric($request->limit) ? (int) $request->limit : 4;
        $code = strtolower(request()->query('language', 'en'));

        $faqs = Faq::select('id')->with(['translations' => function ($q) use ($code) {
            $q->where('lang_code', $code)->select('faq_id', 'question', 'answer');
        }])->latest()->paginate($limit);
        if ($faqs->isNotEmpty()) {
            $data = FaqResource::collection($faqs);
            return response()->json(['status' => 'success',
                'data'                            => $data,
                'pagination'                      => [
                    'current_page' => $faqs->currentPage(),
                    'per_page'     => $faqs->perPage(),
                    'total'        => $faqs->total(),
                    'last_page'    => $faqs->lastPage(),
                    'links'        => [
                        'first' => $faqs->url(1),
                        'prev'  => $faqs->previousPageUrl(),
                        'next'  => $faqs->nextPageUrl(),
                        'last'  => $faqs->url($faqs->lastPage()),
                    ],
                ],
            ], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function on_boarding_screen(): JsonResponse {
        $screens = [
            [
                'title'       => 'Welcome to Skillgro',
                'description' => 'Discover a world of knowledge and unlock your potential with our curated courses.',
            ],
            [
                'title'       => 'Learn at Your Pace',
                'description' => 'Access courses anytime, anywhere, and track your progress as you grow.',
            ],
            [
                'title'       => 'Showcase Your Skills',
                'description' => 'Complete courses to earn certificates and take your career to new heights.',
            ],
        ];
        $screensCollection = collect($screens);

        if ($screensCollection) {
            $data = OnBoardingScreenResource::collection($screensCollection);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    public function country_list(): JsonResponse {
        $countries = $this->cacheService->getCountries();
        
        if (!empty($countries)) {
            return response()->json(['status' => 'success', 'data' => $countries], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }

    public function socialLinks(): JsonResponse {
        $socialLinks = $this->cacheService->getSocialLinks();
        
        if (!empty($socialLinks)) {
            $socialLinksCollection = collect($socialLinks);
            $data = SocialLinkResource::collection($socialLinksCollection);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
    }
    /**
     * Get complete menu structure (cached for 24 hours)
     * This endpoint provides all menu data in one call - perfect for populating navigation
     */
    public function menu(): JsonResponse
    {
        $languageCode = strtolower(request()->query('language', 'en'));
        $menuData = $this->menuCacheService->getMenuStructure($languageCode);

        return response()->json([
            'status' => 'success',
            'data' => $menuData,
            'cache_info' => $this->menuCacheService->getMenuCacheInfo(),
        ], 200);
    }

    /**
     * Get simplified menu for mobile apps (cached for 24 hours)
     * Lighter payload optimized for mobile apps
     */
    public function mobileMenu(): JsonResponse
    {
        $languageCode = strtolower(request()->query('language', 'en'));
        $menuData = $this->menuCacheService->getMobileMenu($languageCode);

        return response()->json([
            'status' => 'success',
            'data' => $menuData,
        ], 200);
    }
}
