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
        <a href="{{ route('lease-contracts.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Contract
        </a>
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
            <div class="filter-actions">
                @if(request()->hasAny(['search','property_code','status']))
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
                    // Progress within lease period
                    $start   = $contract->lease_start_date->timestamp;
                    $end     = $contract->lease_end_date->timestamp;
                    $now     = min(now()->timestamp, $end);
                    $pct     = $end > $start ? max(0, min(100, round(($now - $start) / ($end - $start) * 100))) : 100;
                @endphp
                <tr>
                    <td>
                        <span class="agr-no">{{ $contract->lease_agreement_no }}</span>
                        <div class="cell-sub">{{ $contract->date->format('d M Y') }}</div>
                    </td>
                    <td>
                        <div class="tenant-cell">
                            @if($contract->tenant)
                                <div class="tenant-av {{ $contract->tenant->tenant_type }}">
                                    {{ strtoupper(substr($contract->tenant_name, 0, 1)) }}
                                </div>
                            @else
                                <div class="tenant-av individual">
                                    {{ strtoupper(substr($contract->tenant_name, 0, 1)) }}
                                </div>
                            @endif
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
                        <div class="action-btns" style="justify-content:flex-end;">
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
                        <p>Try adjusting your filters or <a href="{{ route('lease-contracts.create') }}" style="color:var(--accent);font-weight:600;">create a new contract</a>.</p>
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
@endsection

@push('scripts')
<script>
let debounceTimer;
function debounceSubmit() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
}
</script>
@endpush
