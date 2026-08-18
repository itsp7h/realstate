@extends('layouts.admin')

@section('title', 'Tenants')
@section('topbar-title', 'Tenants')

@push('styles')
<style>
    /* ── FILTER BAR ─────────────────────────────────────── */
    .filter-bar {
        display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;
        padding: 16px 20px;
        background: var(--page-bg);
        border-bottom: 1px solid var(--card-border);
    }

    /* ── TABLE ──────────────────────────────────────────── */
    .tenant-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700;
        flex-shrink: 0;
    }
    .tenant-avatar.individual { background: var(--tone-success-bg); color:var(--tone-success-fg); }
    .tenant-avatar.company    { background: var(--tone-info-bg); color:var(--tone-info-fg); }
    .tenant-name { font-weight: 600; font-size: 13.5px; color: var(--text-primary); }
    .tenant-sub  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    /* ── TABLE FOOTER ───────────────────────────────────── */
    .table-footer {
        padding: 14px 20px; border-top: 1px solid var(--card-border);
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
    }

    /* ── MODAL ──────────────────────────────────────────── */

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

    @media (max-width: 600px) {
        .mfield-grid { grid-template-columns: 1fr; }
        .mfield-grid .span-full { grid-column: span 1; }
    }

    /* ── TENANT PROFILE MODAL ───────────────────────────── */
    .profile-modal-overlay {
        display: none; position: fixed; inset: 0; z-index: 1050;
        background: rgba(11,17,32,0.75); backdrop-filter: blur(4px);
        align-items: center; justify-content: center; padding: 24px;
    }
    .profile-modal-overlay.open { display: flex; }
    .profile-modal-header {
        padding: 10px 16px; background: var(--page-bg); border-bottom: 1px solid var(--card-border);
        display: flex; align-items: center; justify-content: flex-end; flex-shrink: 0;
    }
    .profile-modal-body { flex: 1; overflow-y: auto; padding: 22px 24px; }
    .profile-modal-body::-webkit-scrollbar { width: 4px; }
    .profile-modal-body::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
    .profile-modal-loading { text-align: center; padding: 80px 20px; color: var(--text-muted); font-size: 24px; }
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
        <a href="{{ route('export.tenants', request()->only(['search','tenant_type','company_name'])) }}" class="btn btn-outline">
            <i class="fa-solid fa-file-export"></i> Export
        </a>
        <button type="button" class="btn btn-outline" onclick="openImport_tenants()">
            <i class="fa-solid fa-file-import"></i> Import
        </button>
        <button type="button" class="btn btn-primary" onclick="openTenantModal()">
            <i class="fa-solid fa-plus"></i> Add Tenant
        </button>
    </div>
</div>

{{-- ═══════════════════════ MOBILE SCREEN (Miknas Property Manager design) ═══════════════════════ --}}
<div class="m-screen">
    <div style="display:flex;align-items:center;gap:9px;background:var(--pm-surface);border:1px solid var(--pm-border-strong);border-radius:8px;padding:10px 12px;">
        <i class="fa-solid fa-magnifying-glass" style="color:var(--pm-text-3);font-size:13px;"></i>
        <form method="GET" action="{{ route('tenants.index') }}" style="flex:1;">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tenants or units"
                   style="width:100%;border:0;outline:none;font-size:13.5px;color:var(--pm-text);background:transparent;font-family:'Plus Jakarta Sans',sans-serif;"
                   oninput="mDebounceSubmit(this)">
        </form>
    </div>

    @php
        $tenantFilters = [
            ['id' => null,      'label' => 'All'],
            ['id' => 'paid',    'label' => 'Paid'],
            ['id' => 'overdue', 'label' => 'Overdue'],
        ];
        $activeStatus = request('status');
    @endphp
    <div style="display:flex;gap:7px;">
        @foreach($tenantFilters as $f)
            @php $isActive = $activeStatus === $f['id']; @endphp
            <a href="{{ route('tenants.index', array_filter(['search' => request('search'), 'status' => $f['id']])) }}"
               style="padding:7px 13px;border-radius:9999px;font-size:12px;font-weight:600;text-decoration:none;
                      border:1px solid {{ $isActive ? 'var(--pm-navy)' : 'var(--pm-border)' }};
                      background:{{ $isActive ? 'var(--pm-navy)' : 'var(--pm-surface)' }};
                      color:{{ $isActive ? '#fff' : 'var(--pm-text-2)' }};">
                {{ $f['label'] }}
            </a>
        @endforeach
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;">
        <div class="pm-section-label" style="margin-bottom:0;">LEASES</div>
        <div style="font-size:11px;font-weight:600;color:var(--pm-text-3);">{{ $tenants->total() }}</div>
    </div>

    <div style="display:flex;flex-direction:column;gap:8px;">
        @forelse($tenants as $tenant)
            @php
                $lease = $tenant->activeLease;
                $statusMeta = match ($tenant->rentStatus) {
                    'paid'    => ['label' => 'Paid',    'tone' => 'var(--pm-green-text)'],
                    'overdue' => ['label' => 'Overdue', 'tone' => 'var(--pm-red)'],
                    default   => null,
                };
            @endphp
            <a href="{{ route('tenants.show', $tenant) }}" class="pm-action-row" style="text-decoration:none;">
                <div style="flex:none;width:38px;height:38px;border-radius:9999px;background:var(--pm-page);color:var(--pm-text-2);font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;">
                    {{ strtoupper(substr($tenant->name, 0, 2)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="pm-action-title">{{ $tenant->name }}</div>
                    <div class="pm-action-sub">{{ $lease?->property_code ?? '—' }}{{ $lease?->unit ? ' · '.$lease->unit : '' }}</div>
                </div>
                @if($lease?->rent_per_month)
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-family:'Outfit',sans-serif;font-weight:700;font-size:14px;color:var(--pm-text);">BHD {{ number_format($lease->rent_per_month, 0) }}</div>
                        @if($statusMeta)
                            <div style="font-size:10px;font-weight:700;color:{{ $statusMeta['tone'] }};">{{ $statusMeta['label'] }}</div>
                        @endif
                    </div>
                @endif
            </a>
        @empty
            <div class="pm-empty">
                @if($activeStatus)
                    No tenants match this filter.
                @else
                    <div style="font-size:14px;font-weight:700;color:var(--pm-text);margin-bottom:4px;">No tenants found</div>
                    <div>Try adjusting your search or add a new tenant.</div>
                @endif
            </div>
        @endforelse
    </div>

    <button type="button" class="pm-fab" style="position:fixed;border:0;" onclick="openTenantModal()" title="Add a tenant"><i class="fa-solid fa-plus"></i></button>
</div>

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
<div class="card m-hide-desktop-index" style="overflow:hidden;">

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
            @if($companies->isNotEmpty())
            <div class="filter-group">
                <label>Company</label>
                <select name="company_name" onchange="this.form.submit()">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company }}" {{ request('company_name') === $company ? 'selected' : '' }}>{{ $company }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="filter-actions">
                @if(request()->hasAny(['search','tenant_type','company_name']))
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
                <tr data-tenant-modal="{{ route('tenants.show', $tenant) }}" style="cursor:pointer">
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
                            @if($tenant->company_name)
                                <div class="tenant-sub" style="margin-top:4px;">Under {{ $tenant->company_name }}</div>
                            @endif
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
                    <td onclick="event.stopPropagation()">
                        <div class="action-btns" style="justify-content:flex-end;">
                            <button type="button" class="btn btn-outline btn-sm" title="View" onclick="openTenantProfileModal('{{ route('tenants.show', $tenant) }}')">
                                <i class="fa-regular fa-eye"></i>
                            </button>
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

@include('components.import-modal', [
    'type'      => 'tenants',
    'label'     => 'Tenants',
    'icon'      => 'fa-users',
    'routeName' => 'import.tenants',
])

{{-- ═══════════════════════════════════════════════════════
     ADD TENANT MODAL
═══════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="tenantModal" role="dialog" aria-modal="true" aria-labelledby="tenantModalTitle">
    <div class="modal-box" style="--modal-w:560px">

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
                    <div class="option-grid">
                        <div class="option-group">
                            <input type="radio" name="tenant_type" id="type_individual" value="individual"
                                {{ old('tenant_type', 'individual') === 'individual' ? 'checked' : '' }} required>
                            <label for="type_individual" class="option-card">
                                <i class="fa-solid fa-user ti" style="color:var(--success);"></i>
                                Individual
                            </label>
                        </div>
                        <div class="option-group">
                            <input type="radio" name="tenant_type" id="type_company" value="company"
                                {{ old('tenant_type') === 'company' ? 'checked' : '' }}>
                            <label for="type_company" class="option-card">
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

                    {{-- ADDRESS --}}
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Address</label>
                        <div class="mfield-wrap has-micon">
                            <i class="fa-solid fa-location-dot mfield-icon"></i>
                            <input type="text" name="address"
                                class="mfield-input {{ $errors->has('address') ? 'is-invalid' : '' }}"
                                value="{{ old('address') }}"
                                placeholder="e.g. MP 2, Bldg# 233, Road# 3332, Block# 333, Bahrain" maxlength="500">
                        </div>
                        @error('address')
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

{{-- ═══════════════════════════════════════════════════════
     TENANT PROFILE MODAL
═══════════════════════════════════════════════════════ --}}
<div class="profile-modal-overlay" id="tenantProfileModal" onclick="closeTenantProfileModal(event)">
    <div class="modal-box" style="--modal-w:1100px" onclick="event.stopPropagation()">
        <div class="profile-modal-header">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeTenantProfileModal()">
                <i class="fa-solid fa-xmark"></i> Close
            </button>
        </div>
        <div class="profile-modal-body" id="tenantProfileBody"></div>
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

// ── TENANT PROFILE MODAL ──────────────────────────────────────
document.querySelectorAll('tr[data-tenant-modal]').forEach(function (row) {
    row.addEventListener('click', function () {
        openTenantProfileModal(row.dataset.tenantModal);
    });
});

function openTenantProfileModal(url) {
    const body = document.getElementById('tenantProfileBody');
    body.innerHTML = '<div class="profile-modal-loading"><i class="fa-solid fa-spinner fa-spin"></i></div>';
    document.getElementById('tenantProfileModal').classList.add('open');
    document.body.style.overflow = 'hidden';

    const sep = url.includes('?') ? '&' : '?';
    fetch(url + sep + 'modal=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.text();
        })
        .then(function (html) {
            body.innerHTML = html;
            // <script> tags inserted via innerHTML don't execute — recreate
            // them so the tab-switching / note-form JS actually runs.
            body.querySelectorAll('script').forEach(function (oldScript) {
                const newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                oldScript.replaceWith(newScript);
            });
        })
        .catch(function () {
            body.innerHTML = '<div class="profile-modal-loading" style="color:var(--danger)">Failed to load tenant profile.</div>';
        });
}

function closeTenantProfileModal(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('tenantProfileModal').classList.remove('open');
    document.getElementById('tenantProfileBody').innerHTML = '';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('tenantProfileModal').classList.contains('open')) {
        closeTenantProfileModal();
    }
});
</script>
@endpush
