<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\PropertyUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SmartImportUnitFloorTest extends TestCase
{
    use RefreshDatabase;

    private function makeCsv(array $headers, array ...$rows): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        $h   = fopen($tmp, 'w');
        fputcsv($h, $headers);
        foreach ($rows as $row) {
            fputcsv($h, $row);
        }
        fclose($h);

        return new UploadedFile($tmp, 'units.csv', 'text/csv', null, true);
    }

    public function test_smart_import_links_unit_to_floor_via_floor_code(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $floor    = Floor::create(['building_id' => $building->id, 'floor_name' => 'Floor 2', 'floor_code' => 'FL2']);

        $file = $this->makeCsv(
            ['Property Code', 'Floor Code', 'Unit Name', 'Unit Type', 'Unit Condition'],
            ['TA1', 'FL2', 'Flat 21', 'Apartment', 'Fully Furnished']
        );

        $this->post(route('import.smart'), ['file' => $file])
            ->assertRedirect(route('data.index'));

        $unit = PropertyUnit::where('unit_name', 'Flat 21')->first();
        $this->assertNotNull($unit);
        $this->assertEquals($floor->id, $unit->floor_id);
    }

    public function test_smart_import_leaves_floor_id_null_when_floor_code_unmatched(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $file = $this->makeCsv(
            ['Property Code', 'Floor Code', 'Unit Name', 'Unit Type', 'Unit Condition'],
            ['TA1', 'FL99', 'Flat 21', 'Apartment', 'Fully Furnished']
        );

        $this->post(route('import.smart'), ['file' => $file])
            ->assertRedirect(route('data.index'));

        $unit = PropertyUnit::where('unit_name', 'Flat 21')->first();
        $this->assertNotNull($unit);
        $this->assertNull($unit->floor_id);
    }

    public function test_smart_import_links_units_to_floors_created_in_the_same_file(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $file = $this->makeCsv(
            ['Property Code', 'Floor Name', 'Floor Code', 'Unit Name', 'Unit Type', 'Unit Condition'],
            ['TA1', 'Floor 3', 'FL3', 'Flat 31', 'Apartment', 'Fully Furnished']
        );

        $this->post(route('import.smart'), ['file' => $file])
            ->assertRedirect(route('data.index'));

        $floor = Floor::where('building_id', $building->id)->where('floor_code', 'FL3')->first();
        $this->assertNotNull($floor);

        $unit = PropertyUnit::where('unit_name', 'Flat 31')->first();
        $this->assertNotNull($unit);
        $this->assertEquals($floor->id, $unit->floor_id);
    }
}
