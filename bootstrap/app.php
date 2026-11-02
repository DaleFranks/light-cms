<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\RedirectToHttps;
use App\Http\Middleware\RedirectToSetup;
use App\Http\Middleware\UpdateDatabase;
use App\Http\Middleware\UseSiteFolder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The headers come first so they're added to every response, redirects included
        $middleware->prepend([UseSiteFolder::class, AddSecurityHeaders::class, RedirectToHttps::class, RedirectToSetup::class, UpdateDatabase::class]);

        // Hosts that handle HTTPS in front of the site say so in this header.
        // Only that header is trusted; visitors' addresses are never taken from headers.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
