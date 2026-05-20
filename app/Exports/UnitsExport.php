<?php

namespace App\Exports;

use App\Models\PropertyUnit;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Property Units';
    }

    public function query()
    {
        return PropertyUnit::filter($this->filters)
            ->orderBy('property_code')
            ->orderBy('unit_name');
    }

    public function headings(): array
    {
        return [
            'Property Code', 'Floor Code', 'Unit Name', 'Description', 'Unit Type',
            'Creation Date', 'Condition', 'View', 'Parking (FOC)',
            'Area Unit', 'Area Inside', 'Area Terrace', 'Rate per Area Unit',
            'Rent/Month', 'Security Deposit', 'Municipality Nos.',
            'Electricity Installation Date', 'Electricity Meter No.',
            'Water Installation Date', 'Water Meter No.', 'Electricity A/C No.',
        ];
    }

    public function map($row): array
    {
        return [
            $row->property_code,
            optional($row->floor)->floor_code,
            $row->unit_name,
            $row->description,
            $row->unit_type,
            $row->creation_date?->format('Y-m-d'),
            $row->unit_condition,
            $row->view,
            $row->no_of_parkings_foc,
            $row->area_unit,
            $row->area_inside,
            $row->area_terrace,
            $row->rate_per_area_unit,
            $row->rent_per_month,
            $row->security_deposit_amount,
            $row->municipality_nos,
            $row->electricity_installation_date?->format('Y-m-d'),
            $row->electricity_meter_no,
            $row->water_installation_date?->format('Y-m-d'),
            $row->water_meter_no,
            $row->electricity_ac_no,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]],
        ];
    }
}
