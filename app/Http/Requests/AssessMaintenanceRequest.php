<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssessMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supervisor_name'      => ['required', 'string', 'max:255'],
            'supervisor_datetime'  => ['required', 'date'],
            'job_assessment'       => ['nullable', 'string'],
            'selected_quotation'   => ['required', 'integer', 'in:1,2,3'],
            'supervisor_signature' => ['required', 'string'],
            'maintenance_remarks'  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'supervisor_name.required'     => 'Supervisor name is required.',
            'supervisor_datetime.required' => 'Please specify the assessment date & time.',
            'selected_quotation.required'   => 'Please select one of the quotations.',
            'selected_quotation.in'         => 'Selected quotation must be 1, 2, or 3.',
            'supervisor_signature.required' => 'Please provide your signature before submitting.',
        ];
    }
}
