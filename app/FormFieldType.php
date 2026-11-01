<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

/**
 * The kinds of field a form built on the Forms screen can have.
 */
enum FormFieldType: string implements HasLabel
{
    case Text = 'text';
    case Email = 'email';
    case Phone = 'phone';
    case Textarea = 'textarea';
    case Number = 'number';
    case Date = 'date';
    case Dropdown = 'dropdown';
    case Checkboxes = 'checkboxes';
    case Radio = 'radio';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => 'Short text',
            self::Email => 'Email address',
            self::Phone => 'Phone number',
            self::Textarea => 'Long text',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Dropdown => 'Dropdown',
            self::Checkboxes => 'Checkboxes',
            self::Radio => 'Radio buttons',
        };
    }

    /**
     * Whether the visitor picks from a list of choices.
     */
    public function hasChoices(): bool
    {
        return in_array($this, [self::Dropdown, self::Checkboxes, self::Radio], true);
    }

    /**
     * The type attribute of the <input> for this field, for fields that are one.
     */
    public function inputType(): ?string
    {
        return match ($this) {
            self::Text => 'text',
            self::Email => 'email',
            self::Phone => 'tel',
            self::Number => 'number',
            self::Date => 'date',
            default => null,
        };
    }
}
