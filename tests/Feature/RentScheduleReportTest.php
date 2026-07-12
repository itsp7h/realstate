<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentScheduleReportTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_renders_without_a_tenant_selected(): void
    {
        $this->get(route('reports.rent-schedule'))->assertStatus(200);
    }

    public function test_shows_schedule_for_selected_tenant(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);

        $response = $this->get(route('reports.rent-schedule', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);

        $rows = $response->viewData('rows');
        $this->assertTrue($rows->isNotEmpty());
    }

    public function test_date_range_filters_apply(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);

        $response = $this->get(route('reports.rent-schedule', [
            'tenant_id' => $tenant->id,
            'date_from' => '2026-03-01',
            'date_to'   => '2026-05-31',
        ]));

        $rows = $response->viewData('rows');
        $this->assertCount(3, $rows);
    }

    public function test_pdf_downloads(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);

        $response = $this->get(route('reports.rent-schedule.pdf', ['tenant_id' => $tenant->id]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_never_invoiced_month_shows_up_in_view(): void
    {
        $tenant = $this->makeTenant();
        $this->makeContract($tenant);

        $response = $this->get(route('reports.rent-schedule', [
            'tenant_id' => $tenant->id,
            'date_from' => '2026-01-01',
            'date_to'   => '2026-01-31',
        ]));

        $response->assertStatus(200)->assertSee('Not Invoiced');
    }
}
