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
        // Weekly rather than daily: a lease ending in three weeks is not news
        // every morning, and a digest nobody reads is the same as no watch.
        $schedule->command('leases:report-expiring')->weeklyOn(1, '08:15');
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

        // Appended to the WEB GROUP, not the global stack, and the difference
        // is not cosmetic: global middleware runs before StartSession, so
        // $request->user() there is always null.
        //
        // With these registered globally, RestrictDestructiveActions saw a null
        // user on every browser request and refused every DELETE — including an
        // admin's — while RestrictScopedRoles failed open, so the confined
        // roles' confinement never applied to a real request at all. Neither showed up
        // in the suite because actingAs() sets the user on the guard directly,
        // which makes it resolvable even before the session starts.
        //
        // Inside the web group they run after the session and the user is real.
        // The original intent still holds: they are group-wide rather than
        // per-route, so a destroy route added later is covered without anyone
        // remembering to guard it.
        $middleware->web(append: [
            \App\Http\Middleware\RestrictDestructiveActions::class,
            \App\Http\Middleware\RestrictScopedRoles::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
