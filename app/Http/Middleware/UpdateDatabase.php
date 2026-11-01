<?php

namespace App\Http\Middleware;

use App\DatabaseUpdate;
use App\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Brings the database up to date on the first visit after a newer copy of the
 * CMS has been uploaded. Sites that aren't set up yet are left to the setup
 * screen, which creates the database itself.
 */
class UpdateDatabase
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Installation::isChecked() && DatabaseUpdate::isNeeded() && Installation::isComplete()) {
            DatabaseUpdate::run();
        }

        return $next($request);
    }
}
