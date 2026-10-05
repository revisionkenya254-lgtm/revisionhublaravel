<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Product;
use App\Traits\RedirectHelperTrait;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Modules\Coupon\app\Models\Coupon;

class CartController extends Controller {
    use RedirectHelperTrait;

    public function index() {
        if (auth()->check()) {
            $user = userAuth();
            $cart_count = $user->cart_count;
            if ($cart_count == 0) {
                $this->destroyCouponSession();
            }
            $products = $user->carts()->with([
                'course:id,title,slug,price,discount,thumbnail',
                'product:id,title,slug,type,price,discount,thumbnail',
            ])->get(['id', 'user_id', 'course_id', 'product_id', 'item_type']);
        }else{
            $cart_count = Cart::content()->count();
            if (Cart::content()->count() == 0) {
                $this->destroyCouponSession();
            }
            $products = Cart::content();
        }
        
        $cartTotal = $this->cartTotal();
        $discountPercent = Session::has('offer_percentage') ? Session::get('offer_percentage') : 0;
        $discountAmount = ($cartTotal * $discountPercent) / 100;
        $total = defaultCurrency($cartTotal - $discountAmount);
        $coupon = Session::has('coupon_code') ? Session::get('coupon_code') : '';
        return view('frontend.pages.cart', compact('products', 'cart_count','total', 'discountAmount', 'discountPercent', 'coupon'));
    }

    public function addToCart(Request $request,string $id) {
        $shouldRedirectToCheckout = $request->boolean('buy_now');
        $itemType = $request->input('product_type') === 'product' ? 'product' : 'course';
        $item = $itemType === 'product' ? Product::approved()->find($id) : Course::active()->find($id);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Not Found!'], 404);
        }

        if (auth()->check()) {
            $user = userAuth();
            if ($itemType === 'course' && isOwnCourse($user, $item)) {
                return response()->json(['status' => 'error', 'message' => 'You can not add to cart your own course!'], 200);
            }
            if ($itemType === 'course' && hasCourseInPurchased($user, $item)) {
                return response()->json(['status' => 'error', 'message' => 'Already purchased'], 200);
            }
            if ($itemType === 'product' && hasProductInPurchased($user, $item)) {
                return response()->json(['status' => 'error', 'message' => 'Already purchased'], 200);
            }
            if ($itemType === 'course' && hasCourseInCart($user, $item)) {
                return response()->json(['status' => 'error', 'message' => 'Already added to cart'], 200);
            }
            if ($itemType === 'product' && hasProductInCart($user, $item)) {
                return response()->json(['status' => 'error', 'message' => 'Already added to cart'], 200);
            }
            $user->carts()->create([
                'item_type' => $itemType,
                'course_id' => $itemType === 'course' ? $item->id : null,
                'product_id' => $itemType === 'product' ? $item->id : null,
            ]);
            $freshCartCount = (int) optional($user->fresh())->cart_count;
            $response = ['status'     => 'success','message'    => 'Added to cart successfully!','cart_count' => $freshCartCount];
        } else {
            if ($this->checkItemExist($id, $itemType)) {
                $response = ['status' => 'error', 'message' => 'Already added to cart'];

                if ($shouldRedirectToCheckout) {
                    $response['redirect_url'] = route('checkout.index');
                }

                return response()->json($response, 200);
            }
            $cartData = $this->prepareCartData($item, $itemType);
            Cart::add($cartData);
            $response = ['status' => 'success','message' => 'Added to cart successfully!','cart_count' => Cart::content()->count()];
        }

        if ($shouldRedirectToCheckout) {
            $response['redirect_url'] = route('checkout.index');
        }

        $this->handleGoogleTagManager($item, $response, $itemType);
        $this->updateCouponDiscountAmount();

        return response()->json($response, 200);
    }

    private function prepareCartData($item, string $itemType = 'course') {
        $price = $item->discount > 0 ? $item->discount : $item->price;
        return [
            'id'      => $itemType === 'product' ? 'product-' . $item->id : $item->id,
            'name'    => $item->title,
            'qty'     => 1,
            'price'   => $price,
            'weight'  => 0,
            'options' => [
                'image'          => $item->thumbnail,
                'slug'           => $item->slug,
                'real_price'     => $item->price,
                'discount_price' => $item->discount,
                'item_type'      => $itemType,
                'product_id'     => $itemType === 'product' ? $item->id : null,
                'course_id'      => $itemType === 'course' ? $item->id : null,
            ],
        ];
    }

    private function handleGoogleTagManager($item, &$response, string $itemType = 'course') {
        $settings = cache()->get('setting');
        $marketingSettings = cache()->get('marketing_setting');

        if ($settings->google_tagmanager_status == 'active' && $marketingSettings->add_to_cart) {
            $cartData = $this->prepareCartData($item, $itemType);
            $cartData['price'] = currency($item->price);
            $cartData['options']['real_price'] = currency($item->price);
            $cartData['options']['image'] = asset($item->thumbnail);
            $cartData['options']['slug'] = $itemType === 'product' ? route('product.show', $item->slug) : route('course.show', $item->slug);
            unset($cartData['id']);
            $cartData['user'] = auth('web')->check() ? [
                'name'  => auth('web')->user()->name,
                'email' => auth('web')->user()->email,
            ] : 'guest';

            $response['dataLayer'] = $cartData;
        }
    }

    public function removeCartItem(string $rowId) {
        if (auth()->check()) {
            $user = userAuth();
            $course = Course::select('id')->whereSlug($rowId)->first();
            $product = Product::select('id')->whereSlug($rowId)->first();
            if ($course && $user->carts()->where('course_id', $course->id)->exists()) {
                $user->carts()->where('course_id', $course->id)->delete();
            } elseif ($product && $user->carts()->where('product_id', $product->id)->exists()) {
                $user->carts()->where('product_id', $product->id)->delete();
            } else {
                $notification = [
                    'messege'    => __('Not Found!'),
                    'alert-type' => 'error',
                ];
                return redirect()->back()->with($notification);
            }
        }else{
            $cartItem = Cart::get($rowId)?->toArray();
            if ($cartItem) {
                unset($cartItem['rowId'], $cartItem['id']);
                $cartItem['price'] = currency($cartItem['price']);
                $cartItem['options']['real_price'] = currency($cartItem['options']['real_price']);
                $cartItem['options']['image'] = asset($cartItem['options']['image']);
                $cartItem['options']['slug'] = ($cartItem['options']['item_type'] ?? 'course') === 'product'
                    ? route('product.show', $cartItem['options']['slug'])
                    : route('course.show', $cartItem['options']['slug']);
                $cartItem['user'] = auth('web')->check() ? [
                    'name'  => auth('web')->user()->name,
                    'email' => auth('web')->user()->email,
                ] : 'guest';
    
                $settings = cache()->get('setting');
                $marketingSettings = cache()->get('marketing_setting');
                if ($settings->google_tagmanager_status == 'active' && $marketingSettings->remove_from_cart) {
                    session()->put('removeFromCart', $cartItem);
                }
    
            }
            Cart::remove($rowId);
        }
        $notification = [
            'messege'    => __('Item removed from cart!'),
            'alert-type' => 'success',
        ];

        $this->updateCouponDiscountAmount();

        return redirect()->back()->with($notification);
    }

    public function cartTotal() {
        $cartTotal = 0;
        if (auth()->check()) {
            $cartTotal = userAuth()->cart_total;
        }else{
            $cartItems = Cart::content();
        foreach ($cartItems as $key => $cartItem) {
            $cartTotal += $cartItem->price;
        }
        }
        return $cartTotal;
    }

    public function checkItemExist(string $id, string $itemType = 'course') {
        $cartItems = Cart::content();
        $cartId = $itemType === 'product' ? 'product-' . $id : $id;
        foreach ($cartItems as $key => $cartItem) {
            if ($cartItem->id == $cartId) {
                return true;
            }
        }
        return false;
    }

    public function applyCoupon(Request $request) {
        $rules = [
            'coupon' => 'required',
        ];
        $customMessages = [
            'coupon.required' => __('Coupon is required'),
        ];

        $request->validate($rules, $customMessages);

        $coupon = Coupon::where(['coupon_code' => $request->coupon, 'status' => 'active'])->first();

        if (!$coupon) {
            $notification = __('Invalid coupon');

            return response()->json(['message' => $notification], 403);
        }

        if ($coupon->expired_date < date('Y-m-d')) {
            $notification = __('Coupon already expired');

            return response()->json(['message' => $notification], 403);
        }

        if ($this->cartTotal() < $coupon->min_price) {
            $notification = __('Minimum order amount should be :amount', ['amount' => defaultCurrency($coupon->min_price)]);

            return response()->json(['message' => $notification], 403);
        }
        if ($this->cartTotal() <= 0) {
            $notification = __('Cart amount should be greater than 0');

            return response()->json(['message' => $notification], 403);
        }

        $discountAmount = defaultCurrency(($this->cartTotal() * $coupon->offer_percentage) / 100);
        $total = defaultCurrency($this->cartTotal() - ($this->cartTotal() * $coupon->offer_percentage) / 100);

        /** when coupon will be handle for particular seller or author , above condition will be used  */
        Session::put('coupon_code', $coupon->coupon_code);
        Session::put('offer_percentage', $coupon->offer_percentage);
        Session::put('coupon_discount_amount', ($this->cartTotal() * $coupon->offer_percentage) / 100);

        $notification = __('Coupon applied successful');

        return response()->json(['message' => $notification, 'coupon_code' => $coupon->coupon_code, 'offer_percentage' => $coupon->offer_percentage, 'discount_amount' => $discountAmount, 'total' => $total]);
    }

    public function updateCouponDiscountAmount() {
        if (!Session::has('coupon_code')) {
            return;
        }

        $coupon = Coupon::where(['coupon_code' => Session::get('coupon_code'), 'status' => 'active'])->first();
        // update discount amount
        Session::put('coupon_discount_amount', ($this->cartTotal() * $coupon->offer_percentage) / 100);
    }

    public function removeCoupon() {

        $this->destroyCouponSession();

        $notification = [
            'messege'    => __('Coupon removed successfully!'),
            'alert-type' => 'success',
        ];
        return redirect()->back()->with($notification);
    }

    public function destroyCouponSession() {
        Session::forget('coupon_code');
        Session::forget('offer_percentage');
        Session::forget('coupon_discount_amount');
    }
}
