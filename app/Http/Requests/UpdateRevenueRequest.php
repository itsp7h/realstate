<?php

namespace App\Http\Requests;

use App\Models\Revenue;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRevenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'building_id'  => ['required', 'integer', 'exists:buildings,id'],
            'unit_id'      => ['nullable', 'integer', 'exists:property_units,id'],
            'category'     => ['required', 'in:' . implode(',', array_keys(Revenue::CATEGORIES))],
            'description'  => ['nullable', 'string', 'max:500'],
            'amount'       => ['required', 'numeric', 'min:0.001'],
            'revenue_date' => ['required', 'date', 'before_or_equal:today'],
            'source_name'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'building_id.required'         => 'Please select a building.',
            'building_id.exists'           => 'The selected building does not exist.',
            'unit_id.exists'                => 'The selected unit does not exist.',
            'category.required'            => 'Please select a category.',
            'category.in'                  => 'That category is not valid.',
            'amount.required'               => 'Please enter an amount.',
            'amount.min'                    => 'The amount must be greater than zero.',
            'revenue_date.required'         => 'Please pick a date.',
            'revenue_date.before_or_equal'  => 'The revenue date cannot be in the future.',
        ];
    }
}
