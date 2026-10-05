@extends('frontend.layouts.master')

<!-- meta -->
@section('meta_title', __('Student Dashboard'))
<!-- end meta -->

@section('contents')
    @php
        $hasActiveSubscription = auth()->check() && hasActiveSubscription();
        $subscriptionExpiryLabel = $hasActiveSubscription && auth()->user()?->subscription_expires_at
            ? formatDate(auth()->user()->subscription_expires_at)
            : null;
    @endphp
    <!-- breadcrumb-area -->
    <x-frontend.breadcrumb
        :title="__('')"
        :links="[]"
    />
    <!-- breadcrumb-area-end -->

    <!-- dashboard-area -->
    <section class="dashboard__area section-pb-120">
        <div class="container">
            <div class="dashboard__top-wrap">
                <div class="dashboard__top-bg" data-background="{{ asset(auth()->user()->cover) }}"></div>
                <div class="dashboard__instructor-info">
                    <div class="dashboard__instructor-info-left">
                        <div class="thumb">
                            <img src="{{ asset(auth()->user()->image) }}" alt="img">
                        </div>
                        <div class="content">
                            <h4 class="title">{{ auth()->user()->name }}</h4>
                            <ul class="list-wrap">
                                <li>
                                    <img src="{{ asset('frontend/img/icons/envelope.svg') }}" alt="img" class="injectable">
                                    {{ auth()->user()->email }}
                                </li>
                                @if(auth()->user()->phone)
                                <li>
                                    <img  src="{{ asset('frontend/img/icons/phone.svg') }}" alt="img" class="injectable">
                                    {{ auth()->user()->phone }}
                                </li>
                                @endif
                                
                            </ul>
                        </div>
                    </div>
                    <div class="dashboard__instructor-info-right">
                        @if ($hasActiveSubscription)
                            <span class="dashboard__subscription-badge" title="{{ __('Expires on') }} {{ $subscriptionExpiryLabel }}">
                                <i class="fas fa-crown"></i>
                                <span>{{ __('Subscription active') }}</span>
                                <small>{{ __('until') }} {{ $subscriptionExpiryLabel }}</small>
                            </span>
                        @endif
                        @if (isInstructorAccount())
                        <a href="{{ route('instructor.dashboard') }}" class="btn btn-two arrow-btn">{{ __('Instructor Dashboard') }} <img
                            src="{{ asset('frontend/img/icons/right_arrow.svg') }}" alt="img" class="injectable"></a>
                        @elseif (canSeeBecomeInstructorLink())
                        <a href="{{ route(instructorEntryRouteName()) }}" class="btn btn-two arrow-btn">{{ __('Become an Instructor') }} <img
                            src="{{ asset('frontend/img/icons/right_arrow.svg') }}" alt="img" class="injectable"></a>
                        @endif
                        @include('frontend.partials.dashboard-profile-menu', [
                            'name' => auth()->user()->name,
                            'role' => auth()->user()->role,
                            'image' => auth()->user()->image,
                            'profileUrl' => route('student.setting.index'),
                            'logoutFormId' => 'student-logout-form',
                        ])
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3">
                    @hasSection('dashboard-sidebar')
                        @yield('dashboard-sidebar')
                    @else
                        @include('frontend.student-dashboard.layouts.sidebar')
                    @endif
                </div>
                <div class="col-lg-9">
                    @yield('dashboard-contents')
                </div>
            </div>
        </div>
    </section>
    <!-- dashboard-area-end -->
@endsection

@push('styles')
    <style>
        .dashboard__instructor-info-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .dashboard__subscription-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(34, 197, 94, 0.18));
            border: 1px solid rgba(16, 185, 129, 0.22);
            color: #0f7a43;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .dashboard__subscription-badge i {
            color: #1aa05b;
            font-size: 14px;
        }

        .dashboard__subscription-badge small {
            font-size: 11px;
            font-weight: 600;
            color: #0f7a43;
        }

        @media (max-width: 767.98px) {
            .dashboard__instructor-info-right {
                justify-content: flex-start;
            }

            .dashboard__subscription-badge small {
                display: none;
            }
        }
    </style>
@endpush
