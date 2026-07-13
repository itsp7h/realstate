<?php

namespace Tests\Feature;

use App\Models\EwaBill;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
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
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Test Property',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'amount' => 100.000]],
            'vat_rate'       => 0,
            'invoice_date'   => now()->subDays(10)->format('Y-m-d'),
            'status'         => 'issued',
        ], $overrides));
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    // ── LANDING PAGE ─────────────────────────────────────────────

    public function test_reports_index_renders(): void
    {
        $this->get(route('reports.index'))->assertStatus(200);
    }

    // ── TENANT STATEMENT ─────────────────────────────────────────

    public function test_tenant_statement_renders_without_a_tenant_selected(): void
    {
        $this->get(route('reports.tenant-statement'))->assertStatus(200);
    }

    public function test_tenant_statement_shows_outstanding_invoice(): void
    {
        $tenant = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-statement', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200)->assertSee($invoice->invoice_number)->assertSee('100.000 Dr');
    }

    public function test_tenant_statement_excludes_fully_paid_invoices(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        \App\Models\Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now(),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.tenant-statement', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200)->assertDontSee($invoice->invoice_number);
    }

    public function test_tenant_statement_shows_invoice_scoped_credit_note_as_its_own_line(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        \App\Models\InvoiceNote::create([
            'note_number' => 'CN-TEST-' . uniqid(),
            'invoice_id'  => $invoice->id,
            'tenant_id'   => $tenant->id,
            'type'        => 'credit',
            'amount'      => 40.000,
            'note_date'   => now()->format('Y-m-d'),
            'reason'      => 'Goodwill discount',
        ]);

        $response = $this->get(route('reports.tenant-statement', ['tenant_id' => $tenant->id]));

        // The invoice's own row now shows its balance before the note (100
        // less nothing paid), and the note appears as its own separate line
        // referencing the invoice, instead of being silently netted in.
        $response->assertStatus(200)
            ->assertSee($invoice->invoice_number)
            ->assertSee('100.000 Dr')
            ->assertSee('Credit Note — Goodwill discount (Inv ' . $invoice->invoice_number . ')')
            ->assertSee('40.000 Cr');

        // Running total still nets out correctly: 100 invoice - 40 credit = 60 outstanding.
        $response->assertSee('60.000 Dr');
    }

    public function test_tenant_statement_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-statement.pdf', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    // ── TENANT AGEING ────────────────────────────────────────────

    public function test_tenant_ageing_buckets_a_recent_invoice_under_60_days(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, ['invoice_date' => now()->subDays(10)->format('Y-m-d')]);

        $response = $this->get(route('reports.tenant-ageing', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $rows = $response->viewData('rows');
        $this->assertSame('lt60', $rows->first()['bucket']);
    }

    public function test_tenant_ageing_shows_opening_amount_column(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-ageing', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200)
            ->assertSee('Opening (BHD)')
            ->assertSee('100.000 Dr');
    }

    public function test_tenant_ageing_buckets_an_old_invoice_over_120_days(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, ['invoice_date' => now()->subDays(150)->format('Y-m-d')]);

        $response = $this->get(route('reports.tenant-ageing', [
            'tenant_id' => $tenant->id,
            'date_from' => now()->subYear()->format('Y-m-d'),
        ]));
        $rows = $response->viewData('rows');
        $this->assertSame('gt120', $rows->first()['bucket']);
    }

    public function test_tenant_ageing_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-ageing.pdf', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    // ── GROUP OUTSTANDING AGEING ─────────────────────────────────

    public function test_group_ageing_includes_tenants_with_outstanding_balances(): void
    {
        $tenant = $this->makeTenant(['name' => 'Outstanding Tenant']);
        $this->makeInvoice($tenant);

        $paidTenant = $this->makeTenant(['name' => 'Paid Up Tenant']);
        $paidInvoice = $this->makeInvoice($paidTenant);
        \App\Models\Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $paidInvoice->id,
            'amount'         => 100.000,
            'payment_date'   => now(),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.group-ageing'));
        $response->assertStatus(200)
            ->assertSee('Outstanding Tenant')
            ->assertDontSee('Paid Up Tenant');
    }

    public function test_group_ageing_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.group-ageing.pdf'));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    // ── EWA BILLS APPEAR IN THE LEDGER TOO ────────────────────────

    public function test_ewa_bill_appears_in_tenant_statement_when_outstanding(): void
    {
        $tenant   = $this->makeTenant();
        $contract = LeaseContract::create([
            'date'               => now()->format('Y-m-d'),
            'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Test Property',
            'lease_start_date'   => now()->subYear()->format('Y-m-d'),
            'lease_end_date'     => now()->addYear()->format('Y-m-d'),
        ]);
        $bill = EwaBill::create([
            'bill_number'       => 'EWA-TEST-' . uniqid(),
            'lease_contract_id' => $contract->id,
            'tenant_name'       => $tenant->name,
            'billing_period'    => 'July 2026',
            'reading_type'      => 'actual',
            'reading_date'      => now()->subDays(5)->format('Y-m-d'),
            'elec_charges'      => 20.000,
            'water_charges'     => 5.000,
            'total_amount'      => 25.000,
            'tenant_portion'    => 25.000,
            'due_date'          => now()->subDays(5)->format('Y-m-d'),
            'status'            => 'issued',
        ]);

        $response = $this->get(route('reports.tenant-statement', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200)->assertSee($bill->bill_number);
    }
}
