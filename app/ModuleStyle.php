<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum ModuleStyle: string implements HasLabel
{
    case Dark = 'dark';
    case Light = 'light';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dark => 'Dark',
            self::Light => 'Light',
        };
    }
}
