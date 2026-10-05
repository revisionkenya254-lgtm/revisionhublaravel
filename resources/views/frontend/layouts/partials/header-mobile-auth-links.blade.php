<ul class="mobile_menu_login d-flex flex-wrap">
    @if ($isAdminLoggedIn)
        <li><a href="{{ route('admin.dashboard') }}">{{ __('Admin Dashboard') }}</a></li>
    @endif
    @guest
        <li><a href="{{ route('login') }}">{{ __('login') }}</a></li>
        <li><a href="{{ route('register') }}">{{ __('register') }}</a></li>
    @endguest

    @auth('web')
        @php
            $dashboardRoute = isInstructorAccount() ? 'instructor.dashboard' : 'student.dashboard';
            $coursesRoute = isInstructorAccount()
                ? 'instructor.courses.index'
                : 'student.enrolled-courses';
            $badgeState = userAccountBadgeState();
        @endphp
        @if (in_array($badgeState, ['instructor', 'pending-instructor', 'student'], true))
            <li>
                <span class="mobile-role-badge {{ userAccountBadgeClass() }}">
                    <i class="fas {{ userAccountBadgeIcon() }}"></i>
                    {{ userAccountBadgeLabel() }}
                </span>
            </li>
        @endif
        <li><a href="{{ route($dashboardRoute) }}">{{ __('Dashboard') }}</a></li>
        <li><a href="{{ route($coursesRoute) }}">{{ __('Courses') }}</a></li>
        @if ($badgeState === 'pending-instructor')
            <li><a href="{{ route('become-instructor.review') }}">{{ __('Instructor status') }}</a></li>
        @elseif ($badgeState === 'student' && canSeeBecomeInstructorLink() && \Illuminate\Support\Facades\Route::has('become-instructor'))
            <li><a href="{{ route('become-instructor') }}">{{ __('Become Instructor') }}</a></li>
        @endif
    @endauth
</ul>
