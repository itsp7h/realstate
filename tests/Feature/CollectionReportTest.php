<?php

namespace Tests\Feature;

use App\Models\EwaBill;
use App\Models\EwaPayment;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
        ], $overrides));
    }

    private function makeInvoice(Tenant $tenant, array $overrides = []): Invoice
    {
        $invoice = new Invoice(array_merge([
            'invoice_number' => 'INV-COL-' . uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Test Property',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'amount' => 100.000]],
            'vat_rate'       => 0,
            'invoice_date'   => now()->subDays(5)->format('Y-m-d'),
            'status'         => 'issued',
        ], $overrides));
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    private function makeEwaBillForTenant(Tenant $tenant, array $overrides = []): EwaBill
    {
        $contract = LeaseContract::create([
            'date'               => now()->subMonths(2)->format('Y-m-d'),
            'lease_agreement_no' => 'LA-COL-' . uniqid(),
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Test Property',
            'lease_start_date'   => now()->subMonths(2)->format('Y-m-d'),
            'lease_end_date'     => now()->addYear()->format('Y-m-d'),
        ]);

        return EwaBill::create(array_merge([
            'bill_number'        => 'EWA-COL-' . uniqid(),
            'lease_contract_id'  => $contract->id,
            'tenant_name'        => $tenant->name,
            'billing_period'     => 'Collection Test',
            'reading_type'       => 'actual',
            'total_amount'       => 40.000,
            'due_date'           => now()->addDays(20)->format('Y-m-d'),
            'status'             => 'issued',
        ], $overrides));
    }

    public function test_renders_without_any_payments(): void
    {
        $this->get(route('reports.collection'))->assertStatus(200);
    }

    public function test_shows_rent_payment_in_range(): void
    {
        $tenant  = $this->makeTenant(['name' => 'Rent Payer']);
        $invoice = $this->makeInvoice($tenant);
        Payment::create([
            'payment_number' => 'PAY-COL-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.collection'));
        $response->assertStatus(200)
            ->assertSee('Rent Payer')
            ->assertSee($invoice->invoice_number);

        $row = $response->viewData('rows')->first();
        $this->assertEquals(100.0, $row['amount']);
        $this->assertNull($row['cheque_number']);
    }

    public function test_shows_cheque_number_and_date_for_cheque_payment(): void
    {
        $tenant  = $this->makeTenant(['name' => 'Cheque Payer']);
        $invoice = $this->makeInvoice($tenant);
        Payment::create([
            'payment_number' => 'PAY-COL-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cheque',
            'cheque_number'  => 'CHQ-4471',
            'cheque_date'    => now()->subDays(2)->format('Y-m-d'),
        ]);

        $response = $this->get(route('reports.collection'));
        $response->assertStatus(200)->assertSee('CHQ-4471');

        $row = $response->viewData('rows')->first();
        $this->assertSame('CHQ-4471', $row['cheque_number']);
        $this->assertSame(now()->subDays(2)->format('Y-m-d'), $row['cheque_date']->format('Y-m-d'));
    }

    public function test_shows_ewa_payment_in_range(): void
    {
        $tenant = $this->makeTenant(['name' => 'EWA Payer']);
        $bill   = $this->makeEwaBillForTenant($tenant);
        EwaPayment::create([
            'payment_number' => 'EWAPAY-COL-' . uniqid(),
            'ewa_bill_id'    => $bill->id,
            'amount'         => 40.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'bank_transfer',
        ]);

        $response = $this->get(route('reports.collection'));
        $response->assertStatus(200)
            ->assertSee('EWA Payer')
            ->assertSee($bill->bill_number);
    }

    public function test_excludes_payments_outside_date_range(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        Payment::create([
            'payment_number' => 'PAY-COL-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->subYears(2)->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.collection', [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to'   => now()->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
        $this->assertTrue($response->viewData('rows')->isEmpty());
    }

    public function test_total_sums_rent_and_ewa_payments(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        Payment::create([
            'payment_number' => 'PAY-COL-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);
        $bill = $this->makeEwaBillForTenant($tenant);
        EwaPayment::create([
            'payment_number' => 'EWAPAY-COL-' . uniqid(),
            'ewa_bill_id'    => $bill->id,
            'amount'         => 40.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.collection'));
        $this->assertEquals(140.0, $response->viewData('total'));
        $response->assertSee('140.000');
    }

    public function test_pdf_downloads(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        Payment::create([
            'payment_number' => 'PAY-COL-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.collection.pdf'));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_export_downloads_xlsx(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        Payment::create([
            'payment_number' => 'PAY-COL-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.collection.export'));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }
}
