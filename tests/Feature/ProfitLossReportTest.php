<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitLossReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeBuilding(array $overrides = []): Building
    {
        return Building::create(array_merge([
            'property_name' => 'Tower A',
            'property_code' => 'TA1',
        ], $overrides));
    }

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
            'property_name'  => 'Tower A',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Tower A', 'amount' => 100.000]],
            'vat_rate'       => 0,
            'invoice_date'   => now()->subDays(5)->format('Y-m-d'),
            'status'         => 'issued',
        ], $overrides));
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    public function test_profit_loss_renders_without_filters(): void
    {
        $this->get(route('reports.profit-loss'))->assertStatus(200);
    }

    public function test_profit_loss_shows_collected_rent_revenue(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.profit-loss'));
        $response->assertStatus(200);
        $this->assertEquals(100.000, $response->viewData('statement')['revenue']['rent_collected']);
    }

    public function test_profit_loss_filters_by_building(): void
    {
        $building = $this->makeBuilding();
        $tenant   = $this->makeTenant();
        $invoice  = $this->makeInvoice($tenant, ['property_name' => 'Tower A']);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.profit-loss', ['building_id' => $building->id]));
        $response->assertStatus(200);
        $this->assertEquals(100.000, $response->viewData('statement')['total_revenue']);
        $this->assertEmpty($response->viewData('breakdown'));
    }

    public function test_profit_loss_filters_by_tenant(): void
    {
        $tenant = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 100.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        $response = $this->get(route('reports.profit-loss', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertEquals(100.000, $response->viewData('statement')['total_revenue']);
    }

    public function test_profit_loss_pdf_downloads(): void
    {
        $response = $this->get(route('reports.profit-loss.pdf'));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
