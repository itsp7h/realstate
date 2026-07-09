<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaseContractRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('lease_contract')->id;

        return [
            'date'                     => ['required', 'date'],
            'lease_agreement_no'       => ['required', 'string', 'max:100', "unique:lease_contracts,lease_agreement_no,{$id}"],
            'tenant_id'                => ['required', 'integer', 'exists:tenants,id'],
            'property_name'            => ['nullable', 'string', 'max:255'],
            'property_code'            => ['nullable', 'string', 'max:50'],
            'block_name'               => ['nullable', 'string', 'max:100'],
            'block_code'               => ['nullable', 'string', 'max:50'],
            'floor_name'               => ['nullable', 'string', 'max:100'],
            'floor_code'               => ['nullable', 'string', 'max:50'],
            'unit_id'                  => ['nullable', 'integer', 'exists:property_units,id'],
            'unit'                     => ['nullable', 'string', 'max:100'],
            'description'              => ['nullable', 'in:Fitted,Shell & Core,Semi-Fitted'],
            'lease_start_date'         => ['required', 'date'],
            'lease_end_date'           => ['required', 'date', 'after:lease_start_date'],
            'lease_break_date'         => ['nullable', 'date', 'after_or_equal:lease_start_date', 'before_or_equal:lease_end_date'],
            'notice_period'            => ['nullable', 'string', 'max:50'],
            'rental_income_ledger'     => ['nullable', 'string', 'max:50'],
            'currency'                 => ['nullable', 'in:BHD,USD,EUR,GBP,SAR,AED'],
            'security_deposit'         => ['nullable', 'decimal:0,3', 'min:0'],
            'ewa_cap'                  => ['nullable', 'decimal:0,3', 'min:0'],
            'vat_enabled'              => ['required', 'boolean'],
            'vat_rate'                 => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoicing_frequency'      => ['nullable', 'in:Monthly,Quarterly,Semi-Annually,Annually'],
            'rent_start_date'          => ['nullable', 'date'],
            'rent_end_date'            => ['nullable', 'date', 'after_or_equal:rent_start_date'],
            'rent_per_month'           => ['nullable', 'decimal:0,3', 'min:0'],
            'service_frequency'        => ['nullable', 'in:Monthly,Quarterly,Semi-Annually,Annually'],
            'service_start_date'       => ['nullable', 'date'],
            'service_end_date'         => ['nullable', 'date', 'after_or_equal:service_start_date'],
            'service_amount_bd_excl_vat' => ['nullable', 'decimal:0,3', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'vat_rate.max' => 'VAT rate cannot exceed 100%.',
            'vat_rate.min' => 'VAT rate cannot be negative.',
        ];
    }
}
