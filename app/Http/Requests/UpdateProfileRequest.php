<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Your own name and email. Notably absent: `role`.
 *
 * The users resource is admin-only, and self-service must not become a way
 * around that — an account editing itself may change who it is, never what it
 * is allowed to do.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->user()->id;

        return [
            // users.name carries a unique index too, so it has to ignore self
            // the same way email does.
            'name'  => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'  => 'Someone else already signs in with that name.',
            'email.unique' => 'That email address is already on another account.',
        ];
    }
}
