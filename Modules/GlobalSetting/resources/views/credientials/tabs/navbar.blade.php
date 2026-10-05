<li class="nav-item">
    <a class="nav-link active show" id="google-recaptcha-tab" data-toggle="tab" href="#google_recaptcha_tab" role="tab"
        aria-controls="google-recaptcha" aria-selected="false">{{ __('Google reCaptcha') }}</a>
</li>
<li class="nav-item">
    <a class="nav-link" id="google-tag-tab" data-toggle="tab" href="#google_tag_tab" role="tab"
        aria-controls="google-tag" aria-selected="false">{{ __('Google Tag Manager') }}</a>
</li>
<li class="nav-item">
    <a class="nav-link" id="google-analytic-tab" data-toggle="tab" href="#google_analytic_tab" role="tab"
        aria-controls="google-analytic" aria-selected="false">{{ __('Google Analytic') }}</a>
</li>
<li class="nav-item">
    <a class="nav-link" id="facebook-pixel-tab" data-toggle="tab" href="#facebook_pixel_tab" role="tab"
        aria-controls="facebook-pixel" aria-selected="false">{{ __('Facebook Pixel') }}</a>
</li>
<li class="nav-item">
    <a class="nav-link" id="social-login-tab" data-toggle="tab" href="#social_login_tab" role="tab"
        aria-controls="social-login" aria-selected="false">{{ __('Social Login') }}</a>
</li>
<li class="nav-item">
    <a class="nav-link" id="tawk-chat-tab" data-toggle="tab" href="#tawk_chat_tab" role="tab"
        aria-controls="tawk-chat" aria-selected="false">{{ __('Tawk Chat') }}</a>
</li>
<li class="nav-item">
    <a class="nav-link" id="wasabi-chat-tab" data-toggle="tab" href="#wasabi_tab" role="tab"
        aria-controls="wasabi-chat" aria-selected="false">{{ __('Wasabi Cloud Storage') }}</a>
</li>
<li class="nav-item dropdown {{ isRoute(['admin.crediential-setting', 'admin.bunny-dashboard'], 'active') }}">
    <a href="#" class="nav-link has-dropdown dropdown-toggle" data-toggle="dropdown" role="button"
        aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-cloud"></i><span>{{ __('Bunny Cloud') }}</span>
    </a>
    <ul class="dropdown-menu">
        <li class="{{ request()->routeIs('admin.crediential-setting') ? 'active' : '' }}">
            <a class="nav-link" id="bunny-storage-tab" data-toggle="tab" href="#bunny_storage_tab" role="tab"
                aria-controls="bunny-storage" aria-selected="false">{{ __('Bunny Settings') }}</a>
        </li>
        <li class="{{ request()->routeIs('admin.bunny-dashboard') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.bunny-dashboard') }}">{{ __('Bunny Usage') }}</a>
        </li>
    </ul>
</li>
<li class="nav-item">
    <a class="nav-link" id="aws-tab" data-toggle="tab" href="#aws_tab" role="tab"
        aria-controls="aws-chat" aria-selected="false">{{ __('AWS Cloud Storage') }}</a>
</li>
@if (Nwidart\Modules\Facades\Module::has('LiveChat') && Nwidart\Modules\Facades\Module::isEnabled('LiveChat'))
    <li class="nav-item">
        <a class="nav-link" id="pusher-tab" data-toggle="tab" href="#pusher_tab" role="tab"
            aria-controls="pusher" aria-selected="false">{{ __('Pusher') }}</a>
    </li>
@endif
