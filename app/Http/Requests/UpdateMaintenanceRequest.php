<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMaintenanceRequest extends StoreMaintenanceRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $id = $this->route('maintenanceRequest')?->id;
        $rules['job_order'] = ['nullable', 'string', 'max:100', Rule::unique('maintenance_requests', 'job_order')->ignore($id)];

        return $rules;
    }
}
