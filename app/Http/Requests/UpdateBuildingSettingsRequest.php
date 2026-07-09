<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vat_enabled' => ['required', 'boolean'],
            'vat_rate'    => ['nullable', 'numeric', 'min:0', 'max:100'],
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
