<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Expense;
use App\Models\PropertyUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
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
            'category'     => 'repairs_maintenance',
            'description'  => 'Fixed the lobby AC unit',
            'amount'       => 150.500,
            'expense_date' => now()->format('Y-m-d'),
            'vendor_name'  => 'Gulf Cooling Co.',
        ], $overrides);
    }

    public function test_index_renders_successfully(): void
    {
        $this->get(route('expenses.index'))->assertOk();
    }

    public function test_create_form_renders(): void
    {
        $this->get(route('expenses.create'))->assertOk();
    }

    public function test_can_create_expense(): void
    {
        $data = $this->validData();

        $this->post(route('expenses.store'), $data)
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'building_id' => $data['building_id'],
            'category'    => 'repairs_maintenance',
            'amount'      => 150.500,
        ]);
    }

    public function test_can_create_expense_against_a_unit(): void
    {
        $building = $this->makeBuilding();
        $unit = PropertyUnit::create([
            'building_id' => $building->id, 'property_name' => 'Tower A',
            'property_code' => 'TA1', 'unit_name' => 'Flat 1',
        ]);

        $data = $this->validData(['building_id' => $building->id, 'unit_id' => $unit->id]);

        $this->post(route('expenses.store'), $data)->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', ['unit_id' => $unit->id]);
    }

    public function test_store_requires_building_category_amount_and_date(): void
    {
        $this->post(route('expenses.store'), [])
            ->assertSessionHasErrors(['building_id', 'category', 'amount', 'expense_date']);
    }

    public function test_store_rejects_invalid_category(): void
    {
        $data = $this->validData(['category' => 'not-a-real-category']);

        $this->post(route('expenses.store'), $data)->assertSessionHasErrors('category');
    }

    public function test_store_rejects_zero_amount(): void
    {
        $data = $this->validData(['amount' => 0]);

        $this->post(route('expenses.store'), $data)->assertSessionHasErrors('amount');
    }

    public function test_store_rejects_future_date(): void
    {
        $data = $this->validData(['expense_date' => now()->addDay()->format('Y-m-d')]);

        $this->post(route('expenses.store'), $data)->assertSessionHasErrors('expense_date');
    }

    public function test_edit_form_renders_with_existing_values(): void
    {
        $building = $this->makeBuilding();
        $expense  = Expense::create($this->validData(['building_id' => $building->id]));

        $this->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('Gulf Cooling Co.');
    }

    public function test_can_update_expense(): void
    {
        $building = $this->makeBuilding();
        $expense  = Expense::create($this->validData(['building_id' => $building->id]));

        $this->put(route('expenses.update', $expense), $this->validData([
            'building_id' => $building->id,
            'amount'      => 200.000,
        ]))->assertRedirect(route('expenses.index'));

        $this->assertEquals(200.000, $expense->fresh()->amount);
    }

    public function test_can_delete_expense(): void
    {
        $building = $this->makeBuilding();
        $expense  = Expense::create($this->validData(['building_id' => $building->id]));

        $this->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_index_filters_by_building(): void
    {
        $buildingA = $this->makeBuilding(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $buildingB = $this->makeBuilding(['property_name' => 'Tower B', 'property_code' => 'TB1']);

        Expense::create($this->validData(['building_id' => $buildingA->id, 'vendor_name' => 'Vendor A']));
        Expense::create($this->validData(['building_id' => $buildingB->id, 'vendor_name' => 'Vendor B']));

        $this->get(route('expenses.index', ['building_id' => $buildingA->id]))
            ->assertSee('Vendor A')
            ->assertDontSee('Vendor B');
    }

    public function test_index_filters_by_category(): void
    {
        $building = $this->makeBuilding();

        Expense::create($this->validData(['building_id' => $building->id, 'category' => 'insurance', 'vendor_name' => 'Insurer Co']));
        Expense::create($this->validData(['building_id' => $building->id, 'category' => 'cleaning', 'vendor_name' => 'Cleaning Co']));

        $this->get(route('expenses.index', ['category' => 'insurance']))
            ->assertSee('Insurer Co')
            ->assertDontSee('Cleaning Co');
    }
}
