<?php

namespace App\Http\Controllers;

use App\Exports\BuildingsExport;
use App\Exports\FloorsExport;
use App\Exports\LeaseContractsExport;
use App\Exports\TenantsExport;
use App\Exports\UnitsExport;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Floor;
use App\Models\LeaseContract;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    // ── FIELD NAMES (used for DB write) ──────────────────────────────────────

    private const BUILDING_COLUMNS = [
        'property_name', 'property_code', 'type_of_ownership', 'property_type',
        'land_lord_name', 'building_no', 'road', 'block', 'area', 'city',
        'total_no_of_blocks', 'total_no_of_floors', 'total_no_of_units',
    ];

    private const FLOOR_COLUMNS = [
        'property_code', 'floor_name', 'floor_code', 'block_name', 'block_code', 'total_no_of_units',
    ];

    private const UNIT_COLUMNS = [
        'property_code', 'floor_code', 'unit_name', 'description', 'unit_type',
        'creation_date', 'unit_condition', 'view', 'no_of_parkings_foc',
        'area_unit', 'area_inside', 'area_terrace', 'rate_per_area_unit',
        'rent_per_month', 'security_deposit_amount', 'municipality_nos',
        'electricity_installation_date', 'electricity_meter_no',
        'water_installation_date', 'water_meter_no', 'electricity_ac_no',
    ];

    // ── HUMAN-READABLE LABELS (used in headers & templates) ──────────────────
    // label → field name; importer accepts either label or field name

    private const BUILDING_LABELS = [
        'Property Name'       => 'property_name',
        'Prop Name'           => 'property_name',
        'Property Code'       => 'property_code',
        'Prop Code'           => 'property_code',
        'Type of Ownership'   => 'type_of_ownership',
        'Ownership Type'      => 'type_of_ownership',
        'Property Type'       => 'property_type',
        'Land Lord'           => 'land_lord_name',
        'Landlord'            => 'land_lord_name',
        'Land Lord Name'      => 'land_lord_name',
        'Building No.'        => 'building_no',
        'Building No'         => 'building_no',
        'Road'                => 'road',
        'Block'               => 'block',
        'Area'                => 'area',
        'City'                => 'city',
        'Total Blocks'        => 'total_no_of_blocks',
        'Total No of Blocks'  => 'total_no_of_blocks',
        'Total Floors'        => 'total_no_of_floors',
        'Total No of Floors'  => 'total_no_of_floors',
        'Total Units'         => 'total_no_of_units',
        'Total No of Units'   => 'total_no_of_units',
    ];

    private const FLOOR_LABELS = [
        'Property Code'      => 'property_code',
        'Prop Code'          => 'property_code',
        'Floor Name'         => 'floor_name',
        'Floor Code'         => 'floor_code',
        'Block Name'         => 'block_name',
        'Block Code'         => 'block_code',
        'Units'              => 'total_no_of_units',
        'Total Units'        => 'total_no_of_units',
        'Total No of Units'  => 'total_no_of_units',
    ];

    private const UNIT_LABELS = [
        'Property Code'                  => 'property_code',
        'Prop Code'                      => 'property_code',
        'Floor Code'                     => 'floor_code',
        'Unit Name'                      => 'unit_name',
        'Unit'                           => 'unit_name',
        'Description'                    => 'description',
        'Unit Type'                      => 'unit_type',
        'Type'                           => 'unit_type',
        'Creation Date'                  => 'creation_date',
        'Condition'                      => 'unit_condition',
        'Unit Condition'                 => 'unit_condition',
        'View'                           => 'view',
        'Parking (FOC)'                  => 'no_of_parkings_foc',
        'Parkings FOC'                   => 'no_of_parkings_foc',
        'Area Unit'                      => 'area_unit',
        'Area Inside'                    => 'area_inside',
        'Area Terrace'                   => 'area_terrace',
        'Rate per Area Unit'             => 'rate_per_area_unit',
        'Rate/Area Unit'                 => 'rate_per_area_unit',
        'Rent/Month'                     => 'rent_per_month',
        'Rent per Month'                 => 'rent_per_month',
        'Security Deposit'               => 'security_deposit_amount',
        'Security Deposit Amount'        => 'security_deposit_amount',
        'Municipality Nos.'              => 'municipality_nos',
        'Municipality Nos'               => 'municipality_nos',
        'Electricity Installation Date'  => 'electricity_installation_date',
        'Electricity Meter No.'          => 'electricity_meter_no',
        'Electricity Meter No'           => 'electricity_meter_no',
        'Water Installation Date'        => 'water_installation_date',
        'Water Meter No.'                => 'water_meter_no',
        'Water Meter No'                 => 'water_meter_no',
        'Electricity A/C No.'            => 'electricity_ac_no',
        'Electricity AC No'              => 'electricity_ac_no',
    ];

    // ── SAMPLE ROWS ───────────────────────────────────────────────────────────

    private const BUILDING_SAMPLE = [
        'Sunrise Tower', 'SRT001', 'Freehold', 'Residential', 'John Smith',
        '12', 'King Fahad Road', '3', 'Al Olaya', 'Riyadh', '2', '15', '120',
    ];

    private const FLOOR_SAMPLE = [
        'SRT001', 'Ground Floor', 'GF', 'Block A', 'BLA', '8',
    ];

    private const CONTRACT_COLUMNS = [
        'date', 'lease_agreement_no', 'tenant_name',
        'property_name', 'property_code', 'block_name', 'block_code', 'floor_name', 'floor_code',
        'unit', 'description',
        'lease_start_date', 'lease_end_date',
        'rental_income_ledger', 'invoicing_frequency',
        'rent_start_date', 'rent_end_date', 'currency', 'rent_per_month',
        'service_frequency', 'service_start_date', 'service_end_date', 'service_amount_bd_excl_vat',
        'security_deposit', 'lease_break_date', 'notice_period',
    ];

    private const CONTRACT_LABELS = [
        'Date'                                       => 'date',
        'Lease Agreement No'                         => 'lease_agreement_no',
        'Lease Agreement No.'                        => 'lease_agreement_no',
        'Agreement No'                               => 'lease_agreement_no',
        'Tenant Name'                                => 'tenant_name',
        'Tenant'                                     => 'tenant_name',
        'Property Name'                              => 'property_name',
        'Prop Name'                                  => 'property_name',
        'Property Code'                              => 'property_code',
        'Prop Code'                                  => 'property_code',
        'Block Name'                                 => 'block_name',
        'Block Code'                                 => 'block_code',
        'Floor Name'                                 => 'floor_name',
        'Floor Code'                                 => 'floor_code',
        'Unit'                                       => 'unit',
        'Unit Name'                                  => 'unit',
        'Description'                                => 'description',
        'Lease Start Date'                           => 'lease_start_date',
        'Lease End Date'                             => 'lease_end_date',
        'Rental Income Ledger'                       => 'rental_income_ledger',
        'Invoicing Frequency'                        => 'invoicing_frequency',
        'Invoicing Frequncy'                         => 'invoicing_frequency',  // typo alias
        'Invoicing Freq'                             => 'invoicing_frequency',
        'Rent Start Date'                            => 'rent_start_date',
        'Rent End Date'                              => 'rent_end_date',
        'Currency'                                   => 'currency',
        'Rent per Month'                             => 'rent_per_month',
        'Rent/Month'                                 => 'rent_per_month',
        'Service Frequency'                          => 'service_frequency',
        'Service Freq'                               => 'service_frequency',
        'Service Start Date'                         => 'service_start_date',
        'Service End Date'                           => 'service_end_date',
        'Service Amount in BD (Excl. VAT)'           => 'service_amount_bd_excl_vat',
        'Service Amount in BD (Exclusive VAT)'       => 'service_amount_bd_excl_vat',
        'Service Amount in BD (Exlusive VAT)'        => 'service_amount_bd_excl_vat',  // typo alias
        'Service Amount BD'                          => 'service_amount_bd_excl_vat',
        'Security Deposit'                           => 'security_deposit',
        'Lease Break Date'                           => 'lease_break_date',
        'Notice Period'                              => 'notice_period',
    ];

    private const TENANT_COLUMNS = [
        'name', 'tenant_type', 'id_cr_number', 'phone', 'email', 'nationality_country',
    ];

    private const TENANT_LABELS = [
        'Name'                  => 'name',
        'Tenant Name'           => 'name',
        'Full Name'             => 'name',
        'Tenant Type'           => 'tenant_type',
        'Type'                  => 'tenant_type',
        'ID / CR Number'        => 'id_cr_number',
        'ID/CR Number'          => 'id_cr_number',
        'CR Number'             => 'id_cr_number',
        'ID Number'             => 'id_cr_number',
        'Phone'                 => 'phone',
        'Phone Number'          => 'phone',
        'Mobile'                => 'phone',
        'Email'                 => 'email',
        'Email Address'         => 'email',
        'Nationality / Country' => 'nationality_country',
        'Nationality/Country'   => 'nationality_country',
        'Nationality'           => 'nationality_country',
        'Country'               => 'nationality_country',
    ];

    private const TENANT_SAMPLE = [
        'Ahmed Al-Khalifa', 'individual', '840912345', '+973 3300 0000', 'ahmed@email.com', 'Bahraini',
    ];

    private const CONTRACT_SAMPLE = [
        '2025-03-01', 'LA/0001', 'Ahmed Al-Khalifa',
        'P7H Muharraq Bldg. 2', 'P7H-1130N', 'Block 1', 'BL1', 'Floor 1', 'FL1',
        '1130N-F1-110', 'Fitted',
        '2025-03-01', '2026-02-28',
        '41010011', 'Monthly',
        '2025-03-01', '2026-02-28', 'BHD', '450.000',
        'Monthly', '2025-03-01', '2026-02-28', '50.000',
        '900.000', '2026-02-28', '1 Month',
    ];

    private const UNIT_SAMPLE = [
        'SRT001', 'GF', '101', '1-bedroom apartment with city view', 'Apartment',
        '2024-01-15', 'Furnished', 'City', '1', 'sqft', '850.00', '0.00',
        '120.00', '4500.00', '9000.00', 'MUN-12345',
        '2023-06-01', 'EL-001', '2023-06-01', 'WM-001', 'AC-001',
    ];

    // ── CANONICAL TEMPLATE LABELS (download templates — no aliases) ──────────

    private const BUILDING_TEMPLATE_LABELS = [
        'Property Name'       => 'property_name',
        'Property Code'       => 'property_code',
        'Type of Ownership'   => 'type_of_ownership',
        'Property Type'       => 'property_type',
        'Land Lord'           => 'land_lord_name',
        'Building No.'        => 'building_no',
        'Road'                => 'road',
        'Block'               => 'block',
        'Area'                => 'area',
        'City'                => 'city',
        'Total Blocks'        => 'total_no_of_blocks',
        'Total Floors'        => 'total_no_of_floors',
        'Total Units'         => 'total_no_of_units',
    ];

    private const FLOOR_TEMPLATE_LABELS = [
        'Property Code'  => 'property_code',
        'Floor Name'     => 'floor_name',
        'Floor Code'     => 'floor_code',
        'Block Name'     => 'block_name',
        'Block Code'     => 'block_code',
        'Units'          => 'total_no_of_units',
    ];

    private const UNIT_TEMPLATE_LABELS = [
        'Property Code'                  => 'property_code',
        'Floor Code'                     => 'floor_code',
        'Unit Name'                      => 'unit_name',
        'Description'                    => 'description',
        'Unit Type'                      => 'unit_type',
        'Creation Date'                  => 'creation_date',
        'Condition'                      => 'unit_condition',
        'View'                           => 'view',
        'Parking (FOC)'                  => 'no_of_parkings_foc',
        'Area Unit'                      => 'area_unit',
        'Area Inside'                    => 'area_inside',
        'Area Terrace'                   => 'area_terrace',
        'Rate per Area Unit'             => 'rate_per_area_unit',
        'Rent/Month'                     => 'rent_per_month',
        'Security Deposit'               => 'security_deposit_amount',
        'Municipality Nos.'              => 'municipality_nos',
        'Electricity Installation Date'  => 'electricity_installation_date',
        'Electricity Meter No.'          => 'electricity_meter_no',
        'Water Installation Date'        => 'water_installation_date',
        'Water Meter No.'                => 'water_meter_no',
        'Electricity A/C No.'            => 'electricity_ac_no',
    ];

    private const TENANT_TEMPLATE_LABELS = [
        'Name'                  => 'name',
        'Tenant Type'           => 'tenant_type',
        'ID / CR Number'        => 'id_cr_number',
        'Phone'                 => 'phone',
        'Email'                 => 'email',
        'Nationality / Country' => 'nationality_country',
    ];

    private const CONTRACT_TEMPLATE_LABELS = [
        'Date'                             => 'date',
        'Lease Agreement No'               => 'lease_agreement_no',
        'Tenant Name'                      => 'tenant_name',
        'Property Name'                    => 'property_name',
        'Prop Code'                        => 'property_code',
        'Block Name'                       => 'block_name',
        'Block Code'                       => 'block_code',
        'Floor Name'                       => 'floor_name',
        'Floor Code'                       => 'floor_code',
        'Unit'                             => 'unit',
        'Description'                      => 'description',
        'Lease Start Date'                 => 'lease_start_date',
        'Lease End Date'                   => 'lease_end_date',
        'Rental Income Ledger'             => 'rental_income_ledger',
        'Invoicing Frequency'              => 'invoicing_frequency',
        'Rent Start Date'                  => 'rent_start_date',
        'Rent End Date'                    => 'rent_end_date',
        'Currency'                         => 'currency',
        'Rent per Month'                   => 'rent_per_month',
        'Service Frequency'                => 'service_frequency',
        'Service Start Date'               => 'service_start_date',
        'Service End Date'                 => 'service_end_date',
        'Service Amount in BD (Excl. VAT)' => 'service_amount_bd_excl_vat',
        'Security Deposit'                 => 'security_deposit',
        'Lease Break Date'                 => 'lease_break_date',
        'Notice Period'                    => 'notice_period',
    ];

    // ── TEMPLATE DOWNLOADS ────────────────────────────────────────────────────

    public function template(string $type, string $format = 'csv'): StreamedResponse|BinaryFileResponse
    {
        if ($type === 'smart') {
            abort_if($format !== 'xlsx', 404, 'The Smart Import template is only available as XLSX (one tab per data type).');

            $sheets = [
                'Buildings' => [self::BUILDING_TEMPLATE_LABELS,  self::BUILDING_SAMPLE],
                'Floors'    => [self::FLOOR_TEMPLATE_LABELS,     self::FLOOR_SAMPLE],
                'Units'     => [self::UNIT_TEMPLATE_LABELS,      self::UNIT_SAMPLE],
                'Tenants'   => [self::TENANT_TEMPLATE_LABELS,    self::TENANT_SAMPLE],
                'Contracts' => [self::CONTRACT_TEMPLATE_LABELS,  self::CONTRACT_SAMPLE],
            ];

            return Excel::download(
                new class($sheets) implements \Maatwebsite\Excel\Concerns\WithMultipleSheets {
                    public function __construct(private array $sheets) {}
                    public function sheets(): array
                    {
                        return array_map(
                            fn(array $def, string $title) => new class(array_keys($def[0]), $def[1], $title) implements
                                \Maatwebsite\Excel\Concerns\FromArray,
                                \Maatwebsite\Excel\Concerns\WithTitle,
                                \Maatwebsite\Excel\Concerns\WithStyles,
                                \Maatwebsite\Excel\Concerns\ShouldAutoSize
                            {
                                public function __construct(private array $cols, private array $sampleRow, private string $title) {}
                                public function array(): array { return [$this->cols, $this->sampleRow]; }
                                public function title(): string { return $this->title; }
                                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array {
                                    return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]]];
                                }
                            },
                            $this->sheets,
                            array_keys($this->sheets)
                        );
                    }
                },
                'import-smart-template.xlsx'
            );
        }

        [$labelMap, $sample] = match ($type) {
            'buildings' => [self::BUILDING_TEMPLATE_LABELS,  self::BUILDING_SAMPLE],
            'floors'    => [self::FLOOR_TEMPLATE_LABELS,     self::FLOOR_SAMPLE],
            'units'     => [self::UNIT_TEMPLATE_LABELS,      self::UNIT_SAMPLE],
            'tenants'   => [self::TENANT_TEMPLATE_LABELS,    self::TENANT_SAMPLE],
            'contracts' => [self::CONTRACT_TEMPLATE_LABELS,  self::CONTRACT_SAMPLE],
            default     => abort(404),
        };

        $headers = array_keys($labelMap);

        if ($format === 'xlsx') {
            return Excel::download(
                new class($headers, $sample) implements
                    \Maatwebsite\Excel\Concerns\FromArray,
                    \Maatwebsite\Excel\Concerns\WithStyles,
                    \Maatwebsite\Excel\Concerns\ShouldAutoSize
                {
                    public function __construct(private array $cols, private array $sampleRow) {}
                    public function array(): array { return [$this->cols, $this->sampleRow]; }
                    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array {
                        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0B1120']], 'fill' => ['fillType' => 'solid', 'color' => ['argb' => 'FFE8B86D']]]];
                    }
                },
                "import-{$type}-template.xlsx"
            );
        }

        return response()->streamDownload(function () use ($headers, $sample) {
            $h = fopen('php://output', 'w');
            fputcsv($h, $headers);
            fputcsv($h, $sample);
            fclose($h);
        }, "import-{$type}-template.csv", ['Content-Type' => 'text/csv']);
    }

    // ── EXPORTS ───────────────────────────────────────────────────────────────

    public function exportBuildings(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'property_type', 'type_of_ownership', 'company_name']);
        return Excel::download(new BuildingsExport($filters), 'buildings-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportFloors(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new FloorsExport($request->integer('building_id') ?: null),
            'floors-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportUnits(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'property_code', 'unit_type', 'unit_condition']);
        return Excel::download(new UnitsExport($filters), 'units-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportTenants(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'tenant_type', 'company_name']);
        return Excel::download(new TenantsExport($filters), 'tenants-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportContracts(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'property_code']);
        return Excel::download(new LeaseContractsExport($filters), 'lease-contracts-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ── IMPORTS ───────────────────────────────────────────────────────────────

    public function buildings(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        [$imported, $errors] = $this->parseFile(
            $request->file('file'),
            self::BUILDING_LABELS,
            ['property_name', 'property_code'],
            function (array $record, int $row): array {
                $code = strtoupper(trim($record['property_code']));
                if (Building::where('property_code', $code)->exists()) {
                    return ['error' => "Row {$row}: Property Code '{$code}' already exists — skipped."];
                }
                $record['property_code'] = $code;
                return ['data' => $this->onlyFillable($record, self::BUILDING_COLUMNS)];
            },
            fn(array $data) => Building::create($data)
        );

        if ($imported > 0) AuditLog::record('imported', 'Building', null, "{$imported} row(s)");
        return redirect()->route('buildings.index')
            ->with(['import_type' => 'buildings', 'import_count' => $imported, 'import_errors' => $errors]);
    }

    public function floors(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $buildingMap = Building::pluck('id', 'property_code')->mapWithKeys(
            fn($id, $code) => [strtoupper($code) => $id]
        )->all();

        [$imported, $errors] = $this->parseFile(
            $request->file('file'),
            self::FLOOR_LABELS,
            ['property_code', 'floor_name'],
            function (array $record, int $row) use ($buildingMap): array {
                $code = strtoupper(trim($record['property_code']));
                if (!isset($buildingMap[$code])) {
                    return ['error' => "Row {$row}: Property Code '{$code}' not found — skipped."];
                }
                $data = $this->onlyFillable($record, self::FLOOR_COLUMNS, exclude: ['property_code']);
                $data['building_id'] = $buildingMap[$code];
                return ['data' => $data];
            },
            fn(array $data) => Floor::create($data)
        );

        if ($imported > 0) AuditLog::record('imported', 'Floor', null, "{$imported} row(s)");
        return redirect()->route('floors.global')
            ->with(['import_type' => 'floors', 'import_count' => $imported, 'import_errors' => $errors]);
    }

    public function units(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $buildingMap = Building::pluck('id', 'property_code')->mapWithKeys(
            fn($id, $code) => [strtoupper($code) => $id]
        )->all();

        $floorMap = [];
        Floor::whereNotNull('floor_code')->get(['id', 'building_id', 'floor_code'])->each(function ($f) use (&$floorMap) {
            $floorMap[$f->building_id][strtoupper($f->floor_code)] = $f->id;
        });

        [$imported, $errors] = $this->parseFile(
            $request->file('file'),
            self::UNIT_LABELS,
            ['property_code', 'unit_name'],
            function (array $record, int $row) use ($buildingMap, $floorMap): array {
                $bCode = strtoupper(trim($record['property_code']));
                if (!isset($buildingMap[$bCode])) {
                    return ['error' => "Row {$row}: Property Code '{$bCode}' not found — skipped."];
                }
                $buildingId = $buildingMap[$bCode];
                $data = $this->onlyFillable($record, self::UNIT_COLUMNS, exclude: ['property_code', 'floor_code']);

                $building = Building::find($buildingId);
                $data['building_id']       = $buildingId;
                $data['property_code']     = $building->property_code;
                $data['property_name']     = $data['property_name']     ?? $building->property_name;
                $data['type_of_ownership'] = $data['type_of_ownership'] ?? $building->type_of_ownership;
                $data['property_type']     = $data['property_type']     ?? $building->property_type;
                $data['land_lord_name']    = $data['land_lord_name']    ?? $building->land_lord_name;

                $fCode = strtoupper(trim($record['floor_code'] ?? ''));
                if ($fCode && isset($floorMap[$buildingId][$fCode])) {
                    $data['floor_id'] = $floorMap[$buildingId][$fCode];
                }

                return ['data' => $data];
            },
            fn(array $data) => PropertyUnit::create($data)
        );

        if ($imported > 0) AuditLog::record('imported', 'PropertyUnit', null, "{$imported} row(s)");
        return redirect()->route('property-units.index')
            ->with(['import_type' => 'units', 'import_count' => $imported, 'import_errors' => $errors]);
    }

    public function tenants(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        [$imported, $errors] = $this->parseFile(
            $request->file('file'),
            self::TENANT_LABELS,
            ['name'],
            function (array $record, int $row): array {
                $name = trim($record['name']);
                if (Tenant::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
                    return ['error' => "Row {$row}: Tenant '{$name}' already exists — skipped."];
                }

                $data = $this->onlyFillable($record, self::TENANT_COLUMNS);

                if (!empty($data['tenant_type'])) {
                    $data['tenant_type'] = strtolower($data['tenant_type']);
                    if (!in_array($data['tenant_type'], ['individual', 'company'])) {
                        $data['tenant_type'] = 'individual';
                    }
                } else {
                    $data['tenant_type'] = 'individual';
                }

                return ['data' => $data];
            },
            fn(array $data) => Tenant::create($data)
        );

        if ($imported > 0) AuditLog::record('imported', 'Tenant', null, "{$imported} row(s)");
        return redirect()->route('tenants.index')
            ->with(['import_type' => 'tenants', 'import_count' => $imported, 'import_errors' => $errors]);
    }

    public function contracts(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        // Pre-load tenant name → id and unit identifier → id lookups
        $tenantMap = Tenant::pluck('id', 'name')->mapWithKeys(
            fn($id, $name) => [strtolower(trim($name)) => $id]
        )->all();

        $unitMap = PropertyUnit::whereNotNull('unit_name')->pluck('id', 'unit_name')
            ->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id])
            ->all();

        [$imported, $errors] = $this->parseFile(
            $request->file('file'),
            self::CONTRACT_LABELS,
            ['lease_agreement_no', 'tenant_name', 'lease_start_date', 'lease_end_date'],
            function (array $record, int $row) use ($tenantMap, $unitMap): array {
                $agreementNo = trim($record['lease_agreement_no']);
                if (LeaseContract::where('lease_agreement_no', $agreementNo)->exists()) {
                    return ['error' => "Row {$row}: Lease Agreement No '{$agreementNo}' already exists — skipped."];
                }

                $data = $this->onlyFillable($record, self::CONTRACT_COLUMNS);

                // Normalize date fields — accept Excel serial numbers or string dates
                foreach (['date', 'lease_start_date', 'lease_end_date', 'lease_break_date',
                          'rent_start_date', 'rent_end_date', 'service_start_date', 'service_end_date'] as $dateField) {
                    if (!empty($data[$dateField])) {
                        $data[$dateField] = $this->parseDate($data[$dateField]);
                    }
                }

                // Link tenant by name (case-insensitive)
                $tenantKey = strtolower(trim($record['tenant_name'] ?? ''));
                if (isset($tenantMap[$tenantKey])) {
                    $data['tenant_id'] = $tenantMap[$tenantKey];
                }

                // Link unit by identifier (case-insensitive)
                $unitKey = strtolower(trim($record['unit'] ?? ''));
                if ($unitKey && isset($unitMap[$unitKey])) {
                    $data['unit_id'] = $unitMap[$unitKey];
                }

                return ['data' => $data];
            },
            fn(array $data) => LeaseContract::create($data)
        );

        if ($imported > 0) AuditLog::record('imported', 'LeaseContract', null, "{$imported} row(s)");
        return redirect()->route('lease-contracts.index')
            ->with(['import_type' => 'contracts', 'import_count' => $imported, 'import_errors' => $errors]);
    }

    // ── SMART / UNIVERSAL IMPORT ─────────────────────────────────────────────

    public function smart(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());

        // Multi-sheet "Property Data Base" workbooks (e.g. one sheet per Miknas
        // Plaza building, a P7H commercial sheet, a Platinum Tower sheet) don't
        // fit the single-sheet / single-entity-type model below at all — detect
        // that shape up front from the sheet names and route to a dedicated parser.
        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $sheetNames  = $spreadsheet->getSheetNames();
            // "Plat" (not the full "Platinum") deliberately tolerates the
            // "Platimum Tower" typo found in the real monthly workbook.
            // Unanchored (not "^MP\d") so a "Company - <Name> - " prefix on
            // the tab, used to tag tenants under a portfolio company, doesn't
            // push the marker off the start of the string.
            $matches     = array_filter($sheetNames, fn($name) => preg_match('/MP\d|P7H|Plat/i', trim($name)));

            // Some commercial workbooks pack multiple buildings into ONE sheet
            // via row headers ("Building 1130N - Ground Floor", ...) instead of
            // one sheet per building — the sheet-name count alone misses those,
            // so also check for that row-based shape directly.
            if (count($matches) >= 2 || $this->pdbSheetHasBuildingSectionRows($spreadsheet)) {
                $results = $this->smartImportPropertyDataBase($spreadsheet, $request->file('file')->getClientOriginalName());
                return redirect()->route('dashboard')->with('smart_import_results', $results);
            }
        }

        $allRows = in_array($ext, ['xlsx', 'xls'])
            ? $this->readXlsx($request->file('file'))
            : $this->readCsv($request->file('file'));

        if (!$allRows || count($allRows) < 1) {
            return redirect()->route('dashboard')
                ->with('smart_import_error', 'File appears empty or could not be read.');
        }

        $rawHeaders = array_shift($allRows);
        $dataRows   = $allRows;

        // Combined label→field map; CONTRACT_LABELS last so 'Tenant Name' → 'tenant_name'
        $combinedLabels = array_merge(
            self::BUILDING_LABELS, self::FLOOR_LABELS, self::UNIT_LABELS,
            self::TENANT_LABELS,   self::CONTRACT_LABELS,
        );
        $fieldByLabel = [];
        foreach ($combinedLabels as $label => $field) {
            $fieldByLabel[strtolower($label)] = $field;
        }

        $headers = array_map(function ($h) use ($fieldByLabel) {
            $h = trim((string) $h);
            return $fieldByLabel[strtolower($h)] ?? $this->fuzzyUnitIdentifierAlias($h) ?? $h;
        }, $rawHeaders);

        $detected = $this->detectEntities($headers);

        if (empty($detected)) {
            return redirect()->route('dashboard')
                ->with('smart_import_error',
                    'Could not detect any known data type. Columns found: '
                    . implode(', ', array_filter($rawHeaders)));
        }

        $results = [];

        if (in_array('contracts', $detected)) {
            [$tImported, $tErrors, $cImported, $cErrors] =
                $this->smartImportContractsWithTenants($dataRows, $headers);
            $results['tenants']   = ['imported' => $tImported, 'errors' => $tErrors];
            $results['contracts'] = ['imported' => $cImported, 'errors' => $cErrors];
        } else {
            // Always process in dependency order: buildings → floors → units → tenants
            $order = ['buildings', 'floors', 'units', 'tenants'];
            $sorted = array_values(array_intersect($order, $detected));
            foreach ($sorted as $entity) {
                $results[$entity] = $this->smartImportGeneric($entity, $dataRows, $headers);
            }
        }

        return redirect()->route('dashboard')->with('smart_import_results', $results);
    }

    // ── PROPERTY DATA BASE (multi-sheet monthly workbook) IMPORT ─────────────

    private const PDB_RESIDENTIAL_HEADER_ALIASES = [
        'flat'              => 'flat',
        'br'                => 'br',
        'name'              => 'name',
        'rent'              => 'rent',
        'security deposit'  => 'security_deposit',
        'security'          => 'security_deposit',
        'ewa cap'           => 'ewa_cap',
        'contact no'        => 'contact',
        'contact mobile'    => 'contact',
        'email'             => 'email',
        'contract period'   => 'contract_period',
        'status'            => 'status',
        'staus'             => 'status',
    ];

    private const PDB_COMMERCIAL_HEADER_ALIASES = [
        'off/shop'          => 'shop',
        'shop no'           => 'shop',
        'office no'         => 'shop',
        'office number'     => 'shop',
        'company'           => 'company',
        'company name'      => 'company',
        'size /sqm'         => 'size',
        'size/sqm'          => 'size',
        'size'              => 'size',
        'area (m²)'         => 'size',
        'area (m2)'         => 'size',
        'rate/ sqm'         => 'rate',
        'rate/sqm'          => 'rate',
        'rate / m²'         => 'rate',
        'rate/m²'           => 'rate',
        'rent'              => 'rent',
        'service charge'    => 'service_charge',
        'monthly'           => 'monthly',
        'contract period'   => 'contract_period',
        'contact details'   => 'contact',
        'contact name'      => 'contact_name',
        'phone'             => 'phone',
        'email'             => 'email',
        'occupancy'         => 'occupancy',
    ];

    // Shared/common spaces listed alongside real shops and offices in the
    // commercial sheets — not leasable, so they shouldn't become units.
    // "Store" is included because it's used here as a bundled note on an
    // adjacent office's lease (e.g. "#108&Store"), not a standalone unit —
    // importing its own "STORE" row creates a phantom unit and a duplicate
    // tenant record for the same company under a different name.
    private const PDB_NON_RENTABLE_LABELS = [
        'pantry', 'server room', 'woman toilets', 'women toilets', 'men toilets', 'common area', 'store',
    ];

    private const PDB_PLATINUM_HEADER_ALIASES = [
        'office no'         => 'office',
        'title deed (sqm)'  => 'title_deed_sqm',
        'per plan (sqm)'    => 'per_plan_sqm',
        'rate / m²'         => 'rate',
        'rate/m²'           => 'rate',
        'rent'              => 'rent',
        'parking bay nos'   => 'parking',
        'remarks'           => 'remarks',
    ];

    /**
     * True if any sheet has a row-header shape like "Building 1130N - Ground
     * Floor" — the marker pdbParseCommercialSheet looks for. A single sheet
     * can pack multiple buildings this way, so this check exists separately
     * from the sheet-name-based multi-building detection in smart().
     */
    private function pdbSheetHasBuildingSectionRows(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): bool
    {
        foreach ($spreadsheet->getSheetNames() as $name) {
            $rows = $spreadsheet->getSheetByName($name)->toArray(null, true, true, false);
            foreach ($rows as $row) {
                $firstCell = trim((string) ($row[0] ?? ''));
                if ($firstCell === '') {
                    continue;
                }
                // Commercial sheets mark sections with "Building ... - <Floor>"
                // row headers; residential (Miknas Plaza) sheets instead open
                // with a "<Property> - Data Base - <Month>" title row. Either
                // shape means this is a PDB workbook even as a single sheet.
                if (preg_match('/building|bldg/i', $firstCell) || preg_match('/-\s*data base/i', $firstCell)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Entry point for the multi-sheet monthly "Property Data Base" workbook.
     * Each sheet is routed to the parser matching its known shape; sheets that
     * don't match any known shape are left untouched. Writes are committed
     * directly (no staging step) to match how the rest of smart() behaves.
     */
    private function smartImportPropertyDataBase(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $originalFilename = ''): array
    {
        $results = [
            'buildings' => ['imported' => 0, 'errors' => []],
            'floors'    => ['imported' => 0, 'errors' => []],
            'units'     => ['imported' => 0, 'errors' => []],
            'tenants'   => ['imported' => 0, 'errors' => []],
            'contracts' => ['imported' => 0, 'errors' => []],
        ];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $trimmed = trim($sheetName);
            $rows    = $spreadsheet->getSheetByName($sheetName)->toArray(null, true, true, false);

            if (preg_match('/MP(\d+)/i', $trimmed, $m)) {
                $this->pdbParseResidentialSheet($rows, 'MP' . $m[1], $sheetName, $results, $originalFilename);
            } elseif (stripos($trimmed, 'P7H') !== false && str_contains($trimmed, '&')) {
                // Older duplicate "rent allocation" summary of the same buildings/floors
                // covered by the more detailed "P7H ... " sheet — deliberately skipped.
                continue;
            } elseif (stripos($trimmed, 'P7H') !== false) {
                $this->pdbParseCommercialSheet($rows, $sheetName, $results, $originalFilename);
            } elseif (stripos($trimmed, 'Plat') !== false) {
                $this->pdbParsePlatinumTowerSheet($rows, $sheetName, $results, $originalFilename);
            }
        }

        return $results;
    }

    private function pdbNormalizeHeader(mixed $label): string
    {
        $norm = strtolower(trim((string) $label));
        $norm = preg_replace('/\s+/', ' ', $norm);
        return rtrim($norm, '.');
    }

    /** @return array<string,int> field => column index */
    private function pdbMapHeaderRow(array $headerRow, array $aliases): array
    {
        $map = [];
        foreach ($headerRow as $colIdx => $label) {
            $norm = $this->pdbNormalizeHeader($label);
            if (isset($aliases[$norm])) {
                $map[$aliases[$norm]] = $colIdx;
            }
        }
        return $map;
    }

    /**
     * Commercial sheets don't label the "EWA share" column with a fixed
     * name — the header cell instead has that floor's shared EWA meter
     * account number (a different number each section, e.g. "7164177"),
     * with each office's row holding its fixed percentage share of that
     * meter's bill underneath. Detect it by shape (a long digit-only
     * header cell) rather than by text, since the text itself varies.
     */
    private function pdbDetectEwaShareColumn(array $headerRow, array $colMap): array
    {
        if (isset($colMap['ewa_share_percent'])) {
            return $colMap;
        }
        $usedColumns = array_flip($colMap);
        foreach ($headerRow as $colIdx => $label) {
            if (isset($usedColumns[$colIdx])) {
                continue;
            }
            if (preg_match('/^\d{5,}$/', trim((string) $label))) {
                $colMap['ewa_share_percent'] = $colIdx;
                break;
            }
        }
        return $colMap;
    }

    /**
     * True for blank, "vacant", or a bare punctuation placeholder (e.g. "-")
     * that some commercial-sheet rows use for non-lettable common areas
     * (pantry, toilets, server room) instead of a real company name.
     */
    private function pdbIsBlankTenantMarker(string $value): bool
    {
        $normalized = strtolower(trim($value));
        return $normalized === '' || $normalized === 'vacant' || (bool) preg_match('/^[-–—.]+$/', $normalized);
    }

    private function pdbIsNonRentableSpace(string $label): bool
    {
        $normalized = strtolower(trim($label));
        foreach (self::PDB_NON_RENTABLE_LABELS as $needle) {
            if ($normalized === $needle || str_starts_with($normalized, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Source files are inconsistent about how a vacant unit is marked: the
     * Company/Name cell is sometimes left blank, sometimes has "Vacant"
     * typed into it, and sometimes still has stale data from a past tenant.
     * An explicit Occupancy/Status column (when present) is the most
     * reliable signal, so it takes priority; the old blank-cell heuristic
     * is only a fallback for files without that column. Conflicts between
     * the two are still imported (trusting the explicit signal) but logged
     * so the source file can be cleaned up.
     */
    private function pdbResolveVacancy(string $signalRaw, string $tenantNameRaw, string $sheetName, int $displayRow, array &$results): bool
    {
        $signal = strtolower(trim($signalRaw));
        $blank  = $this->pdbIsBlankTenantMarker($tenantNameRaw);

        if ($signal === 'vacant') {
            if (!$blank) {
                $results['units']['errors'][] =
                    "Sheet '{$sheetName}' row {$displayRow}: Occupancy says Vacant but the Company/Name field still has '{$tenantNameRaw}' — treated as vacant, no tenant/lease created.";
            }
            return true;
        }

        if ($signal === 'occupied') {
            if ($blank) {
                $results['units']['errors'][] =
                    "Sheet '{$sheetName}' row {$displayRow}: Occupancy says Occupied but the Company/Name field is empty — no tenant name to import, skipped.";
                return true;
            }
            return false;
        }

        // No explicit signal on this row — fall back to inferring from the tenant name field.
        return $blank;
    }

    private function pdbToDecimal(string $value): ?float
    {
        $value = trim(str_replace([',', 'BD', 'BHD'], '', $value));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }

    /**
     * Best-effort split of a free-text contract period (e.g. "01-01-2024- 31-12-2024",
     * "01.04.2022 - 31.03.2026") into [start, end]. Entries that don't contain two
     * recognizable dates (e.g. "3 Months starting from 01-03-2025") return [null, null]
     * so the caller can skip lease creation rather than guess.
     */
    private function pdbParseContractPeriod(string $text): array
    {
        $matches = $this->pdbExtractDateStrings($text);
        if (count($matches) < 2) {
            return [null, null];
        }

        $start = $this->parseDate($matches[0]);
        $end   = $this->parseDate($matches[1]);

        if (!$start || !$end) {
            return [null, null];
        }

        return $start <= $end ? [$start, $end] : [$end, $start];
    }

    private function pdbExtractDateStrings(string $text): array
    {
        preg_match_all('/\d{1,2}[.\/\-]\d{1,2}[.\/\-]\d{2,4}/', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Some monthly sheets wrap a shop/flat's row across two or three physical
     * spreadsheet rows (the main row has the unit number and start date, a
     * row underneath carries the contact/email, another the end date). When
     * the main row's own Contract Period cell only has one date, scan the
     * continuation rows directly below it — those with a blank identifier
     * cell (shop/flat column) — for a second date in the same column, and
     * stop at the next real entry or the next building/floor header.
     */
    private function pdbCompleteContractPeriod(array $rows, int $rowCount, int $mainRowIndex, string $mainText, ?int $identifierCol, ?int $periodCol): string
    {
        if ($periodCol === null || count($this->pdbExtractDateStrings($mainText)) >= 2) {
            return $mainText;
        }

        $combined = $mainText;
        for ($j = $mainRowIndex + 1; $j < $rowCount; $j++) {
            $nextRow      = $rows[$j];
            $nextFirstCell = trim((string) ($nextRow[0] ?? ''));
            if ($nextFirstCell !== '' && preg_match('/building|bldg/i', $nextFirstCell)) {
                break;
            }
            if ($identifierCol !== null && trim((string) ($nextRow[$identifierCol] ?? '')) !== '') {
                break; // reached the next real shop/flat entry
            }

            $continuationText = trim((string) ($nextRow[$periodCol] ?? ''));
            if ($continuationText !== '') {
                $combined .= ' ' . $continuationText;
            }
            if (count($this->pdbExtractDateStrings($combined)) >= 2) {
                break;
            }
        }

        return $combined;
    }

    /**
     * "Contact Details" in the commercial sheets is really three different
     * things stacked across a shop's main row and its continuation rows: a
     * contact person's name on the main row, then an email and/or phone
     * number(s) on the row(s) underneath. Classify each candidate value by
     * shape (has "@" → email, mostly digits → phone, otherwise the contact
     * person's name) instead of assuming the main row's value is a phone.
     *
     * @return array{phone: ?string, email: ?string, contact_person: ?string}
     */
    private function pdbCompleteContactDetails(array $rows, int $rowCount, int $mainRowIndex, string $mainText, ?int $identifierCol, ?int $contactCol): array
    {
        if ($contactCol === null) {
            return ['phone' => null, 'email' => null, 'contact_person' => null];
        }

        $candidates = $mainText !== '' ? [$mainText] : [];
        for ($j = $mainRowIndex + 1; $j < $rowCount; $j++) {
            $nextRow       = $rows[$j];
            $nextFirstCell = trim((string) ($nextRow[0] ?? ''));
            if ($nextFirstCell !== '' && preg_match('/building|bldg/i', $nextFirstCell)) {
                break;
            }
            if ($identifierCol !== null && trim((string) ($nextRow[$identifierCol] ?? '')) !== '') {
                break; // reached the next real shop/flat entry
            }

            $continuationText = trim((string) ($nextRow[$contactCol] ?? ''));
            if ($continuationText !== '') {
                $candidates[] = $continuationText;
            }
        }

        $phone         = null;
        $email         = null;
        $contactPerson = null;
        foreach ($candidates as $value) {
            if ($email === null && preg_match('/[^\s@]+@[^\s@]+\.[^\s@]+/', $value, $m)) {
                $email = $m[0];
                continue;
            }
            if ($phone === null && preg_match('/^[\d+\-,\/\s]{6,}$/', $value)) {
                $phone = $value;
                continue;
            }
            if ($contactPerson === null) {
                $contactPerson = $value;
            }
        }

        return ['phone' => $phone, 'email' => $email, 'contact_person' => $contactPerson];
    }

    private function pdbFindHeaderRowIndex(array $rows, string $mustContainPattern): ?int
    {
        foreach ($rows as $idx => $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && preg_match($mustContainPattern, trim($cell))) {
                    return $idx;
                }
            }
        }
        return null;
    }

    /**
     * A sheet tab named "<Company> - MP1 Aug 2026" / "<Company> - P7H Aug 2026" /
     * "<Company> - Platimum Tower" (any case, e.g. "Promoseven - P7H Aug 2026")
     * means every tenant on it belongs under that parent/portfolio company —
     * tenant_type becomes 'company' and $companyName carries "<Company>".
     * There's no literal "Company" marker word — Excel caps sheet titles at
     * 31 characters, which "Company - Promoseven - P7H Aug 2026" (35) blows
     * past, so the company name is just whatever text precedes the known
     * MP\d/P7H/Plat shape marker. Tabs with no such leading text (the
     * original "P7H Aug 2026" style) fall back to ['individual', null].
     *
     * @return array{0: string, 1: ?string} [tenant_type, company_name]
     */
    private function pdbCompanyFromSheetName(string $sheetName): array
    {
        $trimmed = trim($sheetName);

        // New convention: "Company_<Name>_P7H_Aug2026" — the segment right
        // after "Company_" is the company name, whatever building/month follows.
        if (preg_match('/^Company_([^_]+)_/i', $trimmed, $m)) {
            return ['company', trim($m[1])];
        }

        // Older convention: "<Name> - P7H Aug 2026".
        if (preg_match('/^(.+?)\s*-\s*(?:MP\d|P7H|Plat)/i', $trimmed, $m)) {
            return ['company', trim($m[1])];
        }

        return ['individual', null];
    }

    /**
     * The uploaded file's own name is checked first (so one company owning
     * multiple buildings/sheets in one file doesn't require renaming every
     * sheet tab), falling back to the sheet tab name. Used to tag both the
     * buildings and the tenants created from a PDB workbook.
     */
    private function pdbResolveCompanyName(string $originalFilename, string $sheetName): ?string
    {
        [, $companyName] = $this->pdbCompanyFromSheetName($originalFilename);
        if ($companyName === null) {
            [, $companyName] = $this->pdbCompanyFromSheetName($sheetName);
        }
        return $companyName;
    }

    private function pdbUpsertTenantAndLease(
        string $tenantName,
        ?string $phone,
        ?string $email,
        Building $building,
        PropertyUnit $unit,
        ?float $rent,
        string $contractPeriod,
        string $sheetName,
        int $displayRow,
        array &$results,
        string $originalFilename = '',
        ?string $contactPerson = null,
    ): void {
        $tenant = Tenant::whereRaw('LOWER(name) = ?', [strtolower($tenantName)])->first();
        if (!$tenant) {
            // The uploaded file's own name can carry the company tag (e.g.
            // "Company_Promoseven_P7H_Aug2026.xlsx") so one company's tenants
            // across multiple buildings/sheets in the same file don't require
            // renaming every individual sheet tab — it's checked first, with
            // the sheet tab itself still usable as a per-sheet override.
            [$tenantType, $companyName] = $this->pdbCompanyFromSheetName($originalFilename);
            if ($tenantType === 'individual') {
                [$tenantType, $companyName] = $this->pdbCompanyFromSheetName($sheetName);
            }
            try {
                $tenant = Tenant::create(array_filter([
                    'name'           => $tenantName,
                    'tenant_type'    => $tenantType,
                    'company_name'   => $companyName,
                    'contact_person' => $contactPerson,
                    'phone'          => $phone ?: null,
                    'email'          => $email ?: null,
                ]));
                $results['tenants']['imported']++;
            } catch (\Exception $e) {
                $results['tenants']['errors'][] = "Sheet '{$sheetName}' row {$displayRow}: " . $e->getMessage();
                return;
            }
        }

        [$start, $end] = $this->pdbParseContractPeriod($contractPeriod);
        if (!$start || !$end) {
            $results['contracts']['errors'][] =
                "Sheet '{$sheetName}' row {$displayRow}: could not parse contract period '{$contractPeriod}' — lease not created.";
            return;
        }

        // whereDate() (not where()) since lease_start_date is stored as a
        // full datetime string ("2022-04-01 00:00:00") but $start here is a
        // bare date ("2022-04-01") — a plain equality check never matched,
        // so re-importing the same file kept creating duplicate leases.
        $exists = LeaseContract::where('unit_id', $unit->id)
            ->where('tenant_id', $tenant->id)
            ->whereDate('lease_start_date', $start)
            ->exists();
        if ($exists) {
            return;
        }

        try {
            LeaseContract::create([
                'date'               => $start,
                'lease_agreement_no' => LeaseContract::generateNumber(),
                'tenant_id'          => $tenant->id,
                'tenant_name'        => $tenant->name,
                'property_name'      => $building->property_name,
                'property_code'      => $building->property_code,
                'unit_id'            => $unit->id,
                'unit'               => $unit->unit_name,
                'lease_start_date'   => $start,
                'lease_end_date'     => $end,
                'rent_per_month'     => $rent,
                'currency'           => 'BHD',
            ]);
            $results['contracts']['imported']++;
        } catch (\Exception $e) {
            $results['contracts']['errors'][] = "Sheet '{$sheetName}' row {$displayRow}: " . $e->getMessage();
        }
    }

    private function pdbParseResidentialSheet(array $rows, string $propertyCode, string $sheetName, array &$results, string $originalFilename = ''): void
    {
        $building = Building::where('property_code', $propertyCode)->first();
        if (!$building) {
            $propertyName = "Miknas Plaza " . preg_replace('/^MP/i', '', $propertyCode);
            foreach ($rows as $row) {
                $cell = trim((string) ($row[0] ?? ''));
                if ($cell !== '' && preg_match('/^(.*?)\s*-\s*Data Base/i', $cell, $m)) {
                    $propertyName = trim($m[1]);
                    break;
                }
            }

            $building = Building::create([
                'property_name' => $propertyName,
                'property_code' => $propertyCode,
                'property_type' => 'Residential',
                'company_name'  => $this->pdbResolveCompanyName($originalFilename, $sheetName),
            ]);
            $results['buildings']['imported']++;
        }

        $headerRowIndex = $this->pdbFindHeaderRowIndex($rows, '/^flat\b/i');
        if ($headerRowIndex === null) {
            $results['units']['errors'][] = "Sheet '{$sheetName}': could not locate a 'Flat' header row — skipped.";
            return;
        }

        $colMap = $this->pdbMapHeaderRow($rows[$headerRowIndex], self::PDB_RESIDENTIAL_HEADER_ALIASES);
        if (!isset($colMap['flat'])) {
            $results['units']['errors'][] = "Sheet '{$sheetName}': no 'Flat' column found — skipped.";
            return;
        }

        $units = PropertyUnit::where('building_id', $building->id)->get()
            ->keyBy(fn($u) => strtolower(trim($u->unit_name)));
        $floors = Floor::where('building_id', $building->id)->get()
            ->keyBy(fn($f) => strtolower(trim($f->floor_name)));

        $rowCount = count($rows);
        for ($i = $headerRowIndex + 1; $i < $rowCount; $i++) {
            $row = $rows[$i];
            $get = fn(string $field) => isset($colMap[$field]) ? trim((string) ($row[$colMap[$field]] ?? '')) : '';
            $displayRow = $i + 1;

            $flatRaw = $get('flat');
            if ($flatRaw === '') {
                continue;
            }
            if (!preg_match('/^\d+$/', $flatRaw)) {
                $results['units']['errors'][] =
                    "Sheet '{$sheetName}' row {$displayRow}: flat value '{$flatRaw}' is not a plain flat number — skipped.";
                continue;
            }

            $name     = $get('name');
            $isVacant = $this->pdbResolveVacancy($get('status'), $name, $sheetName, $displayRow, $results);

            $unit = $units[strtolower(trim("{$propertyCode} - {$flatRaw}"))]
                ?? $units[strtolower(trim("{$propertyCode} - S{$flatRaw}"))]
                ?? null;

            $unitData = [];
            if ($get('br') !== '') {
                $unitData['unit_type'] = $get('br');
            }
            $rent = $this->pdbToDecimal($get('rent'));
            if ($rent !== null) {
                $unitData['rent_per_month'] = $rent;
            }
            $deposit = $this->pdbToDecimal($get('security_deposit'));
            if ($deposit !== null) {
                $unitData['security_deposit_amount'] = $deposit;
            }

            if ($unit) {
                if (!empty($unitData)) {
                    $unit->fill($unitData);
                    if ($unit->isDirty()) {
                        $unit->save();
                        $results['units']['imported']++;
                    }
                }
            } else {
                $isStudio   = stripos($get('br'), 'studio') !== false;
                $floorNum   = (int) (strlen($flatRaw) > 1 ? substr($flatRaw, 0, -1) : $flatRaw);
                $floor      = $floors['floor ' . $floorNum] ?? null;
                $unitData['building_id']   = $building->id;
                $unitData['floor_id']      = $floor?->id;
                $unitData['property_name'] = $building->property_name;
                $unitData['property_code'] = $building->property_code;
                $unitData['unit_name']     = $isStudio ? "{$propertyCode} - S{$flatRaw}" : "{$propertyCode} - {$flatRaw}";

                try {
                    $unit = PropertyUnit::create($unitData);
                    $units[strtolower($unit->unit_name)] = $unit;
                    $results['units']['imported']++;
                } catch (\Exception $e) {
                    $results['units']['errors'][] = "Sheet '{$sheetName}' row {$displayRow}: " . $e->getMessage();
                    continue;
                }
            }

            if ($isVacant) {
                continue;
            }

            $contractPeriod = $this->pdbCompleteContractPeriod(
                $rows, $rowCount, $i, $get('contract_period'), $colMap['flat'] ?? null, $colMap['contract_period'] ?? null,
            );

            $this->pdbUpsertTenantAndLease(
                $name, $get('contact'), $get('email'), $building, $unit,
                $rent, $contractPeriod, $sheetName, $displayRow, $results, $originalFilename,
            );
        }
    }

    private function pdbNormalizeBuildingFloorHeader(string $text): ?array
    {
        if (!preg_match('/1130\s*([mn])/i', $text, $m)) {
            return null;
        }
        $code      = '1130' . strtoupper($m[1]);
        $floorName = trim(preg_replace('/^.*-\s*/', '', $text));
        $floorName = preg_replace('/\s+/', ' ', $floorName);
        return [$code, $floorName !== '' ? $floorName : 'Ground Floor'];
    }

    private function pdbParseCommercialSheet(array $rows, string $sheetName, array &$results, string $originalFilename = ''): void
    {
        $buildingCache = [];
        $floorCache    = [];
        $colMap        = [];
        $building      = null;
        $floor         = null;

        $rowCount = count($rows);
        for ($i = 0; $i < $rowCount; $i++) {
            $row        = $rows[$i];
            $displayRow = $i + 1;
            $firstCell  = trim((string) ($row[0] ?? ''));

            if ($firstCell !== '' && preg_match('/building|bldg/i', $firstCell)) {
                $parsed = $this->pdbNormalizeBuildingFloorHeader($firstCell);
                if (!$parsed) {
                    continue;
                }
                [$code, $floorName] = $parsed;

                if (!isset($buildingCache[$code])) {
                    $propertyCode = "P7H-{$code}";
                    $b = Building::where('property_code', $propertyCode)->first();
                    if (!$b) {
                        $b = Building::create([
                            'property_name' => "P7H Building {$code}",
                            'property_code' => $propertyCode,
                            'property_type' => 'Commercial',
                            'company_name'  => $this->pdbResolveCompanyName($originalFilename, $sheetName),
                        ]);
                        $results['buildings']['imported']++;
                    }
                    $buildingCache[$code] = $b;
                }
                $building = $buildingCache[$code];

                $floorKey = $code . '|' . strtolower($floorName);
                if (!isset($floorCache[$floorKey])) {
                    $f = Floor::where('building_id', $building->id)->where('floor_name', $floorName)->first();
                    if (!$f) {
                        $f = Floor::create([
                            'building_id' => $building->id,
                            'floor_name'  => $floorName,
                        ]);
                        $results['floors']['imported']++;
                    }
                    $floorCache[$floorKey] = $f;
                }
                $floor = $floorCache[$floorKey];

                // A header row normally follows immediately — but some
                // sections (e.g. a floor that continues the same table layout
                // as the section above it) have none, going straight to data.
                // Only consume the next row as a header if it actually maps a
                // shop/office identifier column; otherwise keep the current
                // colMap (carried over from the previous section) and let
                // that row be read as a normal data row on the next pass.
                if (isset($rows[$i + 1])) {
                    $candidateMap = $this->pdbMapHeaderRow($rows[$i + 1], self::PDB_COMMERCIAL_HEADER_ALIASES);
                    if (isset($candidateMap['shop'])) {
                        $colMap = $this->pdbDetectEwaShareColumn($rows[$i + 1], $candidateMap);
                        $i++;
                    }
                }
                continue;
            }

            if ($building === null || empty($colMap) || !isset($colMap['shop'])) {
                continue;
            }

            // A "Total ... Amount" row marks the end of this section's real
            // data — everything below it (legend notes, "Registered Office",
            // running totals) isn't a unit. Clearing colMap here means every
            // row until the next building/floor header gets skipped above.
            foreach ($row as $cell) {
                if (is_string($cell) && preg_match('/^total\b/i', trim($cell))) {
                    $colMap = [];
                    continue 2;
                }
            }

            $get     = fn(string $field) => isset($colMap[$field]) ? trim((string) ($row[$colMap[$field]] ?? '')) : '';
            $shopRaw = $get('shop');
            if ($shopRaw === '' || stripos($shopRaw, 'total') !== false || $this->pdbIsNonRentableSpace($shopRaw)) {
                continue;
            }

            $company  = $get('company');
            $isVacant = $this->pdbResolveVacancy($get('occupancy'), $company, $sheetName, $displayRow, $results);
            $unitName = trim($building->property_code . ' - ' . $shopRaw);

            $unit = PropertyUnit::where('building_id', $building->id)
                ->whereRaw('LOWER(unit_name) = ?', [strtolower($unitName)])
                ->first();

            $rent     = $this->pdbToDecimal($get('rent'));
            $size     = $this->pdbToDecimal($get('size'));
            $ewaShare = $this->pdbToDecimal(str_replace('%', '', $get('ewa_share_percent')));

            $unitData = array_filter([
                'rent_per_month'    => $rent,
                'area_inside'       => $size,
                'ewa_share_percent' => $ewaShare,
            ], fn($v) => $v !== null);

            if ($unit) {
                $unit->fill($unitData);
                if ($unit->isDirty()) {
                    $unit->save();
                    $results['units']['imported']++;
                }
            } else {
                $unitData = array_merge($unitData, [
                    'building_id'   => $building->id,
                    'floor_id'      => $floor?->id,
                    'property_name' => $building->property_name,
                    'property_code' => $building->property_code,
                    'unit_name'     => $unitName,
                    'unit_type'     => 'Commercial',
                ]);
                try {
                    $unit = PropertyUnit::create($unitData);
                    $results['units']['imported']++;
                } catch (\Exception $e) {
                    $results['units']['errors'][] = "Sheet '{$sheetName}' row {$displayRow}: " . $e->getMessage();
                    continue;
                }
            }

            if ($isVacant) {
                continue;
            }

            $contractPeriod = $this->pdbCompleteContractPeriod(
                $rows, $rowCount, $i, $get('contract_period'), $colMap['shop'] ?? null, $colMap['contract_period'] ?? null,
            );

            // Newer files split contact info into its own Contact Name / Phone /
            // Email columns; older ones cram it all into one "Contact Details"
            // column spread across continuation rows — support both.
            if (isset($colMap['contact_name']) || isset($colMap['phone']) || isset($colMap['email'])) {
                $contactDetails = [
                    'contact_person' => $get('contact_name') ?: null,
                    'phone'          => $get('phone') ?: null,
                    'email'          => $get('email') ?: null,
                ];
            } else {
                $contactDetails = $this->pdbCompleteContactDetails(
                    $rows, $rowCount, $i, $get('contact'), $colMap['shop'] ?? null, $colMap['contact'] ?? null,
                );
            }

            $this->pdbUpsertTenantAndLease(
                $company, $contactDetails['phone'], $contactDetails['email'], $building, $unit,
                $rent, $contractPeriod, $sheetName, $displayRow, $results, $originalFilename,
                $contactDetails['contact_person'],
            );
        }
    }

    private function pdbParsePlatinumTowerSheet(array $rows, string $sheetName, array &$results, string $originalFilename = ''): void
    {
        $propertyCode = 'PLATINUM-TOWER';
        $building     = Building::where('property_code', $propertyCode)->first();
        if (!$building) {
            $building = Building::create([
                'property_name' => 'Platinum Tower',
                'property_code' => $propertyCode,
                'property_type' => 'Commercial',
                'company_name'  => $this->pdbResolveCompanyName($originalFilename, $sheetName),
            ]);
            $results['buildings']['imported']++;
        }

        $floor = Floor::where('building_id', $building->id)->where('floor_name', 'Offices')->first();
        if (!$floor) {
            $floor = Floor::create(['building_id' => $building->id, 'floor_name' => 'Offices']);
            $results['floors']['imported']++;
        }

        $headerRowIndex = $this->pdbFindHeaderRowIndex($rows, '/^office no/i');
        if ($headerRowIndex === null) {
            $results['units']['errors'][] = "Sheet '{$sheetName}': could not locate an 'Office No' header row — skipped.";
            return;
        }

        $colMap = $this->pdbMapHeaderRow($rows[$headerRowIndex], self::PDB_PLATINUM_HEADER_ALIASES);
        if (!isset($colMap['office'])) {
            $results['units']['errors'][] = "Sheet '{$sheetName}': no 'Office No' column found — skipped.";
            return;
        }

        $rowCount = count($rows);
        for ($i = $headerRowIndex + 1; $i < $rowCount; $i++) {
            $row        = $rows[$i];
            $displayRow = $i + 1;
            $get        = fn(string $field) => isset($colMap[$field]) ? trim((string) ($row[$colMap[$field]] ?? '')) : '';

            $officeRaw = $get('office');
            if ($officeRaw === '') {
                continue;
            }

            $unitName = trim("Platinum Tower - Office {$officeRaw}");
            $unit     = PropertyUnit::where('building_id', $building->id)
                ->whereRaw('LOWER(unit_name) = ?', [strtolower($unitName)])
                ->first();

            $rent = $this->pdbToDecimal($get('rent'));
            $unitData = array_filter([
                'rent_per_month'     => $rent,
                'rate_per_area_unit' => $this->pdbToDecimal($get('rate')),
                'area_inside'        => $this->pdbToDecimal($get('per_plan_sqm')) ?? $this->pdbToDecimal($get('title_deed_sqm')),
            ], fn($v) => $v !== null);

            if ($unit) {
                $unit->fill($unitData);
                if ($unit->isDirty()) {
                    $unit->save();
                    $results['units']['imported']++;
                }
                continue;
            }

            $unitData = array_merge($unitData, [
                'building_id'   => $building->id,
                'floor_id'      => $floor->id,
                'property_name' => $building->property_name,
                'property_code' => $building->property_code,
                'unit_name'     => $unitName,
                'unit_type'     => 'Office',
                'description'   => $get('remarks') ?: null,
            ]);

            try {
                PropertyUnit::create($unitData);
                $results['units']['imported']++;
            } catch (\Exception $e) {
                $results['units']['errors'][] = "Sheet '{$sheetName}' row {$displayRow}: " . $e->getMessage();
            }
        }
    }

    /**
     * Fuzzy fallback for the unit-identifier column when a header doesn't
     * exact-match anything in UNIT_LABELS/CONTRACT_LABELS (e.g. "Flat No",
     * "Apt #", "Suite Number"). Only ever consulted as a fallback after the
     * exact alias lookup fails, and only recognizes this one column — it
     * deliberately does not try to generically fuzzy-match every field,
     * since a looser net risks misreading unrelated columns (see the
     * "Total No. of Units" false-positive guarded against in the tests).
     *
     * Returns 'unit' (the same target 'Unit'/'Unit Name' already resolve to
     * via CONTRACT_LABELS) so it flows through the exact same downstream
     * unit_name-from-unit fallback that smartImportGeneric() already has.
     */
    private function fuzzyUnitIdentifierAlias(string $header): ?string
    {
        $normalized = strtolower(trim($header));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
        $tokens     = array_filter(explode(' ', trim($normalized)));

        $unitWords = ['flat', 'unit', 'apt', 'apartment', 'suite'];
        $noWords   = ['no', 'number', 'num'];

        $hasUnitWord = (bool) array_intersect($tokens, $unitWords);
        $hasNoWord   = (bool) array_intersect($tokens, $noWords);

        if ($hasUnitWord && ($hasNoWord || count($tokens) === 1)) {
            return 'unit';
        }

        return null;
    }

    private function detectEntities(array $headers): array
    {
        $weights = [
            'lease_agreement_no'         => ['contracts' => 15],
            'lease_start_date'           => ['contracts' => 8],
            'lease_end_date'             => ['contracts' => 8],
            'invoicing_frequency'        => ['contracts' => 6],
            'rent_start_date'            => ['contracts' => 5],
            'rental_income_ledger'       => ['contracts' => 5],
            'lease_break_date'           => ['contracts' => 5],
            'service_amount_bd_excl_vat' => ['contracts' => 5],
            'unit_name'                  => ['units' => 15],
            'unit_type'                  => ['units' => 8],
            'unit_condition'             => ['units' => 8],
            'area_inside'                => ['units' => 6],
            'electricity_meter_no'       => ['units' => 8],
            'water_meter_no'             => ['units' => 8],
            'floor_name'                 => ['floors' => 12],
            'floor_code'                 => ['floors' => 8],
            'block_name'                 => ['floors' => 4, 'buildings' => 1],
            'property_name'              => ['buildings' => 10],
            'type_of_ownership'          => ['buildings' => 10],
            'land_lord_name'             => ['buildings' => 8],
            'total_no_of_floors'         => ['buildings' => 8],
            'total_no_of_blocks'         => ['buildings' => 8],
            'name'                       => ['tenants' => 10],
            'tenant_type'                => ['tenants' => 8],
            'id_cr_number'               => ['tenants' => 8],
            'nationality_country'        => ['tenants' => 8],
        ];

        $scores = ['contracts' => 0, 'units' => 0, 'floors' => 0, 'buildings' => 0, 'tenants' => 0];

        foreach ($headers as $h) {
            if (isset($weights[$h])) {
                foreach ($weights[$h] as $entity => $pts) {
                    $scores[$entity] += $pts;
                }
            }
        }

        if ($scores['contracts'] >= 15) {
            return ['contracts'];
        }

        $detected = [];
        arsort($scores);
        foreach ($scores as $entity => $score) {
            if ($score >= 10) {
                $detected[] = $entity;
            }
        }

        return $detected;
    }

    private function smartImportContractsWithTenants(array $dataRows, array $headers): array
    {
        $headerCount = count($headers);

        $tenantMap = Tenant::pluck('id', 'name')
            ->mapWithKeys(fn($id, $n) => [strtolower(trim($n)) => $id])
            ->all();

        $unitMap = PropertyUnit::whereNotNull('unit_name')->pluck('id', 'unit_name')
            ->mapWithKeys(fn($id, $n) => [strtolower(trim($n)) => $id])
            ->all();

        $tImported = 0; $tErrors = [];
        $cImported = 0; $cErrors = [];
        $row = 1;

        foreach ($dataRows as $raw) {
            $row++;
            $cells = array_map(fn($v) => trim((string) ($v ?? '')), $raw);
            if (array_filter($cells) === []) continue;

            $values = array_slice(array_pad($cells, $headerCount, null), 0, $headerCount);
            $record = array_combine($headers, $values);

            // Extract & upsert tenant from this row
            $tenantName = trim($record['tenant_name'] ?? $record['name'] ?? '');
            if ($tenantName !== '') {
                $tenantKey = strtolower($tenantName);
                if (!isset($tenantMap[$tenantKey])) {
                    $td = ['name' => $tenantName, 'tenant_type' => 'individual'];
                    foreach (['tenant_type', 'id_cr_number', 'phone', 'email', 'nationality_country'] as $f) {
                        if (!empty($record[$f])) $td[$f] = $record[$f];
                    }
                    if (!empty($td['tenant_type'])) {
                        $t = strtolower($td['tenant_type']);
                        $td['tenant_type'] = in_array($t, ['individual', 'company']) ? $t : 'individual';
                    }
                    try {
                        $tenant = Tenant::create($td);
                        $tenantMap[$tenantKey] = $tenant->id;
                        $tImported++;
                    } catch (\Exception $e) {
                        $tErrors[] = "Row {$row} (tenant): " . $e->getMessage();
                    }
                }
            }

            // Import contract
            $agreementNo = trim($record['lease_agreement_no'] ?? '');
            if (!$agreementNo) {
                $cErrors[] = "Row {$row}: 'Lease Agreement No' is required — skipped.";
                continue;
            }
            if (LeaseContract::where('lease_agreement_no', $agreementNo)->exists()) {
                $cErrors[] = "Row {$row}: Agreement No '{$agreementNo}' already exists — skipped.";
                continue;
            }

            $data = $this->onlyFillable($record, self::CONTRACT_COLUMNS);

            foreach (['date', 'lease_start_date', 'lease_end_date', 'lease_break_date',
                      'rent_start_date', 'rent_end_date', 'service_start_date', 'service_end_date'] as $df) {
                if (!empty($data[$df])) $data[$df] = $this->parseDate($data[$df]);
            }

            $tenantKey = strtolower(trim($record['tenant_name'] ?? $record['name'] ?? ''));
            if ($tenantKey && isset($tenantMap[$tenantKey])) {
                $data['tenant_id'] = $tenantMap[$tenantKey];
            }

            $unitKey = strtolower(trim($record['unit'] ?? $record['unit_name'] ?? ''));
            if ($unitKey && isset($unitMap[$unitKey])) {
                $data['unit_id'] = $unitMap[$unitKey];
            }

            try {
                LeaseContract::create($data);
                $cImported++;
            } catch (\Exception $e) {
                $cErrors[] = "Row {$row} (contract): " . $e->getMessage();
            }
        }

        return [$tImported, $tErrors, $cImported, $cErrors];
    }

    private function smartImportGeneric(string $entity, array $dataRows, array $headers): array
    {
        $headerCount = count($headers);
        $imported = 0;
        $errors   = [];

        [$required, $transform, $persist] = match ($entity) {

            'buildings' => (function () {
                $seenCodes = [];
                return [
                    ['property_name', 'property_code'],
                    function (array $r, int $_row) use (&$seenCodes): array {
                        $code = strtoupper(trim($r['property_code']));
                        if (isset($seenCodes[$code]))
                            return ['skip' => true];  // silent within-batch duplicate
                        if (Building::where('property_code', $code)->exists()) {
                            $seenCodes[$code] = true;
                            return ['error' => "Property Code '{$code}' already exists — skipped."];
                        }
                        $seenCodes[$code] = true;
                        $r['property_code'] = $code;
                        return ['data' => $this->onlyFillable($r, self::BUILDING_COLUMNS)];
                    },
                    fn($d) => Building::create($d),
                ];
            })(),

            'floors' => (function () {
                $seen = [];
                return [
                    ['property_code', 'floor_name'],
                    function (array $r, int $row) use (&$seen): array {
                        $code     = strtoupper(trim($r['property_code']));
                        $building = Building::where('property_code', $code)->first();
                        if (!$building)
                            return ['error' => "Row {$row}: Property Code '{$code}' not found — skipped."];
                        $data = $this->onlyFillable($r, self::FLOOR_COLUMNS, exclude: ['property_code']);
                        $data['building_id'] = $building->id;
                        $key = $building->id . '|' . strtolower($data['floor_name']);
                        if (isset($seen[$key]))
                            return ['skip' => true];  // silent within-batch duplicate
                        if (Floor::where('building_id', $building->id)->where('floor_name', $data['floor_name'])->exists()) {
                            $seen[$key] = true;
                            return ['error' => "Floor '{$data['floor_name']}' already exists for '{$code}' — skipped."];
                        }
                        $seen[$key] = true;
                        return ['data' => $data];
                    },
                    fn($d) => Floor::create($d),
                ];
            })(),

            'units' => (function () {
                $floorMap = [];
                Floor::whereNotNull('floor_code')->get(['id', 'building_id', 'floor_code'])->each(function ($f) use (&$floorMap) {
                    $floorMap[$f->building_id][strtoupper($f->floor_code)] = $f->id;
                });

                return [
                    [],
                    function (array $r, int $row) use ($floorMap): array {
                        // Accept 'unit' (from combined label map) as alias for 'unit_name'
                        if (empty($r['unit_name']) && !empty($r['unit'])) {
                            $r['unit_name'] = $r['unit'];
                        }
                        if (empty($r['unit_name'])) {
                            return ['error' => "Row {$row}: 'unit_name' is required — skipped."];
                        }
                        $bCode    = strtoupper(trim($r['property_code'] ?? ''));
                        $building = $bCode ? Building::where('property_code', $bCode)->first() : null;
                        $data = $this->onlyFillable($r, self::UNIT_COLUMNS, exclude: ['property_code', 'floor_code']);
                        if ($building) {
                            $data['building_id']       = $building->id;
                            $data['property_code']     = $building->property_code;
                            $data['property_name']     ??= $building->property_name;
                            $data['type_of_ownership'] ??= $building->type_of_ownership;
                            $data['property_type']     ??= $building->property_type;
                            $data['land_lord_name']    ??= $building->land_lord_name;

                            $fCode = strtoupper(trim($r['floor_code'] ?? ''));
                            if ($fCode && isset($floorMap[$building->id][$fCode])) {
                                $data['floor_id'] = $floorMap[$building->id][$fCode];
                            }
                        }
                        return ['data' => $data];
                    },
                    fn($d) => PropertyUnit::create($d),
                ];
            })(),

            'tenants' => [
                [],
                function (array $r, int $row): array {
                    $name = trim($r['name'] ?? $r['tenant_name'] ?? '');
                    if (!$name) return ['error' => "Row {$row}: Name is required — skipped."];
                    $r['name'] = $name;
                    if (Tenant::whereRaw('LOWER(name) = ?', [strtolower($name)])->exists())
                        return ['error' => "Row {$row}: Tenant '{$name}' already exists — skipped."];
                    $data = $this->onlyFillable($r, self::TENANT_COLUMNS);
                    $data['name'] = $name;
                    $t = strtolower($data['tenant_type'] ?? '');
                    $data['tenant_type'] = in_array($t, ['individual', 'company']) ? $t : 'individual';
                    return ['data' => $data];
                },
                fn($d) => Tenant::create($d),
            ],

            default => [[], fn($_r, $_row) => ['error' => 'Unknown entity.'], fn($_d) => null],
        };

        // Pre-flight: check required columns are present in headers
        $missing = array_diff($required, $headers);
        if (!empty($missing)) {
            return ['imported' => 0, 'errors' => ['Missing required columns: ' . implode(', ', $missing)]];
        }

        $row = 1;
        foreach ($dataRows as $raw) {
            $row++;
            $cells = array_map(fn($v) => trim((string) ($v ?? '')), $raw);
            if (array_filter($cells) === []) continue;

            $values = array_slice(array_pad($cells, $headerCount, null), 0, $headerCount);
            $record = array_combine($headers, $values);

            foreach ($required as $col) {
                if (empty($record[$col] ?? null)) {
                    $errors[] = "Row {$row}: '{$col}' is required — skipped.";
                    continue 2;
                }
            }

            $result = $transform($record, $row);
            if (isset($result['skip'])) { continue; }
            if (isset($result['error'])) { $errors[] = $result['error']; continue; }

            try {
                $persist($result['data']);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: " . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    private function parseDate(mixed $value): ?string
    {
        if (is_numeric($value)) {
            // Excel serial date
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Try common formats
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $value);
            if ($dt && $dt->format($fmt) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        // Fall back to strtotime
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    // ── INTERNALS ─────────────────────────────────────────────────────────────

    /**
     * Parse a CSV or XLSX file. $labelMap is label→field; headers in the file
     * may be either human-readable labels or raw field names — both are accepted.
     */
    private function parseFile(
        \Illuminate\Http\UploadedFile $file,
        array $labelMap,
        array $requiredFields,
        callable $transform,
        callable $persist,
    ): array {
        $ext  = strtolower($file->getClientOriginalExtension());
        $rows = in_array($ext, ['xlsx', 'xls'])
            ? $this->readXlsx($file)
            : $this->readCsv($file);

        if ($rows === null) {
            return [0, ['File appears empty or could not be read.']];
        }

        $rawHeaders  = array_shift($rows);
        $headerCount = count($rawHeaders);

        // Build lowercase-label → field lookup (e.g. "property name" → "property_name")
        $fieldByLabel = [];
        foreach ($labelMap as $label => $field) {
            $fieldByLabel[strtolower($label)] = $field;
        }
        $allFields = array_values($labelMap);

        // Normalise each header: accept human-readable label OR raw field name
        $headers = array_map(function ($h) use ($fieldByLabel, $allFields) {
            $h    = trim((string) $h);
            $hLow = strtolower($h);
            return $fieldByLabel[$hLow]                       // matched a human-readable label
                ?? (in_array($h, $allFields) ? $h : $h);     // already a field name (or unknown — pass through)
        }, $rawHeaders);

        $missing = array_diff($requiredFields, $headers);
        if ($missing) {
            $humanMissing = array_map(
                fn($f) => array_search($f, $labelMap) ?: $f,
                $missing
            );
            $foundLabels = array_map(
                fn($f) => array_search($f, $labelMap) ?: $f,
                $rawHeaders
            );
            return [0, ['Missing required columns: ' . implode(', ', $humanMissing) . '. Columns found in your file: ' . implode(', ', $foundLabels) . '.']];
        }

        $imported = 0;
        $errors   = [];
        $row      = 1;

        foreach ($rows as $raw) {
            $row++;
            $cells = array_map(fn($v) => trim((string) ($v ?? '')), $raw);
            if (array_filter($cells) === []) {
                continue;
            }

            $values = array_slice(array_pad($cells, $headerCount, null), 0, $headerCount);
            $record = array_combine($headers, $values);

            $rowError = null;
            foreach ($requiredFields as $col) {
                if (empty($record[$col] ?? null)) {
                    $label    = array_search($col, $labelMap) ?: $col;
                    $rowError = "Row {$row}: '{$label}' is required — skipped.";
                    break;
                }
            }
            if ($rowError) {
                $errors[] = $rowError;
                continue;
            }

            $result = $transform($record, $row);
            if (isset($result['error'])) {
                $errors[] = $result['error'];
                continue;
            }

            try {
                $persist($result['data']);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: " . $e->getMessage();
            }
        }

        return [$imported, $errors];
    }

    private function readCsv(\Illuminate\Http\UploadedFile $file): ?array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $rows   = [];
        $first  = true;
        while (($raw = fgetcsv($handle)) !== false) {
            $raw = array_map('trim', $raw);
            if ($first) {
                // Strip UTF-8 BOM (\xEF\xBB\xBF) that Excel adds to the first cell
                $raw[0] = ltrim($raw[0], "\xEF\xBB\xBF");
                $first  = false;
            }
            $rows[] = $raw;
        }
        fclose($handle);
        return $rows ?: null;
    }

    private function readXlsx(\Illuminate\Http\UploadedFile $file): ?array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        return $rows ?: null;
    }

    private function onlyFillable(array $record, array $columns, array $exclude = []): array
    {
        $allowed = array_diff($columns, $exclude);
        $result  = [];
        foreach ($allowed as $col) {
            if (isset($record[$col]) && $record[$col] !== '') {
                $result[$col] = $record[$col];
            }
        }
        return $result;
    }
}
