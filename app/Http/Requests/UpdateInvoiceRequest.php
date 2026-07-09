<?php

namespace App\Http\Requests;

class UpdateInvoiceRequest extends StoreInvoiceRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['status'] = ['nullable', 'in:draft,issued,partially_paid,paid,overdue,cancelled'];
        return $rules;
    }
}
