<?php

namespace App\Exports;

use App\Models\LeaseContract;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaseContractsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Lease Contracts';
    }

    public function query()
    {
        $q = LeaseContract::with('tenant')->orderBy('lease_start_date', 'desc');

        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $q->where(function ($sub) use ($s) {
                $sub->where('lease_agreement_no', 'like', "%{$s}%")
                    ->orWhere('tenant_name', 'like', "%{$s}%")
                    ->orWhere('unit', 'like', "%{$s}%");
            });
        }

        if (!empty($this->filters['property_code'])) {
            $q->where('property_code', $this->filters['property_code']);
        }

        return $q;
    }

    public function headings(): array
    {
        return [
            'Date', 'Lease Agreement No', 'Tenant Name',
            'Property Name', 'Prop Code', 'Block Name', 'Block Code', 'Floor Name', 'Floor Code',
            'Unit', 'Description',
            'Lease Start Date', 'Lease End Date',
            'Rental Income Ledger', 'Invoicing Frequency',
            'Rent Start Date', 'Rent End Date', 'Currency', 'Rent per Month',
            'Service Frequency', 'Service Start Date', 'Service End Date', 'Service Amount in BD (Excl. VAT)',
            'Security Deposit', 'Lease Break Date', 'Notice Period',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date?->format('Y-m-d'),
            $row->lease_agreement_no,
            $row->tenant_name,
            $row->property_name,
            $row->property_code,
            $row->block_name,
            $row->block_code,
            $row->floor_name,
            $row->floor_code,
            $row->unit,
            $row->description,
            $row->lease_start_date?->format('Y-m-d'),
            $row->lease_end_date?->format('Y-m-d'),
            $row->rental_income_ledger,
            $row->invoicing_frequency,
            $row->rent_start_date?->format('Y-m-d'),
            $row->rent_end_date?->format('Y-m-d'),
            $row->currency,
            $row->rent_per_month,
            $row->service_frequency,
            $row->service_start_date?->format('Y-m-d'),
            $row->service_end_date?->format('Y-m-d'),
            $row->service_amount_bd_excl_vat,
            $row->security_deposit,
            $row->lease_break_date?->format('Y-m-d'),
            $row->notice_period,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]],
        ];
    }
}
