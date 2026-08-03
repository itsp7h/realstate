<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateMonthlyInvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_date.required' => 'Please pick a date for the invoices.',
            'invoice_date.date'     => 'That date is not valid.',
        ];
    }
}
