<?php

namespace App\Filament\Resources\Entries\Schemas;

use App\EntryStatus;
use App\EntryType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
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
                                    ->unique(ignoreRecord: true)
                                    ->regex('/^[a-z0-9]+(?:[-\/][a-z0-9]+)*$/')
                                    ->validationMessages([
                                        'regex' => 'Use lowercase letters, numbers, hyphens and slashes only.',
                                    ])
                                    ->helperText('The address of the page, e.g. "about" or "articles/my-post".'),
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
                                    ->required(),
                                Select::make('status')
                                    ->options(EntryStatus::class)
                                    ->default(EntryStatus::Draft)
                                    ->required(),
                                DateTimePicker::make('published_at')
                                    ->label('Publish date')
                                    ->helperText('Leave empty to publish immediately.'),
                            ]),
                        Section::make('Menu')
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
}
