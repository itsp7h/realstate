<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'      => ['required', 'numeric', 'min:0.001'],
            'payment_date' => ['required', 'date'],
            'method'      => ['required', 'in:cash,bank_transfer,cheque,online_card'],
            'reference'   => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['required_if:method,cheque', 'nullable', 'string', 'max:50'],
            'cheque_date'   => ['required_if:method,cheque', 'nullable', 'date'],
            'notes'       => ['nullable', 'string'],
            'ewa_bill_id' => ['nullable', 'integer', 'exists:ewa_bills,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min'             => 'Payment amount must be greater than zero.',
            'method.in'              => 'Invalid payment method selected.',
            'cheque_number.required_if' => 'Cheque number is required for cheque payments.',
            'cheque_date.required_if'   => 'Cheque date is required for cheque payments.',
        ];
    }
}
