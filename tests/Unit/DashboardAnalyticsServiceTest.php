<?php

namespace Tests\Unit;

use App\Models\Building;
use App\Models\EwaBill;
use App\Models\Invoice;
use App\Models\InvoiceNote;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Services\DashboardAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardAnalyticsService::class);
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
            'invoice_date'   => now()->format('Y-m-d'),
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

    public function test_monthly_series_places_income_in_the_correct_month(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);
        $this->payInvoice($invoice, 100.000, now()->startOfMonth()->addDays(2)->format('Y-m-d'));

        $result = $this->service->monthlySeries(now()->year);

        $this->assertCount(12, $result['labels']);
        $currentMonthIndex = now()->month - 1;
        $this->assertEquals(100.0, $result['income'][$currentMonthIndex]);
    }

    public function test_monthly_series_marks_future_months_as_null(): void
    {
        $result = $this->service->monthlySeries(now()->year);

        foreach ($result['income'] as $index => $value) {
            if ($index > now()->month - 1) {
                $this->assertNull($value, "Month index {$index} should be null (future)");
            }
        }
    }

    public function test_monthly_series_buckets_credit_and_debit_notes(): void
    {
        $tenant  = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant);

        InvoiceNote::create([
            'note_number' => 'CN-TEST-1',
            'invoice_id'  => $invoice->id,
            'tenant_id'   => $tenant->id,
            'type'        => 'credit',
            'amount'      => 15.000,
            'note_date'   => now()->format('Y-m-d'),
            'reason'      => 'Test credit',
        ]);
        InvoiceNote::create([
            'note_number' => 'DN-TEST-1',
            'invoice_id'  => $invoice->id,
            'tenant_id'   => $tenant->id,
            'type'        => 'debit',
            'amount'      => 7.000,
            'note_date'   => now()->format('Y-m-d'),
            'reason'      => 'Test debit',
        ]);

        $result = $this->service->monthlySeries(now()->year);
        $currentMonthIndex = now()->month - 1;

        $this->assertEquals(15.0, $result['credits'][$currentMonthIndex]);
        $this->assertEquals(7.0, $result['debits'][$currentMonthIndex]);
    }

    public function test_building_performance_computes_income_occupancy_and_expenses(): void
    {
        $building = $this->makeBuilding();
        $unit     = PropertyUnit::create([
            'building_id'   => $building->id,
            'property_name' => 'Tower A',
            'property_code' => 'TA1',
            'unit_name'     => 'Flat 1',
        ]);
        PropertyUnit::create([
            'building_id'   => $building->id,
            'property_name' => 'Tower A',
            'property_code' => 'TA1',
            'unit_name'     => 'Flat 2',
        ]);

        $tenant   = $this->makeTenant();
        $invoice  = $this->makeInvoice($tenant, ['unit' => 'Flat 1', 'lines' => [['property_name' => 'Tower A', 'unit' => 'Flat 1', 'amount' => 100.000]]]);
        $this->payInvoice($invoice, 100.000);

        LeaseContract::create([
            'date'               => now()->format('Y-m-d'),
            'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Tower A',
            'unit_id'            => $unit->id,
            'unit'               => 'Flat 1',
            'lease_start_date'   => now()->subMonth()->format('Y-m-d'),
            'lease_end_date'     => now()->addMonth()->format('Y-m-d'),
        ]);

        EwaBill::create([
            'bill_number'    => 'EWA-TEST-' . uniqid(),
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Tower A',
            'billing_period' => now()->format('F Y'),
            'reading_type'   => 'actual',
            'reading_date'   => now()->format('Y-m-d'),
            'elec_charges'   => 30.000,
            'water_charges'  => 10.000,
            'total_amount'   => 40.000,
            'due_date'       => now()->addDays(10)->format('Y-m-d'),
            'status'         => 'issued',
        ]);

        MaintenanceRequest::create([
            'date'                => now()->format('Y-m-d'),
            'property'            => 'Tower A',
            'tenant'              => $tenant->name,
            'flat'                => 'Flat 1',
            'building_id'         => $building->id,
            'contact_no'          => '+973 1111 2222',
            'available_datetime'  => now(),
            'apartment_status'    => 'occupied',
            'status'              => 'approved',
            'quotation_1'         => 20.000,
            'selected_quotation'  => 1,
            'approved_dept_head'  => 'Dept Head A',
        ]);

        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();
        $result = $this->service->buildingPerformance($from, $to);

        $this->assertCount(1, $result);
        $perf = $result->first();

        $this->assertEquals(100.0, $perf['total_income']);
        $this->assertEquals(50, $perf['occupancy_percent']);
        $this->assertEquals(1, $perf['tenant_count']);
        $this->assertEquals(30.0, $perf['expenses']['electricity']);
        $this->assertEquals(10.0, $perf['expenses']['water']);
        $this->assertEquals(20.0, $perf['expenses']['maintenance']);
        $this->assertEquals(80.0, $perf['net_income']);
    }

    public function test_building_performance_handles_building_with_no_units_without_division_error(): void
    {
        $this->makeBuilding();

        $result = $this->service->buildingPerformance(now()->startOfMonth(), now()->endOfMonth());

        $this->assertEquals(0, $result->first()['occupancy_percent']);
    }

    public function test_building_dashboard_computes_kpis_and_breakdowns(): void
    {
        $building = $this->makeBuilding();
        $unitA = PropertyUnit::create([
            'building_id' => $building->id, 'property_name' => 'Tower A', 'property_code' => 'TA1',
            'unit_name' => 'Flat 1', 'unit_condition' => 'Furnished', 'unit_type' => 'Apartment',
        ]);
        $unitB = PropertyUnit::create([
            'building_id' => $building->id, 'property_name' => 'Tower A', 'property_code' => 'TA1',
            'unit_name' => 'Flat 2', 'unit_condition' => 'Fitted', 'unit_type' => 'Studio',
        ]);

        $tenant = $this->makeTenant();
        $activeContract = LeaseContract::create([
            'date' => now()->format('Y-m-d'), 'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name, 'property_name' => 'Tower A',
            'unit_id' => $unitA->id, 'unit' => 'Flat 1',
            'lease_start_date' => now()->subMonth()->format('Y-m-d'), 'lease_end_date' => now()->addDays(10)->format('Y-m-d'),
        ]);
        $expiredContract = LeaseContract::create([
            'date' => now()->format('Y-m-d'), 'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name, 'property_name' => 'Tower A',
            'unit_id' => $unitB->id, 'unit' => 'Flat 2',
            'lease_start_date' => now()->subYear()->format('Y-m-d'), 'lease_end_date' => now()->subMonth()->format('Y-m-d'),
        ]);
        $contracts = collect([$activeContract, $expiredContract]);
        $units = PropertyUnit::whereIn('id', [$unitA->id, $unitB->id])->with('activeContract')->get();

        MaintenanceRequest::create([
            'date' => now()->format('Y-m-d'), 'job_order' => 'JO-TEST-1', 'property' => 'Tower A',
            'tenant' => $tenant->name, 'flat' => 'Flat 1', 'building_id' => $building->id,
            'contact_no' => '+973 1111 2222', 'available_datetime' => now(), 'apartment_status' => 'occupied',
            'status' => 'in_progress',
        ]);

        $result = $this->service->buildingDashboard($building, $units, $contracts, now()->year);

        $this->assertEquals(2, $result['kpis']['total_units']);
        $this->assertEquals(50, $result['kpis']['occupancy_percent']);
        $this->assertEquals(['Furnished' => 1, 'Fitted' => 1], $result['unit_conditions']->toArray());
        $this->assertEquals(1, $result['lease_status_counts']['expiring']);
        $this->assertEquals(1, $result['lease_status_counts']['expired']);
        $this->assertCount(1, $result['upcoming_expirations']);
        $this->assertTrue($result['upcoming_expirations']->first()->is($activeContract));
        $this->assertCount(1, $result['recent_maintenance']);
        $this->assertEquals('JO-TEST-1', $result['recent_maintenance']->first()->job_order);
    }

    public function test_building_dashboard_excludes_lease_expiring_beyond_60_days(): void
    {
        $building = $this->makeBuilding();
        $unit = PropertyUnit::create(['building_id' => $building->id, 'property_name' => 'Tower A', 'property_code' => 'TA1', 'unit_name' => 'Flat 1']);
        $tenant = $this->makeTenant();

        $farContract = LeaseContract::create([
            'date' => now()->format('Y-m-d'), 'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name, 'property_name' => 'Tower A',
            'unit_id' => $unit->id, 'unit' => 'Flat 1',
            'lease_start_date' => now()->subMonth()->format('Y-m-d'), 'lease_end_date' => now()->addDays(120)->format('Y-m-d'),
        ]);
        $contracts = collect([$farContract]);
        $units = PropertyUnit::whereIn('id', [$unit->id])->with('activeContract')->get();

        $result = $this->service->buildingDashboard($building, $units, $contracts, now()->year);

        $this->assertCount(0, $result['upcoming_expirations']);
    }
}
