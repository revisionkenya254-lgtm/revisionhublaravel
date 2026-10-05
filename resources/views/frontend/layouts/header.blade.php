@php
    $isAdminLoggedIn = auth('admin')->check();
    $userBadgeState = userAccountBadgeState();
    $isInstructorUser = $userBadgeState === 'instructor';
    $isPendingInstructorUser = $userBadgeState === 'pending-instructor';
    $isStudentUser = $userBadgeState === 'student';
    $showHeaderTopbar = $showHeaderTopbar ?? true;
    $showHeaderMainBar = $showHeaderMainBar ?? true;
    $showHeaderMobileMenu = $showHeaderMobileMenu ?? true;
@endphp

<header class="revision-header">
    @includeWhen($showHeaderTopbar && $setting?->header_topbar_status == 'active', 'frontend.layouts.partials.header-topbar')

    @includeWhen($showHeaderMainBar, 'frontend.layouts.partials.header-main')

    @includeWhen($showHeaderMobileMenu, 'frontend.layouts.partials.header-mobile-menu')
</header>
