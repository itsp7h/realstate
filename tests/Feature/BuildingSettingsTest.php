<?php

namespace Tests\Feature;

use App\Models\Building;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeBuilding(array $overrides = []): Building
    {
        return Building::create(array_merge([
            'property_name' => 'Miknas Plaza 2',
            'property_code' => 'MP2',
        ], $overrides));
    }

    public function test_building_defaults_to_vat_disabled(): void
    {
        $building = $this->makeBuilding()->refresh();

        $this->assertFalse($building->vat_enabled);
        $this->assertEquals(0, $building->vat_rate);
        $this->assertEquals(0.0, $building->effective_vat_rate);
    }

    public function test_can_enable_vat_with_a_rate(): void
    {
        $building = $this->makeBuilding();

        $this->put(route('buildings.settings.update', $building), [
            'vat_enabled' => '1',
            'vat_rate'    => '10',
        ])->assertRedirect();

        $building->refresh();
        $this->assertTrue($building->vat_enabled);
        $this->assertEquals(10.0, (float) $building->vat_rate);
        $this->assertEquals(10.0, $building->effective_vat_rate);
    }

    public function test_can_disable_vat(): void
    {
        $building = $this->makeBuilding(['vat_enabled' => true, 'vat_rate' => 10]);

        $this->put(route('buildings.settings.update', $building), [
            'vat_enabled' => '0',
            'vat_rate'    => '10',
        ])->assertRedirect();

        $building->refresh();
        $this->assertFalse($building->vat_enabled);
        // Rate is zeroed out server-side when VAT is turned off, so a stale
        // rate can't silently apply again if it's re-enabled without review.
        $this->assertEquals(0.0, (float) $building->vat_rate);
        $this->assertEquals(0.0, $building->effective_vat_rate);
    }

    public function test_vat_rate_must_be_within_0_to_100(): void
    {
        $building = $this->makeBuilding();

        $this->put(route('buildings.settings.update', $building), [
            'vat_enabled' => '1',
            'vat_rate'    => '150',
        ])->assertSessionHasErrors('vat_rate');
    }

    public function test_vat_rate_rejects_negative_values(): void
    {
        $building = $this->makeBuilding();

        $this->put(route('buildings.settings.update', $building), [
            'vat_enabled' => '1',
            'vat_rate'    => '-5',
        ])->assertSessionHasErrors('vat_rate');
    }

    public function test_settings_tab_renders_on_building_show_page(): void
    {
        $building = $this->makeBuilding();

        $this->get(route('buildings.show', $building))
            ->assertStatus(200)
            ->assertSee('Charge VAT on this building');
    }
}
