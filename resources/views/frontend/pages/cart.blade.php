@extends('frontend.layouts.master')
@section('meta_title', 'Cart' . ' || ' . $setting->app_name)

@section('contents')
    <!-- breadcrumb-area -->
    <x-frontend.breadcrumb :title="__('Cart')" :links="[['url' => route('home'), 'text' => __('Home')], ['url' => route('cart'), 'text' => __('Cart')]]" />
    <!-- breadcrumb-area-end -->

    <!-- cart-area -->
    <div class="cart__area section-py-120">
        <div class="container">
            @auth('web')
                @foreach ($products as $item)
                    @if ($item->item_type === 'course' && in_array($item?->course?->id, session()->get('enrollments')))
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img"
                                aria-label="Warning:">
                                <path
                                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                            </svg>
                            <div>
                                {{ __('You have items in your cart that you already purchased. before proceed please remove those from cart ') }}
                            </div>
                        </div>
                    @elseif ($item->item_type === 'course' && in_array($item?->course?->id, session()->get('instructor_courses')))
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img"
                                aria-label="Warning:">
                                <path
                                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                            </svg>
                            <div>
                                {{ __('You have your own courses in your cart. before proceed please remove those from cart ') }}
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($cart_count > 0)
                    <div class="row">
                        <div class="col-lg-8">
                            <table class="table cart__table">
                                <thead>
                                    <tr>
                                        <th class="product__thumb">&nbsp;</th>
                                        <th class="product__name">{{ __('Resource') }}</th>
                                        <th class="product__price">{{ __('Price') }}</th>
                                        <th class="product__remove">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                        @php
                                            $cartItem = $product->purchasable();
                                            $cartUrl = $product->item_type === 'product' ? route('product.show', $cartItem?->slug) : route('course.show', $cartItem?->slug);
                                        @endphp
                                        <tr>
                                            <td class="product__thumb pe-2">
                                                <a href="{{ $cartUrl }}"><img
                                                        src="{{ asset($cartItem?->thumbnail) }}" alt=""></a>
                                            </td>
                                            <td class="product__name">
                                                <a
                                                    href="{{ $cartUrl }}">{{ $cartItem?->title }}</a>
                                                <br>
                                                @if ($product->item_type === 'course' && in_array($product?->course?->id, session()->get('enrollments')))
                                                    <span class="badge bg-warning mt-2">{{ __('Already purchased') }}</span>
                                                @elseif ($product->item_type === 'course' && in_array($product?->course?->id, session()->get('instructor_courses')))
                                                    <span class="badge bg-warning mt-2">{{ __('Own course') }}</span>
                                                @elseif ($product->item_type === 'product')
                                                    <span class="badge bg-info mt-2">{{ $cartItem?->type_label }}</span>
                                                @else
                                                @endif
                                            </td>
                                            <td class="product__price">{{ $cartItem?->discount > 0 ? defaultCurrency($cartItem?->discount) : defaultCurrency($cartItem?->price) }}</td>
                                            @if ($product->item_type === 'product')
                                            <td class="product__remove">
                                                <a href="{{ route('remove-cart-item', $cartItem?->slug) }}">×</a>
                                            </td>
                                            @else
                                            <td class="product__remove">
                                                <a href="{{ route('remove-cart-item', $product?->course?->slug) }}">×</a>
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="6" class="cart__actions">
                                            <form action="{{ route('apply-coupon') }}" class="cart__actions-form coupon-form"
                                                method="POST">
                                                @csrf
                                                <input type="text" name="coupon" placeholder="Coupon code">
                                                <button type="submit" class="btn">{{ __('Apply coupon') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-4">
                            <div class="cart__collaterals-wrap">
                                <h2 class="title">{{ __('Cart totals') }}</h2>
                                <ul class="list-wrap">
                                    <li>{{ __('Total Items') }}<span>{{ $cart_count }}</span></li>
                                    <li>
                                        @if (Session::has('coupon_code'))
                                            <p class="coupon-discount m-0">
                                                <span>{{ __('Discount') }}</span>
                                                <br>
                                                <small>{{ $coupon }} ({{ $discountPercent }} %)<a
                                                        class="ms-2 text-danger" href="/remove-coupon">×</a></small>
                                            </p>
                                            <span class="discount-amount">{{ defaultCurrency($discountAmount) }}</span>
                                        @else
                                            <p class="coupon-discount m-0">
                                                <span>{{ __('Discount') }}</span>
                                            </p>
                                            <span class="discount-amount">{{ defaultCurrency(0) }}</span>
                                        @endif
                                    </li>
                                    <li>{{ __('Total') }} <span class="amount">{{ $total }}</span></li>
                                </ul>
                                <a href="{{ route('checkout.index') }}" class="cart-checkout-btn">
                                    <span>{{ __('Proceed to checkout') }}</span>
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="w-100 text-center">
                        <img class="mb-4" src="{{ asset('uploads/website-images/empty-cart.png') }}" alt="">
                        <h4 class="text-center">{{ __('Cart is empty!') }}</h4>
                        <p class="text-center">
                            {{ __('Please add some resources in your cart.') }}
                        </p>
                    </div>
                @endif
            @else
                @foreach ($products as $item)
                    @if (($item->options['item_type'] ?? 'course') === 'course' && in_array($item->id, session()->get('enrollments')))
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img"
                                aria-label="Warning:">
                                <path
                                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                            </svg>
                            <div>
                                {{ __('You have items in your cart that you already purchased. before proceed please remove those from cart ') }}
                            </div>
                        </div>
                    @elseif (($item->options['item_type'] ?? 'course') === 'course' && in_array($item->id, session()->get('instructor_courses')))
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img"
                                aria-label="Warning:">
                                <path
                                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                            </svg>
                            <div>
                                {{ __('You have your own courses in your cart. before proceed please remove those from cart ') }}
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($cart_count > 0)
                    <div class="row">
                        <div class="col-lg-8">
                            <table class="table cart__table">
                                <thead>
                                    <tr>
                                        <th class="product__thumb">&nbsp;</th>
                                        <th class="product__name">{{ __('Resource') }}</th>
                                        <th class="product__price">{{ __('Price') }}</th>
                                        <th class="product__remove">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                        <tr>
                                            <td class="product__thumb pe-2">
                                                <a href="{{ ($product->options['item_type'] ?? 'course') === 'product' ? route('product.show', $product->options['slug']) : route('course.show', $product->options['slug']) }}"><img
                                                        src="{{ asset($product->options['image']) }}" alt=""></a>
                                            </td>
                                            <td class="product__name">
                                                <a
                                                    href="{{ ($product->options['item_type'] ?? 'course') === 'product' ? route('product.show', $product->options['slug']) : route('course.show', $product->options['slug']) }}">{{ $product->name }}</a>
                                                <br>
                                                @if (($product->options['item_type'] ?? 'course') === 'course' && in_array($product->id, session()->get('enrollments')))
                                                    <span class="badge bg-warning mt-2">{{ __('Already purchased') }}</span>
                                                @elseif (($product->options['item_type'] ?? 'course') === 'course' && in_array($product->id, session()->get('instructor_courses')))
                                                    <span class="badge bg-warning mt-2">{{ __('Own course') }}</span>
                                                @elseif (($product->options['item_type'] ?? 'course') === 'product')
                                                    <span class="badge bg-info mt-2">{{ __('Product') }}</span>
                                                @else
                                                @endif
                                            </td>
                                            <td class="product__price">{{ defaultCurrency($product->price) }}</td>
                                            <td class="product__remove">
                                                <a href="{{ route('remove-cart-item', $product->rowId) }}">×</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="6" class="cart__actions">
                                            <form action="{{ route('apply-coupon') }}" class="cart__actions-form coupon-form"
                                                method="POST">
                                                @csrf
                                                <input type="text" name="coupon" placeholder="Coupon code">
                                                <button type="submit" class="btn">{{ __('Apply coupon') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-4">
                            <div class="cart__collaterals-wrap">
                                <h2 class="title">{{ __('Cart totals') }}</h2>
                                <ul class="list-wrap">
                                    <li>{{ __('Total Items') }}<span>{{ $cart_count }}</span></li>
                                    <li>
                                        @if (Session::has('coupon_code'))
                                            <p class="coupon-discount m-0">
                                                <span>{{ __('Discount') }}</span>
                                                <br>
                                                <small>{{ $coupon }} ({{ $discountPercent }} %)<a
                                                        class="ms-2 text-danger" href="/remove-coupon">×</a></small>
                                            </p>
                                            <span class="discount-amount">{{ defaultCurrency($discountAmount) }}</span>
                                        @else
                                            <p class="coupon-discount m-0">
                                                <span>{{ __('Discount') }}</span>
                                            </p>
                                            <span class="discount-amount">{{ defaultCurrency(0) }}</span>
                                        @endif
                                    </li>
                                    <li>{{ __('Total') }} <span class="amount">{{ $total }}</span></li>
                                </ul>
                                <a href="{{ route('checkout.index') }}" class="cart-checkout-btn">
                                    <span>{{ __('Proceed to checkout') }}</span>
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="w-100 text-center">
                        <img class="mb-4" src="{{ asset('uploads/website-images/empty-cart.png') }}" alt="">
                        <h4 class="text-center">{{ __('Cart is empty!') }}</h4>
                        <p class="text-center">
                            {{ __('Please add some resources in your cart.') }}
                        </p>
                    </div>
                @endif
            @endauth
        </div>
    </div>
    <!-- cart-area-end -->
@endsection
@push('styles')
    <style>
        .cart-checkout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 56px;
            padding: 0 22px;
            border: 3px solid #161439;
            border-radius: 999px;
            background: #f7c41d;
            color: #161439;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.01em;
            box-shadow: 0 10px 0 #161439, 0 16px 28px rgba(22, 20, 57, 0.14);
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }

        .cart-checkout-btn:hover,
        .cart-checkout-btn:focus-visible {
            transform: translateY(-1px);
            filter: brightness(1.02);
            color: #161439;
            box-shadow: 0 12px 0 #161439, 0 18px 30px rgba(22, 20, 57, 0.16);
        }

        .cart-checkout-btn i {
            font-size: 15px;
        }
    </style>
@endpush
@if (session('removeFromCart') &&
        $setting->google_tagmanager_status == 'active' &&
        $marketing_setting?->remove_from_cart)
    @php
        $removeFromCart = session('removeFromCart');
        session()->forget('removeFromCart');
    @endphp
    @push('scripts')
        <script>
            $(function() {
                dataLayer.push({
                    'event': 'removeFromCart',
                    'cart_details': @json($removeFromCart)
                });
            });
        </script>
    @endpush
@endif
