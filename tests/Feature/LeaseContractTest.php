<?php

namespace Tests\Feature;

use App\Models\LeaseContract;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseContractTest extends TestCase
{
    use RefreshDatabase;

    private function minimalData(array $overrides = []): array
    {
        return array_merge([
            'date'               => '2024-01-01',
            'lease_agreement_no' => 'LA-TEST-001',
            'tenant_name'        => 'Test Tenant',
            'lease_start_date'   => '2024-01-01',
            'lease_end_date'     => '2025-01-01',
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
            ->assertSessionHasErrors(['date', 'lease_agreement_no', 'tenant_name', 'lease_start_date', 'lease_end_date']);
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
        $contract = LeaseContract::create($this->minimalData());

        $this->put(route('lease-contracts.update', $contract), $this->minimalData([
            'tenant_name' => 'Updated Tenant',
        ]))->assertRedirect(route('lease-contracts.index'));

        $this->assertEquals('Updated Tenant', $contract->fresh()->tenant_name);
    }

    public function test_update_allows_same_agreement_no(): void
    {
        $contract = LeaseContract::create($this->minimalData());

        $this->put(route('lease-contracts.update', $contract), $this->minimalData([
            'tenant_name' => 'Same Agreement No Update',
        ]))->assertSessionHasNoErrors();
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
}
