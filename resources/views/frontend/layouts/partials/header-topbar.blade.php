<div class="tg-header__top revision-header__topbar">
    <div class="container-fluid revision-header__frame">
        <div class="row">
            <div class="col-lg-6">
                <ul class="tg-header__top-info list-wrap">
                    @if ($setting?->site_address)
                        <li><img src="{{ asset('frontend/img/icons/map_marker.svg') }}" alt="Icon">
                            <span>{{ $setting?->site_address }}</span>
                        </li>
                    @endif
                    @if ($setting?->site_email)
                        <li><img src="{{ asset('frontend/img/icons/envelope.svg') }}" alt="Icon"> <a
                                href="mailto:{{ $setting?->site_email }}">{{ $setting?->site_email }}</a>
                        </li>
                    @endif
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="tg-header__top-right">
                    @if (\Illuminate\Support\Facades\Route::has('ai-chat'))
                        <a class="site-header__top-link {{ request()->routeIs('ai-chat', 'student.ai-chat.*') ? 'active' : '' }}"
                            href="{{ route('ai-chat') }}">
                            <i class="fas fa-robot"></i>
                            <span>{{ __('AI Chat') }}</span>
                        </a>
                    @endif
                    @auth('web')
                        @if (isInstructorAccount())
                            <a class="site-header__top-link site-header__top-link--success {{ request()->routeIs('instructor.*') ? 'active' : '' }}"
                                href="{{ route('instructor.dashboard') }}">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ __('Instructor Dashboard') }}</span>
                            </a>
                        @elseif (instructorStatus() === \App\Enums\UserStatus::PENDING->value)
                            <a class="site-header__top-link site-header__top-link--warning {{ request()->routeIs('become-instructor.review') ? 'active' : '' }}"
                                href="{{ route('become-instructor.review') }}">
                                <i class="fas fa-hourglass-half"></i>
                                <span>{{ __('Pending instructor') }}</span>
                            </a>
                        @elseif (canSeeBecomeInstructorLink() && \Illuminate\Support\Facades\Route::has('become-instructor'))
                            <a class="site-header__top-link site-header__top-link--primary {{ request()->routeIs('become-instructor') ? 'active' : '' }}"
                                href="{{ route('become-instructor') }}">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>{{ __('Become instructor') }}</span>
                            </a>
                        @endif
                    @else
                        @if (\Illuminate\Support\Facades\Route::has('become-instructor'))
                            <a class="site-header__top-link site-header__top-link--primary {{ request()->routeIs('become-instructor') ? 'active' : '' }}"
                                href="{{ route('become-instructor') }}">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>{{ __('Become instructor') }}</span>
                            </a>
                        @endif
                    @endauth
                    @if ($setting?->header_social_status == 'active')
                        <ul class="tg-header__top-social list-wrap">
                            <li>{{ __('Follow Us On') }} :</li>
                            @foreach (getSocialLinks() as $socialLink)
                                <li class="header-social">
                                    <a href="{{ $socialLink->link }}" target="_blank">
                                        <img src="{{ asset($socialLink->icon) }}" alt="img">
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="header_language_area d-flex flex-wrap d-none d-xl-flex">
                        <ul>
                            <li>
                                @if (count(allLanguages()?->where('status', 1)) > 1)
                                    <form action="{{ route('set-language') }}" id="setLanguageHeader">
                                        <select name="code" class="select_js">
                                            @forelse (allLanguages()?->where('status', 1) as $language)
                                                <option value="{{ $language->code }}"
                                                    {{ getSessionLanguage() == $language->code ? 'selected' : '' }}>
                                                    {{ $language->name }}
                                                </option>
                                            @empty
                                                <option value="en"
                                                    {{ getSessionLanguage() == 'en' ? 'selected' : '' }}>
                                                    {{ __('English') }}
                                                </option>
                                            @endforelse
                                        </select>
                                    </form>
                                @endif
                            </li>
                            <li>
                                @if (count(allCurrencies()?->where('status', 'active')) > 1)
                                    <form action="{{ route('set-currency') }}" class="set-currency-header" method="GET">
                                        <select name="currency" class="change-currency select_js">
                                            @forelse (allCurrencies()?->where('status', 'active') as $currency)
                                                <option value="{{ $currency->currency_code }}"
                                                    {{ getSessionCurrency() == $currency->currency_code ? 'selected' : '' }}>
                                                    {{ $currency->currency_name }}
                                                </option>
                                            @empty
                                                <option value="KES"
                                                    {{ getSessionCurrency() == 'KES' ? 'selected' : '' }}>
                                                    {{ __('KES') }}
                                                </option>
                                            @endforelse
                                        </select>
                                    </form>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .revision-header__topbar {
            position: relative;
            width: 100%;
            z-index: 10;
            overflow: hidden;
            max-height: 220px;
            opacity: 1;
            transform: translateY(0);
            transition: max-height 0.28s ease, opacity 0.28s ease, transform 0.28s ease;
            will-change: max-height, opacity, transform;
        }

        .site-header__top-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            margin-right: 16px;
            padding: 0 12px;
            border-radius: 999px;
            background: rgba(86, 36, 208, 0.08);
            color: #5624d0;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .site-header__top-link--primary {
            background: rgba(86, 36, 208, 0.08);
            color: #5624d0;
        }

        .site-header__top-link--success {
            background: rgba(20, 132, 76, 0.08);
            color: #147a47;
        }

        .site-header__top-link--warning {
            background: rgba(217, 119, 6, 0.08);
            color: #b45309;
        }

        .site-header__top-link:hover,
        .site-header__top-link.active {
            background: rgba(86, 36, 208, 0.14);
            color: #3f1aa8;
        }

        .site-header__top-link--success:hover,
        .site-header__top-link--success.active {
            background: rgba(20, 132, 76, 0.14);
            color: #0f7a43;
        }

        .site-header__top-link--warning:hover,
        .site-header__top-link--warning.active {
            background: rgba(217, 119, 6, 0.14);
            color: #92400e;
        }

        .site-header__top-link i {
            font-size: 13px;
        }

        @media (max-width: 1199.98px) {
            .site-header__top-link {
                margin-right: 12px;
            }
        }
    </style>
@endpush
