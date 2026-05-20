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
        return Building::filter($this->filters)->orderBy('property_code');
    }

    public function headings(): array
    {
        return [
            'Property Name', 'Property Code', 'Type of Ownership', 'Property Type',
            'Land Lord', 'Building No.', 'Road', 'Block', 'Area', 'City',
            'Total Blocks', 'Total Floors', 'Total Units',
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
            $row->total_no_of_floors,
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
