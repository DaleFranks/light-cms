<?php

namespace App\Filament;

use App\Models\Entry;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * Opens an entry on the site in a new tab, as last saved. For an entry
 * visitors can't see yet (a draft, a scheduled one, or a post in a blog that
 * isn't published), it's a preview only signed-in admins can open.
 */
class ViewOnSiteAction
{
    public static function make(): Action
    {
        return Action::make('viewOnSite')
            ->label(fn (Entry $record): string => $record->isVisibleToVisitors() ? 'View' : 'Preview')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (Entry $record): string => $record->url(), shouldOpenInNewTab: true);
    }
}
