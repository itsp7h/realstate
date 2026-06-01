<?php

namespace App\Http\Controllers;

use App\Exports\UnifiedExport;
use App\Models\Building;
use App\Models\Floor;
use App\Models\PropertyUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataController extends Controller
{
    // ── COLUMN DEFINITIONS ───────────────────────────────────────────────────

    /**
     * All template columns in display order, label → internal key.
     * Internal keys are the actual DB field names, except 'floor_units'
     * which maps to floors.total_no_of_units to avoid clash with
     * buildings.total_no_of_units ('Total Units').
     */
    private const UNIFIED_LABELS = [
        // ── Building ──────────────────────────────────────────────────
        'Property Name'                  => 'property_name',
        'Property Code'                  => 'property_code',
        'Type of Ownership'              => 'type_of_ownership',
        'Property Type'                  => 'property_type',
        'Land Lord'                      => 'land_lord_name',
        'Building No.'                   => 'building_no',
        'Road'                           => 'road',
        'Block'                          => 'block',
        'Area'                           => 'area',
        'City'                           => 'city',
        'Total Blocks'                   => 'total_no_of_blocks',
        'Total Floors'                   => 'total_no_of_floors',
        'Total Units'                    => 'total_no_of_units',
        // ── Floor ─────────────────────────────────────────────────────
        'Floor Name'                     => 'floor_name',
        'Floor Code'                     => 'floor_code',
        'Block Name'                     => 'block_name',
        'Block Code'                     => 'block_code',
        'Floor Units'                    => 'floor_units',          // → floors.total_no_of_units
        // ── Unit ──────────────────────────────────────────────────────
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

    /**
     * Extra header aliases → internal key.
     * Takes priority over UNIFIED_LABELS during header normalisation.
     */
    private const HEADER_ALIASES = [
        'landlord'           => 'land_lord_name',
        'land lord name'     => 'land_lord_name',
        'landlord name'      => 'land_lord_name',
        'owner'              => 'land_lord_name',
        'property name'      => 'property_name',
        'property code'      => 'property_code',
        'prop code'          => 'property_code',
        'prop name'          => 'property_name',
        'building no'        => 'building_no',
        'building number'    => 'building_no',
        'floor no'           => 'floor_name',
        'floor number'       => 'floor_name',
        'unit no'            => 'unit_name',
        'unit number'        => 'unit_name',
        'unit'               => 'unit_name',
        'rent'               => 'rent_per_month',
        'rent per month'     => 'rent_per_month',
        'monthly rent'       => 'rent_per_month',
        'area inside (sqft)' => 'area_inside',
        'area inside (sqm)'  => 'area_inside',
        'inside area'        => 'area_inside',
        'type'               => 'unit_type',
        'unit type'          => 'unit_type',
        'condition'          => 'unit_condition',
        'status'             => 'unit_condition',
    ];

    private const BUILDING_FIELDS = [
        'property_name', 'property_code', 'type_of_ownership', 'property_type',
        'land_lord_name', 'building_no', 'road', 'block', 'area', 'city',
        'total_no_of_blocks', 'total_no_of_floors', 'total_no_of_units',
    ];

    private const FLOOR_FIELDS = [
        'floor_name', 'floor_code', 'block_name', 'block_code',
        // floor_units → total_no_of_units handled separately
    ];

    private const UNIT_FIELDS = [
        'unit_name', 'description', 'unit_type', 'creation_date', 'unit_condition',
        'view', 'no_of_parkings_foc', 'area_unit', 'area_inside', 'area_terrace',
        'rate_per_area_unit', 'rent_per_month', 'security_deposit_amount',
        'municipality_nos', 'electricity_installation_date', 'electricity_meter_no',
        'water_installation_date', 'water_meter_no', 'electricity_ac_no',
    ];

    private const SAMPLE_ROW = [
        // Building
        'Sunrise Tower', 'SRT001', 'Freehold', 'Residential', 'John Smith',
        '12', 'King Fahad Road', '3', 'Al Olaya', 'Riyadh', '2', '15', '120',
        // Floor
        'Ground Floor', 'GF', 'Block A', 'BLA', '8',
        // Unit
        '101', '1-bedroom with city view', 'Apartment', '2024-01-15',
        'Furnished', 'City', '1', 'sqft', '850.00', '0.00',
        '120.00', '4500.00', '9000.00', 'MUN-12345',
        '2023-06-01', 'EL-001', '2023-06-01', 'WM-001', 'AC-001',
    ];

    // ── PAGES ─────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('data.index');
    }

    // ── TEMPLATE ──────────────────────────────────────────────────────────────

    public function template(string $format = 'xlsx'): StreamedResponse|BinaryFileResponse
    {
        $headers = array_keys(self::UNIFIED_LABELS);
        $sample  = self::SAMPLE_ROW;

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $sample) {
                $h = fopen('php://output', 'w');
                fputcsv($h, $headers);
                fputcsv($h, $sample);
                fclose($h);
            }, 'import-template.csv', ['Content-Type' => 'text/csv']);
        }

        return Excel::download(
            new class($headers, $sample) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithStyles,
                \Maatwebsite\Excel\Concerns\ShouldAutoSize,
                \Maatwebsite\Excel\Concerns\WithTitle
            {
                public function __construct(private array $cols, private array $row) {}
                public function title(): string { return 'Import Template'; }
                public function array(): array  { return [$this->cols, $this->row]; }
                public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array {
                    // Section colour bands on the header row
                    $sheet->getStyle('A1:M1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFBDD7EE'); // blue – building
                    $sheet->getStyle('N1:R1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFE2EFDA'); // green – floor
                    $sheet->getStyle('S1:AM1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFF2CC'); // yellow – unit
                    $sheet->getStyle('A1:AM1')->getFont()->setBold(true);
                    return [];
                }
            },
            'import-template.xlsx'
        );
    }

    // ── EXPORT ────────────────────────────────────────────────────────────────

    public function export(): BinaryFileResponse
    {
        return Excel::download(
            new UnifiedExport(),
            'real-estate-data-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // ── IMPORT ────────────────────────────────────────────────────────────────

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240']);

        $ext  = strtolower($request->file('file')->getClientOriginalExtension());
        $rows = in_array($ext, ['xlsx', 'xls'])
            ? $this->readXlsx($request->file('file'))
            : $this->readCsv($request->file('file'));

        if (!$rows) {
            return back()->with('import_error', 'File appears empty or could not be read.');
        }

        // Build lowercase-label → internal-key lookup (aliases take priority)
        $labelIndex = [];
        foreach (self::UNIFIED_LABELS as $label => $key) {
            $labelIndex[strtolower($label)] = $key;
        }
        foreach (self::HEADER_ALIASES as $alias => $key) {
            $labelIndex[$alias] = $key;
        }
        $allKeys = array_values(self::UNIFIED_LABELS);

        // Normalise header row → internal keys
        $rawHeaders  = array_shift($rows);
        $headerCount = count($rawHeaders);
        $headers     = array_map(function ($h) use ($labelIndex, $allKeys) {
            $h    = trim((string) $h);
            $norm = strtolower($h);
            return $labelIndex[$norm]
                ?? (in_array($h, $allKeys) ? $h : $h);
        }, $rawHeaders);

        // Pre-load existing buildings & floors for fast lookup
        $buildingMap = Building::pluck('id', 'property_code')
            ->mapWithKeys(fn($id, $c) => [strtoupper($c) => $id])->all();

        $floorMap = [];
        Floor::whereNotNull('floor_code')
            ->get(['id', 'building_id', 'floor_code'])
            ->each(fn($f) => $floorMap[$f->building_id][strtoupper($f->floor_code)] = $f->id);

        $counts = ['buildings' => 0, 'floors' => 0, 'units' => 0];
        $errors = [];
        $row    = 1;

        foreach ($rows as $raw) {
            $row++;
            $cells = array_map(fn($v) => trim((string) ($v ?? '')), $raw);
            if (array_filter($cells) === []) {
                continue;
            }

            $values = array_slice(array_pad($cells, $headerCount, null), 0, $headerCount);
            $r      = array_combine($headers, $values);

            $code       = strtoupper($r['property_code'] ?? '');
            $hasBuilding = !empty($r['property_name']) && !empty($code);
            $hasFloor    = !empty($r['floor_name'])    && !empty($code);
            $hasUnit     = !empty($r['unit_name'])     && !empty($code);

            if (!$hasBuilding && !$hasFloor && !$hasUnit) {
                $errors[] = "Row {$row}: nothing to import — no building name, floor name, or unit name found.";
                continue;
            }

            // ── 1. Building ──────────────────────────────────────────────────
            $buildingId = null;

            if ($hasBuilding) {
                if (isset($buildingMap[$code])) {
                    $buildingId = $buildingMap[$code];
                } else {
                    try {
                        $data = $this->pick($r, self::BUILDING_FIELDS);
                        $data['property_code'] = $code;
                        $building   = Building::create($data);
                        $buildingId = $building->id;
                        $buildingMap[$code] = $buildingId;
                        $counts['buildings']++;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$row} (building): " . $e->getMessage();
                    }
                }
            } elseif (!empty($code)) {
                $buildingId = $buildingMap[$code] ?? null;
                if (!$buildingId) {
                    $errors[] = "Row {$row}: Property Code '{$code}' not found — floor/unit skipped.";
                    continue;
                }
            }

            // ── 2. Floor ─────────────────────────────────────────────────────
            $floorId = null;

            if ($hasFloor && $buildingId) {
                $fCode = strtoupper($r['floor_code'] ?? '');
                // Reuse already-imported floor from this session
                if ($fCode && isset($floorMap[$buildingId][$fCode])) {
                    $floorId = $floorMap[$buildingId][$fCode];
                } else {
                    try {
                        $data = $this->pick($r, self::FLOOR_FIELDS);
                        if (!empty($r['floor_units'])) {
                            $data['total_no_of_units'] = $r['floor_units'];
                        }
                        $data['building_id'] = $buildingId;
                        [$floor, $created] = [
                            Floor::firstOrCreate(
                                ['building_id' => $buildingId, 'floor_name' => $data['floor_name']],
                                $data
                            ),
                            false,
                        ];
                        $created = $floor->wasRecentlyCreated;
                        $floorId = $floor->id;

                        if ($fCode) {
                            $floorMap[$buildingId][$fCode] = $floorId;
                        }
                        // Also key by name so rows without floor_code still deduplicate
                        $floorMap[$buildingId]['name:' . strtolower($data['floor_name'])] = $floorId;
                        if ($created) {
                            $counts['floors']++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Row {$row} (floor): " . $e->getMessage();
                    }
                }
            } elseif ($buildingId) {
                // Resolve an existing floor by code
                $fCode     = strtoupper($r['floor_code'] ?? '');
                $fNameKey  = 'name:' . strtolower($r['floor_name'] ?? '');
                if ($fCode && isset($floorMap[$buildingId][$fCode])) {
                    $floorId = $floorMap[$buildingId][$fCode];
                } elseif (isset($floorMap[$buildingId][$fNameKey])) {
                    $floorId = $floorMap[$buildingId][$fNameKey];
                } else {
                    // Fall back to DB lookup
                    if ($fCode) {
                        $floor = Floor::where('building_id', $buildingId)->where('floor_code', $fCode)->first();
                    } elseif (!empty($r['floor_name'])) {
                        $floor = Floor::where('building_id', $buildingId)->where('floor_name', $r['floor_name'])->first();
                    } else {
                        $floor = null;
                    }
                    if ($floor) {
                        $floorId = $floor->id;
                        if ($fCode) $floorMap[$buildingId][$fCode] = $floorId;
                        $floorMap[$buildingId][$fNameKey] = $floorId;
                    }
                }
            }

            // ── 3. Unit ──────────────────────────────────────────────────────
            if ($hasUnit && $buildingId) {
                try {
                    $building = Building::find($buildingId);
                    $data     = $this->pick($r, self::UNIT_FIELDS);

                    $data['building_id']       = $buildingId;
                    $data['floor_id']          = $floorId;
                    $data['property_code']     = $building->property_code;
                    $data['property_name']     = !empty($r['property_name'])     ? $r['property_name']     : $building->property_name;
                    $data['type_of_ownership'] = !empty($r['type_of_ownership']) ? $r['type_of_ownership'] : $building->type_of_ownership;
                    $data['property_type']     = !empty($r['property_type'])     ? $r['property_type']     : $building->property_type;
                    $data['land_lord_name']    = !empty($r['land_lord_name'])    ? $r['land_lord_name']    : $building->land_lord_name;

                    PropertyUnit::create($data);
                    $counts['units']++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$row} (unit): " . $e->getMessage();
                }
            }
        }

        // Sync actual floor/unit counts back onto each building and floor
        foreach (array_unique(array_values($buildingMap)) as $bid) {
            Building::where('id', $bid)->update([
                'total_no_of_floors' => Floor::where('building_id', $bid)->count(),
                'total_no_of_units'  => PropertyUnit::where('building_id', $bid)->count(),
            ]);
        }
        $touchedFloorIds = [];
        foreach ($floorMap as $bid => $map) {
            foreach ($map as $key => $fid) {
                $touchedFloorIds[$fid] = true;
            }
        }
        foreach (array_keys($touchedFloorIds) as $fid) {
            Floor::where('id', $fid)->update([
                'total_no_of_units' => PropertyUnit::where('floor_id', $fid)->count(),
            ]);
        }

        return back()->with([
            'import_counts' => $counts,
            'import_errors' => $errors,
        ]);
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    private function pick(array $record, array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            if (isset($record[$f]) && $record[$f] !== '') {
                $out[$f] = $record[$f];
            }
        }
        return $out;
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
        $rows = IOFactory::load($file->getRealPath())
            ->getActiveSheet()
            ->toArray(null, true, true, false);
        return $rows ?: null;
    }
}
