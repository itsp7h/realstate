<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks any DELETE-method request from a Staff account, applied globally
 * rather than per-controller — new destroy routes are covered automatically
 * without needing to remember to add a check to each one.
 */
class RestrictDestructiveActions
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('delete') && ! $request->user()?->canDelete()) {
            abort(403, 'Your role does not allow deleting records.');
        }

        return $next($request);
    }
}
