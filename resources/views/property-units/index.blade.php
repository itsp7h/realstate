@extends('layouts.admin')

@section('title', 'Property Units')
@section('topbar-title', 'Property Units')

@push('styles')
<style>
/* ── STATS ─────────────────────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); padding: 18px 20px; display: flex; align-items: center; gap: 14px; box-shadow: var(--shadow-sm); transition: box-shadow 0.2s, transform 0.2s; }
.stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.stat-icon { width: 44px; height: 44px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
.stat-icon.green  { background: #ECFDF5; color: var(--success); }
.stat-icon.blue   { background: #EFF6FF; color: var(--info); }
.stat-icon.purple { background: #F5F3FF; color: #7C3AED; }
.stat-val { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.stat-lbl { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

/* ── FILTER BAR ─────────────────────────────────────────── */
.filter-bar { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; padding: 16px 20px; background: var(--page-bg); border-bottom: 1px solid var(--card-border); }
.filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 140px; }
.filter-group label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
.filter-group input, .filter-group select { padding: 8px 12px; font-size: 13px; border: 1.5px solid var(--input-border); border-radius: var(--radius-sm); background: var(--card-bg); color: var(--text-primary); font-family: 'Plus Jakarta Sans', sans-serif; outline: none; appearance: none; -webkit-appearance: none; transition: border-color 0.18s, box-shadow 0.18s; }
.filter-group input:focus, .filter-group select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.filter-group select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
.filter-actions { display: flex; gap: 8px; align-items: flex-end; margin-left: auto; }

/* ── TABLE ──────────────────────────────────────────────── */
.unit-code { font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--text-primary); font-size: 13.5px; }
.unit-prop { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.rent-val  { font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--text-primary); }
.rent-per  { font-size: 11px; color: var(--text-muted); }
.action-btns { display: flex; gap: 6px; }
.table-footer { padding: 14px 20px; border-top: 1px solid var(--card-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.pagination { display: flex; gap: 4px; align-items: center; }
.page-btn { width: 32px; height: 32px; border: 1.5px solid var(--card-border); background: var(--card-bg); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: var(--text-secondary); cursor: pointer; text-decoration: none; transition: all 0.15s; }
.page-btn:hover { background: var(--page-bg); color: var(--text-primary); }
.page-btn.active { background: var(--accent); border-color: var(--accent); color: #0B1120; }
.result-count { font-size: 13px; color: var(--text-muted); }
.result-count strong { color: var(--text-primary); }
.empty-state { text-align: center; padding: 60px 20px; }
.empty-icon { width: 64px; height: 64px; background: var(--page-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--text-muted); margin: 0 auto 16px; }
.empty-state h4 { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
.empty-state p { font-size: 13px; color: var(--text-muted); }

/* ── MODAL OVERLAY ──────────────────────────────────────── */
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
    width: 100%; max-width: 700px; max-height: 90vh;
    display: flex; flex-direction: column;
    transform: translateY(20px) scale(0.98);
    transition: transform 0.3s cubic-bezier(0.22,1,0.36,1);
    overflow: hidden;
}
.modal-overlay.open .modal-box { transform: translateY(0) scale(1); }

/* ── MODAL HEADER ───────────────────────────────────────── */
.modal-header { padding: 20px 24px 0; flex-shrink: 0; }
.modal-header-top { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
.modal-header-icon { width: 40px; height: 40px; border-radius: 10px; background: var(--accent-dim); border: 1px solid rgba(232,184,109,0.25); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 16px; flex-shrink: 0; }
.modal-header-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.modal-header-sub { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
.modal-close-btn { width: 32px; height: 32px; border-radius: var(--radius-sm); border: 1.5px solid var(--card-border); background: transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 13px; transition: all 0.15s; margin-left: auto; flex-shrink: 0; }
.modal-close-btn:hover { background: var(--page-bg); color: var(--text-primary); }

/* ── STEP TRACK ─────────────────────────────────────────── */
.step-track { display: flex; align-items: flex-start; margin-bottom: 20px; }
.step-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; position: relative; cursor: default; }
.step-item:not(:last-child)::after { content: ''; position: absolute; top: 13px; left: 50%; right: -50%; height: 1.5px; background: var(--card-border); transition: background 0.4s ease; z-index: 0; }
.step-item.done:not(:last-child)::after, .step-item.active:not(:last-child)::after { background: var(--accent); }
.step-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--card-border); background: var(--card-bg); display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; font-size: 11px; font-weight: 800; color: var(--text-muted); flex-shrink: 0; position: relative; z-index: 1; transition: all 0.3s ease; }
.step-item.active .step-dot { border-color: var(--accent); background: var(--accent); color: #0B1120; box-shadow: 0 0 0 3px var(--accent-dim); }
.step-item.done   .step-dot { border-color: var(--accent); background: var(--accent-dim); color: var(--accent); }
.step-name { font-size: 10.5px; font-weight: 600; color: var(--text-muted); white-space: nowrap; text-align: center; position: relative; z-index: 1; transition: color 0.2s; }
.step-item.active .step-name { color: var(--accent); }
.step-item.done   .step-name { color: var(--text-secondary); }

/* ── MODAL BODY ─────────────────────────────────────────── */
.modal-body { padding: 0 24px; overflow-y: auto; flex: 1; }
.modal-body::-webkit-scrollbar { width: 4px; }
.modal-body::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }

/* ── STEP PANELS ────────────────────────────────────────── */
.step-panel { display: none; padding: 6px 0 16px; }
.step-panel.active { display: block; animation: mStepIn 0.25s ease both; }
@keyframes mStepIn { from { opacity: 0; transform: translateX(14px); } to { opacity: 1; transform: translateX(0); } }

.step-panel-heading { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--card-border); }
.step-panel-icon { width: 34px; height: 34px; border-radius: 8px; background: var(--accent-dim); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 14px; flex-shrink: 0; }
.step-panel-title { font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: var(--text-primary); }
.step-panel-sub   { font-size: 11.5px; color: var(--text-muted); margin-top: 1px; }

/* ── MODAL FIELDS ───────────────────────────────────────── */
.mfield-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px 18px; }
.mfield-grid .mspan-2    { grid-column: span 2; }
.mfield-grid .mspan-full { grid-column: 1 / -1; }
.mfield-group { display: flex; flex-direction: column; }
.mfield-label { font-size: 10.5px; font-weight: 700; color: var(--text-secondary); letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 3px; }
.mfield-label .req { color: var(--danger); font-size: 13px; line-height: 1; }
.mfield-wrap { position: relative; }
.mfield-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 11px; pointer-events: none; transition: color 0.2s; }
.mhas-icon input, .mhas-icon select { padding-left: 32px !important; }
.mfield-wrap:focus-within .mfield-icon { color: var(--accent); }
.minput, .mselect {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: #fff; color: var(--text-primary);
    font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px;
    outline: none; appearance: none; -webkit-appearance: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.minput::placeholder { color: var(--text-muted); opacity: 0.6; }
.minput:hover, .mselect:hover { border-color: #B0BCCF; }
.minput:focus, .mselect:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: #FFFDF8; }
.minput.is-invalid, .mselect.is-invalid { border-color: var(--danger); background: #FFF8F8; }
.mselect { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%2394A3B8' d='M5 7L0.669873 2.5L9.33013 2.5L5 7Z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 11px center; padding-right: 30px; cursor: pointer; }
.mfield-error { display: flex; align-items: center; gap: 4px; margin-top: 4px; font-size: 11px; color: var(--danger); font-weight: 500; }

/* Building preview in modal */
.m-bldg-preview {
    display: none; align-items: center; gap: 10px; margin-top: 10px;
    background: var(--accent-dim); border: 1px solid rgba(232,184,109,0.25);
    border-radius: var(--radius-sm); padding: 10px 14px;
}
.m-bldg-preview.visible { display: flex; }
.m-bldg-preview-icon { width: 30px; height: 30px; border-radius: 6px; background: var(--accent); color: #0B1120; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
.m-bldg-preview-name { font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-primary); }
.m-bldg-preview-sub  { font-size: 11px; color: var(--text-secondary); margin-top: 1px; }
.m-lock-badge { margin-left: auto; font-size: 10px; color: var(--accent); font-weight: 600; display: flex; align-items: center; gap: 3px; padding: 2px 8px; background: rgba(232,184,109,0.12); border-radius: 20px; }

/* Sub-dividers inside step panels */
.m-sub-divider { display: flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin: 14px 0 10px; }
.m-sub-divider::after { content: ''; flex: 1; height: 1px; background: var(--card-border); }

/* ── MODAL FOOTER ───────────────────────────────────────── */
.modal-footer { padding: 14px 24px; border-top: 1px solid var(--card-border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-shrink: 0; background: var(--card-bg); }
.step-counter { font-size: 11px; font-weight: 600; color: var(--text-muted); padding: 4px 10px; background: var(--page-bg); border-radius: 20px; border: 1px solid var(--card-border); }
.step-counter strong { color: var(--accent); }

@media (max-width: 620px) {
    .modal-box { max-height: 100vh; border-radius: 0; }
    .modal-overlay { padding: 0; align-items: flex-end; }
    .mfield-grid { grid-template-columns: 1fr; }
    .mfield-grid .mspan-2 { grid-column: span 1; }
    .step-name { display: none; }
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
            <span>Property Units</span>
        </div>
        <h1 class="page-header-title">Property Units</h1>
        <p class="page-header-sub">Manage all property unit records</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('export.units', request()->only(['search','property_code','unit_type','unit_condition'])) }}" class="btn btn-success">
            <i class="fa-solid fa-file-excel"></i> Export
        </a>
        <button type="button" class="btn btn-outline" onclick="openImport_units()">
            <i class="fa-solid fa-file-import"></i> Import
        </button>
        <button type="button" class="btn btn-primary" onclick="openUnitModal()">
            <i class="fa-solid fa-plus"></i> Add Unit
        </button>
    </div>
</div>

@include('components.import-modal', [
    'type'        => 'units',
    'label'       => 'Units',
    'icon'        => 'fa-door-open',
    'routeName'   => 'import.units',
])

@include('components.import-result')

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-door-open"></i></div>
        <div><div class="stat-val">{{ $stats['total'] ?? 0 }}</div><div class="stat-lbl">Total Units</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="stat-val">{{ $stats['furnished'] ?? 0 }}</div><div class="stat-lbl">Furnished</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-wrench"></i></div>
        <div><div class="stat-val">{{ $stats['fitted'] ?? 0 }}</div><div class="stat-lbl">Fitted</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#ECFDF5;color:#059669;"><i class="fa-solid fa-key"></i></div>
        <div>
            <div class="stat-val" style="color:#059669;">{{ $stats['occupied'] ?? 0 }}</div>
            <div class="stat-lbl">Occupied</div>
            @if(($stats['total'] ?? 0) > 0)
            <div style="font-size:11px;color:var(--text-muted);margin-top:1px;">{{ $stats['total'] - ($stats['occupied'] ?? 0) }} vacant</div>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-building"></i></div>
        <div><div class="stat-val">{{ $stats['properties'] ?? 0 }}</div><div class="stat-lbl">Properties</div></div>
    </div>
</div>

{{-- FILTER + TABLE --}}
<div class="card" style="overflow:hidden;">

    <form method="GET" action="{{ route('property-units.index') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group" style="flex:1;min-width:180px;">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Unit name, description…" oninput="debounceSubmit()">
            </div>
            <div class="filter-group">
                <label>Property</label>
                <select name="property_code" onchange="this.form.submit()">
                    <option value="">All Properties</option>
                    @foreach(['AAL','MP1','MP2','MP3','MP4','MP5'] as $code)
                        <option value="{{ $code }}" {{ request('property_code') == $code ? 'selected' : '' }}>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Unit Type</label>
                <select name="unit_type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach(['Studio','1BHK','2BHK','3BHK','4BHK','Commercial'] as $type)
                        <option value="{{ $type }}" {{ request('unit_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Condition</label>
                <select name="unit_condition" onchange="this.form.submit()">
                    <option value="">All Conditions</option>
                    @foreach(['Furnished','Fitted','Semi-Furnished','Unfurnished'] as $cond)
                        <option value="{{ $cond }}" {{ request('unit_condition') == $cond ? 'selected' : '' }}>{{ $cond }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Occupancy</label>
                <select name="occupancy" onchange="this.form.submit()">
                    <option value="">All Units</option>
                    <option value="occupied" {{ request('occupancy') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                    <option value="vacant"   {{ request('occupancy') === 'vacant'   ? 'selected' : '' }}>Vacant</option>
                </select>
            </div>
            <div class="filter-actions">
                @if(request()->hasAny(['search','property_code','unit_type','unit_condition','occupancy']))
                    <a href="{{ route('property-units.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Clear</a>
                @endif
            </div>
        </div>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Property</th>
                    <th>Land Lord</th>
                    <th>Floor / Block</th>
                    <th>Type</th>
                    <th>Condition</th>
                    <th>Area</th>
                    <th>Rent/Month</th>
                    <th>Deposit</th>
                    <th>Elec. A/c</th>
                    <th>Occupancy</th>
                    <th>View</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                <tr data-href="{{ route('property-units.show', $unit) }}" style="cursor:pointer">
                    <td>
                        <div class="unit-code">{{ $unit->unit_name }}</div>
                        <div class="unit-prop">{{ $unit->description }}</div>
                    </td>
                    <td>
                        <span class="badge badge-gold">{{ $unit->property_code }}</span>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">{{ $unit->property_name }}</div>
                    </td>
                    <td>
                        @if($unit->land_lord_name) <div style="font-size:13px;">{{ $unit->land_lord_name }}</div>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        <div style="font-size:13px;">{{ optional($unit->floor)->floor_name ?? '—' }}</div>
                        <div style="font-size:11px;color:var(--text-muted);">{{ optional($unit->floor)->block_name ?? '' }}</div>
                    </td>
                    <td><span class="badge badge-blue">{{ $unit->unit_type ?: '—' }}</span></td>
                    <td>
                        <span class="badge {{ $unit->unit_condition === 'Furnished' ? 'badge-green' : 'badge-gray' }}">
                            {{ $unit->unit_condition ?: '—' }}
                        </span>
                    </td>
                    <td>
                        @if($unit->area_inside)
                            <div>{{ number_format($unit->area_inside, 1) }}</div>
                            <div style="font-size:11px;color:var(--text-muted);">{{ $unit->area_unit }}</div>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        @if($unit->rent_per_month)
                            <div class="rent-val">{{ number_format($unit->rent_per_month) }}</div>
                            <div class="rent-per">BHD / mo</div>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        @if($unit->security_deposit_amount)
                            <div class="rent-val">{{ number_format($unit->security_deposit_amount) }}</div>
                            <div class="rent-per">BHD</div>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        @if($unit->electricity_ac_no)
                            <div style="font-size:13px;font-family:'Outfit',sans-serif;">{{ $unit->electricity_ac_no }}</div>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        @if($unit->activeContract)
                            <span class="badge badge-green" style="white-space:nowrap;">
                                <i class="fa-solid fa-circle" style="font-size:7px;vertical-align:middle;margin-right:4px;"></i>Occupied
                            </span>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">{{ $unit->activeContract->tenant_name }}</div>
                        @else
                            <span class="badge badge-gray">
                                <i class="fa-regular fa-circle" style="font-size:7px;vertical-align:middle;margin-right:4px;"></i>Vacant
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($unit->view) <span class="badge badge-gray">{{ $unit->view }}</span>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td onclick="event.stopPropagation()">
                        <div class="action-btns" style="justify-content:flex-end;">
                            <a href="{{ route('property-units.show', $unit) }}" class="btn btn-outline btn-sm"><i class="fa-regular fa-eye"></i></a>
                            <a href="{{ route('property-units.edit', $unit) }}" class="btn btn-outline btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                            <form method="POST" action="{{ route('property-units.destroy', $unit) }}" onsubmit="return confirm('Delete this unit?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="13">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-door-open"></i></div>
                        <h4>No units found</h4>
                        <p>Try adjusting filters or
                            <button type="button" onclick="openUnitModal()" style="background:none;border:none;cursor:pointer;color:var(--accent);font-weight:600;padding:0;">add a new unit</button>.
                        </p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $units->firstItem() ?? 0 }}–{{ $units->lastItem() ?? 0 }}</strong>
            of <strong>{{ $units->total() }}</strong> units
        </div>
        <div class="pagination">
            @if($units->onFirstPage())
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></span>
            @else
                <a href="{{ $units->previousPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></a>
            @endif
            @foreach($units->getUrlRange(max(1,$units->currentPage()-2), min($units->lastPage(),$units->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="page-btn {{ $page == $units->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            @if($units->hasMorePages())
                <a href="{{ $units->nextPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></a>
            @else
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ADD UNIT — 4-STEP MODAL
═══════════════════════════════════════════════════════ --}}
@php
    $mVisible = collect($formFields ?? [])->filter(fn($f) => !empty($f['visible']))->pluck('name')->all();
    $mShowAll = empty($mVisible);
    $ms = fn(string $f) => $mShowAll || in_array($f, $mVisible);
    $mv = fn(string $f, $d = '') => old($f, $d);
    $hasCustom = count($customFieldDefs ?? []) > 0;
    $totalModalSteps = $hasCustom ? 5 : 4;
@endphp

<div class="modal-overlay" id="unitModal" role="dialog" aria-modal="true">

    <div class="modal-box">

        {{-- HEADER --}}
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-solid fa-door-open"></i></div>
                <div style="flex:1;">
                    <div class="modal-header-title">Add New Unit</div>
                    <div class="modal-header-sub">Complete all steps to register a property unit</div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeUnitModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>

            {{-- STEP TRACK --}}
            <div class="step-track" id="uStepTrack">
                <div class="step-item active" data-step="1"><div class="step-dot">1</div><span class="step-name">Building</span></div>
                <div class="step-item"         data-step="2"><div class="step-dot">2</div><span class="step-name">Unit</span></div>
                <div class="step-item"         data-step="3"><div class="step-dot">3</div><span class="step-name">Pricing</span></div>
                <div class="step-item"         data-step="4"><div class="step-dot">4</div><span class="step-name">Legal</span></div>
                @if($hasCustom)
                <div class="step-item"         data-step="5"><div class="step-dot">5</div><span class="step-name">Custom</span></div>
                @endif
            </div>
        </div>

        {{-- BODY --}}
        <div class="modal-body">
            <form method="POST" action="{{ route('property-units.store') }}" id="addUnitForm" novalidate>
                @csrf

                {{-- STEP 1: BUILDING & FLOOR + PROPERTY IDENTITY --}}
                <div class="step-panel active" id="upanel-1">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-link"></i></div>
                        <div>
                            <div class="step-panel-title">Building & Property</div>
                            <div class="step-panel-sub">Link to a building — property fields auto-fill</div>
                        </div>
                    </div>

                    <div class="mfield-grid">
                        <div class="mfield-group mspan-full">
                            <label class="mfield-label">Building</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-building mfield-icon"></i>
                                <select name="building_id" id="mBuildingSelect" class="mselect {{ $errors->has('building_id') ? 'is-invalid' : '' }}" onchange="mLoadBuilding(this.value)">
                                    <option value="">— Select building to auto-fill property info —</option>
                                    @foreach($buildings as $b)
                                        <option value="{{ $b->id }}" {{ $mv('building_id') == $b->id ? 'selected' : '' }}>
                                            {{ $b->property_code }} — {{ $b->property_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('building_id') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror

                            <div class="m-bldg-preview" id="mBldgPreview">
                                <div class="m-bldg-preview-icon"><i class="fa-solid fa-building"></i></div>
                                <div>
                                    <div class="m-bldg-preview-name" id="mPreviewName">—</div>
                                    <div class="m-bldg-preview-sub"  id="mPreviewSub">—</div>
                                </div>
                                <div class="m-lock-badge"><i class="fa-solid fa-lock"></i> Auto-filled</div>
                            </div>
                        </div>

                        <div class="mfield-group mspan-full">
                            <label class="mfield-label">Floor <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:9px;color:var(--text-muted);">(optional)</span></label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-layer-group mfield-icon"></i>
                                <select name="floor_id" id="mFloorSelect" class="mselect {{ $errors->has('floor_id') ? 'is-invalid' : '' }}" disabled>
                                    <option value="">— Select a building first —</option>
                                </select>
                            </div>
                            @error('floor_id') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Property Identity (hidden inputs, populated from building) --}}
                    <input type="hidden" name="property_name"     id="m_property_name"     value="{{ $mv('property_name') }}">
                    <input type="hidden" name="land_lord_name"    id="m_land_lord_name"    value="{{ $mv('land_lord_name') }}">
                    <input type="hidden" name="road"              id="m_road"              value="{{ $mv('road') }}">
                    <input type="hidden" name="area"              id="m_area"              value="{{ $mv('area') }}">
                    <input type="hidden" name="city"              id="m_city"              value="{{ $mv('city') }}">
                    <input type="hidden" name="building_no"       id="m_building_no"       value="{{ $mv('building_no') }}">
                    <input type="hidden" name="block"             id="m_block"             value="{{ $mv('block') }}">
                    <input type="hidden" name="type_of_ownership" id="m_type_of_ownership" value="{{ $mv('type_of_ownership') }}">
                    <input type="hidden" name="property_type"     id="m_property_type"     value="{{ $mv('property_type') }}">

                    {{-- property_code is required and validated — show it if no building, hidden if building selected --}}
                    <div style="margin-top:14px;" id="mPropCodeWrap">
                        <div class="mfield-grid">
                            <div class="mfield-group mspan-full">
                                <label class="mfield-label">Property Code <span class="req">*</span></label>
                                <div class="mfield-wrap mhas-icon">
                                    <i class="fa-solid fa-barcode mfield-icon"></i>
                                    <select name="property_code" id="m_property_code" class="mselect {{ $errors->has('property_code') ? 'is-invalid' : '' }}" required>
                                        <option value="">Select code…</option>
                                        @foreach(['AAL','MP1','MP2','MP3','MP4','MP5'] as $code)
                                            <option value="{{ $code }}" {{ $mv('property_code') == $code ? 'selected' : '' }}>{{ $code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('property_code') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>

                            @if($ms('property_name'))
                            <div class="mfield-group mspan-full" id="mPropNameWrap">
                                <label class="mfield-label">Property Name <span class="req">*</span></label>
                                <div class="mfield-wrap mhas-icon">
                                    <i class="fa-solid fa-tag mfield-icon"></i>
                                    <input type="text" id="m_property_name_visible"
                                        class="minput {{ $errors->has('property_name') ? 'is-invalid' : '' }}"
                                        value="{{ $mv('property_name') }}"
                                        placeholder="e.g. Miknas Plaza 2" maxlength="255"
                                        oninput="document.getElementById('m_property_name').value=this.value">
                                </div>
                                @error('property_name') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- STEP 2: UNIT DETAILS --}}
                <div class="step-panel" id="upanel-2">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-door-open"></i></div>
                        <div>
                            <div class="step-panel-title">Unit Details</div>
                            <div class="step-panel-sub">Configuration and attributes of this unit</div>
                        </div>
                    </div>
                    <div class="mfield-grid">

                        @if($ms('unit_name'))
                        <div class="mfield-group">
                            <label class="mfield-label">Unit Name <span class="req">*</span></label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-key mfield-icon"></i>
                                <input type="text" name="unit_name" class="minput {{ $errors->has('unit_name') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('unit_name') }}" placeholder="e.g. MP2 - 11" required maxlength="255">
                            </div>
                            @error('unit_name') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('description'))
                        <div class="mfield-group mspan-2">
                            <label class="mfield-label">Description</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-align-left mfield-icon"></i>
                                <input type="text" name="description" class="minput {{ $errors->has('description') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('description') }}" placeholder="e.g. Miknas Plaza 2 - Flat 11" maxlength="500">
                            </div>
                            @error('description') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('unit_type'))
                        <div class="mfield-group">
                            <label class="mfield-label">Unit Type</label>
                            <div class="mfield-wrap">
                                <select name="unit_type" class="mselect {{ $errors->has('unit_type') ? 'is-invalid' : '' }}">
                                    <option value="">Select type…</option>
                                    @foreach(['Studio','1BHK','2BHK','3BHK','4BHK','Penthouse','Commercial','Office'] as $opt)
                                        <option value="{{ $opt }}" {{ $mv('unit_type') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('unit_type') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('unit_condition'))
                        <div class="mfield-group">
                            <label class="mfield-label">Condition</label>
                            <div class="mfield-wrap">
                                <select name="unit_condition" class="mselect {{ $errors->has('unit_condition') ? 'is-invalid' : '' }}">
                                    <option value="">Select…</option>
                                    @foreach(['Furnished','Fitted','Semi-Furnished','Unfurnished','Shell & Core'] as $opt)
                                        <option value="{{ $opt }}" {{ $mv('unit_condition') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('unit_condition') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('view'))
                        <div class="mfield-group">
                            <label class="mfield-label">View</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-regular fa-eye mfield-icon"></i>
                                <input type="text" name="view" class="minput {{ $errors->has('view') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('view') }}" placeholder="e.g. Sea View" maxlength="100">
                            </div>
                            @error('view') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('creation_date'))
                        <div class="mfield-group">
                            <label class="mfield-label">Creation Date</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-regular fa-calendar mfield-icon"></i>
                                <input type="date" name="creation_date" class="minput {{ $errors->has('creation_date') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('creation_date') }}">
                            </div>
                            @error('creation_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('no_of_parkings_foc'))
                        <div class="mfield-group">
                            <label class="mfield-label">Parkings (FOC)</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-square-parking mfield-icon"></i>
                                <input type="number" name="no_of_parkings_foc" class="minput {{ $errors->has('no_of_parkings_foc') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('no_of_parkings_foc') }}" placeholder="0" min="0">
                            </div>
                            @error('no_of_parkings_foc') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                    </div>
                </div>

                {{-- STEP 3: AREA & PRICING --}}
                <div class="step-panel" id="upanel-3">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-coins"></i></div>
                        <div>
                            <div class="step-panel-title">Area &amp; Pricing</div>
                            <div class="step-panel-sub">Size measurements and financial details</div>
                        </div>
                    </div>
                    <div class="mfield-grid">

                        @if($ms('area_unit'))
                        <div class="mfield-group">
                            <label class="mfield-label">Area Unit</label>
                            <div class="mfield-wrap">
                                <select name="area_unit" class="mselect {{ $errors->has('area_unit') ? 'is-invalid' : '' }}">
                                    <option value="">Sq. Mt. / Sq. Ft.</option>
                                    @foreach(['Sq. Mt.','Sq. Ft.'] as $opt)
                                        <option value="{{ $opt }}" {{ $mv('area_unit') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('area_unit') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('area_inside'))
                        <div class="mfield-group">
                            <label class="mfield-label">Area Inside</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-ruler-combined mfield-icon"></i>
                                <input type="number" name="area_inside" step="0.01" min="0" class="minput {{ $errors->has('area_inside') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('area_inside') }}" placeholder="0.00">
                            </div>
                            @error('area_inside') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('area_terrace'))
                        <div class="mfield-group">
                            <label class="mfield-label">Area Terrace</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-ruler mfield-icon"></i>
                                <input type="number" name="area_terrace" step="0.01" min="0" class="minput {{ $errors->has('area_terrace') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('area_terrace') }}" placeholder="0.00">
                            </div>
                            @error('area_terrace') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('rate_per_area_unit'))
                        <div class="mfield-group">
                            <label class="mfield-label">Rate / Area Unit</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-tag mfield-icon"></i>
                                <input type="number" name="rate_per_area_unit" step="0.01" min="0" class="minput {{ $errors->has('rate_per_area_unit') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('rate_per_area_unit') }}" placeholder="0.00">
                            </div>
                            @error('rate_per_area_unit') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('rent_per_month'))
                        <div class="mfield-group">
                            <label class="mfield-label">Rent / Month (BHD)</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-money-bill-wave mfield-icon"></i>
                                <input type="number" name="rent_per_month" step="0.01" min="0" class="minput {{ $errors->has('rent_per_month') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('rent_per_month') }}" placeholder="0.00">
                            </div>
                            @error('rent_per_month') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                        @if($ms('security_deposit_amount'))
                        <div class="mfield-group">
                            <label class="mfield-label">Security Deposit (BHD)</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-shield-halved mfield-icon"></i>
                                <input type="number" name="security_deposit_amount" step="0.01" min="0" class="minput {{ $errors->has('security_deposit_amount') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('security_deposit_amount') }}" placeholder="0.00">
                            </div>
                            @error('security_deposit_amount') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif

                    </div>
                </div>

                {{-- STEP 4: LEGAL & UTILITIES --}}
                <div class="step-panel" id="upanel-4">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-bolt"></i></div>
                        <div>
                            <div class="step-panel-title">Legal &amp; Utilities</div>
                            <div class="step-panel-sub">Municipality reference and meter numbers</div>
                        </div>
                    </div>

                    @if($ms('municipality_nos'))
                    <div class="m-sub-divider"><i class="fa-solid fa-scale-balanced" style="font-size:9px;"></i> Legal</div>
                    <div class="mfield-grid" style="margin-bottom:4px;">
                        <div class="mfield-group mspan-full">
                            <label class="mfield-label">Municipality Nos.</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-file-contract mfield-icon"></i>
                                <input type="text" name="municipality_nos" class="minput {{ $errors->has('municipality_nos') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('municipality_nos') }}" placeholder="e.g. MUN-2024-001" maxlength="255">
                            </div>
                            @error('municipality_nos') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                    </div>
                    @endif

                    <div class="m-sub-divider"><i class="fa-solid fa-bolt" style="font-size:9px;"></i> Electricity</div>
                    <div class="mfield-grid" style="margin-bottom:4px;">
                        @if($ms('electricity_installation_date'))
                        <div class="mfield-group">
                            <label class="mfield-label">Install Date</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-regular fa-calendar mfield-icon"></i>
                                <input type="date" name="electricity_installation_date" class="minput {{ $errors->has('electricity_installation_date') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('electricity_installation_date') }}">
                            </div>
                            @error('electricity_installation_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif
                        @if($ms('electricity_meter_no'))
                        <div class="mfield-group">
                            <label class="mfield-label">Meter No.</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-gauge mfield-icon"></i>
                                <input type="text" name="electricity_meter_no" class="minput {{ $errors->has('electricity_meter_no') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('electricity_meter_no') }}" placeholder="e.g. KS003472" maxlength="100">
                            </div>
                            @error('electricity_meter_no') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif
                        @if($ms('electricity_ac_no'))
                        <div class="mfield-group mspan-2">
                            <label class="mfield-label">Electricity A/c No.</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-plug mfield-icon"></i>
                                <input type="text" name="electricity_ac_no" class="minput {{ $errors->has('electricity_ac_no') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('electricity_ac_no') }}" placeholder="Account number" maxlength="100">
                            </div>
                            @error('electricity_ac_no') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif
                    </div>

                    <div class="m-sub-divider"><i class="fa-solid fa-droplet" style="font-size:9px;"></i> Water</div>
                    <div class="mfield-grid">
                        @if($ms('water_installation_date'))
                        <div class="mfield-group">
                            <label class="mfield-label">Install Date</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-regular fa-calendar mfield-icon"></i>
                                <input type="date" name="water_installation_date" class="minput {{ $errors->has('water_installation_date') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('water_installation_date') }}">
                            </div>
                            @error('water_installation_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif
                        @if($ms('water_meter_no'))
                        <div class="mfield-group">
                            <label class="mfield-label">Water Meter No.</label>
                            <div class="mfield-wrap mhas-icon">
                                <i class="fa-solid fa-gauge mfield-icon"></i>
                                <input type="text" name="water_meter_no" class="minput {{ $errors->has('water_meter_no') ? 'is-invalid' : '' }}"
                                    value="{{ $mv('water_meter_no') }}" placeholder="e.g. 23H163009453" maxlength="100">
                            </div>
                            @error('water_meter_no') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                        </div>
                        @endif
                    </div>
                </div>

                {{-- STEP 5: CUSTOM FIELDS (optional) --}}
                @if($hasCustom)
                <div class="step-panel" id="upanel-5">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                        <div>
                            <div class="step-panel-title">Custom Fields</div>
                            <div class="step-panel-sub">Additional fields configured for this form</div>
                        </div>
                    </div>
                    <div class="mfield-grid">
                        @foreach($customFieldDefs as $def)
                            @if($mShowAll || in_array($def->name, $mVisible))
                            @php $cfv = old('custom_fields.'.$def->name, ''); @endphp
                            <div class="mfield-group {{ $def->field_type === 'textarea' ? 'mspan-full' : '' }}">
                                <label class="mfield-label">{{ $def->label }} @if($def->is_required)<span class="req">*</span>@endif</label>
                                <div class="mfield-wrap">
                                    @if($def->field_type === 'text')
                                        <input type="text" name="custom_fields[{{ $def->name }}]" class="minput" value="{{ $cfv }}" {{ $def->is_required ? 'required' : '' }}>
                                    @elseif($def->field_type === 'number')
                                        <input type="number" name="custom_fields[{{ $def->name }}]" class="minput" value="{{ $cfv }}" {{ $def->is_required ? 'required' : '' }}>
                                    @elseif($def->field_type === 'date')
                                        <input type="date" name="custom_fields[{{ $def->name }}]" class="minput" value="{{ $cfv }}" {{ $def->is_required ? 'required' : '' }}>
                                    @elseif($def->field_type === 'select')
                                        <select name="custom_fields[{{ $def->name }}]" class="mselect" {{ $def->is_required ? 'required' : '' }}>
                                            <option value="">Select…</option>
                                            @foreach($def->options ?? [] as $opt)
                                                <option value="{{ $opt }}" {{ $cfv == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                @error('custom_fields.'.$def->name) <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

            </form>
        </div>{{-- /modal-body --}}

        {{-- FOOTER --}}
        <div class="modal-footer">
            <div style="display:flex;align-items:center;gap:8px;">
                <button type="button" class="btn btn-outline" onclick="closeUnitModal()">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>
                <button type="button" class="btn btn-outline" id="uModalBackBtn" style="display:none;" onclick="uPrevStep()">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="step-counter" id="uStepCounter">Step <strong>1</strong> of <strong>{{ $totalModalSteps }}</strong></span>
                <button type="button" class="btn btn-primary" id="uModalNextBtn" onclick="uNextStep()">
                    Next Step <i class="fa-solid fa-arrow-right"></i>
                </button>
                <button type="submit" form="addUnitForm" class="btn btn-primary btn-lg" id="uModalSubmitBtn" style="display:none;" onclick="uHandleSubmit(this)">
                    <i class="fa-solid fa-floppy-disk"></i> Create Unit
                </button>
            </div>
        </div>

    </div>{{-- /modal-box --}}
</div>{{-- /modal-overlay --}}

@endsection

@push('scripts')
<script>
// ── FILTER ───────────────────────────────────────────────
let debounceTimer;
function debounceSubmit() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
}

// ── MODAL STATE ──────────────────────────────────────────
let uCurrentStep = 1;
const U_TOTAL_STEPS = {{ $totalModalSteps }};
const U_DATA_URL    = '{{ url("/property-units/building") }}';

function openUnitModal(startStep) {
    uCurrentStep = startStep || 1;
    uRenderStep();
    document.getElementById('unitModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        const panel = document.getElementById('upanel-' + uCurrentStep);
        const first = panel?.querySelector('input:not([type=hidden]), select');
        if (first) first.focus();
    }, 320);
}

function closeUnitModal() {
    document.getElementById('unitModal').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('unitModal').addEventListener('click', function(e) {
    if (e.target === this) closeUnitModal();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('unitModal').classList.contains('open')) closeUnitModal();
});

function uRenderStep() {
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('upanel-' + uCurrentStep)?.classList.add('active');

    document.querySelectorAll('#uStepTrack .step-item').forEach(item => {
        const s = parseInt(item.dataset.step);
        item.classList.remove('active', 'done');
        if (s === uCurrentStep) item.classList.add('active');
        else if (s < uCurrentStep) item.classList.add('done');
    });

    const isLast = uCurrentStep === U_TOTAL_STEPS;
    document.getElementById('uModalBackBtn').style.display   = uCurrentStep > 1 ? 'inline-flex' : 'none';
    document.getElementById('uModalNextBtn').style.display   = isLast ? 'none' : 'inline-flex';
    document.getElementById('uModalSubmitBtn').style.display = isLast ? 'inline-flex' : 'none';
    document.getElementById('uStepCounter').innerHTML =
        'Step <strong>' + uCurrentStep + '</strong> of <strong>' + U_TOTAL_STEPS + '</strong>';
}

function uValidateCurrentStep() {
    const panel = document.getElementById('upanel-' + uCurrentStep);
    let valid = true;
    panel?.querySelectorAll('[required]').forEach(f => {
        f.classList.remove('is-invalid');
        if (!f.value.trim()) { f.classList.add('is-invalid'); if (valid) f.focus(); valid = false; }
    });
    return valid;
}

function uNextStep() {
    if (!uValidateCurrentStep()) return;
    if (uCurrentStep < U_TOTAL_STEPS) { uCurrentStep++; uRenderStep(); document.getElementById('unitModal').querySelector('.modal-body').scrollTop = 0; }
}
function uPrevStep() {
    if (uCurrentStep > 1) { uCurrentStep--; uRenderStep(); document.getElementById('unitModal').querySelector('.modal-body').scrollTop = 0; }
}
function uHandleSubmit(btn) {
    if (!uValidateCurrentStep()) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    document.getElementById('addUnitForm').submit();
}

// ── BUILDING AUTO-FILL ───────────────────────────────────
const M_TEXT_MAP = { property_name: 'property_name', land_lord_name: 'land_lord_name', road: 'road', area: 'area', city: 'city' };
const M_NUM_MAP  = { building_no: 'building_no', block: 'block' };
const M_SEL_MAP  = { property_code: 'm_property_code', type_of_ownership: 'm_type_of_ownership', property_type: 'm_property_type' };

async function mLoadBuilding(buildingId) {
    const preview    = document.getElementById('mBldgPreview');
    const floorSel   = document.getElementById('mFloorSelect');
    const codeWrap   = document.getElementById('mPropCodeWrap');
    const nameWrap   = document.getElementById('mPropNameWrap');

    if (!buildingId) {
        if (preview)  preview.classList.remove('visible');
        if (codeWrap) codeWrap.style.display = '';
        if (nameWrap) nameWrap.style.display = '';
        mResetFloors();
        return;
    }

    try {
        const [dataRes, floorsRes] = await Promise.all([
            fetch(`${U_DATA_URL}/${buildingId}/data`),
            fetch(`${U_DATA_URL}/${buildingId}/floors`),
        ]);
        const data   = await dataRes.json();
        const floors = await floorsRes.json();

        // Fill hidden inputs
        Object.entries(M_TEXT_MAP).forEach(([apiKey, elId]) => {
            const el = document.getElementById('m_' + elId.replace('m_', ''));
            if (el) el.value = data[apiKey] ?? '';
        });
        const bno = document.getElementById('m_building_no'); if (bno) bno.value = data.building_no ?? '';
        const blk = document.getElementById('m_block');       if (blk) blk.value = data.block ?? '';
        const tow = document.getElementById('m_type_of_ownership'); if (tow) tow.value = data.type_of_ownership ?? '';
        const pt  = document.getElementById('m_property_type');     if (pt)  pt.value = data.property_type ?? '';

        // Set property_code select & hide manual fields
        const codeEl = document.getElementById('m_property_code');
        if (codeEl) codeEl.value = data.property_code ?? '';
        if (codeWrap) codeWrap.style.display = 'none';

        // Show preview
        if (preview) {
            document.getElementById('mPreviewName').textContent = data.property_name || '—';
            document.getElementById('mPreviewSub').textContent  =
                [data.property_type, data.type_of_ownership, data.city].filter(Boolean).join(' · ') || '—';
            preview.classList.add('visible');
        }

        // Populate floors
        floorSel.innerHTML = '<option value="">— No floor (optional) —</option>';
        floors.forEach(fl => {
            const lbl = fl.floor_name + (fl.floor_code ? ` (${fl.floor_code})` : '') + (fl.block_name ? ` — ${fl.block_name}` : '');
            floorSel.add(new Option(lbl, fl.id));
        });
        floorSel.disabled = false;

    } catch (e) { console.error('Failed loading building', e); }
}

function mResetFloors() {
    const floorSel = document.getElementById('mFloorSelect');
    floorSel.innerHTML = '<option value="">— Select a building first —</option>';
    floorSel.disabled = true;
}

// ── AUTO-OPEN ON VALIDATION ERRORS ──────────────────────
@if($errors->any())
(function () {
    const step1 = ['building_id','floor_id','property_name','property_code'];
    const step2 = ['unit_name','description','unit_type','creation_date','unit_condition','view','no_of_parkings_foc'];
    const step3 = ['area_unit','area_inside','area_terrace','rate_per_area_unit','rent_per_month','security_deposit_amount'];
    const keys  = @json($errors->keys());
    let start = 4;
    for (const k of keys) {
        if (step1.some(f => k === f || k.startsWith(f))) { start = 1; break; }
        if (step2.includes(k)) { start = 2; break; }
        if (step3.includes(k)) { start = 3; break; }
    }
    openUnitModal(start);
})();
@endif
</script>
@endpush
