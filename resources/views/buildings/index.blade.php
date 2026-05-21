@extends('layouts.admin')

@section('title', 'Buildings')
@section('topbar-title', 'Buildings')

@push('styles')
<style>
    /* ── STATS ─────────────────────────────────────────── */
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
        font-size: 18px;
        flex-shrink: 0;
    }
    .stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
    .stat-icon.green  { background: #ECFDF5; color: var(--success); }
    .stat-icon.blue   { background: #EFF6FF; color: var(--info); }
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
    .bldg-code { font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--text-primary); font-size: 13.5px; }
    .bldg-sub  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .action-btns { display: flex; gap: 6px; }
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

    /* ── MODAL OVERLAY ──────────────────────────────────── */
    .modal-overlay {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(11, 17, 32, 0.55);
        backdrop-filter: blur(4px);
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
        opacity: 0; pointer-events: none;
        transition: opacity 0.25s ease;
    }
    .modal-overlay.open {
        opacity: 1; pointer-events: all;
    }
    .modal-box {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(0,0,0,0.18), 0 8px 24px rgba(0,0,0,0.10);
        width: 100%; max-width: 680px;
        max-height: 90vh;
        display: flex; flex-direction: column;
        transform: translateY(20px) scale(0.98);
        transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
        overflow: hidden;
    }
    .modal-overlay.open .modal-box {
        transform: translateY(0) scale(1);
    }

    /* ── MODAL HEADER ───────────────────────────────────── */
    .modal-header {
        padding: 20px 24px 0;
        flex-shrink: 0;
    }
    .modal-header-top {
        display: flex; align-items: center; gap: 12px; margin-bottom: 18px;
    }
    .modal-header-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: var(--accent-dim); border: 1px solid rgba(232,184,109,0.25);
        display: flex; align-items: center; justify-content: center;
        color: var(--accent); font-size: 16px; flex-shrink: 0;
    }
    .modal-header-text { flex: 1; }
    .modal-header-title {
        font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 800;
        color: var(--text-primary); line-height: 1;
    }
    .modal-header-sub { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
    .modal-close-btn {
        width: 32px; height: 32px; border-radius: var(--radius-sm);
        border: 1.5px solid var(--card-border); background: transparent;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        color: var(--text-muted); font-size: 13px;
        transition: all 0.15s; flex-shrink: 0;
    }
    .modal-close-btn:hover { background: var(--page-bg); color: var(--text-primary); border-color: #B0BCCF; }

    /* ── STEP PROGRESS ──────────────────────────────────── */
    .step-track {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
    }
    .step-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        position: relative;
        cursor: default;
    }
    /* connector line between dot centres */
    .step-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 13px;          /* half of 28px dot, minus 1px for line thickness */
        left: 50%;
        right: -50%;
        height: 1.5px;
        background: var(--card-border);
        transition: background 0.4s ease;
        z-index: 0;
    }
    .step-item.done:not(:last-child)::after,
    .step-item.active:not(:last-child)::after { background: var(--accent); }
    .step-dot {
        width: 28px; height: 28px; border-radius: 50%;
        border: 2px solid var(--card-border);
        background: var(--card-bg);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Outfit', sans-serif; font-size: 11px; font-weight: 800;
        color: var(--text-muted);
        flex-shrink: 0;
        position: relative; z-index: 1;   /* sits above the connector line */
        transition: all 0.3s ease;
    }
    .step-item.active .step-dot {
        border-color: var(--accent); background: var(--accent);
        color: #0B1120; box-shadow: 0 0 0 3px var(--accent-dim);
    }
    .step-item.done .step-dot {
        border-color: var(--accent); background: var(--accent-dim); color: var(--accent);
    }
    .step-name {
        font-size: 11px; font-weight: 600; color: var(--text-muted);
        white-space: nowrap; text-align: center;
        transition: color 0.2s;
        position: relative; z-index: 1;
    }
    .step-item.active .step-name { color: var(--accent); }
    .step-item.done  .step-name { color: var(--text-secondary); }

    /* ── MODAL BODY ─────────────────────────────────────── */
    .modal-body {
        padding: 0 24px;
        overflow-y: auto;
        flex: 1;
    }
    .modal-body::-webkit-scrollbar { width: 4px; }
    .modal-body::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }

    /* ── STEP PANELS ────────────────────────────────────── */
    .step-panel { display: none; padding: 6px 0 16px; }
    .step-panel.active { display: block; animation: stepIn 0.25s ease both; }
    @keyframes stepIn {
        from { opacity: 0; transform: translateX(16px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    .step-panel-heading {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 18px; padding-bottom: 14px;
        border-bottom: 1px solid var(--card-border);
    }
    .step-panel-icon {
        width: 34px; height: 34px; border-radius: 8px;
        background: var(--accent-dim);
        display: flex; align-items: center; justify-content: center;
        color: var(--accent); font-size: 14px; flex-shrink: 0;
    }
    .step-panel-title { font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: var(--text-primary); }
    .step-panel-sub   { font-size: 11.5px; color: var(--text-muted); margin-top: 1px; }

    /* ── FIELD GRID (modal) ─────────────────────────────── */
    .mfield-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 20px;
    }
    .mfield-grid .span-2   { grid-column: span 2; }
    .mfield-grid .span-full { grid-column: 1 / -1; }
    .mfield-group { display: flex; flex-direction: column; gap: 0; }
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
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .mfield-input::placeholder { color: var(--text-muted); opacity: 0.6; }
    .mfield-input:hover, .mfield-select:hover { border-color: #B0BCCF; }
    .mfield-input:focus, .mfield-select:focus {
        border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: #FFFDF8;
    }
    .mfield-input.is-invalid, .mfield-select.is-invalid {
        border-color: var(--danger); background: #FFF8F8;
    }
    .mfield-input.is-invalid:focus, .mfield-select.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(239,68,68,0.12);
    }
    .mfield-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%2394A3B8' d='M5 7L0.669873 2.5L9.33013 2.5L5 7Z'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; padding-right: 34px; cursor: pointer;
    }
    .mfield-error {
        display: flex; align-items: center; gap: 4px; margin-top: 5px;
        font-size: 11px; color: var(--danger); font-weight: 500;
    }

    /* ── CAPACITY TILES (modal) ─────────────────────────── */
    .capacity-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .cap-tile {
        background: var(--page-bg); border: 1.5px solid var(--card-border);
        border-radius: var(--radius-sm); padding: 16px 12px;
        display: flex; flex-direction: column; align-items: center; gap: 8px;
        text-align: center; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .cap-tile:focus-within {
        border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: #FFFDF8;
    }
    .cap-tile-icon {
        width: 32px; height: 32px; border-radius: 7px;
        background: var(--accent-dim); display: flex; align-items: center; justify-content: center;
        color: var(--accent); font-size: 13px;
    }
    .cap-tile-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
    .cap-tile input {
        width: 100%; border: none; background: transparent; text-align: center;
        font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800;
        color: var(--text-primary); outline: none; padding: 0; line-height: 1;
        -moz-appearance: textfield;
    }
    .cap-tile input::-webkit-outer-spin-button,
    .cap-tile input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .cap-tile input::placeholder { color: var(--text-muted); font-weight: 400; font-size: 18px; }

    /* ── MODAL FOOTER ───────────────────────────────────── */
    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--card-border);
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        flex-shrink: 0;
        background: var(--card-bg);
    }
    .modal-footer-left { display: flex; align-items: center; gap: 8px; }
    .modal-footer-right { display: flex; align-items: center; gap: 8px; }
    .step-counter {
        font-size: 11.5px; font-weight: 600; color: var(--text-muted);
        padding: 5px 10px; background: var(--page-bg);
        border-radius: 20px; border: 1px solid var(--card-border);
    }
    .step-counter strong { color: var(--accent); }

    @media (max-width: 600px) {
        .modal-box { max-height: 100vh; border-radius: 0; max-width: 100%; }
        .modal-overlay { padding: 0; align-items: flex-end; }
        .mfield-grid { grid-template-columns: 1fr; }
        .mfield-grid .span-2 { grid-column: span 1; }
        .capacity-row { grid-template-columns: 1fr; }
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
            <span>Buildings</span>
        </div>
        <h1 class="page-header-title">Buildings</h1>
        <p class="page-header-sub">Manage all building and property records</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('export.buildings', request()->only(['search','property_type','type_of_ownership'])) }}" class="btn btn-success">
            <i class="fa-solid fa-file-excel"></i> Export
        </a>
        <button type="button" class="btn btn-outline" onclick="openImport_buildings()">
            <i class="fa-solid fa-file-import"></i> Import
        </button>
        <button type="button" class="btn btn-primary" onclick="openBuildingModal()">
            <i class="fa-solid fa-plus"></i> Add Building
        </button>
    </div>
</div>

@include('components.import-modal', [
    'type'        => 'buildings',
    'label'       => 'Buildings',
    'icon'        => 'fa-building',
    'routeName'   => 'import.buildings',
])

@include('components.import-result')

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-building"></i></div>
        <div><div class="stat-val">{{ $stats['total'] ?? 0 }}</div><div class="stat-lbl">Total Buildings</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-house"></i></div>
        <div><div class="stat-val">{{ $stats['residential'] ?? 0 }}</div><div class="stat-lbl">Residential</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-store"></i></div>
        <div><div class="stat-val">{{ $stats['commercial'] ?? 0 }}</div><div class="stat-lbl">Commercial</div></div>
    </div>
</div>

{{-- FILTER BAR + TABLE CARD --}}
<div class="card" style="overflow:hidden;">

    <form method="GET" action="{{ route('buildings.index') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group" style="flex:1;min-width:200px;">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Property name, code…" oninput="debounceSubmit()">
            </div>
            <div class="filter-group">
                <label>Property Type</label>
                <select name="property_type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach(['Residential','Commercial','Mixed Use','Industrial','Retail'] as $type)
                        <option value="{{ $type }}" {{ request('property_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Ownership</label>
                <select name="type_of_ownership" onchange="this.form.submit()">
                    <option value="">All Ownership</option>
                    @foreach(['Owned','Leased','Joint Venture','Managed'] as $own)
                        <option value="{{ $own }}" {{ request('type_of_ownership') == $own ? 'selected' : '' }}>{{ $own }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                @if(request()->hasAny(['search','property_type','type_of_ownership']))
                    <a href="{{ route('buildings.index') }}" class="btn btn-outline btn-sm">
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
                    <th>Property Code</th>
                    <th>Property Name</th>
                    <th>Type</th>
                    <th>Ownership</th>
                    <th>Land Lord</th>
                    <th>Building No. / Road</th>
                    <th>Block / Area</th>
                    <th>Floors</th>
                    <th>Units</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($buildings as $building)
                <tr>
                    <td><span class="badge badge-gold">{{ $building->property_code }}</span></td>
                    <td><div class="bldg-code">{{ $building->property_name }}</div></td>
                    <td>
                        @if($building->property_type)
                            <span class="badge badge-blue">{{ $building->property_type }}</span>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        @if($building->type_of_ownership)
                            <span class="badge badge-gray">{{ $building->type_of_ownership }}</span>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        @if($building->land_lord_name)
                            <div style="font-size:13px;">{{ $building->land_lord_name }}</div>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        <div style="font-size:13px;">{{ $building->building_no ?? '—' }}</div>
                        <div style="font-size:11px;color:var(--text-muted);">{{ $building->road ?? '' }}</div>
                    </td>
                    <td>
                        <div style="font-size:13px;">{{ $building->block ?? '—' }}</div>
                        <div style="font-size:11px;color:var(--text-muted);">{{ $building->area ?? '' }}</div>
                    </td>
                    <td>
                        @php $floorCount = $building->total_no_of_floors ?? $building->floors_count; @endphp
                        @if($floorCount)
                            <div style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $floorCount }}</div>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        @php $unitCount = $building->total_no_of_units ?? $building->units_count; @endphp
                        @if($unitCount)
                            <div style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $unitCount }}</div>
                        @else <span style="color:var(--text-muted);">—</span> @endif
                    </td>
                    <td>
                        <div class="action-btns" style="justify-content:flex-end;">
                            <a href="{{ route('buildings.show', $building) }}?tab=floors" class="btn btn-outline btn-sm" title="Floors">
                                <i class="fa-solid fa-layer-group"></i>
                            </a>
                            <a href="{{ route('buildings.show', $building) }}" class="btn btn-outline btn-sm">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="{{ route('buildings.edit', $building) }}" class="btn btn-outline btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('buildings.destroy', $building) }}"
                                  onsubmit="return confirm('Delete this building? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-building"></i></div>
                        <h4>No buildings found</h4>
                        <p>Try adjusting your filters or
                            <button type="button" onclick="openBuildingModal()" style="background:none;border:none;cursor:pointer;color:var(--accent);font-weight:600;padding:0;">add a new building</button>.
                        </p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $buildings->firstItem() ?? 0 }}–{{ $buildings->lastItem() ?? 0 }}</strong>
            of <strong>{{ $buildings->total() }}</strong> buildings
        </div>
        <div class="pagination">
            @if($buildings->onFirstPage())
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></span>
            @else
                <a href="{{ $buildings->previousPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></a>
            @endif
            @foreach($buildings->getUrlRange(max(1, $buildings->currentPage()-2), min($buildings->lastPage(), $buildings->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="page-btn {{ $page == $buildings->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            @if($buildings->hasMorePages())
                <a href="{{ $buildings->nextPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></a>
            @else
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
            @endif
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════
     ADD BUILDING — MULTI-STEP MODAL
═══════════════════════════════════════════════════════ --}}
@php
    $visibleFields = collect($formFields ?? [])
        ->filter(fn($f) => !empty($f['visible']))
        ->pluck('name')->all();
    $showAll = empty($visibleFields);
    $mshow = fn(string $f) => $showAll || in_array($f, $visibleFields);
    $mval  = fn(string $f, $d = '') => old($f, $d);
@endphp

<div class="modal-overlay" id="buildingModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

    <div class="modal-box">

        {{-- HEADER --}}
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-solid fa-building"></i></div>
                <div class="modal-header-text">
                    <div class="modal-header-title" id="modalTitle">Add New Building</div>
                    <div class="modal-header-sub">Fill in the sections below to register a property</div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeBuildingModal()" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- STEP TRACK --}}
            <div class="step-track" id="stepTrack">
                <div class="step-item active" data-step="1">
                    <div class="step-dot">1</div>
                    <span class="step-name">Property</span>
                </div>
                <div class="step-item" data-step="2">
                    <div class="step-dot">2</div>
                    <span class="step-name">Address</span>
                </div>
                <div class="step-item" data-step="3">
                    <div class="step-dot">3</div>
                    <span class="step-name">Capacity</span>
                </div>
            </div>
        </div>

        {{-- BODY --}}
        <div class="modal-body">
            <form method="POST" action="{{ route('buildings.store') }}" id="addBuildingForm" novalidate>
                @csrf

                {{-- STEP 1: PROPERTY INFORMATION --}}
                <div class="step-panel active" id="panel-1">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-building"></i></div>
                        <div>
                            <div class="step-panel-title">Property Information</div>
                            <div class="step-panel-sub">Core identity and ownership details</div>
                        </div>
                    </div>
                    <div class="mfield-grid">

                        @if($mshow('property_name'))
                        <div class="mfield-group">
                            <label class="mfield-label">Property Name <span class="req">*</span></label>
                            <div class="mfield-wrap has-micon">
                                <i class="fa-solid fa-tag mfield-icon"></i>
                                <input type="text" name="property_name"
                                    class="mfield-input {{ $errors->has('property_name') ? 'is-invalid' : '' }}"
                                    value="{{ $mval('property_name') }}"
                                    placeholder="e.g. Miknas Plaza 2"
                                    required maxlength="255">
                            </div>
                            @error('property_name')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('property_code'))
                        <div class="mfield-group">
                            <label class="mfield-label">Property Code <span class="req">*</span></label>
                            <div class="mfield-wrap has-micon">
                                <i class="fa-solid fa-barcode mfield-icon"></i>
                                <input type="text" name="property_code"
                                    class="mfield-input {{ $errors->has('property_code') ? 'is-invalid' : '' }}"
                                    value="{{ $mval('property_code') }}"
                                    placeholder="e.g. MP2"
                                    required maxlength="10" id="mCodeInput"
                                    style="text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">
                            </div>
                            @error('property_code')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('type_of_ownership'))
                        <div class="mfield-group">
                            <label class="mfield-label">Type of Ownership</label>
                            <div class="mfield-wrap">
                                <select name="type_of_ownership" class="mfield-select {{ $errors->has('type_of_ownership') ? 'is-invalid' : '' }}">
                                    <option value="">Select…</option>
                                    @foreach(['Owned','Leased','Joint Venture','Managed'] as $opt)
                                        <option value="{{ $opt }}" {{ $mval('type_of_ownership') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('type_of_ownership')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('property_type'))
                        <div class="mfield-group">
                            <label class="mfield-label">Property Type</label>
                            <div class="mfield-wrap">
                                <select name="property_type" class="mfield-select {{ $errors->has('property_type') ? 'is-invalid' : '' }}">
                                    <option value="">Select…</option>
                                    @foreach(['Residential','Commercial','Mixed Use','Industrial','Retail'] as $opt)
                                        <option value="{{ $opt }}" {{ $mval('property_type') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('property_type')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('land_lord_name'))
                        <div class="mfield-group span-2">
                            <label class="mfield-label">Landlord Name</label>
                            <div class="mfield-wrap has-micon">
                                <i class="fa-solid fa-user-tie mfield-icon"></i>
                                <input type="text" name="land_lord_name"
                                    class="mfield-input {{ $errors->has('land_lord_name') ? 'is-invalid' : '' }}"
                                    value="{{ $mval('land_lord_name') }}"
                                    placeholder="e.g. Akram Miknas"
                                    maxlength="255">
                            </div>
                            @error('land_lord_name')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                    </div>
                </div>

                {{-- STEP 2: ADDRESS --}}
                <div class="step-panel" id="panel-2">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div>
                            <div class="step-panel-title">Address</div>
                            <div class="step-panel-sub">Physical location of the building</div>
                        </div>
                    </div>
                    <div class="mfield-grid">

                        @if($mshow('building_no'))
                        <div class="mfield-group">
                            <label class="mfield-label">Building No.</label>
                            <div class="mfield-wrap has-micon">
                                <i class="fa-solid fa-hashtag mfield-icon"></i>
                                <input type="number" name="building_no"
                                    class="mfield-input {{ $errors->has('building_no') ? 'is-invalid' : '' }}"
                                    value="{{ $mval('building_no') }}" placeholder="202" min="0">
                            </div>
                            @error('building_no')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('block'))
                        <div class="mfield-group">
                            <label class="mfield-label">Block</label>
                            <div class="mfield-wrap has-micon">
                                <i class="fa-solid fa-table-cells mfield-icon"></i>
                                <input type="number" name="block"
                                    class="mfield-input {{ $errors->has('block') ? 'is-invalid' : '' }}"
                                    value="{{ $mval('block') }}" placeholder="324" min="0">
                            </div>
                            @error('block')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('road'))
                        <div class="mfield-group span-2">
                            <label class="mfield-label">Road / Street</label>
                            <div class="mfield-wrap has-micon">
                                <i class="fa-solid fa-road mfield-icon"></i>
                                <input type="text" name="road"
                                    class="mfield-input {{ $errors->has('road') ? 'is-invalid' : '' }}"
                                    value="{{ $mval('road') }}"
                                    placeholder="e.g. Avenue 0022" maxlength="255">
                            </div>
                            @error('road')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('area'))
                        <div class="mfield-group">
                            <label class="mfield-label">Area / District</label>
                            <div class="mfield-wrap has-micon">
                                <i class="fa-solid fa-map mfield-icon"></i>
                                <input type="text" name="area"
                                    class="mfield-input {{ $errors->has('area') ? 'is-invalid' : '' }}"
                                    value="{{ $mval('area') }}"
                                    placeholder="e.g. Capital Governorate" maxlength="255">
                            </div>
                            @error('area')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('city'))
                        <div class="mfield-group">
                            <label class="mfield-label">City</label>
                            <div class="mfield-wrap has-micon">
                                <i class="fa-solid fa-city mfield-icon"></i>
                                <input type="text" name="city"
                                    class="mfield-input {{ $errors->has('city') ? 'is-invalid' : '' }}"
                                    value="{{ $mval('city') }}"
                                    placeholder="e.g. Manama" maxlength="255">
                            </div>
                            @error('city')
                                <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                    </div>
                </div>

                {{-- STEP 3: CAPACITY --}}
                <div class="step-panel" id="panel-3">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <div class="step-panel-title">Capacity</div>
                            <div class="step-panel-sub">Building size — enter 0 if not applicable</div>
                        </div>
                    </div>
                    <div class="capacity-row">

                        @if($mshow('total_no_of_blocks'))
                        <div class="cap-tile">
                            <div class="cap-tile-icon"><i class="fa-solid fa-cubes-stacked"></i></div>
                            <div class="cap-tile-label">Blocks</div>
                            <input type="number" name="total_no_of_blocks"
                                value="{{ $mval('total_no_of_blocks') }}" placeholder="0" min="0">
                            @error('total_no_of_blocks')
                                <div class="mfield-error" style="font-size:10.5px;text-align:left;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('total_no_of_floors'))
                        <div class="cap-tile">
                            <div class="cap-tile-icon"><i class="fa-solid fa-layer-group"></i></div>
                            <div class="cap-tile-label">Floors</div>
                            <input type="number" name="total_no_of_floors"
                                value="{{ $mval('total_no_of_floors') }}" placeholder="0" min="0">
                            @error('total_no_of_floors')
                                <div class="mfield-error" style="font-size:10.5px;text-align:left;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($mshow('total_no_of_units'))
                        <div class="cap-tile">
                            <div class="cap-tile-icon"><i class="fa-solid fa-door-open"></i></div>
                            <div class="cap-tile-label">Units</div>
                            <input type="number" name="total_no_of_units"
                                value="{{ $mval('total_no_of_units') }}" placeholder="0" min="0">
                            @error('total_no_of_units')
                                <div class="mfield-error" style="font-size:10.5px;text-align:left;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                    </div>
                </div>

                {{-- CUSTOM FIELDS (always in last panel) --}}
                @if(count($customFieldDefs ?? []) > 0)
                <div class="step-panel" id="panel-4">
                    <div class="step-panel-heading">
                        <div class="step-panel-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                        <div>
                            <div class="step-panel-title">Custom Fields</div>
                            <div class="step-panel-sub">Additional configured fields</div>
                        </div>
                    </div>
                    <div class="mfield-grid">
                        @foreach($customFieldDefs as $def)
                            @if($showAll || in_array($def->name, $visibleFields))
                            @php $cfv = old('custom_fields.'.$def->name, ''); @endphp
                            <div class="mfield-group {{ $def->field_type === 'textarea' ? 'span-full' : '' }}">
                                <label class="mfield-label">
                                    {{ $def->label }}
                                    @if($def->is_required) <span class="req">*</span> @endif
                                </label>
                                <div class="mfield-wrap">
                                    @if($def->field_type === 'text')
                                        <input type="text" name="custom_fields[{{ $def->name }}]" class="mfield-input" value="{{ $cfv }}" {{ $def->is_required ? 'required' : '' }}>
                                    @elseif($def->field_type === 'number')
                                        <input type="number" name="custom_fields[{{ $def->name }}]" class="mfield-input" value="{{ $cfv }}" {{ $def->is_required ? 'required' : '' }}>
                                    @elseif($def->field_type === 'date')
                                        <input type="date" name="custom_fields[{{ $def->name }}]" class="mfield-input" value="{{ $cfv }}" {{ $def->is_required ? 'required' : '' }}>
                                    @elseif($def->field_type === 'select')
                                        <select name="custom_fields[{{ $def->name }}]" class="mfield-select" {{ $def->is_required ? 'required' : '' }}>
                                            <option value="">Select…</option>
                                            @foreach($def->options ?? [] as $opt)
                                                <option value="{{ $opt }}" {{ $cfv == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                @error('custom_fields.'.$def->name)
                                    <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                                @enderror
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

            </form>{{-- /addBuildingForm --}}
        </div>{{-- /modal-body --}}

        {{-- FOOTER --}}
        <div class="modal-footer">
            <div class="modal-footer-left">
                <button type="button" class="btn btn-outline" onclick="closeBuildingModal()" id="modalCancelBtn">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>
                <button type="button" class="btn btn-outline" id="modalBackBtn" style="display:none;" onclick="modalPrevStep()">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
            </div>
            <div class="modal-footer-right">
                <span class="step-counter" id="stepCounter">Step <strong>1</strong> of <strong id="totalStepsDisplay">3</strong></span>
                <button type="button" class="btn btn-primary" id="modalNextBtn" onclick="modalNextStep()">
                    Next Step <i class="fa-solid fa-arrow-right"></i>
                </button>
                <button type="submit" form="addBuildingForm" class="btn btn-primary btn-lg" id="modalSubmitBtn" style="display:none;" onclick="handleModalSubmit(this)">
                    <i class="fa-solid fa-floppy-disk"></i> Create Building
                </button>
            </div>
        </div>

    </div>{{-- /modal-box --}}
</div>{{-- /modal-overlay --}}

@endsection

@push('scripts')
<script>
// ── FILTER DEBOUNCE ───────────────────────────────────────
let debounceTimer;
function debounceSubmit() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
}

// ── MODAL STATE ───────────────────────────────────────────
let currentStep = 1;
const totalSteps = {{ count($customFieldDefs ?? []) > 0 ? 4 : 3 }};
document.getElementById('totalStepsDisplay').textContent = totalSteps;

function openBuildingModal(startStep) {
    currentStep = startStep || 1;
    renderStep();
    const overlay = document.getElementById('buildingModal');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    // Focus first input of current step after animation
    setTimeout(() => {
        const panel = document.getElementById('panel-' + currentStep);
        if (panel) {
            const first = panel.querySelector('input, select, textarea');
            if (first) first.focus();
        }
    }, 320);
}

function closeBuildingModal() {
    document.getElementById('buildingModal').classList.remove('open');
    document.body.style.overflow = '';
}

// Close on overlay click (outside modal box)
document.getElementById('buildingModal').addEventListener('click', function(e) {
    if (e.target === this) closeBuildingModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('buildingModal').classList.contains('open')) {
        closeBuildingModal();
    }
});

function renderStep() {
    // Panels
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById('panel-' + currentStep);
    if (panel) panel.classList.add('active');

    // Step track dots
    document.querySelectorAll('#stepTrack .step-item').forEach(item => {
        const s = parseInt(item.dataset.step);
        item.classList.remove('active', 'done');
        if (s === currentStep) item.classList.add('active');
        else if (s < currentStep) item.classList.add('done');
    });

    // Footer buttons
    const isLast = currentStep === totalSteps;
    document.getElementById('modalBackBtn').style.display  = currentStep > 1 ? 'inline-flex' : 'none';
    document.getElementById('modalNextBtn').style.display  = isLast ? 'none' : 'inline-flex';
    document.getElementById('modalSubmitBtn').style.display = isLast ? 'inline-flex' : 'none';

    // Step counter
    document.getElementById('stepCounter').innerHTML =
        'Step <strong>' + currentStep + '</strong> of <strong>' + totalSteps + '</strong>';
}

function validateCurrentStep() {
    const panel = document.getElementById('panel-' + currentStep);
    if (!panel) return true;
    const required = panel.querySelectorAll('[required]');
    let valid = true;
    required.forEach(field => {
        field.classList.remove('is-invalid');
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            valid = false;
            field.focus();
        }
    });
    return valid;
}

function modalNextStep() {
    if (!validateCurrentStep()) return;
    if (currentStep < totalSteps) {
        currentStep++;
        renderStep();
        document.getElementById('buildingModal').querySelector('.modal-body').scrollTop = 0;
    }
}

function modalPrevStep() {
    if (currentStep > 1) {
        currentStep--;
        renderStep();
        document.getElementById('buildingModal').querySelector('.modal-body').scrollTop = 0;
    }
}

function handleModalSubmit(btn) {
    if (!validateCurrentStep()) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    document.getElementById('addBuildingForm').submit();
}

// Auto-uppercase property code
const codeInput = document.getElementById('mCodeInput');
if (codeInput) {
    codeInput.addEventListener('input', function() {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
}

// ── AUTO-OPEN MODAL ON VALIDATION ERRORS ─────────────────
@if($errors->any())
(function () {
    // Determine which step has the first error
    const step1Fields = ['property_name','property_code','type_of_ownership','property_type','land_lord_name'];
    const step2Fields = ['building_no','block','road','area','city'];
    const errorKeys = @json($errors->keys());
    let startStep = 3; // default to last
    for (const key of errorKeys) {
        if (step1Fields.includes(key)) { startStep = 1; break; }
        if (step2Fields.includes(key)) { startStep = 2; break; }
    }
    openBuildingModal(startStep);
})();
@endif
</script>
@endpush
