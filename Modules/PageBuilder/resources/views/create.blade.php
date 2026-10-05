@extends('admin.master_layout')
@section('title')
    <title>{{ __('Create Page') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Create Page') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item active"><a
                            href="{{ route('admin.page-builder.index') }}">{{ __('Page Builder') }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ __('Create Page') }}</div>
                </div>
            </div>
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h4>{{ __('Create Page') }}</h4>
                                <div>
                                    <a href="{{ route('admin.page-builder.index') }}" class="btn btn-primary"><i
                                            class="fa fa-arrow-left"></i> {{ __('Back') }}</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.page-builder.store') }}" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <label for="name">{{ __('name') }}<span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                                            placeholder="" class="form-control">
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="question">{{ __('Slug') }}<span class="text-danger">*</span></label>
                                        <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                                            placeholder="" class="form-control">
                                        @error('slug')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('SEO Title') }}</label>
                                        <input type="text" class="form-control" name="seo_title"
                                            value="{{ old('seo_title') }}">
                                        @error('seo_title')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('SEO Description') }}</label>
                                        <textarea name="seo_description" id="" cols="30" rows="10" class="form-control text-area-5">{{ old('seo_description') }}</textarea>
                                        @error('seo_description')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="d-flex align-items-center">
                                            <input type="hidden" value="0" name="fixed_header" class="custom-switch-input">
                                            <input type="checkbox" value="1" name="fixed_header"
                                                class="custom-switch-input" @checked(old('fixed_header',0))>
                                            <span class="custom-switch-indicator"></span>
                                            <span
                                                class="custom-switch-description">{{ __('Header Fixed') }}</span>
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="d-flex align-items-center">
                                            <input type="hidden" value="0" name="status" class="custom-switch-input">
                                            <input type="checkbox" value="1" name="status"
                                                class="custom-switch-input" @checked(old('status'))>
                                            <span class="custom-switch-indicator"></span>
                                            <span
                                                class="custom-switch-description">{{ __('Status') }}</span>
                                        </label>
                                    </div>
                                    <x-admin.save-button :text="__('Save')" />
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
    <script>
        (function($) {
            "use strict";
            $(document).ready(function() {
                $("#name").on("keyup", function(e) {
                    $("#slug").val(convertToSlug($(this).val()));
                })
            });
        })(jQuery);

        function convertToSlug(Text) {
            return Text
                .toLowerCase()
                .replace(/[^\w ]+/g, '')
                .replace(/ +/g, '-');
        }
    </script>
@endpush
