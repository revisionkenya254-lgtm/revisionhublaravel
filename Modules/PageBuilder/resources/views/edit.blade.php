@extends('admin.master_layout')
@section('title')
    <title>{{ __('Edit Page') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Edit Page') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item active"><a
                            href="{{ route('admin.page-builder.index') }}">{{ __('Page Builder') }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ __('Edit Page') }} ({{ request('code') }})</div>
                </div>
            </div>
            <div class="section-body row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="service_card">{{ __('Available Translations') }}</h5>
                            <hr>
                            @if ($code !== $languages->first()->code)
                                <button onclick="translateAll()" class="btn btn-primary"
                                    id="translate-btn">{{ __('Translate') }}</button>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="lang_list_top">
                                <ul class="lang_list">
                                    @foreach ($languages as $language)
                                        <li><a id="{{ request('code') == $language->code ? 'selected-language' : '' }}"
                                                href="{{ route('admin.page-builder.edit', ['page_builder' => $page->id, 'code' => $language->code]) }}"><i
                                                    class="fas {{ request('code') == $language->code ? 'fa-eye' : 'fa-edit' }}"></i>
                                                {{ $language->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="mt-2 alert alert-danger" role="alert">
                                @php
                                    $current_language = $languages->where('code', request()->get('code'))->first();
                                @endphp
                                <p>{{ __('Your editing mode') }} :
                                    <b>{{ $current_language?->name }}</b>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-body">
                <div class="mt-4 row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h4>{{ __('Edit Page') }}</h4>
                                <div>
                                    <a href="{{ route('admin.page-builder.index') }}" class="btn btn-primary"><i
                                            class="fa fa-arrow-left"></i> {{ __('Back') }}</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form
                                    action="{{ route('admin.page-builder.update', ['page_builder' => $page->id, 'code' => $code]) }}"
                                    method="post">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="name">{{ __('name') }}<span class="text-danger">*</span></label>
                                        <input type="text" data-translate="true" id="name" name="name"
                                            value="{{ $page->getTranslation($code)->name }}" placeholder=""
                                            class="form-control">
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group {{ in_array($page->slug, ['privacy-policy', 'terms-and-conditions']) || $code != $languages->first()->code ? 'd-none' : '' }}">
                                        <label for="question">{{ __('Slug') }}<span class="text-danger">*</span></label>
                                        <input type="text" id="slug" name="slug" value="{{ $page->slug }}"
                                            placeholder="" class="form-control">
                                        @error('slug')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('SEO Title') }}</label>
                                        <input data-translate="true" type="text" class="form-control" name="seo_title"
                                            value="{{ $page->getTranslation($code)->seo_title }}">
                                    </div>

                                    <div class="form-group">
                                        <label>{{ __('SEO Description') }}</label>
                                        <textarea data-translate="true" name="seo_description" id="" cols="30" rows="10"
                                            class="form-control text-area-5">{{ $page->getTranslation($code)->seo_description }}</textarea>
                                    </div>
                                    <div class="form-group {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                        <label class="d-flex align-items-center">
                                            <input type="hidden" value="0" name="fixed_header" class="custom-switch-input">
                                            <input type="checkbox" value="1" name="fixed_header"
                                                class="custom-switch-input" @checked(old('fixed_header',$page?->fixed_header))>
                                            <span class="custom-switch-indicator"></span>
                                            <span
                                                class="custom-switch-description">{{ __('Header Fixed') }}</span>
                                        </label>
                                    </div>
                                    <div class="form-group {{ $code == $languages->first()->code ? '' : 'd-none' }}">
                                        <label class="d-flex align-items-center">
                                            <input type="hidden" value="0" name="status" class="custom-switch-input">
                                            <input type="checkbox" value="1" name="status"
                                                class="custom-switch-input" @checked(old('status',$page?->status))>
                                            <span class="custom-switch-indicator"></span>
                                            <span
                                                class="custom-switch-description">{{ __('Status') }}</span>
                                        </label>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <x-admin.update-button :text="__('Update')" />
                                        <a href="{{ route('admin.page-builder.editor', ['slug' => $page->slug, 'code' => $code]) }}"
                                            class="btn btn-info"><i class="fa fa-th-large"></i>
                                            {{ __('Edit With Builder') }}</a>
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
    <script>
        (function($) {
            "use strict";
            $(document).ready(function() {
                $("#title").on("keyup", function(e) {
                    $("#slug").val(convertToSlug($(this).val()));
                });
                $('#translate-btn').on('click', function() {
                    translateAllTo("{{ $code }}");
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
