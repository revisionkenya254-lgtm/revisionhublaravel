<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Models\Course;
use App\Models\CourseChapterItem;
use App\Models\CourseProgress;
use App\Models\CourseReview;
use App\Models\Product;
use App\Models\ProductNoteProgress;
use App\Models\ProductQuizAttempt;
use App\Models\QuizResult;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\CertificateBuilder\app\Models\CertificateBuilder;
use Modules\CertificateBuilder\app\Models\CertificateBuilderItem;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use App\Services\Ai\AiCreditLedgerService;

class StudentDashboardController extends Controller {
    public function index(): View {
        $user = userAuth();
        $accessibleCourses = $this->accessibleCourses();
        $accessibleProducts = $this->accessibleProducts();
        $aiCreditLedger = app(AiCreditLedgerService::class);
        $aiCreditLedger->ensureMonthlyGrant($user);
        $currentAiCredits = $aiCreditLedger->currentBalance($user);
        $aiConversationCount = AiChatConversation::query()->where('user_id', $user->id)->count();
        $totalEnrolledCourses = $this->accessibleCourseCount();
        $totalQuizAttempts = QuizResult::where('user_id', $user->id)->count();
        $totalReviews = CourseReview::where('user_id', $user->id)->count();
        $orders = Order::where('buyer_id', $user->id)->orderByDesc('id')->take(10)->get();
        $dashboardStats = [
            [
                'label' => __('Enrolled Courses'),
                'value' => $totalEnrolledCourses,
                'icon' => 'flaticon-mortarboard',
                'tone' => 'blue',
                'hint' => __('Course enrollments and subscription access'),
            ],
            [
                'label' => __('Library Items'),
                'value' => $accessibleProducts->count(),
                'icon' => 'flaticon-book',
                'tone' => 'green',
                'hint' => __('Notes, past papers, predictions, and quizzes'),
            ],
            [
                'label' => __('Quiz Attempts'),
                'value' => $totalQuizAttempts,
                'icon' => 'fas fa-poll',
                'tone' => 'amber',
                'hint' => __('All course and product quiz sessions'),
            ],
            [
                'label' => __('Reviews'),
                'value' => $totalReviews,
                'icon' => 'fas fa-star',
                'tone' => 'rose',
                'hint' => __('Feedback you have left on content'),
            ],
        ];
        $productTypeCards = $this->buildProductTypeCards($accessibleCourses, $accessibleProducts);
        $productTypeQuickLinks = $this->buildProductTypeQuickLinks();
        $aiWorkspaceCards = $this->buildAiWorkspaceCards($aiConversationCount, $currentAiCredits);
        $recentResources = $this->buildRecentResources($accessibleProducts);
        $continueLearning = $this->buildContinueLearning($accessibleCourses, $accessibleProducts);
        $quickLinks = [
            [
                'label' => __('Go to Library'),
                'url' => route('student.library'),
                'icon' => 'fa-book-open',
                'tone' => 'green',
            ],
            [
                'label' => __('My Quizzes'),
                'url' => route('student.quiz-attempts'),
                'icon' => 'fa-poll',
                'tone' => 'amber',
            ],
            [
                'label' => __('Enrolled Courses'),
                'url' => route('student.enrolled-courses'),
                'icon' => 'fa-graduation-cap',
                'tone' => 'blue',
            ],
            [
                'label' => __('Order History'),
                'url' => route('student.orders.index'),
                'icon' => 'fa-receipt',
                'tone' => 'slate',
            ],
            [
                'label' => __('AI Chat'),
                'url' => route('student.ai-chat.index'),
                'icon' => 'fa-robot',
                'tone' => 'violet',
            ],
            [
                'label' => __('AI Credits'),
                'url' => route('ai-chat.credits'),
                'icon' => 'fa-wallet',
                'tone' => 'blue',
            ],
        ];
        return view('frontend.student-dashboard.index', compact(
            'accessibleCourses',
            'accessibleProducts',
            'totalEnrolledCourses',
            'totalQuizAttempts',
            'totalReviews',
            'dashboardStats',
            'productTypeCards',
            'productTypeQuickLinks',
            'aiWorkspaceCards',
            'recentResources',
            'continueLearning',
            'quickLinks',
            'orders'
        ));
    }

    function enrolledCourses() {
        $courses = $this->accessibleCourses();
        $enrolls = $this->paginateCollection($courses, 10);
        return view('frontend.student-dashboard.enrolled-courses.index', compact('enrolls'));
    }

    function library() {
        $items = $this->accessibleProducts();
        $requestedType = request()->string('type')->toString();

        if (filled($requestedType) && in_array($requestedType, [
            Product::TYPE_NOTE,
            Product::TYPE_PAST_PAPER,
            Product::TYPE_PREDICTION,
            Product::TYPE_QUIZ,
            Product::TYPE_COURSE,
        ], true)) {
            $items = $items->filter(fn (object $item) => $item->product?->type === $requestedType)->values();
        }

        $items = $this->paginateCollection($items, 12);

        return view('frontend.student-dashboard.library.index', compact('items'));
    }

    function quizAttempts() {
        Session::forget('course_slug');
        $quizAttempts = QuizResult::with(['quiz'])->where('user_id', userAuth()->id)->orderByDesc('id')->paginate(10, ['*'], 'course_page');
        $productQuizAttempts = ProductQuizAttempt::with(['product', 'quiz'])
            ->where('user_id', userAuth()->id)
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'product_page');

        return view('frontend.student-dashboard.quiz-attempts.index', compact('quizAttempts', 'productQuizAttempts'));
    }

    function downloadCertificate(string $id) {
        $certificate = CertificateBuilder::first();
        $certificateItems = CertificateBuilderItem::get();
        $course = Course::withTrashed()->find($id);

        $courseLectureCount = CourseChapterItem::whereHas('chapter', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->count();

        $courseLectureCompletedByUser = CourseProgress::where('user_id', userAuth()->id)
            ->where('course_id', $course->id)->where('watched', 1)->latest();

        $completed_date = formatDate($courseLectureCompletedByUser->first()?->created_at);

        $courseLectureCompletedByUser = CourseProgress::where('user_id', userAuth()->id)
            ->where('course_id', $course->id)->where('watched', 1)->count();

        $courseCompletedPercent = $courseLectureCount > 0 ? ($courseLectureCompletedByUser / $courseLectureCount) * 100 : 0;

        if ($courseCompletedPercent != 100) {
            return abort(404);
        }

        $html = view('frontend.student-dashboard.certificate.index', compact('certificateItems', 'certificate'))->render();

        $html = str_replace('[student_name]', userAuth()->name, $html);
        $html = str_replace('[platform_name]', Cache::get('setting')->app_name, $html);
        $html = str_replace('[course]', $course->title, $html);
        $html = str_replace('[date]', formatDate($completed_date), $html);
        $html = str_replace('[instructor_name]', $course->instructor->name, $html);

        // Initialize Dompdf
        $dompdf = new Dompdf(array('enable_remote' => true));

        // Load HTML content
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();
        $dompdf->stream("certificate.pdf");
        return redirect()->back();
    }

    private function accessibleCourses(): Collection
    {
        $user = userAuth();
        $courseMap = [];

        Enrollment::with(['course' => function ($q) {
            $q->withTrashed()->with(['category.translation', 'instructor']);
        }])->where('user_id', $user->id)->orderByDesc('id')->get()->each(function ($enrollment) use (&$courseMap) {
            if ($enrollment->course) {
                $courseMap[$enrollment->course->id] = (object) [
                    'course' => $enrollment->course,
                ];
            }
        });

        if (hasActiveSubscription($user)) {
            Course::active()->withTrashed()->with(['category.translation', 'instructor'])
                ->get()
                ->each(function ($course) use (&$courseMap) {
                    $courseMap[$course->id] = (object) [
                        'course' => $course,
                    ];
                });
        }

        return collect($courseMap)->values();
    }

    private function accessibleProducts(): Collection
    {
        $user = userAuth();
        $subscriptionActive = hasActiveSubscription($user);

        $purchasedOrderItems = OrderItem::with(['order:id,buyer_id,payment_status,created_at', 'product.category.translation', 'product.course'])
            ->where('item_type', 'product')
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id)->where('payment_status', 'paid'))
            ->orderByDesc('id')
            ->get()
            ->keyBy('product_id');

        $latestNoteProgress = ProductNoteProgress::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_read_at')
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $items) => $items->first());

        $latestQuizAttempts = ProductQuizAttempt::query()
            ->where('user_id', $user->id)
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $items) => $items->first());

        return Product::approved()
            ->with(['category.translation', 'course', 'note', 'quiz'])
            ->get()
            ->filter(function (Product $product) use ($subscriptionActive, $purchasedOrderItems) {
                return $subscriptionActive
                    || (float) $product->effective_price === 0.0
                    || $purchasedOrderItems->has($product->id);
            })
            ->map(function (Product $product) use ($subscriptionActive, $purchasedOrderItems, $latestNoteProgress, $latestQuizAttempts) {
                $orderItem = $purchasedOrderItems->get($product->id);
                $noteProgress = $latestNoteProgress->get($product->id);
                $quizAttempt = $latestQuizAttempts->get($product->id);
                $accessSource = $subscriptionActive
                    ? 'subscription'
                    : (($orderItem !== null) ? 'purchase' : 'free');

                return (object) [
                    'product' => $product,
                    'order' => $orderItem?->order,
                    'item_type' => 'product',
                    'access_source' => $accessSource,
                    'access_source_label' => $this->accessSourceLabel($accessSource),
                    'last_activity_at' => $this->resolveLastActivityAt($product, $orderItem?->order?->created_at, $noteProgress?->last_read_at, $quizAttempt?->submitted_at),
                    'progress_percent' => $this->resolveProductProgressPercent($product, $noteProgress, $quizAttempt),
                    'progress_label' => $this->resolveProductProgressLabel($product, $noteProgress, $quizAttempt),
                    'action_url' => $this->productActionUrl($product),
                    'preview_url' => $this->productPreviewUrl($product),
                    'icon' => $this->productIcon($product->type),
                    'tone' => $this->productTone($product->type),
                ];
            })
            ->sortByDesc(fn (object $item) => $item->last_activity_at?->timestamp ?? 0)
            ->values();
    }

    private function accessibleCourseCount(): int
    {
        if (hasActiveSubscription(userAuth())) {
            return Course::active()->count();
        }

        return Enrollment::where('user_id', userAuth()->id)->count();
    }

    private function buildProductTypeCards(Collection $accessibleCourses, Collection $accessibleProducts): array
    {
        $productTypeCounts = $accessibleProducts
            ->groupBy(fn (object $item) => $item->product?->type ?? 'product')
            ->map(fn (Collection $items) => $items->count());

        return [
            [
                'label' => __('Courses'),
                'count' => $accessibleCourses->count(),
                'icon' => 'flaticon-mortarboard',
                'tone' => 'blue',
                'description' => __('Structured lessons, progress tracking, and certificates.'),
                'url' => route('student.enrolled-courses'),
            ],
            [
                'label' => __('Course Products'),
                'count' => $productTypeCounts->get(Product::TYPE_COURSE, 0),
                'icon' => 'fas fa-book-open',
                'tone' => 'slate',
                'description' => __('Standalone course listings that open directly from the library.'),
                'url' => route('student.library'),
            ],
            [
                'label' => __('Notes'),
                'count' => $productTypeCounts->get(Product::TYPE_NOTE, 0),
                'icon' => 'fas fa-sticky-note',
                'tone' => 'pink',
                'description' => __('Readable study notes with bookmarks and topic navigation.'),
                'url' => route('student.library'),
            ],
            [
                'label' => __('Past Papers'),
                'count' => $productTypeCounts->get(Product::TYPE_PAST_PAPER, 0),
                'icon' => 'fas fa-file-alt',
                'tone' => 'green',
                'description' => __('Exam papers and document readers for practice and revision.'),
                'url' => route('student.library'),
            ],
            [
                'label' => __('Predictions'),
                'count' => $productTypeCounts->get(Product::TYPE_PREDICTION, 0),
                'icon' => 'fas fa-bullseye',
                'tone' => 'orange',
                'description' => __('Prediction packs with the same reading flow as documents.'),
                'url' => route('student.library'),
            ],
            [
                'label' => __('Quizzes'),
                'count' => $productTypeCounts->get(Product::TYPE_QUIZ, 0),
                'icon' => 'fas fa-poll',
                'tone' => 'violet',
                'description' => __('Timed quizzes, attempt history, and instant result pages.'),
                'url' => route('student.quiz-attempts'),
            ],
        ];
    }

    private function buildAiWorkspaceCards(int $aiConversationCount, int $currentAiCredits): array
    {
        return [
            [
                'label' => __('AI Chat'),
                'count' => $aiConversationCount,
                'icon' => 'fas fa-robot',
                'tone' => 'violet',
                'description' => __('Continue conversations, generate images, and keep your revision flow moving.'),
                'url' => route('student.ai-chat.index'),
            ],
            [
                'label' => __('AI Credits'),
                'count' => $currentAiCredits,
                'icon' => 'fas fa-wallet',
                'tone' => 'blue',
                'description' => __('Track your monthly allowance, balance, and recharge options.'),
                'url' => route('ai-chat.credits'),
            ],
        ];
    }

    private function buildProductTypeQuickLinks(): array
    {
        return [
            [
                'label' => __('Notes'),
                'url' => route('student.library', ['type' => Product::TYPE_NOTE]),
                'icon' => 'fa-sticky-note',
                'tone' => 'pink',
            ],
            [
                'label' => __('Past Papers'),
                'url' => route('student.library', ['type' => Product::TYPE_PAST_PAPER]),
                'icon' => 'fa-file-alt',
                'tone' => 'green',
            ],
            [
                'label' => __('Predictions'),
                'url' => route('student.library', ['type' => Product::TYPE_PREDICTION]),
                'icon' => 'fa-bullseye',
                'tone' => 'orange',
            ],
            [
                'label' => __('Quizzes'),
                'url' => route('student.library', ['type' => Product::TYPE_QUIZ]),
                'icon' => 'fa-poll',
                'tone' => 'violet',
            ],
        ];
    }

    private function buildRecentResources(Collection $accessibleProducts): Collection
    {
        return $accessibleProducts
            ->take(8)
            ->values();
    }

    private function buildContinueLearning(Collection $accessibleCourses, Collection $accessibleProducts): Collection
    {
        $courseItems = $accessibleCourses->map(function (object $enrollment) {
            $course = $enrollment->course;

            if (!$course) {
                return null;
            }

            $courseLectureCount = CourseChapterItem::whereHas('chapter', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })->count();

            $courseLectureCompleted = CourseProgress::where('user_id', userAuth()->id)
                ->where('course_id', $course->id)
                ->where('watched', 1)
                ->count();

            $courseCompletedPercent = $courseLectureCount > 0 ? ($courseLectureCompleted / $courseLectureCount) * 100 : 0;

            return (object) [
                'title' => $course->title,
                'subtitle' => $course->category?->translation?->name ?? $course->category?->name ?? __('Course'),
                'badge' => __('Course'),
                'badge_tone' => 'blue',
                'thumbnail' => $course->thumbnail ?: 'uploads/website-images/empty-cart.png',
                'action_url' => route('student.learning.index', $course->slug),
                'meta' => [
                    [
                        'label' => __('Progress'),
                        'value' => number_format($courseCompletedPercent, 1) . '%',
                    ],
                    [
                        'label' => __('Lectures'),
                        'value' => (string) $courseLectureCount,
                    ],
                ],
                'progress_percent' => $courseCompletedPercent,
                'progress_label' => number_format($courseCompletedPercent, 1) . '% ' . __('complete'),
                'last_activity_at' => $course->updated_at ?? $course->created_at,
            ];
        })->filter();

        $productItems = $accessibleProducts->filter(fn (object $item) => in_array($item->product?->type, [
            Product::TYPE_NOTE,
            Product::TYPE_PAST_PAPER,
            Product::TYPE_PREDICTION,
            Product::TYPE_QUIZ,
        ], true))->map(function (object $item) {
            $product = $item->product;

            return (object) [
                'title' => $product->title,
                'subtitle' => $product->category?->translation?->name ?? $product->category?->name ?? $product->type_label,
                'badge' => $product->type_label,
                'badge_tone' => $item->tone,
                'thumbnail' => $product->thumbnail ?: 'uploads/website-images/empty-cart.png',
                'action_url' => $item->action_url,
                'meta' => array_values(array_filter([
                    [
                        'label' => __('Access'),
                        'value' => $item->access_source_label,
                    ],
                    $item->progress_percent > 0 ? [
                        'label' => __('Progress'),
                        'value' => $item->progress_label,
                    ] : null,
                ])),
                'progress_percent' => $item->progress_percent,
                'progress_label' => $item->progress_label,
                'last_activity_at' => $item->last_activity_at,
            ];
        });

        return $courseItems
            ->merge($productItems)
            ->sortByDesc(fn (object $item) => $item->last_activity_at?->timestamp ?? 0)
            ->take(6)
            ->values();
    }

    private function resolveLastActivityAt(Product $product, $orderCreatedAt = null, $noteReadAt = null, $quizSubmittedAt = null)
    {
        return collect([$quizSubmittedAt, $noteReadAt, $orderCreatedAt, $product->updated_at, $product->created_at])
            ->filter()
            ->first();
    }

    private function resolveProductProgressPercent(Product $product, ?ProductNoteProgress $noteProgress = null, ?ProductQuizAttempt $quizAttempt = null): float
    {
        return match ($product->type) {
            Product::TYPE_NOTE => (float) ($noteProgress?->completion_percent ?? 0),
            Product::TYPE_QUIZ => (float) ($quizAttempt?->percentage ?? 0),
            default => 0.0,
        };
    }

    private function resolveProductProgressLabel(Product $product, ?ProductNoteProgress $noteProgress = null, ?ProductQuizAttempt $quizAttempt = null): string
    {
        return match ($product->type) {
            Product::TYPE_NOTE => $noteProgress?->completion_percent !== null
                ? number_format((float) $noteProgress->completion_percent, 1) . '% ' . __('read')
                : __('Ready to read'),
            Product::TYPE_QUIZ => $quizAttempt?->percentage !== null
                ? number_format((float) $quizAttempt->percentage, 1) . '% ' . __('score')
                : __('Ready to start'),
            Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION => __('Ready to read'),
            Product::TYPE_COURSE => __('Ready to open'),
            default => __('Open'),
        };
    }

    private function productActionUrl(Product $product): string
    {
        return match ($product->type) {
            Product::TYPE_NOTE => route('product.read-note', $product->slug),
            Product::TYPE_QUIZ => route('product.start-quiz', $product->slug),
            Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION => route('product.read-document', $product->slug),
            Product::TYPE_COURSE => $product->course?->slug ? route('course.show', $product->course->slug) : route('product.show', $product->slug),
            default => route('product.show', $product->slug),
        };
    }

    private function productPreviewUrl(Product $product): ?string
    {
        return in_array($product->type, [Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION], true)
            ? route('product.preview', $product->slug)
            : null;
    }

    private function productIcon(string $type): string
    {
        return match ($type) {
            Product::TYPE_NOTE => 'fas fa-sticky-note',
            Product::TYPE_PAST_PAPER => 'fas fa-file-alt',
            Product::TYPE_PREDICTION => 'fas fa-bullseye',
            Product::TYPE_QUIZ => 'fas fa-poll',
            Product::TYPE_COURSE => 'flaticon-mortarboard',
            default => 'fas fa-layer-group',
        };
    }

    private function productTone(string $type): string
    {
        return match ($type) {
            Product::TYPE_NOTE => 'pink',
            Product::TYPE_PAST_PAPER => 'green',
            Product::TYPE_PREDICTION => 'orange',
            Product::TYPE_QUIZ => 'violet',
            Product::TYPE_COURSE => 'blue',
            default => 'slate',
        };
    }

    private function accessSourceLabel(string $source): string
    {
        return match ($source) {
            'subscription' => __('Subscription'),
            'purchase' => __('Purchased'),
            'free' => __('Free'),
            default => __('Open'),
        };
    }

    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
