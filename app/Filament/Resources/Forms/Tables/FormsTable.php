<?php

namespace App\Filament\Resources\Forms\Tables;

use App\Models\Form;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('submissions'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Form $record): string => $record->shortcode()),
                TextColumn::make('fields')
                    ->label('Fields')
                    ->state(fn (Form $record): string => $record->formFields()->pluck('label')->implode(', '))
                    ->limit(60),
                TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last edited')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('downloadSubmissions')
                    ->label('Download CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->visible(fn (Form $record): bool => $record->submissions_count > 0)
                    ->action(fn (Form $record): StreamedResponse => $record->downloadSubmissionsCsv()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
