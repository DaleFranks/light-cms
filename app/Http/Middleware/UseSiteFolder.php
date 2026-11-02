<?php

namespace App\Http\Middleware;

use App\Models\Image;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets the CMS run in a folder of a website (such as example.com/blog) as
 * well as at its root: uploaded images are addressed inside that folder, and
 * the login cookie belongs to that folder alone, so two sites on one domain
 * don't log each other out.
 */
class UseSiteFolder
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $folder = self::path();

        config([
            'filesystems.disks.'.Image::DISK.'.url' => $folder.'/uploads',
            'session.path' => $folder === '' ? '/' : $folder,
        ]);
        Storage::forgetDisk(Image::DISK);

        return $next($request);
    }

    /**
     * The folder the site is in, such as "/blog", or "" at the root.
     */
    public static function path(): string
    {
        return app()->bound('request') ? rtrim(request()->getBaseUrl(), '/') : '';
    }
}
