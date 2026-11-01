<?php

namespace App\Models;

use App\FormFieldType;
use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A form built on the Forms screen and placed in a page with [form:slug].
 *
 * Its fields are stored in order, each as:
 * {label, key, type, required, help, choices}. The key names the field's
 * answer in submissions. It's made from the label when the field is first
 * saved and then kept, so renaming a field doesn't disconnect earlier answers.
 */
#[Fillable([
    'name',
    'slug',
    'fields',
    'submit_label',
    'success_message',
])]
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use HasFactory;

    /**
     * The hidden field that only spam bots fill in.
     */
    public const HONEYPOT_FIELD = 'website_url';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'fields' => '[]',
        'submit_label' => 'Send',
        'success_message' => 'Thank you! Your message has been sent.',
    ];

    protected static function booted(): void
    {
        static::saving(function (Form $form): void {
            $form->fields = self::withKeys($form->fields ?? []);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fields' => 'array',
        ];
    }

    /**
     * Give every field without a key one made from its label, unique within the form.
     *
     * @param  array<int|string, array<string, mixed>>  $fields
     * @return list<array<string, mixed>>
     */
    private static function withKeys(array $fields): array
    {
        $fields = array_values($fields);
        $taken = [self::HONEYPOT_FIELD, ...array_filter(array_column($fields, 'key'))];

        foreach ($fields as $index => $field) {
            if (filled($field['key'] ?? null)) {
                continue;
            }

            $baseKey = Str::slug((string) ($field['label'] ?? ''), '_') ?: 'field';
            $key = $baseKey;

            for ($number = 2; in_array($key, $taken, true); $number++) {
                $key = "{$baseKey}_{$number}";
            }

            $fields[$index]['key'] = $key;
            $taken[] = $key;
        }

        return $fields;
    }

    /**
     * The fields, in order, with their type as a FormFieldType and their choices as a list.
     *
     * @return Collection<int, array{label: string, key: string, type: FormFieldType, required: bool, help: ?string, choices: list<string>}>
     */
    public function formFields(): Collection
    {
        return collect($this->fields)
            ->map(fn (array $field): array => [
                'label' => (string) ($field['label'] ?? ''),
                'key' => (string) ($field['key'] ?? ''),
                'type' => FormFieldType::tryFrom((string) ($field['type'] ?? '')) ?? FormFieldType::Text,
                'required' => (bool) ($field['required'] ?? false),
                'help' => filled($field['help'] ?? null) ? (string) $field['help'] : null,
                'choices' => array_values(array_filter((array) ($field['choices'] ?? []), 'filled')),
            ])
            ->filter(fn (array $field): bool => $field['key'] !== '')
            ->values();
    }

    public function shortcode(): string
    {
        return '[form:'.$this->slug.']';
    }

    /**
     * The form's HTML, or nothing if it has no fields yet.
     */
    public function html(): ?string
    {
        $fields = $this->formFields();

        if ($fields->isEmpty()) {
            return null;
        }

        return view('components.site.form', ['form' => $this, 'fields' => $fields])->render();
    }
}
