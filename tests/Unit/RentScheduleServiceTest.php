<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\RentScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RentScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RentScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RentScheduleService();
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
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA-' . uniqid(),
            'tenant_id'          => $tenant->id,
            'tenant_name'        => $tenant->name,
            'property_name'      => 'Test Property',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => '2026-12-31',
            'rent_start_date'    => '2026-01-01',
            'rent_end_date'      => '2026-12-31',
            'rent_per_month'     => 500.000,
        ], $overrides));
    }

    private function makeRentInvoice(Tenant $tenant, string $monthDate, float $amount): Invoice
    {
        $invoice = new Invoice([
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Test Property',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'amount' => $amount]],
            'vat_rate'       => 0,
            'invoice_date'   => $monthDate,
            'status'         => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();

        return $invoice;
    }

    public function test_fully_paid_month_is_marked_paid(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);
        $invoice = $this->makeRentInvoice($tenant, '2026-01-01', 500.000);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 500.000,
            'payment_date'   => '2026-01-05',
            'method'         => 'cash',
        ]);

        $rows = $this->service->build($tenant, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        $this->assertCount(1, $rows);
        $this->assertEquals('paid', $rows->first()['status']);
        $this->assertEquals(0.0, $rows->first()['remaining']);
    }

    public function test_partially_paid_month_shows_remaining_amount(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);
        $invoice = $this->makeRentInvoice($tenant, '2026-02-01', 500.000);
        Payment::create([
            'payment_number' => 'PAY-TEST-' . uniqid(),
            'invoice_id'     => $invoice->id,
            'amount'         => 200.000,
            'payment_date'   => '2026-02-05',
            'method'         => 'cash',
        ]);

        $rows = $this->service->build($tenant, Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

        $this->assertEquals('partial', $rows->first()['status']);
        $this->assertEquals(300.000, $rows->first()['remaining']);
    }

    public function test_invoiced_but_unpaid_month_is_marked_unpaid(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);
        $this->makeRentInvoice($tenant, '2026-03-01', 500.000);

        $rows = $this->service->build($tenant, Carbon::parse('2026-03-01'), Carbon::parse('2026-03-31'));

        $this->assertEquals('unpaid', $rows->first()['status']);
    }

    public function test_never_invoiced_month_is_marked_not_invoiced(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);

        $rows = $this->service->build($tenant, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'));

        $this->assertEquals('not_invoiced', $rows->first()['status']);
        $this->assertEquals(500.000, $rows->first()['remaining']);
        $this->assertEquals(0.0, $rows->first()['invoiced']);
    }

    public function test_months_before_lease_started_are_excluded(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant, ['rent_start_date' => '2026-06-01', 'lease_start_date' => '2026-06-01']);

        $rows = $this->service->build($tenant, Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'));

        $this->assertTrue($rows->every(fn ($r) => $r['month']->greaterThanOrEqualTo(Carbon::parse('2026-06-01'))));
    }

    public function test_concurrent_leases_sum_expected_rent(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant, ['rent_per_month' => 500.000, 'property_name' => 'Property A']);
        $this->makeContract($tenant, ['rent_per_month' => 300.000, 'property_name' => 'Property B']);

        $rows = $this->service->build($tenant, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        $this->assertEquals(800.000, $rows->first()['expected']);
    }

    public function test_date_range_filter_narrows_the_window(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);

        $rows = $this->service->build($tenant, Carbon::parse('2026-03-01'), Carbon::parse('2026-05-31'));

        $this->assertCount(3, $rows);
        $this->assertEquals('March 2026', $rows->first()['month']->format('F Y'));
        $this->assertEquals('May 2026', $rows->last()['month']->format('F Y'));
    }

    public function test_tenant_with_no_rent_bearing_contracts_returns_empty(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant, ['rent_per_month' => null]);

        $rows = $this->service->build($tenant);

        $this->assertTrue($rows->isEmpty());
    }
}
