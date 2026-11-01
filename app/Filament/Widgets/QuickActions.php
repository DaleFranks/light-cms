<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Entries\EntryResource;
use App\Filament\Resources\Images\ImageResource;
use Filament\Widgets\Widget;

/**
 * Buttons for the things a site owner most often comes to the admin panel to do.
 */
class QuickActions extends Widget
{
    protected static ?int $sort = -2;

    protected string $view = 'filament.widgets.quick-actions';

    /**
     * @return list<array{label: string, url: string, icon: string, newTab: bool}>
     */
    public function actions(): array
    {
        return [
            ['label' => 'New page', 'url' => EntryResource::getUrl('create'), 'icon' => 'heroicon-o-document-plus', 'newTab' => false],
            ['label' => 'New post', 'url' => EntryResource::getUrl('create', ['type' => 'post']), 'icon' => 'heroicon-o-pencil-square', 'newTab' => false],
            ['label' => 'Upload images', 'url' => ImageResource::getUrl(), 'icon' => 'heroicon-o-photo', 'newTab' => false],
            ['label' => 'View site', 'url' => url('/'), 'icon' => 'heroicon-o-arrow-top-right-on-square', 'newTab' => true],
        ];
    }
}
