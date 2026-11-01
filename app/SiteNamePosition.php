<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

/**
 * Where the site name sits in the header, relative to the logo.
 */
enum SiteNamePosition: string implements HasLabel
{
    case Beside = 'beside';
    case Below = 'below';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::Beside => 'Beside the logo',
            self::Below => 'Below the logo',
            self::Hidden => 'Don\'t show (logo only)',
        };
    }
}
