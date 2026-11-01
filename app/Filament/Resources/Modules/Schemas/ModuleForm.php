<?php

namespace App\Filament\Resources\Modules\Schemas;

use App\Models\Module;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Get $get, Set $set): void {
                                if ($operation === 'create' && ! $get('slug_is_manual')) {
                                    $set('slug', Str::slug($state ?? '') ?: null);
                                }
                            }),
                        Hidden::make('slug_is_manual')
                            ->default(false)
                            ->dehydrated(false),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('slug_is_manual', filled($state)))
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->validationMessages([
                                'regex' => 'Use lowercase letters, numbers and hyphens only.',
                            ])
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (?Module $record): bool => $record?->isFooter() ?? false)
                            ->helperText(fn (?Module $record): string => $record?->isFooter()
                                ? 'This module is the site footer, shown at the bottom of every page.'
                                : 'The name used to place the module in a page.'),
                        MarkdownEditor::make('body')
                            ->label('Content')
                            ->helperText('Shortcodes: [navigation:flat] shows a row of menu links, [navigation:multi] shows a column per menu item with its sub-pages, [curr-year] shows the current year and [site-name] shows the site name.'),
                    ]),
            ]);
    }
}
