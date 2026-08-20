<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The desktop shell in resources/views/layouts/admin.blade.php, per
 * DASHBOARD-SPEC.md §1: one 228px navy sidebar, one 80px top bar, and the
 * page header inside the content column. These tests pin the two things a
 * layout change is most likely to break silently — the nav model's route
 * names, and the ≤768px mobile layer still being present underneath.
 */
class DesktopShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_renders_the_three_groups_in_order(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder(['OVERVIEW', 'PORTFOLIO', 'CONFIGURATION'], false);
    }

    public function test_sidebar_renders_every_page_in_order(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Dashboard', 'Activity Feed',
            'Buildings', 'Floors', 'Units', 'Tenants', 'Leases',
            'Bills &amp; Payments',
            'Invoices', 'Payments', 'EWA Bills', 'Expenses', 'Revenue',
            'Maintenance', 'Reports',
            'Users', 'Roles &amp; Permissions',
            'Settings', 'Forms &amp; Templates', 'Import &amp; Export',
            'Mail Settings', 'Error Log',
        ], false);
    }

    public function test_bills_and_payments_is_one_item_with_a_submenu(): void
    {
        $html = $this->get(route('dashboard'))->getContent();
        $sidebar = Str::between($html, '<aside class="shell-sidebar"', '</aside>');

        // One row, not five.
        $this->assertSame(1, substr_count($sidebar, 'Bills &amp; Payments'));
        $this->assertStringContainsString('<details class="shell-navgroup"', $sidebar);

        // …that holds the five real destinations.
        foreach (['invoices.index', 'payments.index', 'ewa-bills.index', 'expenses.index', 'revenues.index'] as $name) {
            $this->assertStringContainsString('href="'.route($name).'"', $sidebar);
        }
    }

    public function test_the_submenu_opens_on_the_page_it_contains(): void
    {
        $html = $this->get(route('payments.index'))->getContent();
        $sidebar = Str::between($html, '<aside class="shell-sidebar"', '</aside>');

        $this->assertStringContainsString('<details class="shell-navgroup" open>', $sidebar);
        $this->assertMatchesRegularExpression(
            '/class="shell-subitem is-active[^"]*"[^>]*aria-current="page"/',
            $sidebar
        );
    }

    /**
     * The regression this guards: a page with no sidebar entry is a page
     * users cannot find. Every named top-level GET route that renders a
     * page must be reachable from the sidebar.
     */
    public function test_no_page_is_missing_from_the_sidebar(): void
    {
        $html = $this->get(route('dashboard'))->getContent();

        $expected = [
            'dashboard', 'admin.audit-log', 'buildings.index', 'floors.global',
            'property-units.index', 'tenants.index', 'lease-contracts.index',
            'invoices.index', 'payments.index', 'ewa-bills.index', 'expenses.index',
            'revenues.index', 'maintenance.index', 'reports.index', 'users.index',
            'form-configs.index', 'data.index', 'settings.azure-mail.edit',
            'admin.error-log',
        ];

        $sidebar = Str::between($html, '<aside class="shell-sidebar"', '</aside>');

        foreach ($expected as $name) {
            $this->assertStringContainsString(
                'href="'.route($name).'"',
                $sidebar,
                "Route [{$name}] has no sidebar entry."
            );
        }
    }

    /**
     * The nav model resolves every destination through Route::has(), and this
     * is the invariant that depends on: an item either points at a route that
     * really exists, or it renders inert. A fabricated href — a hand-written
     * path, or a '#' placeholder — is the failure mode, because it looks like
     * a working link and 404s.
     *
     * Written against the mechanism rather than a named example: Roles &
     * Permissions used to be the missing route this test pinned, and it now
     * has one, which is exactly the kind of change that should not leave a
     * test with nothing to say.
     */
    public function test_every_sidebar_destination_is_a_real_route_or_visibly_inert(): void
    {
        $html = $this->get(route('dashboard'))->getContent();
        $sidebar = Str::between($html, '<aside class="shell-sidebar"', '</aside>');

        $urls = collect(Route::getRoutes())
            ->filter(fn ($r) => $r->getName() && in_array('GET', $r->methods(), true))
            ->map(fn ($r) => '/'.ltrim($r->uri(), '/'))
            ->push('/')
            ->unique()
            ->all();

        preg_match_all('/<a\s[^>]*class="shell-(?:navitem|subitem)[^"]*"[^>]*>/', $sidebar, $anchors);
        $this->assertNotEmpty($anchors[0], 'No sidebar items rendered at all.');

        foreach ($anchors[0] as $tag) {
            $hasHref = (bool) preg_match('/\shref="([^"]*)"/', $tag, $m);

            if (str_contains($tag, 'is-disabled')) {
                // An item with no route must not offer a link, and must say so.
                $this->assertFalse($hasHref && $m[1] !== '', "Disabled nav item carries an href: {$tag}");
                $this->assertStringContainsString('aria-disabled="true"', $tag);
                $this->assertStringContainsString('title="Coming soon"', $tag);

                continue;
            }

            $this->assertTrue($hasHref, "Enabled nav item has no href: {$tag}");
            $this->assertNotSame('#', $m[1], "Placeholder href in the sidebar: {$tag}");
            $this->assertContains(
                parse_url($m[1], PHP_URL_PATH),
                $urls,
                "Sidebar points at [{$m[1]}], which is not a registered GET route."
            );
        }
    }

    public function test_the_current_page_is_the_only_active_nav_item(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertSame(
            1,
            substr_count($content, 'shell-navitem is-active') + substr_count($content, 'shell-subitem is-active')
        );
        $response->assertSee('aria-current="page"', false);
    }

    public function test_a_maintenance_user_only_sees_their_own_module(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'maintenance']));

        $response = $this->get(route('maintenance.index'));

        $response->assertOk();
        $response->assertSee('Maintenance');
        $response->assertDontSee('CONFIGURATION', false);
        $response->assertDontSee('shell-navitem-label">Buildings', false);
        $response->assertDontSee('Bills &amp; Payments', false);
    }

    public function test_the_mobile_layer_is_untouched_underneath_the_shell(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        // The ≤768px chrome the shell hides but must not remove: the drawer,
        // its backdrop, the More sheet and the bottom tab bar.
        $response->assertSee('id="sidebar"', false);
        $response->assertSee('id="sidebarBackdrop"', false);
        $response->assertSee('id="moreSheet"', false);
        $response->assertSee('id="bottomTabbar"', false);
        $response->assertSee('id="menuBtn"', false);
    }

    public function test_the_command_palette_lists_the_same_destinations(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="command-palette"', false);
        // ⌘K is the fast path to the same pages the sidebar lists.
        foreach (['Floors', 'Payments', 'EWA bills', 'Expenses', 'Revenue'] as $page) {
            $response->assertSee('<span class="palette-row-title">'.$page.'</span>', false);
        }
    }
}
