<?php

namespace App\Filament\Resources\Images\Pages;

use App\Filament\Resources\Images\ImageResource;
use App\Models\Image;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageImages extends ManageRecords
{
    protected static string $resource = ImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload images')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalSubmitActionLabel('Upload')
                ->schema([
                    FileUpload::make('files')
                        ->hiddenLabel()
                        ->multiple()
                        ->image()
                        ->acceptedFileTypes(array_keys(Image::TYPES))
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Drop one or more images here. A file whose name is already taken is saved with a number added, so nothing is replaced.'),
                ])
                ->action(function (array $data): void {
                    $images = collect($data['files'])
                        ->map(fn (TemporaryUploadedFile $file): Image => Image::storeUpload($file));

                    Notification::make()
                        ->success()
                        ->title($images->count() === 1 ? 'Image uploaded' : $images->count().' images uploaded')
                        ->body('Saved as '.$images->pluck('filename')->implode(', ').'.')
                        ->send();
                }),
        ];
    }
}
