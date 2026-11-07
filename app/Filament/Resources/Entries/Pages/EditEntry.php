<?php

namespace App\Filament\Resources\Entries\Pages;

use App\Filament\Resources\Entries\EntryResource;
use App\Filament\ViewOnSiteAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntry extends EditRecord
{
    protected static string $resource = EntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewOnSiteAction::make()
                ->tooltip('Opens the page as last saved, in a new tab'),
            DeleteAction::make()
                ->failureNotificationTitle('This blog still has posts. Move or delete them first.'),
        ];
    }
}
