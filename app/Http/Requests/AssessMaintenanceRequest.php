<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssessMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supervisor_name'     => ['required', 'string', 'max:255'],
            'supervisor_datetime' => ['required', 'date'],
            'job_assessment'      => ['nullable', 'string'],
            'quotation_1'         => ['nullable', 'numeric', 'min:0'],
            'quotation_1_file'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'quotation_2'         => ['nullable', 'numeric', 'min:0'],
            'quotation_2_file'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'quotation_3'         => ['nullable', 'numeric', 'min:0'],
            'quotation_3_file'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'maintenance_remarks' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'supervisor_name.required'     => 'Supervisor name is required.',
            'supervisor_datetime.required' => 'Please specify the assessment date & time.',
        ];
    }
}
