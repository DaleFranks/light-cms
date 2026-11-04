<?php

namespace App\Filament\Resources\Images;

use App\Filament\Resources\Images\Pages\ManageImages;
use App\Models\Image;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class ImageResource extends Resource
{
    protected static ?string $model = Image::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'filename';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Text::make(fn (Image $record): string => 'Place it in a page with '.$record->shortcode().'. Add a width, an alignment or a description after the name, e.g. [image:'.$record->filename.'|400|right|Our team].'),
                TextInput::make('alt_text')
                    ->label('Description')
                    ->maxLength(255)
                    ->helperText('Read aloud by screen readers and shown if the image can\'t load. Used wherever the shortcode doesn\'t give its own description.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
            ->columns([
                Stack::make([
                    ImageColumn::make('preview')
                        ->state(fn (Image $record): string => $record->path())
                        ->disk(Image::DISK)
                        ->imageHeight(160)
                        ->extraImgAttributes(['style' => 'max-width: 100%; object-fit: contain;', 'loading' => 'lazy']),
                    TextColumn::make('filename')
                        ->weight('medium')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('shortcode')
                        ->state(fn (Image $record): string => $record->shortcode())
                        ->fontFamily('mono')
                        ->size('sm')
                        ->copyable()
                        ->copyMessage('Shortcode copied'),
                    TextColumn::make('details')
                        ->state(fn (Image $record): string => "{$record->width} × {$record->height} · ".Number::fileSize($record->size))
                        ->color('gray')
                        ->size('sm'),
                    TextColumn::make('alt_text')
                        ->color('gray')
                        ->size('sm')
                        ->placeholder('No description')
                        ->limit(60)
                        ->searchable(),
                ])->space(2),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading(fn (Image $record): string => 'Edit '.$record->filename),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Image $record): void {
                        $usages = $record->usages();

                        if ($usages->isEmpty()) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title($record->filename.' is in use and can\'t be deleted')
                            ->body('Remove it from these first: '.$usages->implode(', ').'.')
                            ->persistent()
                            ->send();

                        $action->cancel();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageImages::route('/'),
        ];
    }
}
