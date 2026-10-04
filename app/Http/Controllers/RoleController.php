<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\RoleCatalog;

/**
 * Roles & Permissions — a reference page, deliberately read-only.
 *
 * This application has no roles table and no permissions table: a user carries
 * a single `role` string, and what that role can reach is decided by route
 * middleware (see App\Support\RoleCatalog for the full map). So there is
 * nothing here to edit — an editable matrix would have to be backed by a real
 * permission store and a rewrite of the middleware, which is a separate piece
 * of work. What this page does is make the rules that already exist visible,
 * and name the code that enforces each one.
 */
class RoleController extends Controller
{
    public function index()
    {
        // One grouped count rather than a query per role.
        $counts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('roles.index', [
            'roles'        => RoleCatalog::roles(),
            'capabilities' => RoleCatalog::capabilities(),
            'tally'        => RoleCatalog::tally(),
            'counts'       => $counts,
            'roleKeys'     => RoleCatalog::ROLES,
        ]);
    }
}
