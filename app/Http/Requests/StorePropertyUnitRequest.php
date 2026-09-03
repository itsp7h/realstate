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
            'building_id'      => ['nullable', 'exists:buildings,id'],
            'floor_id'         => ['nullable', 'exists:floors,id'],
            // Property Level
            'property_name'    => ['required', 'string', 'max:255'],
            'property_code'    => ['required', 'string', 'in:AAL,MP1,MP2,MP3,MP4,MP5'],
            'type_of_ownership' => ['nullable', 'string', 'in:Owned,Leased,Joint Venture,Managed'],
            'property_type'    => ['nullable', 'string', 'in:Residential,Commercial,Mixed Use,Industrial,Retail'],
            'land_lord_name'   => ['nullable', 'string', 'max:255'],
            // Address
            'building_no'      => ['nullable', 'integer', 'min:1'],
            'road'             => ['nullable', 'string', 'max:255'],
            'block'            => ['nullable', 'integer', 'min:1'],
            'area'             => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:255'],
            // Unit Level
            'unit_name'        => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:255'],
            'unit_type'        => ['nullable', 'string', 'in:Studio,1BHK,2BHK,3BHK,4BHK,Penthouse,Commercial,Office,Shop'],
            'creation_date'    => ['nullable', 'date'],
            'unit_condition'   => ['nullable', 'string', 'in:Furnished,Fitted,Semi-Furnished,Unfurnished,Shell & Core'],
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
            'ewa_share_percent'             => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
