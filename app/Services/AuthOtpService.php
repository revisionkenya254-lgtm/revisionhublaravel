<?php

namespace App\Services;

use App\Mail\UserRegistration;
use App\Models\Admin;
use App\Models\User;
use App\Traits\MailSenderTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\GlobalSetting\app\Models\EmailTemplate;

class AuthOtpService
{
    use MailSenderTrait;

    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_REGISTER = 'register';

    public const OTP_LENGTH = 5;

    public const OTP_TTL_MINUTES = 10;

    public function generate(User|Admin $user, string $purpose): string
    {
        $otp = str_pad((string) random_int(0, 99999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        $user->forceFill([
            'otp_code' => Hash::make($otp),
            'otp_purpose' => $purpose,
            'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ])->save();

        return $otp;
    }

    public function clear(User|Admin $user): void
    {
        $user->forceFill([
            'otp_code' => null,
            'otp_purpose' => null,
            'otp_expires_at' => null,
        ])->save();
    }

    public function verify(User|Admin $user, string $otp, string $purpose): bool
    {
        if (
            empty($user->otp_code) ||
            empty($user->otp_purpose) ||
            empty($user->otp_expires_at) ||
            $user->otp_purpose !== $purpose ||
            now()->greaterThan($user->otp_expires_at)
        ) {
            return false;
        }

        return Hash::check($otp, $user->otp_code);
    }

    public function send(User|Admin $user, string $purpose): bool
    {
        $otp = $this->generate($user, $purpose);

        if (!self::setMailConfig()) {
            $this->clear($user);
            return false;
        }

        try {
            $template = EmailTemplate::where('name', 'user_verification')->first();
            $subject = $template?->subject ?: __('Your verification code');
            $message = $template?->message ?: __('Use the OTP below to continue.');
            $message = str_replace('{{user_name}}', $user->name, $message);
            $message = str_replace('{{otp}}', $otp, $message);
            $message = str_replace('{{otp_expiry}}', (string) self::OTP_TTL_MINUTES, $message);

            Mail::to($user->email)->send(new UserRegistration($message, $subject, $user, $otp, $purpose));

            return true;
        } catch (\Exception $exception) {
            if (app()->isLocal()) {
                Log::error($exception->getMessage());
            }

            $this->clear($user);

            return false;
        }
    }
}
