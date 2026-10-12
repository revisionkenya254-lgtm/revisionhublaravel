{{ __('Your verification code') }}

@if (filled($mail_message))
{{ trim(strip_tags($mail_message)) }}

@endif
{{ $purpose === 'login' ? __('Enter this code to sign in:') : __('Enter this code to verify your account:') }}

{{ $otp_code }}

{{ __('This code expires in :minutes minutes.', ['minutes' => $otp_expiry]) }}

{{ __('If you did not request this code, you can safely ignore this email. Never share this code with anyone.') }}
