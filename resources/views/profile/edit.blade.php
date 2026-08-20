@extends('layouts.admin')

@section('title', 'Your profile')
@section('topbar-title', 'Your profile')

@section('content')

@section('page-title', 'Your profile')
@section('page-subtitle', 'Your sign-in details. Only an administrator can change which role an account holds.')

@if(session('success'))
    <div class="alert alert-success" role="status">
        <i class="fa-solid fa-circle-check"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

<div class="card-grid is-pair is-natural">

    {{-- ── Who you are ─────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-id-card"></i></div>
            <div class="card-header-text">
                <div class="card-title">Details</div>
                <div class="card-subtitle">The name and address you sign in with</div>
            </div>
        </div>
        <form method="POST" action="{{ route('profile.update') }}" novalidate>
            @csrf @method('PUT')
            <div class="card-body">
                <div class="form-grid cols-1">
                    <div class="form-group">
                        <label class="form-label" for="name">Name <span class="req">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" maxlength="255" required
                               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        <div class="field-help">Doubles as a sign-in identifier, so it has to be unique.</div>
                        @error('name')
                            <div class="field-error" id="name-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        <div class="field-help">Where a password-reset link would be sent.</div>
                        @error('email')
                            <div class="field-error" id="email-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                        @enderror
                    </div>

                    {{-- Read-only on purpose: an account may change who it is,
                         never what it is allowed to do. --}}
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <div>
                            <span class="status-badge {{ $user->role }}">{{ $user->role_label }}</span>
                            <div class="field-help" style="margin-top:6px">
                                Set by an administrator. See
                                <a href="{{ route('roles.index') }}">Roles &amp; Permissions</a> for what it allows.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Save details
                </button>
            </div>
        </form>
    </div>

    {{-- ── Password ────────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon is-warning"><i class="fa-solid fa-key"></i></div>
            <div class="card-header-text">
                <div class="card-title">Password</div>
                <div class="card-subtitle">Changing it signs out nothing else — it just takes effect next time</div>
            </div>
        </div>
        <form method="POST" action="{{ route('profile.password') }}" novalidate>
            @csrf @method('PUT')
            <div class="card-body">
                <div class="form-grid cols-1">
                    <div class="form-group">
                        <label class="form-label" for="current_password">Current password <span class="req">*</span></label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               autocomplete="current-password" required
                               @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror>
                        @error('current_password')
                            <div class="field-error" id="current-password-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New password <span class="req">*</span></label>
                        <input type="password" id="new_password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               minlength="8" autocomplete="new-password" required
                               @error('password') aria-invalid="true" aria-describedby="new-password-error" @enderror>
                        <div class="field-help">At least 8 characters, and different from the current one.</div>
                        @error('password')
                            <div class="field-error" id="new-password-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirm new password <span class="req">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>
            </div>
            <div class="card-footer is-split">
                <span class="card-footer-note">Forgotten it instead? <a href="{{ route('password.request') }}">Email yourself a reset link</a>.</span>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-key"></i> Change password
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
