@extends('layouts.admin')

@section('title', $record ? 'Edit User' : 'New User')
@section('topbar-title', 'Users')

@push('styles')
<style>
.form-card {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 28px 32px; margin-bottom: 20px;
    max-width: 560px;
}
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
.form-grid.cols-1 { grid-template-columns: 1fr; }
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
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">{{ $record ? 'Edit User' : 'New User' }}</h1>
        <p class="page-header-sub">{{ $record ? 'Update this account\'s details or role' : 'Create a new account that can sign in to this system' }}</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('users.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
</div>

<form method="POST" action="{{ $record ? route('users.update', $record) : route('users.store') }}" novalidate>
    @csrf
    @if($record) @method('PUT') @endif

    <div class="form-card">
        <div class="form-grid cols-1">
            <div class="form-group">
                <label class="form-label" for="name">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                       value="{{ old('name', $record?->name) }}" maxlength="255" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                       value="{{ old('email', $record?->email) }}" maxlength="255" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="role">Role <span class="required">*</span></label>
                <select id="role" name="role" class="form-control {{ $errors->has('role') ? 'is-invalid' : '' }}" required>
                    <option value="">— Select —</option>
                    @foreach(['admin' => 'Admin', 'user' => 'User', 'maintenance' => 'Maintenance'] as $value => $label)
                    <option value="{{ $value }}" {{ old('role', $record?->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-hint">Only Admin can delete records, view Reports, and manage Users. Maintenance accounts can only access Maintenance Requests.</div>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    Password @if(! $record)<span class="required">*</span>@endif
                </label>
                <input type="password" id="password" name="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                       placeholder="{{ $record ? 'Leave blank to keep current password' : '' }}" {{ $record ? '' : 'required' }}>
                <div class="form-hint">At least 8 characters.</div>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">
                    Confirm Password @if(! $record)<span class="required">*</span>@endif
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                       {{ $record ? '' : 'required' }}>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('users.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid {{ $record ? 'fa-floppy-disk' : 'fa-plus' }}"></i> {{ $record ? 'Save Changes' : 'Create User' }}
        </button>
    </div>
</form>

@endsection
