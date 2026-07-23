<?php

namespace Tests\Feature;

use App\Models\Building;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_successfully_with_no_data(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('chartData');
        $response->assertViewHas('buildingPerformance');
        $response->assertSee('Portfolio Financial Overview');
    }

    public function test_dashboard_shows_property_performance_card_for_each_building(): void
    {
        Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        Building::create(['property_name' => 'Tower B', 'property_code' => 'TB1']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tower A');
        $response->assertSee('Tower B');
        $response->assertSee('Individual Property Performance');
    }

    public function test_property_performance_card_links_to_the_building_show_page(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-href="' . route('buildings.show', $building) . '"', false);
    }
}
