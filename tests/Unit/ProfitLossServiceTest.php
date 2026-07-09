<?php

namespace Tests\Unit;

use App\Models\Building;
use App\Models\EwaBill;
use App\Models\EwaPayment;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Services\ProfitLossService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProfitLossServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfitLossService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProfitLossService();
    }

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

    private function makeContract(Tenant $tenant, array $overrides = []): LeaseContract
    {
        return LeaseContract::create(array_merge([
            'date'               => now()->format('Y-m-d'),
            'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Tower A',
            'lease_start_date'   => now()->subYear()->format('Y-m-d'),
            'lease_end_date'     => now()->addYear()->format('Y-m-d'),
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

    private function payInvoice(Invoice $invoice, float $amount, ?string $date = null): Payment
    {
        return Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => $amount,
            'payment_date'   => $date ?? now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);
    }

    private function range(): array
    {
        return [now()->subYear()->startOfDay(), now()->endOfDay()];
    }

    public function test_sums_rent_and_utilities_collected(): void
    {
        $tenant = $this->makeTenant();
        $rentInvoice = $this->makeInvoice($tenant, ['type' => 'rent']);
        $this->payInvoice($rentInvoice, 100.000);

        $utilInvoice = $this->makeInvoice($tenant, ['type' => 'utilities', 'lines' => [['property_name' => 'Tower A', 'amount' => 40.000]]]);
        $this->payInvoice($utilInvoice, 40.000);

        [$from, $to] = $this->range();
        $result = $this->service->build($from, $to);

        $this->assertEquals(100.000, $result['revenue']['rent_collected']);
        $this->assertEquals(40.000, $result['revenue']['utilities_collected']);
        $this->assertEquals(140.000, $result['total_revenue']);
    }

    public function test_ewa_payment_counts_as_revenue_and_landlord_portion_as_expense(): void
    {
        $tenant   = $this->makeTenant();
        $contract = $this->makeContract($tenant);

        $bill = EwaBill::create([
            'bill_number'       => 'EWA-TEST-' . uniqid(),
            'lease_contract_id' => $contract->id,
            'tenant_name'       => $tenant->name,
            'property_name'     => 'Tower A',
            'billing_period'    => 'July 2026',
            'reading_type'      => 'actual',
            'reading_date'      => now()->subDays(3)->format('Y-m-d'),
            'elec_charges'      => 30.000,
            'water_charges'     => 10.000,
            'total_amount'      => 40.000,
            'tenant_portion'    => 25.000,
            'due_date'          => now()->subDays(3)->format('Y-m-d'),
            'status'            => 'issued',
        ]);

        EwaPayment::create([
            'payment_number' => 'EWAPAY-TEST-' . uniqid(),
            'ewa_bill_id'    => $bill->id,
            'amount'         => 25.000,
            'payment_date'   => now()->format('Y-m-d'),
            'method'         => 'cash',
        ]);

        [$from, $to] = $this->range();
        $result = $this->service->build($from, $to);

        $this->assertEquals(25.000, $result['revenue']['ewa_collected']);
        $this->assertEquals(15.000, $result['expenses']['ewa_landlord_expense']);
    }

    public function test_approved_maintenance_cost_counts_as_expense(): void
    {
        $building = $this->makeBuilding();

        MaintenanceRequest::create([
            'date'                => now()->subDays(2)->format('Y-m-d'),
            'property'            => 'Tower A',
            'tenant'              => 'Some Tenant',
            'flat'                => '3B',
            'building_id'         => $building->id,
            'contact_no'          => '+973 1111 2222',
            'available_datetime'  => now(),
            'apartment_status'    => 'occupied',
            'status'              => 'approved',
            'quotation_1'         => 75.000,
            'selected_quotation'  => 1,
            'approved_supervisor' => 'Supervisor A',
            'approved_dept_head'  => 'Dept Head A',
        ]);

        [$from, $to] = $this->range();
        $result = $this->service->build($from, $to);

        $this->assertEquals(75.000, $result['expenses']['maintenance_expense']);
    }

    public function test_unapproved_maintenance_request_is_excluded(): void
    {
        $building = $this->makeBuilding();

        MaintenanceRequest::create([
            'date'               => now()->subDays(2)->format('Y-m-d'),
            'property'           => 'Tower A',
            'tenant'             => 'Some Tenant',
            'flat'               => '3B',
            'building_id'        => $building->id,
            'contact_no'         => '+973 1111 2222',
            'available_datetime' => now(),
            'apartment_status'   => 'occupied',
            'status'             => 'waiting_approval',
            'quotation_1'        => 75.000,
            'selected_quotation' => 1,
        ]);

        [$from, $to] = $this->range();
        $result = $this->service->build($from, $to);

        $this->assertEquals(0.0, $result['expenses']['maintenance_expense']);
    }

    public function test_building_filter_isolates_totals(): void
    {
        $buildingA = $this->makeBuilding(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $buildingB = $this->makeBuilding(['property_name' => 'Tower B', 'property_code' => 'TB1']);

        $tenantA = $this->makeTenant(['name' => 'Tenant A']);
        $invoiceA = $this->makeInvoice($tenantA, ['property_name' => 'Tower A', 'lines' => [['property_name' => 'Tower A', 'amount' => 100.000]]]);
        $this->payInvoice($invoiceA, 100.000);

        $tenantB = $this->makeTenant(['name' => 'Tenant B']);
        $invoiceB = $this->makeInvoice($tenantB, ['property_name' => 'Tower B', 'lines' => [['property_name' => 'Tower B', 'amount' => 200.000]]]);
        $this->payInvoice($invoiceB, 200.000);

        [$from, $to] = $this->range();
        $resultA = $this->service->build($from, $to, $buildingA->id);
        $resultB = $this->service->build($from, $to, $buildingB->id);

        $this->assertEquals(100.000, $resultA['total_revenue']);
        $this->assertEquals(200.000, $resultB['total_revenue']);
    }

    public function test_tenant_filter_isolates_totals(): void
    {
        $tenantA = $this->makeTenant(['name' => 'Tenant A']);
        $invoiceA = $this->makeInvoice($tenantA);
        $this->payInvoice($invoiceA, 100.000);

        $tenantB = $this->makeTenant(['name' => 'Tenant B']);
        $invoiceB = $this->makeInvoice($tenantB, ['lines' => [['property_name' => 'Tower A', 'amount' => 200.000]]]);
        $this->payInvoice($invoiceB, 200.000);

        [$from, $to] = $this->range();
        $resultA = $this->service->build($from, $to, null, $tenantA->id);

        $this->assertEquals(100.000, $resultA['total_revenue']);
    }

    public function test_date_range_excluding_everything_returns_zeros(): void
    {
        $tenant = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        $this->payInvoice($invoice, 100.000);

        $result = $this->service->build(Carbon::parse('2000-01-01'), Carbon::parse('2000-01-31'));

        $this->assertEquals(0.0, $result['total_revenue']);
        $this->assertEquals(0.0, $result['total_expense']);
        $this->assertEquals(0.0, $result['net_profit']);
    }

    public function test_net_profit_is_revenue_minus_expense(): void
    {
        $building = $this->makeBuilding();
        $tenant   = $this->makeTenant();
        $invoice  = $this->makeInvoice($tenant, ['property_name' => 'Tower A']);
        $this->payInvoice($invoice, 100.000);

        MaintenanceRequest::create([
            'date'               => now()->subDays(2)->format('Y-m-d'),
            'property'           => 'Tower A',
            'tenant'             => 'Some Tenant',
            'flat'               => '3B',
            'building_id'        => $building->id,
            'contact_no'         => '+973 1111 2222',
            'available_datetime' => now(),
            'apartment_status'   => 'occupied',
            'status'             => 'approved',
            'quotation_1'        => 30.000,
            'selected_quotation' => 1,
            'approved_dept_head' => 'Dept Head A',
        ]);

        [$from, $to] = $this->range();
        $result = $this->service->build($from, $to);

        $this->assertEquals(100.000, $result['total_revenue']);
        $this->assertEquals(30.000, $result['total_expense']);
        $this->assertEquals(70.000, $result['net_profit']);
    }
}
