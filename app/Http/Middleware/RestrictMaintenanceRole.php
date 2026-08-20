<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confines the Maintenance role to the Maintenance Requests module — applied
 * globally so every other route (present and future) is off-limits by default.
 */
class RestrictMaintenanceRole
{
    /**
     * Paths a Maintenance-role account is allowed to reach.
     */
    /**
     * `search` is here because the top bar — which this role does see, on its
     * dashboard — carries the ⌘K palette. Allowing the route is not allowing
     * the data: SearchController returns maintenance results only for this
     * role, so the gate on content stays in the controller and this list only
     * decides whether the door opens at all.
     */
    private const ALLOWED_PATHS = [
        'dashboard', 'maintenance', 'maintenance/*', 'search',
        // Its own account: this role is confined to a module, not barred from
        // changing its own password.
        'profile', 'profile/*',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isMaintenance() && ! $request->is(...self::ALLOWED_PATHS)) {
            abort(403, 'Your account only has access to Maintenance Requests.');
        }

        return $next($request);
    }
}
