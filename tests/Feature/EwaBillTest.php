<?php

namespace Tests\Feature;

use App\Models\EwaBill;
use App\Models\EwaPayment;
use App\Models\LeaseContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EwaBillTest extends TestCase
{
    use RefreshDatabase;

    private function makeContract(array $overrides = []): LeaseContract
    {
        return LeaseContract::create(array_merge([
            'date'               => '2024-01-01',
            'lease_agreement_no' => 'LA-EWA-' . uniqid(),
            'tenant_name'        => 'Test Tenant',
            'property_name'      => 'Test Property',
            'lease_start_date'   => '2024-01-01',
            'lease_end_date'     => '2025-01-01',
        ], $overrides));
    }

    private function makeBill(array $overrides = []): EwaBill
    {
        return EwaBill::create(array_merge([
            'bill_number'    => 'EWA-TEST-' . uniqid(),
            'tenant_name'    => 'Test Tenant',
            'billing_period' => 'March 2024',
            'reading_type'   => 'actual',
            'total_amount'   => 60.000,
            'due_date'       => now()->addDays(20)->format('Y-m-d'),
            'status'         => 'issued',
        ], $overrides));
    }

    // ── INDEX ─────────────────────────────────────────────────────

    public function test_index_renders_successfully(): void
    {
        $this->get(route('ewa-bills.index'))->assertStatus(200);
    }

    public function test_index_shows_bill_rows(): void
    {
        $bill = $this->makeBill();
        $this->get(route('ewa-bills.index'))
            ->assertSee($bill->bill_number)
            ->assertSee($bill->tenant_name);
    }

    public function test_index_filters_by_status(): void
    {
        $this->makeBill(['status' => 'issued']);
        $this->makeBill(['status' => 'paid']);

        $response = $this->get(route('ewa-bills.index', ['status' => 'paid']));
        $bills    = $response->viewData('bills');
        foreach ($bills as $b) {
            $this->assertSame('paid', $b->status);
        }
    }

    // ── CREATE ────────────────────────────────────────────────────

    public function test_create_form_renders(): void
    {
        $this->get(route('ewa-bills.create'))->assertStatus(200);
    }

    public function test_can_create_bill_without_cap(): void
    {
        $this->post(route('ewa-bills.store'), [
            'tenant_name'    => 'Ahmed Al Sayed',
            'billing_period' => 'April 2024',
            'reading_type'   => 'actual',
            'elec_charges'   => '45.000',
            'water_charges'  => '10.000',
            'due_date'       => now()->addDays(14)->format('Y-m-d'),
        ])->assertRedirect();

        $bill = EwaBill::where('tenant_name', 'Ahmed Al Sayed')->first();
        $this->assertNotNull($bill);
        $this->assertEquals(55.000, (float) $bill->total_amount);
        $this->assertEquals(55.000, $bill->effective_tenant_portion);
        $this->assertNull($bill->ewa_cap);
    }

    public function test_can_create_bill_with_cap(): void
    {
        $this->post(route('ewa-bills.store'), [
            'tenant_name'    => 'Sara Al Khalifa',
            'billing_period' => 'April 2024',
            'reading_type'   => 'actual',
            'elec_charges'   => '80.000',
            'water_charges'  => '20.000',
            'ewa_cap'        => '50.000',
            'due_date'       => now()->addDays(14)->format('Y-m-d'),
        ])->assertRedirect();

        $bill = EwaBill::where('tenant_name', 'Sara Al Khalifa')->first();
        $this->assertNotNull($bill);
        $this->assertEquals(100.000, (float) $bill->total_amount);
        $this->assertEquals(50.000, (float) $bill->ewa_cap);
        $this->assertEquals(50.000, (float) $bill->tenant_portion);
        $this->assertEquals(50.000, $bill->effective_tenant_portion);
        $this->assertEquals(50.000, $bill->landlord_portion);
    }

    public function test_cap_larger_than_total_results_in_zero_tenant_portion(): void
    {
        $this->post(route('ewa-bills.store'), [
            'tenant_name'    => 'Zero Tenant',
            'billing_period' => 'April 2024',
            'reading_type'   => 'actual',
            'elec_charges'   => '30.000',
            'water_charges'  => '10.000',
            'ewa_cap'        => '100.000',
            'due_date'       => now()->addDays(14)->format('Y-m-d'),
        ])->assertRedirect();

        $bill = EwaBill::where('tenant_name', 'Zero Tenant')->first();
        $this->assertEquals(0.0, (float) $bill->tenant_portion);
        $this->assertEquals(0.0, $bill->effective_tenant_portion);
        $this->assertEquals(40.000, $bill->landlord_portion);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->post(route('ewa-bills.store'), [])
            ->assertSessionHasErrors(['tenant_name', 'billing_period', 'reading_type', 'due_date']);
    }

    // ── SHOW ─────────────────────────────────────────────────────

    public function test_show_renders(): void
    {
        $bill = $this->makeBill();
        $this->get(route('ewa-bills.show', $bill))
            ->assertStatus(200)
            ->assertSee($bill->bill_number);
    }

    // ── EDIT / UPDATE ─────────────────────────────────────────────

    public function test_edit_form_renders(): void
    {
        $bill = $this->makeBill();
        $this->get(route('ewa-bills.edit', $bill))->assertStatus(200);
    }

    public function test_can_update_bill_cap(): void
    {
        $bill = $this->makeBill(['total_amount' => 80.000, 'ewa_cap' => null, 'tenant_portion' => 80.000]);

        $this->put(route('ewa-bills.update', $bill), [
            'tenant_name'    => $bill->tenant_name,
            'billing_period' => $bill->billing_period,
            'reading_type'   => $bill->reading_type,
            'elec_charges'   => '60.000',
            'water_charges'  => '20.000',
            'ewa_cap'        => '40.000',
            'due_date'       => $bill->due_date->format('Y-m-d'),
            'status'         => 'issued',
        ])->assertRedirect(route('ewa-bills.show', $bill));

        $bill->refresh();
        $this->assertEquals(40.000, (float) $bill->ewa_cap);
        $this->assertEquals(40.000, (float) $bill->tenant_portion);
    }

    // ── DELETE ────────────────────────────────────────────────────

    public function test_can_delete_bill(): void
    {
        $bill = $this->makeBill();
        $this->delete(route('ewa-bills.destroy', $bill))
            ->assertRedirect(route('ewa-bills.index'));
        $this->assertDatabaseMissing('ewa_bills', ['id' => $bill->id]);
    }

    // ── CAP COMPUTED ATTRIBUTES ───────────────────────────────────

    public function test_has_cap_returns_false_when_no_cap(): void
    {
        $bill = $this->makeBill(['ewa_cap' => null]);
        $this->assertFalse($bill->hasCap());
    }

    public function test_has_cap_returns_true_when_cap_set(): void
    {
        $bill = $this->makeBill(['ewa_cap' => 50.000, 'tenant_portion' => 10.000]);
        $this->assertTrue($bill->hasCap());
    }

    public function test_effective_tenant_portion_falls_back_to_total_when_no_cap(): void
    {
        $bill = $this->makeBill(['total_amount' => 75.000, 'ewa_cap' => null, 'tenant_portion' => null]);
        $this->assertEquals(75.000, $bill->effective_tenant_portion);
    }

    public function test_landlord_portion_is_zero_without_cap(): void
    {
        $bill = $this->makeBill(['total_amount' => 60.000, 'ewa_cap' => null, 'tenant_portion' => null]);
        $this->assertEquals(0.0, $bill->landlord_portion);
    }

    public function test_balance_due_uses_tenant_portion_when_cap_set(): void
    {
        $bill = $this->makeBill(['total_amount' => 100.000, 'ewa_cap' => 60.000, 'tenant_portion' => 40.000]);
        $this->assertEquals(40.000, $bill->balance_due);
    }

    // ── PAYMENTS ─────────────────────────────────────────────────

    public function test_can_record_payment(): void
    {
        $bill = $this->makeBill(['total_amount' => 60.000, 'tenant_portion' => 60.000]);

        $this->post(route('ewa-bills.payments.store', $bill), [
            'amount'       => '30.000',
            'payment_date' => now()->format('Y-m-d'),
            'method'       => 'cash',
        ])->assertRedirect(route('ewa-bills.show', $bill));

        $this->assertDatabaseHas('ewa_payments', ['ewa_bill_id' => $bill->id, 'amount' => 30.000]);
    }

    public function test_payment_requires_cheque_number_and_date_when_method_is_cheque(): void
    {
        $bill = $this->makeBill(['total_amount' => 60.000, 'tenant_portion' => 60.000]);

        $this->post(route('ewa-bills.payments.store', $bill), [
            'amount'       => '30.000',
            'payment_date' => now()->format('Y-m-d'),
            'method'       => 'cheque',
        ])->assertSessionHasErrors(['cheque_number', 'cheque_date']);
    }

    public function test_payment_saves_cheque_number_and_date(): void
    {
        $bill = $this->makeBill(['total_amount' => 60.000, 'tenant_portion' => 60.000]);

        $this->post(route('ewa-bills.payments.store', $bill), [
            'amount'        => '30.000',
            'payment_date'  => now()->format('Y-m-d'),
            'method'        => 'cheque',
            'cheque_number' => 'CHQ-5521',
            'cheque_date'   => now()->format('Y-m-d'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ewa_payments', ['ewa_bill_id' => $bill->id, 'cheque_number' => 'CHQ-5521']);
    }

    public function test_payment_syncs_status_to_partially_paid(): void
    {
        $bill = $this->makeBill(['total_amount' => 60.000, 'tenant_portion' => 60.000]);

        EwaPayment::create([
            'payment_number' => 'EPAY-TEST-' . uniqid(),
            'ewa_bill_id'    => $bill->id,
            'amount'         => 30.000,
            'payment_date'   => now(),
            'method'         => 'cash',
        ]);
        $bill->syncStatus();
        $this->assertSame('partially_paid', $bill->fresh()->status);
    }

    public function test_payment_syncs_status_to_paid_when_tenant_portion_covered(): void
    {
        $bill = $this->makeBill(['total_amount' => 100.000, 'ewa_cap' => 60.000, 'tenant_portion' => 40.000]);

        EwaPayment::create([
            'payment_number' => 'EPAY-TEST-' . uniqid(),
            'ewa_bill_id'    => $bill->id,
            'amount'         => 40.000,
            'payment_date'   => now(),
            'method'         => 'bank_transfer',
        ]);
        $bill->syncStatus();
        $this->assertSame('paid', $bill->fresh()->status);
    }

    public function test_sync_status_does_not_change_cancelled(): void
    {
        $bill = $this->makeBill(['status' => 'cancelled']);
        $bill->syncStatus();
        $this->assertSame('cancelled', $bill->fresh()->status);
    }

    public function test_can_delete_payment(): void
    {
        $bill = $this->makeBill();
        $pmt  = EwaPayment::create([
            'payment_number' => 'EPAY-DEL-' . uniqid(),
            'ewa_bill_id'    => $bill->id,
            'amount'         => 20.000,
            'payment_date'   => now(),
            'method'         => 'cash',
        ]);

        $this->delete(route('ewa-bills.payments.destroy', [$bill, $pmt]))
            ->assertRedirect(route('ewa-bills.show', $bill));
        $this->assertDatabaseMissing('ewa_payments', ['id' => $pmt->id]);
    }

    // ── CONTRACT CAP AUTO-FILL ────────────────────────────────────

    public function test_search_returns_ewa_cap_from_contract(): void
    {
        $contract = $this->makeContract(['ewa_cap' => 75.000]);

        $this->get(route('lease-contracts.search', ['q' => $contract->tenant_name]))
            ->assertJsonFragment(['ewa_cap' => '75.000']);
    }

    public function test_search_returns_empty_ewa_cap_when_not_set(): void
    {
        $contract = $this->makeContract(['ewa_cap' => null]);

        $response = $this->get(route('lease-contracts.search', ['q' => $contract->tenant_name]));
        $data     = $response->json();

        $match = collect($data)->firstWhere('id', $contract->id);
        $this->assertNotNull($match);
        $this->assertSame('', $match['ewa_cap']);
    }

    // ── COMPUTE TENANT PORTION ────────────────────────────────────

    public function test_compute_tenant_portion_no_cap(): void
    {
        $this->assertEquals(100.0, EwaBill::computeTenantPortion(100.0, null));
        $this->assertEquals(100.0, EwaBill::computeTenantPortion(100.0, 0));
    }

    public function test_compute_tenant_portion_with_cap(): void
    {
        $this->assertEquals(50.0,  EwaBill::computeTenantPortion(100.0, 50.0));
        $this->assertEquals(0.0,   EwaBill::computeTenantPortion(100.0, 150.0));
        $this->assertEquals(0.0,   EwaBill::computeTenantPortion(100.0, 100.0));
        $this->assertEquals(25.0,  EwaBill::computeTenantPortion(100.0, 75.0));
    }

    // ── COMPUTE TOTAL ──────────────────────────────────────────────

    public function test_compute_total_is_electricity_plus_water_only(): void
    {
        $this->assertEquals(55.0, EwaBill::computeTotal(['elec_charges' => 45.0, 'water_charges' => 10.0]));
    }

    public function test_compute_total_defaults_missing_charges_to_zero(): void
    {
        $this->assertEquals(45.0, EwaBill::computeTotal(['elec_charges' => 45.0]));
        $this->assertEquals(0.0, EwaBill::computeTotal([]));
    }
}
