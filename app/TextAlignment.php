<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

/**
 * Where a line of text sits across its row, such as the top bar's.
 */
enum TextAlignment: string implements HasLabel
{
    case Left = 'left';
    case Center = 'center';
    case Right = 'right';

    public function getLabel(): string
    {
        return match ($this) {
            self::Left => 'Left',
            self::Center => 'Centered',
            self::Right => 'Right',
        };
    }

    /**
     * The matching value for CSS's justify-content.
     */
    public function justify(): string
    {
        return match ($this) {
            self::Left => 'flex-start',
            self::Center => 'center',
            self::Right => 'flex-end',
        };
    }
}
