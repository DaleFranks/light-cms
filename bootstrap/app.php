<?php

use App\Http\Controllers\FormSubmissionController;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\RedirectToHttps;
use App\Http\Middleware\RedirectToSetup;
use App\Http\Middleware\UpdateDatabase;
use App\Http\Middleware\UseSiteFolder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The headers come first so they're added to every response, redirects included
        $middleware->prepend([UseSiteFolder::class, AddSecurityHeaders::class, RedirectToSetup::class, UpdateDatabase::class]);

        // After Laravel's own TrustProxies, so HTTPS handled by a server in front
        // (such as Cloudflare) is recognised and never redirected to itself
        $middleware->append(RedirectToHttps::class);

        // Hosts that handle HTTPS in front of the site say so in this header.
        // Only that header is trusted; visitors' addresses are never taken from headers.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A form sent from a page left open past the session's lifetime fails its
        // security check. The visitor goes back to the form with their answers
        // kept, to send it again, rather than to an error page.
        $exceptions->render(function (HttpException $exception, Request $request): ?RedirectResponse {
            if ($exception->getStatusCode() !== 419 || ! $request->routeIs('forms.submit')) {
                return null;
            }

            $slug = (string) $request->route('form');

            return FormSubmissionController::backToForm($slug)
                ->withErrors(['form' => 'This page was open for a long time, so the form wasn\'t sent. Please check your answers and send it again.'], 'form-'.$slug)
                ->withInput();
        });
    })->create();
