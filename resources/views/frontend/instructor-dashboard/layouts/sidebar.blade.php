@use('Nwidart\Modules\Facades\Module')
@php
    $currentType = Route::is('instructor.quizzes.*')
        ? 'quiz'
        : (request('type')
            ?? ($selectedType ?? null)
            ?? old('type')
            ?? (isset($product) ? $product->type : null));

    $isPastPaperOpen = Route::is('instructor.products.*') && $currentType === 'past_paper';
    $isPredictionOpen = Route::is('instructor.products.*') && $currentType === 'prediction';
    $isNoteOpen = Route::is('instructor.products.*') && $currentType === 'note';
    $isQuizOpen = (Route::is('instructor.products.*') || Route::is('instructor.quizzes.*')) && $currentType === 'quiz';
    $isCourseOpen = Route::is('instructor.courses.*');
    $isAiDocumentsOpen = Route::is('instructor.ai-documents.*');
@endphp

<div class="dashboard-sidebar-shell">
    <div class="dashboard-sidebar-shell__brand">
        <a href="{{ route('home') }}" class="dashboard-brand" aria-label="{{ __('Go to home') }}">
            <span class="dashboard-brand__mark">
                <img src="{{ asset($setting?->logo) }}" alt="{{ $setting?->app_name ?? 'RevisionHub' }}">
            </span>
            <span class="dashboard-brand__text">
                <strong>{{ $setting?->app_name ?? 'RevisionHub' }}</strong>
                <small>{{ __('Kenya') }}</small>
            </span>
        </a>
    </div>

    <div class="dashboard-sidebar-shell__welcome">
        <span>{{ __('Welcome back') }}</span>
        <strong>{{ userAuth()->name }}</strong>
    </div>

    <div class="dashboard-sidebar-shell__section">
        <p>{{ __('Dashboard') }}</p>
        <nav class="dashboard-nav">
            <ul class="list-wrap">
                <li class="{{ Route::is('instructor.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('instructor.dashboard') }}">
                        <i class="fas fa-home"></i>
                        <span>{{ __('Dashboard') }}</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="dashboard-sidebar-shell__section">
        <p>{{ __('Products') }}</p>
        <nav class="dashboard-nav">
            <ul class="list-wrap">
                <li class="dashboard-nav__group {{ $isCourseOpen ? 'active open' : '' }}">
                    <button type="button" class="dashboard-nav__group-trigger" data-dashboard-group-toggle aria-expanded="{{ $isCourseOpen ? 'true' : 'false' }}">
                        <span class="dashboard-nav__trigger-left">
                            <i class="fas fa-video"></i>
                            <span>{{ __('Courses') }}</span>
                        </span>
                        <span class="dashboard-nav__chevron"><i class="fas fa-chevron-{{ $isCourseOpen ? 'up' : 'down' }}"></i></span>
                    </button>
                    <ul class="dashboard-nav__submenu">
                        <li class="{{ $isCourseOpen && Route::is('instructor.courses.index') ? 'active' : '' }}">
                            <a href="{{ route('instructor.courses.index') }}">{{ __('All Courses') }}</a>
                        </li>
                        <li class="{{ $isCourseOpen && Route::is('instructor.courses.create') ? 'active' : '' }}">
                            <a href="{{ route('instructor.courses.create') }}">{{ __('Create Course') }}</a>
                        </li>
                    </ul>
                </li>
                <li class="dashboard-nav__group {{ $isPastPaperOpen ? 'active open' : '' }}">
                    <button type="button" class="dashboard-nav__group-trigger" data-dashboard-group-toggle>
                        <span class="dashboard-nav__trigger-left">
                            <i class="fas fa-file-alt"></i>
                            <span>{{ __('Past Papers') }}</span>
                        </span>
                        <span class="dashboard-nav__chevron"><i class="fas fa-chevron-{{ $isPastPaperOpen ? 'up' : 'down' }}"></i></span>
                    </button>
                    <ul class="dashboard-nav__submenu">
                        <li class="{{ $isPastPaperOpen && Route::is('instructor.products.index') ? 'active' : '' }}">
                            <a href="{{ route('instructor.products.index', ['type' => 'past_paper']) }}">{{ __('All Past Papers') }}</a>
                        </li>
                        <li class="{{ $isPastPaperOpen && Route::is('instructor.products.create') ? 'active' : '' }}">
                            <a href="{{ route('instructor.products.create', ['type' => 'past_paper']) }}">{{ __('Add New Past Paper') }}</a>
                        </li>
                    </ul>
                </li>
                <li class="dashboard-nav__group {{ $isPredictionOpen ? 'active open' : '' }}">
                    <button type="button" class="dashboard-nav__group-trigger" data-dashboard-group-toggle aria-expanded="{{ $isPredictionOpen ? 'true' : 'false' }}">
                        <span class="dashboard-nav__trigger-left">
                            <i class="fas fa-lightbulb"></i>
                            <span>{{ __('Predictions') }}</span>
                        </span>
                        <span class="dashboard-nav__chevron"><i class="fas fa-chevron-{{ $isPredictionOpen ? 'up' : 'down' }}"></i></span>
                    </button>
                    <ul class="dashboard-nav__submenu">
                        <li class="{{ $isPredictionOpen && Route::is('instructor.products.index') ? 'active' : '' }}">
                            <a href="{{ route('instructor.products.index', ['type' => 'prediction']) }}">{{ __('All Predictions') }}</a>
                        </li>
                        <li class="{{ $isPredictionOpen && Route::is('instructor.products.create') ? 'active' : '' }}">
                            <a href="{{ route('instructor.products.create', ['type' => 'prediction']) }}">{{ __('Add New Prediction') }}</a>
                        </li>
                    </ul>
                </li>
                <li class="dashboard-nav__group {{ $isNoteOpen ? 'active open' : '' }}">
                    <button type="button" class="dashboard-nav__group-trigger" data-dashboard-group-toggle aria-expanded="{{ $isNoteOpen ? 'true' : 'false' }}">
                        <span class="dashboard-nav__trigger-left">
                            <i class="far fa-file-alt"></i>
                            <span>{{ __('Notes') }}</span>
                        </span>
                        <span class="dashboard-nav__chevron"><i class="fas fa-chevron-{{ $isNoteOpen ? 'up' : 'down' }}"></i></span>
                    </button>
                    <ul class="dashboard-nav__submenu">
                        <li class="{{ $isNoteOpen && Route::is('instructor.products.index') ? 'active' : '' }}">
                            <a href="{{ route('instructor.products.index', ['type' => 'note']) }}">{{ __('All Notes') }}</a>
                        </li>
                        <li class="{{ $isNoteOpen && Route::is('instructor.products.create') ? 'active' : '' }}">
                            <a href="{{ route('instructor.products.create', ['type' => 'note']) }}">{{ __('Add New Note') }}</a>
                        </li>
                    </ul>
                </li>
                <li class="dashboard-nav__group {{ $isQuizOpen ? 'active open' : '' }}">
                    <button type="button" class="dashboard-nav__group-trigger" data-dashboard-group-toggle aria-expanded="{{ $isQuizOpen ? 'true' : 'false' }}">
                        <span class="dashboard-nav__trigger-left">
                            <i class="fas fa-question-circle"></i>
                            <span>{{ __('Quizzes') }}</span>
                        </span>
                        <span class="dashboard-nav__chevron"><i class="fas fa-chevron-{{ $isQuizOpen ? 'up' : 'down' }}"></i></span>
                    </button>
                    <ul class="dashboard-nav__submenu">
                        <li class="{{ $isQuizOpen && Route::is('instructor.products.index') ? 'active' : '' }}">
                            <a href="{{ route('instructor.products.index', ['type' => 'quiz']) }}">{{ __('All Quizzes') }}</a>
                        </li>
                        <li class="{{ $isQuizOpen && Route::is('instructor.products.create') ? 'active' : '' }}">
                            <a href="{{ route('instructor.products.create', ['type' => 'quiz']) }}">{{ __('Add New Quiz') }}</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>

    <div class="dashboard-sidebar-shell__section">
        <p>{{ __('Orders') }}</p>
        <nav class="dashboard-nav">
            <ul class="list-wrap">
                <li class="{{ Route::is('instructor.my-sells.index') ? 'active' : '' }}">
                    <a href="{{ route('instructor.my-sells.index') }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>{{ __('Orders') }}</span>
                    </a>
                </li>
                <li class="{{ Route::is('instructor.payout.*') ? 'active' : '' }}">
                    <a href="{{ route('instructor.payout.index') }}">
                        <i class="fas fa-exchange-alt"></i>
                        <span>{{ __('Transactions') }}</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="dashboard-sidebar-shell__section">
        <p>{{ __('Users') }}</p>
        <nav class="dashboard-nav">
            <ul class="list-wrap">
                <li class="{{ Route::is('instructor.lesson-questions.*') ? 'active' : '' }}">
                    <a href="{{ route('instructor.lesson-questions.index') }}">
                        <i class="far fa-user"></i>
                        <span>{{ __('Students') }}</span>
                    </a>
                </li>
                <li class="{{ Route::is('instructor.announcements.*') ? 'active' : '' }}">
                    <a href="{{ route('instructor.announcements.index') }}">
                        <i class="fas fa-user-cog"></i>
                        <span>{{ __('Instructors') }}</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="dashboard-sidebar-shell__section">
        <p>{{ __('Reports') }}</p>
        <nav class="dashboard-nav">
            <ul class="list-wrap">
                <li class="{{ Route::is('instructor.my-sells.index') ? 'active' : '' }}">
                    <a href="{{ route('instructor.my-sells.index') }}">
                        <i class="fas fa-chart-bar"></i>
                        <span>{{ __('Sales Report') }}</span>
                    </a>
                </li>
                <li class="{{ Route::is('instructor.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('instructor.dashboard') }}">
                        <i class="fas fa-chart-pie"></i>
                        <span>{{ __('Analytics') }}</span>
                    </a>
                </li>
                <li class="{{ $isAiDocumentsOpen ? 'active' : '' }}">
                    <a href="{{ route('instructor.ai-documents.index') }}">
                        <i class="fas fa-file-pdf"></i>
                        <span>{{ __('AI Documents') }}</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="dashboard-sidebar-shell__section">
        <p>{{ __('Settings') }}</p>
        <nav class="dashboard-nav">
            <ul class="list-wrap">
                <li class="{{ Route::is('instructor.setting.index') ? 'active' : '' }}">
                    <a href="{{ route('instructor.setting.index') }}">
                        <i class="far fa-user"></i>
                        <span>{{ __('Profile') }}</span>
                    </a>
                </li>
                <li class="{{ Route::is('instructor.setting.index') ? 'active' : '' }}">
                    <a href="{{ route('instructor.setting.index') }}">
                        <i class="fas fa-cog"></i>
                        <span>{{ __('Account Settings') }}</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="dashboard-sidebar-shell__section dashboard-sidebar-shell__section--spacer">
        <nav class="dashboard-nav">
            <ul class="list-wrap">
            <li>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); $('#instructor-logout-form').trigger('submit');">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>{{ __('Logout') }}</span>
                </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<form id="instructor-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
