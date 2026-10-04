<?php

namespace Tests\Feature;

use App\Exports\BuildingsExport;
use App\Exports\LeaseContractsExport;
use App\Exports\UnitsExport;
use App\Models\Building;
use App\Models\Floor;
use App\Models\LeaseContract;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ListingPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every list export serves a PDF as well as an XLSX.
 *
 * The document is fed by the same Export class the spreadsheet comes from, so
 * the two cannot drift on columns; the only thing declared separately is which
 * columns are worth printing, because 26 of them on A4 is a table nobody reads.
 */
class ListingPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function seedPortfolio(): Building
    {
        $building = Building::create([
            'property_name' => 'Miknas Plaza',
            'property_code' => 'MP1',
            'property_type' => 'Residential',
        ]);

        Floor::create([
            'building_id' => $building->id,
            'floor_name'  => 'Ground',
            'floor_code'  => 'MP1-G',
        ]);

        PropertyUnit::create([
            'building_id'    => $building->id,
            'property_name'  => 'Miknas Plaza',
            'property_code'  => 'MP1',
            'unit_name'      => 'Flat 101',
            'unit_type'      => 'Apartment',
            'rent_per_month' => 450,
        ]);

        Tenant::create([
            'name'        => 'Ahmed Ali',
            'tenant_type' => 'individual',
            'email'       => 'ahmed@example.com',
        ]);

        LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => 'LA/2026/001',
            'tenant_name'        => 'Ahmed Ali',
            'property_code'      => 'MP1',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => '2027-01-01',
        ]);

        return $building;
    }

    public static function exportRoutes(): array
    {
        return [
            'buildings' => ['export.buildings'],
            'floors'    => ['export.floors'],
            'units'     => ['export.units'],
            'tenants'   => ['export.tenants'],
            'contracts' => ['export.contracts'],
        ];
    }

    #[DataProvider('exportRoutes')]
    public function test_every_list_export_serves_a_pdf(string $route): void
    {
        $this->seedPortfolio();

        $response = $this->actingAs($this->admin())->get(route($route, 'pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    #[DataProvider('exportRoutes')]
    public function test_every_list_export_still_defaults_to_xlsx(string $route): void
    {
        $this->seedPortfolio();

        $response = $this->actingAs($this->admin())->get(route($route));

        $response->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }

    /** whereIn keeps a wrong format a 404, not a silent fall-back to xlsx. */
    public function test_an_unknown_format_is_not_a_route(): void
    {
        $this->seedPortfolio();
        $admin = $this->admin();

        foreach (['buildings', 'floors', 'units', 'tenants', 'contracts'] as $list) {
            $this->actingAs($admin)->get("/export/{$list}/docx")->assertNotFound();
        }

        $this->actingAs($admin)->get('/property-units/export/docx')->assertNotFound();
    }

    public function test_the_units_alias_forwards_the_format(): void
    {
        $this->seedPortfolio();

        $this->actingAs($this->admin())
            ->get(route('property-units.export', 'pdf'))
            ->assertRedirect(route('export.units', ['format' => 'pdf']));
    }

    /** Columns come from the Export class, so the two formats cannot drift. */
    public function test_the_document_takes_its_columns_from_the_export(): void
    {
        $this->seedPortfolio();

        $export = new BuildingsExport([]);
        $pdf = ListingPdf::fromExport($export, 'Buildings', 'property', 'buildings-test');

        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        // pdfColumns() is keyed by heading now, with a short label, an
        // alignment and a width for each — see BuildingsExport.
        $this->assertLessThan(count($export->headings()), count($export->pdfColumns()));
        foreach ($export->pdfColumns() as $heading => $spec) {
            $this->assertContains($heading, $export->headings(), "Unknown column: {$heading}");
            $this->assertArrayHasKey('label', $spec);
            $this->assertArrayHasKey('align', $spec);
            $this->assertArrayHasKey('width', $spec);
        }
    }

    public function test_the_wide_exports_declare_a_printable_subset(): void
    {
        $units = new UnitsExport([]);
        $contracts = new LeaseContractsExport([]);

        $this->assertLessThan(count($units->headings()), count($units->pdfColumns()));
        $this->assertLessThan(count($contracts->headings()), count($contracts->pdfColumns()));

        // Every key must exist in headings(), or the trim silently drops a
        // column that was only ever a typo. Widths must total ~100%, or
        // table-layout: fixed distributes the remainder unpredictably.
        foreach ([$units, $contracts] as $export) {
            foreach ($export->pdfColumns() as $heading => $spec) {
                $this->assertContains($heading, $export->headings(), "Unknown column: {$heading}");
                // A label that wraps is the defect this contract exists to stop.
                $this->assertStringNotContainsString(' ', trim($spec['label']), "Label wraps: {$spec['label']}");
            }

            $width = array_sum(array_column($export->pdfColumns(), 'width'));
            $this->assertGreaterThanOrEqual(95, $width);
            $this->assertLessThanOrEqual(101, $width);
        }
    }

    public function test_the_document_states_the_filters_that_produced_it(): void
    {
        $html = view('exports.listing-pdf', [
            'title'   => 'Buildings', 'noun' => 'property', 'count' => 1,
            'columns' => [['label' => 'NAME', 'align' => 'left', 'width' => 100]],
            'rows'    => collect([['Miknas Plaza']]),
            'applied' => ['property_type' => 'Residential'],
            'trimmed' => false, 'totalColumns' => 1, 'totals' => [],
        ])->render();

        $this->assertStringContainsString('Filters applied', $html);
        $this->assertStringContainsString('property_type = Residential', $html);
    }

    public function test_a_trimmed_document_says_how_many_columns_it_hides(): void
    {
        $html = view('exports.listing-pdf', [
            'title'   => 'Property units', 'noun' => 'unit', 'count' => 1,
            'columns' => [['label' => 'UNIT', 'align' => 'left', 'width' => 100]],
            'rows'    => collect([['Flat 101']]),
            'applied' => [], 'trimmed' => true, 'totalColumns' => 21, 'totals' => [],
        ])->render();

        $this->assertStringContainsString('1 of 21 shown', $html);
        $this->assertStringContainsString('carries every field', $html);
    }

    public function test_an_empty_list_distinguishes_no_records_from_no_matches(): void
    {
        $base = [
            'title' => 'Tenants', 'noun' => 'tenant', 'count' => 0,
            'columns' => [['label' => 'NAME', 'align' => 'left', 'width' => 100]],
            'rows' => collect(), 'trimmed' => false, 'totalColumns' => 1, 'totals' => [],
        ];

        // An empty table is one styled line — no header row over nothing.
        $empty = view('exports.listing-pdf', $base + ['applied' => []])->render();
        $this->assertStringContainsString('No tenants recorded', $empty);
        $this->assertStringContainsString('pdf-none', $empty);
        $this->assertStringNotContainsString('<thead>', $empty);

        $this->assertStringContainsString(
            'No tenants match the filters',
            view('exports.listing-pdf', $base + ['applied' => ['search' => 'zzz']])->render()
        );
    }

    public function test_figures_are_right_aligned(): void
    {
        $this->seedPortfolio();

        $pdf = ListingPdf::render('Test', 'row', 'test', ['Unit Name', 'Rent/Month'], [['Flat 1', '450.000']]);

        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_pdf_export_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'viewer']))
            ->get(route('export.buildings', 'pdf'))
            ->assertForbidden();
    }
}
