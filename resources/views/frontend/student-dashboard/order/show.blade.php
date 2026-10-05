@extends('frontend.student-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title">
            <h4 class="title">{{ __('Order History') }}</h4>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="dashboard__review-table">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row">
                                <div class="col-lg-12 info-wrapper">
                                    <div class="row w-100">
                                        <div class="col-12 col-sm-6 col-md-4">
                                            <div class="invoice-title">
                                                <h2>{{ __('Invoice') }}</h2>
                                                <div class="invoice-number">{{ __('Order ') }} #{{ $order->invoice_id }}
                                                </div>
                                                <span
                                                    class="d-block"><strong>{{ __('Order Date') }}:</strong>{{ formatDate($order->created_at) }}</span>
                                                @if ($order->isBundleOrder())
                                                    <span class="d-block"><strong>{{ __('Bundle Name') }}:</strong>
                                                        {{ $order?->order_details?->title }}</span>
                                                @endif
                                                @if ($order->isSubscriptionOrder())
                                                    <span class="d-block"><strong>{{ __('Subscription Plan') }}:</strong>
                                                        {{ $order?->order_details?->title }}</span>
                                                @endif
                                                @if ($order->isGiftOrder())
                                                    <address>
                                                        <strong>{{ __('Gift') }}:</strong>
                                                        @if (empty($order?->order_details?->verification_token))
                                                            <div class="badge bg-success">{{ __('Claimed') }}</div>
                                                        @else
                                                        <div class="badge bg-warning">{{ __('Pending') }}</div>
                                                        @endif
                                                        <br>
                                                        {{ __('Recipient Name') }}:
                                                        {{ $order?->order_details?->recipient_name }}<br>
                                                        {{ __('Recipient Email') }}:
                                                        {{ $order?->order_details?->recipient_email }}<br>
                                                    </address>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-4">
                                            <address>
                                                <strong>{{ __('Billed To') }}:</strong><br>
                                                {{ $order->user->name }}<br>
                                                {{ __('Phone:') }} {{ $order->user->phone }}<br>
                                                {{ __('Email') }} {{ $order->user->email }}<br>
                                                {{ __('Address') }} {{ $order->user->address }}<br>
                                            </address>
                                        </div>
                                        <div class="col-12 col-sm-6 col-md-4">
                                            <address class="text-capitalize">
                                                <strong>{{ __('Payment Method') }}:</strong><br>
                                                {{ str_replace('_',' ',$order->payment_method) }}<br>
                                            </address>
                                            @if($order?->transaction_id)
                                                <address class="text-capitalize">
                                                    <strong>{{ __('Transaction ID') }}:</strong><br>
                                                    {{ $order->transaction_id }}<br>
                                                </address>
                                            @endif
                                            <address>
                                                <strong>{{ __('Payment Status') }}:</strong><br>
                                                {{ $order->payment_status }}<br><br>
                                            </address>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="section-title">{{ __('Order Summary') }}</div>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-md">
                                            <tr>
                                                <th data-width="40">#</th>
                                                <th>{{ __('Item') }}</th>
                                                <th>{{ __('by') }}</th>
                                                <th class="text-center">{{ __('Price') }}</th>
                                            </tr>
                                            @if ($order->isSubscriptionOrder())
                                                <tr>
                                                    <td>1</td>
                                                    <td>{{ $order?->order_details?->title }}</td>
                                                    <td>
                                                        {{ __('Subscription Plan') }}
                                                        <br>
                                                        {{ __('Digital access') }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ number_format($order->payable_amount * $order->conversion_rate, 2) }}
                                                        {{ $order->payable_currency }}
                                                    </td>
                                                </tr>
                                            @else
                                                @foreach ($order->orderItems as $item)
                                                    @php
                                                        $orderItem = $item->purchasable();
                                                        $bylineName = $item->item_type === 'course' ? $item->course?->instructor?->name : $orderItem?->type_label;
                                                        $bylineEmail = $item->item_type === 'course' ? $item->course?->instructor?->email : __('Digital resource');
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $orderItem?->title }}</td>
                                                        <td>
                                                            {{ $bylineName }}
                                                            <br>
                                                            {{ $bylineEmail }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($order->isBundleOrder())
                                                                --
                                                            @else
                                                                {{ $item->price * $order->conversion_rate }}
                                                                {{ $order->payable_currency }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </table>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-lg-4"></div>
                                        <div class="col-lg-8 text-end">
                                            @if ($order->isBundleOrder() || $order->isSubscriptionOrder())
                                                @php
                                                    $subTotal = $order->payable_amount;
                                                    $subTotalWithCharge = $subTotal * $order->conversion_rate;
                                                    $gatewayCharge = 0;
                                                    if ($order->gateway_charge > 0) {
                                                        $gatewayCharge =
                                                            ($order->gateway_charge / $subTotalWithCharge) * 100;
                                                    }
                                                    $total = number_format(
                                                        $subTotalWithCharge + $order->gateway_charge,
                                                        2,
                                                    );
                                                @endphp
                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Subtotal') }}</div>
                                                    <div class="invoice-detail-value">
                                                        {{ number_format($subTotal * $order->conversion_rate, 2) }}
                                                        {{ $order->payable_currency }}
                                                    </div>

                                                </div>
                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Gateway Charge') }}
                                                        ({{ number_format($gatewayCharge) }}%)</div>
                                                    <div class="invoice-detail-value">
                                                        {{ number_format($order->gateway_charge, 2) }}
                                                        {{ $order->payable_currency }}
                                                    </div>
                                                </div>
                                                <hr class="mt-2 mb-2">
                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Total') }}</div>
                                                    <div class="invoice-detail-value invoice-detail-value-lg">
                                                        {{ $total }} {{ $order->payable_currency }}
                                                    </div>
                                                </div>
                                            @else
                                                @php
                                                    $subTotal = 0;
                                                    $discount = 0;
                                                    $gatewayCharge = 0;
                                                    foreach ($order->orderItems as $item) {
                                                        $subTotal += $item->price;
                                                    }
                                                    $subTotalWithConversion = $subTotal * $order->conversion_rate;

                                                    if ($order->coupon_discount_amount > 0) {
                                                        $discount = $order->coupon_discount_amount;
                                                    }

                                                    if ($order->gateway_charge > 0) {
                                                        $gatewayCharge =
                                                            ($order->gateway_charge /
                                                                ($subTotalWithConversion - $discount)) *
                                                            100;
                                                    }

                                                    $total = number_format(
                                                        $subTotalWithConversion - $discount + $order->gateway_charge,
                                                        2,
                                                    );
                                                @endphp
                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Subtotal') }}</div>
                                                    <div class="invoice-detail-value">
                                                        {{ number_format($subTotal * $order->conversion_rate, 2) }}
                                                        {{ $order->payable_currency }}
                                                    </div>

                                                </div>

                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Discount') }}</div>
                                                    <div class="invoice-detail-value">
                                                        {{ number_format($discount, 2) }}
                                                        {{ $order->payable_currency }}
                                                    </div>
                                                </div>
                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Gateway Charge') }}
                                                        ({{ number_format($gatewayCharge) }}%)</div>
                                                    <div class="invoice-detail-value">
                                                        {{ number_format($order->gateway_charge, 2) }}
                                                        {{ $order->payable_currency }}
                                                    </div>
                                                </div>
                                                <hr class="mt-2 mb-2">
                                                <div class="invoice-detail-item">
                                                    <div class="invoice-detail-name">{{ __('Total') }}</div>
                                                    <div class="invoice-detail-value invoice-detail-value-lg">
                                                        {{ $total }} {{ $order->payable_currency }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-md-right">
                            @if ($canRetryPayment)
                                <a href="{{ route('payment', ['invoice_id' => $order->invoice_id]) }}"
                                    class="btn btn-primary btn-icon icon-left">
                                    <i class="fas fa-credit-card"></i> {{ __('Pay Now') }}
                                </a>
                            @endif

                            <a target="_blank" href="{{ route('student.order.print-invoice', $order->id) }}"
                                class="btn btn-warning btn-icon icon-left print-btn"><i class="fas fa-print"></i>
                                {{ __('Print') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
