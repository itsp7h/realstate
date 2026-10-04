<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Renders any list export as a PDF from the class that already produces its
 * XLSX.
 *
 * The point is that there is exactly one definition of a list's columns. Every
 * export here implements headings() + query() + map() for Laravel-Excel, so the
 * document reads those instead of restating them — add a column to the
 * spreadsheet and the PDF grows it too, with no second place to forget.
 *
 * The one thing a PDF needs that a spreadsheet does not is restraint: units are
 * 21 columns and lease contracts 26, and a 26-column table on A4 is a table
 * nobody reads. An export may declare pdfColumns() naming the headings worth
 * printing; the document then says how many of how many it is showing, so the
 * trim is visible rather than silent.
 */
class ListingPdf
{
    /**
     * Columns whose values are figures, matched against the heading label.
     * Right-aligned so a column of numbers reads down its own edge.
     */
    private const NUMERIC_HINTS = [
        'amount', 'total', 'rent', 'rate', 'area', 'deposit', 'units', 'floors',
        'blocks', 'no.', 'nos.', 'balance', 'paid', 'due', 'vat', 'electricity',
        'water', 'arrears', 'cap', 'fee', 'tax',
    ];

    /**
     * @param  FromQuery&WithHeadings&WithMapping  $export
     * @param  array<string, mixed>  $filters  shown on the document, as applied
     */
    public static function fromExport(
        object $export,
        string $title,
        string $noun,
        string $filename,
        array $filters = [],
    ): Response {
        $headings = $export->headings();
        $records  = $export->query()->get();

        // A document reads in human order: "Floor 2" after "Floor 1", not after
        // "Floor 10". SQL cannot express that on SQLite, so the export declares
        // which of its columns are names and the ordering happens here. Only
        // exports that opt in are re-ordered — the lease-contract list is
        // deliberately chronological and must stay that way.
        if (method_exists($export, 'pdfNaturalOrder')) {
            $records = NaturalOrder::sort($records, ...$export->pdfNaturalOrder());
        }

        $rows = $records->map(fn ($record) => array_values($export->map($record)));

        return self::render(
            $title,
            $noun,
            $filename,
            $headings,
            $rows,
            $filters,
            method_exists($export, 'pdfColumns') ? $export->pdfColumns() : null,
            // Totals come from the records the document actually printed, so the
            // row under the table can never disagree with the rows above it.
            method_exists($export, 'pdfTotals') ? $export->pdfTotals($records) : [],
        );
    }

    /**
     * The same document from headings and pre-mapped rows, for a list that has
     * no Export class of its own (the EWA batch results are built inline from a
     * cached import batch, not a query).
     *
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>|null  $keep  heading labels to print
     */
    public static function render(
        string $title,
        string $noun,
        string $filename,
        array $headings,
        iterable $rows,
        array $filters = [],
        ?array $keep = null,
        array $totals = [],
    ): Response {
        $rows = $rows instanceof Collection ? $rows : collect($rows);

        // pdfColumns() may be a flat list of headings to keep, or a map of
        // heading => ['label' => …, 'align' => …, 'width' => …]. Both forms
        // are normalised here so callers can choose how much they need to say.
        $spec = [];
        $order = null;

        if ($keep !== null) {
            foreach ($keep as $headingOrIndex => $value) {
                if (is_int($headingOrIndex)) {
                    $spec[$value] = [];
                    $order[] = $value;
                } else {
                    $spec[$headingOrIndex] = is_array($value) ? $value : ['label' => $value];
                    $order[] = $headingOrIndex;
                }
            }
        }

        $indexes = $order === null
            ? array_keys($headings)
            : array_values(array_filter(
                array_keys($headings),
                fn ($i) => in_array($headings[$i], $order, true),
            ));

        $columns = array_map(function ($i) use ($headings, $spec) {
            $column = $spec[$headings[$i]] ?? [];

            return [
                // A short label by contract: the spreadsheet's "Type of
                // Ownership" is "OWNERSHIP" on paper, because a header that
                // wraps desynchronises every column's baseline.
                'label' => $column['label'] ?? $headings[$i],
                // Declared, not guessed. The heuristic that inferred alignment
                // from the label right-aligned "Area" — which here is a place
                // name, not a figure — over left-aligned cells.
                'align' => $column['align'] ?? (self::isNumeric($headings[$i]) ? 'right' : 'left'),
                'width' => $column['width'] ?? null,
            ];
        }, $indexes);

        // Reindex each row to the kept columns so the view can address them
        // positionally without knowing anything about the trim — and cap each
        // value. table-layout:fixed keeps a long value inside its column, but a
        // 300-character description would still wrap to 20 lines and shove the
        // row across a page break. DomPDF has no text-overflow, so the cut has
        // to happen here.
        // Cap per column, from its declared share of the width rather than an
        // average: a 30% NAME column holds three times what a 10% CODE does,
        // and one flat cap either truncated names early or let codes overflow.
        $caps = array_map(
            fn ($column) => self::cellCap($column['width'], count($indexes)),
            $columns,
        );

        $trimmedRows = $rows->map(fn ($row) => array_values(array_map(
            fn ($i, $position) => self::cell($row[$i] ?? null, $caps[$position]),
            $indexes,
            array_keys($indexes),
        )));

        $pdf = Pdf::loadView('exports.listing-pdf', [
            'title'        => $title,
            'noun'         => $noun,
            'count'        => $rows->count(),
            'columns'      => $columns,
            'rows'         => $trimmedRows,
            'applied'      => array_filter($filters, fn ($v) => $v !== null && $v !== ''),
            'trimmed'      => count($indexes) < count($headings),
            'totalColumns' => count($headings),
            'totals'       => $totals,
        ])->setPaper('a4', 'portrait');

        return PdfPageNumbers::stamp($pdf)->stream($filename . '.pdf');
    }

    /**
     * How many characters a cell may hold.
     *
     * A4 portrait at 0.45in margins gives 191mm of content, and 8.5pt Figtree
     * averages about 1.95mm a character — so a column's share of the width is
     * its share of roughly 98 characters, less a character of headroom so the
     * cut lands as an ellipsis rather than as DomPDF clipping the glyph against
     * the next cell. Falls back to an even split when no width is declared.
     */
    private static function cellCap(?int $widthPercent, int $columns): int
    {
        $share = $widthPercent !== null
            ? $widthPercent / 100
            : 1 / max($columns, 1);

        return max(6, (int) floor(98 * $share) - 1);
    }

    private static function cell(mixed $value, int $cap): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return Str::limit(trim((string) $value), $cap, '…');
    }

    private static function isNumeric(string $heading): bool
    {
        $heading = strtolower($heading);

        foreach (self::NUMERIC_HINTS as $hint) {
            if (str_contains($heading, $hint)) {
                return true;
            }
        }

        return false;
    }
}
