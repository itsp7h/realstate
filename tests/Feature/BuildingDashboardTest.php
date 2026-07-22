<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_building_show_page_renders_the_dashboard_tab_by_default(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $response = $this->get(route('buildings.show', $building));

        $response->assertOk();
        $response->assertViewHas('dashboard');
        $response->assertSee('id="panel-dashboard"', false);
        $response->assertSee('Financial Performance', false);
    }

    public function test_building_dashboard_shows_kpis_for_units_and_tenants(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $unit = PropertyUnit::create([
            'building_id' => $building->id, 'property_name' => 'Tower A', 'property_code' => 'TA1', 'unit_name' => 'Flat 1',
        ]);
        $tenant = Tenant::create(['name' => 'Test Tenant', 'tenant_type' => 'individual']);
        LeaseContract::create([
            'date' => now()->format('Y-m-d'), 'lease_agreement_no' => 'LA-TEST-1',
            'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name, 'property_name' => 'Tower A',
            'unit_id' => $unit->id, 'unit' => 'Flat 1',
            'lease_start_date' => now()->subMonth()->format('Y-m-d'), 'lease_end_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $response = $this->get(route('buildings.show', $building));

        $response->assertOk();
        $response->assertViewHas('dashboard', function ($dashboard) {
            return $dashboard['kpis']['total_units'] == 1
                && $dashboard['kpis']['occupied_units'] == 1
                && $dashboard['kpis']['occupancy_percent'] == 100
                && $dashboard['kpis']['tenant_count'] == 1;
        });
    }

    public function test_financial_chart_is_not_empty_when_only_expenses_exist(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        MaintenanceRequest::create([
            'date' => now()->format('Y-m-d'), 'job_order' => 'JO-EXPENSE-ONLY', 'property' => 'Tower A',
            'tenant' => 'Some Tenant', 'flat' => 'Flat 1', 'building_id' => $building->id,
            'contact_no' => '+973 1111 2222', 'available_datetime' => now(), 'apartment_status' => 'occupied',
            'status' => 'approved', 'quotation_1' => 18.000, 'selected_quotation' => 1,
            'approved_dept_head' => 'Dept Head A',
        ]);

        $response = $this->get(route('buildings.show', $building));

        $response->assertOk();
        $response->assertDontSee('No financial activity recorded yet');
        $response->assertSee('id="financeChart"', false);
    }
}
