<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingImage;
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

    public function test_property_card_shows_the_photo_without_an_initials_tile(): void
    {
        $building = Building::create(['property_name' => 'Miknas Plaza', 'property_code' => 'MP1']);
        BuildingImage::create(['building_id' => $building->id, 'path' => "buildings/{$building->id}/a.jpg", 'sort_order' => 0]);

        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('class="dash-prop-photo"', $html);
        $this->assertStringNotContainsString('dash-prop-tile', $html);
        // One photo has nothing to slide to.
        $this->assertDoesNotMatchRegularExpression('/<span class="dash-prop-photo"\s+data-prop-slider/', $html);
        $this->assertStringNotContainsString('<span class="dash-prop-dots', $html);
    }

    public function test_property_card_slides_through_every_photo_of_a_building_in_order(): void
    {
        $building = Building::create(['property_name' => 'Miknas Plaza', 'property_code' => 'MP1']);
        foreach (['second' => 1, 'first' => 0, 'third' => 2] as $name => $order) {
            BuildingImage::create(['building_id' => $building->id, 'path' => "buildings/{$building->id}/$name.jpg", 'sort_order' => $order]);
        }

        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<span class="dash-prop-photo"\s+data-prop-slider/', $html);
        $this->assertSame(3, substr_count($html, '<span class="dash-prop-dot '));
        $first = strpos($html, "buildings/{$building->id}/first.jpg");
        $second = strpos($html, "buildings/{$building->id}/second.jpg");
        $third = strpos($html, "buildings/{$building->id}/third.jpg");
        $this->assertTrue($first < $second && $second < $third);
    }

    public function test_property_card_without_photos_shows_the_neutral_placeholder(): void
    {
        Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('<span class="dash-prop-photo-empty"', $html);
        $this->assertDoesNotMatchRegularExpression('/<span class="dash-prop-photo"/', $html);
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
