@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title">
            <h4 class="title">{{ __('Create Quiz') }}</h4>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="dashboard__contact-form-wrap">
                    <div class="dashboard__contact-form">
                        <h5>{{ __('Short Quiz') }}</h5>
                        <p>{{ __('Fewer than 10 questions. Sale price must not exceed KES 50.') }}</p>
                        <a href="{{ route('instructor.products.create', ['type' => 'quiz']) }}" class="btn btn-primary">{{ __('Create Short Quiz') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard__contact-form-wrap">
                    <div class="dashboard__contact-form">
                        <h5>{{ __('Long Quiz') }}</h5>
                        <p>{{ __('More than 10 questions. Sale price must be at least KES 20.') }}</p>
                        <a href="{{ route('instructor.products.create', ['type' => 'quiz']) }}" class="btn btn-primary">{{ __('Create Long Quiz') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
