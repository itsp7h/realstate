<?php

namespace App\Exports;

use App\Models\Building;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BuildingsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Buildings';
    }

    public function query()
    {
        // withCount, because total_no_of_floors / total_no_of_units are
        // hand-entered columns and are null on most rows — the export printed
        // "—" for a portfolio that plainly has 18 floors and 65 units. The
        // declared figure still wins where someone entered one; the count is
        // the fallback, so the sheet can no longer be silently empty.
        return Building::filter($this->filters)
            ->withCount(['floors', 'units'])
            ->orderBy('property_code');
    }

    /**
     * Ordering for the PDF of this list — see ListingPdf::fromExport(). The
     * workbook keeps the query's order, which sorts by code.
     *
     * @return array<int, string>
     */
    public function pdfNaturalOrder(): array
    {
        return ['property_name'];
    }

    public function headings(): array
    {
        return [
            'Property Name', 'Property Code', 'Type of Ownership', 'Property Type',
            'Land Lord', 'Building No.', 'Road', 'Block', 'Area', 'City',
            'Total Blocks', 'Total Floors', 'Total Units',
        ];
    }

    /**
     * The subset worth printing. All 13 columns on A4 portrait leaves each one
     * 14mm, which broke headers mid-word ("PROPERT / Y NAME") and cut values to
     * "Combo Test Tow…". Portrait is not negotiable, so the fix is fewer
     * columns rather than smaller type or turned paper — and the document says
     * "8 of 13 shown" so the trim is visible.
     *
     * Labels, not indexes: reordering headings() must not silently reshuffle
     * which columns the PDF shows.
     */
    /**
     * What paper needs and a spreadsheet does not: a short label, an explicit
     * alignment, and a share of the width.
     *
     * All 13 columns on A4 portrait leaves each one 15mm, which broke headers
     * mid-word ("PROPERT / Y NAME") and cut values to "Combo Test Tow…". The
     * document says "8 of 13 shown" so the trim is visible, and the XLSX still
     * carries everything.
     *
     * Keyed by heading, not index: reordering headings() must not silently
     * reshuffle which columns the PDF shows or what they are called.
     */
    public function pdfColumns(): array
    {
        return [
            'Property Name'  => ['label' => 'NAME',     'align' => 'left',  'width' => 24],
            'Property Code'  => ['label' => 'CODE',     'align' => 'left',  'width' => 11],
            'Property Type'  => ['label' => 'TYPE',     'align' => 'left',  'width' => 13],
            'Land Lord'      => ['label' => 'LANDLORD', 'align' => 'left',  'width' => 15],
            'Area'           => ['label' => 'AREA',     'align' => 'left',  'width' => 14],
            'City'           => ['label' => 'CITY',     'align' => 'left',  'width' => 11],
            'Total Floors'   => ['label' => 'FLOORS',   'align' => 'right', 'width' => 6],
            'Total Units'    => ['label' => 'UNITS',    'align' => 'right', 'width' => 6],
        ];
    }

    /**
     * Totals for the printed rows, keyed by the PDF's own labels.
     *
     * @param  \Illuminate\Support\Collection<int, Building>  $records
     */
    public function pdfTotals($records): array
    {
        return [
            'FLOORS' => number_format($records->sum(fn ($b) => $b->total_no_of_floors ?? $b->floors_count)),
            'UNITS'  => number_format($records->sum(fn ($b) => $b->total_no_of_units ?? $b->units_count)),
        ];
    }

    public function map($row): array
    {
        return [
            $row->property_name,
            $row->property_code,
            $row->type_of_ownership,
            $row->property_type,
            $row->land_lord_name,
            $row->building_no,
            $row->road,
            $row->block,
            $row->area,
            $row->city,
            $row->total_no_of_blocks,
            $row->total_no_of_floors ?? $row->floors_count,
            $row->total_no_of_units ?? $row->units_count,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]],
        ];
    }
}
