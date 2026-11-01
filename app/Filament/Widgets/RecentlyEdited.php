<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Entries\EntryResource;
use App\Models\Entry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The pages and posts edited most recently, each opening its edit screen.
 */
class RecentlyEdited extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Recently edited';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Entry::query()->latest('updated_at')->limit(5))
            ->paginated(false)
            ->emptyStateHeading('No pages or posts yet')
            ->recordUrl(fn (Entry $record): string => EntryResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->weight('medium')
                    ->description(fn (Entry $record): string => '/'.$record->path),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Edited')
                    ->since()
                    ->dateTimeTooltip(),
            ]);
    }
}
