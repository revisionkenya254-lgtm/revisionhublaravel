<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>@yield('meta_title', $setting->app_name)</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', '')">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Custom Meta -->
    @stack('custom_meta')
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset($setting->favicon) }}">
    <!-- CSS here -->
    @include('frontend.layouts.styles')
    @if (cache()->get('customCode')?->css)
        <style>
            {!! cache()->get('customCode')?->css !!}
        </style>
    @endif

    {{-- dynamic header scripts --}}
    @include('frontend.layouts.header-scripts')

    @php
        setEnrollmentIdsInSession();
        setInstructorCourseIdsInSession();
        $theme_name = session()->has('demo_theme') ? session()->get('demo_theme') : DEFAULT_HOMEPAGE;
    @endphp
</head>
<body class="{{ isRoute('home', "home_{$theme_name}") }} @yield('body_class', '')">
    @if ($setting->google_tagmanager_status == 'active')
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $setting->google_tagmanager_id }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif

    @if ($setting->preloader_status == 1)
        <!--Preloader-->
        <div id="preloader">
            <div id="loader" class="loader">
                <div class="loader-container">
                    <div class="loader-icon"><img src="{{ asset($setting->preloader) }}" alt="Preloader">
                    </div>
                </div>
            </div>
        </div>
        <!--Preloader-end -->
    @endif

    @php
        $isAiChatPage = request()->routeIs('ai-chat') || request()->routeIs('student.ai-chat.*');
    @endphp

    @unless ($isAiChatPage)
        <!-- Scroll-top -->
        <button class="scroll__top scroll-to-target" data-target="html" aria-label="Scroll Top">
            <i class="tg-flaticon-arrowhead-up"></i>
        </button>
        <!-- Scroll-top-end-->

        <!-- header-area -->
        @include('frontend.layouts.header')
        <!-- header-area-end -->
    @endunless
    <!-- CustomCSS here -->
    @stack('styles')

    <!-- main-area -->
    <main class="main-area fix">
        @yield('contents')
    </main>
    <!-- main-area-end -->

    <!-- modal-area -->
    @include('frontend.partials.modal')
    @include('frontend.instructor-dashboard.course.partials.add-new-section-modal')
    <!-- modal-area -->

    @unless ($isAiChatPage)
        <!-- footer-area -->
        @include('frontend.layouts.footer')
        <!-- footer-area-end -->
    @endunless


    <!-- JS here -->
    @include('frontend.layouts.scripts')

    <!-- Language Translation Variables -->
    @include('global.dynamic-js-variables')

    <!-- Page specific js -->
    @if (session('registerUser') && $setting->google_tagmanager_status == 'active' && $marketing_setting?->register)
        @php
            $registerUser = session('registerUser');
            session()->forget('registerUser');
        @endphp
        <script>
            $(function() {
                dataLayer.push({
                    'event': 'newStudent',
                    'student_info': @json($registerUser)
                });
            });
        </script>
    @endif
    @stack('scripts')
    @if (cache()->get('customCode')?->javascript)
        <script>
            "use strict";
            {!! cache()->get('customCode')?->javascript !!}
        </script>
    @endif
</body>

</html>
