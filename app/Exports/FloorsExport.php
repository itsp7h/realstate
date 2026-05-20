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
            ->when($this->buildingId, fn($q) => $q->where('building_id', $this->buildingId))
            ->orderBy('building_id')
            ->orderBy('floor_name');
    }

    public function headings(): array
    {
        return [
            'Property Code', 'Building Name', 'Floor Name', 'Floor Code',
            'Block Name', 'Block Code', 'Units',
        ];
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
            $row->total_no_of_units,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]],
        ];
    }
}
