@extends('frontend.student-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title d-flex flex-wrap justify-content-between">
            <h4 class="title">{{ __('Refund History') }}</h4>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="dashboard__review-table table-responsive">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>{{ __('No') }}</th>
                                <th>{{ __('Order Id') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($refund_requests as $key => $refundRequest)
                                <tr>
                                    <td>
                                        <p>{{ $loop->iteration }}</p>
                                    </td>
                                    <td>
                                        <p><a href="{{ route('student.order.show', $refundRequest?->invoice_id) }}">{{ $refundRequest?->invoice_id }}</a></p>
                                    </td>
                                    <td>
                                        <p>{{ $refundRequest->method }}</p>
                                    </td>
                                    <td>
                                        @if ($refundRequest->status == 'success')
                                            <span class="badge bg-success">{{ __('Approved') }}</span>
                                        @elseif($refundRequest->status == 'rejected')
                                            <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                        @elseif($refundRequest->status == 'pending')
                                            <span class="badge bg-warning">{{ __('Pending') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <p>{{ formatDate($refundRequest->created_at) }}</p>
                                    </td>

                                    <td>
                                        @if ($refundRequest->status == 'pending')
                                            <p><a href="{{ route('refund-request.destroy', $refundRequest->id) }}"
                                                    class="text-danger delete-item"><i class="fas fa-trash-alt"></i></a></p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <td class="text-center" colspan="100">{{ __('No Data') }}</td>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
