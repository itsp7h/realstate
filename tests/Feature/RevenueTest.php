<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\PropertyUnit;
use App\Models\Revenue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueTest extends TestCase
{
    use RefreshDatabase;

    private function makeBuilding(array $overrides = []): Building
    {
        return Building::create(array_merge([
            'property_name' => 'Tower A',
            'property_code' => 'TA1',
        ], $overrides));
    }

    private function validData(array $overrides = []): array
    {
        $buildingId = $overrides['building_id'] ?? $this->makeBuilding()->id;

        return array_merge([
            'building_id'  => $buildingId,
            'category'     => 'parking_fee',
            'description'  => 'Extra parking spot rented to a visitor',
            'amount'       => 25.000,
            'revenue_date' => now()->format('Y-m-d'),
            'source_name'  => 'Front Desk',
        ], $overrides);
    }

    public function test_index_renders_successfully(): void
    {
        $this->get(route('revenues.index'))->assertOk();
    }

    public function test_create_form_renders(): void
    {
        $this->get(route('revenues.create'))->assertOk();
    }

    public function test_can_create_revenue(): void
    {
        $data = $this->validData();

        $this->post(route('revenues.store'), $data)
            ->assertRedirect(route('revenues.index'));

        $this->assertDatabaseHas('revenues', [
            'building_id' => $data['building_id'],
            'category'    => 'parking_fee',
            'amount'      => 25.000,
        ]);
    }

    public function test_can_create_revenue_against_a_unit(): void
    {
        $building = $this->makeBuilding();
        $unit = PropertyUnit::create([
            'building_id' => $building->id, 'property_name' => 'Tower A',
            'property_code' => 'TA1', 'unit_name' => 'Flat 1',
        ]);

        $data = $this->validData(['building_id' => $building->id, 'unit_id' => $unit->id]);

        $this->post(route('revenues.store'), $data)->assertRedirect(route('revenues.index'));

        $this->assertDatabaseHas('revenues', ['unit_id' => $unit->id]);
    }

    public function test_store_requires_building_category_amount_and_date(): void
    {
        $this->post(route('revenues.store'), [])
            ->assertSessionHasErrors(['building_id', 'category', 'amount', 'revenue_date']);
    }

    public function test_store_rejects_invalid_category(): void
    {
        $data = $this->validData(['category' => 'not-a-real-category']);

        $this->post(route('revenues.store'), $data)->assertSessionHasErrors('category');
    }

    public function test_store_rejects_zero_amount(): void
    {
        $data = $this->validData(['amount' => 0]);

        $this->post(route('revenues.store'), $data)->assertSessionHasErrors('amount');
    }

    public function test_store_rejects_future_date(): void
    {
        $data = $this->validData(['revenue_date' => now()->addDay()->format('Y-m-d')]);

        $this->post(route('revenues.store'), $data)->assertSessionHasErrors('revenue_date');
    }

    public function test_edit_form_renders_with_existing_values(): void
    {
        $building = $this->makeBuilding();
        $revenue  = Revenue::create($this->validData(['building_id' => $building->id]));

        $this->get(route('revenues.edit', $revenue))
            ->assertOk()
            ->assertSee('Front Desk');
    }

    public function test_can_update_revenue(): void
    {
        $building = $this->makeBuilding();
        $revenue  = Revenue::create($this->validData(['building_id' => $building->id]));

        $this->put(route('revenues.update', $revenue), $this->validData([
            'building_id' => $building->id,
            'amount'      => 40.000,
        ]))->assertRedirect(route('revenues.index'));

        $this->assertEquals(40.000, $revenue->fresh()->amount);
    }

    public function test_can_delete_revenue(): void
    {
        $building = $this->makeBuilding();
        $revenue  = Revenue::create($this->validData(['building_id' => $building->id]));

        $this->delete(route('revenues.destroy', $revenue))
            ->assertRedirect(route('revenues.index'));

        $this->assertDatabaseMissing('revenues', ['id' => $revenue->id]);
    }

    public function test_index_filters_by_building(): void
    {
        $buildingA = $this->makeBuilding(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $buildingB = $this->makeBuilding(['property_name' => 'Tower B', 'property_code' => 'TB1']);

        Revenue::create($this->validData(['building_id' => $buildingA->id, 'source_name' => 'Source A']));
        Revenue::create($this->validData(['building_id' => $buildingB->id, 'source_name' => 'Source B']));

        $this->get(route('revenues.index', ['building_id' => $buildingA->id]))
            ->assertSee('Source A')
            ->assertDontSee('Source B');
    }

    public function test_index_filters_by_category(): void
    {
        $building = $this->makeBuilding();

        Revenue::create($this->validData(['building_id' => $building->id, 'category' => 'late_fee', 'source_name' => 'Late Fee Source']));
        Revenue::create($this->validData(['building_id' => $building->id, 'category' => 'other', 'source_name' => 'Other Source']));

        $this->get(route('revenues.index', ['category' => 'late_fee']))
            ->assertSee('Late Fee Source')
            ->assertDontSee('Other Source');
    }
}
