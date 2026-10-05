@php
    $showDashboardProfile = auth()->check() && request()->routeIs('instructor.*');
@endphp

@if ($categoryStripCategories->isNotEmpty() || $showDashboardProfile)
    <div class="site-header__menu-strip site-header__menu-strip--desktop d-none d-xl-block">
        <div class="site-header__menu-strip-inner">
            <div class="site-header__menu-strip-scroll">
                @foreach ($categoryStripCategories as $index => $category)
                    @php
                        $menuIcons = [
                            'pre-primary' => 'fa-child',
                            'lower-primary' => 'fa-school',
                            'upper-primary' => 'fa-user-graduate',
                            'junior-school' => 'fa-book-reader',
                            'senior-school-cbc' => 'fa-graduation-cap',
                            'high-school' => 'fa-book',
                            'tvet' => 'fa-wrench',
                            'certificate-courses' => 'fa-certificate',
                            'diploma-courses' => 'fa-file-alt',
                            'undergraduate' => 'fa-university',
                            'professional-courses' => 'fa-award',
                            'teacher-resources' => 'fa-book-open',
                        ];
                        $menuColors = ['#f97316', '#16a34a', '#0ea5e9', '#7c3aed', '#ef4444', '#0891b2', '#ca8a04'];
                        $categorySlug = (string) data_get($category, 'slug', '');
                        $categoryHref = (string) data_get($category, 'href', route('courses'));
                        $categoryName = (string) data_get($category, 'name', $categorySlug);
                        $menuIcon = $menuIcons[$categorySlug] ?? 'fa-folder-open';
                        $menuColor = $menuColors[$index % count($menuColors)];
                    @endphp
                    <a class="site-header__menu-item" href="{{ $categoryHref }}" style="--menu-accent: {{ $menuColor }};">
                        <span class="site-header__menu-icon">
                            <i class="fas {{ $menuIcon }}"></i>
                        </span>
                        <span class="site-header__menu-text">{{ $categoryName }}</span>
                    </a>
                @endforeach

                @if ($showDashboardProfile)
                    <div class="site-header__menu-strip-profile">
                        @include('frontend.partials.dashboard-profile-menu', [
                            'name' => userAuth()->name,
                            'role' => userAuth()->role,
                            'image' => userAuth()->image,
                            'profileUrl' => route('instructor.setting.index'),
                            'logoutFormId' => 'instructor-logout-form',
                        ])
                    </div>
                @endif
            </div>

            @if ($categoryStripOverflow->isNotEmpty())
                <div class="site-header__menu-more">
                    <button type="button" class="site-header__menu-more-trigger" aria-haspopup="true" aria-expanded="false">
                        <span class="site-header__menu-more-grid" aria-hidden="true">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </span>
                        <span>{{ __('More') }}</span>
                        <i class="fas fa-chevron-down site-header__menu-more-caret"></i>
                    </button>
                    <div class="site-header__menu-more-panel">
                        @foreach ($categoryStripOverflow as $category)
                            <a class="site-header__menu-more-link" href="{{ data_get($category, 'href', route('courses')) }}">
                                {{ data_get($category, 'name', data_get($category, 'slug', '')) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="site-header__menu-strip site-header__menu-strip--mobile d-xl-none">
        <div class="site-header__menu-strip-mobilebar">
            <button type="button"
                class="site-header__menu-strip-mobiletrigger"
                aria-expanded="false"
                aria-controls="site-header-menu-strip-mobile-panel">
                <span class="site-header__menu-strip-mobileicon" aria-hidden="true">
                    <i class="fas fa-bars"></i>
                </span>
                <span>{{ __('Menu') }}</span>
            </button>
        </div>

        <div class="site-header__menu-strip-mobilepanel" id="site-header-menu-strip-mobile-panel">
            <div class="site-header__menu-strip-mobilepanel-inner">
                @foreach ($categoryStripCategories as $index => $category)
                    @php
                        $menuIcons = [
                            'pre-primary' => 'fa-child',
                            'lower-primary' => 'fa-school',
                            'upper-primary' => 'fa-user-graduate',
                            'junior-school' => 'fa-book-reader',
                            'senior-school-cbc' => 'fa-graduation-cap',
                            'high-school' => 'fa-book',
                            'tvet' => 'fa-wrench',
                            'certificate-courses' => 'fa-certificate',
                            'diploma-courses' => 'fa-file-alt',
                            'undergraduate' => 'fa-university',
                            'professional-courses' => 'fa-award',
                            'teacher-resources' => 'fa-book-open',
                        ];
                        $menuColors = ['#f97316', '#16a34a', '#0ea5e9', '#7c3aed', '#ef4444', '#0891b2', '#ca8a04'];
                        $categorySlug = (string) data_get($category, 'slug', '');
                        $categoryHref = (string) data_get($category, 'href', route('courses'));
                        $categoryName = (string) data_get($category, 'name', $categorySlug);
                        $menuIcon = $menuIcons[$categorySlug] ?? 'fa-folder-open';
                        $menuColor = $menuColors[$index % count($menuColors)];
                    @endphp
                    <a class="site-header__menu-strip-mobilelink" href="{{ $categoryHref }}" style="--menu-accent: {{ $menuColor }};">
                        <span class="site-header__menu-icon">
                            <i class="fas {{ $menuIcon }}"></i>
                        </span>
                        <span class="site-header__menu-text">{{ $categoryName }}</span>
                    </a>
                @endforeach

                @if ($showDashboardProfile)
                    <div class="site-header__menu-strip-mobileprofile">
                        @include('frontend.partials.dashboard-profile-menu', [
                            'name' => userAuth()->name,
                            'role' => userAuth()->role,
                            'image' => userAuth()->image,
                            'profileUrl' => route('instructor.setting.index'),
                            'logoutFormId' => 'instructor-logout-form',
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
