@extends('layouts.admin')

@section('title', 'Branding')
@section('topbar-title', 'Branding')

@section('page-title', 'Branding')
@section('page-subtitle', 'Site identity shown across the shell, login screens, and printed documents')

@push('styles')
<style>
.branding-upload { display: flex; flex-wrap: wrap; gap: var(--sp-4); align-items: flex-start; }
.branding-preview {
    width: 88px; height: 88px; flex: none;
    border: 1.5px solid var(--card-border); border-radius: var(--radius-sm);
    background: var(--page-bg-alt);
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.branding-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
.branding-preview i { font-size: var(--fs-xl); color: var(--text-muted); }
.branding-upload-fields { flex: 1; min-width: 200px; }
.color-field { display: flex; align-items: center; gap: var(--sp-2); }
.color-field input[type="color"] {
    width: 44px; height: var(--h-control); padding: 2px; flex: none;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); cursor: pointer;
}
.color-field input[type="text"] { flex: 1; font-family: var(--font-data); }
</style>
@endpush

@section('content')

<form method="POST" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    <div class="section-stack">

        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-signature"></i></div>
                <div class="card-header-text">
                    <div class="card-title">Site Identity</div>
                    <div class="card-subtitle">Names shown in the sidebar, browser tab, and on printed documents</div>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid cols-2">
                    <div class="form-group">
                        <label class="form-label" for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name"
                               class="form-control {{ $errors->has('site_name') ? 'is-invalid' : '' }}"
                               value="{{ old('site_name', $setting->site_name) }}" maxlength="100"
                               placeholder="{{ config('app.name') }}">
                        <p class="field-help">Shown in the browser tab and sidebar brand mark. Falls back to "{{ config('app.name') }}" when blank.</p>
                        @error('site_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name"
                               class="form-control {{ $errors->has('company_name') ? 'is-invalid' : '' }}"
                               value="{{ old('company_name', $setting->company_name) }}" maxlength="150"
                               placeholder="e.g. Promoseven Holdings">
                        <p class="field-help">Used on invoices, receipts, and other exported documents.</p>
                        @error('company_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group col-span-full">
                        <label class="form-label" for="tagline">Tagline</label>
                        <input type="text" id="tagline" name="tagline"
                               class="form-control {{ $errors->has('tagline') ? 'is-invalid' : '' }}"
                               value="{{ old('tagline', $setting->tagline) }}" maxlength="150"
                               placeholder="e.g. Management Suite">
                        <p class="field-help">Short line shown under the site name in the sidebar.</p>
                        @error('tagline')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-image"></i></div>
                <div class="card-header-text">
                    <div class="card-title">Logo &amp; Favicon</div>
                    <div class="card-subtitle">Replace the app's default artwork</div>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid cols-2">
                    <div class="form-group">
                        <label class="form-label" for="logo">Logo</label>
                        <div class="branding-upload">
                            <div class="branding-preview" id="logo-preview">
                                @if($setting->logoUrl())
                                    <img src="{{ $setting->logoUrl() }}" alt="Current logo">
                                @else
                                    <i class="fa-solid fa-image" aria-hidden="true"></i>
                                @endif
                            </div>
                            <div class="branding-upload-fields">
                                <input type="file" id="logo" name="logo" data-preview="logo-preview"
                                       class="form-control {{ $errors->has('logo') ? 'is-invalid' : '' }}"
                                       accept=".png,.jpg,.jpeg,.svg">
                                <p class="field-help">PNG, JPG, or SVG. Up to 2MB.</p>
                                @error('logo')<p class="field-error">{{ $message }}</p>@enderror
                                @if($setting->logo_path)
                                <div class="form-check">
                                    <input type="checkbox" id="remove_logo" name="remove_logo" value="1">
                                    <label for="remove_logo">Remove current logo</label>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="favicon">Favicon</label>
                        <div class="branding-upload">
                            <div class="branding-preview" id="favicon-preview">
                                @if($setting->faviconUrl())
                                    <img src="{{ $setting->faviconUrl() }}" alt="Current favicon">
                                @else
                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                @endif
                            </div>
                            <div class="branding-upload-fields">
                                <input type="file" id="favicon" name="favicon" data-preview="favicon-preview"
                                       class="form-control {{ $errors->has('favicon') ? 'is-invalid' : '' }}"
                                       accept=".png,.ico">
                                <p class="field-help">PNG or ICO. Up to 512KB.</p>
                                @error('favicon')<p class="field-error">{{ $message }}</p>@enderror
                                @if($setting->favicon_path)
                                <div class="form-check">
                                    <input type="checkbox" id="remove_favicon" name="remove_favicon" value="1">
                                    <label for="remove_favicon">Remove current favicon</label>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-palette"></i></div>
                <div class="card-header-text">
                    <div class="card-title">Brand Colors</div>
                    <div class="card-subtitle">Accent colors used across the shell</div>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid cols-2">
                    <div class="form-group">
                        <label class="form-label" for="primary_color">Primary Color</label>
                        <div class="color-field">
                            <input type="color" aria-label="Pick primary color"
                                   value="{{ old('primary_color', $setting->primary_color) ?: '#0B1120' }}">
                            <input type="text" id="primary_color" name="primary_color"
                                   class="form-control {{ $errors->has('primary_color') ? 'is-invalid' : '' }}"
                                   value="{{ old('primary_color', $setting->primary_color) }}"
                                   maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" placeholder="#0B1120">
                        </div>
                        <p class="field-help">Hex color, e.g. #0B1120.</p>
                        @error('primary_color')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="secondary_color">Secondary Color</label>
                        <div class="color-field">
                            <input type="color" aria-label="Pick secondary color"
                                   value="{{ old('secondary_color', $setting->secondary_color) ?: '#E8B86D' }}">
                            <input type="text" id="secondary_color" name="secondary_color"
                                   class="form-control {{ $errors->has('secondary_color') ? 'is-invalid' : '' }}"
                                   value="{{ old('secondary_color', $setting->secondary_color) }}"
                                   maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" placeholder="#E8B86D">
                        </div>
                        <p class="field-help">Hex color, e.g. #E8B86D.</p>
                        @error('secondary_color')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Save Branding
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.querySelectorAll('.color-field').forEach(function (field) {
    var picker = field.querySelector('input[type="color"]');
    var text = field.querySelector('input[type="text"]');
    if (!picker || !text) return;
    picker.addEventListener('input', function () { text.value = picker.value; });
    text.addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
    });
});

document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    input.addEventListener('change', function () {
        var file = input.files[0];
        var box = document.getElementById(input.dataset.preview);
        if (!file || !box) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            box.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endpush
