@php
    $googleClientId = config('services.google.client_id') ?: data_get($authSettings, 'gmail_client_id');
@endphp

@if(data_get($authSettings, 'google_login_status') === 'active' && filled($googleClientId))
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <div id="g_id_onload"
        data-client_id="{{ $googleClientId }}"
        data-context="{{ $context ?? 'signin' }}"
        data-ux_mode="redirect"
        data-login_uri="{{ route('auth.google') }}"
        data-auto_prompt="false">
    </div>
    <div class="account__social">
        <div class="g_id_signin"
            data-type="standard"
            data-size="large"
            data-theme="outline"
            data-text="{{ $buttonText ?? 'continue_with' }}"
            data-shape="rectangular"
            data-logo_alignment="left"
            data-width="400">
        </div>
    </div>
    <div class="account__divider">
        <span>{{ __('or') }}</span>
    </div>
@endif
