<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\LibraryItemResource;
use App\Models\Product;
use App\Models\ProductNoteProgress;
use App\Models\ProductQuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Order\app\Models\OrderItem;

class LibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:course,note,past_paper,prediction,quiz'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $subscriptionActive = hasActiveSubscription($user);
        $purchasedItems = OrderItem::query()
            ->with('order:id,buyer_id,payment_status,created_at')
            ->where('item_type', 'product')
            ->whereHas('order', fn ($query) => $query
                ->where('buyer_id', $user->id)
                ->where('payment_status', 'paid'))
            ->latest('id')
            ->get()
            ->keyBy('product_id');

        $noteProgress = ProductNoteProgress::query()
            ->where('user_id', $user->id)
            ->latest('last_read_at')
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $items) => $items->first());

        $quizAttempts = ProductQuizAttempt::query()
            ->where('user_id', $user->id)
            ->latest('submitted_at')
            ->get()
            ->groupBy('product_id')
            ->map(fn (Collection $items) => $items->first());

        $items = Product::approved()
            ->with(['category.translation'])
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->get()
            ->filter(fn (Product $product) => $subscriptionActive
                || (float) $product->effective_price === 0.0
                || $purchasedItems->has($product->id))
            ->map(function (Product $product) use ($subscriptionActive, $purchasedItems, $noteProgress, $quizAttempts) {
                $orderItem = $purchasedItems->get($product->id);
                $note = $noteProgress->get($product->id);
                $quiz = $quizAttempts->get($product->id);
                $accessSource = $subscriptionActive ? 'subscription' : ($orderItem ? 'purchase' : 'free');
                $progressPercent = match ($product->type) {
                    Product::TYPE_NOTE => (float) ($note?->completion_percent ?? 0),
                    Product::TYPE_QUIZ => (float) ($quiz?->percentage ?? 0),
                    default => 0.0,
                };
                $progressLabel = match ($product->type) {
                    Product::TYPE_NOTE => $note?->completion_percent !== null ? number_format($progressPercent, 1) . '% read' : 'Ready to read',
                    Product::TYPE_QUIZ => $quiz?->percentage !== null ? number_format($progressPercent, 1) . '% score' : 'Ready to start',
                    Product::TYPE_PAST_PAPER, Product::TYPE_PREDICTION => 'Ready to read',
                    Product::TYPE_COURSE => 'Ready to open',
                    default => 'Open',
                };

                return (object) [
                    'product' => $product,
                    'access_source' => $accessSource,
                    'progress_percent' => $progressPercent,
                    'progress_label' => $progressLabel,
                    'last_activity_at' => collect([$quiz?->submitted_at, $note?->last_read_at, $orderItem?->order?->created_at, $product->updated_at, $product->created_at])->filter()->first(),
                ];
            })
            ->sortByDesc(fn (object $item) => $item->last_activity_at?->timestamp ?? 0)
            ->values();

        $perPage = $validated['per_page'] ?? 12;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'status' => 'success',
            'data' => LibraryItemResource::collection($paginator->getCollection()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
