<?php

namespace Tests\Feature;

use App\Models\EwaBill;
use App\Models\Invoice;
use App\Models\InvoiceNote;
use App\Models\LeaseContract;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    // ── INDEX ────────────────────────────────────────────────────

    public function test_index_renders_successfully(): void
    {
        $response = $this->get(route('tenants.index'));
        $response->assertStatus(200);
        $response->assertViewIs('tenants.index');
    }

    public function test_index_shows_tenants(): void
    {
        Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->get(route('tenants.index'))
            ->assertSee('Ahmed Al-Khalifa');
    }

    public function test_index_filters_by_search(): void
    {
        Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        Tenant::create(['name' => 'Zahra Investments',  'tenant_type' => 'company']);

        $response = $this->get(route('tenants.index', ['search' => 'Zahra']));
        $tenants  = $response->viewData('tenants');

        $this->assertCount(1, $tenants);
        $this->assertEquals('Zahra Investments', $tenants->first()->name);
    }

    public function test_index_filters_by_tenant_type(): void
    {
        Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        Tenant::create(['name' => 'Zahra Investments',  'tenant_type' => 'company']);

        $response = $this->get(route('tenants.index', ['tenant_type' => 'company']));
        $tenants  = $response->viewData('tenants');

        $this->assertCount(1, $tenants);
        $this->assertEquals('Zahra Investments', $tenants->first()->name);
    }

    // ── STORE ────────────────────────────────────────────────────

    public function test_store_creates_tenant_with_minimal_fields(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);
    }

    public function test_store_creates_tenant_with_all_fields(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'                => 'Zahra Investments W.L.L.',
            'tenant_type'         => 'company',
            'id_cr_number'        => 'CR-12345',
            'phone'               => '+973 1700 0000',
            'email'               => 'info@zahra.bh',
            'nationality_country' => 'Bahrain',
        ]);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', [
            'name'                => 'Zahra Investments W.L.L.',
            'tenant_type'         => 'company',
            'id_cr_number'        => 'CR-12345',
            'email'               => 'info@zahra.bh',
        ]);
    }

    public function test_store_fails_without_name(): void
    {
        $response = $this->post(route('tenants.store'), [
            'tenant_type' => 'individual',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_store_fails_without_tenant_type(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name' => 'Ahmed Al-Khalifa',
        ]);

        $response->assertSessionHasErrors(['tenant_type']);
    }

    public function test_store_fails_with_invalid_tenant_type(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'partnership',
        ]);

        $response->assertSessionHasErrors(['tenant_type']);
    }

    public function test_store_fails_with_invalid_email(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
            'email'       => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_fails_with_name_exceeding_max_length(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'        => str_repeat('A', 256),
            'tenant_type' => 'individual',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    // ── SHOW ─────────────────────────────────────────────────────

    public function test_show_displays_tenant_profile(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
            'email'       => 'ahmed@example.com',
        ]);

        $this->get(route('tenants.show', $tenant))
            ->assertStatus(200)
            ->assertViewIs('tenants.show')
            ->assertSee('Ahmed Al-Khalifa')
            ->assertSee('ahmed@example.com');
    }

    public function test_show_returns_404_for_missing_tenant(): void
    {
        $this->get(route('tenants.show', 999))
            ->assertStatus(404);
    }

    // ── FINANCIAL HISTORY TABS ────────────────────────────────────

    public function test_show_displays_lease_contract(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA-PROFILE-1',
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Profile Tower',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => '2026-12-31',
        ]);

        $this->get(route('tenants.show', $tenant))
            ->assertStatus(200)
            ->assertSee('LA-PROFILE-1')
            ->assertSee('Profile Tower');
    }

    public function test_show_displays_invoice_payment_and_receipt_link(): void
    {
        $tenant  = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        $invoice = new Invoice([
            'invoice_number' => 'INV-PROFILE-1',
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Profile Tower',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Profile Tower', 'amount' => 500.000]],
            'vat_rate'       => 0,
            'invoice_date'   => '2026-01-01',
            'status'         => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();
        $payment = Payment::create([
            'payment_number' => 'PAY-PROFILE-1',
            'invoice_id'     => $invoice->id,
            'amount'         => 500.000,
            'payment_date'   => '2026-01-05',
            'method'         => 'cash',
        ]);

        $response = $this->get(route('tenants.show', $tenant));
        $response->assertStatus(200)
            ->assertSee('INV-PROFILE-1')
            ->assertSee('PAY-PROFILE-1')
            ->assertSee(route('invoices.payments.receipt', [$invoice, $payment]), false);
    }

    public function test_show_displays_ewa_bill_via_lease_contract(): void
    {
        $tenant   = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        $contract = LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA-PROFILE-2',
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Profile Tower',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => '2026-12-31',
        ]);
        EwaBill::create([
            'bill_number'       => 'EWA-PROFILE-1',
            'lease_contract_id' => $contract->id,
            'tenant_name'       => $tenant->name,
            'billing_period'    => 'January 2026',
            'reading_type'      => 'actual',
            'reading_date'      => '2026-01-05',
            'elec_charges'      => 20.000,
            'water_charges'     => 5.000,
            'total_amount'      => 25.000,
            'tenant_portion'    => 25.000,
            'due_date'          => '2026-01-20',
            'status'            => 'issued',
        ]);

        $this->get(route('tenants.show', $tenant))
            ->assertStatus(200)
            ->assertSee('EWA-PROFILE-1');
    }

    public function test_show_displays_credit_note_via_invoice(): void
    {
        $tenant  = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        $invoice = new Invoice([
            'invoice_number' => 'INV-PROFILE-2',
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Profile Tower',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Profile Tower', 'amount' => 1000.000]],
            'vat_rate'       => 0,
            'invoice_date'   => '2026-01-01',
            'status'         => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();
        InvoiceNote::create([
            'note_number' => 'CN-PROFILE-1',
            'invoice_id'  => $invoice->id,
            'tenant_id'   => $tenant->id,
            'type'        => 'credit',
            'amount'      => 100.000,
            'note_date'   => '2026-01-10',
            'reason'      => 'Overcharged tenant',
        ]);

        $this->get(route('tenants.show', $tenant))
            ->assertStatus(200)
            ->assertSee('CN-PROFILE-1')
            ->assertSee('Overcharged tenant');
    }

    public function test_show_displays_rent_ledger_tab_badge_count(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA-PROFILE-3',
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Profile Tower',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => '2026-12-31',
            'rent_start_date'    => '2026-01-01',
            'rent_end_date'      => '2026-12-31',
            'rent_per_month'     => 500.000,
        ]);

        $response = $this->get(route('tenants.show', $tenant));
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('rentSchedule')->isNotEmpty());
    }

    public function test_show_rent_ledger_totals_row_sums_received_amount(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA-PROFILE-4',
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Profile Tower',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => '2026-12-31',
            'rent_start_date'    => '2026-01-01',
            'rent_end_date'      => '2026-12-31',
            'rent_per_month'     => 500.000,
        ]);
        $invoice = new Invoice([
            'invoice_number' => 'INV-LEDGER-1',
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Profile Tower',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Profile Tower', 'amount' => 500.000]],
            'vat_rate'       => 0,
            'invoice_date'   => '2026-01-01',
            'status'         => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();
        Payment::create([
            'payment_number' => 'PAY-LEDGER-1',
            'invoice_id'     => $invoice->id,
            'amount'         => 500.000,
            'payment_date'   => '2026-01-05',
            'method'         => 'cash',
        ]);

        $response = $this->get(route('tenants.show', $tenant));
        $rentSchedule = $response->viewData('rentSchedule');

        $response->assertStatus(200)
            ->assertDontSee('Expected (BHD)')
            ->assertSee('Received (BHD)')
            ->assertSee(number_format($rentSchedule->sum('paid'), 3));
    }

    // ── EDIT / UPDATE ────────────────────────────────────────────

    public function test_edit_renders_form_with_existing_values(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->get(route('tenants.edit', $tenant))
            ->assertStatus(200)
            ->assertViewIs('tenants.edit')
            ->assertSee('Ahmed Al-Khalifa');
    }

    public function test_update_saves_changes(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->put(route('tenants.update', $tenant), [
            'name'        => 'Ahmed Al-Khalifa Jr.',
            'tenant_type' => 'individual',
            'phone'       => '+973 3300 1234',
        ])->assertRedirect(route('tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'id'    => $tenant->id,
            'name'  => 'Ahmed Al-Khalifa Jr.',
            'phone' => '+973 3300 1234',
        ]);
    }

    public function test_update_fails_without_name(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->put(route('tenants.update', $tenant), [
            'tenant_type' => 'company',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_update_fails_with_invalid_email(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->put(route('tenants.update', $tenant), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
            'email'       => 'bad-email',
        ])->assertSessionHasErrors(['email']);
    }

    // ── DESTROY ──────────────────────────────────────────────────

    public function test_destroy_deletes_tenant(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->delete(route('tenants.destroy', $tenant))
            ->assertRedirect(route('tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_destroy_returns_404_for_missing_tenant(): void
    {
        $this->delete(route('tenants.destroy', 999))
            ->assertStatus(404);
    }

    // ── TENANT CODE / ADDRESS ───────────────────────────────────────

    public function test_tenant_code_is_auto_generated_on_creation(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);

        $this->assertNotEmpty($tenant->tenant_code);
        $this->assertStringStartsWith('Tenant-', $tenant->tenant_code);
    }

    public function test_tenant_codes_are_sequential_and_unique(): void
    {
        $first  = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        $second = Tenant::create(['name' => 'Zahra Investments', 'tenant_type' => 'company']);

        $this->assertNotEquals($first->tenant_code, $second->tenant_code);
    }

    public function test_store_saves_address(): void
    {
        $this->post(route('tenants.store'), [
            'name'        => 'Bahrain Telecommunication Co.',
            'tenant_type' => 'company',
            'address'     => 'MP 2, Bldg# 233, Road# 3332, Block# 333, Bahrain',
        ]);

        $this->assertDatabaseHas('tenants', [
            'name'    => 'Bahrain Telecommunication Co.',
            'address' => 'MP 2, Bldg# 233, Road# 3332, Block# 333, Bahrain',
        ]);
    }

    public function test_update_saves_address(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);

        $this->put(route('tenants.update', $tenant), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
            'address'     => 'Road 1531, Muharraq',
        ]);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'address' => 'Road 1531, Muharraq']);
    }

    // ── SEARCH ───────────────────────────────────────────────────────

    public function test_search_returns_matching_tenants(): void
    {
        Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        Tenant::create(['name' => 'Zahra Investments', 'tenant_type' => 'company']);

        $response = $this->getJson(route('tenants.search', ['q' => 'Ahmed']));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Ahmed Al-Khalifa']);
    }

    public function test_search_matches_by_tenant_code(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);

        $response = $this->getJson(route('tenants.search', ['q' => $tenant->tenant_code]));
        $response->assertJsonFragment(['id' => $tenant->id]);
    }
}
