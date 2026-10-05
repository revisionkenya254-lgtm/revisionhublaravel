@extends('frontend.layouts.master')
@php($authSettings = $setting ?? Cache::get('setting'))
@section('meta_title', 'OTP Verification'. ' || ' . data_get($authSettings, 'app_name', config('app.name')))

@section('contents')
    <x-frontend.breadcrumb
        :title="__('Verify OTP')"
        :links="[
            ['url' => route('home'), 'text' => __('Home')],
            ['url' => route('auth.otp.notice', ['email' => $email, 'purpose' => $purpose]), 'text' => __('Verify OTP')],
        ]"
    />

    <section class="singUp-area section-py-120">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-8">
                    <div class="singUp-wrap">
                        <h2 class="title">{{ __('Check your email') }}</h2>
                        <p>{{ __('Enter the 5-digit OTP we sent to') }} <strong>{{ $email }}</strong>. {{ __('The code expires in 10 minutes.') }}</p>

                        <form method="POST" action="{{ route('auth.otp.verify') }}" class="account__form" id="otpVerifyForm">
                            @csrf
                            <input type="hidden" name="email" value="{{ old('email', $email) }}">
                            <input type="hidden" name="purpose" value="{{ old('purpose', $purpose) }}">

                            <div class="form-grp otp-group">
                                <label for="otp-1">{{ __('5 Digit OTP') }}</label>
                                <input type="hidden" id="otp" name="otp" value="{{ old('otp') }}">

                                <div class="otp-inputs" data-otp-inputs>
                                    @for ($i = 1; $i <= 5; $i++)
                                        <input
                                            id="otp-{{ $i }}"
                                            class="otp-input"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}"
                                            maxlength="1"
                                            aria-label="{{ __('OTP digit') }} {{ $i }}"
                                            data-otp-digit
                                        >
                                    @endfor
                                </div>

                                <p class="otp-help-text">{{ __('Paste the code or enter the digits one by one.') }}</p>
                                <x-frontend.validation-error name="otp" />
                            </div>

                            <button type="submit" class="btn btn-two arrow-btn auth-submit-btn auth-submit-btn--light" data-loading-button>
                                <span class="auth-submit-btn__content">
                                    <span class="auth-submit-btn__label">{{ __('Verify OTP') }}</span>
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

                        <form method="POST" action="{{ route('auth.otp.resend') }}" class="mt-3" id="otpResendForm">
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">
                            <input type="hidden" name="purpose" value="{{ $purpose }}">
                            <button type="submit" class="btn btn-border w-100 auth-submit-btn auth-submit-btn--light" data-loading-button>
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

                        <div class="account__switch">
                            <p>{{ __('Need another account?') }} <a href="{{ route('register') }}">{{ __('Sign Up') }}</a></p>
                            <p>{{ __('Want to use a different email?') }} <a href="{{ route('login') }}">{{ __('Back to Login') }}</a></p>
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
        .otp-action-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            overflow: hidden;
        }

        .otp-action-btn__content {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .otp-action-btn__loading {
            display: none;
            align-items: center;
            gap: 10px;
            min-width: 92px;
        }

        .otp-action-btn__spinner {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-top-color: #fff;
            animation: otpActionSpin 0.8s linear infinite;
        }

        .otp-action-btn__spinner--dark {
            border-color: rgba(15, 23, 42, 0.14);
            border-top-color: #121826;
        }

        .otp-action-btn__progress {
            position: relative;
            flex: 0 0 58px;
            height: 4px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.26);
        }

        .otp-action-btn__progress--dark {
            background: rgba(15, 23, 42, 0.08);
        }

        .otp-action-btn__bar {
            position: absolute;
            inset: 0;
            width: 45%;
            border-radius: inherit;
            background: rgba(255, 255, 255, 0.96);
            animation: otpActionProgress 1.1s ease-in-out infinite;
        }

        .otp-action-btn__bar--dark {
            background: #121826;
        }

        .otp-action-btn.is-loading,
        .otp-resend-btn.is-loading {
            cursor: progress;
        }

        .otp-action-btn.is-loading .otp-action-btn__label,
        .otp-resend-btn.is-loading .otp-action-btn__label {
            display: none;
        }

        .otp-action-btn.is-loading .otp-action-btn__loading,
        .otp-resend-btn.is-loading .otp-action-btn__loading {
            display: inline-flex;
        }

        .otp-action-btn.is-loading img {
            display: none;
        }

        @keyframes otpActionSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes otpActionProgress {
            0% {
                transform: translateX(-120%);
            }
            100% {
                transform: translateX(220%);
            }
        }

        .otp-group {
            margin-top: 12px;
        }

        .otp-inputs {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
            max-width: 360px;
            width: 100%;
        }

        .otp-input {
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

        .otp-input:focus {
            outline: none;
            border-color: var(--tg-theme-primary);
            box-shadow: 0 0 0 4px rgba(91, 103, 241, 0.09), 0 8px 24px rgba(15, 23, 42, 0.06);
            transform: translateY(-1px);
        }

        .otp-help-text {
            margin: 10px 0 0;
            font-size: 12px;
            color: #6b7280;
        }

        @media (max-width: 575.98px) {
            .otp-inputs {
                gap: 8px;
                max-width: 100%;
            }

            .otp-input {
                height: 46px;
                font-size: 16px;
                border-radius: 10px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const verifyForm = document.getElementById('otpVerifyForm');
            const resendForm = document.getElementById('otpResendForm');
            const hiddenOtpInput = document.getElementById('otp');
            const otpInputs = Array.from(document.querySelectorAll('[data-otp-digit]'));

            if (!verifyForm || !hiddenOtpInput || !otpInputs.length) {
                return;
            }

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

            const setButtonLoading = (button, loading) => {
                window.AuthSubmitButton?.set(button, loading);
            };

            const verifyButton = verifyForm.querySelector('[data-loading-button]');
            const resendButton = resendForm?.querySelector('[data-loading-button]');

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
