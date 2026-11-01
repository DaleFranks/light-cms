<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends visits made over plain http:// to the same address on https://, and
 * marks the login cookie as HTTPS-only, so passwords and the cookie are never
 * sent unencrypted.
 *
 * It only applies to a site whose address starts with https:// (set when the
 * site was set up), so a copy running on a computer without HTTPS still works.
 */
class RedirectToHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::isRequired()) {
            return $next($request);
        }

        if ($request->secure()) {
            config(['session.secure' => true]);

            return $next($request);
        }

        return redirect()->to('https://'.$request->getHttpHost().$request->getRequestUri(), 301);
    }

    /**
     * Whether the site runs on HTTPS, judged by its address.
     */
    public static function isRequired(): bool
    {
        return str_starts_with(strtolower((string) config('app.url')), 'https://');
    }
}
