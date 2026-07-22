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
        $filters = $request->only(['search', 'property_type', 'type_of_ownership']);
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
        $filters = $request->only(['search', 'tenant_type']);
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

        $ext     = strtolower($request->file('file')->getClientOriginalExtension());
        $allRows = in_array($ext, ['xlsx', 'xls'])
            ? $this->readXlsx($request->file('file'))
            : $this->readCsv($request->file('file'));

        if (!$allRows || count($allRows) < 1) {
            return redirect()->route('data.index')
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
            return $fieldByLabel[strtolower($h)] ?? $h;
        }, $rawHeaders);

        $detected = $this->detectEntities($headers);

        if (empty($detected)) {
            return redirect()->route('data.index')
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

        return redirect()->route('data.index')->with('smart_import_results', $results);
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
