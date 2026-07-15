<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(array $overrides = []): Invoice
    {
        $tenant = Tenant::create([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
        ]);

        $amount = $overrides['amount'] ?? 200.000;
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

    public function test_can_record_payment(): void
    {
        $inv = $this->makeInvoice();

        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '100.000',
            'payment_date' => '2024-03-15',
            'method'       => 'cash',
        ])->assertRedirect(route('invoices.show', $inv));

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $inv->id,
            'method'     => 'cash',
        ]);
    }

    public function test_payment_updates_invoice_status_to_partially_paid(): void
    {
        $inv = $this->makeInvoice(['amount' => 200.000]);

        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '50.000',
            'payment_date' => '2024-03-15',
            'method'       => 'bank_transfer',
        ]);

        $this->assertSame('partially_paid', $inv->fresh()->status);
    }

    public function test_payment_updates_invoice_status_to_paid(): void
    {
        $inv = $this->makeInvoice(['amount' => 200.000]);

        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '200.000',
            'payment_date' => '2024-03-15',
            'method'       => 'cheque',
            'reference'    => 'CHQ-001',
        ]);

        $this->assertSame('paid', $inv->fresh()->status);
    }

    public function test_store_validates_required_fields(): void
    {
        $inv = $this->makeInvoice();
        $this->post(route('invoices.payments.store', $inv), [])
            ->assertSessionHasErrors(['amount', 'payment_date', 'method']);
    }

    public function test_store_validates_amount_minimum(): void
    {
        $inv = $this->makeInvoice();
        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '0',
            'payment_date' => '2024-03-15',
            'method'       => 'cash',
        ])->assertSessionHasErrors(['amount']);
    }

    public function test_store_validates_method_values(): void
    {
        $inv = $this->makeInvoice();
        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '50.000',
            'payment_date' => '2024-03-15',
            'method'       => 'bitcoin',
        ])->assertSessionHasErrors(['method']);
    }

    public function test_payment_number_is_auto_generated(): void
    {
        $inv = $this->makeInvoice();

        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '50.000',
            'payment_date' => '2024-03-15',
            'method'       => 'cash',
        ]);

        $pmt = Payment::where('invoice_id', $inv->id)->first();
        $this->assertNotNull($pmt);
        $this->assertStringStartsWith('PAY-', $pmt->payment_number);
    }

    // ── DESTROY ───────────────────────────────────────────────────

    public function test_can_delete_payment(): void
    {
        $inv = $this->makeInvoice();
        $pmt = Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 50.000,
            'payment_date'   => '2024-03-15',
            'method'         => 'cash',
        ]);

        $this->delete(route('invoices.payments.destroy', [$inv, $pmt]))
            ->assertRedirect(route('invoices.show', $inv));

        $this->assertDatabaseMissing('payments', ['id' => $pmt->id]);
    }

    public function test_deleting_payment_resyncs_status(): void
    {
        $inv = $this->makeInvoice(['amount' => 100.000]);
        $pmt = Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 100.000,
            'payment_date'   => '2024-03-15',
            'method'         => 'cash',
        ]);
        $inv->syncStatus();
        $this->assertSame('paid', $inv->fresh()->status);

        $this->delete(route('invoices.payments.destroy', [$inv, $pmt]));
        $this->assertSame('issued', $inv->fresh()->status);
    }

    // ── RECEIPT ───────────────────────────────────────────────────

    public function test_receipt_download_returns_pdf(): void
    {
        $inv = $this->makeInvoice();
        $pmt = Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 100.000,
            'payment_date'   => '2024-03-15',
            'method'         => 'cash',
        ]);

        $response = $this->get(route('invoices.payments.receipt', [$inv, $pmt]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_receipt_preview_streams_inline_pdf(): void
    {
        $inv = $this->makeInvoice();
        $pmt = Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inv->id,
            'amount'         => 100.000,
            'payment_date'   => '2024-03-15',
            'method'         => 'cash',
        ]);

        $response = $this->get(route('invoices.payments.receipt.preview', [$inv, $pmt]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_receipt_stylesheet_does_not_zero_out_page_margin(): void
    {
        // DomPDF silently cancels an @page margin if the stylesheet also
        // has a bare `* { margin: 0 }` reset — this bit us once (the
        // receipt looked print-ready but rendered with 0mm margins). Guard
        // against reintroducing exactly that combination.
        $source = file_get_contents(resource_path('views/payments/receipt.blade.php'));

        $this->assertMatchesRegularExpression('/@page\s*\{[^}]*margin-left\s*:\s*\d/', $source);
        $this->assertDoesNotMatchRegularExpression('/\*\s*\{[^}]*\bmargin\s*:\s*0/', $source);
    }

    // ── PAYMENT METHODS ───────────────────────────────────────────

    public function test_all_payment_methods_accepted(): void
    {
        foreach (['cash', 'bank_transfer', 'cheque', 'online_card'] as $method) {
            $inv = $this->makeInvoice();
            $this->post(route('invoices.payments.store', $inv), [
                'amount'       => '50.000',
                'payment_date' => '2024-03-15',
                'method'       => $method,
            ])->assertSessionHasNoErrors();
        }
    }
}
