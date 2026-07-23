<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('invoices:send-overdue-reminders')->dailyAt('08:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // The app sits behind a reverse proxy (rs.p7h.me terminates HTTPS,
        // then forwards to this server over plain HTTP) — without this,
        // Laravel has no way to know the original request was HTTPS, and
        // generates http:// links for everything (route(), asset(), the
        // invoice PDF preview iframe, etc.), which browsers then block as
        // mixed content on the HTTPS page.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // Applied globally (not just inside the authenticated route group)
        // so every DELETE request is covered, including any added later.
        $middleware->append(\App\Http\Middleware\RestrictDestructiveActions::class);
        $middleware->append(\App\Http\Middleware\RestrictMaintenanceRole::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
