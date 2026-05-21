<?php

namespace App\Http\Controllers;

use App\Exports\BuildingsExport;
use App\Exports\FloorsExport;
use App\Exports\UnitsExport;
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

    private const FLOOR_LABELS = [
        'Property Code'  => 'property_code',
        'Floor Name'     => 'floor_name',
        'Floor Code'     => 'floor_code',
        'Block Name'     => 'block_name',
        'Block Code'     => 'block_code',
        'Units'          => 'total_no_of_units',
    ];

    private const UNIT_LABELS = [
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
        'Date'                              => 'date',
        'Lease Agreement No'                => 'lease_agreement_no',
        'Tenant Name'                       => 'tenant_name',
        'Property Name'                     => 'property_name',
        'Prop Code'                         => 'property_code',
        'Block Name'                        => 'block_name',
        'Block Code'                        => 'block_code',
        'Floor Name'                        => 'floor_name',
        'Floor Code'                        => 'floor_code',
        'Unit'                              => 'unit',
        'Description'                       => 'description',
        'Lease Start Date'                  => 'lease_start_date',
        'Lease End Date'                    => 'lease_end_date',
        'Rental Income Ledger'              => 'rental_income_ledger',
        'Invoicing Frequency'               => 'invoicing_frequency',
        'Rent Start Date'                   => 'rent_start_date',
        'Rent End Date'                     => 'rent_end_date',
        'Currency'                          => 'currency',
        'Rent per Month'                    => 'rent_per_month',
        'Service Frequency'                 => 'service_frequency',
        'Service Start Date'                => 'service_start_date',
        'Service End Date'                  => 'service_end_date',
        'Service Amount in BD (Excl. VAT)'  => 'service_amount_bd_excl_vat',
        'Security Deposit'                  => 'security_deposit',
        'Lease Break Date'                  => 'lease_break_date',
        'Notice Period'                     => 'notice_period',
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

    // ── TEMPLATE DOWNLOADS ────────────────────────────────────────────────────

    public function template(string $type, string $format = 'csv'): StreamedResponse|BinaryFileResponse
    {
        [$labelMap, $sample] = match ($type) {
            'buildings' => [self::BUILDING_LABELS,  self::BUILDING_SAMPLE],
            'floors'    => [self::FLOOR_LABELS,      self::FLOOR_SAMPLE],
            'units'     => [self::UNIT_LABELS,       self::UNIT_SAMPLE],
            'contracts' => [self::CONTRACT_LABELS,   self::CONTRACT_SAMPLE],
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

        return redirect()->route('property-units.index')
            ->with(['import_type' => 'units', 'import_count' => $imported, 'import_errors' => $errors]);
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

        return redirect()->route('lease-contracts.index')
            ->with(['import_type' => 'contracts', 'import_count' => $imported, 'import_errors' => $errors]);
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
            // Also check whether any required field's label is present under a different case
            $humanMissing = array_map(
                fn($f) => array_search($f, $labelMap) ?: $f,
                $missing
            );
            return [0, ['Missing required columns: ' . implode(', ', $humanMissing) . '. Extra columns are fine and will be ignored.']];
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
        while (($raw = fgetcsv($handle)) !== false) {
            $rows[] = array_map('trim', $raw);
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
