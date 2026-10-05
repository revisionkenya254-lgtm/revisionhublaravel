@extends('admin.auth.app')
@section('title')
    <title>{{ __('Admin OTP Verification') }}</title>
@endsection

@section('content')
    <section class="admin-otp-page">
        <div class="admin-otp-page__glow admin-otp-page__glow--one"></div>
        <div class="admin-otp-page__glow admin-otp-page__glow--two"></div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-6 col-xl-5">
                    <div class="admin-otp-card">
                        <div class="admin-otp-card__brand">
                            <a href="{{ route('home') }}">
                                <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name }}" class="admin-otp-card__logo">
                            </a>
                        </div>

                        <div class="admin-otp-card__header">
                            <h1>{{ __('Check your email') }}</h1>
                            <p>{{ __('Enter the 5-digit OTP we sent to') }} <strong>{{ $email }}</strong>. {{ __('The code expires in 10 minutes.') }}</p>
                        </div>

                        <form method="POST" action="{{ route('admin.otp.verify') }}" class="admin-otp-form" id="adminOtpVerifyForm">
                            @csrf
                            <input type="hidden" name="email" value="{{ old('email', $email) }}">
                            <input type="hidden" name="purpose" value="{{ old('purpose', $purpose) }}">

                            <div class="form-group admin-otp-group">
                                <label for="admin-otp-1">{{ __('5 Digit OTP') }}</label>
                                <input type="hidden" id="admin-otp" name="otp" value="{{ old('otp') }}">

                                <div class="admin-otp-inputs" data-admin-otp-inputs>
                                    @for ($i = 1; $i <= 5; $i++)
                                        <input
                                            id="admin-otp-{{ $i }}"
                                            class="admin-otp-input"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}"
                                            maxlength="1"
                                            aria-label="{{ __('OTP digit') }} {{ $i }}"
                                            data-admin-otp-digit
                                        >
                                    @endfor
                                </div>

                                <p class="admin-otp-help">{{ __('Paste the code or enter the digits one by one.') }}</p>
                                <x-frontend.validation-error name="otp" />
                            </div>

                            <button type="submit" class="admin-otp-btn admin-otp-btn--submit auth-submit-btn auth-submit-btn--light" data-loading-button>
                                <span class="auth-submit-btn__content">
                                    <span class="auth-submit-btn__label">{{ __('Verify OTP') }}</span>
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

                        <form method="POST" action="{{ route('admin.otp.resend') }}" class="admin-otp-resend" id="adminOtpResendForm">
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">
                            <input type="hidden" name="purpose" value="{{ $purpose }}">
                            <button type="submit" class="admin-otp-resend__btn admin-otp-btn--resend auth-submit-btn auth-submit-btn--light" data-loading-button>
                                <span class="auth-submit-btn__content">
                                    <span class="auth-submit-btn__label">{{ __('Resend OTP') }}</span>
                                    <span class="auth-submit-btn__loading" aria-hidden="true">
                                        <span class="auth-submit-btn__spinner"></span>
                                        <span class="auth-submit-btn__progress">
                                            <span class="auth-submit-btn__bar"></span>
                                        </span>
                                    </span>
                                </span>
                            </button>
                        </form>

                        <div class="admin-otp-card__footer">
                            <p><a href="{{ route('admin.login') }}">{{ __('Back to Admin Login') }}</a></p>
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
        .admin-otp-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            overflow: hidden;
        }

        .admin-otp-btn__content {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .admin-otp-btn__loading {
            display: none;
            align-items: center;
            gap: 10px;
            min-width: 92px;
        }

        .admin-otp-btn__spinner {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.42);
            border-top-color: #fff;
            animation: adminOtpSpin 0.8s linear infinite;
        }

        .admin-otp-btn__spinner--dark {
            border-color: rgba(79, 70, 229, 0.16);
            border-top-color: #4f46e5;
        }

        .admin-otp-btn__progress {
            position: relative;
            flex: 0 0 58px;
            height: 4px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.25);
        }

        .admin-otp-btn__progress--dark {
            background: rgba(79, 70, 229, 0.08);
        }

        .admin-otp-btn__bar {
            position: absolute;
            inset: 0;
            width: 45%;
            border-radius: inherit;
            background: rgba(255, 255, 255, 0.96);
            animation: adminOtpProgress 1.1s ease-in-out infinite;
        }

        .admin-otp-btn__bar--dark {
            background: #4f46e5;
        }

        .admin-otp-btn--submit.is-loading,
        .admin-otp-btn--resend.is-loading {
            cursor: progress;
        }

        .admin-otp-btn--submit.is-loading .admin-otp-btn__label,
        .admin-otp-btn--resend.is-loading .admin-otp-btn__label {
            display: none;
        }

        .admin-otp-btn--submit.is-loading .admin-otp-btn__loading,
        .admin-otp-btn--resend.is-loading .admin-otp-btn__loading {
            display: inline-flex;
        }

        .admin-otp-btn--submit.is-loading i,
        .admin-otp-btn--resend.is-loading i {
            display: none;
        }

        @keyframes adminOtpSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes adminOtpProgress {
            0% {
                transform: translateX(-120%);
            }
            100% {
                transform: translateX(220%);
            }
        }

        .admin-otp-page {
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

        .admin-otp-page__glow {
            position: absolute;
            border-radius: 999px;
            filter: blur(8px);
            opacity: 0.65;
            pointer-events: none;
        }

        .admin-otp-page__glow--one {
            width: 240px;
            height: 240px;
            top: -72px;
            right: 8%;
            background: rgba(91, 103, 241, 0.14);
        }

        .admin-otp-page__glow--two {
            width: 190px;
            height: 190px;
            left: 6%;
            bottom: -56px;
            background: rgba(245, 158, 11, 0.1);
        }

        .admin-otp-card {
            position: relative;
            z-index: 1;
            padding: 36px 34px 30px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(91, 103, 241, 0.1);
            box-shadow: 0 16px 46px rgba(15, 23, 42, 0.1);
        }

        .admin-otp-card__brand {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }

        .admin-otp-card__logo {
            width: 170px;
            height: auto;
            object-fit: contain;
        }

        .admin-otp-card__header {
            text-align: center;
            margin-bottom: 24px;
        }

        .admin-otp-card__header h1 {
            margin-bottom: 8px;
            font-size: 28px;
            line-height: 1.18;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #111827;
        }

        .admin-otp-card__header p {
            margin-bottom: 0;
            color: #6b7280;
            font-size: 15px;
            line-height: 1.72;
            max-width: 34ch;
            margin-left: auto;
            margin-right: auto;
        }

        .admin-otp-group {
            margin-top: 0;
        }

        .admin-otp-group label {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .admin-otp-inputs {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
            width: 100%;
        }

        .admin-otp-input {
            width: 100%;
            height: 52px;
            border: 1px solid rgba(91, 103, 241, 0.16);
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.18em;
            font-variant-numeric: tabular-nums;
            color: #1f2937;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .admin-otp-input:focus {
            outline: none;
            border-color: var(--tg-theme-primary);
            box-shadow: 0 0 0 4px rgba(91, 103, 241, 0.09), 0 8px 24px rgba(15, 23, 42, 0.06);
            transform: translateY(-1px);
        }

        .admin-otp-help {
            margin: 10px 0 0;
            font-size: 12px;
            color: #6b7280;
        }

        .admin-otp-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
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

        .admin-otp-btn:hover {
            filter: brightness(1.02);
            transform: translateY(-1px);
            box-shadow: 0 19px 32px rgba(245, 158, 11, 0.28);
        }

        .admin-otp-resend {
            margin-top: 12px;
        }

        .admin-otp-resend__btn {
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

        .admin-otp-resend__btn:hover {
            filter: brightness(1.02);
            transform: translateY(-1px);
            box-shadow: 0 19px 32px rgba(245, 158, 11, 0.28);
        }

        .admin-otp-card__footer {
            margin-top: 22px;
            text-align: center;
        }

        .admin-otp-card__footer p {
            margin-bottom: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .admin-otp-card__footer a {
            color: #4f46e5;
            font-weight: 700;
        }

        @media (max-width: 575.98px) {
            .admin-otp-page {
                padding: 32px 0;
            }

            .admin-otp-card {
                padding: 26px 18px 22px;
                border-radius: 18px;
            }

            .admin-otp-card__header h1 {
                font-size: 25px;
            }

            .admin-otp-card__logo {
                width: 150px;
            }

            .admin-otp-input {
                height: 50px;
                font-size: 16px;
                border-radius: 10px;
            }

            .admin-otp-btn {
                min-height: 52px;
                border-radius: 14px;
            }

            .admin-otp-resend__btn {
                min-height: 52px;
                border-radius: 14px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const verifyForm = document.getElementById('adminOtpVerifyForm');
            const resendForm = document.getElementById('adminOtpResendForm');
            const hiddenOtpInput = document.getElementById('admin-otp');
            const otpInputs = Array.from(document.querySelectorAll('[data-admin-otp-digit]'));

            if (!verifyForm || !hiddenOtpInput || !otpInputs.length) {
                return;
            }

            const setButtonLoading = (button, loading) => {
                window.AuthSubmitButton?.set(button, loading);
            };

            const verifyButton = verifyForm.querySelector('[data-loading-button]');
            const resendButton = resendForm?.querySelector('[data-loading-button]');

            const syncHiddenValue = () => {
                hiddenOtpInput.value = otpInputs.map((input) => input.value).join('');
            };

            const fillOtp = (value) => {
                const digits = (value || '').replace(/\D/g, '').slice(0, otpInputs.length).split('');

                otpInputs.forEach((input, index) => {
                    input.value = digits[index] || '';
                });

                syncHiddenValue();

                const nextIndex = digits.length < otpInputs.length ? digits.length : otpInputs.length - 1;
                otpInputs[nextIndex]?.focus();
                otpInputs[nextIndex]?.select?.();
            };

            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function () {
                    const digits = this.value.replace(/\D/g, '');

                    if (!digits) {
                        this.value = '';
                        syncHiddenValue();
                        return;
                    }

                    this.value = digits.charAt(0);
                    syncHiddenValue();

                    if (digits.length > 1) {
                        fillOtp(digits);
                        return;
                    }

                    const nextInput = otpInputs[index + 1];
                    if (nextInput) {
                        nextInput.focus();
                        nextInput.select?.();
                    }
                });

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Backspace' && !this.value && index > 0) {
                        otpInputs[index - 1].focus();
                        otpInputs[index - 1].value = '';
                        syncHiddenValue();
                    }
                });

                input.addEventListener('paste', function (event) {
                    event.preventDefault();
                    const pastedValue = (event.clipboardData || window.clipboardData).getData('text');
                    fillOtp(pastedValue);
                });
            });

            verifyForm.addEventListener('submit', function () {
                syncHiddenValue();
                setButtonLoading(verifyButton, true);
            });

            resendForm?.addEventListener('submit', function () {
                setButtonLoading(resendButton, true);
            });

            fillOtp(hiddenOtpInput.value);
        });
    </script>
@endpush

@push('scripts')
    @include('partials.auth-button-scripts')
@endpush
