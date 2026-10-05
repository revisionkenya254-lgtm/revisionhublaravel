@extends('admin.master_layout')
@section('title')
    <title>{{ __($meta['title']) }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1 class="text-primary">{{ __($meta['title']) }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></div>
                    <div class="breadcrumb-item">{{ __($meta['title']) }}</div>
                </div>
            </div>
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route($meta['route'] . '.reviews.index') }}" method="GET" onchange="$(this).trigger('submit')" class="form_padding">
                                    <div class="row">
                                        <div class="col-md-4 form-group">
                                            <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="{{ __('Search') }}">
                                        </div>
                                        <div class="col-md-3 form-group">
                                            <select name="status" class="form-control">
                                                <option value="">{{ __('Status') }}</option>
                                                <option value="0" @selected(request('status') == '0')>{{ __('Pending') }}</option>
                                                <option value="1" @selected(request('status') == '1')>{{ __('Approved') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 form-group">
                                            <select name="order_by" class="form-control">
                                                <option value="">{{ __('Order By') }}</option>
                                                <option value="1" @selected(request('order_by') == '1')>{{ __('ASC') }}</option>
                                                <option value="0" @selected(request('order_by') == '0')>{{ __('DESC') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 form-group">
                                            <select name="par-page" class="form-control">
                                                <option value="">{{ __('Per Page') }}</option>
                                                <option value="10" @selected(request('par-page') == '10')>{{ __('10') }}</option>
                                                <option value="50" @selected(request('par-page') == '50')>{{ __('50') }}</option>
                                                <option value="100" @selected(request('par-page') == '100')>{{ __('100') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h4>{{ __('Review List') }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive max-h-400">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('SN') }}</th>
                                                <th>{{ __($meta['singular']) }}</th>
                                                <th>{{ __('By') }}</th>
                                                <th>{{ __('Rating') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th class="text-center">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($reviews as $review)
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>{{ $review->product?->title }}</td>
                                                    <td>{{ $review->user?->name }}</td>
                                                    <td>
                                                        @for ($i = 0; $i < $review->rating; $i++)
                                                            <i class="fa fa-star text-warning"></i>
                                                        @endfor
                                                    </td>
                                                    <td>
                                                        @if ($review->status == 0)
                                                            <span class="badge badge-warning">{{ __('Pending') }}</span>
                                                        @else
                                                            <span class="badge badge-success">{{ __('Approved') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route($meta['route'] . '.reviews.show', $review->id) }}" class="m-1 text-white btn btn-sm btn-primary" title="{{ __('Show') }}">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $review->id }})">
                                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <x-empty-table :name="__('Review')" create="no" :message="__('No data found!')" colspan="6"></x-empty-table>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $reviews->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <x-admin.delete-modal />
@endsection

@push('js')
    <script>
        function deleteData(id) {
            $("#deleteForm").attr("action", "{{ $meta['delete_url'] }}" + "/" + id)
        }
    </script>
@endpush

@push('css')
    <style>
        .max-h-400 {
            min-height: 400px;
        }
    </style>
@endpush
