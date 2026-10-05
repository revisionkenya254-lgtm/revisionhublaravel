@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title">
            <h4 class="title">{{ __('Order History') }}</h4>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="dashboard__review-table table-responsive">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>{{ __('No') }}</th>
                                <th>{{ __('Item') }}</th>
                                <th class="text-center">{{ __('Type') }}</th>
                                <th class="text-center">{{ __('Buyer') }}</th>
                                <th class="text-center">{{ __('Price') }}</th>
                                <th class="text-center">{{ __('Admin Commission') }}</th>
                                <th class="text-center">{{ __('Total Earnings') }}</th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse ($orders as $index => $order)
                                <tr>
                                    <td>{{ ++$index }}</td>
                                    <td>
                                        @if($order->item_type == 'course' && $order->course)
                                            {{ $order->course->title }}
                                        @elseif($order->item_type == 'product' && $order->product)
                                            {{ $order->product->title }}
                                        @else
                                            {{ __('N/A') }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $order->item_type == 'course' ? 'bg-primary' : 'bg-success' }}">
                                            {{ $order->item_type == 'course' ? __('Course') : ucfirst(str_replace('_', ' ', $order->product->type ?? '')) }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $order->order->user->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ defaultCurrency($order->price) }}</td>
                                    @php
                                        $commissionAmount = $order->price * ($order->commission_rate / 100);
                                        $amountAfterCommission = $order->price - $commissionAmount;
                                    @endphp
                                    <td class="text-center">{{ defaultCurrency($commissionAmount) }}</td>
                                    <td class="text-center">{{ defaultCurrency($amountAfterCommission) }}</td>
                                </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('No orders found!') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $orders->links() }}
            </div>
        </div>
    </div>
@endsection