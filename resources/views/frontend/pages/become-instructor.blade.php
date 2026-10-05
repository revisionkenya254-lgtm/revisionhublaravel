@extends('frontend.layouts.master')
@section('meta_title', 'Become Instructor'. ' || ' . $setting->app_name)
@section('body_class', 'instructor-onboarding-page')

@php
    $steps = [
        [
            'name' => 'teaching_experience',
            'title' => __('What kind of teaching have you done before?'),
            'intro' => __('Online courses are video-based experiences that give students the chance to learn actionable skills. Whether you have experience teaching, or it is your first time, we will help you package your knowledge into an online course that improves student lives.'),
            'image' => asset('frontend/img/banner/banner_img.png'),
            'options' => [
                'in_person_informally' => __('In person, informally'),
                'in_person_professionally' => __('In person, professionally'),
                'online' => __('Online'),
                'other' => __('Other'),
            ],
        ],
        [
            'name' => 'video_experience',
            'title' => __('How much of a video pro are you?'),
            'intro' => __('Over the years we have helped thousands of instructors learn how to record at home. No matter your experience level, you can become a video pro too. We will equip you with resources, tips, and support to help you succeed.'),
            'image' => asset('frontend/img/instructor/h2_instructor02.png'),
            'options' => [
                'beginner' => __('I am a beginner'),
                'some_knowledge' => __('I have some knowledge'),
                'experienced' => __('I am experienced'),
                'ready_to_upload' => __('I have videos ready to upload'),
            ],
        ],
        [
            'name' => 'audience_level',
            'title' => __('Do you have an audience to share your course with?'),
            'intro' => __('Once you publish your course, you can grow your student audience and make an impact with the support of marketplace promotions and your own marketing efforts. Together, we will help the right students discover your course.'),
            'image' => asset('frontend/img/instructor/h2_instructor03.png'),
            'options' => [
                'not_at_the_moment' => __('Not at the moment'),
                'small_following' => __('I have a small following'),
                'sizeable_following' => __('I have a sizeable following'),
            ],
        ],
    ];
@endphp

@push('styles')
    <style>
        body.instructor-onboarding-active header,
        body.instructor-onboarding-active .footer__area,
        body.instructor-onboarding-active .scroll__top {
            display: none !important;
        }

        body.instructor-onboarding-page {
            background: #fff;
        }

        .instructor-landing {
            background: #fff;
            color: #171b35;
        }

        body.instructor-onboarding-active .instructor-landing {
            display: none;
        }

        .instructor-landing__hero {
            min-height: 570px;
            display: grid;
            grid-template-columns: minmax(320px, 520px) 1fr;
            align-items: center;
            gap: 42px;
            padding: 64px clamp(18px, 5vw, 70px) 0;
            background: #f3f4f7;
            overflow: hidden;
        }

        .instructor-landing__hero h1 {
            max-width: 430px;
            margin-bottom: 18px;
            font-size: clamp(44px, 5vw, 64px);
            line-height: .98;
            font-weight: 800;
            color: #262840;
        }

        .instructor-landing__hero p {
            max-width: 410px;
            margin-bottom: 22px;
            font-size: 19px;
            line-height: 1.45;
            color: #1f2a44;
        }

        .instructor-landing__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 220px;
            min-height: 48px;
            padding: 0 26px;
            border: 0;
            border-radius: 7px;
            background: var(--tg-theme-primary);
            color: #fff;
            font-weight: 800;
        }

        .instructor-landing__btn:hover {
            color: #fff;
            filter: brightness(.95);
        }

        .instructor-landing__hero-image {
            align-self: end;
            display: flex;
            justify-content: center;
        }

        .instructor-landing__hero-image img {
            width: min(520px, 100%);
            max-height: 570px;
            object-fit: contain;
        }

        .instructor-landing__section {
            padding: 72px clamp(18px, 5vw, 70px);
        }

        .instructor-landing__section h2 {
            margin-bottom: 44px;
            text-align: center;
            font-size: clamp(34px, 4vw, 50px);
            line-height: 1.08;
            font-weight: 800;
            color: #171b35;
        }

        .instructor-landing__reasons {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 46px;
            text-align: center;
        }

        .instructor-landing__reason img {
            width: 74px;
            height: 74px;
            margin-bottom: 18px;
            object-fit: contain;
        }

        .instructor-landing__reason h3 {
            margin-bottom: 8px;
            font-size: 18px;
            font-weight: 800;
            color: #111827;
        }

        .instructor-landing__reason p {
            max-width: 330px;
            margin: 0 auto;
            font-size: 16px;
            line-height: 1.65;
            color: #25304a;
        }

        .instructor-landing__stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 26px;
            padding: 52px clamp(18px, 6vw, 90px);
            background: var(--tg-theme-primary);
            color: #fff;
            text-align: center;
        }

        .instructor-landing__stat strong {
            display: block;
            margin-bottom: 4px;
            font-size: clamp(34px, 4vw, 48px);
            line-height: 1;
            font-weight: 900;
        }

        .instructor-landing__stat span {
            font-size: 16px;
        }

        .instructor-landing__begin {
            display: grid;
            grid-template-columns: minmax(280px, 460px) minmax(280px, 420px);
            align-items: center;
            justify-content: center;
            gap: 90px;
        }

        .instructor-landing__tabs {
            display: flex;
            justify-content: center;
            gap: 48px;
            margin: -22px 0 48px;
            font-size: clamp(20px, 2vw, 24px);
            font-weight: 800;
            color: #596073;
        }

        .instructor-landing__tabs span:first-child {
            color: #171b35;
            border-bottom: 2px solid currentColor;
        }

        .instructor-landing__begin-text p {
            margin-bottom: 16px;
            font-size: 18px;
            line-height: 1.6;
            color: #25304a;
        }

        .instructor-landing__begin-text h3 {
            margin: 26px 0 10px;
            font-size: 18px;
            font-weight: 800;
            color: #111827;
        }

        .instructor-landing__begin-image img {
            width: min(360px, 100%);
            object-fit: contain;
        }

        .instructor-landing__support {
            position: relative;
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            text-align: center;
        }

        .instructor-landing__support::before,
        .instructor-landing__support::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 250px;
            height: 250px;
            background: #f1f0ff;
            transform: translateY(-50%) rotate(6deg);
        }

        .instructor-landing__support::before {
            left: -110px;
        }

        .instructor-landing__support::after {
            right: -110px;
        }

        .instructor-landing__support-inner {
            max-width: 660px;
            position: relative;
            z-index: 1;
        }

        .instructor-landing__support h2 {
            margin-bottom: 12px;
        }

        .instructor-landing__support p {
            font-size: 18px;
            line-height: 1.55;
            color: #25304a;
        }

        .instructor-landing__cta {
            padding: 64px 18px 86px;
            background: #f6f7fb;
            text-align: center;
        }

        .instructor-landing__cta h2 {
            margin-bottom: 14px;
        }

        .instructor-landing__cta p {
            max-width: 520px;
            margin: 0 auto 24px;
            font-size: 23px;
            line-height: 1.35;
            color: #1f2a44;
        }

        body.instructor-onboarding-page .main-area {
            min-height: 100vh;
        }

        .instructor-onboarding {
            display: none;
            min-height: 100vh;
            padding-bottom: 96px;
            color: #0f172a;
        }

        body.instructor-onboarding-active .instructor-onboarding {
            display: block;
        }

        .instructor-onboarding__topbar {
            display: grid;
            grid-template-columns: 132px 1fr 92px;
            align-items: center;
            min-height: 74px;
            border-bottom: 1px solid #d9d7e5;
            background: #fff;
        }

        .instructor-onboarding__brand {
            display: flex;
            align-items: center;
            height: 74px;
            padding: 0 18px;
            border-right: 1px solid #d9d7e5;
        }

        .instructor-onboarding__brand img {
            max-width: 105px;
            max-height: 44px;
        }

        .instructor-onboarding__step-label {
            padding: 0 24px;
            font-size: 18px;
            font-weight: 500;
            color: #111827;
        }

        .instructor-onboarding__exit {
            justify-self: end;
            margin-right: 24px;
            font-weight: 700;
            color: var(--tg-theme-primary);
        }

        .instructor-onboarding__progress {
            height: 4px;
            background: #d7d4e4;
        }

        .instructor-onboarding__progress span {
            display: block;
            width: 25%;
            height: 100%;
            background: var(--tg-theme-primary);
            transition: width .25s ease;
        }

        .instructor-onboarding__content {
            max-width: 1320px;
            margin: 0 auto;
            padding: 18px 22px 42px;
        }

        .instructor-onboarding__step {
            display: none;
        }

        .instructor-onboarding__step.active {
            display: block;
        }

        .instructor-onboarding__intro {
            max-width: 820px;
            margin-bottom: 58px;
            font-size: 16px;
            line-height: 1.65;
            color: #1f2a44;
        }

        .instructor-onboarding__grid {
            display: grid;
            grid-template-columns: minmax(280px, 480px) minmax(260px, 1fr);
            align-items: center;
            gap: 80px;
        }

        .instructor-onboarding__question {
            margin-bottom: 16px;
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
        }

        .instructor-onboarding__options {
            display: grid;
            gap: 10px;
        }

        .instructor-onboarding__option {
            position: relative;
            display: flex;
            align-items: center;
            min-height: 52px;
            padding: 12px 18px 12px 52px;
            border: 1px solid #9aa3bd;
            border-radius: 4px;
            background: #fff;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .instructor-onboarding__option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .instructor-onboarding__option span::before {
            content: "";
            position: absolute;
            left: 16px;
            top: 50%;
            width: 20px;
            height: 20px;
            border: 2px solid #35405d;
            border-radius: 50%;
            transform: translateY(-50%);
        }

        .instructor-onboarding__option span::after {
            content: "";
            position: absolute;
            left: 21px;
            top: 50%;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--tg-theme-primary);
            opacity: 0;
            transform: translateY(-50%);
        }

        .instructor-onboarding__option:has(input:checked) {
            border-color: var(--tg-theme-primary);
            box-shadow: inset 0 0 0 1px var(--tg-theme-primary);
        }

        .instructor-onboarding__option:has(input:checked) span::before {
            border-color: var(--tg-theme-primary);
        }

        .instructor-onboarding__option:has(input:checked) span::after {
            opacity: 1;
        }

        .instructor-onboarding__visual {
            display: flex;
            justify-content: center;
            min-height: 310px;
        }

        .instructor-onboarding__visual img {
            width: min(360px, 100%);
            max-height: 340px;
            object-fit: contain;
        }

        .instructor-onboarding__application {
            max-width: 760px;
            margin: 0 auto;
        }

        .instructor-onboarding__application .singUp-wrap {
            box-shadow: none;
            padding: 0;
        }

        .instructor-onboarding__application .title {
            margin-bottom: 14px;
        }

        .instructor-onboarding__actions {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 72px;
            padding: 12px 28px;
            border-top: 1px solid #eceaf2;
            background: #fff;
            box-shadow: 0 -8px 24px rgba(15, 23, 42, .06);
        }

        .instructor-onboarding__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 86px;
            min-height: 40px;
            padding: 0 18px;
            border-radius: 8px;
            border: 1px solid var(--tg-theme-primary);
            font-weight: 800;
            background: #fff;
            color: var(--tg-theme-primary);
        }

        .instructor-onboarding__btn--primary {
            border-color: var(--tg-theme-primary);
            background: var(--tg-theme-primary);
            color: #fff;
        }

        .instructor-onboarding__btn:disabled {
            border-color: #ddc8f7;
            background: #ddc8f7;
            color: #fff;
            cursor: not-allowed;
        }

        .instructor-onboarding__submit {
            display: none;
        }

        .instructor-onboarding__submit.active {
            display: inline-flex;
        }

        @media (max-width: 991px) {
            .instructor-landing__hero,
            .instructor-landing__begin {
                grid-template-columns: 1fr;
            }

            .instructor-landing__hero {
                min-height: auto;
                padding-top: 46px;
            }

            .instructor-landing__reasons,
            .instructor-landing__stats {
                grid-template-columns: 1fr;
            }

            .instructor-landing__tabs {
                flex-wrap: wrap;
                gap: 18px 28px;
            }

            .instructor-onboarding__grid {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .instructor-onboarding__visual {
                min-height: 220px;
            }
        }

        @media (max-width: 575px) {
            .instructor-onboarding__topbar {
                grid-template-columns: 112px 1fr 64px;
            }

            .instructor-onboarding__brand {
                padding: 0 14px;
            }

            .instructor-onboarding__step-label {
                padding: 0 14px;
                font-size: 15px;
            }

            .instructor-onboarding__exit {
                margin-right: 14px;
            }

            .instructor-onboarding__content {
                padding: 18px 16px 34px;
            }

            .instructor-onboarding__intro {
                margin-bottom: 30px;
            }

            .instructor-onboarding__actions {
                padding: 12px 16px;
            }
        }
    </style>
@endpush

@section('contents')
    <section class="instructor-landing" data-instructor-landing>
        <div class="instructor-landing__hero">
            <div>
                <h1>{{ __('Come teach with us') }}</h1>
                <p>{{ __('Become an instructor and change lives, including your own.') }}</p>
                <button type="button" class="instructor-landing__btn" data-start-onboarding>{{ __('Get started') }}</button>
            </div>
            <div class="instructor-landing__hero-image">
                <img src="{{ asset('frontend/img/instructor/h2_instructor01.png') }}" alt="{{ __('Instructor') }}">
            </div>
        </div>

        <div class="instructor-landing__section">
            <h2>{{ __('So many reasons to start') }}</h2>
            <div class="instructor-landing__reasons">
                <div class="instructor-landing__reason">
                    <img src="{{ asset('frontend/img/icons/features_icon01.svg') }}" alt="">
                    <h3>{{ __('Teach your way') }}</h3>
                    <p>{{ __('Publish the course you want, in the way you want, and always have control of your own content.') }}</p>
                </div>
                <div class="instructor-landing__reason">
                    <img src="{{ asset('frontend/img/icons/features_icon02.svg') }}" alt="">
                    <h3>{{ __('Inspire learners') }}</h3>
                    <p>{{ __('Teach what you know and help learners explore their interests, gain new skills, and advance their careers.') }}</p>
                </div>
                <div class="instructor-landing__reason">
                    <img src="{{ asset('frontend/img/icons/features_icon03.svg') }}" alt="">
                    <h3>{{ __('Get rewarded') }}</h3>
                    <p>{{ __('Expand your professional network, build your expertise, and earn money on each paid enrollment.') }}</p>
                </div>
            </div>
        </div>

        <div class="instructor-landing__stats">
            <div class="instructor-landing__stat">
                <strong>80M</strong>
                <span>{{ __('Students') }}</span>
            </div>
            <div class="instructor-landing__stat">
                <strong>75+</strong>
                <span>{{ __('Languages') }}</span>
            </div>
            <div class="instructor-landing__stat">
                <strong>1.1B</strong>
                <span>{{ __('Enrollments') }}</span>
            </div>
            <div class="instructor-landing__stat">
                <strong>180+</strong>
                <span>{{ __('Countries') }}</span>
            </div>
            <div class="instructor-landing__stat">
                <strong>17,200+</strong>
                <span>{{ __('Enterprise customers') }}</span>
            </div>
        </div>

        <div class="instructor-landing__section">
            <h2>{{ __('How to begin') }}</h2>
            <div class="instructor-landing__tabs" aria-hidden="true">
                <span>{{ __('Plan your curriculum') }}</span>
                <span>{{ __('Record your video') }}</span>
                <span>{{ __('Launch your course') }}</span>
            </div>
            <div class="instructor-landing__begin">
                <div class="instructor-landing__begin-text">
                    <p>{{ __('You start with your passion and knowledge. Then choose a promising topic and shape it into a course learners can follow.') }}</p>
                    <p>{{ __('The way that you teach, and what you bring to it, is up to you.') }}</p>
                    <h3>{{ __('How we help you') }}</h3>
                    <p>{{ __('We offer resources on how to create your first course, and the instructor dashboard helps keep your curriculum organized.') }}</p>
                </div>
                <div class="instructor-landing__begin-image">
                    <img src="{{ asset('frontend/img/banner/banner_img.png') }}" alt="{{ __('Plan your curriculum') }}">
                </div>
            </div>
        </div>

        <div class="instructor-landing__support">
            <div class="instructor-landing__support-inner">
                <h2>{{ __('You will not have to do it alone') }}</h2>
                <p>{{ __('Our support team can answer questions and review your details, while learning resources and community support help you through the process.') }}</p>
            </div>
        </div>

        <div class="instructor-landing__cta">
            <h2>{{ __('Become an instructor today') }}</h2>
            <p>{{ __("Join one of the world's largest online learning marketplaces.") }}</p>
            <button type="button" class="instructor-landing__btn" data-start-onboarding>{{ __('Get started') }}</button>
        </div>
    </section>

    <section class="instructor-onboarding" data-onboarding>
        <div class="instructor-onboarding__topbar">
            <a href="{{ route('home') }}" class="instructor-onboarding__brand">
                <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name }}">
            </a>
            <div class="instructor-onboarding__step-label" data-step-label>{{ __('Step 1 of 4') }}</div>
            <a href="{{ route('home') }}" class="instructor-onboarding__exit">{{ __('Exit') }}</a>
        </div>
        <div class="instructor-onboarding__progress"><span data-progress></span></div>

        <form method="POST" action="{{ route('become-instructor.create') }}" class="account__form" enctype="multipart/form-data" data-onboarding-form>
            @csrf

            <div class="instructor-onboarding__content">
                @foreach ($steps as $index => $step)
                    <div class="instructor-onboarding__step {{ $index === 0 ? 'active' : '' }}" data-step="{{ $index + 1 }}">
                        <p class="instructor-onboarding__intro">{{ $step['intro'] }}</p>
                        <div class="instructor-onboarding__grid">
                            <div>
                                <h2 class="instructor-onboarding__question">{{ $step['title'] }}</h2>
                                <div class="instructor-onboarding__options">
                                    @foreach ($step['options'] as $value => $label)
                                        <label class="instructor-onboarding__option">
                                            <input type="radio" name="{{ $step['name'] }}" value="{{ $value }}" @checked(old($step['name']) === $value) required>
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="instructor-onboarding__visual">
                                <img src="{{ $step['image'] }}" alt="">
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="instructor-onboarding__step" data-step="4">
                    <div class="instructor-onboarding__application">
                        <div class="singUp-wrap">
                            <h2 class="title">{{ __('Become Instructor') }}</h2>
                            <div class="normal-text">
                                {!! clean($instructorRequestSetting?->instructions) !!}
                            </div>

                            @if ($instructorRequestSetting?->need_certificate == 1)
                                <div class="from-group mb-3">
                                    <label>{{ __('Certificate and documents') }} <code>*</code></label>
                                    <input type="file" class="form-control" name="certificate">
                                </div>
                            @endif

                            @if ($instructorRequestSetting?->need_identity_scan == 1)
                                <div class="from-group mb-3">
                                    <label>{{ __('Identity scan') }} <code>*</code></label>
                                    <input type="file" class="form-control" name="identity_scan">
                                </div>
                            @endif

                            <div class="form-grp">
                                <label for="payout_account">{{ __('Payout Account') }} <code>*</code></label>
                                <select name="payout_account" id="payout_account" class="form-select">
                                    <option value="" @selected(!old('payout_account'))>{{ __('Select') }}</option>
                                    @foreach ($withdrawMethods as $method)
                                        <option value="{{ $method->name }}" @selected(old('payout_account') === $method->name)>{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-grp">
                                <label for="phone_number">{{ __('Phone Number') }} <code>*</code></label>
                                <input type="text" class="form-control" name="phone_number" id="phone_number" value="{{ old('phone_number', userAuth()?->phone) }}" placeholder="{{ __('Phone Number') }}">
                            </div>

                            <div class="form-grp">
                                <label for="extra_information">{{ __('Extra Information') }}</label>
                                <textarea name="extra_information" placeholder="{{ __('Extra Information') }}" id="extra_information">{{ old('extra_information') }}</textarea>
                            </div>

                            @if (Cache::get('setting')->recaptcha_status === 'active')
                                <div class="form-grp mt-3">
                                    <div class="g-recaptcha" data-sitekey="{{ Cache::get('setting')->recaptcha_site_key }}"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="instructor-onboarding__actions">
                <button type="button" class="instructor-onboarding__btn" data-previous>{{ __('Previous') }}</button>
                <button type="button" class="instructor-onboarding__btn instructor-onboarding__btn--primary" data-continue disabled>{{ __('Continue') }}</button>
                <button type="submit" class="instructor-onboarding__btn instructor-onboarding__btn--primary instructor-onboarding__submit" data-submit>
                    {{ userAuth()->role == 'instructor' ? __('Submit') : __('Submit for Review') }}
                </button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        "use strict";

        $(function () {
            const $root = $('[data-onboarding]');
            const totalSteps = 4;
            let currentStep = 1;

            const openOnboarding = function (step = 1) {
                $('body').addClass('instructor-onboarding-active');
                showStep(step);
            };

            const showStep = function (step) {
                currentStep = Math.max(1, Math.min(step, totalSteps));
                $root.find('[data-step]').removeClass('active');
                $root.find(`[data-step="${currentStep}"]`).addClass('active');
                $root.find('[data-step-label]').text(`{{ __('Step') }} ${currentStep} {{ __('of') }} ${totalSteps}`);
                $root.find('[data-progress]').css('width', `${(currentStep / totalSteps) * 100}%`);
                $root.find('[data-previous]').toggle(currentStep > 1);
                $root.find('[data-continue]').toggle(currentStep < totalSteps);
                $root.find('[data-submit]').toggleClass('active', currentStep === totalSteps);
                updateContinueState();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            const updateContinueState = function () {
                const $activeStep = $root.find(`[data-step="${currentStep}"]`);
                const $requiredChoice = $activeStep.find('input[type="radio"][required]');
                const canContinue = !$requiredChoice.length || $requiredChoice.is(':checked');
                $root.find('[data-continue]').prop('disabled', !canContinue);
            };

            $root.on('change', 'input[type="radio"]', updateContinueState);
            $root.find('[data-continue]').on('click', function () {
                if (!$(this).prop('disabled')) {
                    showStep(currentStep + 1);
                }
            });
            $root.find('[data-previous]').on('click', function () {
                showStep(currentStep - 1);
            });
            $('[data-start-onboarding]').on('click', function () {
                openOnboarding(1);
            });

            if ($root.find('.is-invalid, .invalid-feedback, .text-danger').length || @json($errors->any())) {
                openOnboarding(4);
                return;
            }

            showStep(1);
            $('#payout_account').trigger('change');
        });
    </script>
@endpush
