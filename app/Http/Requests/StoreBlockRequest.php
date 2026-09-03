<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $building = $this->route('building');

        return [
            'block_name' => [
                'required', 'string', 'max:100',
                Rule::unique('blocks', 'block_name')
                    ->where(fn ($query) => $query->where('building_id', $building?->id)),
            ],
            'block_code'          => ['nullable', 'string', 'max:50'],
            'total_no_of_floors'  => ['nullable', 'integer', 'min:1'],
        ];
    }
}
