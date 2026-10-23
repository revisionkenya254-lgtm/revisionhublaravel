@extends('admin.master_layout')

@section('title')
    <title>{{ __('Sabbath Plan') }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Sabbath Plan') }}</h1>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4>{{ __('Automatic free access') }}</h4>
                                <div class="card-header-action">
                                    <span class="badge badge-{{ $active ? 'success' : 'secondary' }}">
                                        {{ $active ? __('Active now') : __('Waiting for next cycle') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="mb-4">
                                    {{ __('Every student automatically receives full catalogue access during each Sabbath window. No order, payment, enrollment, or permanent subscription record is created.') }}
                                </p>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <small class="text-muted d-block mb-1">{{ $active ? __('Current cycle starts') : __('Next cycle starts') }}</small>
                                            <strong>{{ $window['starts_at']->format('l, d M Y g:i A T') }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <small class="text-muted d-block mb-1">{{ $active ? __('Current cycle ends') : __('Next cycle ends') }}</small>
                                            <strong>{{ $window['ends_at']->format('l, d M Y g:i A T') }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-light border mb-0">
                                    <i class="fas fa-eye-slash mr-2"></i>
                                    {{ __('This internal plan is hidden from student subscription pages and the public plans API.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card card-primary">
                            <div class="card-header"><h4>{{ __('Plan details') }}</h4></div>
                            <div class="card-body">
                                <dl class="mb-0">
                                    <dt>{{ __('Plan') }}</dt>
                                    <dd>{{ $plan['name'] }}</dd>
                                    <dt>{{ __('Price') }}</dt>
                                    <dd>{{ $plan['price'] }}</dd>
                                    <dt>{{ __('Recurring schedule') }}</dt>
                                    <dd>{{ __('Friday 6:00 PM to Saturday 7:00 PM') }}</dd>
                                    <dt>{{ __('Timezone') }}</dt>
                                    <dd>{{ $plan['timezone'] }}</dd>
                                    <dt>{{ __('Current local time') }}</dt>
                                    <dd class="mb-0">{{ $now->format('l, d M Y g:i A T') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
