<!doctype html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ __('YOUR OTP CODE') }}</title>
</head>

<body style="margin:0;padding:24px;background:#f4f5f6;font-family:Helvetica,sans-serif;color:#1f2937;">
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
                            {!! clean($mail_message) !!}
                            <p style="margin:24px 0 12px 0;">{{ $purpose === 'login' ? __('Use this OTP to sign in:') : __('Use this OTP to verify your account:') }}</p>
                            <div style="margin:0 0 16px 0;padding:18px 20px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;text-align:center;font-size:32px;font-weight:700;letter-spacing:10px;">
                                {{ $otp_code }}
                            </div>
                            <p style="margin:0 0 8px 0;">{{ __('This code expires in 10 minutes.') }}</p>
                            <p style="margin:0;">{{ __('If you did not request this code, you can safely ignore this email.') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
