<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\PropertyUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The PDF half of /data/export.
 *
 * The endpoint only ever produced an XLSX workbook, so the Export button was a
 * bare link. It now takes a format, and the PDF renders the same three
 * datasets as a document — units nested under the property that owns them
 * rather than a fourth flat table.
 */
class DataExportPdfTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function seedPortfolio(): Building
    {
        $building = Building::create([
            'property_name'  => 'Miknas Plaza',
            'property_code'  => 'MP1',
            'property_type'  => 'Residential',
            'land_lord'      => 'Promoseven',
            'city'           => 'Muharraq',
        ]);

        Floor::create([
            'building_id'       => $building->id,
            'floor_name'        => 'Ground',
            'floor_code'        => 'MP1-G',
            'total_no_of_units' => 4,
        ]);

        PropertyUnit::create([
            'building_id'    => $building->id,
            'property_code'  => 'MP1',
            'property_name'  => 'Miknas Plaza',
            'unit_name'      => 'Flat 101',
            'unit_type'      => 'Apartment',
            'rent_per_month' => 450,
        ]);

        return $building;
    }

    public function test_it_streams_a_pdf(): void
    {
        $this->seedPortfolio();

        $response = $this->actingAs($this->admin())->get(route('data.export', 'pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('real-estate-data-', $response->headers->get('Content-Disposition'));
        // Pdf::stream() returns a plain inline Response, not a StreamedResponse.
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** The default is unchanged, so every existing route('data.export') still works. */
    public function test_the_format_defaults_to_the_workbook(): void
    {
        $this->seedPortfolio();

        $response = $this->actingAs($this->admin())->get(route('data.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }

    public function test_an_unknown_format_is_not_a_route(): void
    {
        $this->actingAs($this->admin())->get('/data/export/docx')->assertNotFound();
    }

    /** Rendering the view is where a bad column reference would surface. */
    public function test_the_document_renders_the_portfolio(): void
    {
        $building = $this->seedPortfolio();

        $html = view('data.export-pdf', [
            'buildings'   => Building::with('floors', 'units')->get(),
            'orphanUnits' => collect(),
            'totals'      => ['buildings' => 1, 'floors' => 1, 'units' => 1, 'rent' => 450.0],
        ])->render();

        // Title-cased in the markup; the layout's CSS uppercases it, which is
        // where a print text-transform belongs.
        $this->assertStringContainsString('Portfolio Data Export', $html);
        $this->assertStringContainsString($building->property_name, $html);
        $this->assertStringContainsString('MP1-G', $html);      // the floor
        $this->assertStringContainsString('Flat 101', $html);   // the unit
        $this->assertStringContainsString('450.000', $html);    // rent, 3dp
    }

    public function test_an_empty_portfolio_says_so_instead_of_rendering_blank(): void
    {
        $html = view('data.export-pdf', [
            'buildings'   => collect(),
            'orphanUnits' => collect(),
            'totals'      => ['buildings' => 0, 'floors' => 0, 'units' => 0, 'rent' => 0.0],
        ])->render();

        $this->assertStringContainsString('nothing to export yet', $html);
    }

    /** A unit with no building must not vanish from a document claiming everything. */
    public function test_units_without_a_property_get_their_own_section(): void
    {
        $orphan = PropertyUnit::create([
            'property_code' => 'GHOST',
            'property_name' => 'Ghost Tower',
            'unit_name'     => 'Flat X',
        ]);

        $html = view('data.export-pdf', [
            'buildings'   => collect(),
            'orphanUnits' => collect([$orphan]),
            'totals'      => ['buildings' => 0, 'floors' => 0, 'units' => 1, 'rent' => 0.0],
        ])->render();

        $this->assertStringContainsString('Units without a property', $html);
        $this->assertStringContainsString('GHOST', $html);
        $this->assertStringContainsString('Flat X', $html);
    }

    public function test_export_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'viewer']))
            ->get(route('data.export', 'pdf'))
            ->assertForbidden();
    }
}
