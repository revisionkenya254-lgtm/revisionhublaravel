@extends('admin.master_layout')
@section('title')
    <title>{{ __('Payment Gateway') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <div class="section-header-back">
                    <a href="{{ route('admin.settings') }}" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
                </div>
                <h1>{{ __('Payment Gateway') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></div>
                    <div class="breadcrumb-item active"><a href="{{ route('admin.settings') }}">{{ __('Settings') }}</a></div>
                    <div class="breadcrumb-item">{{ __('Payment Gateway') }}</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card payment-gateway-sidebar">
                            <div class="card-body">
                                <ul class="nav nav-pills flex-column" id="basicPaymentTab" role="tablist">
                                    @include('basicpayment::tabs.navbar')
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="tab-content" id="myTabContent4">
                                    @include('basicpayment::sections.paypal')
                                    @include('basicpayment::sections.mpesa-stk-push')
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
    <script src="{{ asset('backend/js/jquery.uploadPreview.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            "use strict";

            var activeTab = localStorage.getItem("activeTab");
            if (activeTab) {
                $('#basicPaymentTab a[href="#' + activeTab + '"]').tab("show");
            } else {
                $("#basicPaymentTab a:first").tab("show");
            }

            $('a[data-toggle="tab"]').on("shown.bs.tab", function(e) {
                var newTab = $(e.target).attr("href").substring(1);
                localStorage.setItem("activeTab", newTab);
            });

            $.uploadPreview({
                input_field: "#image-upload-paypal",
                preview_box: "#image-preview-paypal",
                label_field: "#image-label-paypal",
                label_default: "{{ __('Choose Image') }}",
                label_selected: "{{ __('Change Image') }}",
                no_label: false,
                success_callback: null
            });

            $('#image-preview-paypal').css({
                'background-image': 'url({{ asset($basic_payment->paypal_image) }})',
                'background-size': 'contain',
                'background-position': 'center',
                'background-repeat': 'no-repeat'
            });

            $.uploadPreview({
                input_field: "#mpesa_stk_push_image_upload",
                preview_box: "#mpesa_stk_push_image_preview",
                label_field: "#mpesa_stk_push_image_label",
                label_default: "{{ __('Choose Image') }}",
                label_selected: "{{ __('Change Image') }}",
                no_label: false,
                success_callback: null
            });

            $('#mpesa_stk_push_image_preview').css({
                'background-image': 'url({{ asset($basic_payment->mpesa_stk_push_image ?? "uploads/website-images/mpesa.webp") }})',
                'background-size': 'contain',
                'background-position': 'center',
                'background-repeat': 'no-repeat'
            });
        });
    </script>
@endpush
