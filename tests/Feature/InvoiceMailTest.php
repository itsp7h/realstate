<?php

namespace Tests\Feature;

use App\Mail\InvoiceIssuedMail;
use App\Models\LeaseContract;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceMailTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
        ], $overrides));
    }

    public function test_storing_an_invoice_emails_tenant_with_email_on_file(): void
    {
        Mail::fake();
        $tenant = $this->makeTenant(['email' => 'tenant@example.com']);

        $this->post(route('invoices.store'), [
            'tenant_id'    => $tenant->id,
            'type'         => 'rent',
            'invoice_date' => '2024-04-01',
            'lines'        => [
                ['property_name' => 'Test Property', 'unit' => 'Flat 1', 'amount' => '250.000'],
            ],
        ])->assertRedirect();

        Mail::assertSent(InvoiceIssuedMail::class, function (InvoiceIssuedMail $mail) use ($tenant) {
            return $mail->invoice->tenant_id === $tenant->id
                && $mail->hasTo('tenant@example.com');
        });
    }

    public function test_storing_an_invoice_does_not_email_tenant_without_email(): void
    {
        Mail::fake();
        $tenant = $this->makeTenant(['email' => null]);

        $this->post(route('invoices.store'), [
            'tenant_id'    => $tenant->id,
            'type'         => 'rent',
            'invoice_date' => '2024-04-01',
            'lines'        => [
                ['property_name' => 'Test Property', 'unit' => 'Flat 1', 'amount' => '250.000'],
            ],
        ])->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_generate_monthly_emails_each_tenant_with_email_on_file(): void
    {
        Mail::fake();
        $tenant = $this->makeTenant(['email' => 'tenant@example.com']);

        LeaseContract::create([
            'date'               => '2024-01-01',
            'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Test Property',
            'lease_start_date'   => now()->subMonth()->toDateString(),
            'lease_end_date'     => now()->addYear()->toDateString(),
            'rent_per_month'     => 300.000,
        ]);

        $this->post(route('invoices.generate-monthly'))->assertRedirect();

        Mail::assertSent(InvoiceIssuedMail::class, 1);
    }

    public function test_mail_includes_the_invoice_pdf_as_an_attachment(): void
    {
        $tenant = $this->makeTenant(['email' => 'tenant@example.com']);

        $invoice = new Invoice([
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'tenant_code'    => $tenant->tenant_code,
            'property_name'  => 'Test Property',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'amount' => 250]],
            'vat_rate'       => 0,
            'invoice_date'   => '2024-04-01',
            'status'         => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();

        $mail = new InvoiceIssuedMail($invoice);
        $attachments = $mail->attachments();

        $this->assertCount(1, $attachments);
    }
}
