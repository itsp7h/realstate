<?php

namespace App\Exports;

use App\Models\Floor;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FloorsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private ?int $buildingId = null) {}

    public function title(): string
    {
        return 'Floors';
    }

    public function query()
    {
        return Floor::with('building')
            // The live count: floors.total_no_of_units is only written when an
            // import sheet carries a Floor Units value, so it was blank on
            // every floor and both exports printed nothing for a floor holding
            // 25 units.
            ->withCount('units')
            ->when($this->buildingId, fn($q) => $q->where('building_id', $this->buildingId))
            ->orderBy('building_id')
            ->orderBy('floor_name');
    }

    /**
     * Ordering for the PDF of this list — see ListingPdf::fromExport().
     * ORDER BY floor_name put "Floor 10" between "Floor 1" and "Floor 2".
     *
     * @return array<int, string>
     */
    public function pdfNaturalOrder(): array
    {
        return ['building.property_code', 'floor_name'];
    }

    public function headings(): array
    {
        return [
            'Property Code', 'Building Name', 'Floor Name', 'Floor Code',
            'Block Name', 'Block Code', 'Units',
        ];
    }

    public function pdfColumns(): array
    {
        return [
            'Property Code' => ['label' => 'CODE',    'align' => 'left',  'width' => 14],
            'Building Name' => ['label' => 'PROPERTY','align' => 'left',  'width' => 26],
            'Floor Name'    => ['label' => 'FLOOR',   'align' => 'left',  'width' => 17],
            'Floor Code'    => ['label' => 'FLOOR #', 'align' => 'left',  'width' => 13],
            'Block Name'    => ['label' => 'BLOCK',   'align' => 'left',  'width' => 15],
            'Block Code'    => ['label' => 'BLOCK #', 'align' => 'left',  'width' => 8],
            'Units'         => ['label' => 'UNITS',   'align' => 'right', 'width' => 7],
        ];
    }

    /** @param  \Illuminate\Support\Collection  $records */
    public function pdfTotals($records): array
    {
        return ['UNITS' => number_format($records->sum('total_no_of_units'))];
    }

    public function map($row): array
    {
        return [
            optional($row->building)->property_code,
            optional($row->building)->property_name,
            $row->floor_name,
            $row->floor_code,
            $row->block_name,
            $row->block_code,
            $row->units_count ?? $row->total_no_of_units,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]],
        ];
    }
}
