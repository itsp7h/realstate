<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_name'       => ['nullable', 'string', 'max:100'],
            'company_name'    => ['nullable', 'string', 'max:150'],
            'tagline'         => ['nullable', 'string', 'max:150'],
            // Printed on tenant-facing documents, so it is validated as a real
            // address rather than free text. 255 matches the column.
            'company_email'   => ['nullable', 'string', 'email:rfc', 'max:255'],
            'logo'            => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'favicon'         => ['nullable', 'image', 'mimes:png,ico', 'max:512'],
            'primary_color'   => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'remove_logo'     => ['nullable', 'boolean'],
            'remove_favicon'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.image'              => 'The logo must be an image file.',
            'logo.mimes'              => 'The logo must be a PNG, JPG, or SVG file.',
            'logo.max'                => 'The logo must not be larger than 2MB.',
            'favicon.image'           => 'The favicon must be an image file.',
            'favicon.mimes'           => 'The favicon must be a PNG or ICO file.',
            'favicon.max'             => 'The favicon must not be larger than 512KB.',
            'company_email.email'     => 'The contact email must be a valid email address (e.g. name@company.com).',
            'company_email.max'       => 'The contact email must not be longer than 255 characters.',
            'primary_color.regex'     => 'The primary color must be a valid hex color (e.g. #1A2B3C).',
            'secondary_color.regex'   => 'The secondary color must be a valid hex color (e.g. #1A2B3C).',
        ];
    }
}
