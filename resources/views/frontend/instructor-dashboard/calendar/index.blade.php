@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title">
            <h4 class="title">{{ __('Google Calendar Connect') }}</h4>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <label>{{ __('Google Calendar') }}</label>
                    <p class="text-muted">{{ __('Connect your Google Calendar to automatically add live class events.') }}
                    </p>
                    @if (auth('web')->user()->google_access_token)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> {{ __('Google Calendar is connected.') }}
                        </div>
                        <a href="{{ route('instructor.google-calendar.disconnect') }}" class="btn btn-danger btn-sm confirm-action"
                           data-method="POST"
                           data-confirm-title="{{ __('Are you sure?') }}"
                           data-confirm-message="{{ __('You want to disconnect Google Calendar?') }}"
                           data-confirm-button-text="{{ __('Yes, disconnect') }}"
                           data-success-title="{{ __('Disconnected') }}"
                           data-success-message="{{ __('Google Calendar has been disconnected.') }}">
                            <i class="fas fa-unlink"></i> {{ __('Disconnect') }}
                        </a>
                    @else
                        <a href="{{ route('instructor.google-calendar.connect') }}" class="btn btn-primary btn-sm">
                            <i class="fab fa-google"></i> {{ __('Connect Google Calendar') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endsection
