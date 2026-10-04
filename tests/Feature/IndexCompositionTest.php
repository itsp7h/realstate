<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\LeaseContract;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Composition of the index pages, against the design handoff.
 *
 * Two different claims are pinned here, and they pull in opposite directions:
 * the Units list was carrying thirteen columns and needed cutting, while the
 * Buildings cards were missing the two figures the handoff leads with. Both are
 * easy to undo by accident, so both are asserted.
 */
class IndexCompositionTest extends TestCase
{
    use RefreshDatabase;

    private function building(): Building
    {
        return Building::create([
            'property_name'  => 'Marina Bay Tower',
            'property_code'  => 'MBT',
            'property_type'  => 'Residential',
            'land_lord_name' => 'Kanoo Holdings',
            'area'           => 'Seef',
        ]);
    }

    private function unit(Building $building, array $overrides = []): PropertyUnit
    {
        $floor = Floor::firstOrCreate(
            ['building_id' => $building->id, 'floor_name' => 'Floor 1'],
            ['block_name' => 'Block A'],
        );

        return PropertyUnit::create(array_merge([
            'property_name'           => $building->property_name,
            'property_code'           => $building->property_code,
            'unit_name'               => 'Flat 101',
            'building_id'             => $building->id,
            'floor_id'                => $floor->id,
            'unit_type'               => 'Apartment',
            'unit_condition'          => 'Furnished',
            'land_lord_name'          => 'Kanoo Holdings',
            'area_inside'             => 120.5,
            'area_unit'               => 'sqm',
            'rent_per_month'          => 450,
            'security_deposit_amount' => 900,
            'electricity_ac_no'       => 'EA-99887',
            'view'                    => 'Sea view',
        ], $overrides));
    }

    // ── Units: the column trim ───────────────────────────────────────────────

    public function test_the_units_list_keeps_the_columns_that_identify_and_price_a_unit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->unit($this->building());

        $response = $this->get(route('property-units.index'));

        $response->assertOk();
        foreach (['Unit', 'Property', 'Floor / Block', 'Type', 'Area', 'Rent/Month', 'Occupancy'] as $column) {
            $response->assertSee($column);
        }
    }

    public function test_the_units_list_drops_the_columns_that_only_widened_it(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->unit($this->building());

        $content = $this->get(route('property-units.index'))->getContent();
        $head = substr($content, strpos($content, '<thead>') ?: 0, 900);

        // Thirteen columns guaranteed a horizontal scrollbar. These five moved
        // to the unit's own page — asserted below, so this is a move, not a loss.
        foreach (['Land Lord', 'Condition', 'Deposit', 'Elec. A/c', '>View<'] as $gone) {
            $this->assertStringNotContainsString($gone, $head, "Column [{$gone}] is back on the units list.");
        }
    }

    public function test_what_the_units_list_dropped_is_still_on_the_unit_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $unit = $this->unit($this->building());

        $response = $this->get(route('property-units.show', $unit));

        $response->assertOk();
        // The point of the trim: fewer columns, same information available.
        $response->assertSee('Kanoo Holdings');   // land lord
        $response->assertSee('Furnished');        // condition
        $response->assertSee('EA-99887');         // electricity account
        $response->assertSee('Sea view');         // view
    }

    // ── Buildings: occupancy and net ─────────────────────────────────────────

    public function test_a_building_card_leads_with_occupancy_and_net(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $building = $this->building();
        $this->unit($building);

        $response = $this->get(route('buildings.index'));

        $response->assertOk();
        $response->assertSee('% occupied');
        $response->assertSee('Net / mo');
    }

    public function test_occupancy_reflects_the_units_actually_let(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $building = $this->building();
        $let = $this->unit($building, ['unit_name' => 'Flat 101']);
        $this->unit($building, ['unit_name' => 'Flat 102']);

        $tenant = Tenant::create(['name' => 'Yousif Kanoo', 'tenant_type' => 'individual']);
        LeaseContract::create([
            'date'               => Carbon::today()->subMonth()->toDateString(),
            'lease_agreement_no' => 'LA-OCC-1',
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => $building->property_name,
            'unit_id'            => $let->id,
            'lease_start_date'   => Carbon::today()->subMonth()->toDateString(),
            'lease_end_date'     => Carbon::today()->addYear()->toDateString(),
            'rent_per_month'     => 450,
        ]);

        // One of two units let.
        $this->get(route('buildings.index'))->assertOk()->assertSee('50% occupied');
    }

    public function test_a_building_with_no_units_does_not_claim_a_percentage(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->building();

        // 0/0 is not 0% — it is "nothing to report", and inventing a figure
        // there is how a dashboard starts lying.
        $this->get(route('buildings.index'))->assertOk()->assertDontSee('% occupied');
    }

    private function lease(Building $building, PropertyUnit $unit): LeaseContract
    {
        $tenant = Tenant::firstOrCreate(['name' => 'Yousif Kanoo'], ['tenant_type' => 'individual']);

        return LeaseContract::create([
            'date'               => Carbon::today()->subMonth()->toDateString(),
            'lease_agreement_no' => 'LA-NUM-'.uniqid(),
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => $building->property_name,
            'unit_id'            => $unit->id,
            'unit'               => $unit->unit_name,
            'lease_start_date'   => Carbon::today()->subMonth()->toDateString(),
            'lease_end_date'     => Carbon::today()->addYear()->toDateString(),
            'rent_per_month'     => 450,
        ]);
    }

    private function invoice(Tenant $tenant): \App\Models\Invoice
    {
        $invoice = new \App\Models\Invoice([
            'invoice_number' => 'INV-NUM-'.uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Marina Bay Tower',
            'unit'           => 'Flat 101',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Marina Bay Tower', 'unit' => 'Flat 101', 'amount' => 450.000]],
            'vat_rate'       => 0,
            'invoice_date'   => Carbon::today()->toDateString(),
            'status'         => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    // ── Tenants: the tenancy, not the identity ───────────────────────────────

    public function test_the_tenants_list_leads_with_the_tenancy(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $building = $this->building();
        $unit = $this->unit($building);
        $this->lease($building, $unit);

        $response = $this->get(route('tenants.index'));

        $response->assertOk();
        foreach (['Unit', 'Lease ends', 'Balance (BHD)', 'Status'] as $column) {
            $response->assertSee($column);
        }
        $response->assertSee('Flat 101');
    }

    public function test_the_tenants_list_drops_the_identity_only_columns(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $building = $this->building();
        $this->lease($building, $this->unit($building));

        $content = $this->get(route('tenants.index'))->getContent();
        $head = substr($content, strpos($content, '<thead>') ?: 0, 800);

        // All four remain on the tenant's own profile, which the row opens.
        foreach (['ID / CR Number', 'Email', 'Nationality'] as $gone) {
            $this->assertStringNotContainsString($gone, $head, "Column [{$gone}] is back on the tenants list.");
        }
    }

    public function test_a_tenant_with_no_lease_says_so_rather_than_showing_a_blank(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Tenant::create(['name' => 'Unhoused Tenant', 'tenant_type' => 'individual']);

        $this->get(route('tenants.index'))->assertOk()->assertSee('No active lease');
    }

    public function test_the_balance_column_totals_what_is_still_owed(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $building = $this->building();
        $unit = $this->unit($building);
        $lease = $this->lease($building, $unit);
        $this->invoice($lease->tenant);   // 450.000 issued, nothing paid

        $this->get(route('tenants.index'))->assertOk()->assertSee('450.000');
    }

    public function test_the_tenants_list_does_not_query_per_row(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $building = $this->building();

        for ($i = 1; $i <= 5; $i++) {
            $unit = $this->unit($building, ['unit_name' => 'Flat 10'.$i]);
            $tenant = Tenant::create(['name' => 'Tenant '.$i, 'tenant_type' => 'individual']);
            LeaseContract::create([
                'date'               => Carbon::today()->subMonth()->toDateString(),
                'lease_agreement_no' => 'LA-N1-'.$i,
                'tenant_id'          => $tenant->id,
                'tenant_name'        => $tenant->name,
                'property_name'      => $building->property_name,
                'unit_id'            => $unit->id,
                'unit'               => $unit->unit_name,
                'lease_start_date'   => Carbon::today()->subMonth()->toDateString(),
                'lease_end_date'     => Carbon::today()->addYear()->toDateString(),
                'rent_per_month'     => 450,
            ]);
            $this->invoice($tenant);
        }

        // The balance accessor walks each invoice's payments. Without the
        // eager loads that is a query per invoice per tenant, which is the
        // failure mode this asserts against — not an exact count, just that
        // it does not scale with the row count.
        \DB::enableQueryLog();
        $this->get(route('tenants.index'))->assertOk();
        $queries = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThan(40, $queries, "Tenants index ran {$queries} queries for 5 rows — the eager loads are not doing their job.");
    }

    // ── Numeric columns ──────────────────────────────────────────────────────

    public function test_money_and_measurement_columns_use_the_shared_numeric_cell(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $building = $this->building();
        $unit = $this->unit($building);
        $lease = $this->lease($building, $unit);
        $this->invoice($lease->tenant);

        // app-core's .num is the one right-aligned tabular cell; three pages
        // were each doing it by hand with an inline font the retheme replaced.
        foreach ([
            route('property-units.index'),
            route('lease-contracts.index'),
            route('invoices.index'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('class="num"', false);
        }
    }

    public function test_no_index_page_pins_the_pre_retheme_font(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $building = $this->building();
        $unit = $this->unit($building);
        $lease = $this->lease($building, $unit);
        $this->invoice($lease->tenant);

        foreach ([
            route('property-units.index'),
            route('lease-contracts.index'),
            route('invoices.index'),
        ] as $url) {
            $content = $this->get($url)->getContent();
            $body = substr($content, strpos($content, '<tbody>') ?: 0);

            $this->assertStringNotContainsString("'Outfit'", $body,
                "A table cell in [{$url}] still hardcodes Outfit instead of the design system's face.");
        }
    }
}
