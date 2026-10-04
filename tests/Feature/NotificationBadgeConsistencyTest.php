<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AttentionFeed;
use App\Support\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * One number, everywhere.
 *
 * The bell badge read 1 on the dashboard and 2 on Buildings, because the
 * dashboard derived its own alert list from $portfolioMetrics — counting
 * categories where AttentionFeed counts records, off a different definition
 * of "overdue", and without the feed's role filter. These pin the fix: the
 * feed is the only place the number is computed, every header renders that
 * same number, and a write refreshes it for every role at once.
 */
class NotificationBadgeConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeOverdueInvoice(): Invoice
    {
        $tenant = Tenant::create(['name' => 'Badge Tenant', 'tenant_type' => 'individual']);

        $invoice = new Invoice([
            'invoice_number' => 'INV-BADGE-'.uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Test Property',
            'unit'           => 'Flat 1',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'unit' => 'Flat 1', 'amount' => 100.000]],
            'vat_rate'       => 0,
            'invoice_date'   => '2026-03-01',
            'status'         => 'overdue',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    private function makeRequest(string $status): MaintenanceRequest
    {
        return MaintenanceRequest::create([
            'date'               => '2026-05-21',
            'property'           => 'Tower A',
            'tenant'             => 'Ahmed Ali',
            'flat'               => '3B',
            'contact_no'         => '+973 3300 0000',
            'available_datetime' => '2026-05-22 10:00:00',
            'apartment_status'   => 'occupied',
            'status'             => $status,
        ]);
    }

    /** Every parameterless page that renders the shell, so the sweep is not a fixed list. */
    private function badgeRoutes(): array
    {
        $names = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name
                || in_array($name, ['login', 'logout'], true)
                || ! in_array('GET', $route->methods(), true)
                || str_contains($route->uri(), '{')
                || str_starts_with($name, 'generated::')
                || str_starts_with($name, 'export.')
                || str_ends_with($name, '.export')
                || str_ends_with($name, '.download')
                || str_contains($name, '.pdf')
                || str_contains($name, 'template')
                || str_contains($name, 'search')) {
                continue;
            }

            $names[] = $name;
        }

        sort($names);

        return $names;
    }

    /** How many badges a page renders, and what each of them says. */
    private function badgesOn(string $html): array
    {
        // Attribute-tolerant: the badge also carries data-bell-count, which is
        // how the layout's pop-on-increment helper finds it without naming the
        // class (see the audit at the bottom of this file).
        preg_match_all('/<span class="shell-bell-badge"[^>]*>([^<]*)<\/span>/', $html, $m);

        return array_map('trim', $m[1]);
    }

    public function test_every_screen_shows_the_same_count(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        // Two overdue invoices and one unassessed request: three records
        // across two categories. The old dashboard would have said 2.
        $this->makeOverdueInvoice();
        $this->makeOverdueInvoice();
        $this->makeRequest('open');

        $expected = (string) app(AttentionFeed::class)->count(auth()->user());
        $this->assertSame('3', $expected, 'The feed counts records, not categories.');

        $seen = [];

        foreach ($this->badgeRoutes() as $name) {
            $response = $this->get(route($name));

            if ($response->getStatusCode() !== 200) {
                continue;
            }

            foreach ($this->badgesOn($response->getContent()) as $badge) {
                $seen[$name][] = $badge;
                $this->assertSame($expected, $badge,
                    "{$name} renders a bell badge of {$badge}; every screen must show {$expected}.");
            }
        }

        $this->assertArrayHasKey('dashboard', $seen, 'The dashboard rendered no badge at all.');
        $this->assertGreaterThan(5, count($seen), 'Suspiciously few screens rendered a badge.');
    }

    public function test_the_dashboard_agrees_with_the_other_screens(): void
    {
        // The exact reported symptom: 1 here, 2 there.
        $this->actingAs(User::factory()->admin()->create());
        $this->makeOverdueInvoice();
        $this->makeOverdueInvoice();

        $dashboard = $this->badgesOn($this->get(route('dashboard'))->getContent());
        $buildings = $this->badgesOn($this->get(route('buildings.index'))->getContent());

        $this->assertNotEmpty($dashboard);
        $this->assertNotEmpty($buildings);
        $this->assertSame(array_unique($dashboard), array_unique($buildings));
        $this->assertSame('2', $dashboard[0]);
    }

    public function test_the_badge_is_absent_at_zero(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['dashboard', 'buildings.index'] as $name) {
            $html = $this->get(route($name))->getContent();
            $this->assertSame([], $this->badgesOn($html), "{$name} drew a badge with nothing to show.");
        }
    }

    public function test_it_caps_at_99_plus(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        // One item carrying a count of 100 — the badge caps on the total, not
        // on the number of rows.
        for ($i = 0; $i < 100; $i++) {
            $this->makeRequest('open');
        }

        $this->assertSame(100, app(AttentionFeed::class)->count(auth()->user()));

        foreach (['dashboard', 'buildings.index'] as $name) {
            $this->assertSame(['99+'], array_unique($this->badgesOn($this->get(route($name))->getContent())),
                "{$name} did not cap its badge at 99+.");
        }
    }

    public function test_a_write_refreshes_the_count_for_every_role(): void
    {
        $admin = User::factory()->admin()->create();
        $feed = app(AttentionFeed::class);

        $this->assertSame(0, $feed->count($admin));

        // Warms the cache for a second role too, so a stale entry for either
        // one would survive the write and be caught below.
        $accountant = User::factory()->create(['role' => 'accountant']);
        $this->assertSame(0, $feed->count($accountant));

        $this->makeOverdueInvoice();

        $this->assertSame(1, $feed->count($admin), 'The admin badge went stale after a write.');
        $this->assertSame(1, $feed->count($accountant), 'The accountant badge went stale after a write.');

        // ...and a write that resolves the item takes it back down.
        Invoice::query()->each(fn (Invoice $invoice) => $invoice->update(['status' => 'paid']));
        $this->assertSame(0, $feed->count($admin));
    }

    public function test_forget_covers_every_role_in_the_catalog(): void
    {
        foreach (RoleCatalog::ROLES as $role) {
            Cache::put("attention-feed:{$role}", [['count' => 99]], 60);
        }

        AttentionFeed::forget();

        foreach (RoleCatalog::ROLES as $role) {
            $this->assertFalse(Cache::has("attention-feed:{$role}"),
                "attention-feed:{$role} survived forget(), so that role's badge would stay stale.");
        }
    }

    public function test_deleting_a_source_record_refreshes_the_count(): void
    {
        $admin = User::factory()->admin()->create();
        $feed = app(AttentionFeed::class);

        $request = $this->makeRequest('open');
        $this->assertSame(1, $feed->count($admin));

        $request->delete();
        $this->assertSame(0, $feed->count($admin), 'Deleting the last item left the badge showing it.');
    }

    /**
     * The audit, kept honest. Nothing outside the feed and its one badge
     * partial may compute or hardcode the number.
     */
    public function test_only_one_place_renders_the_badge(): void
    {
        $offenders = [];
        $base = dirname(__DIR__, 2).'/resources/views';

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $rel = str_replace($base.'/', '', $file->getPathname());
            if ($rel === 'partials/bell-badge.blade.php') {
                continue;
            }

            if (str_contains(file_get_contents($file->getPathname()), 'shell-bell-badge')) {
                $offenders[] = $rel;
            }
        }

        $this->assertSame([], $offenders,
            "These views draw the badge themselves instead of including partials.bell-badge:\n  "
            .implode("\n  ", $offenders));
    }

    public function test_no_view_derives_its_own_attention_count(): void
    {
        $offenders = [];
        $base = dirname(__DIR__, 2).'/resources/views';

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            $rel = str_replace($base.'/', '', $file->getPathname());

            // The dashboard's old private count, and the shape of any successor.
            if (preg_match('/\$\w*[Aa]lertCount\s*=/', $contents)
                || str_contains($contents, '$mAlerts')) {
                $offenders[] = $rel;
            }
        }

        $this->assertSame([], $offenders,
            "These views compute their own notification count instead of using \$attentionCount:\n  "
            .implode("\n  ", $offenders));
    }

    public function test_expiring_leases_still_reach_the_badge(): void
    {
        // Guards the third source model's hook, which the other tests don't touch.
        $admin = User::factory()->admin()->create();
        $feed = app(AttentionFeed::class);

        $this->assertSame(0, $feed->count($admin));

        LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA-BADGE-'.uniqid(),
            'tenant_name'        => 'Badge Tenant',
            'property_name'      => 'Test Property',
            'unit'               => 'Flat 1',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => now()->addDays(10)->toDateString(),
        ]);

        $this->assertSame(1, $feed->count($admin));
    }
}
