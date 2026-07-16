<?php

namespace Tests\Feature;

use App\Models\Building;
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

    private function makeBuilding(array $overrides = []): Building
    {
        return Building::create(array_merge([
            'property_name' => 'Test Property',
            'property_code' => 'TP-' . uniqid(),
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

    public function test_tenant_statement_shows_partial_payment_as_its_own_line(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        \App\Models\Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 40.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.tenant-statement', ['tenant_id' => $tenant->id]));

        // Invoice still shows its full gross amount, the payment appears as
        // its own separate line (rather than being silently netted in), and
        // the running balance still nets out correctly: 100 - 40 = 60.
        $response->assertStatus(200)
            ->assertSee($invoice->invoice_number)
            ->assertSee('100.000 Dr')
            ->assertSee('Payment — Cash (Inv ' . $invoice->invoice_number . ')')
            ->assertSee('40.000 Cr')
            ->assertSee('60.000 Dr');
    }

    public function test_tenant_statement_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-statement.pdf', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_tenant_statement_export_downloads_xlsx(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-statement.export', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    // ── BILL-WISE STATEMENT (one row per bill, netted) ────────────

    public function test_bill_wise_statement_renders_without_a_tenant_selected(): void
    {
        $this->get(route('reports.bill-wise-statement'))->assertStatus(200);
    }

    public function test_bill_wise_statement_shows_outstanding_invoice(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);

        $response = $this->get(route('reports.bill-wise-statement', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200)->assertSee($invoice->invoice_number)->assertSee('100.000 Dr');
    }

    public function test_bill_wise_statement_nets_credit_note_into_bill_instead_of_showing_it_separately(): void
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

        $response = $this->get(route('reports.bill-wise-statement', ['tenant_id' => $tenant->id]));
        $rows = $response->viewData('rows');

        $this->assertCount(1, $rows);
        $this->assertEquals(60.0, $rows->first()['pending_amount']);
    }

    public function test_bill_wise_statement_excludes_fully_paid_invoices(): void
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

        $response = $this->get(route('reports.bill-wise-statement', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200)->assertDontSee($invoice->invoice_number);
    }

    public function test_bill_wise_statement_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.bill-wise-statement.pdf', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_bill_wise_statement_export_downloads_xlsx(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.bill-wise-statement.export', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    // ── TENANT LEDGER (full history, running balance) ────────────

    public function test_tenant_ledger_renders_without_a_tenant_selected(): void
    {
        $this->get(route('reports.tenant-ledger'))->assertStatus(200);
    }

    public function test_tenant_ledger_shows_running_balance_after_each_transaction(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        \App\Models\Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 40.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.tenant-ledger', ['tenant_id' => $tenant->id]));

        $response->assertStatus(200)
            ->assertSee($invoice->invoice_number)
            ->assertSee('100.000') // invoice debit
            ->assertSee('40.000')  // payment credit
            ->assertSee('60.000 Dr'); // running balance after both
    }

    public function test_tenant_ledger_includes_fully_settled_invoices(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        \App\Models\Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        // Unlike the Tenant Statement, the ledger is a full history and
        // must still show a bill even once it's fully paid off.
        $response = $this->get(route('reports.tenant-ledger', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200)->assertSee($invoice->invoice_number);
    }

    public function test_tenant_ledger_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-ledger.pdf', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_tenant_ledger_export_downloads_xlsx(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-ledger.export', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
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

    public function test_tenant_ageing_nets_credit_note_into_bill_instead_of_showing_it_as_its_own_row(): void
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

        $response = $this->get(route('reports.tenant-ageing', ['tenant_id' => $tenant->id]));
        $rows = $response->viewData('rows');

        // Exactly one row (the invoice itself), netted down to 60 — not a
        // separate "Credit Note" row with its own (meaningless) age.
        $this->assertCount(1, $rows);
        $this->assertEquals(60.0, $rows->first()['pending_amount']);
        $this->assertStringNotContainsString('Credit Note', $rows->first()['description']);
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

    public function test_tenant_ageing_export_downloads_xlsx(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.tenant-ageing.export', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
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

    public function test_group_ageing_export_downloads_xlsx(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.group-ageing.export'));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    // ── TENANT FINANCIAL SUMMARY (all tenants, date range) ────────

    public function test_financial_summary_renders_without_any_tenants(): void
    {
        $this->get(route('reports.financial-summary'))->assertStatus(200);
    }

    public function test_financial_summary_computes_opening_amount_received_and_net_balance(): void
    {
        $tenant = $this->makeTenant(['name' => 'Ledger Tenant']);

        // Billed and unpaid before the report range — carries in as the opening balance.
        $this->makeInvoice($tenant, [
            'invoice_date' => now()->subDays(60)->format('Y-m-d'),
        ]);

        // Billed inside the range.
        $inRangeInvoice = $this->makeInvoice($tenant, [
            'invoice_date' => now()->subDays(5)->format('Y-m-d'),
        ]);

        // Paid inside the range, against the in-range invoice.
        \App\Models\Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $inRangeInvoice->id,
            'amount'         => 30.000,
            'payment_date'   => now()->subDays(2)->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.financial-summary', [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to'   => now()->format('Y-m-d'),
        ]));
        $response->assertStatus(200)->assertSee('Ledger Tenant');

        $row = $response->viewData('rows')->firstWhere('tenant.id', $tenant->id);
        $this->assertEquals(100.0, $row['opening_balance']);
        $this->assertEquals(100.0, $row['period_amount']);
        $this->assertEquals(30.0, $row['period_received']);
        // 100 opening + 100 billed in range - 30 received = 170.
        $this->assertEquals(170.0, $row['net_balance']);
    }

    public function test_financial_summary_excludes_tenants_with_no_balance_or_activity(): void
    {
        // No invoices, payments, or notes at all — nothing to report.
        $this->makeTenant(['name' => 'Untouched Tenant']);

        $response = $this->get(route('reports.financial-summary', [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to'   => now()->format('Y-m-d'),
        ]));
        $response->assertStatus(200)->assertDontSee('Untouched Tenant');
    }

    public function test_financial_summary_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.financial-summary.pdf'));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_financial_summary_export_downloads_xlsx(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.financial-summary.export'));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
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

    // ── VAT RETURN ───────────────────────────────────────────────

    public function test_vat_return_renders_without_a_building_selected(): void
    {
        $this->get(route('reports.vat-return'))->assertStatus(200);
    }

    public function test_vat_return_shows_exempt_rent_invoice(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant, ['vat_rate' => 0]);

        $response = $this->get(route('reports.vat-return'));
        $response->assertStatus(200)
            ->assertSee($invoice->invoice_number)
            ->assertSee('EXM-S')
            ->assertSee('100.000');
    }

    public function test_vat_return_shows_standard_rated_invoice_with_s_tax_code(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant, ['vat_rate' => 10]);

        $response = $this->get(route('reports.vat-return'));
        $response->assertStatus(200)
            ->assertSee($invoice->invoice_number)
            ->assertSee('>S<', false);
    }

    public function test_vat_return_filters_by_building(): void
    {
        $buildingA = $this->makeBuilding(['property_name' => 'Building A']);
        $buildingB = $this->makeBuilding(['property_name' => 'Building B']);
        $tenant    = $this->makeTenant();

        $inA = $this->makeInvoice($tenant, ['property_name' => 'Building A']);
        $inB = $this->makeInvoice($tenant, ['property_name' => 'Building B']);

        $response = $this->get(route('reports.vat-return', ['building_id' => $buildingA->id]));
        $response->assertStatus(200)
            ->assertSee($inA->invoice_number)
            ->assertDontSee($inB->invoice_number);
    }

    public function test_vat_return_includes_ewa_bills_as_exempt(): void
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

        $response = $this->get(route('reports.vat-return'));
        $response->assertStatus(200)->assertSee($bill->bill_number)->assertSee('EXM-S');
    }

    public function test_vat_return_shows_totals_row(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, ['lines' => [['property_name' => 'Test Property', 'amount' => 100.000]], 'vat_rate' => 0]);
        $this->makeInvoice($tenant, ['lines' => [['property_name' => 'Test Property', 'amount' => 50.000]], 'vat_rate' => 0]);

        $response = $this->get(route('reports.vat-return'));
        $response->assertStatus(200)->assertSee('150.000');
    }

    public function test_vat_return_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, ['vat_rate' => 10]);

        $response = $this->get(route('reports.vat-return.pdf'));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_vat_return_export_returns_xlsx(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant);

        $response = $this->get(route('reports.vat-return.export'));
        $response->assertStatus(200);
        $this->assertStringContainsString('vat-return-', $response->headers->get('Content-Disposition'));
    }

    public function test_vat_return_export_has_one_tab_per_property(): void
    {
        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, ['property_name' => 'Building One']);
        $this->makeInvoice($tenant, ['property_name' => 'Building Two']);

        $response = $this->get(route('reports.vat-return.export'));
        $response->assertStatus(200);

        $tmpFile = tempnam(sys_get_temp_dir(), 'vat-export') . '.xlsx';
        file_put_contents($tmpFile, $response->streamedContent());

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFile);
        $sheetNames  = $spreadsheet->getSheetNames();
        unlink($tmpFile);

        $this->assertContains('Building One', $sheetNames);
        $this->assertContains('Building Two', $sheetNames);
    }
}
