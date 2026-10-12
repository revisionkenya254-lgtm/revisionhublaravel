<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class UserRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public $mail_subject;

    public $mail_message;

    public $from_user;

    public $otp_code;

    public $purpose;

    public $otp_expiry = 10;

    public function __construct($mail_message, $mail_subject, $from_user, $otp_code, $purpose)
    {
        $this->mail_subject = $mail_subject;
        $this->mail_message = $this->messageContainsOtp($mail_message, $otp_code)
            ? null
            : $mail_message;
        $this->from_user = $from_user;
        $this->otp_code = $otp_code;
        $this->purpose = $purpose;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = data_get(Cache::get('setting'), 'app_name') ?: config('app.name', 'RevisionHub');

        return new Envelope(
            subject: "Your {$appName} verification code is {$this->otp_code}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'auth.email_otp_mail',
            text: 'auth.email_otp_mail_text',
        );
    }

    private function messageContainsOtp($message, $otpCode): bool
    {
        if (! is_string($message)) {
            return false;
        }

        return str_contains($message, '{{otp}}')
            || str_contains(html_entity_decode(strip_tags($message)), (string) $otpCode);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
