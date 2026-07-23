<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role'     => ['required', Rule::in(['admin', 'user', 'maintenance'])],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min'       => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.in'            => 'Invalid role selected.',
        ];
    }
}
