<?php

namespace App\Filament\Resources\Entries\Schemas;

use App\EntryStatus;
use App\EntryType;
use App\Models\Entry;
use Closure;
use Filament\Forms\Components\DateTimePicker;
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
                                    ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state ?? ''));
                                        }
                                    }),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefix(fn (Get $get): ?string => self::blogPathPrefix($get))
                                    ->regex(fn (Get $get): string => self::isPost($get)
                                        ? '/^[a-z0-9]+(?:-[a-z0-9]+)*$/'
                                        : '/^[a-z0-9]+(?:[-\/][a-z0-9]+)*$/')
                                    ->validationMessages([
                                        'regex' => 'Use lowercase letters, numbers and hyphens only. Pages and blogs may also use slashes.',
                                    ])
                                    ->rule(fn (Get $get, ?Entry $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                        $entry = new Entry([
                                            'type' => $get('type'),
                                            'blog_id' => self::isPost($get) ? $get('blog_id') : null,
                                        ]);
                                        $path = $entry->buildPath((string) $value);

                                        $isTaken = Entry::query()
                                            ->where('path', $path)
                                            ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                            ->exists();

                                        if ($isTaken) {
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
