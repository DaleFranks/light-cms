<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Forms\FormResource;
use App\Models\FormSubmission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * The last few things visitors sent with any form, each opening its form's submissions.
 */
class RecentSubmissions extends TableWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Recent form submissions';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => FormSubmission::query()->with('form')->latest()->limit(5))
            ->paginated(false)
            ->emptyStateHeading('Nothing has been sent with your forms yet')
            ->recordUrl(fn (FormSubmission $record): string => FormResource::getUrl('edit', ['record' => $record->form]))
            ->columns([
                TextColumn::make('form.name')
                    ->label('Form')
                    ->weight('medium')
                    ->description(fn (FormSubmission $record): string => self::summary($record)),
                TextColumn::make('created_at')
                    ->label('Sent')
                    ->since()
                    ->dateTimeTooltip(),
            ]);
    }

    /**
     * The first couple of answers, as a reminder of who sent it and what about.
     */
    private static function summary(FormSubmission $submission): string
    {
        return Str::limit(
            collect(array_keys($submission->answers))
                ->map(fn (string $key): string => $submission->answerText($key))
                ->filter()
                ->take(2)
                ->implode(' · '),
            80,
        );
    }
}
