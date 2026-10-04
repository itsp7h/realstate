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

    /**
     * Ordering for the PDF of this list — see ListingPdf::fromExport().
     *
     * @return array<int, string>
     */
    public function pdfNaturalOrder(): array
    {
        return ['property_code', 'unit_name'];
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

    /**
     * The subset worth printing. 21 columns on A4 is a table nobody reads, so
     * the document keeps identity, size and money and leaves meter numbers,
     * installation dates and municipality references to the spreadsheet.
     *
     * Labels, not indexes: reordering headings() must not silently reshuffle
     * which columns the PDF shows.
     */
    /**
     * 21 columns is a spreadsheet's job. On paper the document keeps identity,
     * size and money, with short labels so no header wraps, and says "9 of 21
     * shown" above the table.
     */
    public function pdfColumns(): array
    {
        return [
            'Property Code'    => ['label' => 'CODE',     'align' => 'left',  'width' => 10],
            'Floor Code'       => ['label' => 'FLOOR',    'align' => 'left',  'width' => 10],
            'Unit Name'        => ['label' => 'UNIT',     'align' => 'left',  'width' => 15],
            'Unit Type'        => ['label' => 'TYPE',     'align' => 'left',  'width' => 10],
            'Condition'        => ['label' => 'CONDITION','align' => 'left',  'width' => 12],
            'Area Inside'      => ['label' => 'AREA',     'align' => 'right', 'width' => 9],
            'Area Terrace'     => ['label' => 'TERRACE',  'align' => 'right', 'width' => 9],
            'Rent/Month'       => ['label' => 'RENT/MO',  'align' => 'right', 'width' => 12],
            'Security Deposit' => ['label' => 'DEPOSIT',  'align' => 'right', 'width' => 13],
        ];
    }

    /** @param  \Illuminate\Support\Collection  $records */
    public function pdfTotals($records): array
    {
        $rented = $records->whereNotNull('rent_per_month');

        return [
            // A sum over nothing but NULLs is unknown, not zero.
            'RENT/MO' => $rented->isEmpty() ? null : number_format($rented->sum('rent_per_month'), 3),
            'DEPOSIT' => $records->whereNotNull('security_deposit_amount')->isEmpty()
                ? null
                : number_format($records->sum('security_deposit_amount'), 3),
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
