<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AttentionFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The bell in the top bar was a button that did nothing. These pin what it
 * counts, who sees which items, and that every item points somewhere the
 * signed-in role can actually open.
 */
class AttentionFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function feed(): AttentionFeed
    {
        Cache::flush();

        return app(AttentionFeed::class);
    }

    // The app has no model factories beyond UserFactory, so these mirror the
    // helpers the existing module tests use.
    private function makeInvoice(array $overrides = []): Invoice
    {
        $tenant = Tenant::create(['name' => 'Feed Tenant', 'tenant_type' => 'individual']);
        $lines  = [['property_name' => 'Test Property', 'unit' => 'Flat 1', 'amount' => 100.000]];

        $invoice = new Invoice(array_merge([
            'invoice_number' => 'INV-FEED-'.uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Test Property',
            'unit'           => 'Flat 1',
            'type'           => 'rent',
            'lines'          => $lines,
            'vat_rate'       => 0,
            'invoice_date'   => '2026-03-01',
            'status'         => 'issued',
        ], $overrides));
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    private function makeLease(string $endDate): LeaseContract
    {
        return LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA-'.uniqid(),
            'tenant_name'        => 'Feed Tenant',
            'property_name'      => 'Test Property',
            'unit'               => 'Flat 1',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => $endDate,
        ]);
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

    public function test_it_is_empty_when_nothing_needs_attention(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame([], $this->feed()->items($admin));
        $this->assertSame(0, $this->feed()->count($admin));
    }

    public function test_a_guest_gets_nothing(): void
    {
        $this->assertSame([], $this->feed()->items(null));
        $this->assertSame(0, $this->feed()->count(null));
    }

    public function test_it_counts_overdue_invoices(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeInvoice(['status' => 'overdue']);
        $this->makeInvoice(['status' => 'overdue']);
        $this->makeInvoice(['status' => 'paid']);

        $items = collect($this->feed()->items($admin))->keyBy('key');

        $this->assertSame(2, $items['overdue-invoices']['count']);
        $this->assertStringContainsString('status=overdue', $items['overdue-invoices']['url']);
    }

    public function test_it_counts_leases_ending_inside_the_window(): void
    {
        $admin = User::factory()->admin()->create();

        $this->makeLease(Carbon::today()->addDays(5)->toDateString());
        $this->makeLease(Carbon::today()->addDays(AttentionFeed::EXPIRING_DAYS)->toDateString());
        // Outside the window on both sides.
        $this->makeLease(Carbon::today()->addDays(AttentionFeed::EXPIRING_DAYS + 10)->toDateString());
        $this->makeLease(Carbon::today()->subDay()->toDateString());

        $items = collect($this->feed()->items($admin))->keyBy('key');

        $this->assertSame(2, $items['expiring-leases']['count']);
    }

    public function test_it_counts_maintenance_awaiting_a_decision(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeRequest('waiting_approval');
        $this->makeRequest('waiting_supervisor');
        $this->makeRequest('completed');

        $items = collect($this->feed()->items($admin))->keyBy('key');

        $this->assertSame(2, $items['maintenance-awaiting']['count']);
    }

    public function test_the_count_is_the_sum_of_the_items(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeInvoice(['status' => 'overdue']);
        $this->makeInvoice(['status' => 'overdue']);
        $this->makeInvoice(['status' => 'overdue']);
        $this->makeRequest('open');

        $this->assertSame(4, $this->feed()->count($admin));
    }

    public function test_the_maintenance_role_only_sees_maintenance_items(): void
    {
        $maintenance = User::factory()->maintenance()->create();
        $this->makeInvoice(['status' => 'overdue']);
        $this->makeLease(Carbon::today()->addDays(3)->toDateString());
        $this->makeRequest('open');

        $keys = collect($this->feed()->items($maintenance))->pluck('key')->all();

        // The accounting items would be 403 for this role, so offering them
        // would be offering a dead link.
        $this->assertSame(['maintenance-unassessed'], $keys);
    }

    public function test_every_item_points_at_a_route_the_role_can_open(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeInvoice(['status' => 'overdue']);
        $this->makeLease(Carbon::today()->addDays(3)->toDateString());
        $this->makeRequest('open');

        foreach ($this->feed()->items($admin) as $item) {
            $this->actingAs($admin)->get($item['url'])->assertOk();
        }
    }

    public function test_the_bell_renders_its_count_in_the_shell(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeInvoice(['status' => 'overdue']);
        $this->makeInvoice(['status' => 'overdue']);
        Cache::flush();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('shell-bell-badge', false);
        $response->assertSee('>2</span>', false);
        $response->assertSee('2 overdue invoices');
    }

    public function test_the_bell_says_so_when_there_is_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('shell-bell-badge', false);
        $response->assertSee('Nothing needs your attention.');
    }

    /**
     * The cache saves the queries, not a stale number.
     *
     * This used to assert the opposite — that a write left the old figure
     * standing until the minute expired or someone remembered to call
     * forget(). That was the trade when nothing invalidated the cache; it is
     * also how a badge ends up disagreeing with the page under it, so
     * AppServiceProvider now hooks the three source models and the count
     * refreshes on the write itself.
     */
    public function test_a_write_refreshes_the_cached_feed(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();

        $this->assertSame(0, app(AttentionFeed::class)->count($admin));

        $this->makeInvoice(['status' => 'overdue']);

        $this->assertSame(1, app(AttentionFeed::class)->count($admin));
    }

    public function test_the_feed_is_cached_and_can_be_forgotten(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::flush();

        $this->assertSame(0, app(AttentionFeed::class)->count($admin));

        // Written straight to the table, so no model event fires and the
        // cached figure is genuinely the stale one — which is what forget()
        // is for.
        \Illuminate\Support\Facades\Cache::put(
            "attention-feed:{$admin->role}",
            [['key' => 'stub', 'count' => 7]],
            60
        );

        $this->assertSame(7, app(AttentionFeed::class)->count($admin));

        AttentionFeed::forget();

        $this->assertSame(0, app(AttentionFeed::class)->count($admin));
    }
}
