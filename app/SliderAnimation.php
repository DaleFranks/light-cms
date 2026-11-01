<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

/**
 * How a slider moves from one slide to the next.
 */
enum SliderAnimation: string implements HasLabel
{
    case Slide = 'slide';
    case Fade = 'fade';

    public function getLabel(): string
    {
        return match ($this) {
            self::Slide => 'Slide',
            self::Fade => 'Fade',
        };
    }
}
