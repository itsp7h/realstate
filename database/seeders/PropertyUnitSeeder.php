<?php

namespace Database\Seeders;

use App\Models\PropertyUnit;
use Illuminate\Database\Seeder;

class PropertyUnitSeeder extends Seeder
{
    public function run(): void
    {
        PropertyUnit::truncate();

        $properties = [
            [
                'property_name'     => 'AAL Tower',
                'property_code'     => 'AAL',
                'type_of_ownership' => 'Owned',
                'property_type'     => 'Commercial',
                'landlord_name'     => 'Al Akaria Ltd',
                'building_no'       => 101,
                'road'              => 'King Faisal Highway',
                'block'             => 304,
                'area'              => 'Diplomatic Area',
                'city'              => 'Manama',
                'total_no_of_blocks'=> 1,
                'total_no_of_floors'=> 12,
                'total_no_of_units' => 24,
                'area_unit'         => 'Sq. Mt.',
            ],
            [
                'property_name'     => 'Miknas Plaza 1',
                'property_code'     => 'MP1',
                'type_of_ownership' => 'Owned',
                'property_type'     => 'Residential',
                'landlord_name'     => 'Akram Miknas',
                'building_no'       => 201,
                'road'              => 'Avenue 0018',
                'block'             => 318,
                'area'              => 'Capital Governorate',
                'city'              => 'Manama',
                'total_no_of_blocks'=> 2,
                'total_no_of_floors'=> 8,
                'total_no_of_units' => 32,
                'area_unit'         => 'Sq. Mt.',
            ],
            [
                'property_name'     => 'Miknas Plaza 2',
                'property_code'     => 'MP2',
                'type_of_ownership' => 'Owned',
                'property_type'     => 'Residential',
                'landlord_name'     => 'Akram Miknas',
                'building_no'       => 202,
                'road'              => 'Avenue 0022',
                'block'             => 324,
                'area'              => 'Capital Governorate',
                'city'              => 'Manama',
                'total_no_of_blocks'=> 1,
                'total_no_of_floors'=> 10,
                'total_no_of_units' => 40,
                'area_unit'         => 'Sq. Mt.',
            ],
            [
                'property_name'     => 'Miknas Plaza 3',
                'property_code'     => 'MP3',
                'type_of_ownership' => 'Owned',
                'property_type'     => 'Residential',
                'landlord_name'     => 'Akram Miknas',
                'building_no'       => 203,
                'road'              => 'Avenue 0030',
                'block'             => 330,
                'area'              => 'Capital Governorate',
                'city'              => 'Manama',
                'total_no_of_blocks'=> 1,
                'total_no_of_floors'=> 10,
                'total_no_of_units' => 40,
                'area_unit'         => 'Sq. Ft.',
            ],
            [
                'property_name'     => 'Miknas Plaza 4',
                'property_code'     => 'MP4',
                'type_of_ownership' => 'Leased',
                'property_type'     => 'Mixed Use',
                'landlord_name'     => 'Akram Miknas',
                'building_no'       => 204,
                'road'              => 'Umm Al Hassam Road',
                'block'             => 340,
                'area'              => 'Southern Governorate',
                'city'              => 'Riffa',
                'total_no_of_blocks'=> 2,
                'total_no_of_floors'=> 6,
                'total_no_of_units' => 24,
                'area_unit'         => 'Sq. Mt.',
            ],
            [
                'property_name'     => 'Miknas Plaza 5',
                'property_code'     => 'MP5',
                'type_of_ownership' => 'Owned',
                'property_type'     => 'Residential',
                'landlord_name'     => 'Akram Miknas',
                'building_no'       => 205,
                'road'              => 'Salmaniya Avenue',
                'block'             => 350,
                'area'              => 'Central Governorate',
                'city'              => 'Isa Town',
                'total_no_of_blocks'=> 1,
                'total_no_of_floors'=> 8,
                'total_no_of_units' => 32,
                'area_unit'         => 'Sq. Mt.',
            ],
        ];

        $unitTypes  = ['Studio', '1BHK', '2BHK', '3BHK'];
        $conditions = ['Furnished', 'Fitted', 'Semi-Furnished', 'Unfurnished'];
        $views      = ['City View', 'Sea View', 'Garden View', 'Pool View', 'Street View'];

        $unitTypeConfig = [
            'Studio' => ['area' => [35, 50],  'rent' => [250,  350],  'deposit' => 500],
            '1BHK'   => ['area' => [55, 80],  'rent' => [350,  500],  'deposit' => 700],
            '2BHK'   => ['area' => [90, 130], 'rent' => [500,  750],  'deposit' => 1000],
            '3BHK'   => ['area' => [140, 200],'rent' => [750,  1200], 'deposit' => 1500],
        ];

        foreach ($properties as $prop) {
            $floors = min(4, $prop['total_no_of_floors']);
            $blocks = $prop['total_no_of_blocks'];

            for ($b = 1; $b <= $blocks; $b++) {
                for ($f = 1; $f <= $floors; $f++) {
                    $unitsPerFloor = 4;
                    for ($u = 1; $u <= $unitsPerFloor; $u++) {
                        $unitNo   = (($b - 1) * $floors * $unitsPerFloor) + (($f - 1) * $unitsPerFloor) + $u;
                        $unitType = $unitTypes[($unitNo - 1) % count($unitTypes)];
                        $cond     = $conditions[array_rand($conditions)];
                        $view     = $views[array_rand($views)];
                        $cfg      = $unitTypeConfig[$unitType];
                        $area     = rand($cfg['area'][0] * 10, $cfg['area'][1] * 10) / 10;
                        $rent     = rand($cfg['rent'][0], $cfg['rent'][1]);
                        $rate     = round($rent / $area, 2);

                        $elecDate = date('Y-m-d', strtotime('-' . rand(6, 36) . ' months'));
                        $waterDate= date('Y-m-d', strtotime('-' . rand(6, 36) . ' months'));

                        PropertyUnit::create(array_merge($prop, [
                            'block_name'                    => "Block {$b}",
                            'block_code'                    => "BL{$b}",
                            'building_no_2'                 => $prop['building_no'],
                            'floor_name'                    => "Floor {$f}",
                            'floor_code'                    => "FL{$f}",
                            'unit_name'                     => "{$prop['property_code']} - {$unitNo}",
                            'description'                   => "{$prop['property_name']} - Flat {$unitNo}",
                            'unit_type'                     => $unitType,
                            'unit_condition'                => $cond,
                            'view'                          => $view,
                            'creation_date'                 => '2024-01-01',
                            'no_of_parkings_foc'            => rand(0, 2),
                            'area_inside'                   => $area,
                            'area_terrace'                  => rand(0, 1) ? rand(5, 20) : null,
                            'rate_per_area_unit'            => $rate,
                            'rent_per_month'                => $rent,
                            'security_deposit'              => $cfg['deposit'],
                            'municipality_nos'              => 'MUN-' . strtoupper($prop['property_code']) . '-' . str_pad($unitNo, 3, '0', STR_PAD_LEFT),
                            'electricity_installation_date' => $elecDate,
                            'electricity_meter_no'          => 'KS' . str_pad(rand(100000, 999999), 6, '0'),
                            'electricity_account_no'        => 'ACC-' . rand(10000, 99999),
                            'water_installation_date'       => $waterDate,
                            'water_meter_no'                => '23H' . rand(100000000, 999999999),
                        ]));
                    }
                }
            }
        }
    }
}
