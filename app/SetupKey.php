<?php

namespace App;

use Illuminate\Support\Facades\File;

/**
 * The key that proves whoever is on the setup screen owns the site.
 *
 * It's saved in a file beside the site's .env settings file. Only someone with
 * access to the site's files can read it, because browsers can't reach that
 * folder. A visitor who merely finds the setup screen can't finish setup.
 */
class SetupKey
{
    public const FILE_NAME = 'setup-key.txt';

    /**
     * Create the key file, unless it already holds a key. A file the owner has
     * deleted is replaced the next time the setup screen is opened.
     */
    public static function create(): void
    {
        if (self::stored() === '') {
            File::replace(self::path(), bin2hex(random_bytes(16))."\n", 0644);
        }
    }

    public static function matches(string $key): bool
    {
        $stored = self::stored();

        return $stored !== '' && hash_equals($stored, trim($key));
    }

    public static function delete(): void
    {
        File::delete(self::path());
    }

    private static function stored(): string
    {
        return File::exists(self::path()) ? trim(File::get(self::path())) : '';
    }

    private static function path(): string
    {
        return app()->environmentPath().'/'.self::FILE_NAME;
    }
}
