@extends('frontend.student-dashboard.layouts.master')

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
                                <th>{{ __('Invoice') }}</th>
                                <th>{{ __('Paid') }}</th>
                                <th>{{ __('Gateway') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Payment') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>

                            @forelse ($orders as $index => $order)
                                <tr>
                                    <td>{{ ++$index }}</td>
                                    <td>#{{ $order->invoice_id }}</td>
                                    <td>{{ $order->paid_amount }} {{ $order->payable_currency }}</td>
                                    <td class="text-capitalize">
                                        {{ str_replace('_',' ',$order->payment_method) }}
                                    </td>
                                    <td>
                                        @if ($order->status == 'completed')
                                            <div class="badge bg-success">{{ __('Completed') }}</div>
                                        @elseif($order->status == 'processing')
                                            <div class="badge bg-warning">{{ __('Processing') }}</div>
                                        @elseif($order->status == 'declined')
                                            <div class="badge bg-danger">{{ __('Declined') }}</div>
                                        @else
                                            <div class="badge bg-warning">{{ __('Pending') }}</div>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($order->payment_status == 'paid')
                                            <div class="badge bg-success">{{ __('Paid') }}</div>
                                        @elseif ($order->payment_status == 'cancelled')
                                            <div class="badge bg-danger">{{ __('Cancelled') }}</div>
                                        @elseif ($order->payment_status == 'refunded')
                                            <div class="badge bg-dark">{{ __('Refunded') }}</div>
                                        @else
                                            <div class="badge bg-danger">{{ __('Pending') }}</div>
                                        @endif
                                    </td>

                                    <td>
                                        <a href="{{ route('student.order.show', $order->id) }}" class=""><i
                                                class="fa fa-eye"></i></a>
                                        @if ($setting?->is_refundable  == 'active' && $order->payment_status == 'paid' && $order->instructorEarningsHolds->where('status', 'pending')->isNotEmpty())
                                            <a href="{{ route('refund-request.create', $order->invoice_id) }}"
                                                class="bg-dark" title="Refund"><i class="fas fa-undo"></i></a>
                                        @endif
                                        @if ($order->payment_method !== 'Free' && !in_array($order->payment_status, ['paid', 'refunded']) && app(\Modules\BasicPayment\app\Services\PaymentMethodService::class)->isActive($order->payment_method))
                                            <a target="_blank"
                                                href="{{ route('payment', ['invoice_id' => $order->invoice_id]) }}"
                                                class="bg-info"><i class="fa fa-credit-card"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">{{ __('No orders found!') }}</td>
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
