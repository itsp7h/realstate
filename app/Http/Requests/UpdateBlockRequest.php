<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateBlockRequest extends StoreBlockRequest
{
    public function rules(): array
    {
        $block = $this->route('block');

        return [
            'block_name' => [
                'required', 'string', 'max:100',
                Rule::unique('blocks', 'block_name')
                    ->where(fn ($query) => $query->where('building_id', $block?->building_id))
                    ->ignore($block?->id),
            ],
            'block_code'          => ['nullable', 'string', 'max:50'],
            'total_no_of_floors'  => ['nullable', 'integer', 'min:1'],
        ];
    }
}
