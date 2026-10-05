@php
    $layout = $layout ?? 'home';
@endphp

@if ($layout === 'header-menu')
    <li><a href="{{ route('become-instructor') }}">{{ __('Become Instructor') }}</a></li>
    <li><a href="{{ route('login') }}">{{ __('Sign in') }}</a></li>
    <li><a href="{{ route('register') }}">{{ __('Sign Up') }}</a></li>
@else
    <div class="revision-nav__actions">
        <a href="{{ route('login') }}" class="revision-btn revision-btn--ghost">{{ __('Login') }}</a>
        <a href="{{ route('register') }}" class="revision-btn revision-btn--primary">{{ __('Sign Up') }}</a>
    </div>
@endif
