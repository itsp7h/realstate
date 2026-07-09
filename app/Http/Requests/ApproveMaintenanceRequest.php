<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMaintenanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'approved_supervisor' => ['nullable', 'string', 'max:255'],
            'approved_dept_head'  => ['nullable', 'string', 'max:255'],
            'dept_head_signature' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'dept_head_signature.required' => 'Please provide your signature before approving.',
        ];
    }
}
