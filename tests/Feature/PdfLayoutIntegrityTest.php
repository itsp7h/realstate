<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Support\PdfPageNumbers;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Layout integrity for the exported documents.
 *
 * Every rule here was a defect on paper first: a total row alone at the top of
 * page 3, a "UNITS — 1" heading stranded at the foot of page 4 with its table
 * overleaf, floors ordered "1, 10, 2", a units column of dashes over a floor
 * holding 25 units, and "0.000" for a deposit nobody ever entered.
 *
 * The pagination rules themselves are enforced in public/css/pdf.css, which
 * every one of these documents inlines, so they are asserted once against the
 * stylesheet rather than fifteen times against markup.
 */
class PdfLayoutIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** Every template that renders through the shared PDF layout. */
    private function pdfTemplates(): array
    {
        return collect(glob(resource_path('views/**/*.blade.php')))
            ->merge(glob(resource_path('views/*/*-pdf.blade.php')))
            ->unique()
            ->filter(fn ($path) => str_contains(file_get_contents($path), "@extends('layouts.pdf')"))
            ->values()
            ->all();
    }

    private function css(): string
    {
        return file_get_contents(public_path('css/pdf.css'));
    }

    private function seedPortfolio(): Building
    {
        $building = Building::create([
            'property_name' => 'Miknas Plaza 2',
            'property_code' => 'MP2',
            'property_type' => 'Residential',
        ]);

        // Deliberately inserted out of order, and spanning one to two digits:
        // this is the exact shape that produced "Floor 1, Floor 10, Floor 2".
        foreach (['Floor 10', 'Floor 2', 'Floor 1'] as $name) {
            $floor = Floor::create([
                'building_id' => $building->id,
                'floor_name'  => $name,
                'floor_code'  => str_replace('Floor ', 'FL', $name),
            ]);

            // Two units per floor, so the per-floor count has something to count.
            foreach (['201', '30'] as $unit) {
                PropertyUnit::create([
                    'building_id'             => $building->id,
                    'floor_id'                => $floor->id,
                    'property_name'           => 'Miknas Plaza 2',
                    'property_code'           => 'MP2',
                    'unit_name'               => 'MP2 - ' . $unit,
                    // A blank money cell in the import lands in the column as 0.
                    'security_deposit_amount' => 0,
                    'rent_per_month'          => null,
                ]);
            }
        }

        return $building;
    }

    /** The document's HTML, which is what the layout rules are visible in. */
    private function renderPortfolioView(): string
    {
        $buildings = Building::with(['floors' => fn ($q) => $q->withCount('units'), 'units'])->get();

        \App\Support\NaturalOrder::relations($buildings, [
            'floors' => ['floor_name', 'floor_code'],
            'units'  => ['unit_name'],
        ]);

        return view('data.export-pdf', [
            'buildings'   => $buildings,
            'orphanUnits' => collect(),
            'totals'      => ['buildings' => $buildings->count(), 'floors' => Floor::count(), 'units' => PropertyUnit::count(), 'rent' => null],
            'unlisted'    => ['floors' => 0],
        ])->render();
    }

    // ── The pagination contract ───────────────────────────────────────────────

    /** A row that splits across a page break loses half its figures. */
    public function test_no_table_row_may_split_across_a_page(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.pdf-table tr \{[^}]*page-break-inside:\s*avoid/',
            $this->css()
        );
    }

    /** A continuation page must still say what its columns are. */
    public function test_table_heads_repeat_on_every_page(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.pdf-table thead \{[^}]*display:\s*table-header-group/',
            $this->css()
        );
    }

    /**
     * The mechanism matters, not just the intent: DomPDF drops
     * page-break-inside on a tbody and does not bind a tfoot to the last row,
     * so the binding has to be page-break-before on the total row itself.
     */
    public function test_every_kind_of_total_row_is_bound_to_the_row_above_it(): void
    {
        $css = $this->css();

        $rules = preg_replace('#/\*.*?\*/#s', '', $css);

        $selector = null;
        foreach (explode('}', $rules) as $rule) {
            if (str_contains($rule, 'page-break-before: avoid')) {
                $selector = $rule;
                break;
            }
        }

        $this->assertNotNull($selector, 'Nothing in pdf.css binds a row to the one above it.');

        foreach (['tfoot tr', 'tr.pdf-total', 'tr.subtotal-row', 'tr.net-row', 'tr.closing-row',
                  'tr.inv-total-row', 'tr.ewa-total-row'] as $kind) {
            $this->assertStringContainsString($kind, $selector, "{$kind} can still open a page alone.");
        }
    }

    public function test_a_short_subsection_travels_as_one_block(): void
    {
        $this->assertMatchesRegularExpression('/\.pdf-keep \{[^}]*page-break-inside:\s*avoid/', $this->css());
    }

    /** Totals belong in tfoot; the two paper facsimiles are the stated exception. */
    public function test_no_template_leaves_a_total_row_loose_in_the_table_body(): void
    {
        foreach ($this->pdfTemplates() as $path) {
            $source = file_get_contents($path);
            $name   = basename($path);

            if (! preg_match('/class="pdf-total"/', $source)) {
                continue;
            }

            $body = preg_replace('/<tfoot>.*?<\/tfoot>/s', '', $source);

            $this->assertStringNotContainsString(
                'class="pdf-total"',
                $body,
                "{$name} keeps a total row outside <tfoot>."
            );
        }
    }

    // ── Empty states ─────────────────────────────────────────────────────────

    /**
     * Column headers over zero rows read as data that failed to load, and a
     * total under zero rows states a figure nothing supports.
     */
    public function test_no_template_prints_an_empty_state_as_a_table_row(): void
    {
        foreach ($this->pdfTemplates() as $path) {
            $source = file_get_contents($path);

            // A <td class="pdf-blank"> is the old pattern: a message in a cell
            // under column headers, with a total row often below it. A
            // standalone .pdf-blank block — "nothing has been added yet" for a
            // whole document — is not a table and is fine.
            $this->assertDoesNotMatchRegularExpression(
                '/<td[^>]*class="[^"]*pdf-blank[^"-][^"]*"/',
                $source,
                basename($path) . ' still renders its empty state as a row inside the table.'
            );
            // A @forelse over a whole document is fine — the portfolio export
            // ends with one, and its @empty branch is a block. What must not
            // come back is an @empty branch that emits a table row.
            foreach (preg_split('/@empty/', $source, -1) as $i => $branch) {
                if ($i === 0) {
                    continue;
                }

                $untilEnd = preg_split('/@endforelse/', $branch)[0];

                $this->assertDoesNotMatchRegularExpression(
                    '/<t[dr][\s>]/',
                    $untilEnd,
                    basename($path) . ' still renders its empty state as a table row in an @empty branch.'
                );
            }
        }
    }

    public function test_an_empty_subsection_prints_one_line_and_no_total(): void
    {
        $building = Building::create(['property_name' => 'PL Unit Filter Tower', 'property_code' => 'PLUF1']);

        $html = $this->renderPortfolioView();

        $this->assertStringContainsString('pdf-blank-line', $html);
        $this->assertStringContainsString('No floors recorded for this property.', $html);
        $this->assertStringNotContainsString('Rent per month', $html, 'An empty units table still printed a total.');
        $this->assertStringNotContainsString('<thead>', $html, 'An empty subsection still printed column headers.');
        $this->assertSame('PLUF1', $building->property_code);
    }

    // ── Data consistency ─────────────────────────────────────────────────────

    public function test_floors_are_listed_in_human_order(): void
    {
        $this->seedPortfolio();

        $html = $this->renderPortfolioView();

        $positions = array_map(fn ($n) => strpos($html, $n), ['Floor 1', 'Floor 2', 'Floor 10']);

        $this->assertSame($positions, array_values(array_filter($positions, 'is_int')));
        $this->assertTrue(
            $positions[0] < $positions[1] && $positions[1] < $positions[2],
            'Floors are still ordered lexicographically: 1, 10, 2.'
        );
    }

    /** The stored floors.total_no_of_units is blank on imported floors. */
    public function test_each_floor_prints_its_live_unit_count(): void
    {
        $this->seedPortfolio();

        $this->assertNull(Floor::first()->total_no_of_units, 'Fixture assumption: the column is blank.');

        $html = $this->renderPortfolioView();

        // Two units per floor, and the count must be a figure, not a dash.
        $countCells = preg_match_all('/<td class="right">2<\/td>/', $html);
        $this->assertSame(3, $countCells, 'The per-floor unit count is missing or still a dash.');
    }

    public function test_a_deposit_nobody_entered_prints_a_dash_not_a_zero(): void
    {
        $this->seedPortfolio();

        $html = $this->renderPortfolioView();

        $this->assertStringNotContainsString('>0.000<', $html, 'A blank money cell still prints 0.000.');
        $this->assertStringContainsString('pdf-empty', $html);
    }

    /** A column of dashes must never be totalled into a figure. */
    public function test_a_total_over_no_real_values_prints_a_dash(): void
    {
        $this->seedPortfolio();

        $html = $this->renderPortfolioView();

        $tfoot = preg_match('/<tfoot>(.*?)<\/tfoot>/s', $html, $m) ? $m[1] : '';

        $this->assertStringContainsString('pdf-empty', $tfoot, 'The rent total invented a figure.');
    }

    // ── The figure component ─────────────────────────────────────────────────

    /**
     * Blade needs a non-word character before an @, so `@endif@else` compiled
     * to a literal "@else" and the component printed markup into the document.
     */
    public function test_the_figure_component_never_leaks_a_blade_directive(): void
    {
        foreach (['null', '0', '1234.5', "''"] as $value) {
            foreach (['', ' money'] as $money) {
                $out = Blade::render("<x-pdf.figure :value=\"{$value}\"{$money} />");
                $this->assertStringNotContainsString('@', $out, "Directive leaked for value {$value}.");
            }
        }
    }

    public function test_the_figure_component_states_absence_and_presence(): void
    {
        $dash = 'pdf-empty';

        $this->assertStringContainsString($dash, Blade::render('<x-pdf.figure :value="null" />'));
        $this->assertStringContainsString($dash, Blade::render('<x-pdf.figure :value="0" money />'));
        $this->assertStringContainsString('0.000', Blade::render('<x-pdf.figure :value="0" />'));
        $this->assertStringContainsString('1,234.500', Blade::render('<x-pdf.figure :value="1234.5" money />'));
        $this->assertStringContainsString('12.00', Blade::render('<x-pdf.figure :value="12" :dp="2" />'));
        $this->assertStringContainsString('12.000 BHD', Blade::render('<x-pdf.figure :value="12" suffix="BHD" />'));
    }

    public function test_the_text_component_greys_the_dash_it_prints(): void
    {
        $this->assertStringContainsString('pdf-empty', Blade::render('<x-pdf.text :value="null" />'));
        $this->assertStringContainsString('pdf-empty', Blade::render('<x-pdf.text :value="\'  \'" />'));
        $this->assertStringContainsString('Ground', Blade::render('<x-pdf.text :value="\'Ground\'" />'));
    }

    // ── Page numbering ───────────────────────────────────────────────────────

    /**
     * The page script is applied immediately in DomPDF 3, over the pages that
     * exist when it is called — so stamping before the render numbered page 1
     * as "PAGE 1 / 1" and left every later page bare. If stamp() renders first,
     * the canvas it stamps knows how many pages there are.
     */
    public function test_the_page_stamp_is_applied_after_the_document_is_laid_out(): void
    {
        $long = '<html><body>' . str_repeat('<p>A paragraph that fills the page.</p>', 400) . '</body></html>';

        $pdf = Pdf::loadHTML($long)->setPaper('a4', 'portrait');
        PdfPageNumbers::stamp($pdf);

        $this->assertGreaterThan(
            1,
            $pdf->getDomPDF()->getCanvas()->get_page_count(),
            'stamp() saw a single-page canvas, so it ran before the layout.'
        );
    }

    // ── Crash regressions ────────────────────────────────────────────────────

    /** The subtitle dereferenced row one of an empty collection. */
    public function test_the_rent_schedule_renders_for_a_tenant_with_no_schedule(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Ali', 'tenant_type' => 'individual']);

        $html = view('reports.rent-schedule-pdf', ['tenant' => $tenant, 'rows' => collect()])->render();

        $this->assertStringContainsString('No rent-bearing lease contracts on file.', $html);
        $this->assertStringContainsString('pdf-blank-line', $html);
    }

    /** Leftovers from the KPI-strip migration printed two bare figures. */
    public function test_the_profit_and_loss_statement_has_no_orphaned_markup(): void
    {
        $source = file_get_contents(resource_path('views/reports/profit-loss-pdf.blade.php'));

        $this->assertStringNotContainsString('class="stat-value', $source);
        $this->assertSame(
            substr_count($source, '<div'),
            substr_count($source, '</div>'),
            'The statement has unbalanced divs again.'
        );
    }
}
