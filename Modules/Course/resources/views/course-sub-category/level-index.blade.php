@extends('admin.master_layout')

@section('title')
    <title>{{ $title }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ $title }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active">
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ __('Manage Categories') }}</div>
                    <div class="breadcrumb-item">{{ $level === 'grand' ? __('Grand Child Categories') : $title }}</div>
                </div>
            </div>

            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ $level === 'sub' ? route('admin.course-sub-categories.index') : route('admin.course-grand-categories.index') }}"
                                    method="GET" onchange="$(this).trigger('submit')" class="form_padding">
                                    <div class="row">
                                        <div class="col-md-3 form-group">
                                            <input type="text" name="keyword" value="{{ request('keyword') }}"
                                                class="form-control" placeholder="{{ __('Search') }}">
                                        </div>

                                        <div class="col-md-3 form-group">
                                            <select name="status" class="form-control">
                                                <option value="">{{ __('Select Status') }}</option>
                                                <option value="1" @selected(request('status') == '1')>{{ __('Active') }}</option>
                                                <option value="0" @selected(request('status') == '0')>{{ __('In-Active') }}</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 form-group">
                                            <select name="order_by" class="form-control">
                                                <option value="">{{ __('Order By') }}</option>
                                                <option value="1" @selected(request('order_by') == '1')>{{ __('ASC') }}</option>
                                                <option value="0" @selected(request('order_by') == '0')>{{ __('DESC') }}</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 form-group">
                                            <select name="par-page" class="form-control">
                                                <option value="">{{ __('Per Page') }}</option>
                                                <option value="10" @selected(request('par-page') == '10')>{{ __('10') }}</option>
                                                <option value="50" @selected(request('par-page') == '50')>{{ __('50') }}</option>
                                                <option value="100" @selected(request('par-page') == '100')>{{ __('100') }}</option>
                                                <option value="all" @selected(request('par-page') == 'all')>{{ __('All') }}</option>
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
                                <h4>{{ $level === 'grand' ? __('Grand Child Categories') : $title }}</h4>
                                @if ($level === 'sub')
                                    <a href="{{ route('admin.course-sub-categories.create') }}" class="btn btn-primary">
                                        <i class="fa fa-plus"></i>{{ __('Add New') }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.course-grand-categories.create') }}" class="btn btn-primary">
                                        <i class="fa fa-plus"></i>{{ __('Add New') }}
                                    </a>
                                @endif
                            </div>

                            <div class="card-body">
                                <div class="table-responsive max-h-400">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('SN') }}</th>
                                                @if ($level === 'grand')
                                                    <th>{{ __('Parent Category') }}</th>
                                                    <th>{{ __('Parent Sub Category') }}</th>
                                                    <th>{{ __('Grand Child Category') }}</th>
                                                @else
                                                    <th>{{ __('Category') }}</th>
                                                    <th>{{ __('Sub Category') }}</th>
                                                @endif
                                                <th>{{ __('Slug') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th class="text-center">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($categories as $category)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    @if ($level === 'grand')
                                                        <td>{{ $category->parentCategory?->parentCategory?->translation?->name ?? '-' }}</td>
                                                        <td>{{ $category->parentCategory?->translation?->name ?? '-' }}</td>
                                                        <td>{{ $category->translation?->name }}</td>
                                                    @else
                                                        <td>{{ $category->parentCategory?->translation?->name ?? '-' }}</td>
                                                        <td>{{ $category->translation?->name }}</td>
                                                    @endif
                                                    <td>{{ $category->slug }}</td>
                                                    <td>
                                                        <input onchange="changeStatus({{ $category->id }})"
                                                            type="checkbox" {{ $category->status ? 'checked' : '' }}
                                                            data-toggle="toggle" data-on="{{ __('Active') }}"
                                                            data-off="{{ __('Inactive') }}" data-onstyle="success"
                                                            data-offstyle="danger">
                                                    </td>
                                                    <td class="text-center min-200">
                                                        <a href="{{ route('admin.course-sub-category.edit', [
                                                            'parent_id' => $category->parent_id,
                                                            'sub_category_id' => $category->id,
                                                            'code' => getSessionLanguage(),
                                                        ]) }}"
                                                            class="m-1 text-white btn btn-sm btn-warning" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>

                                                        @if ($level === 'sub')
                                                            <a href="{{ route('admin.course-sub-category.index', $category->id) }}"
                                                                class="m-1 text-white btn btn-sm btn-primary"
                                                                title="Grand categories">
                                                                <i class="fas fa-list"></i>
                                                            </a>
                                                        @endif

                                                        <a href="javascript:;" data-toggle="modal" data-target="#deleteModal"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="deleteData({{ $category->parent_id }}, {{ $category->id }})">
                                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ $level === 'grand' ? 7 : 6 }}"
                                                        class="text-center text-muted py-4">
                                                        {{ __('No data found!') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="float-right">
                                    @if (method_exists($categories, 'links'))
                                        {{ $categories->links() }}
                                    @endif
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
        function deleteData(parent_id, sub_category_id) {
            $("#deleteForm").attr("action", "{{ url('/admin/course-sub-category/') }}" + "/" + parent_id + "/" +
                sub_category_id)
        }

        function changeStatus(id) {
            var isDemo = "{{ env('PROJECT_MODE') ?? 1 }}"
            if (isDemo == 0) {
                toastr.error("{{ __('This Is Demo Version. You Can Not Change Anything') }}");
                return;
            }
            $.ajax({
                type: "put",
                data: {
                    _token: '{{ csrf_token() }}',
                },
                url: "{{ url('/admin/course-sub-category/status-update') }}" + "/" + id,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.warning(response.message);
                    }
                },
                error: function(xhr, status, err) {
                    console.log(err);
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function (key, value) {
                        toastr.error(value);
                    })
                }
            })
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
