@extends('frontend.layouts.master')
@php($authSettings = $setting ?? Cache::get('setting'))
@section('meta_title', 'Login'. ' || ' . data_get($authSettings, 'app_name', config('app.name')))
@section('contents')
    <!-- breadcrumb-area -->
    <x-frontend.breadcrumb
        :title="__('Login')"
        :links="[
            ['url' => route('home'), 'text' => __('Home')],
            ['url' => route('login'), 'text' => __('Login')],
        ]"
    />
    <!-- breadcrumb-area-end -->

    <!-- singUp-area -->
    <section class="singUp-area section-py-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-8">
                    <div class="singUp-wrap">
                        <h2 class="title">{{ __('Welcome back!') }}</h2>
                        <p>{{ __('Enter your email address and we will send you a 5-digit OTP to sign in securely.') }}
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
                        <form method="POST" action="{{ route('user-login') }}" class="account__form" id="mainLoginForm"
                            data-account-check-url="{{ route('user-login.check') }}"
                            data-admin-login-url="{{ route('admin.login') }}">
                            @csrf
                            <div class="form-grp">
                                <label for="email">{{ __('Email') }} <code>*</code></label>
                                <input id="email" type="email" placeholder="email" value="{{ old('email') }}" name="email"
                                    autocomplete="email">
                                <x-frontend.validation-error name="email" />
                            </div>
                            <div class="account__check">
                                <div class="account__check-forgot">
                                    <a href="{{ route('password.request') }}">{{ __('Forgot Password?') }}</a>
                                </div>
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
                                    <span class="auth-submit-btn__label">{{ __('Send OTP') }}</span>
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
                            <p>{{ __('Dont have an account?') }}<a href="{{ route('register') }}">{{ __('Sign Up') }}</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- singUp-area-end -->
@endsection

@push('scripts')
    <script>
        (function() {
            const form = document.getElementById('mainLoginForm');
            const emailInput = document.getElementById('email');

            if (!form || !emailInput) {
                return;
            }

            const checkUrl = form.dataset.accountCheckUrl;
            const adminLoginUrl = form.dataset.adminLoginUrl;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let isCheckingAccountType = false;
            let adminGuardTriggered = false;

            const submitButton = form.querySelector('button[type="submit"]');

            const setSubmitDisabled = (disabled) => {
                if (submitButton) {
                    submitButton.disabled = disabled;
                }
            };

            const setSubmitLoading = (loading) => {
                window.AuthSubmitButton?.set(submitButton, loading);
            };

            const redirectToAdminLogin = () => {
                adminGuardTriggered = true;
                toastr.error(@json(__('Admin accounts must sign in from the admin login page.')));
                window.location.href = adminLoginUrl;
            };

            const checkAccountType = async () => {
                const email = emailInput.value.trim();

                if (!email || !checkUrl || !csrfToken) {
                    return true;
                }

                if (isCheckingAccountType) {
                    return false;
                }

                isCheckingAccountType = true;
                setSubmitDisabled(true);

                try {
                    const response = await fetch(checkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            email: email,
                        }),
                    });

                    const data = await response.json();

                    if (response.ok && data.is_admin) {
                        redirectToAdminLogin();
                        return false;
                    }

                    return true;
                } catch (error) {
                    return true;
                } finally {
                    isCheckingAccountType = false;
                    setSubmitDisabled(false);
                }
            };

            emailInput.addEventListener('blur', () => {
                checkAccountType();
            });

            form.addEventListener('submit', async (event) => {
                if (adminGuardTriggered) {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();

                const canSubmit = await checkAccountType();

                if (canSubmit && !adminGuardTriggered) {
                    setSubmitLoading(true);
                    form.submit();
                } else {
                    setSubmitLoading(false);
                }
            });
        })();
    </script>
@endpush

@push('styles')
    @include('partials.auth-button-styles')
@endpush

@push('scripts')
    @include('partials.auth-button-scripts')
@endpush
