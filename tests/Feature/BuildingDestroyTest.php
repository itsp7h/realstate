<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\LeaseContract;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_removes_building_and_cascades_to_floors_units_and_leases(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $floor = Floor::create(['building_id' => $building->id, 'floor_name' => 'Ground Floor']);
        $unit = PropertyUnit::create([
            'building_id' => $building->id, 'floor_id' => $floor->id,
            'property_name' => 'Tower A', 'property_code' => 'TA1', 'unit_name' => 'Flat 1',
        ]);
        $tenant = Tenant::create(['name' => 'Solo Tenant', 'tenant_type' => 'individual']);
        $lease = LeaseContract::create([
            'date' => now()->format('Y-m-d'), 'lease_agreement_no' => 'LA-TEST-1',
            'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name,
            'unit_id' => $unit->id, 'unit' => 'Flat 1',
            'lease_start_date' => now()->format('Y-m-d'), 'lease_end_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $this->delete(route('buildings.destroy', $building))
            ->assertRedirect(route('buildings.index'));

        $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
        $this->assertDatabaseMissing('floors', ['id' => $floor->id]);
        $this->assertDatabaseMissing('property_units', ['id' => $unit->id]);
        $this->assertDatabaseMissing('lease_contracts', ['id' => $lease->id]);
        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_destroy_keeps_a_tenant_that_has_a_lease_in_another_building(): void
    {
        $buildingA = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $buildingB = Building::create(['property_name' => 'Tower B', 'property_code' => 'TB1']);
        $unitA = PropertyUnit::create([
            'building_id' => $buildingA->id, 'property_name' => 'Tower A', 'property_code' => 'TA1', 'unit_name' => 'Flat A1',
        ]);
        $unitB = PropertyUnit::create([
            'building_id' => $buildingB->id, 'property_name' => 'Tower B', 'property_code' => 'TB1', 'unit_name' => 'Flat B1',
        ]);
        $tenant = Tenant::create(['name' => 'Shared Tenant', 'tenant_type' => 'individual']);
        $leaseA = LeaseContract::create([
            'date' => now()->format('Y-m-d'), 'lease_agreement_no' => 'LA-TEST-A',
            'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name,
            'unit_id' => $unitA->id, 'unit' => 'Flat A1',
            'lease_start_date' => now()->format('Y-m-d'), 'lease_end_date' => now()->addYear()->format('Y-m-d'),
        ]);
        $leaseB = LeaseContract::create([
            'date' => now()->format('Y-m-d'), 'lease_agreement_no' => 'LA-TEST-B',
            'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name,
            'unit_id' => $unitB->id, 'unit' => 'Flat B1',
            'lease_start_date' => now()->format('Y-m-d'), 'lease_end_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $this->delete(route('buildings.destroy', $buildingA))
            ->assertRedirect(route('buildings.index'));

        $this->assertDatabaseMissing('buildings', ['id' => $buildingA->id]);
        $this->assertDatabaseMissing('property_units', ['id' => $unitA->id]);
        $this->assertDatabaseMissing('lease_contracts', ['id' => $leaseA->id]);

        // Tenant and their other building's lease must survive.
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('lease_contracts', ['id' => $leaseB->id]);
        $this->assertDatabaseHas('buildings', ['id' => $buildingB->id]);
    }
}
