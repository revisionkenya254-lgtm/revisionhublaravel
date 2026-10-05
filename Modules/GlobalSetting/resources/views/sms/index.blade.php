@extends('admin.master_layout')
@section('title')
    <title>{{ __('SMS Configuration') }}</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <div class="section-header-back">
                    <a href="{{ route('admin.settings') }}" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
                </div>
                <h1>{{ __('SMS Configuration') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a>
                    </div>
                    <div class="breadcrumb-item active"><a href="{{ route('admin.settings') }}">{{ __('Settings') }}</a>
                    </div>
                    <div class="breadcrumb-item">{{ __('SMS Configuration') }}</div>
                </div>
            </div>
            <div class="section-body">

                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <ul class="nav nav-pills flex-column" id="emailTab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active show" id="setting-tab" data-toggle="tab"
                                            href="#setting_tab" role="tab" aria-controls="setting"
                                            aria-selected="true">{{ __('Setting') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="twilio-template-tab" data-toggle="tab"
                                            href="#twilio_template_tab" role="tab" aria-controls="twilio-template"
                                            aria-selected="false">{{ __('Twilio') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="sms-template-tab" data-toggle="tab" href="#sms_template_tab"
                                            role="tab" aria-controls="sms-template"
                                            aria-selected="false">{{ __('SMS Template') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-body">
                                <div class="tab-content" id="myTabContent2">
                                    <div class="tab-pane fade active show" id="setting_tab" role="tabpanel">
                                        <form action="{{ route('admin.update-sms-configuration') }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="default_sms_driver">{{ __('Default SMS Driver') }}
                                                            <span class="text-danger">*</span></label>
                                                        <select name="default_sms_driver" id="default_sms_driver"
                                                            class="form-control">
                                                            <option
                                                                {{ $setting?->default_sms_driver == 'twilio' ? 'selected' : '' }}
                                                                value="twilio">{{ __('Twilio') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                @php
                                                    $settings = [
                                                        'sms_order_completed' => __('Order Completed'),
                                                        'sms_payment_status' => __('Payment Status'),
                                                        'sms_approved_withdraw' => __('Approved Withdraw'),
                                                        'sms_rejected_withdraw' => __('Rejected Withdraw'),
                                                        'sms_instructor_request_approved' => __('Instructor Request Approved'),
                                                        'sms_instructor_request_rejected' => __('Instructor Request Rejected'),
                                                        'sms_qna_reply_mail' => __('QnA Reply'),
                                                        'sms_live_class_mail' => __('Live Class'),
                                                        'sms_gift_course' => __('Gift Course'),
                                                    ];
                                                @endphp

                                                @foreach ($settings as $key => $label)
                                                    @if ($key == 'sms_gift_course' && !Nwidart\Modules\Facades\Module::has('GiftCourse'))
                                                        @continue
                                                    @endif
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label class="d-flex align-items-center">
                                                                <input type="hidden" value="0"
                                                                    name="{{ $key }}" class="custom-switch-input">
                                                                <input type="checkbox" value="1"
                                                                    name="{{ $key }}" class="custom-switch-input"
                                                                    {{ old($key, $setting?->$key) ? 'checked' : '' }}>
                                                                <span class="custom-switch-indicator"></span>
                                                                <span
                                                                    class="custom-switch-description">{{ $label }}</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="submit" class="btn btn-success">{{ __('Update') }}</button>
                                        </form>
                                    </div>
                                    <div class="tab-pane fade" id="twilio_template_tab" role="tabpanel">
                                        <form action="{{ route('admin.update-twilio-sms-configuration') }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="twilio_sid">{{ __('Twilio SID') }} <span
                                                                class="text-danger">*</span></label>
                                                        @if (env('APP_MODE') == 'DEMO')
                                                            <input name="twilio_sid" id="twilio_sid" value="twilio_sid"
                                                                class="form-control">
                                                        @else
                                                            <input name="twilio_sid" id="twilio_sid"
                                                                value="{{ $setting->twilio_sid }}" class="form-control">
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="twilio_token">{{ __('Auth token') }} <span
                                                                class="text-danger">*</span></label>
                                                        @if (env('APP_MODE') == 'DEMO')
                                                            <input name="twilio_token" id="twilio_token"
                                                                value="twilio_token" class="form-control">
                                                        @else
                                                            <input name="twilio_token" id="twilio_token"
                                                                value="{{ $setting->twilio_token }}"
                                                                class="form-control">
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="twilio_phone_code">{{ __('Phone Code') }} <span
                                                                class="text-danger">*</span></label>
                                                        <input name="twilio_phone_code" id="twilio_phone_code"
                                                            value="{{ $setting->twilio_phone_code }}"
                                                            class="form-control">
                                                    </div>
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="twilio_from">{{ __('Phone Number') }} <span
                                                                class="text-danger">*</span></label>
                                                        @if (env('APP_MODE') == 'DEMO')
                                                            <input name="twilio_from" id="twilio_from"
                                                                value="twilio_from" class="form-control">
                                                        @else
                                                            <input name="twilio_from" id="twilio_from"
                                                                value="{{ $setting->twilio_from }}" class="form-control">
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success">{{ __('Update') }}</button>
                                        </form>
                                    </div>

                                    <div class="tab-pane fade" id="sms_template_tab" role="tabpanel">
                                        <div class="table-responsive table-invoice">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>{{ __('SN') }}</th>
                                                        <th>{{ __('Email Template') }}</th>
                                                        <th>{{ __('Action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($templates as $index => $item)
                                                        @if ($item->name == 'gift_course' && !Nwidart\Modules\Facades\Module::has('GiftCourse'))
                                                            @continue
                                                        @endif
                                                        <tr>
                                                            <td>{{ ++$index }}</td>
                                                            <td>{{ ucfirst(Str::replace('_', ' ', $item->name)) }}</td>
                                                            <td>
                                                                <a href="{{ route('admin.edit-sms-template', $item->id) }}"
                                                                    class="btn btn-success btn-sm"><i
                                                                        class="fas fa-edit"></i></a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
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
    <script>
        $(document).ready(function() {
            "use strict";
            var activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                $('#emailTab a[href="#' + activeTab + '"]').tab('show');
            } else {
                $('#emailTab a:first').tab('show');
            }

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var newTab = $(e.target).attr('href').substring(1);
                localStorage.setItem('activeTab', newTab);
            });
        });
    </script>
@endpush
