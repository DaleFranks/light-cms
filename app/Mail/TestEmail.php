<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent from the Email screen to check that the site can send email.
 */
class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $siteName) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test email from '.$this->siteName,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.test',
        );
    }
}
