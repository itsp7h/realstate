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
    private const ALLOWED_PATHS = ['dashboard', 'maintenance', 'maintenance/*', 'logout'];

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
