<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEwaBillRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'lease_contract_id'  => ['nullable', 'integer', 'exists:lease_contracts,id'],
            'tenant_name'        => ['required', 'string', 'max:255'],
            'property_name'      => ['nullable', 'string', 'max:255'],
            'unit'               => ['nullable', 'string', 'max:100'],
            'ewa_account_number' => ['nullable', 'string', 'max:50'],
            'billing_period'     => ['required', 'string', 'max:30'],
            'reading_date'       => ['nullable', 'date'],
            'reading_type'       => ['required', 'in:actual,estimated'],
            'elec_prev_reading'  => ['nullable', 'numeric', 'min:0'],
            'elec_curr_reading'  => ['nullable', 'numeric', 'min:0'],
            'elec_charges'       => ['nullable', 'numeric', 'min:0'],
            'water_prev_reading' => ['nullable', 'numeric', 'min:0'],
            'water_curr_reading' => ['nullable', 'numeric', 'min:0'],
            'water_charges'      => ['nullable', 'numeric', 'min:0'],
            'ewa_cap'            => ['nullable', 'numeric', 'min:0'],
            'due_date'           => ['required', 'date'],
            'notes'              => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_name.required'    => 'Tenant name is required.',
            'billing_period.required' => 'Please enter the billing period.',
            'due_date.required'       => 'Please enter the due date.',
        ];
    }
}
