<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\Floor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeBuilding(array $overrides = []): Building
    {
        return Building::create(array_merge([
            'property_name' => 'Miknas Plaza 2',
            'property_code' => 'MP2',
        ], $overrides));
    }

    public function test_can_create_a_block(): void
    {
        $building = $this->makeBuilding();

        $this->post(route('buildings.blocks.store', $building), [
            'block_name' => 'Block A',
            'block_code' => 'BLK-A',
            'total_no_of_floors' => 10,
        ])->assertRedirect(route('buildings.show', $building) . '?tab=blocks');

        $this->assertDatabaseHas('blocks', [
            'building_id' => $building->id,
            'block_name'  => 'Block A',
            'block_code'  => 'BLK-A',
            'total_no_of_floors' => 10,
        ]);
    }

    public function test_block_name_is_required(): void
    {
        $building = $this->makeBuilding();

        $this->post(route('buildings.blocks.store', $building), [
            'block_code' => 'BLK-A',
        ])->assertSessionHasErrors('block_name');

        $this->assertDatabaseCount('blocks', 0);
    }

    public function test_block_name_must_be_unique_within_the_same_building(): void
    {
        $building = $this->makeBuilding();
        Block::create(['building_id' => $building->id, 'block_name' => 'Block A']);

        $this->post(route('buildings.blocks.store', $building), [
            'block_name' => 'Block A',
        ]);

        $this->assertDatabaseCount('blocks', 1);
    }

    public function test_same_block_name_is_allowed_across_different_buildings(): void
    {
        $buildingOne = $this->makeBuilding();
        $buildingTwo = $this->makeBuilding(['property_name' => 'Miknas Plaza 3', 'property_code' => 'MP3']);

        Block::create(['building_id' => $buildingOne->id, 'block_name' => 'Block A']);

        $this->post(route('buildings.blocks.store', $buildingTwo), [
            'block_name' => 'Block A',
        ])->assertRedirect(route('buildings.show', $buildingTwo) . '?tab=blocks');

        $this->assertDatabaseCount('blocks', 2);
    }

    public function test_can_update_a_block(): void
    {
        $building = $this->makeBuilding();
        $block = Block::create(['building_id' => $building->id, 'block_name' => 'Block A']);

        $this->put(route('blocks.update', $block), [
            'block_name' => 'Block A Renamed',
            'total_no_of_floors' => 5,
        ])->assertRedirect(route('buildings.show', $building) . '?tab=blocks');

        $block->refresh();
        $this->assertEquals('Block A Renamed', $block->block_name);
        $this->assertEquals(5, $block->total_no_of_floors);
    }

    public function test_can_delete_a_block_with_no_floors(): void
    {
        $building = $this->makeBuilding();
        $block = Block::create(['building_id' => $building->id, 'block_name' => 'Block A']);

        $this->delete(route('blocks.destroy', $block))
            ->assertRedirect(route('buildings.show', $building) . '?tab=blocks');

        $this->assertDatabaseMissing('blocks', ['id' => $block->id]);
    }

    public function test_cannot_delete_a_block_with_floors_linked(): void
    {
        $building = $this->makeBuilding();
        $block = Block::create(['building_id' => $building->id, 'block_name' => 'Block A']);
        Floor::create([
            'building_id' => $building->id,
            'block_id'    => $block->id,
            'floor_name'  => 'Ground Floor',
        ]);

        $this->delete(route('blocks.destroy', $block))
            ->assertSessionHas('error', 'Cannot delete block — it still has floors linked to it.');

        $this->assertDatabaseHas('blocks', ['id' => $block->id]);
    }

    public function test_a_floor_can_reference_a_block_in_the_same_building(): void
    {
        $building = $this->makeBuilding();
        $block = Block::create(['building_id' => $building->id, 'block_name' => 'Block A']);

        $this->post(route('buildings.floors.store', $building), [
            'floor_name' => 'Ground Floor',
            'block_id'   => $block->id,
        ])->assertRedirect(route('buildings.show', $building) . '?tab=floors');

        $this->assertDatabaseHas('floors', [
            'building_id' => $building->id,
            'block_id'    => $block->id,
            'floor_name'  => 'Ground Floor',
        ]);
    }

    public function test_a_floor_can_be_created_without_a_block(): void
    {
        $building = $this->makeBuilding();

        $this->post(route('buildings.floors.store', $building), [
            'floor_name' => 'Ground Floor',
        ])->assertRedirect(route('buildings.show', $building) . '?tab=floors');

        $this->assertDatabaseHas('floors', [
            'building_id' => $building->id,
            'block_id'    => null,
            'floor_name'  => 'Ground Floor',
        ]);
    }
}
