<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The app sits behind a reverse proxy (rs.p7h.me terminates HTTPS,
        // then forwards to this server over plain HTTP) — without this,
        // Laravel has no way to know the original request was HTTPS, and
        // generates http:// links for everything (route(), asset(), the
        // invoice PDF preview iframe, etc.), which browsers then block as
        // mixed content on the HTTPS page.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
