<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingImageRequest extends FormRequest
{
    /** Per-photo cap in kilobytes — mirrored by data-max-kb on the upload form. */
    public const MAX_KB = 4096;

    /** Photos per upload — mirrored by data-max-files on the upload form. */
    public const MAX_FILES = 10;

    public const MIMES = ['jpeg', 'png', 'webp'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images'   => ['required', 'array', 'max:' . self::MAX_FILES],
            'images.*' => ['required', 'image', 'mimes:' . implode(',', self::MIMES), 'max:' . self::MAX_KB],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Choose at least one photo to upload.',
            'images.array'    => 'Choose at least one photo to upload.',
            'images.max'      => 'You can upload up to ' . self::MAX_FILES . ' photos at a time.',
            // PHP drops a file over upload_max_filesize before Laravel sees it.
            'images.*.uploaded' => ':attribute couldn\'t be uploaded. It is larger than 4 MB.',
            'images.*.required' => ':attribute couldn\'t be uploaded. It is larger than 4 MB.',
            'images.*.image'    => ':attribute isn\'t a JPG, PNG or WEBP image.',
            'images.*.mimes'    => ':attribute isn\'t a JPG, PNG or WEBP image.',
            'images.*.max'      => ':attribute is larger than 4 MB. Compress or resize it and try again.',
        ];
    }

    /** Name each photo by its file name, so the message says which one failed. */
    public function attributes(): array
    {
        $names = [];
        foreach ((array) $this->file('images', []) as $i => $file) {
            $names["images.$i"] = '"' . $file->getClientOriginalName() . '"';
        }

        return $names;
    }

    /** Always land back on the Photos tab, where the errors are shown. */
    protected function getRedirectUrl(): string
    {
        return route('buildings.show', [$this->route('building'), 'tab' => 'photos']);
    }
}
