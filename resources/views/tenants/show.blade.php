@extends('layouts.admin')

@section('title', $tenant->name . ' — Tenant Profile')
@section('topbar-title', 'Tenant Profile')

@push('styles')
<style>
    .profile-hero {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        padding: 28px;
        display: flex;
        align-items: center;
        gap: 22px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .profile-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800;
        flex-shrink: 0;
        border: 3px solid var(--card-border);
    }
    .profile-avatar.individual { background: #ECFDF5; color: var(--success); border-color: #A7F3D0; }
    .profile-avatar.company    { background: #EFF6FF; color: var(--info);    border-color: #BFDBFE; }
    .profile-name { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .profile-meta { display: flex; align-items: center; gap: 10px; margin-top: 6px; flex-wrap: wrap; }
    .profile-actions { margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap; }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }
    .detail-item {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        padding: 18px 20px;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }
    .detail-icon {
        width: 38px; height: 38px; border-radius: var(--radius-sm);
        background: var(--accent-dim); color: var(--accent);
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }
    .detail-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 4px; }
    .detail-value { font-size: 14px; font-weight: 600; color: var(--text-primary); word-break: break-all; }
    .detail-value.empty { color: var(--text-muted); font-weight: 400; font-style: italic; }
    .detail-value a { color: var(--info); text-decoration: none; }
    .detail-value a:hover { text-decoration: underline; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('tenants.index') }}">Tenants</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Profile</span>
        </div>
        <h1 class="page-header-title">Tenant Profile</h1>
        <p class="page-header-sub">Full details for this tenant record</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('tenants.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

{{-- PROFILE HERO --}}
<div class="profile-hero">
    <div class="profile-avatar {{ $tenant->tenant_type }}">
        {{ strtoupper(substr($tenant->name, 0, 1)) }}
    </div>
    <div>
        <div class="profile-name">{{ $tenant->name }}</div>
        <div class="profile-meta">
            @if($tenant->tenant_type === 'individual')
                <span class="badge badge-green"><i class="fa-solid fa-user"></i> Individual</span>
            @else
                <span class="badge badge-blue"><i class="fa-solid fa-building-user"></i> Company</span>
            @endif
            @if($tenant->nationality_country)
                <span class="badge badge-gray"><i class="fa-solid fa-earth-americas"></i> {{ $tenant->nationality_country }}</span>
            @endif
            <span style="font-size:12px;color:var(--text-muted);">
                <i class="fa-regular fa-clock"></i> Added {{ $tenant->created_at->format('d M Y') }}
            </span>
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-primary">
            <i class="fa-regular fa-pen-to-square"></i> Edit Profile
        </a>
        <form method="POST" action="{{ route('tenants.destroy', $tenant) }}"
              onsubmit="return confirm('Delete {{ addslashes($tenant->name) }}? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fa-regular fa-trash-can"></i> Delete
            </button>
        </form>
    </div>
</div>

{{-- DETAIL CARDS --}}
<div class="detail-grid">

    <div class="detail-item">
        <div class="detail-icon"><i class="fa-solid fa-id-card"></i></div>
        <div>
            <div class="detail-label">ID / CR Number</div>
            <div class="detail-value {{ $tenant->id_cr_number ? '' : 'empty' }}">
                {{ $tenant->id_cr_number ?? 'Not provided' }}
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon"><i class="fa-solid fa-phone"></i></div>
        <div>
            <div class="detail-label">Phone</div>
            <div class="detail-value {{ $tenant->phone ? '' : 'empty' }}">
                @if($tenant->phone)
                    <a href="tel:{{ $tenant->phone }}">{{ $tenant->phone }}</a>
                @else
                    Not provided
                @endif
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon" style="background:#EFF6FF;color:var(--info);"><i class="fa-solid fa-envelope"></i></div>
        <div>
            <div class="detail-label">Email Address</div>
            <div class="detail-value {{ $tenant->email ? '' : 'empty' }}">
                @if($tenant->email)
                    <a href="mailto:{{ $tenant->email }}">{{ $tenant->email }}</a>
                @else
                    Not provided
                @endif
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon" style="background:#ECFDF5;color:var(--success);"><i class="fa-solid fa-earth-americas"></i></div>
        <div>
            <div class="detail-label">Nationality / Country</div>
            <div class="detail-value {{ $tenant->nationality_country ? '' : 'empty' }}">
                {{ $tenant->nationality_country ?? 'Not provided' }}
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon"><i class="fa-regular fa-calendar-plus"></i></div>
        <div>
            <div class="detail-label">Created At</div>
            <div class="detail-value">{{ $tenant->created_at->format('d M Y, H:i') }}</div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon"><i class="fa-regular fa-calendar-check"></i></div>
        <div>
            <div class="detail-label">Last Updated</div>
            <div class="detail-value">{{ $tenant->updated_at->format('d M Y, H:i') }}</div>
        </div>
    </div>

</div>

@endsection
