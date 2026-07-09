@extends('layouts.admin')

@section('title', 'Lease Contracts')
@section('topbar-title', 'Lease Contracts')

@push('styles')
<style>
    /* ── STATS ─────────────────────────────────────────────── */
    .stats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:16px; margin-bottom:24px; }
    .stat-card {
        background:var(--card-bg); border:1px solid var(--card-border); border-radius:var(--radius);
        padding:18px 20px; display:flex; align-items:center; gap:14px;
        box-shadow:var(--shadow-sm); transition:box-shadow .2s,transform .2s;
    }
    .stat-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
    .stat-icon { width:44px;height:44px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
    .stat-icon.gold   { background:var(--accent-dim); color:var(--accent); }
    .stat-icon.green  { background:#ECFDF5; color:var(--success); }
    .stat-icon.amber  { background:#FFFBEB; color:var(--warning); }
    .stat-icon.gray   { background:#F1F5F9; color:var(--text-muted); }
    .stat-val { font-family:'Outfit',sans-serif; font-size:24px; font-weight:800; color:var(--text-primary); line-height:1; }
    .stat-lbl { font-size:12px; color:var(--text-muted); margin-top:3px; }

    /* ── FILTER BAR ─────────────────────────────────────────── */
    .filter-bar { display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;padding:16px 20px;background:var(--page-bg);border-bottom:1px solid var(--card-border); }
    .filter-group { display:flex;flex-direction:column;gap:5px;min-width:150px; }
    .filter-group label { font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em; }
    .filter-group input,.filter-group select {
        padding:8px 12px;font-size:13px;border:1.5px solid var(--input-border);border-radius:var(--radius-sm);
        background:var(--card-bg);color:var(--text-primary);font-family:'Plus Jakarta Sans',sans-serif;
        outline:none;appearance:none;-webkit-appearance:none;transition:border-color .18s,box-shadow .18s;
    }
    .filter-group input:focus,.filter-group select:focus { border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim); }
    .filter-group select {
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat:no-repeat;background-position:right 10px center;padding-right:32px;
    }
    .filter-actions { display:flex;gap:8px;align-items:flex-end;margin-left:auto; }

    /* ── TABLE ──────────────────────────────────────────────── */
    .agr-no { font-family:'Outfit',sans-serif;font-weight:700;font-size:13px;color:var(--text-primary); }
    .tenant-cell { display:flex;align-items:center;gap:9px; }
    .tenant-av { width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Outfit',sans-serif;font-size:12px;font-weight:700;flex-shrink:0; }
    .tenant-av.individual { background:#ECFDF5;color:var(--success); }
    .tenant-av.company    { background:#EFF6FF;color:var(--info); }
    .cell-main { font-size:13.5px;font-weight:600;color:var(--text-primary); }
    .cell-sub  { font-size:11px;color:var(--text-muted);margin-top:2px; }
    .period-bar { height:4px;border-radius:4px;background:var(--card-border);margin-top:5px;position:relative;overflow:hidden; }
    .period-fill { height:100%;border-radius:4px;background:var(--accent); }
    .action-btns { display:flex;gap:6px; }

    /* ── STATUS BADGES ──────────────────────────────────────── */
    .status-active   { background:#ECFDF5;color:var(--success);border:1px solid #A7F3D0; }
    .status-expiring { background:#FFFBEB;color:var(--warning);border:1px solid #FDE68A; }
    .status-expired  { background:#F1F5F9;color:var(--text-muted);border:1px solid var(--card-border); }
    .status-upcoming { background:#EFF6FF;color:var(--info);border:1px solid #BFDBFE; }

    /* ── FOOTER / PAGINATION ────────────────────────────────── */
    .table-footer { padding:14px 20px;border-top:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px; }
    .pagination { display:flex;gap:4px;align-items:center; }
    .page-btn { width:32px;height:32px;border:1.5px solid var(--card-border);background:var(--card-bg);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:var(--text-secondary);cursor:pointer;text-decoration:none;transition:all .15s; }
    .page-btn:hover { background:var(--page-bg);color:var(--text-primary); }
    .page-btn.active { background:var(--accent);border-color:var(--accent);color:#0B1120; }
    .result-count { font-size:13px;color:var(--text-muted); }
    .result-count strong { color:var(--text-primary); }
    .empty-state { text-align:center;padding:60px 20px; }
    .empty-icon { width:64px;height:64px;background:var(--page-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--text-muted);margin:0 auto 16px; }
    .empty-state h4 { font-family:'Outfit',sans-serif;font-size:16px;font-weight:700;color:var(--text-primary);margin-bottom:6px; }
    .empty-state p { font-size:13px;color:var(--text-muted); }

    /* ── MODAL ──────────────────────────────────────────────── */
    .modal-overlay {
        position:fixed;inset:0;z-index:1000;
        background:rgba(11,17,32,.55);backdrop-filter:blur(4px);
        display:flex;align-items:center;justify-content:center;padding:20px;
        opacity:0;pointer-events:none;transition:opacity .25s ease;
    }
    .modal-overlay.open { opacity:1;pointer-events:all; }
    .modal-box {
        background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;
        box-shadow:0 24px 60px rgba(0,0,0,.18),0 8px 24px rgba(0,0,0,.10);
        width:100%;max-width:780px;max-height:92vh;
        display:flex;flex-direction:column;
        transform:translateY(20px) scale(.98);
        transition:transform .3s cubic-bezier(.22,1,.36,1);
        overflow:hidden;
    }
    .modal-overlay.open .modal-box { transform:translateY(0) scale(1); }
    .modal-header { padding:18px 24px 0;border-bottom:1px solid var(--card-border);flex-shrink:0; }
    .modal-header-top { display:flex;align-items:center;gap:12px;padding-bottom:14px; }
    .modal-header-icon {
        width:40px;height:40px;border-radius:10px;
        background:var(--accent-dim);border:1px solid rgba(232,184,109,.25);
        display:flex;align-items:center;justify-content:center;
        color:var(--accent);font-size:16px;flex-shrink:0;
    }
    .modal-header-title { font-family:'Outfit',sans-serif;font-size:17px;font-weight:800;color:var(--text-primary); }
    .modal-header-sub { font-size:12px;color:var(--text-muted);margin-top:2px; }
    .modal-close-btn {
        margin-left:auto;width:32px;height:32px;border-radius:var(--radius-sm);
        border:1.5px solid var(--card-border);background:transparent;
        cursor:pointer;display:flex;align-items:center;justify-content:center;
        color:var(--text-muted);font-size:13px;transition:all .15s;
    }
    .modal-close-btn:hover { background:var(--page-bg);color:var(--text-primary); }

    /* ── MODAL TABS ─────────────────────────────────────────── */
    .modal-tabs { display:flex;gap:0;overflow-x:auto; }
    .modal-tabs::-webkit-scrollbar { display:none; }
    .mtab-btn {
        padding:10px 16px;font-size:12px;font-weight:700;color:var(--text-muted);
        border:none;background:none;cursor:pointer;
        border-bottom:2px solid transparent;margin-bottom:-1px;
        white-space:nowrap;display:flex;align-items:center;gap:6px;
        transition:color .15s,border-color .15s;
        font-family:'Plus Jakarta Sans',sans-serif;text-transform:uppercase;letter-spacing:.05em;
    }
    .mtab-btn:hover { color:var(--text-primary); }
    .mtab-btn.active { color:var(--accent);border-bottom-color:var(--accent); }
    .mtab-btn .err-dot {
        width:6px;height:6px;border-radius:50%;background:var(--danger);
        display:none;
    }
    .mtab-btn .err-dot.show { display:inline-block; }

    .modal-body { padding:20px 24px;overflow-y:auto;flex:1; }
    .modal-body::-webkit-scrollbar { width:4px; }
    .modal-body::-webkit-scrollbar-thumb { background:#CBD5E1;border-radius:10px; }

    .mtab-panel { display:none; }
    .mtab-panel.active { display:block; }

    .modal-footer {
        padding:14px 24px;border-top:1px solid var(--card-border);
        display:flex;align-items:center;justify-content:space-between;gap:10px;
        flex-shrink:0;
    }
    .modal-footer-nav { display:flex;gap:8px; }

    /* ── MODAL FIELDS ───────────────────────────────────────── */
    .mfield-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:14px 20px; }
    .mfield-grid .span-full { grid-column:1/-1; }
    .mfield-group { display:flex;flex-direction:column; }
    .mfield-label {
        font-size:11px;font-weight:700;color:var(--text-secondary);
        letter-spacing:.04em;text-transform:uppercase;margin-bottom:5px;
        display:flex;align-items:center;gap:3px;
    }
    .mfield-label .req { color:var(--danger);font-size:13px;line-height:1; }
    .mfield-input, .mfield-select {
        width:100%;padding:9px 12px;
        border:1.5px solid var(--input-border);border-radius:var(--radius-sm);
        background:#fff;color:var(--text-primary);
        font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;
        outline:none;appearance:none;-webkit-appearance:none;
        transition:border-color .2s,box-shadow .2s;
    }
    .mfield-input:focus,.mfield-select:focus { border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim);background:#FFFDF8; }
    .mfield-input.is-invalid,.mfield-select.is-invalid { border-color:var(--danger);background:#FFF8F8; }
    .mfield-select {
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%2394A3B8' d='M5 7L0.669873 2.5L9.33013 2.5L5 7Z'/%3E%3C/svg%3E");
        background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;
    }
    .mfield-error { display:flex;align-items:center;gap:4px;margin-top:4px;font-size:11px;color:var(--danger);font-weight:500; }

    /* ── TENANT SEARCH DROPDOWN ─────────────────────────────── */
    .tenant-combo { position:relative; }
    .tenant-combo-dropdown {
        position:absolute;top:calc(100% + 4px);left:0;right:0;max-height:230px;overflow-y:auto;z-index:60;
        background:#fff;border:1.5px solid var(--input-border);border-radius:var(--radius-sm);
        box-shadow:0 12px 28px rgba(0,0,0,.14);display:none;
    }
    .tenant-combo-dropdown.open { display:block; }
    .tenant-combo-item { padding:9px 14px;font-size:13px;color:var(--text-primary);cursor:pointer; }
    .tenant-combo-item:hover, .tenant-combo-item.active { background:var(--accent-dim); }
    .tenant-combo-item.hidden { display:none; }
    .tenant-combo-empty { padding:14px;text-align:center;font-size:12px;color:var(--text-muted);display:none; }
    .tenant-combo-empty.show { display:block; }

    /* ── LOCATION SEARCH DROPDOWNS (property / block / floor / unit) ── */
    .search-combo { position:relative; }
    .search-combo-dropdown {
        position:absolute;top:calc(100% + 4px);left:0;right:0;max-height:230px;overflow-y:auto;z-index:60;
        background:#fff;border:1.5px solid var(--input-border);border-radius:var(--radius-sm);
        box-shadow:0 12px 28px rgba(0,0,0,.14);display:none;
    }
    .search-combo-dropdown.open { display:block; }
    .search-combo-item { padding:9px 14px;font-size:13px;color:var(--text-primary);cursor:pointer; }
    .search-combo-item:hover { background:var(--accent-dim); }
    .search-combo-empty { padding:14px;text-align:center;font-size:12px;color:var(--text-muted); }

    /* ── SECTION DIVIDER ────────────────────────────────────── */
    .msection-label {
        font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;
        color:var(--text-muted);margin-bottom:12px;padding-bottom:8px;
        border-bottom:1px solid var(--card-border);
    }

    @media (max-width:600px) {
        .modal-box { max-height:100vh;border-radius:0;max-width:100%; }
        .modal-overlay { padding:0;align-items:flex-end; }
        .mfield-grid { grid-template-columns:1fr; }
        .mfield-grid .span-full { grid-column:span 1; }
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Lease Contracts</span>
        </div>
        <h1 class="page-header-title">Lease Contracts</h1>
        <p class="page-header-sub">Manage all lease agreements and contract records</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('export.contracts', request()->only(['search','property_code'])) }}" class="btn btn-outline">
            <i class="fa-solid fa-file-export"></i> Export
        </a>
        <button type="button" class="btn btn-outline" onclick="openImport_contracts()">
            <i class="fa-solid fa-file-import"></i> Import
        </button>
        <button type="button" class="btn btn-primary" onclick="openContractModal()">
            <i class="fa-solid fa-plus"></i> New Contract
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-file-contract"></i></div>
        <div><div class="stat-val">{{ $stats['total'] }}</div><div class="stat-lbl">Total Contracts</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="stat-val">{{ $stats['active'] }}</div><div class="stat-lbl">Active</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div><div class="stat-val">{{ $stats['expiring'] }}</div><div class="stat-lbl">Expiring (30 days)</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gray"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div><div class="stat-val">{{ $stats['expired'] }}</div><div class="stat-lbl">Expired</div></div>
    </div>
</div>

{{-- TABLE CARD --}}
<div class="card" style="overflow:hidden;">

    <form method="GET" action="{{ route('lease-contracts.index') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group" style="flex:1;min-width:220px;">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Agreement No, tenant, unit…" oninput="debounceSubmit()">
            </div>
            <div class="filter-group">
                <label>Property Code</label>
                <select name="property_code" onchange="this.form.submit()">
                    <option value="">All Properties</option>
                    @foreach($propertyCodes as $code)
                        <option value="{{ $code }}" {{ request('property_code') === $code ? 'selected' : '' }}>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="expiring" {{ request('status') === 'expiring' ? 'selected' : '' }}>Expiring Soon</option>
                    <option value="upcoming" {{ request('status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="expired"  {{ request('status') === 'expired'  ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <div class="filter-group">
                <label>As of Date <span style="font-size:10px;color:var(--text-muted);text-transform:none;font-weight:400">(default: today)</span></label>
                <input type="date" name="as_of" value="{{ $asOfValue }}" onchange="this.form.submit()">
            </div>
            <div class="filter-actions">
                @if(request()->hasAny(['search','property_code','status','as_of']))
                    <a href="{{ route('lease-contracts.index') }}" class="btn btn-outline btn-sm">
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
                    <th>Agreement No</th>
                    <th>Tenant</th>
                    <th>Property / Unit</th>
                    <th>Lease Period</th>
                    <th>Rent / Month</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contracts as $contract)
                @php
                    $contractShowUrl = route('lease-contracts.show', $contract);
                @endphp
                @php
                    $status = $contract->status;
                    $statusLabel = match($status) {
                        'active'   => 'Active',
                        'expiring' => 'Expiring',
                        'upcoming' => 'Upcoming',
                        'expired'  => 'Expired',
                    };
                    $statusIcon = match($status) {
                        'active'   => 'fa-circle-check',
                        'expiring' => 'fa-triangle-exclamation',
                        'upcoming' => 'fa-clock',
                        'expired'  => 'fa-circle-xmark',
                    };
                    $start = $contract->lease_start_date->timestamp;
                    $end   = $contract->lease_end_date->timestamp;
                    $now   = min(now()->timestamp, $end);
                    $pct   = $end > $start ? max(0, min(100, round(($now - $start) / ($end - $start) * 100))) : 100;
                @endphp
                <tr data-href="{{ $contractShowUrl }}" style="cursor:pointer">
                    <td>
                        <span class="agr-no">{{ $contract->lease_agreement_no }}</span>
                        <div class="cell-sub">{{ $contract->date->format('d M Y') }}</div>
                    </td>
                    <td>
                        <div class="tenant-cell">
                            <div class="tenant-av {{ $contract->tenant?->tenant_type ?? 'individual' }}">
                                {{ strtoupper(substr($contract->tenant_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="cell-main">{{ $contract->tenant_name }}</div>
                                @if($contract->description)
                                    <div class="cell-sub">{{ $contract->description }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="cell-main">{{ $contract->property_code ?? '—' }}</div>
                        <div class="cell-sub">{{ $contract->unit ?? $contract->floor_name ?? '—' }}</div>
                    </td>
                    <td>
                        <div style="font-size:13px;">
                            {{ $contract->lease_start_date->format('d M Y') }}
                            <span style="color:var(--text-muted);"> → </span>
                            {{ $contract->lease_end_date->format('d M Y') }}
                        </div>
                        <div class="period-bar" style="width:120px;">
                            <div class="period-fill" style="width:{{ $pct }}%;background:{{ $status === 'expired' ? 'var(--text-muted)' : ($status === 'expiring' ? 'var(--warning)' : 'var(--accent)') }};"></div>
                        </div>
                    </td>
                    <td>
                        @if($contract->rent_per_month)
                            <div style="font-family:'Outfit',sans-serif;font-weight:700;font-size:14px;">
                                {{ $contract->currency ?? 'BHD' }} {{ number_format($contract->rent_per_month, 3) }}
                            </div>
                            @if($contract->invoicing_frequency)
                                <div class="cell-sub">{{ $contract->invoicing_frequency }}</div>
                            @endif
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge status-{{ $status }}">
                            <i class="fa-solid {{ $statusIcon }}"></i> {{ $statusLabel }}
                        </span>
                    </td>
                    <td>
                        <div class="action-btns" style="justify-content:flex-end;" onclick="event.stopPropagation()">
                            <a href="{{ route('lease-contracts.show', $contract) }}" class="btn btn-outline btn-sm" title="View">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="{{ route('lease-contracts.edit', $contract) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('lease-contracts.destroy', $contract) }}"
                                  onsubmit="return confirm('Delete contract {{ addslashes($contract->lease_agreement_no) }}? This cannot be undone.')">
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
                        <div class="empty-icon"><i class="fa-solid fa-file-contract"></i></div>
                        <h4>No contracts found</h4>
                        <p>Try adjusting your filters or
                            <button type="button" onclick="openContractModal()" style="background:none;border:none;cursor:pointer;color:var(--accent);font-weight:600;padding:0;">create a new contract</button>.
                        </p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $contracts->firstItem() ?? 0 }}–{{ $contracts->lastItem() ?? 0 }}</strong>
            of <strong>{{ $contracts->total() }}</strong> contracts
        </div>
        <div class="pagination">
            @if($contracts->onFirstPage())
                <span class="page-btn" style="opacity:.4;cursor:default;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></span>
            @else
                <a href="{{ $contracts->previousPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></a>
            @endif
            @foreach($contracts->getUrlRange(max(1,$contracts->currentPage()-2),min($contracts->lastPage(),$contracts->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="page-btn {{ $page == $contracts->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            @if($contracts->hasMorePages())
                <a href="{{ $contracts->nextPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></a>
            @else
                <span class="page-btn" style="opacity:.4;cursor:default;"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
            @endif
        </div>
    </div>

</div>

@include('components.import-modal', [
    'type'      => 'contracts',
    'label'     => 'Contracts',
    'icon'      => 'fa-file-contract',
    'routeName' => 'import.contracts',
])

{{-- ═══════════════════════════════════════════════════════
     NEW CONTRACT MODAL
═══════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="contractModal" role="dialog" aria-modal="true" aria-labelledby="contractModalTitle">
    <div class="modal-box">

        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-solid fa-file-contract"></i></div>
                <div>
                    <div class="modal-header-title" id="contractModalTitle">New Lease Contract</div>
                    <div class="modal-header-sub">Fill in the sections below to create a new agreement</div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeContractModal()" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- SECTION TABS --}}
            <div class="modal-tabs">
                <button type="button" class="mtab-btn active" data-tab="mc-info" onclick="switchMTab('mc-info')">
                    <i class="fa-solid fa-file-lines" style="color:#C2410C;font-size:11px;"></i> Contract
                    <span class="err-dot" id="dot-mc-info"></span>
                </button>
                <button type="button" class="mtab-btn" data-tab="mc-location" onclick="switchMTab('mc-location')">
                    <i class="fa-solid fa-location-dot" style="color:#15803D;font-size:11px;"></i> Location
                    <span class="err-dot" id="dot-mc-location"></span>
                </button>
                <button type="button" class="mtab-btn" data-tab="mc-lease" onclick="switchMTab('mc-lease')">
                    <i class="fa-solid fa-calendar-days" style="color:#1D4ED8;font-size:11px;"></i> Lease Term
                    <span class="err-dot" id="dot-mc-lease"></span>
                </button>
                <button type="button" class="mtab-btn" data-tab="mc-rent" onclick="switchMTab('mc-rent')">
                    <i class="fa-solid fa-coins" style="color:#BE123C;font-size:11px;"></i> Rent
                    <span class="err-dot" id="dot-mc-rent"></span>
                </button>
                <button type="button" class="mtab-btn" data-tab="mc-service" onclick="switchMTab('mc-service')">
                    <i class="fa-solid fa-screwdriver-wrench" style="color:#6D28D9;font-size:11px;"></i> Service
                    <span class="err-dot" id="dot-mc-service"></span>
                </button>
                <button type="button" class="mtab-btn" data-tab="mc-financial" onclick="switchMTab('mc-financial')">
                    <i class="fa-solid fa-landmark" style="color:#92400E;font-size:11px;"></i> Financial
                    <span class="err-dot" id="dot-mc-financial"></span>
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('lease-contracts.store') }}" id="contractForm" novalidate>
            @csrf

            {{-- TAB 1: CONTRACT INFO --}}
            <div class="mtab-panel active" id="mc-info">
                <div class="mfield-grid">

                    <div class="mfield-group">
                        <label class="mfield-label">Date <span class="req">*</span></label>
                        <input type="date" name="date"
                            class="mfield-input {{ $errors->has('date') ? 'is-invalid' : '' }}"
                            value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Agreement No.</label>
                        <input type="text" name="lease_agreement_no"
                            class="mfield-input {{ $errors->has('lease_agreement_no') ? 'is-invalid' : '' }}"
                            value="{{ old('lease_agreement_no') }}"
                            placeholder="Leave blank to auto-generate (e.g. LA-2026-001)" maxlength="100">
                        @error('lease_agreement_no') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group span-full">
                        <label class="mfield-label">Tenant <span class="req">*</span></label>
                        <div class="tenant-combo" id="mc_tenant_combo">
                            <input type="text" id="mc_tenant_search" class="mfield-input {{ $errors->has('tenant_id') ? 'is-invalid' : '' }}"
                                placeholder="Search tenant by name…" autocomplete="off"
                                value="{{ old('tenant_id') ? $tenants->firstWhere('id', (int) old('tenant_id'))?->name : '' }}">
                            <input type="hidden" name="tenant_id" id="mc_tenant_id" value="{{ old('tenant_id') }}">
                            <div class="tenant-combo-dropdown" id="mc_tenant_dropdown">
                                @foreach($tenants as $t)
                                    <div class="tenant-combo-item" data-id="{{ $t->id }}" data-name="{{ $t->name }}">
                                        {{ $t->name }}@if($t->tenant_code) <span style="color:var(--text-muted);font-size:11px">({{ $t->tenant_code }})</span>@endif
                                    </div>
                                @endforeach
                                <div class="tenant-combo-empty" id="mc_tenant_empty">No tenants match your search</div>
                            </div>
                        </div>
                        @error('tenant_id') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group span-full">
                        <label class="mfield-label">Unit Description</label>
                        <select name="description" class="mfield-select {{ $errors->has('description') ? 'is-invalid' : '' }}">
                            <option value="">— Select —</option>
                            @foreach(['Fitted','Shell & Core','Semi-Fitted'] as $opt)
                                <option value="{{ $opt }}" {{ old('description') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('description') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- TAB 2: PROPERTY LOCATION --}}
            <div class="mtab-panel" id="mc-location">
                <div class="mfield-grid">

                    <div class="mfield-group">
                        <label class="mfield-label">Property Name</label>
                        <div class="search-combo" id="mc_property_name_combo">
                            <input type="text" name="property_name" id="mc_property_name"
                                class="mfield-input {{ $errors->has('property_name') ? 'is-invalid' : '' }}"
                                value="{{ old('property_name') }}" placeholder="Search property name…" maxlength="255" autocomplete="off">
                            <div class="search-combo-dropdown"></div>
                        </div>
                        @error('property_name') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Property Code</label>
                        <div class="search-combo" id="mc_property_code_combo">
                            <input type="text" name="property_code" id="mc_property_code"
                                class="mfield-input {{ $errors->has('property_code') ? 'is-invalid' : '' }}"
                                value="{{ old('property_code') }}" placeholder="Search property code…" maxlength="50" autocomplete="off">
                            <div class="search-combo-dropdown"></div>
                        </div>
                        @error('property_code') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Block Name</label>
                        <div class="search-combo" id="mc_block_name_combo">
                            <input type="text" name="block_name" id="mc_block_name"
                                class="mfield-input {{ $errors->has('block_name') ? 'is-invalid' : '' }}"
                                value="{{ old('block_name') }}" placeholder="Search block…" maxlength="100" autocomplete="off">
                            <div class="search-combo-dropdown"></div>
                        </div>
                        @error('block_name') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Block Code</label>
                        <input type="text" name="block_code" id="mc_block_code"
                            class="mfield-input {{ $errors->has('block_code') ? 'is-invalid' : '' }}"
                            value="{{ old('block_code') }}" placeholder="Auto-filled from block" maxlength="50">
                        @error('block_code') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Floor Name</label>
                        <div class="search-combo" id="mc_floor_name_combo">
                            <input type="text" name="floor_name" id="mc_floor_name"
                                class="mfield-input {{ $errors->has('floor_name') ? 'is-invalid' : '' }}"
                                value="{{ old('floor_name') }}" placeholder="Search floor…" maxlength="100" autocomplete="off">
                            <div class="search-combo-dropdown"></div>
                        </div>
                        @error('floor_name') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Floor Code</label>
                        <input type="text" name="floor_code" id="mc_floor_code"
                            class="mfield-input {{ $errors->has('floor_code') ? 'is-invalid' : '' }}"
                            value="{{ old('floor_code') }}" placeholder="Auto-filled from floor" maxlength="50">
                        @error('floor_code') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Unit (System)</label>
                        <div class="search-combo" id="mc_unit_combo">
                            <input type="text" id="mc_unit_search"
                                class="mfield-input {{ $errors->has('unit_id') ? 'is-invalid' : '' }}"
                                value="{{ old('unit_id') ? $units->firstWhere('id', (int) old('unit_id'))?->unit_name : '' }}"
                                placeholder="Search unit…" autocomplete="off">
                            <input type="hidden" name="unit_id" id="mc_unit_id" value="{{ old('unit_id') }}">
                            <div class="search-combo-dropdown"></div>
                        </div>
                        @error('unit_id') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Unit (Free-text)</label>
                        <input type="text" name="unit" id="mc_unit_text"
                            class="mfield-input {{ $errors->has('unit') ? 'is-invalid' : '' }}"
                            value="{{ old('unit') }}" placeholder="If not in system" maxlength="100">
                        @error('unit') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- TAB 3: LEASE TERM --}}
            <div class="mtab-panel" id="mc-lease">
                <div class="mfield-grid">

                    <div class="mfield-group">
                        <label class="mfield-label">Start Date <span class="req">*</span></label>
                        <input type="date" name="lease_start_date"
                            class="mfield-input {{ $errors->has('lease_start_date') ? 'is-invalid' : '' }}"
                            value="{{ old('lease_start_date') }}" required>
                        @error('lease_start_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">End Date <span class="req">*</span></label>
                        <input type="date" name="lease_end_date"
                            class="mfield-input {{ $errors->has('lease_end_date') ? 'is-invalid' : '' }}"
                            value="{{ old('lease_end_date') }}" required>
                        @error('lease_end_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Break Date</label>
                        <input type="date" name="lease_break_date"
                            class="mfield-input {{ $errors->has('lease_break_date') ? 'is-invalid' : '' }}"
                            value="{{ old('lease_break_date') }}">
                        @error('lease_break_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Notice Period</label>
                        <input type="text" name="notice_period"
                            class="mfield-input {{ $errors->has('notice_period') ? 'is-invalid' : '' }}"
                            value="{{ old('notice_period') }}" placeholder="e.g. 3 months" maxlength="50">
                        @error('notice_period') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- TAB 4: RENT --}}
            <div class="mtab-panel" id="mc-rent">
                <div class="mfield-grid">

                    <div class="mfield-group">
                        <label class="mfield-label">Invoicing Frequency</label>
                        <select name="invoicing_frequency" class="mfield-select {{ $errors->has('invoicing_frequency') ? 'is-invalid' : '' }}">
                            <option value="">— Select —</option>
                            @foreach(['Monthly','Quarterly','Semi-Annually','Annually'] as $opt)
                                <option value="{{ $opt }}" {{ old('invoicing_frequency') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('invoicing_frequency') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Currency</label>
                        <select name="currency" class="mfield-select {{ $errors->has('currency') ? 'is-invalid' : '' }}">
                            <option value="">— Select —</option>
                            @foreach(['BHD','USD','EUR','GBP','SAR','AED'] as $cur)
                                <option value="{{ $cur }}" {{ old('currency') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                            @endforeach
                        </select>
                        @error('currency') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Rent Start Date</label>
                        <input type="date" name="rent_start_date"
                            class="mfield-input {{ $errors->has('rent_start_date') ? 'is-invalid' : '' }}"
                            value="{{ old('rent_start_date') }}">
                        @error('rent_start_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Rent End Date</label>
                        <input type="date" name="rent_end_date"
                            class="mfield-input {{ $errors->has('rent_end_date') ? 'is-invalid' : '' }}"
                            value="{{ old('rent_end_date') }}">
                        @error('rent_end_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group span-full">
                        <label class="mfield-label">Rent per Month</label>
                        <input type="number" name="rent_per_month"
                            class="mfield-input {{ $errors->has('rent_per_month') ? 'is-invalid' : '' }}"
                            value="{{ old('rent_per_month') }}" placeholder="0.000" min="0" step="0.001">
                        @error('rent_per_month') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- TAB 5: SERVICE CHARGE --}}
            <div class="mtab-panel" id="mc-service">
                <div class="mfield-grid">

                    <div class="mfield-group">
                        <label class="mfield-label">Service Frequency</label>
                        <select name="service_frequency" class="mfield-select {{ $errors->has('service_frequency') ? 'is-invalid' : '' }}">
                            <option value="">— Select —</option>
                            @foreach(['Monthly','Quarterly','Semi-Annually','Annually'] as $opt)
                                <option value="{{ $opt }}" {{ old('service_frequency') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('service_frequency') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Amount (BD excl. VAT)</label>
                        <input type="number" name="service_amount_bd_excl_vat"
                            class="mfield-input {{ $errors->has('service_amount_bd_excl_vat') ? 'is-invalid' : '' }}"
                            value="{{ old('service_amount_bd_excl_vat') }}" placeholder="0.000" min="0" step="0.001">
                        @error('service_amount_bd_excl_vat') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Service Start</label>
                        <input type="date" name="service_start_date"
                            class="mfield-input {{ $errors->has('service_start_date') ? 'is-invalid' : '' }}"
                            value="{{ old('service_start_date') }}">
                        @error('service_start_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Service End</label>
                        <input type="date" name="service_end_date"
                            class="mfield-input {{ $errors->has('service_end_date') ? 'is-invalid' : '' }}"
                            value="{{ old('service_end_date') }}">
                        @error('service_end_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- TAB 6: FINANCIAL --}}
            <div class="mtab-panel" id="mc-financial">
                <div class="mfield-grid">

                    <div class="mfield-group">
                        <label class="mfield-label">Rental Income Ledger</label>
                        <input type="text" name="rental_income_ledger"
                            class="mfield-input {{ $errors->has('rental_income_ledger') ? 'is-invalid' : '' }}"
                            value="{{ old('rental_income_ledger') }}" placeholder="e.g. 4100-RENTAL" maxlength="50">
                        @error('rental_income_ledger') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Security Deposit</label>
                        <input type="number" name="security_deposit"
                            class="mfield-input {{ $errors->has('security_deposit') ? 'is-invalid' : '' }}"
                            value="{{ old('security_deposit') }}" placeholder="0.000" min="0" step="0.001">
                        @error('security_deposit') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group span-full">
                        <label class="mfield-label">EWA Cap <span style="font-size:11px;color:var(--text-muted);font-weight:400;text-transform:none">(BHD/bill — landlord covers up to this amount per EWA bill)</span></label>
                        <div style="position:relative;max-width:320px;">
                            <input type="number" name="ewa_cap"
                                class="mfield-input {{ $errors->has('ewa_cap') ? 'is-invalid' : '' }}"
                                value="{{ old('ewa_cap') }}" placeholder="0.000 — leave blank if tenant pays full bill"
                                min="0" step="0.001" style="padding-right:52px;">
                            <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:700;color:var(--text-muted);pointer-events:none;">BHD</span>
                        </div>
                        @error('ewa_cap') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            </form>
        </div>

        <div class="modal-footer">
            <div class="modal-footer-nav">
                <button type="button" class="btn btn-outline btn-sm" id="mc-prev-btn" onclick="prevMTab()" style="display:none;">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-outline btn-sm" id="mc-next-btn" onclick="nextMTab()">
                    Next <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-outline" onclick="closeContractModal()">Cancel</button>
                <button type="submit" form="contractForm" class="btn btn-primary" id="contractSubmitBtn" onclick="handleContractSubmit(this)">
                    <i class="fa-solid fa-floppy-disk"></i> Create Contract
                </button>
            </div>
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

// ── MODAL OPEN/CLOSE ─────────────────────────────────────────
function openContractModal() {
    document.getElementById('contractModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        const first = document.querySelector('#mc-info input[name="lease_agreement_no"]');
        if (first) first.focus();
    }, 320);
}
function closeContractModal() {
    document.getElementById('contractModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('contractModal').addEventListener('click', function(e) {
    if (e.target === this) closeContractModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('contractModal').classList.contains('open')) {
        closeContractModal();
    }
});

// ── TABS ─────────────────────────────────────────────────────
const TABS = ['mc-info','mc-location','mc-lease','mc-rent','mc-service','mc-financial'];
let currentTab = 0;

function switchMTab(tabId) {
    currentTab = TABS.indexOf(tabId);
    TABS.forEach((id, i) => {
        const panel = document.getElementById(id);
        const btn   = document.querySelector(`[data-tab="${id}"]`);
        if (i === currentTab) {
            panel.classList.add('active');
            btn.classList.add('active');
        } else {
            panel.classList.remove('active');
            btn.classList.remove('active');
        }
    });
    document.getElementById('mc-prev-btn').style.display = currentTab === 0 ? 'none' : '';
    document.getElementById('mc-next-btn').style.display = currentTab === TABS.length - 1 ? 'none' : '';
}

function nextMTab() { if (currentTab < TABS.length - 1) switchMTab(TABS[currentTab + 1]); }
function prevMTab() { if (currentTab > 0) switchMTab(TABS[currentTab - 1]); }

// ── LOCATION SEARCH DROPDOWNS (property / block / floor / unit) ─
@php
    $mcBuildingsJson = $buildings->map(fn ($b) => ['id' => $b->id, 'name' => $b->property_name, 'code' => $b->property_code]);
    $mcFloorsJson    = $floors->map(fn ($f) => ['id' => $f->id, 'building_id' => $f->building_id, 'name' => $f->floor_name, 'code' => $f->floor_code, 'block_name' => $f->block_name, 'block_code' => $f->block_code]);
    $mcUnitsJson     = $units->map(fn ($u) => ['id' => $u->id, 'name' => $u->unit_name, 'building_id' => $u->building_id, 'floor_id' => $u->floor_id]);
@endphp
(function() {
    const BUILDINGS = @json($mcBuildingsJson);
    const FLOORS     = @json($mcFloorsJson);
    const UNITS      = @json($mcUnitsJson);

    const scope = { buildingId: null, floorId: null };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function wireCombo(comboId, { items, matchText, render, onPick }) {
        const root     = document.getElementById(comboId);
        const input    = root.querySelector('input[type="text"]');
        const dropdown = root.querySelector('.search-combo-dropdown');

        function show(list) {
            if (!list.length) {
                dropdown.innerHTML = '<div class="search-combo-empty">No matches</div>';
            } else {
                dropdown.innerHTML = list.map((item, i) => `<div class="search-combo-item" data-i="${i}">${render(item)}</div>`).join('');
                dropdown.querySelectorAll('.search-combo-item').forEach(el => {
                    el.addEventListener('click', function() {
                        onPick(list[+this.dataset.i]);
                        dropdown.classList.remove('open');
                    });
                });
            }
            dropdown.classList.add('open');
        }

        function open() {
            const q = input.value.trim().toLowerCase();
            show(items().filter(item => !q || matchText(item).toLowerCase().includes(q)));
        }

        input.addEventListener('focus', open);
        input.addEventListener('input', open);
        document.addEventListener('click', e => { if (!root.contains(e.target)) dropdown.classList.remove('open'); });
    }

    function fillProperty(b) {
        document.getElementById('mc_property_name').value = b.name;
        document.getElementById('mc_property_code').value = b.code;
        scope.buildingId = b.id;
    }

    wireCombo('mc_property_name_combo', {
        items: () => BUILDINGS,
        matchText: b => b.name,
        render: b => `${esc(b.name)} <span style="color:var(--text-muted);font-size:11px">(${esc(b.code)})</span>`,
        onPick: fillProperty,
    });

    wireCombo('mc_property_code_combo', {
        items: () => BUILDINGS,
        matchText: b => b.code,
        render: b => `${esc(b.code)} <span style="color:var(--text-muted);font-size:11px">— ${esc(b.name)}</span>`,
        onPick: fillProperty,
    });

    wireCombo('mc_block_name_combo', {
        items: () => {
            const seen = new Set(), list = [];
            FLOORS.filter(f => f.block_name && (!scope.buildingId || f.building_id === scope.buildingId))
                .forEach(f => { if (!seen.has(f.block_name)) { seen.add(f.block_name); list.push(f); } });
            return list;
        },
        matchText: f => f.block_name,
        render: f => f.block_code ? `${esc(f.block_name)} <span style="color:var(--text-muted);font-size:11px">(${esc(f.block_code)})</span>` : esc(f.block_name),
        onPick: f => {
            document.getElementById('mc_block_name').value = f.block_name || '';
            document.getElementById('mc_block_code').value = f.block_code || '';
        },
    });

    wireCombo('mc_floor_name_combo', {
        items: () => FLOORS.filter(f => !scope.buildingId || f.building_id === scope.buildingId),
        matchText: f => f.name,
        render: f => f.block_name ? `${esc(f.name)} <span style="color:var(--text-muted);font-size:11px">— ${esc(f.block_name)}</span>` : esc(f.name),
        onPick: f => {
            document.getElementById('mc_floor_name').value = f.name;
            document.getElementById('mc_floor_code').value = f.code || '';
            if (f.block_name) document.getElementById('mc_block_name').value = f.block_name;
            if (f.block_code) document.getElementById('mc_block_code').value = f.block_code;
            scope.floorId = f.id;
        },
    });

    wireCombo('mc_unit_combo', {
        items: () => UNITS.filter(u => (!scope.buildingId || u.building_id === scope.buildingId) && (!scope.floorId || u.floor_id === scope.floorId)),
        matchText: u => u.name,
        render: u => esc(u.name),
        onPick: u => {
            document.getElementById('mc_unit_id').value = u.id;
            document.getElementById('mc_unit_search').value = u.name;
            document.getElementById('mc_unit_text').value = u.name;
        },
    });
    document.getElementById('mc_unit_search').addEventListener('input', function() {
        document.getElementById('mc_unit_id').value = '';
    });
})();

// ── TENANT SEARCH DROPDOWN ─────────────────────────────────────
(function() {
    const combo    = document.getElementById('mc_tenant_combo');
    const search   = document.getElementById('mc_tenant_search');
    const hidden   = document.getElementById('mc_tenant_id');
    const dropdown = document.getElementById('mc_tenant_dropdown');
    const empty    = document.getElementById('mc_tenant_empty');
    const items    = Array.from(dropdown.querySelectorAll('.tenant-combo-item'));

    function filter() {
        const q = search.value.trim().toLowerCase();
        let visible = 0;
        items.forEach(item => {
            const match = !q || item.dataset.name.toLowerCase().includes(q);
            item.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        empty.classList.toggle('show', visible === 0);
    }

    search.addEventListener('focus', function() {
        filter();
        dropdown.classList.add('open');
    });
    search.addEventListener('input', function() {
        hidden.value = '';
        filter();
        dropdown.classList.add('open');
    });

    items.forEach(item => {
        item.addEventListener('click', function() {
            hidden.value = item.dataset.id;
            search.value = item.dataset.name;
            dropdown.classList.remove('open');
        });
    });

    document.addEventListener('click', function(e) {
        if (!combo.contains(e.target)) dropdown.classList.remove('open');
    });
})();

// ── SUBMIT ───────────────────────────────────────────────────
function handleContractSubmit(btn) {
    const tenantId = document.getElementById('mc_tenant_id');
    if (!tenantId.value) {
        switchMTab('mc-info');
        document.getElementById('mc_tenant_search').focus();
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    document.getElementById('contractForm').submit();
}

// ── ERROR DOTS — show dot on tab that has an error ───────────
@php
    $tabErrorMap = [
        'mc-info'     => ['date','lease_agreement_no','tenant_id','description'],
        'mc-location' => ['property_name','property_code','block_name','block_code','floor_name','floor_code','unit_id','unit'],
        'mc-lease'    => ['lease_start_date','lease_end_date','lease_break_date','notice_period'],
        'mc-rent'     => ['invoicing_frequency','currency','rent_start_date','rent_end_date','rent_per_month'],
        'mc-service'  => ['service_frequency','service_amount_bd_excl_vat','service_start_date','service_end_date'],
        'mc-financial'=> ['rental_income_ledger','security_deposit'],
    ];
    $firstErrorTab = null;
    foreach($tabErrorMap as $tab => $fields) {
        foreach($fields as $f) {
            if($errors->has($f)) {
                if(!$firstErrorTab) $firstErrorTab = $tab;
                break;
            }
        }
    }
@endphp

@foreach($tabErrorMap as $tab => $fields)
    @if(collect($fields)->some(fn($f) => $errors->has($f)))
        document.getElementById('dot-{{ $tab }}').classList.add('show');
    @endif
@endforeach

@if($errors->any())
    openContractModal();
    @if($firstErrorTab)
        switchMTab('{{ $firstErrorTab }}');
    @endif
@endif
</script>
@endpush
