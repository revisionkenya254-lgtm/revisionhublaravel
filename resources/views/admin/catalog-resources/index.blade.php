@extends('admin.master_layout')

@section('title')
    <title>{{ __($meta['title']) }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __($meta['title']) }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active">
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ __($meta['title']) }}</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route($meta['route'] . '.index') }}" method="GET"
                                    onchange="$(this).trigger('submit')" class="form_padding">
                                    <div class="row">
                                        <div class="col-md-3 form-group">
                                            <input type="text" name="keyword" value="{{ request('keyword') }}"
                                                class="form-control" placeholder="{{ __('Search') }}">
                                        </div>

                                        <div class="col-md-3 form-group">
                                            <input type="text" autocomplete="off" name="date" value="{{ request('date') }}"
                                                class="form-control datepicker" placeholder="{{ __('Date') }}">
                                        </div>

                                        <div class="col-md-3 form-group">
                                            <select class="select2 form-control" name="category">
                                                <option value="">{{ __('Category') }}</option>
                                                @foreach ($categories as $category)
                                                    @if ($category->subCategories->count())
                                                        <optgroup label="{{ $category->translation?->name }}">
                                                            @foreach ($category->subCategories as $subCategory)
                                                                <option value="{{ $subCategory->id }}" @selected(request('category') == $subCategory->id)>
                                                                    {{ $subCategory->translation?->name }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @else
                                                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                                                            {{ $category->translation?->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3 form-group">
                                            <select name="approve_status" class="form-control">
                                                <option value="">{{ __('Approval Status') }}</option>
                                                <option value="pending" @selected(request('approve_status') == 'pending')>{{ __('Pending') }}</option>
                                                <option value="approved" @selected(request('approve_status') == 'approved')>{{ __('Approved') }}</option>
                                                <option value="rejected" @selected(request('approve_status') == 'rejected')>{{ __('Rejected') }}</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3 form-group">
                                            <select name="status" class="form-control">
                                                <option value="">{{ __('Status') }}</option>
                                                <option value="active" @selected(request('status') == 'active')>{{ __('Published') }}</option>
                                                <option value="inactive" @selected(request('status') == 'inactive')>{{ __('Unpublished') }}</option>
                                                <option value="is_draft" @selected(request('status') == 'is_draft')>{{ __('Drafted') }}</option>
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
                                            <select name="par_page" class="form-control">
                                                <option value="">{{ __('Per Page') }}</option>
                                                <option value="10" @selected(request('par_page') == '10')>{{ __('10') }}</option>
                                                <option value="20" @selected(request('par_page') == '20')>{{ __('20') }}</option>
                                                <option value="50" @selected(request('par_page') == '50')>{{ __('50') }}</option>
                                                <option value="100" @selected(request('par_page') == '100')>{{ __('100') }}</option>
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
                                <h4>{{ __($meta['title'] . ' List') }}</h4>
                                <a href="{{ route($meta['route'] . '.create') }}" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> {{ __('Add New') }}
                                </a>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive max-h-400">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('SN') }}</th>
                                                <th>{{ __('Title') }}</th>
                                                <th>{{ __('Category') }}</th>
                                                <th>{{ __('Price') }}</th>
                                                <th>{{ __('Created Date') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th class="text-center">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($resources as $resource)
                                                @php
                                                    $approvalMeta = [
                                                        'pending' => ['label' => __('Pending'), 'class' => 'badge-warning'],
                                                        'approved' => ['label' => __('Published'), 'class' => 'badge-success'],
                                                        'rejected' => ['label' => __('Rejected'), 'class' => 'badge-danger'],
                                                    ][$resource->is_approved] ?? ['label' => ucfirst((string) $resource->is_approved), 'class' => 'badge-secondary'];
                                                    $resourceMetadata = $resource->metadata ?? [];
                                                    $resourceSummary = collect([
                                                        $resourceMetadata['education_level'] ?? null,
                                                        $resourceMetadata['class_grade'] ?? null,
                                                        $resourceMetadata['exam_category'] ?? null,
                                                        $resourceMetadata['subject'] ?? null,
                                                        $resourceMetadata['year'] ?? null,
                                                    ])->filter()->implode(' • ');
                                                @endphp
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <span>{{ truncate($resource->title) }}</span>
                                                        @if ($resource->file_path)
                                                            <br><small class="text-muted">{{ $resource->file_type ?: __('File attached') }}</small>
                                                        @endif
                                                        @if ($resourceSummary)
                                                            <br><small class="text-muted">{{ $resourceSummary }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $resource->category?->translation?->name ?? '-' }}</td>
                                                    <td>
                                                        @if ($resource->price == 0)
                                                            {{ __('Free') }}
                                                        @elseif ($resource->discount > 0)
                                                            {{ currency($resource->discount) }}
                                                        @else
                                                            {{ currency($resource->price) }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <small>{{ formatDate($resource->created_at) }}</small><br>
                                                        <small>{{ formatDate($resource->created_at, 'H:i') }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $approvalMeta['class'] }}">{{ $approvalMeta['label'] }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="dropdown">
                                                            <button class="btn btn-primary dropdown-toggle" type="button"
                                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fa fa-ellipsis-v"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a href="{{ route($meta['route'] . '.edit', array_merge(['resource' => $resource->id], request()->query())) }}"
                                                                    class="dropdown-item">{{ __('Edit') }}</a>
                                                                <form action="{{ route($meta['route'] . '.destroy', $resource->id) }}"
                                                                    method="POST" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        {{ __('Delete') }}
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        {{ __('No data found!') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $resources->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('js')
    <script src="{{ asset('global/js/jquery-ui.min.js') }}"></script>
@endpush
