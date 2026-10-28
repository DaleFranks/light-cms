<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum EntryType: string implements HasLabel
{
    case Page = 'page';
    case Blog = 'blog';
    case Post = 'post';

    public function getLabel(): string
    {
        return match ($this) {
            self::Page => 'Page',
            self::Blog => 'Blog',
            self::Post => 'Post',
        };
    }
}
