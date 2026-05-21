<?php

namespace App\Http\Requests;

class UpdateMaintenanceRequest extends StoreMaintenanceRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // Allow same job_order on update (ignore current record)
        $id = $this->route('maintenance_request')?->id;
        $rules['job_order'] = ['nullable', 'string', 'max:100', "unique:maintenance_requests,job_order,{$id}"];

        return $rules;
    }
}
