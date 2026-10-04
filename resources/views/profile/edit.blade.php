@extends('layouts.admin')

@section('title', 'Your profile')
@section('topbar-title', 'Your profile')

@section('content')

@section('page-title', 'Your profile')
@section('page-subtitle', 'Your photo, sign-in details and password. Only an administrator can change which role an account holds.')

@if(session('success'))
    <div class="alert alert-success" role="status">
        <i class="fa-solid fa-circle-check"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

{{-- Equalised, not is-natural: these are two forms that each end in a footer
     button, so their actions belong on one line. Left as natural, the photo
     field made Details 160px taller than Password and left a dead gap under
     it with the Save footer reading as detached. --}}
<div class="card-grid is-pair">

    {{-- ── Who you are ─────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-id-card"></i></div>
            <div class="card-header-text">
                <div class="card-title">Details</div>
                <div class="card-subtitle">The name and address you sign in with</div>
            </div>
        </div>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" novalidate>
            @csrf @method('PUT')
            <div class="card-body">
                <div class="form-grid cols-1">
                    {{-- Photo first: it is the only field on this page that
                         other people see, and the preview is the actual circle
                         the account chip will draw, so what you check here is
                         what ships. --}}
                    <div class="form-group">
                        <label class="form-label" for="photo">Photo</label>
                        <div class="upload-row">
                            <div class="upload-preview is-round" id="photo-preview">
                                @if($user->photoUrl())
                                    <img src="{{ $user->photoUrl() }}" alt="Your current photo">
                                @else
                                    {{ $user->initial() }}
                                @endif
                            </div>
                            <div class="upload-fields">
                                <input type="file" id="photo" name="photo" data-preview="photo-preview"
                                       class="form-control @error('photo') is-invalid @enderror"
                                       accept=".jpg,.jpeg,.png,.webp"
                                       @error('photo') aria-invalid="true" aria-describedby="photo-error" @enderror>
                                <div class="field-help">JPG, PNG or WebP, at least 96 &times; 96 pixels, up to 2 MB. Shown in the account menu and the sidebar.</div>
                                @error('photo')
                                    <div class="field-error" id="photo-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
                                @enderror
                                @if($user->photo_path)
                                    <div class="form-check" style="margin-top:8px">
                                        <input type="checkbox" id="remove_photo" name="remove_photo" value="1">
                                        <label for="remove_photo">Remove my photo &mdash; go back to the initial</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

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

{{-- ── Account activity ────────────────────────────────────────────────────
     Outside the pair grid and full width: it is a list, not a form, and a third
     card inside a two-column grid leaves a hole beside it.

     Every figure here is already being written — LoginController audits
     signed_in / sign_in_failed / locked_out, and this controller audits
     password_changed and profile_updated. Until now only an Admin could see
     any of it, so the account it happened to could not check its own. --}}
<div class="card" style="margin-top:var(--sp-5)">
    <div class="card-header">
        <div class="card-header-icon is-info"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="card-header-text">
            <div class="card-title">Account activity</div>
            <div class="card-subtitle">Sign-ins and security changes on this account &mdash; only you and an administrator can see this</div>
        </div>
    </div>

    <div class="card-body">
        <div class="stats-grid is-triple">
            <div class="stat-tile">
                <div class="stat-tile-label"><i class="fa-solid fa-right-to-bracket"></i> Previous sign-in</div>
                {{-- The previous one, not the latest: the latest IS the session
                     reading this page, and telling you that you are signed in
                     now is not information. --}}
                <div class="stat-tile-value" style="font-size:15px">
                    @if($previousSignIn)
                        {{ $previousSignIn->created_at->format('d M Y, H:i') }}
                    @else
                        <span style="color:var(--text-muted)">&mdash;</span>
                    @endif
                </div>
                @if($previousSignIn?->ip_address)
                    <div class="field-help" style="font-family:var(--font-data)">from {{ $previousSignIn->ip_address }}</div>
                @elseif(! $previousSignIn)
                    <div class="field-help">This is the first sign-in on record.</div>
                @endif
            </div>

            <div class="stat-tile">
                <div class="stat-tile-label"><i class="fa-solid fa-key"></i> Password last changed</div>
                <div class="stat-tile-value" style="font-size:15px">
                    @if($lastPasswordChange)
                        {{ $lastPasswordChange->created_at->format('d M Y') }}
                    @else
                        <span style="color:var(--text-muted)">&mdash;</span>
                    @endif
                </div>
                <div class="field-help">
                    @if($lastPasswordChange)
                        {{ $lastPasswordChange->created_at->diffForHumans() }}
                    @else
                        Never changed since the account was created.
                    @endif
                </div>
            </div>

            <div class="stat-tile @if($failedAttempts > 0) is-danger @endif">
                <div class="stat-tile-label"><i class="fa-solid fa-triangle-exclamation"></i> Failed sign-ins</div>
                <div class="stat-tile-value">{{ number_format($failedAttempts) }}</div>
                <div class="field-help">
                    @if($failedAttempts > 0)
                        In the last 30 days. If none of these were you, change your password.
                    @else
                        None in the last 30 days.
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($events->isEmpty())
        <div class="card-body" style="padding-top:0">
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h4>Nothing recorded yet</h4>
                <p>Sign-ins and password changes will appear here as they happen.</p>
            </div>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Event</th>
                        <th>Detail</th>
                        <th>IP address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($events as $event)
                        <tr>
                            <td data-label="When">
                                <div style="font-weight:600;color:var(--text-primary)">{{ $event->created_at->format('d M Y, H:i') }}</div>
                                <div class="cell-muted">{{ $event->created_at->diffForHumans() }}</div>
                            </td>
                            <td data-label="Event">
                                <span class="status-badge {{ $event->action }}">{{ $event->action_label }}</span>
                            </td>
                            <td data-label="Detail" class="cell-muted">
                                @if($event->action === 'profile_updated' && ! empty($event->changes['photo']))
                                    Photo {{ $event->changes['photo'] }}
                                    @php $others = array_diff($event->changes['fields'] ?? [], ['photo']); @endphp
                                    @if($others) &middot; changed {{ implode(', ', $others) }} @endif
                                @elseif($event->action === 'profile_updated' && ! empty($event->changes['fields']))
                                    Changed {{ implode(', ', $event->changes['fields']) }}
                                @elseif($event->action === 'signed_in')
                                    {{ ($event->changes['remember'] ?? false) ? 'Stayed signed in' : 'Single session' }}
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td data-label="IP address" style="font-family:var(--font-data);font-size:var(--fs-sm)">
                                {{ $event->ip_address ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <span class="card-footer-note">The eight most recent events on this account.</span>
        </div>
    @endif
</div>

@endsection
