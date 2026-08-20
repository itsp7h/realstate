<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'string', 'email', 'max:255'],
            // min:8 mirrors the HTML minlength on the field. Uncompromised()
            // is deliberately NOT used: it calls out to an external API, and a
            // password reset must not fail because that host is unreachable.
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'The two passwords do not match.',
            'password.min'       => 'Use at least 8 characters.',
        ];
    }
}
