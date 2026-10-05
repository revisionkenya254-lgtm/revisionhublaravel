@extends('admin.auth.app')
@section('title')
    <title>{{ __('Admin Login') }}</title>
@endsection

@section('content')
    <section class="admin-login-page">
        <div class="admin-login-page__glow admin-login-page__glow--one"></div>
        <div class="admin-login-page__glow admin-login-page__glow--two"></div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-6 col-xl-5">
                    <div class="admin-login-card">
                        <div class="admin-login-card__brand">
                            <a href="{{ route('home') }}">
                                <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name }}" class="admin-login-card__logo">
                            </a>
                        </div>

                        <div class="admin-login-card__header">
                            <h1>{{ __('Welcome back') }}</h1>
                            <p>{{ __('Enter your admin email and we will send a 5-digit OTP to sign you in.') }}</p>
                        </div>

                        <form novalidate id="adminLoginForm" action="{{ route('admin.store-login') }}" method="POST" class="admin-login-form">
                            @csrf

                            <div class="form-group admin-field">
                                <label for="admin-email">{{ __('Email') }} <code>*</code></label>
                                <div class="admin-field__control">
                                    <i class="fas fa-envelope"></i>
                                    <input
                                        id="admin-email"
                                        type="email"
                                        class="form-control admin-input"
                                        name="email"
                                        tabindex="1"
                                        autofocus
                                        autocomplete="email"
                                        value="{{ old('email') }}"
                                        placeholder="{{ __('Enter your admin email') }}"
                                    >
                                </div>
                            </div>

                            <button id="adminLoginBtn" type="submit" class="admin-login-btn admin-login-btn--submit auth-submit-btn auth-submit-btn--light" tabindex="4" data-loading-button>
                                <span class="auth-submit-btn__content">
                                    <span class="auth-submit-btn__label">{{ __('Send OTP') }}</span>
                                    <span class="auth-submit-btn__loading" aria-hidden="true">
                                        <span class="auth-submit-btn__spinner"></span>
                                        <span class="auth-submit-btn__progress">
                                            <span class="auth-submit-btn__bar"></span>
                                        </span>
                                    </span>
                                </span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>

                        <div class="admin-login-card__footer">
                            <p>{{ __('Need help signing in?') }} <a href="{{ route('home') }}">{{ __('Return to website') }}</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('partials.auth-button-styles')
    <style>
        .admin-login-page {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 84px 0;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(91, 103, 241, 0.14), transparent 34%),
                radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.12), transparent 30%),
                linear-gradient(180deg, #f8f9ff 0%, #eef1ff 100%);
        }

        .admin-login-page__glow {
            position: absolute;
            border-radius: 999px;
            filter: blur(8px);
            opacity: 0.65;
            pointer-events: none;
        }

        .admin-login-page__glow--one {
            width: 240px;
            height: 240px;
            top: -72px;
            right: 8%;
            background: rgba(91, 103, 241, 0.14);
        }

        .admin-login-page__glow--two {
            width: 190px;
            height: 190px;
            left: 6%;
            bottom: -56px;
            background: rgba(245, 158, 11, 0.1);
        }

        .admin-login-card {
            position: relative;
            z-index: 1;
            padding: 36px 34px 30px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(91, 103, 241, 0.1);
            box-shadow: 0 16px 46px rgba(15, 23, 42, 0.1);
        }

        .admin-login-card__brand {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }

        .admin-login-card__logo {
            width: 170px;
            height: auto;
            object-fit: contain;
        }

        .admin-login-card__header {
            text-align: center;
            margin-bottom: 24px;
        }

        .admin-login-card__header h1 {
            margin-bottom: 8px;
            font-size: 28px;
            line-height: 1.18;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #111827;
        }

        .admin-login-card__header p {
            margin-bottom: 0;
            color: #6b7280;
            font-size: 15px;
            line-height: 1.72;
            max-width: 34ch;
            margin-left: auto;
            margin-right: auto;
        }

        .admin-login-form {
            margin-top: 0;
        }

        .admin-field {
            margin-bottom: 18px;
        }

        .admin-field label {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .admin-field__control {
            position: relative;
            margin-top: 8px;
        }

        .admin-field__control i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
            pointer-events: none;
        }

        .admin-input.form-control {
            height: 52px;
            padding-left: 42px;
            padding-right: 16px;
            border-radius: 13px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            font-size: 14px;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .admin-input.form-control:focus {
            border-color: var(--tg-theme-primary);
            box-shadow: 0 0 0 4px rgba(91, 103, 241, 0.07), 0 8px 22px rgba(15, 23, 42, 0.06);
            transform: translateY(-1px);
        }

        .admin-login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            overflow: hidden;
            width: 100%;
            min-height: 56px;
            padding: 0 24px;
            border: 0;
            border-radius: 16px;
            background: linear-gradient(135deg, #f4b000 0%, #f59e0b 100%);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            box-shadow: 0 15px 28px rgba(245, 158, 11, 0.22);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }

        .admin-login-btn:hover {
            filter: brightness(1.02);
            transform: translateY(-1px);
            box-shadow: 0 19px 32px rgba(245, 158, 11, 0.28);
        }

        .admin-login-btn__content {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .admin-login-btn__loading {
            display: none;
            align-items: center;
            gap: 10px;
            min-width: 92px;
        }

        .admin-login-btn__spinner {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-top-color: #fff;
            animation: adminLoginSpin 0.8s linear infinite;
        }

        .admin-login-btn__progress {
            position: relative;
            flex: 0 0 58px;
            height: 4px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.25);
        }

        .admin-login-btn__bar {
            position: absolute;
            inset: 0;
            width: 45%;
            border-radius: inherit;
            background: rgba(255, 255, 255, 0.96);
            animation: adminLoginProgress 1.1s ease-in-out infinite;
        }

        .admin-login-btn--submit.is-loading {
            cursor: progress;
        }

        .admin-login-btn--submit.is-loading .admin-login-btn__label {
            display: none;
        }

        .admin-login-btn--submit.is-loading .admin-login-btn__loading {
            display: inline-flex;
        }

        .admin-login-btn--submit.is-loading i {
            display: none;
        }

        @keyframes adminLoginSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes adminLoginProgress {
            0% {
                transform: translateX(-120%);
            }
            100% {
                transform: translateX(220%);
            }
        }

        .admin-login-card__footer {
            margin-top: 22px;
            text-align: center;
        }

        .admin-login-card__footer p {
            margin-bottom: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .admin-login-card__footer a {
            color: #4f46e5;
            font-weight: 700;
        }

        @media (max-width: 575.98px) {
            .admin-login-page {
                padding: 32px 0;
            }

            .admin-login-card {
                padding: 26px 18px 22px;
                border-radius: 18px;
            }

            .admin-login-card__header h1 {
                font-size: 25px;
            }

            .admin-login-card__logo {
                width: 150px;
            }

            .admin-input.form-control {
                height: 52px;
                border-radius: 12px;
            }

            .admin-login-btn {
                min-height: 52px;
                border-radius: 14px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('adminLoginForm');
            const button = document.getElementById('adminLoginBtn');

            if (!form || !button) {
                return;
            }

            const setLoading = (loading) => {
                window.AuthSubmitButton?.set(button, loading);
            };

            form.addEventListener('submit', function () {
                setLoading(true);
            });
        });
    </script>
@endpush

@push('scripts')
    @include('partials.auth-button-scripts')
@endpush
