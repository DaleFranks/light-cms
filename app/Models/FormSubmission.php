<?php

namespace App\Models;

use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a visitor sent with a form.
 *
 * The answers are keyed by field key, each with the field's label as it was
 * when the form was sent: {"your_name": {"label": "Your name", "value": "Ann"}}.
 * Checkbox answers are lists. Keeping the label means renaming or removing a
 * field later doesn't make older submissions unreadable.
 */
#[Fillable([
    'answers',
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
     * An answer as text, with checkbox choices separated by commas.
     */
    public function answerText(string $key): string
    {
        $value = $this->answers[$key]['value'] ?? null;

        return is_array($value) ? implode(', ', $value) : (string) $value;
    }
}
