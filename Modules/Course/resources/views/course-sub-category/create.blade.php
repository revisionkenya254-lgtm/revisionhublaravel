@extends('admin.master_layout')
@section('title')
    <title>{{ $title ?? __('Create Sub Category') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ $title ?? __('Create Sub Category') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item active"><a
                            href="{{ ($categoryLevel ?? 'sub') === 'grand' ? route('admin.course-grand-categories.index') : route('admin.course-sub-categories.index') }}">
                            {{ ($categoryLevel ?? 'sub') === 'grand' ? __('Grand Child Category List') : __('Sub Category List') }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ ($categoryLevel ?? 'sub') === 'grand' ? __('Add Grand Child Category') : __('Add Sub Category') }}</div>
                </div>
            </div>
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h4>{{ ($categoryLevel ?? 'sub') === 'grand' ? __('Add Grand Child Category') : __('Add Sub Category') }}</h4>
                                <div>
                                    <a href="{{ request()->parent_id ? route('admin.course-sub-category.index', request()->parent_id) : (($categoryLevel ?? 'sub') === 'grand' ? route('admin.course-grand-categories.index') : route('admin.course-sub-categories.index')) }}" class="btn btn-primary"><i
                                            class="fa fa-arrow-left"></i>{{ __('Back') }}</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ request()->parent_id ? route('admin.course-sub-category.store', request()->parent_id) : route($storeRouteName ?? 'admin.course-sub-categories.store') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-8 offset-md-2">
                                            <div class="form-group">
                                                <label for="parent_id">{{ ($categoryLevel ?? 'sub') === 'grand' ? __('Parent Sub Category') : __('Parent Category') }}<span
                                                        class="text-danger">*</span></label>
                                                <select class="form-control select2" name="parent_id" id="parent_id">
                                                    <option value="">{{ __('Select') }}</option>
                                                    @foreach ($parentCategories as $parentCategory)
                                                        <option value="{{ $parentCategory->id }}"
                                                            @selected(old('parent_id', $selectedParentId) == $parentCategory->id)>
                                                            {{ $parentCategory->translation?->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('parent_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-8 offset-md-2">
                                            <div class="form-group">
                                                <label for="name">{{ __('Name') }}<span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="name" name="name"
                                                    value="{{ old('name') }}" placeholder="{{ __('Enter name') }}"
                                                    class="form-control">
                                                @error('name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-8 offset-md-2">
                                            <div class="form-group">
                                                <label for="slug">{{ __('Slug') }}<span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="slug" name="slug"
                                                    value="{{ old('slug') }}" placeholder="{{ __('Enter Slug') }}"
                                                    class="form-control">
                                                @error('slug')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-8 offset-md-2">
                                            <div class="form-group">
                                                <label for="status">{{ __('Status') }}<span
                                                        class="text-danger">*</span></label>
                                                <select class="form-control" name="status">
                                                    <option value="1">{{ __('Active') }}</option>
                                                    <option value="0">{{ __('Inactive') }}</option>
                                                </select>
                                                @error('status')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="text-center offset-md-2 col-md-8">
                                            <x-admin.save-button :text="__('Save')">
                                            </x-admin.save-button>
                                        </div>

                                        <div class="col-md-10 offset-md-1 mt-4">
                                            <h6>{{ ($categoryLevel ?? 'sub') === 'grand' ? __('Existing Grand Child Categories') : __('Existing Sub Categories') }}</h6>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            @if (($categoryLevel ?? 'sub') === 'grand')
                                                                <th>{{ __('Parent Category') }}</th>
                                                                <th>{{ __('Parent Sub Category') }}</th>
                                                                <th>{{ __('Grand Child Category') }}</th>
                                                            @else
                                                                <th>{{ __('Parent Category') }}</th>
                                                                <th>{{ __('Sub Category') }}</th>
                                                            @endif
                                                            <th>{{ __('Slug') }}</th>
                                                            <th>{{ __('Status') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($existingCategories as $category)
                                                            <tr>
                                                                @if (($categoryLevel ?? 'sub') === 'grand')
                                                                    <td>{{ $category->parentCategory?->parentCategory?->translation?->name ?? '-' }}</td>
                                                                    <td>{{ $category->parentCategory?->translation?->name ?? '-' }}</td>
                                                                    <td>{{ $category->translation?->name }}</td>
                                                                @else
                                                                    <td>{{ $category->parentCategory?->translation?->name ?? '-' }}</td>
                                                                    <td>{{ $category->translation?->name }}</td>
                                                                @endif
                                                                <td>{{ $category->slug }}</td>
                                                                <td>
                                                                    @if ($category->status)
                                                                        <span class="badge badge-success">{{ __('Active') }}</span>
                                                                    @else
                                                                        <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="{{ ($categoryLevel ?? 'sub') === 'grand' ? 5 : 4 }}"
                                                                    class="text-center text-muted">
                                                                    {{ ($categoryLevel ?? 'sub') === 'grand' ? __('No grand child categories found.') : __('No sub categories found.') }}
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('js')
    <script src="{{ asset('backend/js/jquery.uploadPreview.min.js') }}"></script>
    <script>
        'use strict';
        $(function() {
            const $name = $("#name"),
                $slug = $("#slug");

            $name.on("keyup", function(e) {
                $slug.val(convertToSlug($name.val()));
            });

            function convertToSlug(text) {
                return text
                    .toLowerCase()
                    .replace(/[^a-z\s-]/g, "") // Remove all non-word characters (except -)
                    .replace(/\s+/g, "-") // Replace spaces with -
                    .replace(/-+/g, "-"); // Replace multiple - with single -
            }

            $.uploadPreview({
                input_field: "#image-upload",
                preview_box: "#image-preview",
                label_field: "#image-label",
                label_default: "{{ __('Choose Icon') }}",
                label_selected: "{{ __('Change Icon') }}",
                no_label: false,
                success_callback: null
            });
        });
    </script>
@endpush
