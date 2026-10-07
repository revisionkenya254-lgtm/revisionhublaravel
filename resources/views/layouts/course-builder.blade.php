<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Course Creator'))</title>
    <link rel="stylesheet" href="{{ asset('frontend/css/course-builder.css') }}?v={{ config('app.asset_version', '1') }}">
    @stack('styles')
</head>
<body class="rh-app">
    @php
        $creatorIsAdmin = $isAdmin ?? auth('admin')->check();
        $creatorUser = $creatorIsAdmin ? auth('admin')->user() : auth('web')->user();
        $dashboardRoute = $creatorIsAdmin ? 'admin.dashboard' : 'instructor.dashboard';
        $coursesRoute = $creatorIsAdmin ? 'admin.courses.index' : 'instructor.courses.index';
        $createCourseRoute = $creatorIsAdmin ? 'admin.courses.create' : 'instructor.courses.create';
        $createLessonRoute = $creatorIsAdmin ? 'admin.lessons.create' : 'instructor.lessons.create';
    @endphp
    <div class="rh-shell" data-creator-shell>
        <aside class="rh-sidebar" data-creator-sidebar>
            <div class="rh-sidebar-head">
                <a class="rh-brand" href="{{ Route::has($dashboardRoute) ? route($dashboardRoute) : url('/') }}">
                    <span class="rh-brand-mark">R</span>
                    <span>RevisionHub<em>Kenya</em></span>
                </a>
                <button class="rh-sidebar-close" type="button" data-sidebar-close aria-label="{{ __('Close navigation') }}">×</button>
            </div>

            <nav class="rh-nav" aria-label="{{ __('Creator navigation') }}">
                <span class="rh-nav-label">{{ __('WORKSPACE') }}</span>
                @if(Route::has($dashboardRoute))
                    <a href="{{ route($dashboardRoute) }}"><span class="rh-nav-icon">⌂</span><span>{{ __('Dashboard') }}</span></a>
                @endif
                @if(Route::has($coursesRoute))
                    <a href="{{ route($coursesRoute) }}"><span class="rh-nav-icon">▤</span><span>{{ $creatorIsAdmin ? __('Courses') : __('My Courses') }}</span></a>
                @endif
                @if(Route::has($createCourseRoute))
                    <a class="{{ Route::is($createCourseRoute) || Route::is($createLessonRoute) ? 'active' : '' }}" href="{{ route($createCourseRoute) }}"><span class="rh-nav-icon">＋</span><span>{{ __('Create Course') }}</span></a>
                @endif

                <span class="rh-nav-label">{{ __('TEACHING') }}</span>
                @if(!$creatorIsAdmin && Route::has('instructor.products.index'))
                    <a href="{{ route('instructor.products.index') }}"><span class="rh-nav-icon">□</span><span>{{ __('Resources') }}</span></a>
                @endif
                @if(!$creatorIsAdmin && Route::has('instructor.lesson-questions.index'))
                    <a href="{{ route('instructor.lesson-questions.index') }}"><span class="rh-nav-icon">?</span><span>{{ __('Lesson Q&A') }}</span></a>
                @endif
                @if($creatorIsAdmin && Route::has('admin.course-category.index'))
                    <a href="{{ route('admin.course-category.index') }}"><span class="rh-nav-icon">◇</span><span>{{ __('Categories') }}</span></a>
                @endif
                @if($creatorIsAdmin && Route::has('admin.course-review.index'))
                    <a href="{{ route('admin.course-review.index') }}"><span class="rh-nav-icon">★</span><span>{{ __('Reviews') }}</span></a>
                @endif
            </nav>

            <div class="rh-sidebar-user">
                <span class="rh-user-avatar">{{ strtoupper(mb_substr($creatorUser?->name ?? 'R', 0, 1)) }}</span>
                <span><strong>{{ $creatorUser?->name ?? __('Creator') }}</strong><small>{{ $creatorIsAdmin ? __('Administrator') : __('Instructor') }}</small></span>
            </div>
        </aside>
        <button class="rh-sidebar-backdrop" type="button" data-sidebar-close aria-label="{{ __('Close navigation') }}"></button>
        <div class="rh-main">
            <header class="rh-mobile-bar"><button type="button" data-sidebar-open aria-label="{{ __('Open navigation') }}">☰</button><span>RevisionHub<em>Kenya</em></span></header>
            @yield('content')
        </div>
    </div>
    @stack('scripts')
    <script src="{{ asset('frontend/js/course-builder-shell.js') }}?v={{ config('app.asset_version', '1') }}" defer></script>
</body>
</html>
