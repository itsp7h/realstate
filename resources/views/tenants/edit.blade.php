@extends('layouts.admin')

@section('title', 'Edit Tenant — ' . $tenant->name)
@section('topbar-title', 'Edit Tenant')

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('tenants.index') }}">Tenants</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Edit</span>
        </div>
        <h1 class="page-header-title">Edit Tenant</h1>
        <p class="page-header-sub">Update profile information for {{ $tenant->name }}</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-outline">
            <i class="fa-regular fa-eye"></i> View Profile
        </a>
        <a href="{{ route('tenants.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Tenants
        </a>
    </div>
</div>

<form method="POST" action="{{ route('tenants.update', $tenant) }}" novalidate>
    @csrf @method('PUT')

    <div class="section-stack">

        {{-- TENANT TYPE --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-user-tag"></i></div>
                <div>
                    <h3>Tenant Type</h3>
                    <p>Is this tenant an individual person or a company?</p>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:400px;">
                    @foreach(['individual' => ['fa-user','var(--success)','Individual'], 'company' => ['fa-building-user','var(--info)','Company']] as $val => [$icon, $color, $label])
                    <div style="position:relative;">
                        <input type="radio" name="tenant_type" id="type_{{ $val }}" value="{{ $val }}"
                            {{ old('tenant_type', $tenant->tenant_type) === $val ? 'checked' : '' }}
                            style="position:absolute;opacity:0;width:0;height:0;" required>
                        <label for="type_{{ $val }}" style="
                            display:flex;align-items:center;gap:10px;
                            padding:13px 16px;border-radius:var(--radius-sm);
                            border:1.5px solid var(--card-border);cursor:pointer;
                            font-size:13.5px;font-weight:600;color:var(--text-secondary);
                            transition:all 0.18s;background:var(--page-bg);">
                            <i class="fa-solid {{ $icon }}" style="color:{{ $color }};font-size:16px;"></i>
                            {{ $label }}
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('tenant_type')
                    <div style="margin-top:8px;font-size:11px;color:var(--danger);display:flex;align-items:center;gap:4px;">
                        <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                    </div>
                @enderror
            </div>
        </div>

        {{-- IDENTITY --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-id-card"></i></div>
                <div>
                    <h3>Identity</h3>
                    <p>Name and identification details</p>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid">

                    <div class="form-group col-span-2">
                        <label>Full Name / Company Name <span class="required">*</span></label>
                        <input type="text" name="name"
                            class="{{ $errors->has('name') ? 'error' : '' }}"
                            value="{{ old('name', $tenant->name) }}"
                            placeholder="e.g. Ahmed Al-Khalifa"
                            required maxlength="255">
                        @error('name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>ID / CR Number</label>
                        <input type="text" name="id_cr_number"
                            class="{{ $errors->has('id_cr_number') ? 'error' : '' }}"
                            value="{{ old('id_cr_number', $tenant->id_cr_number) }}"
                            placeholder="e.g. 840912345" maxlength="100">
                        @error('id_cr_number') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Nationality / Country</label>
                        <input type="text" name="nationality_country"
                            class="{{ $errors->has('nationality_country') ? 'error' : '' }}"
                            value="{{ old('nationality_country', $tenant->nationality_country) }}"
                            placeholder="e.g. Bahraini" maxlength="100">
                        @error('nationality_country') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- CONTACT --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-address-book"></i></div>
                <div>
                    <h3>Contact Information</h3>
                    <p>Phone number and email address</p>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone"
                            class="{{ $errors->has('phone') ? 'error' : '' }}"
                            value="{{ old('phone', $tenant->phone) }}"
                            placeholder="+973 3300 0000" maxlength="50">
                        @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email"
                            class="{{ $errors->has('email') ? 'error' : '' }}"
                            value="{{ old('email', $tenant->email) }}"
                            placeholder="tenant@email.com" maxlength="255">
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group col-span-2">
                        <label>Address</label>
                        <input type="text" name="address"
                            class="{{ $errors->has('address') ? 'error' : '' }}"
                            value="{{ old('address', $tenant->address) }}"
                            placeholder="e.g. MP 2, Bldg# 233, Road# 3332, Block# 333, Bahrain" maxlength="500">
                        @error('address') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div style="display:flex;gap:10px;justify-content:flex-end;padding-bottom:8px;">
            <a href="{{ route('tenants.index') }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
// Sync radio label styles on load and change
function syncTypeLabels() {
    document.querySelectorAll('input[name="tenant_type"]').forEach(radio => {
        const label = document.querySelector(`label[for="${radio.id}"]`);
        if (!label) return;
        if (radio.checked) {
            label.style.borderColor = 'var(--accent)';
            label.style.background  = 'var(--accent-dim)';
            label.style.color       = 'var(--text-primary)';
            label.style.boxShadow   = '0 0 0 3px var(--accent-dim)';
        } else {
            label.style.borderColor = 'var(--card-border)';
            label.style.background  = 'var(--page-bg)';
            label.style.color       = 'var(--text-secondary)';
            label.style.boxShadow   = 'none';
        }
    });
}
document.querySelectorAll('input[name="tenant_type"]').forEach(r => r.addEventListener('change', syncTypeLabels));
syncTypeLabels();
</script>
@endpush
