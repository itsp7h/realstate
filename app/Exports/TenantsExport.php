<?php

namespace App\Exports;

use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TenantsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Tenants';
    }

    public function query()
    {
        $q = Tenant::orderBy('name');

        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $q->where(function ($sub) use ($s) {
                $sub->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('id_cr_number', 'like', "%{$s}%");
            });
        }

        if (!empty($this->filters['tenant_type'])) {
            $q->where('tenant_type', $this->filters['tenant_type']);
        }

        return $q;
    }

    public function headings(): array
    {
        return ['Name', 'Tenant Type', 'ID / CR Number', 'Phone', 'Email', 'Nationality / Country'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->tenant_type,
            $row->id_cr_number,
            $row->phone,
            $row->email,
            $row->nationality_country,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]],
        ];
    }
}
