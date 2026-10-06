@php
    $footerSetting = \FooterSetting();
    $footer_menu_one = menu_get_by_slug('footer-col-one');
    $footer_menu_two = menu_get_by_slug('footer-col-two-1PiTN');
    $footer_menu_three = menu_get_by_slug('footer-col-three');
    $footerMenuOneItems = $footer_menu_one->menuItems ?? collect();
    $footerMenuTwoItems = $footer_menu_two->menuItems ?? collect();
    $footerMenuThreeItems = $footer_menu_three->menuItems ?? collect();
@endphp

<footer
    class="footer__area {{ $setting?->site_theme && Route::is('home') == 'theme-two' ? 'footer__area-two' : '' }}">

    <div class="footer__top">
        <div class="container">
            <div class="row">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="footer__widget">
                        <div class="logo mb-35">
                            <a href="{{ route('home') }}"><img src="{{ !empty($footerSetting?->logo) ? asset($footerSetting?->logo) : asset($setting?->logo) }}" alt="img"></a>
                        </div>
                        <div class="footer__content">
                            <p>{{ $footerSetting?->footer_text }}</p>
                            <ul class="list-wrap">
                                <li>{{ $footerSetting?->address }}</li>
                                <li>{{ $footerSetting?->phone }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    @if($footerMenuOneItems->count() > 0)
                    <div class="footer__widget">
                        <h4 class="footer__widget-title">{{ __('Useful Links') }}</h4>
                        <div class="footer__link">
                            <ul class="list-wrap">
                                @foreach ($footerMenuOneItems as $footerMenuOne)
                                    <li><a href="{{ url($footerMenuOne?->link) }}">{{ $footerMenuOne?->translation_label }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                    @if($footerMenuTwoItems->count() > 0)
                    <div class="footer__widget">
                        <h4 class="footer__widget-title">{{ __('Our Company') }}</h4>
                        <div class="footer__link">
                            <ul class="list-wrap">
                                @foreach ($footerMenuTwoItems as $footerMenuTwo)
                                    <li><a href="{{ url($footerMenuTwo?->link) }}">{{ $footerMenuTwo?->translation_label }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="footer__widget">
                        <h4 class="footer__widget-title">{{ __('Get In Touch') }}</h4>
                        <div class="footer__contact-content">
                            <p>{{ $footerSetting?->get_in_touch_text }}</p>
                            <ul class="list-wrap footer__social">
                                @foreach (getSocialLinks() as $socialLink)
                                    <li>
                                        <a href="{{ $socialLink->link }}" target="_blank">
                                            <img src="{{ asset($socialLink->icon) }}" alt="img">
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="app-download">
                            @if ($footerSetting?->google_play_link)
                                <a href="{{ $footerSetting->google_play_link }}"><img
                                        src="{{ asset('frontend/img/others/google-play.svg') }}" alt="img"></a>
                            @endif
                            @if ($footerSetting?->apple_store_link)
                                <a href="{{ $footerSetting->apple_store_link }}"><img
                                        src="{{ asset('frontend/img/others/apple-store.svg') }}" alt="img"></a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer__bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="copy-right-text">
                        @if($setting?->copyright_text)
                        <p>&copy; {{ $setting?->copyright_text }}</p>
                        @endif
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="footer__bottom-menu">
                        <ul class="list-wrap">
                            @foreach ($footerMenuThreeItems as $footerMenuThree)
                                <li><a href="{{ url($footerMenuThree?->link) }}">{{ $footerMenuThree?->translation_label }}</a></li>
                            @endforeach
                            @if (\Illuminate\Support\Facades\Route::has('contact.index'))
                                <li><a href="{{ route('contact.index') }}">{{ __('Contact') }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
