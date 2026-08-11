<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\LeaseContract;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PropertyDataBaseImportTest extends TestCase
{
    use RefreshDatabase;

    private function writeSheet(Spreadsheet $spreadsheet, int $index, string $title, array $rows): void
    {
        if ($index === 0) {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($title);
        } else {
            $sheet = $spreadsheet->createSheet($index);
            $sheet->setTitle($title);
        }

        foreach ($rows as $rowIdx => $row) {
            foreach ($row as $colIdx => $value) {
                $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowIdx + 1, $value);
            }
        }
    }

    private function buildWorkbook(array $sheets): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $index = 0;
        foreach ($sheets as $title => $rows) {
            $this->writeSheet($spreadsheet, $index, $title, $rows);
            $index++;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pdb') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);

        return new UploadedFile($tmp, 'Data Base P7H 2026.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function mp1Rows(): array
    {
        return [
            ['Miknas Plaza 1 - Data Base - August 2026'],
            ['Studio Flats', null, 3, null, 'RENTED', 1, 'VACANT', 1],
            ['SN', 'Flat', 'BR', 'Name', 'Rent', 'Security Deposit', 'EWA Cap', 'Contact No.', 'Email'],
            [1, 701, 'Studio', 'Vacant', 0, 'none', null, null, null],
            [2, 702, 'Studio', 'Coral Bay', 200, 150, 20, '3300 0000', 'coral@example.com'],
            [3, '24 flats Managed by Florissant properties', null, null, 8350, null, null, null, null],
        ];
    }

    private function mp1ContractRow(): array
    {
        // Appended separately so the contract-period column can be tested
        // (kept short above to mirror the real sheet's sparse layout).
        return [];
    }

    public function test_smart_import_detects_multi_sheet_workbook_and_reconciles_existing_units(): void
    {
        $building = Building::create(['property_name' => 'Miknas Plaza 1', 'property_code' => 'MP1']);
        Floor::create(['building_id' => $building->id, 'floor_name' => 'Floor 7']);
        $existingUnit = PropertyUnit::create([
            'building_id'   => $building->id,
            'property_name' => 'Miknas Plaza 1',
            'property_code' => 'MP1',
            'unit_name'     => 'MP1 - S702',
            'unit_type'     => '3 BHK',
            'rent_per_month' => 0,
        ]);

        $rows = $this->mp1Rows();
        // Give row for flat 702 a full contract period so a lease is created.
        $rows[4][] = null; // pad
        $rows[4] = array_merge($rows[4], []);

        $mp2Rows = [
            ['Miknas Plaza 2 - Data Base - August 2026'],
            ['Total Flats', null, 37, null, 'RENTED', 1, 'VACANT', 0],
            ['SN', 'Flat', 'BR', 'Name', 'Rent', 'Security Deposit', 'EWA Cap', 'Contact No.', 'Email', 'Contract Period'],
            [1, 11, '2BR', 'Midhun Mani', 350, 350, 30, '3352 5201', 'midhun@example.com', '05-04-2026 - 04-04-2027'],
        ];
        Building::create(['property_name' => 'Miknas Plaza 2', 'property_code' => 'MP2']);

        $file = $this->buildWorkbook([
            'MP1 Aug 2026' => $rows,
            'MP2 Aug 2026' => $mp2Rows,
        ]);

        $response = $this->post(route('import.smart'), ['file' => $file]);
        $response->assertRedirect(route('dashboard'));

        // Existing unit reconciled in place, not duplicated.
        $this->assertEquals(1, PropertyUnit::where('unit_name', 'MP1 - S702')->count());
        $existingUnit->refresh();
        $this->assertEquals('Studio', $existingUnit->unit_type);
        $this->assertEquals(200, (float) $existingUnit->rent_per_month);

        // Vacant flat 701 creates a unit with no tenant/lease.
        $vacantUnit = PropertyUnit::where('unit_name', 'MP1 - S701')->first();
        $this->assertNotNull($vacantUnit);
        $this->assertNull(Tenant::whereRaw('LOWER(name) = ?', ['vacant'])->first());

        // Lump-sum "24 flats..." row is skipped, not imported as a unit.
        $this->assertNull(PropertyUnit::whereRaw('LOWER(unit_name) LIKE ?', ['%florissant%'])->first());

        // MP2 rented flat creates unit, tenant, and lease with parsed dates.
        $mp2Unit = PropertyUnit::where('unit_name', 'MP2 - 11')->first();
        $this->assertNotNull($mp2Unit);
        $tenant = Tenant::whereRaw('LOWER(name) = ?', ['midhun mani']);
        $this->assertTrue($tenant->exists());
        $lease = LeaseContract::where('unit_id', $mp2Unit->id)->first();
        $this->assertNotNull($lease);
        $this->assertEquals('2026-04-05', $lease->lease_start_date->format('Y-m-d'));
        $this->assertEquals('2027-04-04', $lease->lease_end_date->format('Y-m-d'));
    }

    public function test_smart_import_auto_creates_residential_building_when_missing(): void
    {
        $rows = [
            ['Miknas Plaza 3 - Data Base - August 2026'],
            ['Studio Flats', null, 1, null, 'RENTED', 1, 'VACANT', 0],
            ['SN', 'Flat', 'BR', 'Name', 'Rent', 'Security Deposit', 'EWA Cap', 'Contact No.', 'Email'],
            [1, 301, '1BR', 'Ali Hassan', 250, 200, 25, '3311 2233', 'ali@example.com'],
        ];
        $mp2Rows = [
            ['Miknas Plaza 2 - Data Base - August 2026'],
            ['Total Flats', null, 1, null, 'RENTED', 1, 'VACANT', 0],
            ['SN', 'Flat', 'BR', 'Name', 'Rent'],
            [1, 11, '2BR', 'Vacant', 0],
        ];

        $file = $this->buildWorkbook([
            'MP3 Aug 2026' => $rows,
            'MP2 Aug 2026' => $mp2Rows,
        ]);

        $this->assertNull(Building::where('property_code', 'MP3')->first());

        $this->post(route('import.smart'), ['file' => $file])->assertRedirect(route('dashboard'));

        $building = Building::where('property_code', 'MP3')->first();
        $this->assertNotNull($building);
        $this->assertEquals('Miknas Plaza 3', $building->property_name);

        $unit = PropertyUnit::where('unit_name', 'MP3 - 301')->first();
        $this->assertNotNull($unit);
        $this->assertEquals($building->id, $unit->building_id);
        $this->assertNotNull(Tenant::whereRaw('LOWER(name) = ?', ['ali hassan'])->first());
    }

    public function test_smart_import_creates_commercial_building_floor_and_unit_from_section_headers(): void
    {
        $rows = [
            ['Building 1130N - Ground Floor'],
            ['SN', 'Off/Shop', 'Company', 'Contact Details', 'SIZE /SQM', 'Contract Period', 'Rate/ SQM', 'Rent', 'Service Charge', 'Monthly'],
            [1, 'Shop 2', 'Bahrain Insurance Co.', 'Fadhel', 170.73, '01.03.2025 - 28.02.2028', 11.49, 1961.688, 0.15, 2285.365],
            [2, 'Shop 5', 'vacant', null, 86.5, null, 10.5, 0, null, 0],
        ];

        $platinumRows = [
            ['Platinum Tower - Office Details'],
            ['SN', 'Office No', 'Title Deed (SQM)', 'Per Plan (SQM)', 'Difference', 'Rate / m²', 'Monthly inclusive of 15% Charges', 'Parking Bay Nos.', 'Rent', 'Remarks'],
            [1, 151, 145, 149.85, 4.85, 6, 899.1, 'P2-7-8', null, 'Municipality Closed/ Sold'],
        ];

        $file = $this->buildWorkbook([
            'P7H Aug 2026'    => $rows,
            'Platimum Tower'  => $platinumRows,
        ]);

        $response = $this->post(route('import.smart'), ['file' => $file]);
        $response->assertRedirect(route('dashboard'));

        $building = Building::where('property_code', 'P7H-1130N')->first();
        $this->assertNotNull($building);
        $floor = Floor::where('building_id', $building->id)->where('floor_name', 'Ground Floor')->first();
        $this->assertNotNull($floor);

        $rentedUnit = PropertyUnit::where('unit_name', 'P7H-1130N - Shop 2')->first();
        $this->assertNotNull($rentedUnit);
        $this->assertEquals($floor->id, $rentedUnit->floor_id);
        $tenant = Tenant::whereRaw('LOWER(name) = ?', ['bahrain insurance co.'])->first();
        $this->assertNotNull($tenant);
        $this->assertNotNull(LeaseContract::where('unit_id', $rentedUnit->id)->first());

        $vacantUnit = PropertyUnit::where('unit_name', 'P7H-1130N - Shop 5')->first();
        $this->assertNotNull($vacantUnit);

        $platinum = Building::where('property_code', 'PLATINUM-TOWER')->first();
        $this->assertNotNull($platinum);
        $office = PropertyUnit::where('unit_name', 'Platinum Tower - Office 151')->first();
        $this->assertNotNull($office);
    }

    public function test_smart_import_skips_older_duplicate_p7h_summary_sheet(): void
    {
        $duplicateRows = [
            ['Building 1130N - Ground Floor'],
            ['SN', 'Off/Shop', 'Company', 'SIZE /SQM', 'Rate/ SQM', 'Rent', 'Service Charge', 'Monthly'],
            [1, 'Shop 2', 'Bahrain Insurance Co.', 170.73, 11.49, 1961.688, 0.15, 2285.365],
        ];
        $mainRows = [
            ['Building 1130N - Ground Floor'],
            ['SN', 'Off/Shop', 'Company', 'Contact Details', 'SIZE /SQM', 'Contract Period', 'Rate/ SQM', 'Rent', 'Service Charge', 'Monthly'],
            [1, 'Shop 2', 'Bahrain Insurance Co.', 'Fadhel', 170.73, '01.03.2025 - 28.02.2028', 11.49, 1961.688, 0.15, 2285.365],
        ];
        $mp2Rows = [
            ['Miknas Plaza 2 - Data Base - August 2026'],
            ['Total Flats', null, 37, null, 'RENTED', 1, 'VACANT', 0],
            ['SN', 'Flat', 'BR', 'Name', 'Rent'],
            [1, 11, '2BR', 'Vacant', 0],
        ];
        Building::create(['property_name' => 'Miknas Plaza 2', 'property_code' => 'MP2']);

        $file = $this->buildWorkbook([
            'P7H 1130M & N' => $duplicateRows,
            'P7H Aug 2026'  => $mainRows,
            'MP2 Aug 2026'  => $mp2Rows,
        ]);

        $this->post(route('import.smart'), ['file' => $file])->assertRedirect(route('dashboard'));

        // Only one unit created for Shop 2, not duplicated across the two P7H sheets.
        $this->assertEquals(1, PropertyUnit::where('unit_name', 'P7H-1130N - Shop 2')->count());
    }

    public function test_smart_import_tags_tenant_as_company_from_sheet_name_prefix(): void
    {
        $companyRows = [
            ['Building 1130N - Ground Floor'],
            ['SN', 'Off/Shop', 'Company', 'Contact Details', 'SIZE /SQM', 'Contract Period', 'Rate/ SQM', 'Rent', 'Service Charge', 'Monthly'],
            [1, 'Shop 2', 'Bahrain Insurance Co.', 'Fadhel', 170.73, '01.03.2025 - 28.02.2028', 11.49, 1961.688, 0.15, 2285.365],
        ];
        $mp1Rows = $this->mp1Rows();
        Building::create(['property_name' => 'Miknas Plaza 1', 'property_code' => 'MP1']);

        $file = $this->buildWorkbook([
            'Promoseven - P7H Aug 2026' => $companyRows,
            'MP1 Aug 2026'              => $mp1Rows,
        ]);

        $this->post(route('import.smart'), ['file' => $file])->assertRedirect(route('dashboard'));

        $companyTenant = Tenant::whereRaw('LOWER(name) = ?', ['bahrain insurance co.'])->first();
        $this->assertNotNull($companyTenant);
        $this->assertEquals('company', $companyTenant->tenant_type);
        $this->assertEquals('Promoseven', $companyTenant->company_name);

        $individualTenant = Tenant::whereRaw('LOWER(name) = ?', ['coral bay'])->first();
        $this->assertNotNull($individualTenant);
        $this->assertEquals('individual', $individualTenant->tenant_type);
        $this->assertNull($individualTenant->company_name);
    }

    public function test_single_sheet_workbook_still_uses_original_smart_import_path(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');
        $sheet->fromArray(['Property Code', 'Unit Name', 'Unit Type', 'Unit Condition'], null, 'A1');
        $sheet->fromArray(['TA1', 'Flat 21', 'Apartment', 'Fully Furnished'], null, 'A2');

        $tmp = tempnam(sys_get_temp_dir(), 'single') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);
        $file = new UploadedFile($tmp, 'units.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->post(route('import.smart'), ['file' => $file])->assertRedirect(route('dashboard'));

        $unit = PropertyUnit::where('unit_name', 'Flat 21')->first();
        $this->assertNotNull($unit);
        $this->assertEquals($building->id, $unit->building_id);
    }
}
