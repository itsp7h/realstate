<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Confines the roles that only work in one part of the app, applied globally so
 * every route outside their slice — present and future — is off-limits by
 * default.
 *
 * Opt-in, not opt-out: a role listed here reaches exactly the paths written
 * against it and nothing else, so a module added to the app next month is
 * refused until someone deliberately adds it. That is the whole point of the
 * shape; an "everything except…" list would silently leak each new page.
 *
 * A path here opens a door, it does not hand over the data behind it: DELETE is
 * refused separately by RestrictDestructiveActions, and controllers that scope
 * their results by role (SearchController) keep doing so.
 */
class RestrictScopedRoles
{
    /**
     * Role => the paths that role may reach, in `Request::is()` glob form.
     *
     * Roles absent from this map are unconfined here and gated per-route
     * instead (`role:admin` middleware) — that is Admin and User.
     *
     * @var array<string, list<string>>
     */
    private const SCOPES = [
        /**
         * `search` is here because the top bar — which this role does see, on
         * its dashboard — carries the ⌘K palette. Allowing the route is not
         * allowing the data: SearchController returns maintenance results only
         * for this role, so the gate on content stays in the controller and
         * this list only decides whether the door opens at all.
         */
        'maintenance' => [
            'dashboard', 'maintenance', 'maintenance/*', 'search',
            // Its own account: this role is confined to a module, not barred
            // from changing its own password.
            'profile', 'profile/*',
            'logout',
        ],

        /**
         * The ledger and the reports drawn from it. No portfolio: an Accountant
         * bills against buildings, units and leases without editing them, so
         * those paths are absent and the sidebar hides them
         * (User::canAccessPortfolio()).
         *
         * `tenants/search` is the one portfolio path allowed, and only because
         * the invoice form's payer autocomplete calls it. It is a read-only
         * JSON lookup; `tenants/*` is deliberately NOT allowed, since this list
         * matches on path and not method — it would hand over the tenant edit
         * form and its PUT along with the record.
         */
        'accountant' => [
            'dashboard', 'search',
            'invoices', 'invoices/*',
            'payments',
            'ewa-bills', 'ewa-bills/*',
            'expenses', 'expenses/*',
            'revenues', 'revenues/*',
            'reports', 'reports/*',
            'tenants/search',
            'profile', 'profile/*',
            'logout',
        ],
    ];

    /**
     * What a refused account is told, per role — the message names the slice
     * the account does have, which is more use than "forbidden".
     *
     * @var array<string, string>
     */
    private const MESSAGES = [
        'maintenance' => 'Your account only has access to Maintenance Requests.',
        'accountant'  => 'Your account only has access to the accounting and reporting sections.',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role  = $request->user()?->role;
        $scope = self::SCOPES[$role] ?? null;

        if ($scope !== null && ! $request->is(...$scope)) {
            abort(403, self::MESSAGES[$role] ?? 'Your role does not have access to this page.');
        }

        return $next($request);
    }
}
