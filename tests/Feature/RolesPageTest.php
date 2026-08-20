<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('roles.index'))->assertOk();
    }

    public function test_user_role_cannot_access_the_roles_page(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $this->get(route('roles.index'))->assertForbidden();
    }

    public function test_maintenance_cannot_access_the_roles_page(): void
    {
        $this->actingAs(User::factory()->maintenance()->create());

        $this->get(route('roles.index'))->assertForbidden();
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        // The base TestCase signs a user in for every test, so a guest case
        // has to log out first.
        auth()->logout();

        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }

    public function test_every_role_and_capability_group_is_listed(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('roles.index'));

        foreach (RoleCatalog::roles() as $role) {
            $response->assertSee($role['label']);
            $response->assertSee($role['scope']);
        }

        foreach (array_keys(RoleCatalog::capabilities()) as $group) {
            $response->assertSee($group);
        }
    }

    public function test_it_counts_the_accounts_holding_each_role(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        User::factory()->count(2)->user()->create();
        User::factory()->maintenance()->create();

        $response = $this->get(route('roles.index'));

        // 1 admin (the signed-in one), 2 users, 1 maintenance.
        $response->assertSee('1 account');
        $response->assertSee('2 accounts');
    }

    public function test_it_names_the_middleware_that_enforces_each_capability(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('roles.index'));

        // The page's whole value is being checkable, so the enforcement
        // column must actually reach the markup.
        $response->assertSee('RestrictDestructiveActions', false);
        $response->assertSee('RestrictMaintenanceRole', false);
        $response->assertSee('role:admin', false);
    }

    public function test_every_verdict_carries_screen_reader_text(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $content = $this->get(route('roles.index'))->getContent();

        $cells = collect(RoleCatalog::capabilities())->flatten(1)->count()
            * count(RoleCatalog::ROLES);

        $labels = substr_count($content, '<span class="sr-only">Granted</span>')
            + substr_count($content, '<span class="sr-only">Limited</span>')
            + substr_count($content, '<span class="sr-only">Refused</span>');

        $this->assertSame($cells, $labels,
            'A verdict cell that is only a coloured glyph is unreadable without sight.');
    }

    public function test_the_page_is_read_only(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $content = $this->get(route('roles.index'))->getContent();

        // Roles are code-defined; a form here would imply otherwise.
        $this->assertStringNotContainsString('<form', substr($content, strpos($content, 'Capability matrix') ?: 0));
    }

    public function test_accounts_and_roles_are_bound_by_one_tab_bar(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach ([route('users.index'), route('roles.index')] as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $response->assertSee('aria-label="Access management"', false);
            $response->assertSee('href="'.route('users.index').'"', false);
            $response->assertSee('href="'.route('roles.index').'"', false);
        }
    }
}
