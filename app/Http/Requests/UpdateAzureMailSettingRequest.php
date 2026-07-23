<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAzureMailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'     => ['required', 'string', 'max:255'],
            'client_id'     => ['required', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:1000'],
            'from_address'  => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required'    => 'Please enter the Azure AD tenant ID.',
            'client_id.required'    => 'Please enter the Azure AD application (client) ID.',
            'from_address.required' => 'Please enter the from address emails will be sent from.',
            'from_address.email'    => 'The from address must be a valid email address.',
        ];
    }
}
