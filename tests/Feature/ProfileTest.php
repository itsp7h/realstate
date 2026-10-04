<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Self-service account editing.
 *
 * The users resource is Admin-only, so before this a User or Maintenance
 * account could not change its own name, email or password — the only route
 * was to ask an administrator.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::factory()->{$role}()->create([
            'name'     => 'Original Name',
            'email'    => 'original@example.com',
            'password' => bcrypt('current-password'),
        ]);
    }

    public function test_every_role_can_open_its_own_profile(): void
    {
        foreach (['admin', 'user', 'maintenance'] as $role) {
            $this->actingAs(User::factory()->{$role}()->create());

            $this->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('Your profile');
        }
    }

    public function test_a_guest_cannot(): void
    {
        auth()->logout();

        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_the_account_menu_links_to_it(): void
    {
        $this->actingAs($this->user());

        // The answer to "where do I change my details" has to be where people
        // already look for it.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('profile.edit').'"', false)
            ->assertSee('Your profile');
    }

    public function test_a_user_can_change_their_name_and_email(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.update'), ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_a_change_is_recorded_in_the_audit_log(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.update'), ['name' => 'New Name', 'email' => $user->email]);

        $log = AuditLog::where('action', 'profile_updated')->sole();
        $this->assertSame($user->id, (int) $log->entity_id);
        $this->assertSame(['name'], $log->changes['fields']);
    }

    public function test_saving_without_changing_anything_records_nothing(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.update'), ['name' => $user->name, 'email' => $user->email]);

        $this->assertSame(0, AuditLog::where('action', 'profile_updated')->count());
    }

    public function test_a_user_cannot_take_a_name_or_email_already_in_use(): void
    {
        User::factory()->user()->create(['name' => 'Taken Name', 'email' => 'taken@example.com']);
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.update'), ['name' => 'Taken Name', 'email' => $user->email])
            ->assertSessionHasErrors('name');
        $this->put(route('profile.update'), ['name' => $user->name, 'email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertSame('Original Name', $user->fresh()->name);
    }

    public function test_keeping_your_own_name_is_not_a_collision_with_yourself(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        // users.name is unique, so the rule has to ignore the row it is on.
        $this->put(route('profile.update'), ['name' => $user->name, 'email' => 'moved@example.com'])
            ->assertSessionHasNoErrors();
    }

    /**
     * The one that matters: self-service must not be a way around the
     * Admin-only users resource.
     */
    public function test_a_user_cannot_promote_themselves(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.update'), [
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => 'admin',
        ]);

        $this->assertSame('user', $user->fresh()->role);
    }

    // ── Password ─────────────────────────────────────────────────────────────

    public function test_a_user_can_change_their_password(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.password'), [
            'current_password'      => 'current-password',
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_the_new_password_signs_in(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.password'), [
            'current_password'      => 'current-password',
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        auth()->logout();
        $this->post(route('login.attempt'), ['login' => $user->email, 'password' => 'a-brand-new-password']);

        $this->assertAuthenticated();
    }

    public function test_the_current_password_must_be_right(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        // Otherwise an unlocked screen is a password change.
        $this->put(route('profile.password'), [
            'current_password'      => 'not-my-password',
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('current-password', $user->fresh()->password));
    }

    public function test_the_confirmation_must_match(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.password'), [
            'current_password'      => 'current-password',
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'something-else',
        ])->assertSessionHasErrors('password');
    }

    public function test_a_short_or_unchanged_password_is_refused(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.password'), [
            'current_password'      => 'current-password',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->put(route('profile.password'), [
            'current_password'      => 'current-password',
            'password'              => 'current-password',
            'password_confirmation' => 'current-password',
        ])->assertSessionHasErrors('password');
    }

    public function test_a_password_change_is_recorded(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->put(route('profile.password'), [
            'current_password'      => 'current-password',
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        $log = AuditLog::where('action', 'password_changed')->sole();
        $this->assertSame($user->id, (int) $log->entity_id);
        $this->assertStringNotContainsString('a-brand-new-password', json_encode($log->toArray()));
    }

    public function test_the_maintenance_role_reaches_its_profile_despite_the_allowlist(): void
    {
        // RestrictScopedRoles is an opt-in allowlist, so a path that is not
        // named is refused — including, before this, the role's own account.
        $this->actingAs(User::factory()->maintenance()->create(['password' => bcrypt('current-password')]));

        $this->get(route('profile.edit'))->assertOk();
        $this->put(route('profile.password'), [
            'current_password'      => 'current-password',
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasNoErrors();
    }
}
