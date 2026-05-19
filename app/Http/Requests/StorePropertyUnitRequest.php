<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Property Level
            'property_name'    => ['required', 'string', 'max:255'],
            'property_code'    => ['required', 'string', 'in:AAL,MP1,MP2,MP3,MP4,MP5'],
            'type_of_ownership' => ['nullable', 'string', 'max:100'],
            'property_type'    => ['nullable', 'string', 'max:100'],
            'land_lord_name'   => ['nullable', 'string', 'max:255'],
            // Address
            'building_no'      => ['nullable', 'integer', 'min:1'],
            'road'             => ['nullable', 'string', 'max:255'],
            'block'            => ['nullable', 'integer', 'min:1'],
            'area'             => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:255'],
            // Block Level
            'total_no_of_blocks' => ['nullable', 'integer', 'min:1'],
            'block_name'       => ['nullable', 'string', 'max:100'],
            'block_code'       => ['nullable', 'string', 'max:50'],
            'building_no_2'    => ['nullable', 'integer', 'min:1'],
            // Floor Level
            'total_no_of_floors' => ['nullable', 'integer', 'min:1'],
            'floor_name'       => ['nullable', 'string', 'max:100'],
            'floor_code'       => ['nullable', 'string', 'max:50'],
            // Unit Level
            'total_no_of_units'  => ['nullable', 'integer', 'min:1'],
            'unit_name'        => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:500'],
            'unit_type'        => ['nullable', 'string', 'max:50'],
            'creation_date'    => ['nullable', 'date'],
            'unit_condition'   => ['nullable', 'string', 'max:100'],
            'view'             => ['nullable', 'string', 'max:100'],
            'no_of_parkings_foc' => ['nullable', 'integer', 'min:0'],
            // Area & Pricing
            'area_unit'        => ['nullable', 'string', 'in:Sq. Mt.,Sq. Ft.'],
            'area_inside'      => ['nullable', 'numeric', 'min:0'],
            'area_terrace'     => ['nullable', 'numeric', 'min:0'],
            'rate_per_area_unit' => ['nullable', 'numeric', 'min:0'],
            'rent_per_month'   => ['nullable', 'numeric', 'min:0'],
            'security_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            // Legal
            'municipality_nos' => ['nullable', 'string', 'max:255'],
            // Utilities
            'electricity_installation_date' => ['nullable', 'date'],
            'electricity_meter_no'          => ['nullable', 'string', 'max:100'],
            'water_installation_date'       => ['nullable', 'date'],
            'water_meter_no'                => ['nullable', 'string', 'max:100'],
            'electricity_ac_no'             => ['nullable', 'string', 'max:100'],
        ];
    }
}
