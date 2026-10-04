<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⌘K used to filter a hardcoded list of destinations while the top bar
 * advertised "Search buildings, tenants, units…". These pin the real search.
 */
class PaletteSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeBuilding(array $overrides = []): Building
    {
        return Building::create(array_merge([
            'property_name' => 'Marina Bay Tower',
            'property_code' => 'MBT',
            'area'          => 'Seef',
        ], $overrides));
    }

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name'        => 'Yousif Kanoo',
            'tenant_type' => 'individual',
            'phone'       => '+973 3300 1234',
        ], $overrides));
    }

    public function test_a_short_query_returns_nothing_rather_than_everything(): void
    {
        $this->makeBuilding();

        $this->getJson(route('search', ['q' => 'M']))
            ->assertOk()
            ->assertJson(['groups' => []]);
    }

    public function test_it_finds_a_building_by_name(): void
    {
        $building = $this->makeBuilding();

        $response = $this->getJson(route('search', ['q' => 'Marina']));

        $response->assertOk();
        $response->assertJsonPath('groups.0.label', 'Buildings');
        $response->assertJsonPath('groups.0.items.0.title', 'Marina Bay Tower');
        $response->assertJsonPath('groups.0.items.0.url', route('buildings.show', $building));
    }

    public function test_it_finds_a_building_by_code(): void
    {
        $this->makeBuilding();

        $this->getJson(route('search', ['q' => 'MBT']))
            ->assertOk()
            ->assertJsonPath('groups.0.items.0.title', 'Marina Bay Tower');
    }

    public function test_it_finds_a_tenant_by_name_and_by_phone(): void
    {
        $this->makeTenant();

        foreach (['Kanoo', '3300 1234'] as $q) {
            $groups = $this->getJson(route('search', ['q' => $q]))->json('groups');
            $labels = array_column($groups, 'label');

            $this->assertContains('Tenants', $labels, "Query [{$q}] found no tenant.");
        }
    }

    public function test_it_finds_a_lease_and_an_invoice(): void
    {
        $tenant = $this->makeTenant();

        LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA-2026-777',
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Marina Bay Tower',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => '2027-01-01',
        ]);

        $invoice = new Invoice([
            'invoice_number' => 'INV-SEARCH-9001',
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Marina Bay Tower',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Marina Bay Tower', 'unit' => 'Flat 1', 'amount' => 100.000]],
            'vat_rate'       => 0,
            'invoice_date'   => '2026-03-01',
            'status'         => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();

        $labels = array_column($this->getJson(route('search', ['q' => 'LA-2026-777']))->json('groups'), 'label');
        $this->assertContains('Leases', $labels);

        $labels = array_column($this->getJson(route('search', ['q' => 'INV-SEARCH-9001']))->json('groups'), 'label');
        $this->assertContains('Invoices', $labels);
    }

    public function test_an_empty_group_is_dropped_rather_than_returned_empty(): void
    {
        $this->makeBuilding();

        $groups = $this->getJson(route('search', ['q' => 'Marina']))->json('groups');

        foreach ($groups as $group) {
            $this->assertNotEmpty($group['items'], "Group [{$group['label']}] came back empty.");
        }
    }

    public function test_nothing_matching_returns_no_groups(): void
    {
        $this->makeBuilding();

        $this->getJson(route('search', ['q' => 'zzzz-no-such-thing']))
            ->assertOk()
            ->assertJson(['groups' => []]);
    }

    public function test_a_wildcard_cannot_widen_the_search(): void
    {
        $this->makeBuilding();

        // A bare % would match every row if it were interpolated raw.
        $this->getJson(route('search', ['q' => '%%']))
            ->assertOk()
            ->assertJson(['groups' => []]);
    }

    public function test_the_maintenance_role_only_searches_maintenance(): void
    {
        $this->makeBuilding();
        MaintenanceRequest::create([
            'date'               => '2026-05-21',
            'property'           => 'Marina Bay Tower',
            'tenant'             => 'Ahmed Ali',
            'flat'               => '3B',
            'contact_no'         => '+973 3300 0000',
            'available_datetime' => '2026-05-22 10:00:00',
            'apartment_status'   => 'occupied',
            'job_order'          => 'JO-MARINA-1',
        ]);

        $groups = $this->actingAs(User::factory()->maintenance()->create())
            ->getJson(route('search', ['q' => 'Marina']))
            ->json('groups');

        $labels = array_column($groups, 'label');
        $this->assertSame(['Maintenance'], $labels);
    }

    public function test_the_accountant_role_searches_invoices_and_not_the_portfolio(): void
    {
        $tenant = $this->makeTenant();
        $this->makeBuilding();
        MaintenanceRequest::create([
            'date'               => '2026-05-21',
            'property'           => 'Marina Bay Tower',
            'tenant'             => 'Ahmed Ali',
            'flat'               => '3B',
            'contact_no'         => '+973 3300 0000',
            'available_datetime' => '2026-05-22 10:00:00',
            'apartment_status'   => 'occupied',
            'job_order'          => 'JO-MARINA-1',
        ]);

        $invoice = new Invoice([
            'invoice_number' => 'INV-MARINA-1',
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Marina Bay Tower',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Marina Bay Tower', 'unit' => 'Flat 1', 'amount' => 100.000]],
            'vat_rate'       => 0,
            'invoice_date'   => '2026-03-01',
            'status'         => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();

        $groups = $this->actingAs(User::factory()->accountant()->create())
            ->getJson(route('search', ['q' => 'Marina']))
            ->json('groups');

        // Every portfolio group links to a show page this role answers 403 on,
        // and Maintenance is outside its scope entirely.
        $this->assertSame(['Invoices'], array_column($groups, 'label'));
    }

    public function test_the_jump_list_offers_a_role_only_what_it_can_open(): void
    {
        $html = $this->actingAs(User::factory()->accountant()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $jump = \Illuminate\Support\Str::between($html, 'data-palette-jump', 'data-palette-results');

        foreach (['Invoices', 'Payments', 'EWA bills', 'Expenses', 'Revenue', 'Report library'] as $row) {
            $this->assertStringContainsString('>'.$row.'<', $jump, "The palette should offer [{$row}].");
        }

        // Ungated before this role existed: a confined account was offered
        // Buildings and got a 403 for taking the offer.
        foreach (['Buildings', 'Floors', 'Units', 'Tenants', 'Leases', 'Maintenance'] as $row) {
            $this->assertStringNotContainsString('>'.$row.'<', $jump, "The palette should not offer [{$row}].");
        }
    }

    public function test_a_guest_cannot_search(): void
    {
        auth()->logout();

        $this->get(route('search', ['q' => 'Marina']))->assertRedirect(route('login'));
    }

    public function test_the_palette_declares_the_endpoint_and_a_results_container(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-palette-results', false);
        $response->assertSee(route('search'), false);
    }
}
