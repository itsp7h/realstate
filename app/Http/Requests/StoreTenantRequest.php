<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'tenant_type'         => ['required', 'in:individual,company'],
            'id_cr_number'        => ['nullable', 'string', 'max:100'],
            'phone'               => ['nullable', 'string', 'max:50'],
            'email'               => ['nullable', 'email', 'max:255'],
            'nationality_country' => ['nullable', 'string', 'max:100'],
        ];
    }
}
