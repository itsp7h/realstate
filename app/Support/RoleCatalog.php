<?php

namespace App\Support;

/**
 * What each role in this application is allowed to do, and where that is
 * enforced.
 *
 * Roles here are defined in code, not in the database: there is no roles table
 * and no permissions table. Authorisation comes from four places, and every
 * verdict below cites the one that produces it —
 *
 *   • EnsureUserHasRole          the `role:…` middleware on a route group
 *   • RestrictScopedRoles        a global path allowlist for the confined
 *                                roles — Maintenance and Accountant
 *   • RestrictDestructiveActions a global block on DELETE for non-admins
 *   • User::canAccessPortfolio, canAccessAccounting, canAccessMaintenance,
 *     canViewReports, canDelete   the same rules, for view-level checks
 *
 * This class exists so the Roles & Permissions page cannot drift from the code
 * it describes: change who can reach what, and the matrix here is the one place
 * that has to be updated with it. RoleCatalogTest pins the role list to
 * StoreUserRequest's validation rule — which now reads the list from here — so a
 * role cannot appear in the app without appearing on the page too.
 */
final class RoleCatalog
{
    /**
     * The role keys, most privileged first. Mirrors StoreUserRequest's
     * `Rule::in([...])` for the `role` field.
     *
     * @var list<string>
     */
    public const ROLES = ['admin', 'user', 'accountant', 'maintenance'];

    /** A capability is granted, refused, or granted with a limit. */
    public const FULL = 'full';

    public const NONE = 'none';

    public const PARTIAL = 'partial';

    /**
     * The roles themselves — label, glyph, badge tone, and what the role is
     * for in one sentence.
     *
     * @return list<array{key:string,label:string,icon:string,tone:string,summary:string,scope:string}>
     */
    public static function roles(): array
    {
        return [
            [
                'key'     => 'admin',
                'label'   => 'Admin',
                'icon'    => 'fa-user-shield',
                'tone'    => 'accent',
                'summary' => 'Unrestricted. The only role that can delete records, open the financial reports, manage accounts, or change system settings.',
                'scope'   => 'Every module',
            ],
            [
                'key'     => 'user',
                'label'   => 'User',
                'icon'    => 'fa-user',
                'tone'    => 'info',
                'summary' => 'Day-to-day operations across the portfolio and the accounting side. Can create and edit freely, but never delete, and cannot see financial reports or system settings.',
                'scope'   => 'Portfolio & accounting',
            ],
            [
                'key'     => 'accountant',
                'label'   => 'Accountant',
                'icon'    => 'fa-calculator',
                'tone'    => 'success',
                'summary' => 'The ledger and the reports drawn from it — invoices, payments, EWA bills, expenses and revenue, plus every financial report. Bills against the portfolio without being able to change it, and never deletes.',
                'scope'   => 'Accounting & reports',
            ],
            [
                'key'     => 'maintenance',
                'label'   => 'Maintenance',
                'icon'    => 'fa-screwdriver-wrench',
                'tone'    => 'neutral',
                'summary' => 'Confined to Maintenance Requests and the dashboard. Every other path is refused — including pages added to the app later, because the allowlist is opt-in rather than opt-out.',
                'scope'   => 'Maintenance only',
            ],
        ];
    }

    /**
     * The capability matrix, grouped the way the sidebar is grouped.
     *
     * Each row carries a `by` line naming the code that enforces it, so a
     * reader can go and check rather than trust the page.
     *
     * @return array<string, list<array{
     *     label:string, detail:string, by:string,
     *     verdicts:array<string, string>, notes?:array<string, string>
     * }>>
     */
    public static function capabilities(): array
    {
        return [
            'Portfolio' => [
                [
                    'label'  => 'View buildings, floors, units, tenants and leases',
                    'detail' => 'Read access to every portfolio record.',
                    'by'     => 'RestrictScopedRoles (global path allowlist)',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::FULL, 'accountant' => self::PARTIAL, 'maintenance' => self::NONE],
                    'notes'    => ['accountant' => "Can look a tenant up by name from an invoice's payer field, which is a JSON lookup. The tenant, building, unit and lease pages themselves are refused."],
                ],
                [
                    'label'  => 'Create and edit portfolio records',
                    'detail' => 'Add a building, edit a unit, register a tenant, write a lease.',
                    'by'     => 'RestrictScopedRoles (global path allowlist)',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::FULL, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                ],
                [
                    'label'  => 'Delete any record',
                    'detail' => 'Every DELETE request in the app, not just the portfolio ones.',
                    'by'     => 'RestrictDestructiveActions (global) · User::canDelete()',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::NONE, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                ],
            ],
            'Accounting' => [
                [
                    'label'  => 'Invoices, payments, EWA bills, expenses and revenue',
                    'detail' => 'Raise an invoice, record a payment, enter a bill or an expense.',
                    'by'     => 'RestrictScopedRoles (global path allowlist)',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::FULL, 'accountant' => self::FULL, 'maintenance' => self::NONE],
                ],
                [
                    'label'  => 'Financial reports',
                    'detail' => 'Profit & loss, VAT return, collections, ageing, tenant statements and ledgers.',
                    'by'     => 'role:admin,accountant on the /reports group · User::canViewReports()',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::NONE, 'accountant' => self::FULL, 'maintenance' => self::NONE],
                ],
            ],
            'Maintenance' => [
                [
                    'label'  => 'Raise, view and update maintenance requests',
                    'detail' => 'Raising and tracking work on the portfolio.',
                    'by'     => 'RestrictScopedRoles (Accountant is outside the module)',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::FULL, 'accountant' => self::NONE, 'maintenance' => self::FULL],
                ],
                [
                    'label'  => 'Assess and approve a request',
                    'detail' => 'Record an assessment, then approve or reject the quoted work.',
                    'by'     => 'RestrictScopedRoles (Accountant is outside the module)',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::FULL, 'accountant' => self::NONE, 'maintenance' => self::FULL],
                ],
            ],
            'Configuration' => [
                [
                    'label'  => 'Forms & Templates',
                    'detail' => 'Which fields appear on the add/edit forms, and the import templates.',
                    'by'     => 'role:admin on form-configs.update',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::PARTIAL, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                    'notes'    => ['user' => 'Can open and read a form config, but saving one is refused.'],
                ],
                [
                    'label'  => 'Custom fields',
                    'detail' => 'Adding or removing a custom field changes the forms for everybody.',
                    'by'     => 'role:admin on the custom-fields routes',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::NONE, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                ],
                [
                    'label'  => 'Import & Export',
                    'detail' => 'Bulk create and overwrite across buildings, floors, units, tenants and leases.',
                    'by'     => 'role:admin on the /data, /import and /export groups',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::NONE, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                ],
                [
                    'label'  => 'Branding and mail settings',
                    'detail' => 'Site name, logo, favicon, and the outgoing-mail account.',
                    'by'     => 'role:admin on the /settings group',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::NONE, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                ],
            ],
            'Administration' => [
                [
                    'label'  => 'User accounts',
                    'detail' => "Create an account, change someone's role, remove access.",
                    'by'     => 'role:admin on the users resource',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::NONE, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                ],
                [
                    'label'  => 'Roles & Permissions',
                    'detail' => 'This page.',
                    'by'     => 'role:admin on roles.index',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::NONE, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                ],
                [
                    'label'  => 'Activity feed and error log',
                    'detail' => 'Who changed what, and what the application failed on.',
                    'by'     => 'role:admin on the /admin group',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::NONE, 'accountant' => self::NONE, 'maintenance' => self::NONE],
                ],
                [
                    'label'  => 'Dashboard',
                    'detail' => 'The landing screen after sign-in.',
                    'by'     => 'Signed-in users (allowlisted for the confined roles)',
                    'verdicts' => ['admin' => self::FULL, 'user' => self::FULL, 'accountant' => self::FULL, 'maintenance' => self::FULL],
                ],
            ],
        ];
    }

    /**
     * How many capabilities each role is granted outright, over the total —
     * the figure the role cards lead with.
     *
     * @return array<string, array{granted:int, partial:int, total:int}>
     */
    public static function tally(): array
    {
        $out = [];

        foreach (self::ROLES as $role) {
            $out[$role] = ['granted' => 0, 'partial' => 0, 'total' => 0];
        }

        foreach (self::capabilities() as $rows) {
            foreach ($rows as $row) {
                foreach (self::ROLES as $role) {
                    $out[$role]['total']++;

                    if (($row['verdicts'][$role] ?? self::NONE) === self::FULL) {
                        $out[$role]['granted']++;
                    } elseif (($row['verdicts'][$role] ?? self::NONE) === self::PARTIAL) {
                        $out[$role]['partial']++;
                    }
                }
            }
        }

        return $out;
    }
}
