<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Product;
use App\Models\ProductReview;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Modules\PaymentWithdraw\app\Models\WithdrawRequest;

class InstructorDashboardController extends Controller
{
    public function index(): View
    {
        $instructorId = userAuth()->id;

        $courses = Course::where('instructor_id', $instructorId)
            ->with(['levels.level.translation'])
            ->get(['id', 'title', 'slug', 'thumbnail', 'status', 'is_approved', 'created_at', 'instructor_id']);

        $products = Product::instructorOwned($instructorId)
            ->nonCourse()
            ->get(['id', 'title', 'slug', 'thumbnail', 'type', 'status', 'is_approved', 'created_at', 'instructor_id']);

        $courseIds = $courses->pluck('id')->all();
        $productIds = $products->pluck('id')->all();

        $paidItems = OrderItem::query()
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->where(function ($query) use ($courseIds, $productIds) {
                $query->where(function ($subQuery) use ($courseIds) {
                    $subQuery->where('item_type', 'course')->whereIn('course_id', $courseIds);
                })->orWhere(function ($subQuery) use ($productIds) {
                    $subQuery->where('item_type', 'product')->whereIn('product_id', $productIds);
                });
            })
            ->with([
                'order:id,invoice_id,buyer_id,status,payment_status,payable_currency,paid_amount,created_at',
                'order.user:id,name,image',
                'course:id,title,slug,thumbnail,instructor_id',
                'course.levels.level.translation',
                'product:id,title,slug,thumbnail,type,instructor_id',
            ])
            ->latest('id')
            ->get();

        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $contentCreatedThisMonth = $courses->filter(function ($item) use ($currentMonthStart, $currentMonthEnd) {
            return $item->created_at?->between($currentMonthStart, $currentMonthEnd);
        })->count() + $products->filter(function ($item) use ($currentMonthStart, $currentMonthEnd) {
            return $item->created_at?->between($currentMonthStart, $currentMonthEnd);
        })->count();

        $contentCreatedLastMonth = $courses->filter(function ($item) use ($previousMonthStart, $previousMonthEnd) {
            return $item->created_at?->between($previousMonthStart, $previousMonthEnd);
        })->count() + $products->filter(function ($item) use ($previousMonthStart, $previousMonthEnd) {
            return $item->created_at?->between($previousMonthStart, $previousMonthEnd);
        })->count();

        $pendingCourses = $courses->where('is_approved', 'pending')->count();
        $pendingProducts = $products->where('is_approved', 'pending')->count();
        $pendingContent = $pendingCourses + $pendingProducts;

        $totalProducts = $courses->count() + $products->count();
        $totalSales = (int) $paidItems->sum('qty');
        $totalOrders = (int) $paidItems->pluck('order_id')->unique()->count();
        $totalStudents = (int) $paidItems->pluck('order.buyer_id')->filter()->unique()->count();
        $totalWithdraw = WithdrawRequest::where(['user_id' => $instructorId, 'status' => 'approved'])->sum('withdraw_amount');
        $pendingBalance = (float) userAuth()->instructorEarningsHolds()->where('status', 'pending')->sum('amount');
        $currentBalance = (float) userAuth()->wallet_balance;

        $itemValue = function (OrderItem $item): float {
            return (float) $item->price * max((int) $item->qty, 1);
        };

        $periodStats = function (Carbon $start, Carbon $end) use ($paidItems, $itemValue) {
            $items = $paidItems->filter(function (OrderItem $item) use ($start, $end) {
                $createdAt = $item->order?->created_at;

                return $createdAt && $createdAt->between($start, $end);
            });

            return [
                'sales' => (int) $items->sum('qty'),
                'orders' => (int) $items->pluck('order_id')->unique()->count(),
                'students' => (int) $items->pluck('order.buyer_id')->filter()->unique()->count(),
                'earnings' => (float) $items->sum(fn (OrderItem $item) => $itemValue($item)),
            ];
        };

        $currentMonthStats = $periodStats($currentMonthStart, $currentMonthEnd);
        $previousMonthStats = $periodStats($previousMonthStart, $previousMonthEnd);

        $getChange = function (float $current, float $previous): float {
            if ($previous <= 0) {
                return $current > 0 ? 100 : 0;
            }

            return (($current - $previous) / $previous) * 100;
        };

        $earningsByDay = [];
        $daysInMonth = $now->daysInMonth;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($now->year, $now->month, $day)->toDateString();
            $earningsByDay[$date] = 0;
        }

        foreach ($paidItems as $item) {
            $createdAt = $item->order?->created_at;
            if (! $createdAt || ! isset($earningsByDay[$createdAt->toDateString()])) {
                continue;
            }

            $earningsByDay[$createdAt->toDateString()] += $itemValue($item);
        }

        $earningsChart = $this->buildLineChart($earningsByDay);

        $reviews = collect([
            (object) [
                'count' => (int) CourseReview::whereHas('course', function ($query) use ($instructorId) {
                    $query->where('instructor_id', $instructorId);
                })->count(),
                'average' => (float) CourseReview::whereHas('course', function ($query) use ($instructorId) {
                    $query->where('instructor_id', $instructorId);
                })->avg('rating'),
            ],
            (object) [
                'count' => (int) ProductReview::whereIn('product_id', $productIds)->count(),
                'average' => (float) ProductReview::whereIn('product_id', $productIds)->avg('rating'),
            ],
        ])->filter(fn ($item) => $item->count > 0);

        $totalReviewCount = (int) $reviews->sum('count');
        $weightedReviewScore = $totalReviewCount > 0
            ? $reviews->sum(fn ($item) => $item->count * $item->average) / $totalReviewCount
            : 0.0;

        $currentReviewCount = (int) CourseReview::whereHas('course', function ($query) use ($instructorId) {
            $query->where('instructor_id', $instructorId);
        })->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])->count()
            + (int) ProductReview::whereIn('product_id', $productIds)->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])->count();

        $previousReviewCount = (int) CourseReview::whereHas('course', function ($query) use ($instructorId) {
            $query->where('instructor_id', $instructorId);
        })->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->count()
            + (int) ProductReview::whereIn('product_id', $productIds)->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->count();

        $previousReviewAverage = 0.0;
        $previousCourseReviewStats = CourseReview::whereHas('course', function ($query) use ($instructorId) {
            $query->where('instructor_id', $instructorId);
        })->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->selectRaw('COUNT(*) as total, AVG(rating) as average')->first();
        $previousProductReviewStats = ProductReview::whereIn('product_id', $productIds)->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])->selectRaw('COUNT(*) as total, AVG(rating) as average')->first();
        $previousReviewTotal = (int) (($previousCourseReviewStats?->total ?? 0) + ($previousProductReviewStats?->total ?? 0));
        if ($previousReviewTotal > 0) {
            $previousReviewAverage = (
                (($previousCourseReviewStats?->total ?? 0) * (float) ($previousCourseReviewStats?->average ?? 0))
                + (($previousProductReviewStats?->total ?? 0) * (float) ($previousProductReviewStats?->average ?? 0))
            ) / $previousReviewTotal;
        }

        $currentReviewAverage = round($weightedReviewScore, 1);
        $reviewChange = $currentReviewAverage - $previousReviewAverage;

        $topProducts = $this->buildTopProducts($paidItems);
        $recentOrders = $this->buildRecentOrders($paidItems);
        $contentBreakdown = $this->buildContentBreakdown($courses, $products);
        $studentsByLevel = $this->buildStudentsByLevel($courses, $instructorId);

        return view('frontend.instructor-dashboard.index', [
            'courses' => $courses,
            'products' => $products,
            'totalProducts' => $totalProducts,
            'totalSales' => $totalSales,
            'totalRevenue' => $paidItems->sum(fn (OrderItem $item) => $itemValue($item)),
            'totalOrders' => $totalOrders,
            'totalStudents' => $totalStudents,
            'averageRating' => $currentReviewAverage,
            'ratingChange' => $reviewChange,
            'reviewCount' => $totalReviewCount,
            'totalWithdraw' => $totalWithdraw,
            'pendingBalance' => $pendingBalance,
            'currentBalance' => $currentBalance,
            'pendingCourses' => $pendingCourses,
            'pendingProducts' => $pendingProducts,
            'pendingContent' => $pendingContent,
            'contentCreatedThisMonth' => $contentCreatedThisMonth,
            'contentCreatedLastMonth' => $contentCreatedLastMonth,
            'salesChange' => $getChange($currentMonthStats['sales'], $previousMonthStats['sales']),
            'earningsChange' => $getChange($currentMonthStats['earnings'], $previousMonthStats['earnings']),
            'studentChange' => $getChange($currentMonthStats['students'], $previousMonthStats['students']),
            'contentChange' => $getChange($contentCreatedThisMonth, $contentCreatedLastMonth),
            'currentMonthEarnings' => $currentMonthStats['earnings'],
            'previousMonthEarnings' => $previousMonthStats['earnings'],
            'currentMonthSales' => $currentMonthStats['sales'],
            'previousMonthSales' => $previousMonthStats['sales'],
            'currentMonthStudents' => $currentMonthStats['students'],
            'previousMonthStudents' => $previousMonthStats['students'],
            'earningsChart' => $earningsChart,
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
            'contentBreakdown' => $contentBreakdown,
            'studentsByLevel' => $studentsByLevel,
        ]);
    }

    function mySells()
    {
        $instructorId = userAuth()->id;

        // Get course IDs for this instructor
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id')->toArray();

        // Get non-course product IDs for this instructor
        $productIds = Product::instructorOwned($instructorId)
            ->nonCourse()
            ->pluck('id')
            ->toArray();

        // Get paid orders for both courses and products
        $orders = OrderItem::where(function ($query) use ($courseIds, $productIds) {
            $query->where(function ($q) use ($courseIds) {
                $q->where('item_type', 'course')
                    ->whereIn('course_id', $courseIds);
            })->orWhere(function ($q) use ($productIds) {
                $q->where('item_type', 'product')
                    ->whereIn('product_id', $productIds);
            });
        })
            ->with(['order', 'course', 'product'])
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->orderBy('id', 'desc')
            ->paginate(30);

        return view('frontend.instructor-dashboard.my-sells.index', compact('orders'));
    }

    private function buildLineChart(array $values): array
    {
        $points = array_values($values);
        $count = count($points);
        $width = 720;
        $height = 320;
        $paddingLeft = 44;
        $paddingRight = 20;
        $paddingTop = 20;
        $paddingBottom = 40;
        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;
        $maxValue = max($points ?: [0]);
        $maxValue = $maxValue > 0 ? $maxValue : 1;
        $segments = max($count - 1, 1);
        $coordinates = [];

        foreach ($points as $index => $value) {
            $x = $paddingLeft + ($plotWidth * ($index / $segments));
            $y = $paddingTop + ($plotHeight - (($value / $maxValue) * $plotHeight));
            $coordinates[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
            ];
        }

        $path = collect($coordinates)
            ->map(fn (array $point, int $index) => ($index === 0 ? 'M' : 'L') . ' ' . $point['x'] . ' ' . $point['y'])
            ->implode(' ');

        $firstPoint = $coordinates[0] ?? ['x' => $paddingLeft, 'y' => $paddingTop + $plotHeight];
        $lastPoint = $coordinates[$count - 1] ?? $firstPoint;
        $areaPath = $path . ' L ' . $lastPoint['x'] . ' ' . ($paddingTop + $plotHeight) . ' L ' . $firstPoint['x'] . ' ' . ($paddingTop + $plotHeight) . ' Z';

        $ticks = [];
        for ($step = 0; $step <= 4; $step++) {
            $value = ($maxValue / 4) * $step;
            $y = $paddingTop + ($plotHeight - (($value / $maxValue) * $plotHeight));
            $ticks[] = [
                'label' => $value,
                'y' => round($y, 2),
            ];
        }

        return [
            'width' => $width,
            'height' => $height,
            'points' => $coordinates,
            'path' => $path,
            'areaPath' => $areaPath,
            'ticks' => $ticks,
        ];
    }

    private function buildTopProducts(Collection $paidItems): Collection
    {
        return $paidItems
            ->groupBy(function (OrderItem $item) {
                return $item->item_type === 'course'
                    ? 'course:' . $item->course_id
                    : 'product:' . $item->product_id;
            })
            ->map(function (Collection $items) {
                /** @var OrderItem $firstItem */
                $firstItem = $items->first();
                $isCourse = $firstItem->item_type === 'course';
                $model = $isCourse ? $firstItem->course : $firstItem->product;
                $title = $model?->title ?? __('Untitled');

                return (object) [
                    'title' => $title,
                    'subtitle' => $isCourse ? __('Video Lessons') : $this->getProductTypeLabel($model?->type ?? 'product'),
                    'thumbnail' => $model?->thumbnail,
                    'url' => $this->getDashboardItemUrl($firstItem),
                    'sales' => (int) $items->sum('qty'),
                    'earnings' => (float) $items->sum(fn (OrderItem $item) => (float) $item->price * max((int) $item->qty, 1)),
                    'type' => $isCourse ? 'course' : ($model?->type ?? 'product'),
                ];
            })
            ->sortByDesc('earnings')
            ->take(5)
            ->values()
            ->map(function ($item, int $index) {
                $item->rank = $index + 1;

                return $item;
            });
    }

    private function buildRecentOrders(Collection $paidItems): Collection
    {
        return $paidItems
            ->sortByDesc(fn (OrderItem $item) => $item->order?->created_at?->timestamp ?? 0)
            ->take(5)
            ->values()
            ->map(function (OrderItem $item, int $index) {
                $model = $item->item_type === 'course' ? $item->course : $item->product;

                return (object) [
                    'rank' => $index + 1,
                    'invoice' => $item->order?->invoice_id ?? __('N/A'),
                    'student' => $item->order?->user?->name ?? __('Guest'),
                    'student_image' => $item->order?->user?->image,
                    'title' => $model?->title ?? __('Untitled'),
                    'subtitle' => $item->item_type === 'course' ? __('Video Lessons') : $this->getProductTypeLabel($model?->type ?? 'product'),
                    'amount' => (float) $item->price * max((int) $item->qty, 1),
                    'date' => $item->order?->created_at,
                    'status' => $item->order?->status ?? __('pending'),
                    'url' => $this->getDashboardItemUrl($item),
                ];
            });
    }

    private function buildContentBreakdown(Collection $courses, Collection $products): Collection
    {
        $items = collect([
            ['label' => __('Video Lessons'), 'count' => $courses->count(), 'color' => '#5B8DEF'],
            ['label' => __('Notes'), 'count' => $products->where('type', Product::TYPE_NOTE)->count(), 'color' => '#35C78A'],
            ['label' => __('Past Papers'), 'count' => $products->where('type', Product::TYPE_PAST_PAPER)->count(), 'color' => '#F6B93B'],
            ['label' => __('Predictions'), 'count' => $products->where('type', Product::TYPE_PREDICTION)->count(), 'color' => '#9AA0B5'],
            ['label' => __('Quizzes'), 'count' => $products->where('type', Product::TYPE_QUIZ)->count(), 'color' => '#D66BF7'],
        ])->filter(fn (array $item) => $item['count'] > 0)->values();

        $total = max($items->sum('count'), 1);

        return $items->map(function (array $item) use ($total) {
            $item['percent'] = round(($item['count'] / $total) * 100, 1);

            return (object) $item;
        });
    }

    private function buildStudentsByLevel(Collection $courses, int $instructorId): Collection
    {
        $enrollments = Enrollment::query()
            ->whereHas('course', function ($query) use ($instructorId) {
                $query->where('instructor_id', $instructorId);
            })
            ->with(['course.levels.level.translation'])
            ->get(['id', 'course_id', 'user_id']);

        $levels = [];

        foreach ($enrollments as $enrollment) {
            foreach ($enrollment->course?->levels ?? [] as $selectedLevel) {
                $levelName = $selectedLevel->level?->translation?->name
                    ?? $selectedLevel->level?->slug
                    ?? __('Level');

                $levels[$levelName]['users'][$enrollment->user_id] = true;
            }
        }

        $totalStudents = max($enrollments->pluck('user_id')->unique()->count(), 1);

        return collect($levels)
            ->map(function (array $level, string $name) use ($totalStudents) {
                $count = count($level['users'] ?? []);

                return (object) [
                    'label' => $name,
                    'count' => $count,
                    'percent' => round(($count / $totalStudents) * 100, 1),
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values();
    }

    private function getDashboardItemUrl(OrderItem $item): string
    {
        if ($item->item_type === 'course') {
            return route('instructor.courses.edit-view', $item->course_id);
        }

        if ($item->product?->type === Product::TYPE_NOTE) {
            return route('product.show', $item->product->slug);
        }

        if ($item->product?->type === Product::TYPE_QUIZ) {
            return route('instructor.quizzes.edit', $item->product_id);
        }

        if ($item->item_type === 'product') {
            return route('instructor.products.edit', $item->product_id);
        }

        return route('instructor.dashboard');
    }

    private function getProductTypeLabel(?string $type): string
    {
        return match ($type) {
            Product::TYPE_PAST_PAPER => __('Past Papers'),
            Product::TYPE_PREDICTION => __('Predictions'),
            Product::TYPE_NOTE => __('Notes'),
            Product::TYPE_QUIZ => __('Quizzes'),
            Product::TYPE_COURSE => __('Video Lessons'),
            default => __('Products'),
        };
    }
}
