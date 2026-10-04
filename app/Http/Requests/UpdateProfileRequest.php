<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Your own name, email and photo. Notably absent: `role`.
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

            // An avatar is displayed at 44px at most, so 2MB is already
            // generous; the dimension floor is what stops a 16px favicon being
            // stretched across the account chip. mimes AND image: the first
            // checks the extension, the second that the bytes really decode as
            // an image.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=96,min_height=96'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'  => 'Someone else already signs in with that name.',
            'email.unique' => 'That email address is already on another account.',
            'photo.image'      => 'That file is not an image.',
            'photo.mimes'      => 'Use a JPG, PNG or WebP image.',
            'photo.max'        => 'Keep the image under 2 MB.',
            'photo.dimensions' => 'Use an image at least 96 × 96 pixels — smaller ones look blurred in the account menu.',
        ];
    }
}
