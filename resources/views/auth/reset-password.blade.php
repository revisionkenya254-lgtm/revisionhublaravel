@extends('frontend.layouts.master')
@php($authSettings = $setting ?? Cache::get('setting'))
@section('meta_title', 'Reset Password'. ' || ' . data_get($authSettings, 'app_name', config('app.name')))

@section('contents')
    <!-- breadcrumb-area -->
    <x-frontend.breadcrumb
        :title="__('Reset Password')"
        :links="[]"
    />
    <!-- breadcrumb-area-end -->

    <!-- singUp-area -->
    <section class="singUp-area section-py-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-8">
                    <div class="singUp-wrap">
                        <h2 class="title">{{ __('Reset Password') }}</h2>
                        <p>{{ __('Enter your new password below') }}
                        </p>

                        <form method="POST" action="{{ route('reset-password-store', $token) }}" class="account__form">
                            @csrf
                            <div class="form-grp">
                                <label for="email">{{ __('Email') }} <code>*</code></label>
                                <input id="email" type="text" placeholder="{{ __('email') }}" name="email" value="{{ $user->email }}">
                                <x-frontend.validation-error name="email" />
                            </div>

                            <div class="form-grp">
                                <label for="password">{{ __('Password') }} <code>*</code></label>
                                <input id="password" type="password" placeholder="{{ __('password') }}" name="password" value="">
                                <x-frontend.validation-error name="password" />
                            </div>

                            <div class="form-grp">
                                <label for="confirm-password">{{ __('Confirm Password') }} <code>*</code></label>
                                <input id="confirm-password" type="password" placeholder="Confirm Password" name="password_confirmation" value="">
                                <x-frontend.validation-error name="password_confirmation" />
                            </div>

                            <!-- g-recaptcha -->
                            @if (data_get($authSettings, 'recaptcha_status') === 'active')
                            <div class="form-grp mt-3">
                                <div class="g-recaptcha" data-sitekey="{{ data_get($authSettings, 'recaptcha_site_key') }}"></div>
                                <x-frontend.validation-error name="g-recaptcha-response" />
                            </div>
                            @endif

                            <button type="submit" class="btn btn-two arrow-btn auth-submit-btn auth-submit-btn--light" data-loading-button>
                                <span class="auth-submit-btn__content">
                                    <span class="auth-submit-btn__label">{{ __('Reset Password') }}</span>
                                    <span class="auth-submit-btn__loading" aria-hidden="true">
                                        <span class="auth-submit-btn__spinner"></span>
                                        <span class="auth-submit-btn__progress">
                                            <span class="auth-submit-btn__bar"></span>
                                        </span>
                                    </span>
                                </span>
                                <img src="{{ asset('frontend/img/icons/right_arrow.svg') }}" alt="img" class="injectable">
                            </button>
                        </form>
                        <div class="account__switch">
                            <p>{{ __('Already have an account?') }}<a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- singUp-area-end -->
@endsection

@push('styles')
    @include('partials.auth-button-styles')
@endpush

@push('scripts')
    @include('partials.auth-button-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.account__form');
            const button = form?.querySelector('[data-loading-button]');

            if (!form || !button) {
                return;
            }

            form.addEventListener('submit', function () {
                window.AuthSubmitButton?.set(button, true);
            });
        });
    </script>
@endpush
