<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'                      => ['required', 'integer', 'exists:tenants,id'],
            'type'                           => ['required', 'in:rent,utilities,other'],
            'description'                    => ['nullable', 'string', 'max:500'],
            'vat_rate'                       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_date'                   => ['required', 'date'],
            'notes'                          => ['nullable', 'string'],
            'lines'                          => ['required', 'array', 'min:1'],
            'lines.*.lease_contract_id'      => ['nullable', 'integer', 'exists:lease_contracts,id'],
            'lines.*.property_name'          => ['required', 'string', 'max:255'],
            'lines.*.unit'                   => ['nullable', 'string', 'max:100'],
            'lines.*.lease_agreement_no'     => ['nullable', 'string', 'max:100'],
            'lines.*.rental_period_start'    => ['nullable', 'date'],
            'lines.*.rental_period_end'      => ['nullable', 'date', 'after_or_equal:lines.*.rental_period_start'],
            'lines.*.amount'                 => ['required', 'numeric', 'min:0.001'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required'          => 'Please select a tenant.',
            'tenant_id.exists'             => 'The selected tenant does not exist.',
            'lines.required'              => 'Add at least one rental line to this invoice.',
            'lines.min'                    => 'Add at least one rental line to this invoice.',
            'lines.*.property_name.required' => 'Each line needs a property.',
            'lines.*.amount.required'     => 'Each line needs an amount.',
            'lines.*.amount.min'          => 'Each line amount must be greater than zero.',
        ];
    }
}
