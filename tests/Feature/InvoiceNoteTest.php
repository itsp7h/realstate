<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceNote;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNoteTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(array $overrides = []): Invoice
    {
        $tenant = Tenant::create([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
        ]);

        $amount = $overrides['amount'] ?? 1000.000;
        unset($overrides['amount']);

        $invoice = new Invoice(array_merge([
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'tenant_code'    => $tenant->tenant_code,
            'property_name'  => 'Test Property',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'amount' => $amount]],
            'vat_rate'       => 0,
            'invoice_date'   => '2024-03-01',
            'status'         => 'issued',
        ], $overrides));
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    // ── STORE ─────────────────────────────────────────────────────

    public function test_credit_note_reduces_balance_due(): void
    {
        $invoice = $this->makeInvoice(['amount' => 1000.000]);

        $this->post(route('invoices.notes.store', $invoice), [
            'type'      => 'credit',
            'amount'    => '100.000',
            'note_date' => '2024-03-10',
            'reason'    => 'Overcharged tenant',
        ])->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals(900.000, $invoice->balance_due);
        $this->assertDatabaseHas('invoice_notes', ['invoice_id' => $invoice->id, 'type' => 'credit', 'amount' => 100.000]);
    }

    public function test_invoice_scoped_note_also_gets_tenant_id(): void
    {
        $invoice = $this->makeInvoice(['amount' => 1000.000]);

        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '50.000', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ]);

        $this->assertDatabaseHas('invoice_notes', ['invoice_id' => $invoice->id, 'tenant_id' => $invoice->tenant_id]);
    }

    public function test_debit_note_increases_balance_due(): void
    {
        $invoice = $this->makeInvoice(['amount' => 800.000]);

        $this->post(route('invoices.notes.store', $invoice), [
            'type'      => 'debit',
            'amount'    => '200.000',
            'note_date' => '2024-03-10',
            'reason'    => 'Undercharged tenant',
        ])->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals(1000.000, $invoice->balance_due);
    }

    public function test_note_number_uses_correct_prefix(): void
    {
        $invoice = $this->makeInvoice();

        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '50.000', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ]);
        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'debit', 'amount' => '50.000', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ]);

        $this->assertStringStartsWith('CN-', InvoiceNote::where('type', 'credit')->first()->note_number);
        $this->assertStringStartsWith('DN-', InvoiceNote::where('type', 'debit')->first()->note_number);
    }

    public function test_credit_note_larger_than_balance_drives_it_negative(): void
    {
        $invoice = $this->makeInvoice(['amount' => 100.000]);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => '2024-03-05',
            'method'         => 'cash',
        ]);

        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '150.000', 'note_date' => '2024-03-10', 'reason' => 'Refund owed',
        ]);

        $invoice->refresh();
        $this->assertEquals(-150.000, $invoice->balance_due);
    }

    // ── VALIDATION ────────────────────────────────────────────────

    public function test_store_fails_with_invalid_type(): void
    {
        $invoice = $this->makeInvoice();
        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'invalid', 'amount' => '50.000', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ])->assertSessionHasErrors('type');
    }

    public function test_store_fails_with_zero_amount(): void
    {
        $invoice = $this->makeInvoice();
        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '0', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ])->assertSessionHasErrors('amount');
    }

    public function test_store_fails_without_reason(): void
    {
        $invoice = $this->makeInvoice();
        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '50.000', 'note_date' => '2024-03-10',
        ])->assertSessionHasErrors('reason');
    }

    public function test_store_fails_with_future_date(): void
    {
        $invoice = $this->makeInvoice();
        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '50.000', 'note_date' => now()->addDay()->format('Y-m-d'), 'reason' => 'Test',
        ])->assertSessionHasErrors('note_date');
    }

    // ── DESTROY ───────────────────────────────────────────────────

    public function test_deleting_note_restores_prior_balance(): void
    {
        $invoice = $this->makeInvoice(['amount' => 1000.000]);
        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '100.000', 'note_date' => '2024-03-10', 'reason' => 'Test',
        ]);
        $note = InvoiceNote::first();
        $invoice->refresh();
        $this->assertEquals(900.000, $invoice->balance_due);

        $this->delete(route('invoices.notes.destroy', [$invoice, $note]))
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertEquals(1000.000, $invoice->balance_due);
        $this->assertDatabaseMissing('invoice_notes', ['id' => $note->id]);
    }

    // ── STATUS SYNC ───────────────────────────────────────────────

    public function test_invoice_fully_offset_by_credit_note_becomes_paid(): void
    {
        $invoice = $this->makeInvoice(['amount' => 500.000]);

        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '500.000', 'note_date' => '2024-03-10', 'reason' => 'Full waiver',
        ]);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_invoice_partially_offset_by_credit_note_stays_issued(): void
    {
        $invoice = $this->makeInvoice(['amount' => 500.000]);

        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '100.000', 'note_date' => '2024-03-10', 'reason' => 'Partial waiver',
        ]);

        $invoice->refresh();
        $this->assertEquals('issued', $invoice->status);
        $this->assertEquals(400.000, $invoice->balance_due);
    }

    public function test_invoice_with_debit_note_and_partial_payment_is_partially_paid(): void
    {
        $invoice = $this->makeInvoice(['amount' => 500.000]);
        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'debit', 'amount' => '100.000', 'note_date' => '2024-03-10', 'reason' => 'Undercharge correction',
        ]);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 300.000,
            'payment_date'   => '2024-03-12',
            'method'         => 'cash',
        ]);
        $invoice->syncStatus();

        $invoice->refresh();
        $this->assertEquals(300.000, $invoice->balance_due);
        $this->assertEquals('partially_paid', $invoice->status);
    }

    // ── SHOW PAGE ─────────────────────────────────────────────────

    public function test_show_page_renders_notes_list_and_issue_form(): void
    {
        $invoice = $this->makeInvoice();
        $this->post(route('invoices.notes.store', $invoice), [
            'type' => 'credit', 'amount' => '50.000', 'note_date' => '2024-03-10', 'reason' => 'Test reason',
        ]);

        $response = $this->get(route('invoices.show', $invoice));
        $response->assertStatus(200)
            ->assertSee('Credit &amp; Debit Notes', false)
            ->assertSee('Test reason')
            ->assertSee('Issue Note');
    }

    public function test_show_page_hides_issue_form_when_cancelled(): void
    {
        $invoice = $this->makeInvoice(['status' => 'cancelled']);

        $response = $this->get(route('invoices.show', $invoice));
        $response->assertStatus(200)->assertDontSee('Issue Note');
    }
}
