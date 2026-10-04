<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // `current_password` proves the person at the keyboard is the
            // account holder and not someone who found an unlocked screen.
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8), 'different:current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'That is not your current password.',
            'password.confirmed'                => 'The two passwords do not match.',
            'password.min'                      => 'Use at least 8 characters.',
            'password.different'                => 'The new password must be different from the current one.',
        ];
    }
}
