@extends('admin.master_layout')
@section('title')
    <title>{{ $title }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ $title }}</h1>
            </div>

            <div class="section-body">
                <div class="row">
                    {{-- Search filter --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('admin.orders') }}" method="GET" onchange="$(this).trigger('submit')"
                                    class="form_padding">
                                    <div class="row">
                                        <div class="col-md-4 form-group">
                                            <input type="text" name="keyword" value="{{ request()->get('keyword') }}"
                                                class="form-control" placeholder="{{ __('Search') }}">
                                        </div>

                                        <div class="col-md-2 form-group">
                                            <select name="order_status" class="form-control">
                                                <option value="">{{ __('Order status') }}</option>
                                                <option value="pending"
                                                    {{ request('order_status') == 'pending' ? 'selected' : '' }}>
                                                    {{ __('Pending') }}
                                                </option>
                                                <option value="processing"
                                                    {{ request('order_status') == 'processing' ? 'selected' : '' }}>
                                                    {{ __('Processing') }}
                                                </option>
                                                <option value="completed"
                                                    {{ request('order_status') == 'completed' ? 'selected' : '' }}>
                                                    {{ __('Completed') }}
                                                </option>
                                                <option value="declined"
                                                    {{ request('order_status') == 'declined' ? 'selected' : '' }}>
                                                    {{ __('Declined') }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 form-group">
                                            <select name="payment_status" id="status" class="form-control">
                                                <option value="">{{ __('Payment Status') }}</option>
                                                <option value="pending"
                                                    {{ request('payment_status') == 'pending' ? 'selected' : '' }}>
                                                    {{ __('Pending') }}
                                                </option>
                                                <option value="paid"
                                                    {{ request('payment_status') == 'paid' ? 'selected' : '' }}>
                                                    {{ __('Paid') }}
                                                </option>
                                                <option value="refunded"
                                                    {{ request('payment_status') == 'refunded' ? 'selected' : '' }}>
                                                    {{ __('Refunded') }}
                                                </option>
                                                <option value="cancelled"
                                                    {{ request('payment_status') == 'cancelled' ? 'selected' : '' }}>
                                                    {{ __('Cancelled') }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 form-group">
                                            <select name="order_by" id="order_by" class="form-control">
                                                <option value="">{{ __('Order By') }}</option>
                                                <option value="1" {{ request('order_by') == '1' ? 'selected' : '' }}>
                                                    {{ __('ASC') }}
                                                </option>
                                                <option value="0" {{ request('order_by') == '0' ? 'selected' : '' }}>
                                                    {{ __('DESC') }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 form-group">
                                            <select name="par-page" id="par-page" class="form-control">
                                                <option value="">{{ __('Per Page') }}</option>
                                                <option value="10" {{ '10' == request('par-page') ? 'selected' : '' }}>
                                                    {{ __('10') }}
                                                </option>
                                                <option value="50" {{ '50' == request('par-page') ? 'selected' : '' }}>
                                                    {{ __('50') }}
                                                </option>
                                                <option value="100"
                                                    {{ '100' == request('par-page') ? 'selected' : '' }}>
                                                    {{ __('100') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <tr>
                                            <th>{{ __('SN') }}</th>
                                            <th>{{ __('User') }}</th>
                                            <th>{{ __('Order Id') }}</th>
                                            <th>{{ __('Paid Amount') }}</th>
                                            <th>{{ __('Gateway') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Payment') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>

                                        @forelse ($orders as $index => $order)
                                            <tr>
                                                <td>{{ ++$index }}</td>
                                                <td><a
                                                        href="{{ route('admin.customer-show', $order->buyer_id) }}">{{ $order?->user?->name }}</a>
                                                </td>
                                                <td>#{{ $order->invoice_id }}</td>
                                                <td>{{ $order->paid_amount }} {{ $order->payable_currency }}</td>
                                                <td class="text-capitalize">
                                                    {{ str_replace('_',' ',$order->payment_method) }}
                                                </td>
                                                <td>
                                                    @if ($order->status == 'completed')
                                                        <div class="badge badge-success">{{ __('Completed') }}</div>
                                                    @elseif($order->status == 'processing')
                                                        <div class="badge badge-warning">{{ __('Processing') }}</div>
                                                    @elseif($order->status == 'declined')
                                                        <div class="badge badge-danger">{{ __('Declined') }}</div>
                                                    @else
                                                        <div class="badge badge-warning">{{ __('Pending') }}</div>
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($order->payment_status == 'paid')
                                                        <div class="badge badge-success">{{ __('Paid') }}</div>
                                                    @elseif ($order->payment_status == 'cancelled')
                                                        <div class="badge badge-danger">{{ __('Cancelled') }}</div>
                                                    @elseif($order->payment_status == 'refunded')
                                                        <div class="badge badge-dark">{{ __('Refunded') }}</div>
                                                    @else
                                                        <div class="badge badge-danger">{{ __('Pending') }}</div>
                                                    @endif
                                                </td>

                                                <td>
                                                    <a href="{{ route('admin.order', $order->id) }}"
                                                        class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>

                                                    @php
                                                        $canRevokeAccess = $order->isSubscriptionOrder()
                                                            || $order->isGiftOrder()
                                                            || $order->orderItems->contains(fn ($item) => $item->course_id && $item->item_type !== 'product');
                                                    @endphp

                                                    @if ($canRevokeAccess && !in_array($order->payment_status, ['cancelled', 'refunded'], true))
                                                        <a href="javascript:;" data-toggle="modal"
                                                            data-target="#revokeAccessModal"
                                                            class="btn btn-warning btn-sm"
                                                            onclick="revokeAccessData({{ $order->id }})">
                                                            <i class="fa fa-user-slash" aria-hidden="true"></i>
                                                        </a>
                                                    @endif

                                                    <a href="javascript:;" data-toggle="modal" data-target="#deleteModal"
                                                        class="btn btn-danger btn-sm"
                                                        onclick="deleteData({{ $order->id }})"><i class="fa fa-trash"
                                                            aria-hidden="true"></i></a>
                                                </td>
                                            </tr>
                                        @empty
                                            <x-empty-table :name="__('Orders')" route="" create="no" :message="__('No data found!')"
                                                colspan="9"></x-empty-table>
                                        @endforelse
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end">
                                    {{ $orders->links() }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>


    <x-admin.delete-modal />

    <div class="modal fade" tabindex="-1" role="dialog" id="revokeAccessModal">
        <div class="modal-dialog" role="document">
            <form action="" method="POST" id="revokeAccessForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Revoke Access') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger">{{ __('This will remove the student access, cancel the order, and keep the order record for audit history. Continue?') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Yes, Revoke Access') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        'use strict'

        function deleteData(id) {
            $("#deleteForm").attr("action", "{{ url('admin/order-delete/') }}" + "/" + id)
        }

        function revokeAccessData(id) {
            $("#revokeAccessForm").attr("action", "{{ url('admin/revoke-order-access/') }}" + "/" + id)
        }
    </script>
@endsection
