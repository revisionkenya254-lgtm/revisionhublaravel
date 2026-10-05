@extends('frontend.layouts.master')
@php($authSettings = $setting ?? Cache::get('setting'))
@section('meta_title', 'Register'. ' || ' . data_get($authSettings, 'app_name', config('app.name')))

@section('contents')
    <!-- breadcrumb-area -->
    <x-frontend.breadcrumb :title="__('Register')" :links="[['url' => route('home'), 'text' => __('Home')], ['url' => route('register'), 'text' => __('Register')]]" />
    <!-- breadcrumb-area-end -->

    <!-- singUp-area -->
    <section class="singUp-area section-py-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-8">
                    <div class="singUp-wrap">
                        <h2 class="title">{{ __('Create Your Account') }}</h2>
                        <p>{{ __('Create your account, then confirm it with a 5-digit OTP sent to your email.') }}
                        </p>
                        @if(data_get($authSettings, 'google_login_status') == 'active')
                        <div class="account__social">
                            <a href="{{ route('auth.social', 'google') }}" class="account__social-btn">
                                <img src="{{ asset('frontend/img/icons/google.svg') }}" alt="img">
                                {{ __('Continue with google') }}
                            </a>
                        </div>
                        <div class="account__divider">
                            <span>{{ __('or') }}</span>
                        </div>
                        @endif
                        <form method="POST" action="{{ route('register') }}" class="account__form">
                            @csrf

                            <div class="row gutter-20">
                                <div class="col-md-12">
                                    <div class="form-grp">
                                        <label for="fast-name">{{ __('Full Name') }} <code>*</code></label>
                                        <input type="text" id="fast-name" placeholder="{{ __('full name') }}"
                                            required
                                            name="name" value="{{ old('name') }}">
                                        <x-frontend.validation-error name="name" />
                                    </div>
                                </div>
                            </div>

                            <div class="row gutter-20">
                                <div class="col-md-5">
                                    <div class="form-grp">
                                        <label for="country_id">{{ __('Country') }} <code>*</code></label>
                                        <select name="country_id" id="country_id" class="form-select select2 country-select" data-placeholder="{{ __('Select country') }}" required>
                                            <option value="">{{ __('Select country') }}</option>
                                            @foreach (countries() as $country)
                                                <option value="{{ $country->id }}" @selected(old('country_id') == $country->id)>
                                                    {{ $country->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-frontend.validation-error name="country_id" />
                                    </div>
                                </div>

                                <div class="col-md-7">
                                    <div class="form-grp">
                                        <label for="phone">{{ __('Phone Number') }} <code>*</code></label>
                                        <input
                                            type="tel"
                                            id="phone"
                                            name="phone"
                                            placeholder="{{ __('Phone number') }}"
                                            value="{{ old('phone') }}"
                                            inputmode="tel"
                                            autocomplete="tel"
                                            required
                                        >
                                        <x-frontend.validation-error name="phone" />
                                    </div>
                                </div>
                            </div>

                            <div class="form-grp">
                                <label for="email">{{ __('Email') }} <code>*</code></label>
                                <input type="email" id="email" placeholder="{{ __('email') }}" name="email" value="{{ old('email') }}" required>
                                <x-frontend.validation-error name="email" />
                            </div>
                            <div class="form-grp">
                                <label for="password">{{ __('Password') }} <code>*</code></label>
                                <input type="password" id="password" placeholder="{{ __('password') }}" name="password" required>
                                <x-frontend.validation-error name="password" />
                            </div>
                            <div class="form-grp">
                                <label for="confirm-password">{{ __('Confirm Password') }} <code>*</code></label>
                                <input type="password" id="confirm-password" placeholder="{{ __('Confirm Password') }}"
                                    name="password_confirmation" required>
                                <x-frontend.validation-error name="password_confirmation" />
                            </div>

                            <!-- g-recaptcha -->
                            @if (data_get($authSettings, 'recaptcha_status') === 'active')
                                <div class="form-grp mt-3">
                                    <div class="g-recaptcha"
                                        data-sitekey="{{ data_get($authSettings, 'recaptcha_site_key') }}"></div>
                                    <x-frontend.validation-error name="g-recaptcha-response" />
                                </div>
                            @endif

                            <button type="submit" class="btn btn-two arrow-btn auth-submit-btn auth-submit-btn--light" data-loading-button>
                                <span class="auth-submit-btn__content">
                                    <span class="auth-submit-btn__label">{{ __('Sign Up & Send OTP') }}</span>
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
                            <p>{{ __('Already have an account?') }}<a href="{{ route('login') }}">{{ __('Login') }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- singUp-area-end -->
@endsection

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
    @vite('resources/js/register-phone.js')
@endpush

@push('styles')
    @include('partials.auth-button-styles')
@endpush
