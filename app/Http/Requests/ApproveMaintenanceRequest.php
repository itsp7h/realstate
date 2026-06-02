<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'selected_quotation'  => ['required', 'integer', 'in:1,2,3'],
            'approved_supervisor' => ['nullable', 'string', 'max:255'],
            'approved_dept_head'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'selected_quotation.required' => 'Please select a quotation to approve.',
            'selected_quotation.in'       => 'Selected quotation must be 1, 2, or 3.',
        ];
    }
}
