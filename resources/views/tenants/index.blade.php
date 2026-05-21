@extends('layouts.admin')

@section('title', 'Tenants')
@section('topbar-title', 'Tenants')

@push('styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: var(--shadow-sm);
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
    .stat-icon {
        width: 44px; height: 44px;
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
    }
    .stat-icon.gold  { background: var(--accent-dim); color: var(--accent); }
    .stat-icon.green { background: #ECFDF5; color: var(--success); }
    .stat-icon.blue  { background: #EFF6FF; color: var(--info); }
    .stat-val { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .stat-lbl { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

    /* ── FILTER BAR ─────────────────────────────────────── */
    .filter-bar {
        display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;
        padding: 16px 20px;
        background: var(--page-bg);
        border-bottom: 1px solid var(--card-border);
    }
    .filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 150px; }
    .filter-group label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .filter-group input, .filter-group select {
        padding: 8px 12px; font-size: 13px;
        border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
        background: var(--card-bg); color: var(--text-primary);
        font-family: 'Plus Jakarta Sans', sans-serif;
        outline: none; appearance: none; -webkit-appearance: none;
        transition: border-color 0.18s, box-shadow 0.18s;
    }
    .filter-group input:focus, .filter-group select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
    .filter-group select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px;
    }
    .filter-actions { display: flex; gap: 8px; align-items: flex-end; margin-left: auto; }

    /* ── TABLE ──────────────────────────────────────────── */
    .tenant-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700;
        flex-shrink: 0;
    }
    .tenant-avatar.individual { background: #ECFDF5; color: var(--success); }
    .tenant-avatar.company    { background: #EFF6FF; color: var(--info); }
    .tenant-name { font-weight: 600; font-size: 13.5px; color: var(--text-primary); }
    .tenant-sub  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .action-btns { display: flex; gap: 6px; }

    /* ── TABLE FOOTER ───────────────────────────────────── */
    .table-footer {
        padding: 14px 20px; border-top: 1px solid var(--card-border);
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    }
    .pagination { display: flex; gap: 4px; align-items: center; }
    .page-btn {
        width: 32px; height: 32px; border: 1.5px solid var(--card-border);
        background: var(--card-bg); border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 600; color: var(--text-secondary);
        cursor: pointer; text-decoration: none; transition: all 0.15s;
    }
    .page-btn:hover { background: var(--page-bg); color: var(--text-primary); }
    .page-btn.active { background: var(--accent); border-color: var(--accent); color: #0B1120; }
    .result-count { font-size: 13px; color: var(--text-muted); }
    .result-count strong { color: var(--text-primary); }
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-icon { width: 64px; height: 64px; background: var(--page-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--text-muted); margin: 0 auto 16px; }
    .empty-state h4 { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .empty-state p { font-size: 13px; color: var(--text-muted); }

    /* ── MODAL ──────────────────────────────────────────── */
    .modal-overlay {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(11,17,32,0.55); backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center; padding: 20px;
        opacity: 0; pointer-events: none; transition: opacity 0.25s ease;
    }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    .modal-box {
        background: var(--card-bg); border: 1px solid var(--card-border);
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(0,0,0,0.18), 0 8px 24px rgba(0,0,0,0.10);
        width: 100%; max-width: 560px; max-height: 90vh;
        display: flex; flex-direction: column;
        transform: translateY(20px) scale(0.98);
        transition: transform 0.3s cubic-bezier(0.22,1,0.36,1);
        overflow: hidden;
    }
    .modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
    .modal-header { padding: 20px 24px 16px; border-bottom: 1px solid var(--card-border); flex-shrink: 0; }
    .modal-header-top { display: flex; align-items: center; gap: 12px; }
    .modal-header-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: var(--accent-dim); border: 1px solid rgba(232,184,109,0.25);
        display: flex; align-items: center; justify-content: center;
        color: var(--accent); font-size: 16px; flex-shrink: 0;
    }
    .modal-header-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .modal-header-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .modal-close-btn {
        margin-left: auto; width: 32px; height: 32px; border-radius: var(--radius-sm);
        border: 1.5px solid var(--card-border); background: transparent;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        color: var(--text-muted); font-size: 13px; transition: all 0.15s;
    }
    .modal-close-btn:hover { background: var(--page-bg); color: var(--text-primary); }
    .modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
    .modal-body::-webkit-scrollbar { width: 4px; }
    .modal-body::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
    .modal-footer {
        padding: 16px 24px; border-top: 1px solid var(--card-border);
        display: flex; align-items: center; justify-content: flex-end; gap: 10px;
        flex-shrink: 0;
    }

    /* ── MODAL FIELDS ───────────────────────────────────── */
    .mfield-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 16px 20px; }
    .mfield-grid .span-full { grid-column: 1/-1; }
    .mfield-group { display: flex; flex-direction: column; }
    .mfield-label {
        font-size: 11px; font-weight: 700; color: var(--text-secondary);
        letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 6px;
        display: flex; align-items: center; gap: 3px;
    }
    .mfield-label .req { color: var(--danger); font-size: 13px; line-height: 1; }
    .mfield-wrap { position: relative; }
    .mfield-icon {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        color: var(--text-muted); font-size: 12px; pointer-events: none; transition: color 0.2s;
    }
    .mfield-wrap:focus-within .mfield-icon { color: var(--accent); }
    .has-micon input, .has-micon select { padding-left: 34px; }
    .mfield-input, .mfield-select {
        width: 100%; padding: 9.5px 13px;
        border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
        background: #fff; color: var(--text-primary);
        font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px;
        outline: none; appearance: none; -webkit-appearance: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .mfield-input:focus, .mfield-select:focus {
        border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: #FFFDF8;
    }
    .mfield-input.is-invalid, .mfield-select.is-invalid { border-color: var(--danger); background: #FFF8F8; }
    .mfield-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%2394A3B8' d='M5 7L0.669873 2.5L9.33013 2.5L5 7Z'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; padding-right: 34px;
    }
    .mfield-error { display: flex; align-items: center; gap: 4px; margin-top: 5px; font-size: 11px; color: var(--danger); font-weight: 500; }

    /* ── TYPE TOGGLE ────────────────────────────────────── */
    .type-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 4px; }
    .type-option { position: relative; }
    .type-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .type-option label {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px; border-radius: var(--radius-sm);
        border: 1.5px solid var(--card-border); cursor: pointer;
        font-size: 13px; font-weight: 600; color: var(--text-secondary);
        transition: all 0.18s; background: var(--page-bg);
        text-transform: none; letter-spacing: 0;
    }
    .type-option label .ti { font-size: 16px; }
    .type-option input:checked + label {
        border-color: var(--accent); background: var(--accent-dim);
        color: var(--text-primary);
        box-shadow: 0 0 0 3px var(--accent-dim);
    }
    .type-option label:hover { border-color: #B0BCCF; background: #F8FAFC; }

    @media (max-width: 600px) {
        .modal-box { max-height: 100vh; border-radius: 0; max-width: 100%; }
        .modal-overlay { padding: 0; align-items: flex-end; }
        .mfield-grid { grid-template-columns: 1fr; }
        .mfield-grid .span-full { grid-column: span 1; }
        .type-toggle { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Tenants</span>
        </div>
        <h1 class="page-header-title">Tenants</h1>
        <p class="page-header-sub">Manage all tenant profiles and contact records</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" onclick="openTenantModal()">
            <i class="fa-solid fa-plus"></i> Add Tenant
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        {{ session('success') }}
    </div>
@endif

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-users"></i></div>
        <div><div class="stat-val">{{ $tenants->total() }}</div><div class="stat-lbl">Total Tenants</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-user"></i></div>
        <div><div class="stat-val">{{ $stats['individual'] ?? 0 }}</div><div class="stat-lbl">Individuals</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-building-user"></i></div>
        <div><div class="stat-val">{{ $stats['company'] ?? 0 }}</div><div class="stat-lbl">Companies</div></div>
    </div>
</div>

{{-- TABLE CARD --}}
<div class="card" style="overflow:hidden;">

    <form method="GET" action="{{ route('tenants.index') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group" style="flex:1;min-width:220px;">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Name, email, ID / CR number…" oninput="debounceSubmit()">
            </div>
            <div class="filter-group">
                <label>Tenant Type</label>
                <select name="tenant_type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="individual" {{ request('tenant_type') === 'individual' ? 'selected' : '' }}>Individual</option>
                    <option value="company"    {{ request('tenant_type') === 'company'    ? 'selected' : '' }}>Company</option>
                </select>
            </div>
            <div class="filter-actions">
                @if(request()->hasAny(['search','tenant_type']))
                    <a href="{{ route('tenants.index') }}" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Type</th>
                    <th>ID / CR Number</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Nationality / Country</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="tenant-avatar {{ $tenant->tenant_type }}">
                                {{ strtoupper(substr($tenant->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="tenant-name">{{ $tenant->name }}</div>
                                <div class="tenant-sub">Added {{ $tenant->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($tenant->tenant_type === 'individual')
                            <span class="badge badge-green"><i class="fa-solid fa-user"></i> Individual</span>
                        @else
                            <span class="badge badge-blue"><i class="fa-solid fa-building-user"></i> Company</span>
                        @endif
                    </td>
                    <td>
                        @if($tenant->id_cr_number)
                            <span style="font-family:'Outfit',sans-serif;font-weight:600;font-size:13px;">{{ $tenant->id_cr_number }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($tenant->phone)
                            <a href="tel:{{ $tenant->phone }}" style="color:var(--text-primary);text-decoration:none;">{{ $tenant->phone }}</a>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($tenant->email)
                            <a href="mailto:{{ $tenant->email }}" style="color:var(--info);text-decoration:none;font-size:13px;">{{ $tenant->email }}</a>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($tenant->nationality_country)
                            <span style="font-size:13px;">{{ $tenant->nationality_country }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns" style="justify-content:flex-end;">
                            <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-outline btn-sm" title="View">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('tenants.destroy', $tenant) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($tenant->name) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                        <h4>No tenants found</h4>
                        <p>Try adjusting your filters or
                            <button type="button" onclick="openTenantModal()" style="background:none;border:none;cursor:pointer;color:var(--accent);font-weight:600;padding:0;">add a new tenant</button>.
                        </p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $tenants->firstItem() ?? 0 }}–{{ $tenants->lastItem() ?? 0 }}</strong>
            of <strong>{{ $tenants->total() }}</strong> tenants
        </div>
        <div class="pagination">
            @if($tenants->onFirstPage())
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></span>
            @else
                <a href="{{ $tenants->previousPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></a>
            @endif
            @foreach($tenants->getUrlRange(max(1,$tenants->currentPage()-2), min($tenants->lastPage(),$tenants->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="page-btn {{ $page == $tenants->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            @if($tenants->hasMorePages())
                <a href="{{ $tenants->nextPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></a>
            @else
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
            @endif
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════
     ADD TENANT MODAL
═══════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="tenantModal" role="dialog" aria-modal="true" aria-labelledby="tenantModalTitle">
    <div class="modal-box">

        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-solid fa-user-plus"></i></div>
                <div>
                    <div class="modal-header-title" id="tenantModalTitle">Add New Tenant</div>
                    <div class="modal-header-sub">Enter the tenant's profile information below</div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeTenantModal()" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('tenants.store') }}" id="addTenantForm" novalidate>
                @csrf

                {{-- TENANT TYPE --}}
                <div style="margin-bottom:20px;">
                    <div class="mfield-label" style="margin-bottom:10px;">Tenant Type <span class="req">*</span></div>
                    <div class="type-toggle">
                        <div class="type-option">
                            <input type="radio" name="tenant_type" id="type_individual" value="individual"
                                {{ old('tenant_type', 'individual') === 'individual' ? 'checked' : '' }} required>
                            <label for="type_individual">
                                <i class="fa-solid fa-user ti" style="color:var(--success);"></i>
                                Individual
                            </label>
                        </div>
                        <div class="type-option">
                            <input type="radio" name="tenant_type" id="type_company" value="company"
                                {{ old('tenant_type') === 'company' ? 'checked' : '' }}>
                            <label for="type_company">
                                <i class="fa-solid fa-building-user ti" style="color:var(--info);"></i>
                                Company
                            </label>
                        </div>
                    </div>
                    @error('tenant_type')
                        <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="mfield-grid">

                    {{-- NAME --}}
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Full Name / Company Name <span class="req">*</span></label>
                        <div class="mfield-wrap has-micon">
                            <i class="fa-solid fa-user mfield-icon"></i>
                            <input type="text" name="name"
                                class="mfield-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                value="{{ old('name') }}"
                                placeholder="e.g. Ahmed Al-Khalifa"
                                required maxlength="255" autofocus>
                        </div>
                        @error('name')
                            <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ID / CR NUMBER --}}
                    <div class="mfield-group">
                        <label class="mfield-label">ID / CR Number</label>
                        <div class="mfield-wrap has-micon">
                            <i class="fa-solid fa-id-card mfield-icon"></i>
                            <input type="text" name="id_cr_number"
                                class="mfield-input {{ $errors->has('id_cr_number') ? 'is-invalid' : '' }}"
                                value="{{ old('id_cr_number') }}"
                                placeholder="e.g. 840912345" maxlength="100">
                        </div>
                        @error('id_cr_number')
                            <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- NATIONALITY / COUNTRY --}}
                    <div class="mfield-group">
                        <label class="mfield-label">Nationality / Country</label>
                        <div class="mfield-wrap has-micon">
                            <i class="fa-solid fa-earth-americas mfield-icon"></i>
                            <input type="text" name="nationality_country"
                                class="mfield-input {{ $errors->has('nationality_country') ? 'is-invalid' : '' }}"
                                value="{{ old('nationality_country') }}"
                                placeholder="e.g. Bahraini" maxlength="100">
                        </div>
                        @error('nationality_country')
                            <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- PHONE --}}
                    <div class="mfield-group">
                        <label class="mfield-label">Phone</label>
                        <div class="mfield-wrap has-micon">
                            <i class="fa-solid fa-phone mfield-icon"></i>
                            <input type="text" name="phone"
                                class="mfield-input {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                value="{{ old('phone') }}"
                                placeholder="+973 3300 0000" maxlength="50">
                        </div>
                        @error('phone')
                            <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div class="mfield-group">
                        <label class="mfield-label">Email</label>
                        <div class="mfield-wrap has-micon">
                            <i class="fa-solid fa-envelope mfield-icon"></i>
                            <input type="email" name="email"
                                class="mfield-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                value="{{ old('email') }}"
                                placeholder="tenant@email.com" maxlength="255">
                        </div>
                        @error('email')
                            <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeTenantModal()">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button type="submit" form="addTenantForm" class="btn btn-primary" id="tenantSubmitBtn" onclick="handleSubmit(this)">
                <i class="fa-solid fa-floppy-disk"></i> Create Tenant
            </button>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
let debounceTimer;
function debounceSubmit() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
}

function openTenantModal() {
    document.getElementById('tenantModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        const first = document.querySelector('#tenantModal input[name="name"]');
        if (first) first.focus();
    }, 320);
}

function closeTenantModal() {
    document.getElementById('tenantModal').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('tenantModal').addEventListener('click', function(e) {
    if (e.target === this) closeTenantModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('tenantModal').classList.contains('open')) {
        closeTenantModal();
    }
});

function handleSubmit(btn) {
    const form = document.getElementById('addTenantForm');
    const name = form.querySelector('[name="name"]');
    if (!name.value.trim()) {
        name.classList.add('is-invalid');
        name.focus();
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    form.submit();
}

@if($errors->any())
openTenantModal();
@endif
</script>
@endpush
