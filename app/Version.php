<?php

namespace App;

use Illuminate\Support\Facades\File;

/**
 * Which version of Light CMS this copy is, such as "v0.4.0-alpha".
 *
 * The release build writes it into a VERSION file in the site's folder. A copy
 * that didn't come from a release (one being worked on) has no such file.
 */
class Version
{
    public const DEVELOPMENT = 'development';

    public static function current(): string
    {
        $path = base_path('VERSION');

        $version = File::exists($path) ? trim(File::get($path)) : '';

        return $version !== '' ? $version : self::DEVELOPMENT;
    }

    /**
     * Such as "Light CMS v0.4.0-alpha".
     */
    public static function label(): string
    {
        return 'Light CMS '.self::current();
    }
}
