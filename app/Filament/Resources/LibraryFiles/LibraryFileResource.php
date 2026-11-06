<?php

namespace App\Filament\Resources\LibraryFiles;

use App\Filament\Resources\LibraryFiles\Pages\ManageLibraryFiles;
use App\Models\LibraryFile;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * The file library: PDFs, documents, spreadsheets and zips that
 * pages link to with [file:name].
 */
class LibraryFileResource extends Resource
{
    protected static ?string $model = LibraryFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperClip;

    protected static ?string $navigationLabel = 'Files';

    protected static ?string $modelLabel = 'file';

    protected static ?string $slug = 'files';

    protected static ?string $recordTitleAttribute = 'filename';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('filename')
                    ->label('File')
                    ->weight('medium')
                    ->url(fn (LibraryFile $record): string => $record->url(), shouldOpenInNewTab: true)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shortcode')
                    ->state(fn (LibraryFile $record): string => $record->shortcode())
                    ->fontFamily('mono')
                    ->size('sm')
                    ->copyable()
                    ->copyMessage('Shortcode copied'),
                TextColumn::make('details')
                    ->label('Type and size')
                    ->state(fn (LibraryFile $record): string => $record->details())
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                self::replaceAction(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, LibraryFile $record): void {
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

    /**
     * The upload box for the library, which takes only the types it allows.
     */
    public static function uploadField(string $name): FileUpload
    {
        return FileUpload::make($name)
            ->storeFiles(false)
            ->required()
            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                if ($value instanceof UploadedFile && ($problem = LibraryFile::problemWith($value)) !== null) {
                    $fail($problem);
                }
            });
    }

    /**
     * Swaps a new file in under a file's name, for every link to it.
     */
    private static function replaceAction(): Action
    {
        return Action::make('replace')
            ->icon(Heroicon::OutlinedArrowPath)
            ->modalHeading(fn (LibraryFile $record): string => 'Replace '.$record->filename)
            ->modalSubmitActionLabel('Replace')
            ->schema(fn (LibraryFile $record): array => [
                Text::make(self::whereUsedText($record).' The new file keeps this name, so every link gets it. The current file is overwritten and can\'t be brought back, except from a backup.'),
                self::uploadField('file')->hiddenLabel(),
            ])
            ->action(function (Action $action, LibraryFile $record, array $data): void {
                try {
                    $record->replaceWith($data['file']);
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('The file wasn\'t replaced')
                        ->body($exception->getMessage())
                        ->persistent()
                        ->send();

                    $action->halt();
                }

                Notification::make()
                    ->success()
                    ->title($record->filename.' replaced')
                    ->body('Now '.$record->details())
                    ->send();
            });
    }

    private static function whereUsedText(LibraryFile $file): string
    {
        $usages = $file->usages();

        return $usages->isEmpty()
            ? 'This file isn\'t linked from anywhere yet.'
            : 'This file is linked from: '.$usages->implode(', ').'.';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLibraryFiles::route('/'),
        ];
    }
}
