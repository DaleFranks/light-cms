<?php

namespace App\Models;

use App\Mail\FormSubmissionReceived;
use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * What a visitor sent with a form.
 *
 * The answers are keyed by field key, each with the field's label as it was
 * when the form was sent: {"your_name": {"label": "Your name", "value": "Ann"}}.
 * Checkbox answers are lists. Keeping the label means renaming or removing a
 * field later doesn't make older submissions unreadable.
 *
 * The email status is "sent", "failed", or empty when the form emails no one.
 */
#[Fillable([
    'answers',
    'email_status',
])]
class FormSubmission extends Model
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Email the submission to the addresses set on its form, if any. A failure
     * is noted and logged, but never stops the submission being kept.
     */
    public function sendNotification(): void
    {
        $addresses = $this->form->notifyAddresses();

        if ($addresses === []) {
            return;
        }

        try {
            MailSettings::current()->apply();
            Mail::to($addresses)->send(new FormSubmissionReceived($this));
            $this->update(['email_status' => 'sent']);
        } catch (Throwable $exception) {
            report($exception);
            $this->update(['email_status' => 'failed']);
        }
    }

    /**
     * An answer as text, with checkbox choices separated by commas.
     */
    public function answerText(string $key): string
    {
        $value = $this->answers[$key]['value'] ?? null;

        return is_array($value) ? implode(', ', $value) : (string) $value;
    }
}
