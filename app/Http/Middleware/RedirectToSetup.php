<?php

namespace App\Http\Middleware;

use App\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends every visit to the setup screen until the site has been set up.
 */
class RedirectToSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Installation::isChecked() || $request->is('setup', 'up') || Installation::isComplete()) {
            return $next($request);
        }

        return redirect()->route('setup.show');
    }
}
