<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceNoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'      => ['required', 'in:credit,debit'],
            'amount'    => ['required', 'numeric', 'min:0.001'],
            'note_date' => ['required', 'date', 'before_or_equal:today'],
            'reason'    => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'             => 'Invalid note type selected.',
            'amount.min'          => 'Note amount must be greater than zero.',
            'note_date.before_or_equal' => 'Note date cannot be in the future.',
            'reason.required'     => 'Please explain why this note is being issued.',
        ];
    }
}
