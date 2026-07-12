<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'       => ['required', 'numeric', 'min:0.001'],
            'payment_date' => ['required', 'date'],
            'method'       => ['required', 'in:cash,bank_transfer,cheque,online_card'],
            'reference'    => ['nullable', 'string', 'max:255'],
            'notes'        => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min'    => 'Payment amount must be greater than zero.',
            'method.in'     => 'Invalid payment method selected.',
        ];
    }
}
