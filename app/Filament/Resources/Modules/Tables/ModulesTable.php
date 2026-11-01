<?php

namespace App\Filament\Resources\Modules\Tables;

use App\Models\Module;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Module $record): string => $record->isFooter() ? 'Shown at the bottom of every page' : '[module:'.$record->slug.']'),
                TextColumn::make('style')
                    ->badge()
                    ->placeholder('None'),
                TextColumn::make('updated_at')
                    ->label('Last edited')
                    ->since()
                    ->sortable(),
            ])
            ->checkIfRecordIsSelectableUsing(fn (Module $record): bool => ! $record->isFooter())
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
