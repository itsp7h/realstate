<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends StoreBuildingRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['property_code'] = [
            'required',
            'string',
            'max:10',
            Rule::unique('buildings', 'property_code')->ignore($this->route('building')),
        ];

        return $rules;
    }
}
