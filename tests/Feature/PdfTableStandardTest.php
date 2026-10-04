<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The table standard, held across every PDF export.
 *
 * These are the defects the Buildings export was reported with, turned into
 * guards: headers that wrapped to two lines, values that broke mid-word or were
 * clipped against the next cell with no ellipsis, totals that read "—" over a
 * portfolio with 18 floors and 65 units, and a header alignment that disagreed
 * with its column.
 */
class PdfTableStandardTest extends TestCase
{
    use RefreshDatabase;

    /** Every table-bearing PDF view. */
    private const TABLE_VIEWS = [
        'exports/listing-pdf',
        'data/export-pdf',
        'reports/bill-wise-statement-pdf',
        'reports/collection-pdf',
        'reports/financial-summary-pdf',
        'reports/group-ageing-pdf',
        'reports/profit-loss-pdf',
        'reports/rent-schedule-pdf',
        'reports/tenant-ageing-pdf',
        'reports/tenant-ledger-pdf',
        'reports/tenant-statement-pdf',
        'reports/vat-return-pdf',
    ];

    private function source(string $view): string
    {
        return file_get_contents(resource_path("views/{$view}.blade.php"));
    }

    /** A header never wraps, and a value never breaks mid-word. */
    public function test_the_stylesheet_forbids_wrapping(): void
    {
        $css = file_get_contents(public_path('css/pdf.css'));

        $this->assertMatchesRegularExpression('/\.pdf-table th \{[^}]*white-space: nowrap/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-table th \{[^}]*word-break: keep-all/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-table td \{[^}]*white-space: nowrap/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-table \{[^}]*table-layout: fixed/', $css);
    }

    /**
     * Widths on the th, not only on a <col>: DomPDF ignores <col> widths, the
     * columns collapse to their content, and long names get clipped mid-glyph.
     */
    public function test_every_table_declares_its_column_widths(): void
    {
        foreach (self::TABLE_VIEWS as $view) {
            $source = $this->source($view);

            $this->assertTrue(
                str_contains($source, 'style="width:') || str_contains($source, "column['width']"),
                "{$view} declares no column widths, so DomPDF sizes columns by content."
            );
        }
    }

    /**
     * No unbounded string cell. DomPDF has no text-overflow, so a value longer
     * than its column is clipped against the next cell with nothing to show a
     * cut was made — the truncation has to happen in PHP.
     */
    public function test_no_table_cell_prints_an_unbounded_string(): void
    {
        $formatted = ['->format(', 'number_format', 'crDr', '$fmt(', 'count(', '::'];

        foreach (self::TABLE_VIEWS as $view) {
            $source = $this->source($view);
            preg_match_all('/<td[^>]*>\s*\{\{\s*\$[^}]*\}\}\s*<\/td>/', $source, $matches);

            $unbounded = array_filter(
                $matches[0],
                fn ($cell) => ! array_filter($formatted, fn ($f) => str_contains($cell, $f))
            );

            $this->assertSame([], array_values($unbounded),
                "{$view} prints a raw string cell — use <x-pdf.text :limit=\"…\"> so it truncates.");
        }
    }

    /** Buildings' counts come from the data, not from a nullable column. */
    public function test_the_buildings_export_totals_real_counts(): void
    {
        $export = new \App\Exports\BuildingsExport([]);

        $this->assertStringContainsString('withCount', (new \ReflectionMethod($export, 'query'))
            ->getDeclaringClass()->getFileName()
            ? file_get_contents((new \ReflectionClass($export))->getFileName())
            : '');

        $this->assertArrayHasKey('FLOORS', $export->pdfTotals(collect()));
        $this->assertArrayHasKey('UNITS', $export->pdfTotals(collect()));
    }

    /** Header alignment is declared per column, never inferred from its label. */
    public function test_alignment_is_declared_not_guessed(): void
    {
        // FloorsExport takes a nullable building id, not a filter array.
        foreach ([
            new \App\Exports\BuildingsExport([]),
            new \App\Exports\FloorsExport(),
            new \App\Exports\UnitsExport([]),
            new \App\Exports\TenantsExport([]),
            new \App\Exports\LeaseContractsExport([]),
        ] as $export) {
            $class = $export::class;

            foreach ($export->pdfColumns() as $heading => $spec) {
                $this->assertArrayHasKey('align', $spec, "{$class}: {$heading} declares no alignment.");
                $this->assertContains($spec['align'], ['left', 'right', 'center']);
                // Short enough not to need wrapping. A space is fine — "FLOOR #"
                // and "ID / CR" read better than a jammed-together word, and
                // pdf.css forbids wrapping outright. Length is the real
                // invariant: a long label is what forces a two-line head.
                $this->assertLessThanOrEqual(12, mb_strlen(trim($spec['label'])),
                    "{$class}: label \"{$spec['label']}\" is too long for one line.");
            }
        }
    }

    /** "Area" is a place name here — the old heuristic right-aligned it. */
    public function test_the_area_column_is_left_aligned(): void
    {
        $columns = (new \App\Exports\BuildingsExport([]))->pdfColumns();

        $this->assertSame('left', $columns['Area']['align']);
        $this->assertSame('right', $columns['Total Floors']['align']);
        $this->assertSame('right', $columns['Total Units']['align']);
    }
}
