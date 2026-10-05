<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\CourseListResource;
use App\Models\Cart;
use App\Models\Course;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CartController extends Controller {
    public function index(): JsonResponse {
        $user = auth()->user();
        $currency = strtoupper(request()->query('currency'));
        $cartItems = $user->carts()->with(['course.instructor', 'product.instructor'])->get();
        $subscriptionActive = hasActiveSubscription($user);
        $data = [
            'total_qty'    => (int) $user->cartCount,
            'total_amount' => (string) apiCurrency($subscriptionActive ? 0 : $user->cartTotal, $currency),
            'subscription_active' => $subscriptionActive,
            'cart_items'   => $cartItems->map(fn (Cart $cart) => $this->cartItem($cart, $currency, $subscriptionActive))->values(),
        ];

        $courses = $cartItems->where('item_type', '!=', 'product')->pluck('course')->filter();
        if ($courses->isNotEmpty()) {
            $courseIds = $courses->pluck('id');
            $cartCourses = Course::select('id', 'slug', 'title', 'instructor_id', 'thumbnail', 'price', 'discount')
                ->active()
                ->with(['instructor:id,name,image'])
                ->whereIn('id', $courseIds)
                ->withCount([
                    'reviews as average_rating' => fn ($query) => $query
                        ->select(DB::raw('coalesce(avg(rating), 0)'))
                        ->where('status', 1),
                    'enrollments',
                ])
                ->get();

            $data['cart_courses'] = CourseListResource::collection($cartCourses);
        }

        $products = $cartItems->where('item_type', 'product');
        if ($products->isNotEmpty()) {
            $data['cart_products'] = $products
                ->map(fn (Cart $cart) => $this->cartItem($cart, $currency))
                ->values();
        }

        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function add_to_cart(string $slug): JsonResponse {
        $user = auth()->user();
        $course = Course::select('id', 'instructor_id')->whereSlug($slug)->active()->first();
        $product = Product::select('id', 'instructor_id')->whereSlug($slug)->active()->first();

        if (!$course && !$product) {
            return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
        }

        if (hasActiveSubscription($user)) {
            return response()->json([
                'status' => 'success',
                'state' => 'access_granted',
                'message' => 'This item is included in your active subscription.',
                'access_source' => 'subscription',
                'payment_required' => false,
            ], 200);
        }

        $itemType = $product ? 'product' : 'course';
        $item = $product ?: $course;
        $itemId = $item->id;

        if ($item->instructor_id == $user->id) {
            return response()->json(['status' => 'error', 'message' => 'You can not add your own item to cart!'], 400);
        }

        if ($itemType === 'course' && $user->enrollments()->select('course_id')->where('course_id', $itemId)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Already purchased'], 400);
        }

        if ($user->carts()->where('item_type', $itemType)->where($itemType === 'product' ? 'product_id' : 'course_id', $itemId)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Already added to cart!'], 400);
        }

        $user->carts()->create([
            'item_type' => $itemType,
            'course_id' => $itemType === 'course' ? $itemId : null,
            'product_id' => $itemType === 'product' ? $itemId : null,
            'qty' => 1,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Added to cart successfully!', 'cart_count' => $user->cartCount], 200);
    }

    public function remove_from_cart(string $slug): JsonResponse {
        $user = auth()->user();

        $product = Product::select('id')->whereSlug($slug)->first();
        $course = Course::select('id')->whereSlug($slug)->first();
        $cart = $product
            ? $user->carts()->where('item_type', 'product')->where('product_id', $product->id)
            : ($course ? $user->carts()->where('item_type', 'course')->where('course_id', $course->id) : null);

        if (!$cart || !$cart->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
        }

        $cart->delete();
        return response()->json(['status' => 'success', 'message' => 'Item removed from cart!'], 200);
    }

    private function cartItem(Cart $cart, string $currency, bool $subscriptionActive = false): array
    {
        $item = $cart->item_type === 'product' ? $cart->product : $cart->course;
        $price = (float) ($item?->price ?? 0);
        $discount = (float) ($item?->discount ?? 0);

        return [
            'type' => $cart->item_type === 'product' ? 'product' : 'course',
            'product_type' => $cart->item_type === 'product' ? $item?->type : 'course',
            'slug' => (string) ($item?->slug ?? ''),
            'title' => (string) ($item?->title ?? ''),
            'thumbnail' => (string) ($item?->thumbnail ?? ''),
            'price' => $subscriptionActive || $price == 0 ? 0 : (string) apiCurrency($price, $currency),
            'discount' => $subscriptionActive || $discount == 0 ? 0 : (string) apiCurrency($discount, $currency),
            'quantity' => (int) $cart->qty,
            'access_source' => $subscriptionActive ? 'subscription' : null,
            'payment_required' => ! $subscriptionActive,
            'instructor' => $item?->instructor ? [
                'id' => $item->instructor->id,
                'name' => $item->instructor->name,
                'image' => $item->instructor->image,
            ] : null,
        ];
    }
}
