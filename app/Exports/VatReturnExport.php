<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VatReturnExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private Collection $rows, private string $title) {}

    public function title(): string
    {
        return $this->title;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Invoice date', 'Date of supply', 'Invoice number', 'Client/Customer name',
            'Good/Service description', 'Total BHD (exclusive of VAT)', 'VAT amount',
            'Total BHD (inclusive of VAT)', 'Tax Code', 'Place of supply',
        ];
    }

    public function map($row): array
    {
        return [
            $row['invoice_date']?->format('Y-m-d'),
            $row['date_of_supply']?->format('Y-m-d'),
            $row['reference'],
            $row['customer_name'],
            $row['description'],
            $row['taxable_amount'],
            $row['vat_amount'],
            $row['total_incl_vat'],
            $row['tax_code'],
            $row['place_of_supply'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]],
        ];
    }
}
