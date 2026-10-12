<!doctype html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ __('Your verification code') }}</title>
</head>

<body style="margin:0;padding:24px;background:#f4f5f6;font-family:Helvetica,sans-serif;color:#1f2937;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        {{ __('Use this one-time code to complete your verification.') }}
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border:1px solid #eaebed;border-radius:16px;">
                    <tr>
                        <td style="padding:24px;text-align:center;">
                            <img src="{{ asset(data_get($setting ?? Cache::get('setting'), 'logo', 'uploads/website-images/logo.svg')) }}" alt="logo" style="max-height:56px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 24px 24px 24px;">
                            <h1 style="margin:0 0 12px 0;font-size:24px;line-height:1.3;">{{ __('Your verification code') }}</h1>
                            @if (filled($mail_message))
                                <div style="margin:0 0 20px 0;line-height:1.6;">{!! clean($mail_message) !!}</div>
                            @endif
                            <p style="margin:0 0 12px 0;line-height:1.6;">{{ $purpose === 'login' ? __('Enter this code to sign in:') : __('Enter this code to verify your account:') }}</p>
                            <div style="margin:0 0 16px 0;padding:18px 20px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;text-align:center;font-family:Consolas,Monaco,'Courier New',monospace;font-size:32px;line-height:1.2;font-weight:700;letter-spacing:8px;color:#111827;">
                                {{ $otp_code }}
                            </div>
                            <p style="margin:0 0 8px 0;line-height:1.6;">{{ __('This code expires in :minutes minutes.', ['minutes' => $otp_expiry]) }}</p>
                            <p style="margin:0;line-height:1.6;color:#4b5563;">{{ __('If you did not request this code, you can safely ignore this email. Never share this code with anyone.') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
