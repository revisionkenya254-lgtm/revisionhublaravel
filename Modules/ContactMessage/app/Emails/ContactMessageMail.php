<?php

namespace Modules\ContactMessage\app\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mail_subject;

    public $mail_template;

    public $reply_to;

    public function __construct($mail_subject, $mail_template, $reply_to)
    {
        $this->mail_subject = $mail_subject;
        $this->mail_template = $mail_template;
        $this->reply_to = $reply_to;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject($this->mail_subject)
            ->replyTo($this->reply_to)
            ->view('contactmessage::contact_message_template', ['mail_template' => $this->mail_template]);
    }
}
