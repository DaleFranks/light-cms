<?php

namespace App\Filament\Resources\Entries\Schemas;

use App\EntryStatus;
use App\EntryType;
use App\Models\Entry;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;

class EntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, Get $get, Set $set, ?Entry $record) => self::fillSlug($operation, $get, $set, $record)),
                                Hidden::make('slug_is_manual')
                                    ->default(false)
                                    ->dehydrated(false),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?string $state, Set $set, Component $livewire, TextInput $component): void {
                                        $set('slug_is_manual', filled($state));
                                        $livewire->validateOnly($component->getStatePath());
                                    })
                                    ->prefix(fn (Get $get): ?string => self::blogPathPrefix($get))
                                    ->regex(fn (Get $get): string => self::isPost($get)
                                        ? '/^[a-z0-9]+(?:-[a-z0-9]+)*$/'
                                        : '/^[a-z0-9]+(?:[-\/][a-z0-9]+)*$/')
                                    ->validationMessages([
                                        'regex' => 'Use lowercase letters, numbers and hyphens only. Pages and blogs may also use slashes.',
                                    ])
                                    ->rule(fn (Get $get, ?Entry $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                        $path = self::pathFor($get, (string) $value);

                                        if (in_array(Str::before($path, '/'), Entry::RESERVED_PATHS, true)) {
                                            $fail('Addresses starting with "'.Str::before($path, '/').'" are used by the system.');

                                            return;
                                        }

                                        if (self::isPathTaken($path, $record)) {
                                            $fail("The address /{$path} is already used by another entry.");
                                        }
                                    })
                                    ->helperText(fn (Get $get): string => self::isPost($get)
                                        ? 'The last part of the post\'s address, e.g. "my-first-post".'
                                        : 'The address of the page, e.g. "about" or "services/design".'),
                                MarkdownEditor::make('body')
                                    ->label('Content'),
                            ]),
                        Section::make('Search engines')
                            ->collapsible()
                            ->schema([
                                TextInput::make('meta_description')
                                    ->maxLength(255),
                                TextInput::make('meta_keywords')
                                    ->maxLength(255),
                            ]),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Publishing')
                            ->schema([
                                Select::make('type')
                                    ->options(EntryType::class)
                                    ->default(EntryType::Page)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (string $operation, Get $get, Set $set, ?Entry $record) => self::fillSlug($operation, $get, $set, $record))
                                    ->disabled(fn (?Entry $record): bool => $record?->type === EntryType::Blog && $record->posts()->exists())
                                    ->helperText(fn (?Entry $record): ?string => $record?->type === EntryType::Blog && $record->posts()->exists()
                                        ? 'A blog with posts can\'t change type.'
                                        : null),
                                Select::make('blog_id')
                                    ->label('Blog')
                                    ->relationship(
                                        'blog',
                                        'title',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('type', EntryType::Blog),
                                    )
                                    ->visible(fn (Get $get): bool => self::isPost($get))
                                    ->required(fn (Get $get): bool => self::isPost($get))
                                    ->live()
                                    ->afterStateUpdated(fn (string $operation, Get $get, Set $set, ?Entry $record) => self::fillSlug($operation, $get, $set, $record))
                                    ->preload(),
                                Select::make('status')
                                    ->options(EntryStatus::class)
                                    ->default(EntryStatus::Draft)
                                    ->required(),
                                DateTimePicker::make('published_at')
                                    ->label('Publish date')
                                    ->helperText('Leave empty to publish immediately.'),
                            ]),
                        Section::make('Menu')
                            ->hidden(fn (Get $get): bool => self::isPost($get))
                            ->schema([
                                Select::make('parent_id')
                                    ->label('Parent')
                                    ->relationship('parent', 'title', ignoreRecord: true)
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('nav_order')
                                    ->label('Menu order')
                                    ->integer()
                                    ->minValue(0)
                                    ->helperText('Leave empty to keep it out of the menu.'),
                            ]),
                    ]),
            ]);
    }

    /**
     * While creating an entry, fill the slug from the title, numbering it
     * ("welcome-2") if the address is taken. Stops once the slug is typed by hand.
     */
    private static function fillSlug(string $operation, Get $get, Set $set, ?Entry $record): void
    {
        if ($operation !== 'create' || $get('slug_is_manual')) {
            return;
        }

        $baseSlug = Str::slug($get('title') ?? '');

        if ($baseSlug === '') {
            $set('slug', null);

            return;
        }

        $slug = $baseSlug;

        for ($number = 2; self::isPathTaken(self::pathFor($get, $slug), $record); $number++) {
            $slug = "{$baseSlug}-{$number}";
        }

        $set('slug', $slug);
    }

    /**
     * The full address an entry would have with the form's current type and blog.
     */
    private static function pathFor(Get $get, string $slug): string
    {
        $entry = new Entry([
            'type' => $get('type'),
            'blog_id' => self::isPost($get) ? $get('blog_id') : null,
        ]);

        return $entry->buildPath($slug);
    }

    private static function isPathTaken(string $path, ?Entry $record): bool
    {
        return Entry::query()
            ->where('path', $path)
            ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
            ->exists();
    }

    private static function isPost(Get $get): bool
    {
        $type = $get('type');

        return $type === EntryType::Post || $type === EntryType::Post->value;
    }

    private static function blogPathPrefix(Get $get): ?string
    {
        if (! self::isPost($get) || blank($get('blog_id'))) {
            return null;
        }

        $blogPath = Entry::query()->whereKey($get('blog_id'))->value('path');

        return $blogPath === null ? null : '/'.$blogPath.'/';
    }
}
