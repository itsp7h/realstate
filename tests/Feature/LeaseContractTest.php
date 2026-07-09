<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\LeaseContract;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseContractTest extends TestCase
{
    use RefreshDatabase;

    private function minimalData(array $overrides = []): array
    {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'tenant_type' => 'individual']);

        return array_merge([
            'date'               => '2024-01-01',
            'lease_agreement_no' => 'LA-TEST-001',
            'tenant_id'          => $tenant->id,
            'tenant_name'        => 'Test Tenant',
            'lease_start_date'   => '2024-01-01',
            'lease_end_date'     => '2025-01-01',
            'vat_enabled'        => false,
        ], $overrides);
    }

    // ── INDEX ────────────────────────────────────────────────────

    public function test_index_renders_successfully(): void
    {
        $this->get(route('lease-contracts.index'))
            ->assertStatus(200)
            ->assertViewIs('lease-contracts.index');
    }

    public function test_index_shows_contracts(): void
    {
        LeaseContract::create($this->minimalData());

        $this->get(route('lease-contracts.index'))
            ->assertSee('LA-TEST-001');
    }

    public function test_index_filters_by_search(): void
    {
        LeaseContract::create($this->minimalData(['lease_agreement_no' => 'LA-001', 'tenant_name' => 'Alpha Corp']));
        LeaseContract::create($this->minimalData(['lease_agreement_no' => 'LA-002', 'tenant_name' => 'Beta LLC']));

        $response  = $this->get(route('lease-contracts.index', ['search' => 'Alpha']));
        $contracts = $response->viewData('contracts');

        $this->assertCount(1, $contracts);
        $this->assertEquals('LA-001', $contracts->first()->lease_agreement_no);
    }

    public function test_index_filters_by_property_code(): void
    {
        LeaseContract::create($this->minimalData(['lease_agreement_no' => 'LA-001', 'property_code' => 'P001']));
        LeaseContract::create($this->minimalData(['lease_agreement_no' => 'LA-002', 'property_code' => 'P002']));

        $response  = $this->get(route('lease-contracts.index', ['property_code' => 'P001']));
        $contracts = $response->viewData('contracts');

        $this->assertCount(1, $contracts);
        $this->assertEquals('LA-001', $contracts->first()->lease_agreement_no);
    }

    public function test_index_filters_by_status_active(): void
    {
        // active: started in past, ends in > 30 days
        LeaseContract::create($this->minimalData([
            'lease_agreement_no' => 'LA-ACTIVE',
            'lease_start_date'   => now()->subMonths(6)->format('Y-m-d'),
            'lease_end_date'     => now()->addMonths(6)->format('Y-m-d'),
        ]));
        // expired
        LeaseContract::create($this->minimalData([
            'lease_agreement_no' => 'LA-EXPIRED',
            'lease_start_date'   => '2020-01-01',
            'lease_end_date'     => '2021-01-01',
        ]));

        $response  = $this->get(route('lease-contracts.index', ['status' => 'active']));
        $contracts = $response->viewData('contracts');

        $this->assertCount(1, $contracts);
        $this->assertEquals('LA-ACTIVE', $contracts->first()->lease_agreement_no);
    }

    // ── CREATE ───────────────────────────────────────────────────

    public function test_create_page_renders(): void
    {
        $this->get(route('lease-contracts.create'))
            ->assertStatus(200)
            ->assertViewIs('lease-contracts.create');
    }

    // ── STORE ────────────────────────────────────────────────────

    public function test_store_creates_minimal_contract(): void
    {
        $response = $this->post(route('lease-contracts.store'), $this->minimalData());

        $response->assertRedirect(route('lease-contracts.index'));
        $this->assertDatabaseHas('lease_contracts', ['lease_agreement_no' => 'LA-TEST-001']);
    }

    public function test_store_creates_full_contract(): void
    {
        $tenant = Tenant::create(['name' => 'Acme Ltd', 'tenant_type' => 'company']);

        $data = $this->minimalData([
            'lease_agreement_no'       => 'LA-FULL-001',
            'tenant_id'                => $tenant->id,
            'tenant_name'              => 'Acme Ltd',
            'property_name'            => 'Al Reef Tower',
            'property_code'            => 'P001',
            'block_name'               => 'Block A',
            'block_code'               => 'BLK-A',
            'floor_name'               => 'Ground Floor',
            'floor_code'               => 'GF',
            'description'              => 'Fitted',
            'lease_break_date'         => '2024-07-01',
            'notice_period'            => '3 months',
            'currency'                 => 'BHD',
            'invoicing_frequency'      => 'Monthly',
            'rent_start_date'          => '2024-01-01',
            'rent_end_date'            => '2025-01-01',
            'rent_per_month'           => '500.000',
            'service_frequency'        => 'Quarterly',
            'service_start_date'       => '2024-01-01',
            'service_end_date'         => '2025-01-01',
            'service_amount_bd_excl_vat' => '100.000',
            'rental_income_ledger'     => '4100-RENTAL',
            'security_deposit'         => '1500.000',
        ]);

        $this->post(route('lease-contracts.store'), $data)
            ->assertRedirect(route('lease-contracts.index'));

        $contract = LeaseContract::where('lease_agreement_no', 'LA-FULL-001')->first();
        $this->assertNotNull($contract);
        $this->assertEquals($tenant->id, $contract->tenant_id);
        $this->assertEquals('Al Reef Tower', $contract->property_name);
        $this->assertEquals('BHD', $contract->currency);
        $this->assertEquals(500.0, (float) $contract->rent_per_month);
    }

    public function test_store_links_tenant_name_from_id(): void
    {
        $tenant = Tenant::create(['name' => 'Auto Name Tenant', 'tenant_type' => 'individual']);

        $this->post(route('lease-contracts.store'), $this->minimalData([
            'tenant_id'   => $tenant->id,
            'tenant_name' => 'Will Be Overwritten',
        ]));

        $contract = LeaseContract::where('lease_agreement_no', 'LA-TEST-001')->first();
        $this->assertEquals('Auto Name Tenant', $contract->tenant_name);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $this->post(route('lease-contracts.store'), [])
            ->assertSessionHasErrors(['date', 'tenant_id', 'lease_start_date', 'lease_end_date']);
    }

    public function test_store_auto_generates_agreement_no_when_blank(): void
    {
        $data = $this->minimalData();
        unset($data['lease_agreement_no']);

        $this->post(route('lease-contracts.store'), $data)
            ->assertRedirect(route('lease-contracts.index'));

        $contract = LeaseContract::latest('id')->first();
        $this->assertNotNull($contract->lease_agreement_no);
        $this->assertStringStartsWith('LA-' . now()->year . '-', $contract->lease_agreement_no);
    }

    public function test_store_uses_provided_agreement_no_when_given(): void
    {
        $this->post(route('lease-contracts.store'), $this->minimalData(['lease_agreement_no' => 'LA-CUSTOM-001']));

        $this->assertDatabaseHas('lease_contracts', ['lease_agreement_no' => 'LA-CUSTOM-001']);
    }

    public function test_store_auto_generated_agreement_numbers_increment(): void
    {
        $first  = $this->minimalData();
        $second = $this->minimalData(['lease_agreement_no' => null]);
        unset($first['lease_agreement_no']);
        unset($second['lease_agreement_no']);
        $second['lease_start_date'] = '2024-02-01';
        $second['lease_end_date']   = '2025-02-01';

        $this->post(route('lease-contracts.store'), $first);
        $this->post(route('lease-contracts.store'), $second);

        $numbers = LeaseContract::orderBy('id')->pluck('lease_agreement_no');
        $this->assertCount(2, $numbers->unique());
    }

    public function test_store_fails_with_duplicate_agreement_no(): void
    {
        LeaseContract::create($this->minimalData());

        $this->post(route('lease-contracts.store'), $this->minimalData())
            ->assertSessionHasErrors(['lease_agreement_no']);
    }

    public function test_store_fails_when_end_before_start(): void
    {
        $this->post(route('lease-contracts.store'), $this->minimalData([
            'lease_start_date' => '2024-06-01',
            'lease_end_date'   => '2024-01-01',
        ]))->assertSessionHasErrors(['lease_end_date']);
    }

    public function test_store_fails_with_invalid_currency(): void
    {
        $this->post(route('lease-contracts.store'), $this->minimalData([
            'currency' => 'XYZ',
        ]))->assertSessionHasErrors(['currency']);
    }

    public function test_store_fails_with_invalid_description(): void
    {
        $this->post(route('lease-contracts.store'), $this->minimalData([
            'description' => 'Invalid Type',
        ]))->assertSessionHasErrors(['description']);
    }

    public function test_store_fails_when_break_date_outside_lease_range(): void
    {
        $this->post(route('lease-contracts.store'), $this->minimalData([
            'lease_start_date' => '2024-01-01',
            'lease_end_date'   => '2025-01-01',
            'lease_break_date' => '2026-01-01',
        ]))->assertSessionHasErrors(['lease_break_date']);
    }

    public function test_store_fails_with_negative_rent(): void
    {
        $this->post(route('lease-contracts.store'), $this->minimalData([
            'rent_per_month' => '-100',
        ]))->assertSessionHasErrors(['rent_per_month']);
    }

    // ── SHOW ─────────────────────────────────────────────────────

    public function test_show_renders_successfully(): void
    {
        $contract = LeaseContract::create($this->minimalData());

        $this->get(route('lease-contracts.show', $contract))
            ->assertStatus(200)
            ->assertViewIs('lease-contracts.show')
            ->assertSee('LA-TEST-001');
    }

    public function test_show_returns_404_for_missing_contract(): void
    {
        $this->get(route('lease-contracts.show', 999))
            ->assertStatus(404);
    }

    // ── EDIT ─────────────────────────────────────────────────────

    public function test_edit_page_renders_with_prefilled_data(): void
    {
        $contract = LeaseContract::create($this->minimalData());

        $this->get(route('lease-contracts.edit', $contract))
            ->assertStatus(200)
            ->assertViewIs('lease-contracts.edit')
            ->assertSee('LA-TEST-001');
    }

    // ── UPDATE ───────────────────────────────────────────────────

    public function test_update_modifies_contract(): void
    {
        $contract  = LeaseContract::create($this->minimalData());
        $newTenant = Tenant::create(['name' => 'Updated Tenant', 'tenant_type' => 'individual']);

        $this->put(route('lease-contracts.update', $contract), $this->minimalData([
            'lease_agreement_no' => $contract->lease_agreement_no,
            'tenant_id'          => $newTenant->id,
        ]))->assertRedirect(route('lease-contracts.index'));

        $this->assertEquals('Updated Tenant', $contract->fresh()->tenant_name);
    }

    public function test_update_allows_same_agreement_no(): void
    {
        $contract = LeaseContract::create($this->minimalData());

        $this->put(route('lease-contracts.update', $contract), $this->minimalData([
            'lease_agreement_no' => $contract->lease_agreement_no,
        ]))->assertSessionHasNoErrors();
    }

    public function test_update_fails_without_tenant(): void
    {
        $contract = LeaseContract::create($this->minimalData());

        $data = $this->minimalData(['lease_agreement_no' => $contract->lease_agreement_no]);
        unset($data['tenant_id']);

        $this->put(route('lease-contracts.update', $contract), $data)
            ->assertSessionHasErrors(['tenant_id']);
    }

    public function test_update_fails_with_another_contracts_agreement_no(): void
    {
        LeaseContract::create($this->minimalData(['lease_agreement_no' => 'LA-001']));
        $c2 = LeaseContract::create($this->minimalData(['lease_agreement_no' => 'LA-002']));

        $this->put(route('lease-contracts.update', $c2), $this->minimalData([
            'lease_agreement_no' => 'LA-001',
        ]))->assertSessionHasErrors(['lease_agreement_no']);
    }

    public function test_update_fails_with_invalid_invoicing_frequency(): void
    {
        $contract = LeaseContract::create($this->minimalData());

        $this->put(route('lease-contracts.update', $contract), $this->minimalData([
            'invoicing_frequency' => 'Weekly',
        ]))->assertSessionHasErrors(['invoicing_frequency']);
    }

    // ── DESTROY ──────────────────────────────────────────────────

    public function test_destroy_deletes_contract(): void
    {
        $contract = LeaseContract::create($this->minimalData());

        $this->delete(route('lease-contracts.destroy', $contract))
            ->assertRedirect(route('lease-contracts.index'));

        $this->assertDatabaseMissing('lease_contracts', ['id' => $contract->id]);
    }

    public function test_destroy_returns_404_for_missing_contract(): void
    {
        $this->delete(route('lease-contracts.destroy', 999))
            ->assertStatus(404);
    }

    // ── ACTIVE LEASES FOR TENANT (invoicing) ──────────────────────

    public function test_active_for_tenant_returns_only_currently_active_leases(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);

        LeaseContract::create($this->minimalData([
            'tenant_id'        => $tenant->id,
            'property_name'    => 'Active Property',
            'lease_start_date' => now()->subMonth()->format('Y-m-d'),
            'lease_end_date'   => now()->addMonth()->format('Y-m-d'),
        ]));

        LeaseContract::create($this->minimalData([
            'tenant_id'        => $tenant->id,
            'property_name'    => 'Expired Property',
            'lease_agreement_no' => 'LA-TEST-002',
            'lease_start_date' => now()->subYear()->format('Y-m-d'),
            'lease_end_date'   => now()->subMonth()->format('Y-m-d'),
        ]));

        $response = $this->getJson(route('lease-contracts.active-for-tenant', $tenant));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['property_name' => 'Active Property']);
    }

    // ── SEARCH FOR TENANT (per-line picker) ───────────────────────

    public function test_search_for_tenant_returns_all_of_that_tenants_contracts_regardless_of_date(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        $other  = Tenant::create(['name' => 'Zahra Investments', 'tenant_type' => 'company']);

        LeaseContract::create($this->minimalData([
            'tenant_id'        => $tenant->id,
            'property_name'    => 'Expired Property',
            'lease_start_date' => now()->subYear()->format('Y-m-d'),
            'lease_end_date'   => now()->subMonth()->format('Y-m-d'),
        ]));
        LeaseContract::create($this->minimalData([
            'tenant_id'          => $other->id,
            'property_name'      => 'Other Tenant Property',
            'lease_agreement_no' => 'LA-TEST-003',
        ]));

        $response = $this->getJson(route('lease-contracts.search-for-tenant', $tenant));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['property_name' => 'Expired Property']);
    }

    public function test_search_for_tenant_filters_by_query(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);

        LeaseContract::create($this->minimalData([
            'tenant_id'          => $tenant->id,
            'property_name'      => 'Miknas Plaza 2',
            'lease_agreement_no' => 'LA-TEST-004',
        ]));
        LeaseContract::create($this->minimalData([
            'tenant_id'          => $tenant->id,
            'property_name'      => 'Seef Tower',
            'lease_agreement_no' => 'LA-TEST-005',
        ]));

        $response = $this->getJson(route('lease-contracts.search-for-tenant', $tenant) . '?q=Miknas');
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['property_name' => 'Miknas Plaza 2']);
    }

    // ── VAT RATE ON LEASE LOOKUPS (for invoice auto-fill) ──────────

    public function test_search_for_tenant_includes_contracts_vat_rate(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        Building::create([
            'property_name' => 'Miknas Plaza 2',
            'property_code' => 'MP2',
            'vat_enabled'   => true,
            'vat_rate'      => 15,
        ]);
        LeaseContract::create($this->minimalData([
            'tenant_id'     => $tenant->id,
            'property_name' => 'Miknas Plaza 2',
            'property_code' => 'MP2',
            'vat_enabled'   => true,
            'vat_rate'      => 10,
        ]));

        $response = $this->getJson(route('lease-contracts.search-for-tenant', $tenant));
        $response->assertJsonFragment(['vat_rate' => 10]);
    }

    public function test_search_for_tenant_vat_rate_is_zero_when_contract_vat_disabled(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        LeaseContract::create($this->minimalData([
            'tenant_id'     => $tenant->id,
            'property_name' => 'Seef Tower',
            'property_code' => 'ST1',
            'vat_enabled'   => false,
            'vat_rate'      => 10,
        ]));

        $response = $this->getJson(route('lease-contracts.search-for-tenant', $tenant));
        $response->assertJsonFragment(['vat_rate' => 0]);
    }

    public function test_active_for_tenant_includes_contracts_vat_rate(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        LeaseContract::create($this->minimalData([
            'tenant_id'        => $tenant->id,
            'property_name'    => 'Miknas Plaza 2',
            'property_code'    => 'MP2',
            'lease_start_date' => now()->subMonth()->format('Y-m-d'),
            'lease_end_date'   => now()->addMonth()->format('Y-m-d'),
            'vat_enabled'      => true,
            'vat_rate'         => 5,
        ]));

        $response = $this->getJson(route('lease-contracts.active-for-tenant', $tenant));
        $response->assertJsonFragment(['vat_rate' => 5]);
    }

    public function test_different_tenants_on_same_flat_can_have_different_vat_treatment(): void
    {
        $vatTenant    = Tenant::create(['name' => 'Bahrain Telecom', 'tenant_type' => 'company']);
        $noVatTenant  = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);

        LeaseContract::create($this->minimalData([
            'lease_agreement_no' => 'LA-VAT-001',
            'tenant_id'          => $vatTenant->id,
            'property_name'      => 'Miknas Plaza 2',
            'unit'               => 'MP2-101',
            'vat_enabled'        => true,
            'vat_rate'           => 10,
        ]));

        LeaseContract::create($this->minimalData([
            'lease_agreement_no' => 'LA-VAT-002',
            'tenant_id'          => $noVatTenant->id,
            'property_name'      => 'Miknas Plaza 2',
            'unit'               => 'MP2-101',
            'vat_enabled'        => false,
        ]));

        $withVat    = LeaseContract::where('lease_agreement_no', 'LA-VAT-001')->first();
        $withoutVat = LeaseContract::where('lease_agreement_no', 'LA-VAT-002')->first();

        $this->assertEquals(10.0, $withVat->effective_vat_rate);
        $this->assertEquals(0.0, $withoutVat->effective_vat_rate);
    }
}
