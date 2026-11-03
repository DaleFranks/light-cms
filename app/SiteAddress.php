<?php

namespace App;

use App\Http\Middleware\RedirectToHttps;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * The site's own address, saved as APP_URL in the .env settings file.
 */
class SiteAddress
{
    /**
     * Save the address the owner finished setup on. It replaces the one taken
     * from the site's first visit, which may have been a bot's. The login
     * cookies made during this same visit follow the new address too.
     *
     * A site whose settings file can't be changed still works, on the address
     * it already has, so that doesn't stop setup finishing.
     */
    public static function save(string $address): void
    {
        config(['app.url' => $address]);

        if (RedirectToHttps::isRequired()) {
            config(['session.secure' => true]);
            Cookie::setDefaultPathAndDomain(config('session.path'), config('session.domain'), true, config('session.same_site'));
        }

        try {
            self::writeToSettingsFile($address);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function writeToSettingsFile(string $address): void
    {
        $file = app()->environmentFilePath();
        $settings = File::get($file);
        $line = 'APP_URL='.$address;
        $pattern = '/^APP_URL=.*$/m';

        File::replace($file, preg_match($pattern, $settings)
            ? preg_replace_callback($pattern, fn (): string => $line, $settings)
            : rtrim($settings)."\n".$line."\n", 0644);
    }
}
