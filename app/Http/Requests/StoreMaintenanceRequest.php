<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date'                           => ['required', 'date'],
            'property'                       => ['required', 'string', 'max:255'],
            'tenant'                         => ['required', 'string', 'max:255'],
            'flat'                           => ['required', 'string', 'max:100'],
            'contact_no'                     => ['required', 'string', 'max:50'],
            'available_datetime'             => ['required', 'date'],
            'apartment_status'               => ['required', 'in:occupied,vacant,furnished,other'],
            'job_order'                      => ['nullable', 'string', 'max:100'],
            'request_date'                   => ['nullable', 'date'],
            'status'                         => ['nullable', 'in:open,in_progress,completed,cancelled'],
            'job_lines'                      => ['nullable', 'array'],
            'job_lines.*.location'           => ['nullable', 'string', 'max:255'],
            'job_lines.*.description'        => ['nullable', 'string'],
            'job_lines.*.supervisor_comment' => ['nullable', 'string'],
            'supervisor_name'                => ['nullable', 'string', 'max:255'],
            'supervisor_datetime'            => ['nullable', 'date'],
            'job_assessment'                 => ['nullable', 'string'],
            'quotation_1'                    => ['nullable', 'numeric', 'min:0'],
            'quotation_2'                    => ['nullable', 'numeric', 'min:0'],
            'quotation_3'                    => ['nullable', 'numeric', 'min:0'],
            'maintenance_remarks'            => ['nullable', 'string'],
            'approved_supervisor'            => ['nullable', 'string', 'max:255'],
            'approved_dept_head'             => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'               => 'The date field is required.',
            'property.required'           => 'Please enter the property name.',
            'tenant.required'             => 'Tenant name is required.',
            'flat.required'               => 'Flat / unit number is required.',
            'contact_no.required'         => 'Contact number is required.',
            'available_datetime.required' => 'Please specify an available date & time.',
            'apartment_status.required'   => 'Please select the apartment status.',
            'apartment_status.in'         => 'Invalid apartment status selected.',
            'status.in'                   => 'Invalid status selected.',
        ];
    }
}
