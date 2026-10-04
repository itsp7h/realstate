<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two restriction middlewares, exercised through a real session.
 *
 * These exist because of a bug the rest of the suite structurally could not
 * see. RestrictDestructiveActions and RestrictScopedRoles were registered
 * with $middleware->append(), which is the GLOBAL stack — it runs before
 * StartSession, so $request->user() there is always null on a browser request.
 * The consequences were opposite and both wrong:
 *
 *   • RestrictDestructiveActions failed CLOSED: a null user cannot delete, so
 *     every DELETE in the application answered 403, including an admin's.
 *   • RestrictScopedRoles failed OPEN: a null user matches no confined role,
 *     so those roles' confinement never applied to a real request at all.
 *
 * Every other test in the suite used actingAs(), which sets the user on the
 * guard directly and therefore resolves even before a session exists — so the
 * suite was green while both guards were broken in the browser.
 *
 * ── AND HERE IS THE UNCOMFORTABLE PART ─────────────────────────────────────
 * Signing in through the real login route, as the cases below do, does NOT
 * reproduce the bug either. Verified by putting the global registration back:
 * all four behavioural tests still passed. A feature test reuses one
 * application instance across requests, so the auth guard keeps the user it
 * resolved during the login request and $request->user() answers from memory
 * no matter where in the stack it is asked.
 *
 * So the behavioural cases below document the intended behaviour, and are
 * worth having for that — but the tripwire for THIS class of bug is the last
 * test, which asserts the registration itself. The bug was found in a browser,
 * and a browser is the only place it shows.
 */
class RestrictionMiddlewareOrderTest extends TestCase
{
    use RefreshDatabase;

    /** Sign in through the real login route rather than actingAs(). */
    private function signIn(User $user): void
    {
        auth()->logout();

        $this->post(route('login.attempt'), [
            'login'    => $user->email,
            'password' => 'known-password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    private function user(string $role): User
    {
        return User::factory()->{$role}()->create(['password' => bcrypt('known-password')]);
    }

    public function test_an_admin_can_delete_through_a_real_session(): void
    {
        $this->signIn($this->user('admin'));
        $tenant = Tenant::create(['name' => 'Disposable Tenant', 'tenant_type' => 'individual']);

        $this->delete(route('tenants.destroy', $tenant))->assertRedirect();

        // The symptom of the ordering bug: this used to be a 403 and the row
        // stayed put, for an administrator.
        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_a_user_role_still_cannot_delete_through_a_real_session(): void
    {
        $this->signIn($this->user('user'));
        $tenant = Tenant::create(['name' => 'Protected Tenant', 'tenant_type' => 'individual']);

        $this->delete(route('tenants.destroy', $tenant))->assertForbidden();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_the_maintenance_role_is_confined_through_a_real_session(): void
    {
        $this->signIn($this->user('maintenance'));

        // Failed open before: this answered 200 in a browser.
        $this->get(route('buildings.index'))->assertForbidden();
        $this->get(route('invoices.index'))->assertForbidden();

        // …while its own module and the paths on the allowlist stay reachable.
        $this->get(route('maintenance.index'))->assertOk();
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_the_maintenance_role_still_cannot_delete_through_a_real_session(): void
    {
        $this->signIn($this->user('maintenance'));
        $tenant = Tenant::create(['name' => 'Protected Tenant', 'tenant_type' => 'individual']);

        $this->delete(route('tenants.destroy', $tenant))->assertForbidden();
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_the_accountant_role_is_confined_to_the_ledger_and_the_reports(): void
    {
        $this->signIn($this->user('accountant'));

        // Its own half of the app.
        $this->get(route('invoices.index'))->assertOk();
        $this->get(route('payments.index'))->assertOk();
        $this->get(route('ewa-bills.index'))->assertOk();
        $this->get(route('expenses.index'))->assertOk();
        $this->get(route('revenues.index'))->assertOk();
        $this->get(route('reports.index'))->assertOk();
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();

        // The portfolio is not, including the read-only pages.
        $this->get(route('buildings.index'))->assertForbidden();
        $this->get(route('property-units.index'))->assertForbidden();
        $this->get(route('tenants.index'))->assertForbidden();
        $this->get(route('lease-contracts.index'))->assertForbidden();

        // Nor is the module it has no part in, nor configuration, nor admin.
        $this->get(route('maintenance.index'))->assertForbidden();
        $this->get(route('form-configs.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('data.index'))->assertForbidden();
    }

    /**
     * The payer autocomplete on the invoice form is the one portfolio path an
     * Accountant may call, and it must not drag the rest of the tenant routes
     * in with it — the allowlist matches on path, not on method.
     */
    public function test_the_accountant_reaches_the_tenant_lookup_but_not_the_tenant_records(): void
    {
        $this->signIn($this->user('accountant'));
        $tenant = Tenant::create(['name' => 'Acme Holdings', 'tenant_type' => 'individual']);

        $this->get(route('tenants.search', ['q' => 'Acme']))->assertOk();

        $this->get(route('tenants.show', $tenant))->assertForbidden();
        $this->get(route('tenants.edit', $tenant))->assertForbidden();
        $this->put(route('tenants.update', $tenant), ['name' => 'Renamed'])->assertForbidden();
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Acme Holdings']);
    }

    public function test_the_accountant_role_cannot_delete_through_a_real_session(): void
    {
        $this->signIn($this->user('accountant'));
        $tenant = Tenant::create(['name' => 'Protected Tenant', 'tenant_type' => 'individual']);

        $this->delete(route('tenants.destroy', $tenant))->assertForbidden();
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    /**
     * The actual tripwire. Proven to fail against the old registration, which
     * is more than can be said for the four cases above.
     */
    public function test_the_guards_are_registered_on_the_web_group_not_the_global_stack(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertMatchesRegularExpression(
            '/\$middleware->web\(\s*append:/',
            $bootstrap,
            'The restriction middlewares must be appended to the web group; the global stack runs before StartSession and sees a null user.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\$middleware->append\(\s*\\\\?App\\\\Http\\\\Middleware\\\\Restrict/',
            $bootstrap,
            'A restriction middleware is back on the global stack, where $request->user() is null.'
        );
    }
}
