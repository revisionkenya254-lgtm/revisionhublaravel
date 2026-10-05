<?php

namespace App\Jobs;

use Exception;
use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Mail\UserRegistration;
use App\Services\AuthOtpService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Traits\GetGlobalInformationTrait;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\GlobalSetting\app\Models\EmailTemplate;

class SendVerifyMailToUser
{
    use Dispatchable, GetGlobalInformationTrait, InteractsWithQueue, Queueable, SerializesModels;

    private $user_type;

    private $user_info;

    public function __construct($user_type, $user_info = null)
    {
        $this->user_type = $user_type;
        $this->user_info = $user_info;
    }

    public function handle(): void
    {
        $this->set_mail_config();

        if ($this->user_type == 'all_user') {
            $users = User::where('email_verified_at', null)->orderBy('id', 'desc')->get();
            foreach ($users as $index => $user) {
                try {
                    $template = EmailTemplate::where('name', 'user_verification')->first();
                    $subject = $template->subject;
                    $message = $template->message;
                    $message = str_replace('{{user_name}}', $user->name, $message);
                    $otp = app(AuthOtpService::class)->generate($user, AuthOtpService::PURPOSE_REGISTER);
                    $message = str_replace('{{otp}}', $otp, $message);
                    $message = str_replace('{{otp_expiry}}', (string) AuthOtpService::OTP_TTL_MINUTES, $message);

                    Mail::to($user->email)->send(new UserRegistration($message, $subject, $user, $otp, AuthOtpService::PURPOSE_REGISTER));
                } catch (Exception $ex) {
                }
            }
        } else {
            try {
                $template = EmailTemplate::where('name', 'user_verification')->first();
                $subject = $template->subject;
                $message = $template->message;
                $message = str_replace('{{user_name}}', $this->user_info->name, $message);
                $otp = app(AuthOtpService::class)->generate($this->user_info, AuthOtpService::PURPOSE_REGISTER);
                $message = str_replace('{{otp}}', $otp, $message);
                $message = str_replace('{{otp_expiry}}', (string) AuthOtpService::OTP_TTL_MINUTES, $message);

                Mail::to($this->user_info->email)->send(new UserRegistration($message, $subject, $this->user_info, $otp, AuthOtpService::PURPOSE_REGISTER));
            } catch (Exception $ex) {
            }
        }

    }
}
