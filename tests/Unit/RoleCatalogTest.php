<?php

namespace Tests\Unit;

use App\Http\Requests\StoreUserRequest;
use App\Support\RoleCatalog;
use PHPUnit\Framework\TestCase;

/**
 * The Roles & Permissions page describes authorisation that lives in
 * middleware, so the risk is drift: a fourth role, or a capability that
 * silently has no verdict for one of the roles. These pin both.
 */
class RoleCatalogTest extends TestCase
{
    public function test_the_role_list_matches_the_validation_rule_users_are_created_with(): void
    {
        $rules = (new StoreUserRequest)->rules();

        // The rule is Rule::in([...]); its string form is `in:"admin","user",...`.
        $rule = collect($rules['role'])->first(fn ($r) => str_contains((string) $r, 'in:'));

        $allowed = collect(explode(',', str_replace('in:', '', (string) $rule)))
            ->map(fn ($v) => trim($v, '"\''))
            ->sort()
            ->values()
            ->all();

        $catalog = collect(RoleCatalog::ROLES)->sort()->values()->all();

        $this->assertSame($allowed, $catalog,
            'A role exists in the app that the Roles & Permissions page does not describe.');
    }

    public function test_every_role_definition_matches_a_role_key(): void
    {
        $keys = array_column(RoleCatalog::roles(), 'key');

        $this->assertSame(RoleCatalog::ROLES, $keys);

        foreach (RoleCatalog::roles() as $role) {
            foreach (['label', 'icon', 'tone', 'summary', 'scope'] as $field) {
                $this->assertNotEmpty($role[$field], "Role [{$role['key']}] has no {$field}.");
            }
        }
    }

    public function test_every_capability_has_a_verdict_for_every_role(): void
    {
        $missing = [];

        foreach (RoleCatalog::capabilities() as $group => $rows) {
            $this->assertNotEmpty($rows, "Capability group [{$group}] is empty.");

            foreach ($rows as $row) {
                $this->assertNotEmpty($row['by'],
                    "Capability [{$row['label']}] does not name the code that enforces it.");

                foreach (RoleCatalog::ROLES as $role) {
                    $verdict = $row['verdicts'][$role] ?? null;

                    if (! in_array($verdict, [RoleCatalog::FULL, RoleCatalog::PARTIAL, RoleCatalog::NONE], true)) {
                        $missing[] = "{$group} / {$row['label']} / {$role}";
                    }
                }
            }
        }

        $this->assertSame([], $missing,
            "A blank cell in the matrix reads as 'refused' but means 'nobody decided':\n  ".implode("\n  ", $missing));
    }

    public function test_a_limit_note_only_appears_where_the_verdict_is_partial(): void
    {
        $offenders = [];

        foreach (RoleCatalog::capabilities() as $group => $rows) {
            foreach ($rows as $row) {
                foreach ($row['notes'] ?? [] as $role => $note) {
                    if (($row['verdicts'][$role] ?? null) !== RoleCatalog::PARTIAL) {
                        $offenders[] = "{$group} / {$row['label']} / {$role}";
                    }
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n  ", $offenders));
    }

    public function test_admin_is_granted_every_capability(): void
    {
        // Admin is the unrestricted role. If a capability ever refuses Admin,
        // that is either a real new restriction or a typo in the matrix — both
        // worth failing over.
        foreach (RoleCatalog::capabilities() as $group => $rows) {
            foreach ($rows as $row) {
                $this->assertSame(RoleCatalog::FULL, $row['verdicts']['admin'],
                    "Admin is not granted [{$group} / {$row['label']}].");
            }
        }
    }

    public function test_the_tally_counts_the_matrix(): void
    {
        $tally = RoleCatalog::tally();
        $rows = collect(RoleCatalog::capabilities())->flatten(1)->count();

        foreach (RoleCatalog::ROLES as $role) {
            $this->assertSame($rows, $tally[$role]['total']);
            $this->assertLessThanOrEqual($rows, $tally[$role]['granted'] + $tally[$role]['partial']);
        }

        $this->assertSame($rows, $tally['admin']['granted'], 'Admin should be granted every row.');
        $this->assertSame(0, $tally['maintenance']['partial'], 'Maintenance holds no partial grants.');
    }
}
