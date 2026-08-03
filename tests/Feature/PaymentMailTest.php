<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentMailTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(array $overrides = []): Invoice
    {
        $tenant = Tenant::create([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
            'email'       => array_key_exists('tenant_email', $overrides) ? $overrides['tenant_email'] : 'tenant@example.com',
        ]);
        unset($overrides['tenant_email']);

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

    public function test_recording_a_payment_emails_a_receipt_when_tenant_has_email(): void
    {
        Mail::fake();
        $inv = $this->makeInvoice();

        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '100.000',
            'payment_date' => '2024-03-15',
            'method'       => 'cash',
        ])->assertRedirect();

        Mail::assertSent(PaymentReceiptMail::class, function (PaymentReceiptMail $mail) use ($inv) {
            return $mail->invoice->id === $inv->id && $mail->hasTo('tenant@example.com');
        });
    }

    public function test_recording_a_payment_does_not_email_when_tenant_has_no_email(): void
    {
        Mail::fake();
        $inv = $this->makeInvoice(['tenant_email' => null]);

        $this->post(route('invoices.payments.store', $inv), [
            'amount'       => '100.000',
            'payment_date' => '2024-03-15',
            'method'       => 'cash',
        ])->assertRedirect();

        Mail::assertNothingSent();
    }
}
