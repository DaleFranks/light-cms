<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum FooterStyle: string implements HasLabel
{
    case Dark = 'dark';
    case Light = 'light';
    case Primary = 'primary';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dark => 'Dark',
            self::Light => 'Light',
            self::Primary => 'Primary color',
        };
    }
}
