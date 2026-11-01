<?php

namespace App\Mail;

use App\FormFieldType;
use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the site owner what a visitor sent with a form. Replying goes to the
 * visitor, if the form asked for their email address.
 */
class FormSubmissionReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        $replyTo = $this->visitorEmail();

        return new Envelope(
            subject: 'New submission: '.$this->submission->form->name,
            replyTo: $replyTo ? [new Address($replyTo)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.form-submission',
        );
    }

    /**
     * The first answer to an email field, if it's a usable address.
     */
    private function visitorEmail(): ?string
    {
        $emailKeys = $this->submission->form->formFields()
            ->filter(fn (array $field): bool => $field['type'] === FormFieldType::Email)
            ->pluck('key');

        foreach ($emailKeys as $key) {
            $address = $this->submission->answerText($key);

            if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return $address;
            }
        }

        return null;
    }
}
