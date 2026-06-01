<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaseContractRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Contract identity
            'date'                     => ['required', 'date'],
            'lease_agreement_no'       => ['required', 'string', 'max:100', 'unique:lease_contracts,lease_agreement_no'],

            // Tenant
            'tenant_id'                => ['nullable', 'integer', 'exists:tenants,id'],
            'tenant_name'              => ['required', 'string', 'max:255'],

            // Property location
            'property_name'            => ['nullable', 'string', 'max:255'],
            'property_code'            => ['nullable', 'string', 'max:50'],
            'block_name'               => ['nullable', 'string', 'max:100'],
            'block_code'               => ['nullable', 'string', 'max:50'],
            'floor_name'               => ['nullable', 'string', 'max:100'],
            'floor_code'               => ['nullable', 'string', 'max:50'],

            // Unit
            'unit_id'                  => ['nullable', 'integer', 'exists:property_units,id'],
            'unit'                     => ['nullable', 'string', 'max:100'],

            // Fit-out
            'description'              => ['nullable', 'in:Fitted,Shell & Core,Semi-Fitted'],

            // Lease term
            'lease_start_date'         => ['required', 'date'],
            'lease_end_date'           => ['required', 'date', 'after:lease_start_date'],
            'lease_break_date'         => ['nullable', 'date', 'after_or_equal:lease_start_date', 'before_or_equal:lease_end_date'],
            'notice_period'            => ['nullable', 'string', 'max:50'],

            // Accounting
            'rental_income_ledger'     => ['nullable', 'string', 'max:50'],
            'currency'                 => ['nullable', 'in:BHD,USD,EUR,GBP,SAR,AED'],
            'security_deposit'         => ['nullable', 'decimal:0,3', 'min:0'],

            // Rent component
            'invoicing_frequency'      => ['nullable', 'in:Monthly,Quarterly,Semi-Annually,Annually'],
            'rent_start_date'          => ['nullable', 'date'],
            'rent_end_date'            => ['nullable', 'date', 'after_or_equal:rent_start_date'],
            'rent_per_month'           => ['nullable', 'decimal:0,3', 'min:0'],

            // Service charge component
            'service_frequency'        => ['nullable', 'in:Monthly,Quarterly,Semi-Annually,Annually'],
            'service_start_date'       => ['nullable', 'date'],
            'service_end_date'         => ['nullable', 'date', 'after_or_equal:service_start_date'],
            'service_amount_bd_excl_vat' => ['nullable', 'decimal:0,3', 'min:0'],
        ];
    }
}
