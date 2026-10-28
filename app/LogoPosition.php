<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum LogoPosition: string implements HasLabel
{
    case Left = 'left';
    case Center = 'center';

    public function getLabel(): string
    {
        return match ($this) {
            self::Left => 'Left, with the menu beside it',
            self::Center => 'Centered, with the menu below it',
        };
    }
}
