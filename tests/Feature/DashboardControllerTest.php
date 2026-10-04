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
        $response->assertSee('Portfolio financial overview');
    }

    public function test_dashboard_shows_property_performance_card_for_each_building(): void
    {
        Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        Building::create(['property_name' => 'Tower B', 'property_code' => 'TB1']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tower A');
        $response->assertSee('Tower B');
        $response->assertSee('Property performance — '.now()->format('F Y'), false);
    }

    public function test_property_performance_card_links_to_the_building_show_page(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(
            '<a href="'.route('buildings.show', $building).'" class="dash-prop">',
            false
        );
    }

    public function test_dashboard_renders_zero_as_a_real_value_when_there_is_no_data(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        // DESKTOP-UI.md §7: zero is a real value — never a dash, never blank.
        $response->assertSee('BHD 0');
        $response->assertSee('No buildings yet');
    }

    public function test_dashboard_uses_the_shell_page_header_rather_than_its_own(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<h1 class="shell-pagehead-title">Dashboard</h1>', false);
        $response->assertDontSee('page-header-title');
    }
}
