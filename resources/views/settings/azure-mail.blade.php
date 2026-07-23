@extends('layouts.admin')

@section('title', 'Mail Settings')
@section('topbar-title', 'Mail Settings')

@push('styles')
<style>
.form-card {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 28px 32px; margin-bottom: 20px;
    max-width: 560px;
}
.form-grid.cols-1 { display: grid; grid-template-columns: 1fr; gap: 18px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.form-label .required { color: #DC2626; margin-left: 2px; }
.form-control {
    padding: 9px 13px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
    transition: border-color 0.18s; width: 100%; box-sizing: border-box;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.form-control:focus { border-color: var(--accent); }
.form-control.is-invalid { border-color: #DC2626; }
.invalid-feedback { font-size: 11px; color: #DC2626; margin-top: 3px; }
.form-hint { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
.form-actions { display: flex; gap: 10px; align-items: center; justify-content: flex-end; padding-top: 6px; }
.status-pill {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11.5px; font-weight: 700; padding: 4px 11px; border-radius: 20px;
    margin-bottom: 18px;
}
.status-pill.configured   { background: #ECFDF5; color: var(--success); }
.status-pill.unconfigured { background: #F1F5F9; color: var(--text-muted); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Mail Settings</h1>
        <p class="page-header-sub">Azure AD app registration used to send invoice, receipt, and reminder emails to tenants</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
@endif

<span class="status-pill {{ $setting->isConfigured() ? 'configured' : 'unconfigured' }}">
    <i class="fa-solid {{ $setting->isConfigured() ? 'fa-circle-check' : 'fa-circle-minus' }}"></i>
    {{ $setting->isConfigured() ? 'Configured' : 'Not configured yet' }}
</span>

<form method="POST" action="{{ route('settings.azure-mail.update') }}" novalidate>
    @csrf
    @method('PUT')

    <div class="form-card">
        <div class="form-grid cols-1">
            <div class="form-group">
                <label class="form-label" for="tenant_id">Azure AD Tenant ID <span class="required">*</span></label>
                <input type="text" id="tenant_id" name="tenant_id" class="form-control {{ $errors->has('tenant_id') ? 'is-invalid' : '' }}"
                       value="{{ old('tenant_id', $setting->tenant_id) }}" maxlength="255" required>
                @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="client_id">Application (Client) ID <span class="required">*</span></label>
                <input type="text" id="client_id" name="client_id" class="form-control {{ $errors->has('client_id') ? 'is-invalid' : '' }}"
                       value="{{ old('client_id', $setting->client_id) }}" maxlength="255" required>
                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="client_secret">Client Secret</label>
                <input type="password" id="client_secret" name="client_secret" class="form-control {{ $errors->has('client_secret') ? 'is-invalid' : '' }}"
                       placeholder="{{ $setting->client_secret ? 'Leave blank to keep current secret' : '' }}" maxlength="1000" autocomplete="new-password">
                <div class="form-hint">Stored encrypted. Only re-enter this to rotate the secret.</div>
                @error('client_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="from_address">From Address <span class="required">*</span></label>
                <input type="email" id="from_address" name="from_address" class="form-control {{ $errors->has('from_address') ? 'is-invalid' : '' }}"
                       value="{{ old('from_address', $setting->from_address) }}" maxlength="255" required>
                @error('from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Save Settings
        </button>
    </div>
</form>

@if($setting->isConfigured())
<form method="POST" action="{{ route('settings.azure-mail.test') }}" style="max-width:560px; margin-top:4px;">
    @csrf
    <div class="form-actions" style="justify-content:flex-start;">
        <button type="submit" class="btn btn-outline">
            <i class="fa-solid fa-paper-plane"></i> Send Test Email to {{ auth()->user()->email }}
        </button>
    </div>
</form>
@endif

@endsection
