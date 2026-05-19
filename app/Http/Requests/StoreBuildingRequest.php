<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_name'      => ['required', 'string', 'max:255'],
            'property_code'      => ['required', 'string', 'max:10', 'unique:buildings,property_code'],
            'type_of_ownership'  => ['nullable', 'string', 'max:100', 'in:Owned,Leased,Joint Venture,Managed'],
            'property_type'      => ['nullable', 'string', 'max:100', 'in:Residential,Commercial,Mixed Use,Industrial,Retail'],
            'land_lord_name'     => ['nullable', 'string', 'max:255'],
            'building_no'        => ['nullable', 'integer', 'min:0'],
            'road'               => ['nullable', 'string', 'max:255'],
            'block'              => ['nullable', 'integer', 'min:0'],
            'area'               => ['nullable', 'string', 'max:255'],
            'city'               => ['nullable', 'string', 'max:255'],
            'total_no_of_blocks' => ['nullable', 'integer', 'min:0'],
            'total_no_of_floors' => ['nullable', 'integer', 'min:0'],
            'total_no_of_units'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
