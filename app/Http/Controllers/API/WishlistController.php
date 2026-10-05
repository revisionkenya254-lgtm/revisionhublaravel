<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 20), 1), 100);
        $items = WishlistItem::query()
            ->where('user_id', $request->user()->id)
            ->with('wishlistable')
            ->latest()
            ->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $items->getCollection()->map(fn (WishlistItem $item) => $this->serializeItem($item)),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_type' => ['required', 'string', 'in:course,note,quiz,prediction,past_paper,past-paper,pastpaper,paspaper'],
            'item_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $itemType = $this->normalizeItemType($request->string('item_type')->toString());
        [$modelClass, $item] = $this->resolveItem($itemType, (int) $request->integer('item_id'));

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Item not found.'], 404);
        }

        $wishlistItem = WishlistItem::firstOrCreate([
            'user_id' => $request->user()->id,
            'wishlistable_type' => $modelClass,
            'wishlistable_id' => $item->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $wishlistItem->wasRecentlyCreated ? 'Item added to wishlist.' : 'Item is already in wishlist.',
            'data' => $this->serializeItem($wishlistItem->load('wishlistable')),
        ], $wishlistItem->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, string $itemType, int $itemId): JsonResponse
    {
        [$modelClass, $item] = $this->resolveItem($this->normalizeItemType($itemType), $itemId);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Item not found.'], 404);
        }

        $deleted = WishlistItem::where('user_id', $request->user()->id)
            ->where('wishlistable_type', $modelClass)
            ->where('wishlistable_id', $item->id)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => $deleted ? 'Item removed from wishlist.' : 'Item was not in wishlist.',
        ]);
    }

    private function resolveItem(string $itemType, int $itemId): array
    {
        if ($itemType === 'course') {
            return [Course::class, Course::active()->find($itemId)];
        }

        if (in_array($itemType, [
            Product::TYPE_NOTE,
            Product::TYPE_QUIZ,
            Product::TYPE_PREDICTION,
            Product::TYPE_PAST_PAPER,
        ], true)) {
            return [Product::class, Product::active()->where('type', $itemType)->find($itemId)];
        }

        return match ($itemType) {
            default => [null, null],
        };
    }

    private function normalizeItemType(string $itemType): string
    {
        return match (strtolower($itemType)) {
            'past-paper', 'pastpaper', 'paspaper' => Product::TYPE_PAST_PAPER,
            default => strtolower($itemType),
        };
    }

    private function serializeItem(WishlistItem $wishlistItem): array
    {
        $item = $wishlistItem->wishlistable;
        $itemType = $item instanceof Course ? 'course' : $item?->type;

        return [
            'id' => $wishlistItem->id,
            'item_type' => $itemType,
            'item_id' => $item?->id,
            'item' => $item ? [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'thumbnail' => $item->thumbnail,
                'description' => $item->description,
                'price' => $item->price,
                'discount' => $item->discount,
            ] : null,
            'created_at' => $wishlistItem->created_at,
        ];
    }
}
