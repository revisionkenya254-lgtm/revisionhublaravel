<?php

namespace Tests\Feature\Auth;

use App\Mail\UserRegistration;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OtpMailFormattingTest extends TestCase
{
    public function test_otp_email_uses_a_recognizable_subject_and_both_body_formats(): void
    {
        Cache::put('setting', (object) ['app_name' => 'RevisionHub']);

        $mail = new UserRegistration(
            '<p>Your OTP is 12345</p>',
            'Legacy subject',
            (object) ['name' => 'Student'],
            '12345',
            'login',
        );

        $this->assertSame('Your RevisionHub verification code is 12345', $mail->envelope()->subject);
        $this->assertNull($mail->mail_message);
        $this->assertSame(10, $mail->otp_expiry);
        $this->assertSame('auth.email_otp_mail', $mail->content()->view);
        $this->assertSame('auth.email_otp_mail_text', $mail->content()->text);

        $html = $mail->render();

        $this->assertSame(1, substr_count($html, '12345'));
        $this->assertStringContainsString('Never share this code with anyone.', $html);
    }

    public function test_safe_configured_intro_is_preserved(): void
    {
        $mail = new UserRegistration(
            '<p>Hello Student, use the code below to continue.</p>',
            'Legacy subject',
            (object) ['name' => 'Student'],
            '12345',
            'registration',
        );

        $this->assertSame('<p>Hello Student, use the code below to continue.</p>', $mail->mail_message);
    }
}
