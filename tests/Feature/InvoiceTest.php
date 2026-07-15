<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\EwaBill;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
        ], $overrides));
    }

    private function makeContract(array $overrides = []): LeaseContract
    {
        return LeaseContract::create(array_merge([
            'date'               => '2024-01-01',
            'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_name'        => 'Test Tenant',
            'property_name'      => 'Test Property',
            'lease_start_date'   => '2024-01-01',
            'lease_end_date'     => '2025-01-01',
        ], $overrides));
    }

    private function makeLines(array $overrides = []): array
    {
        return [array_merge([
            'property_name' => 'Test Property',
            'unit'          => 'Flat 1',
            'amount'        => 100.000,
        ], $overrides)];
    }

    private function makeInvoice(array $overrides = []): Invoice
    {
        $tenant = $this->makeTenant();
        $lines  = $overrides['lines'] ?? $this->makeLines(['amount' => $overrides['amount'] ?? 100.000]);
        unset($overrides['lines']);

        $invoice = new Invoice(array_merge([
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'tenant_code'    => $tenant->tenant_code,
            'property_name'  => $lines[0]['property_name'],
            'unit'           => $lines[0]['unit'] ?? null,
            'type'           => 'rent',
            'lines'          => $lines,
            'vat_rate'       => 0,
            'invoice_date'   => '2024-03-01',
            'status'         => 'issued',
        ], $overrides));
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    // ── INDEX ─────────────────────────────────────────────────────

    public function test_index_renders_successfully(): void
    {
        $this->get(route('invoices.index'))->assertStatus(200);
    }

    public function test_index_shows_invoice_rows(): void
    {
        $inv = $this->makeInvoice();
        $this->get(route('invoices.index'))
            ->assertSee($inv->invoice_number)
            ->assertSee($inv->tenant_name);
    }

    public function test_index_filters_by_status(): void
    {
        $this->makeInvoice(['status' => 'issued']);
        $this->makeInvoice(['status' => 'paid']);

        $response = $this->get(route('invoices.index', ['status' => 'paid']));
        $response->assertStatus(200);

        $invoices = $response->viewData('invoices');
        foreach ($invoices as $inv) {
            $this->assertSame('paid', $inv->status);
        }
    }

    public function test_index_filters_by_type(): void
    {
        $this->makeInvoice(['type' => 'rent']);
        $this->makeInvoice(['type' => 'utilities']);

        $response = $this->get(route('invoices.index', ['type' => 'utilities']));
        $invoices = $response->viewData('invoices');
        foreach ($invoices as $inv) {
            $this->assertSame('utilities', $inv->type);
        }
    }

    public function test_index_filters_by_search(): void
    {
        $this->makeInvoice(['tenant_name' => 'Alice Smith']);
        $this->makeInvoice(['tenant_name' => 'Bob Jones']);

        $response = $this->get(route('invoices.index', ['search' => 'Alice']));
        $invoices = $response->viewData('invoices');
        foreach ($invoices as $inv) {
            $this->assertStringContainsStringIgnoringCase('Alice', $inv->tenant_name);
        }
    }

    // ── CREATE ────────────────────────────────────────────────────

    public function test_create_form_renders(): void
    {
        $this->get(route('invoices.create'))->assertStatus(200);
    }

    public function test_can_create_invoice(): void
    {
        $tenant = $this->makeTenant();

        $this->post(route('invoices.store'), [
            'tenant_id'    => $tenant->id,
            'type'         => 'rent',
            'invoice_date' => '2024-04-01',
            'description'  => 'April rent',
            'lines'       => [
                ['property_name' => 'Test Property', 'unit' => 'Flat 1', 'amount' => '250.000'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'tenant_id'     => $tenant->id,
            'tenant_name'   => $tenant->name,
            'property_name' => 'Test Property',
            'type'          => 'rent',
            'status'        => 'issued',
            'amount'        => 250.000,
        ]);
    }

    public function test_can_create_invoice_with_multiple_lines_grouped_under_one_invoice(): void
    {
        $tenant = $this->makeTenant();

        $this->post(route('invoices.store'), [
            'tenant_id'    => $tenant->id,
            'type'         => 'rent',
            'invoice_date' => '2024-04-01',
            'lines'        => [
                ['property_name' => 'Miknas Plaza 2', 'unit' => 'Flat 22', 'amount' => '400.000'],
                ['property_name' => 'Miknas Plaza 4', 'unit' => 'Flat 32', 'amount' => '350.000'],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('tenant_id', $tenant->id)->first();
        $this->assertCount(2, $invoice->lines);
        $this->assertEquals(750.000, $invoice->amount);
    }

    public function test_falls_back_to_building_address_when_tenant_has_none(): void
    {
        $tenant = $this->makeTenant(['address' => null]);
        Building::create([
            'property_name' => 'Miknas Plaza 2',
            'property_code' => 'MP2',
            'road'          => 'Avenue 0022',
            'block'         => 324,
            'area'          => 'Capital Governorate',
            'city'          => 'Manama',
        ]);
        $contract = $this->makeContract([
            'tenant_id'     => $tenant->id,
            'property_name' => 'Miknas Plaza 2',
            'property_code' => 'MP2',
        ]);

        $this->post(route('invoices.store'), [
            'tenant_id'    => $tenant->id,
            'type'         => 'rent',
            'invoice_date' => '2024-04-01',
            'lines'        => [
                ['lease_contract_id' => $contract->id, 'property_name' => 'Miknas Plaza 2', 'amount' => '400.000'],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('tenant_id', $tenant->id)->first();
        $this->assertSame('Avenue 0022, Block 324, Capital Governorate, Manama', $invoice->tenant_address);
    }

    public function test_display_address_falls_back_live_for_invoices_saved_before_this_feature(): void
    {
        $tenant = $this->makeTenant(['address' => null]);
        Building::create([
            'property_name' => 'Miknas Plaza 2',
            'property_code' => 'MP2',
            'road'          => 'Avenue 0022',
            'block'         => 324,
            'area'          => 'Capital Governorate',
            'city'          => 'Manama',
        ]);

        // Simulate an invoice created before the address fallback existed:
        // tenant_address is null on the stored row.
        $invoice = $this->makeInvoice([
            'tenant_id'      => $tenant->id,
            'tenant_address' => null,
            'lines'          => [['property_name' => 'Miknas Plaza 2', 'amount' => 400.000]],
        ]);

        $this->assertNull($invoice->tenant_address);
        $this->assertSame('Avenue 0022, Block 324, Capital Governorate, Manama', $invoice->display_address);
    }

    public function test_uses_tenants_own_address_when_present(): void
    {
        $tenant = $this->makeTenant(['address' => 'Custom Mailing Address, Manama']);

        $this->post(route('invoices.store'), [
            'tenant_id'    => $tenant->id,
            'type'         => 'rent',
            'invoice_date' => '2024-04-01',
            'lines'        => [
                ['property_name' => 'Test Property', 'amount' => '100.000'],
            ],
        ])->assertRedirect();

        $invoice = Invoice::where('tenant_id', $tenant->id)->first();
        $this->assertSame('Custom Mailing Address, Manama', $invoice->tenant_address);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->post(route('invoices.store'), [])
            ->assertSessionHasErrors(['tenant_id', 'type', 'invoice_date', 'lines']);
    }

    public function test_store_validates_line_amount_minimum(): void
    {
        $tenant = $this->makeTenant();
        $this->post(route('invoices.store'), [
            'tenant_id'    => $tenant->id,
            'type'         => 'rent',
            'invoice_date' => '2024-04-01',
            'lines'        => [['property_name' => 'Test Property', 'amount' => '0']],
        ])->assertSessionHasErrors(['lines.0.amount']);
    }

    // ── SHOW ─────────────────────────────────────────────────────

    public function test_show_renders(): void
    {
        $inv = $this->makeInvoice();
        $this->get(route('invoices.show', $inv))
            ->assertStatus(200)
            ->assertSee($inv->invoice_number);
    }

    public function test_show_renders_record_payment_form_when_balance_due(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000]);
        $this->get(route('invoices.show', $inv))
            ->assertStatus(200)
            ->assertSee('Record Payment');
    }

    public function test_show_lists_existing_payments(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000]);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 40.000,
            'payment_date'   => '2024-03-05',
            'method'         => 'cash',
        ]);

        $this->get(route('invoices.show', $inv))
            ->assertStatus(200)
            ->assertSee('PAY-TEST')
            ->assertSee('40.000');
    }

    public function test_show_has_receipt_preview_button_for_each_payment(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000]);
        $pmt = Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 40.000,
            'payment_date'   => '2024-03-05',
            'method'         => 'cash',
        ]);

        $this->get(route('invoices.show', $inv))
            ->assertStatus(200)
            ->assertSee(route('invoices.payments.receipt.preview', [$inv, $pmt]), false);
    }

    public function test_show_hides_record_payment_form_when_fully_paid(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000]);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 100.000,
            'payment_date'   => '2024-03-05',
            'method'         => 'cash',
        ]);

        $this->get(route('invoices.show', $inv))
            ->assertStatus(200)
            ->assertDontSee('Record Payment');
    }

    public function test_can_record_payment_from_invoice_show_page(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000]);

        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '100.000',
            'payment_date' => '2024-03-15',
            'method'       => 'cash',
        ])->assertRedirect(route('invoices.show', $inv));

        $inv->refresh();
        $this->assertEquals(0.0, $inv->balance_due);
        $this->assertEquals('paid', $inv->status);
    }

    public function test_can_record_payment_linked_to_ewa_bill(): void
    {
        $inv      = $this->makeInvoice(['amount' => 100.000]);
        $contract = $this->makeContract(['tenant_id' => $inv->tenant_id, 'tenant_name' => $inv->tenant_name]);
        $bill     = EwaBill::create([
            'bill_number'       => 'EWA-TEST-' . uniqid(),
            'lease_contract_id' => $contract->id,
            'tenant_name'       => $inv->tenant_name,
            'billing_period'    => 'March 2024',
            'reading_type'      => 'actual',
            'reading_date'      => '2024-03-01',
            'elec_charges'      => 20.000,
            'water_charges'     => 5.000,
            'total_amount'      => 25.000,
            'tenant_portion'    => 25.000,
            'ewa_cap'           => 40.000,
            'due_date'          => '2024-03-10',
            'status'            => 'issued',
        ]);

        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '100.000',
            'payment_date' => '2024-03-15',
            'method'       => 'cash',
            'ewa_bill_id'  => $bill->id,
        ])->assertRedirect(route('invoices.show', $inv));

        $payment = Payment::where('invoice_id', $inv->id)->first();
        $this->assertEquals($bill->id, $payment->ewa_bill_id);
    }

    public function test_record_payment_form_offers_tenants_ewa_bills(): void
    {
        $inv      = $this->makeInvoice(['amount' => 100.000]);
        $contract = $this->makeContract(['tenant_id' => $inv->tenant_id, 'tenant_name' => $inv->tenant_name]);
        $bill     = EwaBill::create([
            'bill_number'       => 'EWA-TEST-' . uniqid(),
            'lease_contract_id' => $contract->id,
            'tenant_name'       => $inv->tenant_name,
            'billing_period'    => 'March 2024',
            'reading_type'      => 'actual',
            'reading_date'      => '2024-03-01',
            'elec_charges'      => 20.000,
            'water_charges'     => 5.000,
            'total_amount'      => 25.000,
            'tenant_portion'    => 25.000,
            'due_date'          => '2024-03-10',
            'status'            => 'issued',
        ]);

        $this->get(route('invoices.show', $inv))
            ->assertStatus(200)
            ->assertSee($bill->bill_number);
    }

    // ── EDIT / UPDATE ─────────────────────────────────────────────

    public function test_edit_form_renders(): void
    {
        $inv = $this->makeInvoice();
        $this->get(route('invoices.edit', $inv))->assertStatus(200);
    }

    public function test_can_update_invoice(): void
    {
        $inv    = $this->makeInvoice();
        $tenant = $this->makeTenant(['name' => 'Updated Tenant']);

        $this->put(route('invoices.update', $inv), [
            'tenant_id'    => $tenant->id,
            'type'         => 'utilities',
            'invoice_date' => '2024-04-01',
            'status'       => 'issued',
            'lines'        => [['property_name' => 'Test Property', 'amount' => '75.500']],
        ])->assertRedirect(route('invoices.show', $inv));

        $this->assertDatabaseHas('invoices', ['id' => $inv->id, 'type' => 'utilities', 'amount' => 75.500]);
    }

    // ── DELETE ────────────────────────────────────────────────────

    public function test_can_delete_invoice(): void
    {
        $inv = $this->makeInvoice();
        $this->delete(route('invoices.destroy', $inv))
            ->assertRedirect(route('invoices.index'));
        $this->assertDatabaseMissing('invoices', ['id' => $inv->id]);
    }

    // ── SYNC STATUS ───────────────────────────────────────────────

    public function test_sync_status_issued_when_no_payments(): void
    {
        $inv = $this->makeInvoice();
        $inv->syncStatus();
        $this->assertSame('issued', $inv->fresh()->status);
    }

    public function test_sync_status_does_not_change_manually_marked_overdue(): void
    {
        $inv = $this->makeInvoice(['status' => 'overdue']);
        $inv->syncStatus();
        $this->assertSame('overdue', $inv->fresh()->status);
    }

    public function test_sync_status_partially_paid(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000]);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 50.000,
            'payment_date'   => now(),
            'method'         => 'cash',
        ]);
        $inv->syncStatus();
        $this->assertSame('partially_paid', $inv->fresh()->status);
    }

    public function test_sync_status_paid_when_fully_paid(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000]);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 100.000,
            'payment_date'   => now(),
            'method'         => 'cash',
        ]);
        $inv->syncStatus();
        $this->assertSame('paid', $inv->fresh()->status);
    }

    public function test_sync_status_does_not_change_cancelled(): void
    {
        $inv = $this->makeInvoice(['status' => 'cancelled']);
        $inv->syncStatus();
        $this->assertSame('cancelled', $inv->fresh()->status);
    }

    // ── COMPUTED ATTRIBUTES ───────────────────────────────────────

    public function test_balance_due_is_amount_when_no_payments(): void
    {
        $inv = $this->makeInvoice(['amount' => 200.000]);
        $this->assertEquals(200.000, $inv->balance_due);
    }

    public function test_balance_due_decreases_with_payment(): void
    {
        $inv = $this->makeInvoice(['amount' => 200.000]);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 80.000,
            'payment_date'   => now(),
            'method'         => 'cash',
        ]);
        $inv->load('payments');
        $this->assertEqualsWithDelta(120.000, $inv->balance_due, 0.001);
    }

    public function test_vat_amount_is_computed_from_rate(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000, 'vat_rate' => 10]);
        $inv->recomputeTotals();
        $inv->save();
        $this->assertEqualsWithDelta(10.000, $inv->vat_amount, 0.001);
        $this->assertEqualsWithDelta(110.000, $inv->total_incl_vat, 0.001);
    }

    public function test_amount_in_words_spells_out_bahraini_dinars(): void
    {
        $inv = $this->makeInvoice(['amount' => 750.000]);
        $this->assertSame('Bahraini Dinar Seven Hundred Fifty Only.', $inv->amount_in_words);
    }

    // ── BULK MONTHLY GENERATION ─────────────────────────────────────

    public function test_generate_monthly_groups_tenants_active_leases_into_one_invoice(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract([
            'tenant_id'        => $tenant->id,
            'property_name'    => 'Property A',
            'unit'             => 'Flat 1',
            'rent_per_month'   => 100.000,
            'lease_start_date' => now()->subMonth()->format('Y-m-d'),
            'lease_end_date'   => now()->addMonth()->format('Y-m-d'),
        ]);
        $this->makeContract([
            'tenant_id'        => $tenant->id,
            'property_name'    => 'Property B',
            'unit'             => 'Flat 2',
            'rent_per_month'   => 150.000,
            'lease_start_date' => now()->subMonth()->format('Y-m-d'),
            'lease_end_date'   => now()->addMonth()->format('Y-m-d'),
        ]);

        $this->post(route('invoices.generate-monthly'))->assertRedirect(route('invoices.index'));

        $invoices = Invoice::where('tenant_id', $tenant->id)->get();
        $this->assertCount(1, $invoices);
        $this->assertCount(2, $invoices->first()->lines);
        $this->assertEquals(250.000, $invoices->first()->amount);
    }

    public function test_generate_monthly_skips_tenant_with_existing_invoice_this_month(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract([
            'tenant_id'        => $tenant->id,
            'rent_per_month'   => 100.000,
            'lease_start_date' => now()->subMonth()->format('Y-m-d'),
            'lease_end_date'   => now()->addMonth()->format('Y-m-d'),
        ]);

        $this->post(route('invoices.generate-monthly'));
        $this->post(route('invoices.generate-monthly'));

        $this->assertCount(1, Invoice::where('tenant_id', $tenant->id)->get());
    }
}
