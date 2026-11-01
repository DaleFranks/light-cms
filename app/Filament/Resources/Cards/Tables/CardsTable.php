<?php

namespace App\Filament\Resources\Cards\Tables;

use App\Models\Card;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Card $record): string => '[card:'.$record->slug.']'),
                TextColumn::make('style')
                    ->badge()
                    ->placeholder('None'),
                IconColumn::make('featured')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Last edited')
                    ->since()
                    ->sortable(),
            ])
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
