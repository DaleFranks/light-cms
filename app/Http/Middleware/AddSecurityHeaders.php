<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standard browser protections on every page the CMS sends: other websites
 * can't show its pages inside a frame (which could trick an admin into
 * clicking something), browsers don't guess at file types, and other sites
 * only learn the domain a visitor came from, not the full address.
 *
 * They don't affect what pages can show, including embedded YouTube videos.
 */
class AddSecurityHeaders
{
    /**
     * @var array<string, string>
     */
    public const HEADERS = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
