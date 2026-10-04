<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The account form is where a role is actually handed out, and it had the role
 * list written into it by hand — three <option>s in the form, three more in the
 * index filter, and a sentence under the field describing them. All four now
 * come from RoleCatalog, so these cases assert the wiring rather than the
 * literals: a role added to the catalog has to appear in the UI, and a role
 * that is not in the catalog has to be refused.
 */
class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_the_form_offers_every_role_in_the_catalog(): void
    {
        $this->actingAs($this->admin());

        $response = $this->get(route('users.create'))->assertOk();

        foreach (RoleCatalog::roles() as $role) {
            $response->assertSee('value="'.$role['key'].'"', false);
            $response->assertSee($role['label']);
        }
    }

    public function test_the_index_filter_offers_every_role_in_the_catalog(): void
    {
        $this->actingAs($this->admin());

        $response = $this->get(route('users.index'))->assertOk();

        foreach (RoleCatalog::roles() as $role) {
            $response->assertSee('value="'.$role['key'].'"', false);
        }
    }

    public function test_the_edit_form_preselects_the_account_s_role(): void
    {
        $this->actingAs($this->admin());
        $accountant = User::factory()->accountant()->create();

        $this->get(route('users.edit', $accountant))
            ->assertOk()
            ->assertSee('value="accountant" selected', false);
    }

    public function test_an_accountant_account_can_be_created(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('users.store'), [
            'name'                  => 'Ledger Keeper',
            'email'                 => 'ledger@example.com',
            'role'                  => 'accountant',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'ledger@example.com',
            'role'  => 'accountant',
        ]);
    }

    public function test_a_role_outside_the_catalog_is_refused(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('users.store'), [
            'name'                  => 'Invented Role',
            'email'                 => 'invented@example.com',
            'role'                  => 'superuser',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'invented@example.com']);
    }

    public function test_an_existing_account_can_be_moved_to_the_new_role(): void
    {
        $this->actingAs($this->admin());
        $staff = User::factory()->user()->create();

        $this->put(route('users.update', $staff), [
            'name'  => $staff->name,
            'email' => $staff->email,
            'role'  => 'accountant',
        ])->assertRedirect(route('users.index'));

        $this->assertSame('accountant', $staff->fresh()->role);
    }

    public function test_the_role_field_carries_the_selected_role_s_own_summary(): void
    {
        $this->actingAs($this->admin());
        $accountant = User::factory()->accountant()->create();

        // The hint under the field is the catalog's sentence, not a hand-written
        // one that can fall out of step with the Roles page.
        $summary = collect(RoleCatalog::roles())->firstWhere('key', 'accountant')['summary'];

        $this->get(route('users.edit', $accountant))->assertSee($summary, false);
    }
}
