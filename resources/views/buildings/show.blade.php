@extends('layouts.admin')

@section('title', $building->property_name)
@section('topbar-title', 'Building Detail')

@push('styles')
<style>
    .tab-bar {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid var(--card-border);
        margin-bottom: 24px;
    }
    .tab-btn {
        padding: 11px 22px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-muted);
        border: none;
        background: none;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color 0.18s, border-color 0.18s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tab-btn:hover { color: var(--text-primary); }
    .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
    .tab-btn .tab-badge {
        background: var(--accent-dim);
        color: var(--accent);
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 20px;
        min-width: 18px;
        text-align: center;
    }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* Detail grid */
    .detail-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }
    .detail-value {
        font-size: 14px;
        color: var(--text-primary);
        font-weight: 500;
    }
    .section-heading {
        font-family: 'Outfit', sans-serif;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 0 0 10px;
        border-bottom: 1px solid var(--card-border);
        margin-bottom: 16px;
    }

    /* Floor table */
    .floor-table-wrap { overflow-x: auto; }
    .floor-table { width: 100%; border-collapse: collapse; }
    .floor-table th {
        background: var(--page-bg);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        padding: 11px 16px;
        text-align: left;
        border-bottom: 1px solid var(--card-border);
        white-space: nowrap;
    }
    .floor-table td {
        padding: 13px 16px;
        font-size: 13.5px;
        color: var(--text-primary);
        border-bottom: 1px solid var(--card-border);
        vertical-align: middle;
    }
    .floor-table tr:last-child td { border-bottom: none; }
    .floor-table tr:hover td { background: var(--page-bg); }
    .floor-actions { display: flex; gap: 6px; justify-content: flex-end; }

    /* Modal */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        width: 100%;
        max-width: 560px;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalIn 0.18s ease;
    }
    @keyframes modalIn {
        from { opacity: 0; transform: translateY(-12px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 22px 14px;
        border-bottom: 1px solid var(--card-border);
    }
    .modal-title {
        font-family: 'Outfit', sans-serif;
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .modal-close {
        width: 30px; height: 30px;
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--card-border);
        background: none;
        cursor: pointer;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: background 0.15s, color 0.15s;
    }
    .modal-close:hover { background: var(--page-bg); color: var(--text-primary); }
    .modal-body { padding: 20px 22px; }
    .modal-footer {
        padding: 14px 22px;
        border-top: 1px solid var(--card-border);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    /* ── PHOTOS PANEL ───────────────────────────────────── */
    .photos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
    }
    .photo-thumb {
        position: relative; border-radius: 10px; overflow: hidden;
        aspect-ratio: 4/3; background: var(--page-bg);
        border: 1.5px solid var(--card-border);
        group: true;
    }
    .photo-thumb img {
        width: 100%; height: 100%; object-fit: cover; display: block;
        cursor: zoom-in; transition: transform 0.25s;
    }
    .photo-thumb:hover img { transform: scale(1.05); }
    .photo-delete-btn {
        position: absolute; top: 6px; right: 6px;
        width: 24px; height: 24px; border-radius: 50%;
        background: rgba(239,68,68,0.9); border: none;
        color: #fff; font-size: 10px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.18s, transform 0.18s;
        transform: scale(0.8);
    }
    .photo-thumb:hover .photo-delete-btn { opacity: 1; transform: scale(1); }
    .photo-delete-form { position: absolute; top: 6px; right: 6px; }

    /* Upload zone */
    .upload-zone {
        border: 2px dashed var(--card-border);
        border-radius: 12px;
        padding: 40px 24px;
        text-align: center;
        cursor: pointer;
        background: var(--page-bg);
        transition: border-color 0.2s, background 0.2s;
    }
    .upload-zone:hover, .upload-zone.drag-over {
        border-color: var(--accent);
        background: var(--accent-dim);
    }
    .upload-zone-icon {
        font-size: 32px; color: var(--text-muted); margin-bottom: 12px;
        transition: color 0.2s, transform 0.2s;
    }
    .upload-zone:hover .upload-zone-icon { color: var(--accent); transform: translateY(-3px); }
    .upload-zone-title { font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .upload-zone-sub { font-size: 12px; color: var(--text-muted); }

    /* Preview thumb (pre-upload) */
    .preview-thumb {
        position: relative; border-radius: 10px; overflow: hidden;
        aspect-ratio: 4/3; background: var(--page-bg);
        border: 1.5px solid var(--card-border);
    }
    .preview-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .preview-remove {
        position: absolute; top: 6px; right: 6px;
        width: 22px; height: 22px; border-radius: 50%;
        background: rgba(239,68,68,0.88); border: none;
        color: #fff; font-size: 9px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .empty-floors {
        text-align: center;
        padding: 56px 20px;
    }
    .empty-floors .empty-icon {
        width: 60px; height: 60px;
        background: var(--page-bg);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        color: var(--text-muted);
        margin: 0 auto 14px;
    }
    .empty-floors h4 {
        font-family: 'Outfit', sans-serif;
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 6px;
    }
    .empty-floors p { font-size: 13px; color: var(--text-muted); }
</style>
@endpush

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.index') }}">Buildings</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>{{ $building->property_name }}</span>
        </div>
        <h1 class="page-header-title">{{ $building->property_name }}</h1>
        <p class="page-header-sub">
            <span class="badge badge-gold" style="font-size:12px;">{{ $building->property_code }}</span>
            @if($building->property_type)
                &nbsp;<span class="badge badge-blue" style="font-size:12px;">{{ $building->property_type }}</span>
            @endif
        </p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('buildings.edit', $building) }}" class="btn btn-primary">
            <i class="fa-regular fa-pen-to-square"></i> Edit Building
        </a>
    </div>
</div>

{{-- FLASH --}}
@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:20px;">
        <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
    </div>
@endif

{{-- TABS --}}
<div class="tab-bar">
    <button class="tab-btn" id="tab-details" onclick="switchTab('details')">
        <i class="fa-solid fa-building"></i>
        Details
    </button>
    <button class="tab-btn" id="tab-floors" onclick="switchTab('floors')">
        <i class="fa-solid fa-layer-group"></i>
        Floors
        <span class="tab-badge">{{ $floors->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-units" onclick="switchTab('units')">
        <i class="fa-solid fa-door-open"></i>
        Units
        <span class="tab-badge">{{ $units->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-tenants" onclick="switchTab('tenants')">
        <i class="fa-solid fa-users"></i>
        Tenants
        <span class="tab-badge">{{ $tenants->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-agreements" onclick="switchTab('agreements')">
        <i class="fa-solid fa-file-contract"></i>
        Agreements
        <span class="tab-badge">{{ $contracts->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-photos" onclick="switchTab('photos')">
        <i class="fa-solid fa-images"></i>
        Photos
        <span class="tab-badge">{{ $building->images->count() }}</span>
    </button>
</div>

{{-- ===================== DETAILS TAB ===================== --}}
<div class="tab-panel" id="panel-details">
    <div class="card">
        <div class="card-body" style="padding: 24px;">

            {{-- Property Info --}}
            <div class="section-heading"><i class="fa-solid fa-building" style="margin-right:6px;color:var(--accent);"></i>Property Information</div>
            <div class="form-grid" style="margin-bottom:28px;">
                <div class="form-group">
                    <div class="detail-label">Property Name</div>
                    <div class="detail-value">{{ $building->property_name ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Property Code</div>
                    <div class="detail-value">{{ $building->property_code ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Property Type</div>
                    <div class="detail-value">{{ $building->property_type ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Type of Ownership</div>
                    <div class="detail-value">{{ $building->type_of_ownership ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Land Lord Name</div>
                    <div class="detail-value">{{ $building->land_lord_name ?? '—' }}</div>
                </div>
            </div>

            {{-- Address --}}
            <div class="section-heading"><i class="fa-solid fa-location-dot" style="margin-right:6px;color:var(--accent);"></i>Address</div>
            <div class="form-grid" style="margin-bottom:28px;">
                <div class="form-group">
                    <div class="detail-label">Building No.</div>
                    <div class="detail-value">{{ $building->building_no ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Road</div>
                    <div class="detail-value">{{ $building->road ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Block</div>
                    <div class="detail-value">{{ $building->block ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Area</div>
                    <div class="detail-value">{{ $building->area ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">City</div>
                    <div class="detail-value">{{ $building->city ?? '—' }}</div>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="section-heading"><i class="fa-solid fa-chart-bar" style="margin-right:6px;color:var(--accent);"></i>Statistics</div>
            <div class="form-grid">
                <div class="form-group">
                    <div class="detail-label">Total No. of Blocks</div>
                    <div class="detail-value" style="font-family:'Outfit',sans-serif;font-size:20px;font-weight:800;">{{ $building->total_no_of_blocks ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Total No. of Floors</div>
                    <div class="detail-value" style="font-family:'Outfit',sans-serif;font-size:20px;font-weight:800;">{{ $building->total_no_of_floors ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <div class="detail-label">Total No. of Units</div>
                    <div class="detail-value" style="font-family:'Outfit',sans-serif;font-size:20px;font-weight:800;">{{ $building->total_no_of_units ?? '—' }}</div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ===================== FLOORS TAB ===================== --}}
<div class="tab-panel" id="panel-floors">

    {{-- Floors card --}}
    <div class="card" style="overflow:hidden;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="card-header-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div>
                    <h3>Floors</h3>
                    <p>{{ $floors->count() }} floor{{ $floors->count() !== 1 ? 's' : '' }} in this building</p>
                </div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="openAddFloorModal()">
                <i class="fa-solid fa-plus"></i> Add Floor
            </button>
        </div>

        @if($floors->isNotEmpty())
        <div class="floor-table-wrap">
            <table class="floor-table">
                <thead>
                    <tr>
                        <th>Floor Name</th>
                        <th>Floor Code</th>
                        <th>Block Name</th>
                        <th>Block Code</th>
                        <th>Units</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($floors as $floor)
                    <tr>
                        <td>
                            <span style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $floor->floor_name }}</span>
                        </td>
                        <td>
                            @if($floor->floor_code)
                                <span class="badge badge-gray">{{ $floor->floor_code }}</span>
                            @else
                                <span style="color:var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td>{{ $floor->block_name ?? '—' }}</td>
                        <td>{{ $floor->block_code ?? '—' }}</td>
                        <td>
                            @if($floor->total_no_of_units !== null)
                                <span style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $floor->total_no_of_units }}</span>
                            @else
                                <span style="color:var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="floor-actions">
                                <a href="{{ route('floors.edit', $floor) }}"
                                   class="btn btn-outline btn-sm" title="Edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="{{ route('floors.destroy', $floor) }}"
                                      onsubmit="return confirm('Delete floor {{ addslashes($floor->floor_name) }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-floors">
            <div class="empty-icon"><i class="fa-solid fa-layer-group"></i></div>
            <h4>No floors yet</h4>
            <p>Click <strong>Add Floor</strong> to define the first floor for this building.</p>
        </div>
        @endif
    </div>

</div>

{{-- ===================== UNITS TAB ===================== --}}
<div class="tab-panel" id="panel-units">
    <div class="card" style="overflow:hidden;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="card-header-icon"><i class="fa-solid fa-door-open"></i></div>
                <div>
                    <h3>Units</h3>
                    <p>{{ $units->count() }} unit{{ $units->count() !== 1 ? 's' : '' }} in this building</p>
                </div>
            </div>
            <a href="{{ route('property-units.index', ['property_code' => $building->property_code]) }}" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View All
            </a>
        </div>
        @if($units->isNotEmpty())
        <div class="floor-table-wrap">
            <table class="floor-table">
                <thead>
                    <tr>
                        <th>Unit Name</th>
                        <th>Floor</th>
                        <th>Type</th>
                        <th>Condition</th>
                        <th>Description</th>
                        <th>Rent / Month</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($units as $unit)
                    <tr style="cursor:pointer" onclick="window.location='{{ route('property-units.show', $unit) }}'">
                        <td>
                            <span style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $unit->unit_name }}</span>
                        </td>
                        <td>{{ $unit->floor?->floor_name ?? '—' }}</td>
                        <td>
                            @if($unit->unit_type)
                                <span class="badge badge-blue">{{ $unit->unit_type }}</span>
                            @else <span style="color:var(--text-muted);">—</span> @endif
                        </td>
                        <td>
                            @if($unit->unit_condition)
                                <span class="badge badge-gray">{{ $unit->unit_condition }}</span>
                            @else <span style="color:var(--text-muted);">—</span> @endif
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);">{{ $unit->description ?? '—' }}</td>
                        <td>
                            @if($unit->rent_per_month)
                                <span style="font-family:'Outfit',sans-serif;font-weight:700;">BHD {{ number_format($unit->rent_per_month, 3) }}</span>
                            @else <span style="color:var(--text-muted);">—</span> @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-floors">
            <div class="empty-icon"><i class="fa-solid fa-door-open"></i></div>
            <h4>No units yet</h4>
            <p>Units linked to this building will appear here.</p>
        </div>
        @endif
    </div>
</div>

{{-- ===================== TENANTS TAB ===================== --}}
<div class="tab-panel" id="panel-tenants">
    <div class="card" style="overflow:hidden;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="card-header-icon"><i class="fa-solid fa-users"></i></div>
                <div>
                    <h3>Tenants</h3>
                    <p>{{ $tenants->count() }} tenant{{ $tenants->count() !== 1 ? 's' : '' }} with agreements in this building</p>
                </div>
            </div>
            <a href="{{ route('tenants.index') }}" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> All Tenants
            </a>
        </div>
        @if($tenants->isNotEmpty())
        <div class="floor-table-wrap">
            <table class="floor-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Type</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Active Agreements</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenants as $tenant)
                    @php
                        $activeContracts = $contracts->where('tenant_id', $tenant->id)
                            ->filter(fn($c) => in_array($c->status, ['active','expiring','upcoming']))->count();
                    @endphp
                    <tr style="cursor:pointer" onclick="window.location='{{ route('tenants.show', $tenant) }}'">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Outfit',sans-serif;font-weight:700;font-size:13px;flex-shrink:0;">
                                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                </div>
                                <span style="font-weight:600;font-size:13.5px;">{{ $tenant->name }}</span>
                            </div>
                        </td>
                        <td>
                            @if($tenant->tenant_type)
                                <span class="badge badge-gray">{{ ucfirst($tenant->tenant_type) }}</span>
                            @else <span style="color:var(--text-muted);">—</span> @endif
                        </td>
                        <td style="font-size:13px;">{{ $tenant->phone ?? '—' }}</td>
                        <td style="font-size:13px;">{{ $tenant->email ?? '—' }}</td>
                        <td>
                            @if($activeContracts > 0)
                                <span class="badge" style="background:#ECFDF5;color:#059669;">{{ $activeContracts }} active</span>
                            @else
                                <span style="color:var(--text-muted);">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-floors">
            <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
            <h4>No tenants yet</h4>
            <p>Tenants with lease agreements in this building will appear here.</p>
        </div>
        @endif
    </div>
</div>

{{-- ===================== AGREEMENTS TAB ===================== --}}
<div class="tab-panel" id="panel-agreements">
    <div class="card" style="overflow:hidden;">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="card-header-icon"><i class="fa-solid fa-file-contract"></i></div>
                <div>
                    <h3>Lease Agreements</h3>
                    <p>{{ $contracts->count() }} agreement{{ $contracts->count() !== 1 ? 's' : '' }} for this building</p>
                </div>
            </div>
            <a href="{{ route('lease-contracts.index', ['property_code' => $building->property_code]) }}" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View All
            </a>
        </div>
        @if($contracts->isNotEmpty())
        <div class="floor-table-wrap">
            <table class="floor-table">
                <thead>
                    <tr>
                        <th>Agreement No.</th>
                        <th>Tenant</th>
                        <th>Unit</th>
                        <th>Lease Period</th>
                        <th>Rent / Month</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $contract)
                    @php
                        $status = $contract->status;
                        $statusLabel = match($status) {
                            'active'   => 'Active',
                            'expiring' => 'Expiring',
                            'upcoming' => 'Upcoming',
                            'expired'  => 'Expired',
                            default    => ucfirst($status),
                        };
                        $statusStyle = match($status) {
                            'active'   => 'background:#ECFDF5;color:#059669;',
                            'expiring' => 'background:#FFFBEB;color:#D97706;',
                            'upcoming' => 'background:#EFF6FF;color:#2563EB;',
                            'expired'  => 'background:#F1F5F9;color:#64748B;',
                            default    => 'background:#F1F5F9;color:#64748B;',
                        };
                    @endphp
                    <tr style="cursor:pointer" onclick="window.location='{{ route('lease-contracts.show', $contract) }}'">
                        <td>
                            <span style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $contract->lease_agreement_no }}</span>
                        </td>
                        <td>{{ $contract->tenant_name ?? $contract->tenant?->name ?? '—' }}</td>
                        <td>{{ $contract->unit ?? '—' }}</td>
                        <td style="font-size:12px;white-space:nowrap;">
                            {{ $contract->lease_start_date?->format('d M Y') }} →
                            {{ $contract->lease_end_date?->format('d M Y') }}
                        </td>
                        <td>
                            @if($contract->rent_per_month)
                                <span style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $contract->currency ?? 'BHD' }} {{ number_format($contract->rent_per_month, 3) }}</span>
                            @else <span style="color:var(--text-muted);">—</span> @endif
                        </td>
                        <td>
                            <span class="badge" style="{{ $statusStyle }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-floors">
            <div class="empty-icon"><i class="fa-solid fa-file-contract"></i></div>
            <h4>No agreements yet</h4>
            <p>Lease agreements for this property code will appear here.</p>
        </div>
        @endif
    </div>
</div>

{{-- ===================== ADD FLOOR MODAL ===================== --}}
<div class="modal-overlay" id="addFloorModal" onclick="closeAddFloorModalOnOverlay(event)">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-layer-group" style="color:var(--accent);"></i>
                Add Floor
            </div>
            <button class="modal-close" onclick="closeAddFloorModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form method="POST" action="{{ route('buildings.floors.store', $building) }}" novalidate id="addFloorForm">
            @csrf
            <div class="modal-body">
                @if($errors->any() && old('_modal') === 'add_floor')
                    <div class="alert alert-danger" style="margin-bottom:16px;font-size:13px;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        Please fix the errors below.
                    </div>
                @endif

                <input type="hidden" name="_modal" value="add_floor">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Floor Name <span class="required">*</span></label>
                        <input type="text" name="floor_name"
                            value="{{ old('floor_name') }}"
                            placeholder="e.g. Floor 1"
                            class="{{ $errors->has('floor_name') ? 'error' : '' }}"
                            required maxlength="100">
                        @error('floor_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Floor Code</label>
                        <input type="text" name="floor_code"
                            value="{{ old('floor_code') }}"
                            placeholder="e.g. FL01"
                            class="{{ $errors->has('floor_code') ? 'error' : '' }}"
                            maxlength="50">
                        @error('floor_code') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Block Name</label>
                        <input type="text" name="block_name"
                            value="{{ old('block_name') }}"
                            placeholder="e.g. Block A"
                            class="{{ $errors->has('block_name') ? 'error' : '' }}"
                            maxlength="100">
                        @error('block_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Block Code</label>
                        <input type="text" name="block_code"
                            value="{{ old('block_code') }}"
                            placeholder="e.g. BLA"
                            class="{{ $errors->has('block_code') ? 'error' : '' }}"
                            maxlength="50">
                        @error('block_code') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Total No. of Units</label>
                        <input type="number" name="total_no_of_units"
                            value="{{ old('total_no_of_units') }}"
                            placeholder="e.g. 10"
                            class="{{ $errors->has('total_no_of_units') ? 'error' : '' }}"
                            min="1">
                        @error('total_no_of_units') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddFloorModal()">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Add Floor
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== PHOTOS TAB ===================== --}}
<div class="tab-panel" id="panel-photos">
    <div class="card" style="overflow:hidden;">
        <div style="padding:20px 24px 16px; border-bottom:1px solid var(--card-border); display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div>
                <div style="font-family:'Outfit',sans-serif;font-size:15px;font-weight:800;color:var(--text-primary);">Building Photos</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">{{ $building->images->count() }} photo{{ $building->images->count() !== 1 ? 's' : '' }} uploaded</div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('photoUploadZone').scrollIntoView({behavior:'smooth'})">
                <i class="fa-solid fa-plus"></i> Add Photos
            </button>
        </div>

        {{-- Existing photos grid --}}
        @if($building->images->isNotEmpty())
        <div style="padding:20px;">
            <div class="photos-grid">
                @foreach($building->images as $img)
                <div class="photo-thumb" id="thumb-{{ $img->id }}">
                    <img src="{{ $img->url }}" alt="Building photo" loading="lazy" onclick="openLightbox('{{ $img->url }}')">
                    <form method="POST" action="{{ route('buildings.images.destroy', [$building, $img]) }}" class="photo-delete-form"
                          onsubmit="return confirm('Remove this photo?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="photo-delete-btn" title="Remove photo">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Upload zone --}}
        <div style="padding: {{ $building->images->isNotEmpty() ? '0 20px 20px' : '20px' }};" id="photoUploadZone">
            <form method="POST" action="{{ route('buildings.images.store', $building) }}" enctype="multipart/form-data" id="photoUploadForm">
                @csrf
                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('photoFileInput').click()">
                    <div class="upload-zone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="upload-zone-title">Drop photos here or click to browse</div>
                    <div class="upload-zone-sub">JPG, PNG or WEBP · Max 4 MB each · Up to 10 photos</div>
                    <input type="file" id="photoFileInput" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                           style="display:none;" onchange="handlePhotoSelect(this)">
                </div>

                {{-- Client-side previews before upload --}}
                <div id="uploadPreviewGrid" class="photos-grid" style="display:none;margin-top:16px;"></div>

                <div id="uploadActions" style="display:none;margin-top:14px;display:none;gap:10px;align-items:center;">
                    <span id="uploadFileCount" style="font-size:13px;color:var(--text-muted);"></span>
                    <div style="margin-left:auto;display:flex;gap:8px;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="clearPhotoSelection()">
                            <i class="fa-solid fa-xmark"></i> Clear
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" id="uploadSubmitBtn">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Photos
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

{{-- Lightbox --}}
<div id="photoLightbox" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,0.88);backdrop-filter:blur(6px);align-items:center;justify-content:center;cursor:zoom-out;" onclick="closeLightbox()">
    <img id="lightboxImg" src="" alt="" style="max-width:92vw;max-height:88vh;border-radius:8px;box-shadow:0 24px 64px rgba(0,0,0,0.5);object-fit:contain;">
</div>

@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
        history.replaceState(null, '', '?tab=' + tab);
    }

    // Re-open modal on validation error
    const hasModalError = {{ ($errors->any() && old('_modal') === 'add_floor') ? 'true' : 'false' }};

    // Activate tab from URL, default to details
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    const validTabs = ['details', 'floors', 'units', 'tenants', 'agreements', 'photos'];
    switchTab(validTabs.includes(urlTab) ? urlTab : 'details');

    if (hasModalError) {
        openAddFloorModal();
    }

    function openAddFloorModal() {
        document.getElementById('addFloorModal').classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const first = document.querySelector('#addFloorForm input[name="floor_name"]');
            if (first) first.focus();
        }, 100);
    }

    function closeAddFloorModal() {
        document.getElementById('addFloorModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    function closeAddFloorModalOnOverlay(e) {
        if (e.target === document.getElementById('addFloorModal')) closeAddFloorModal();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeAddFloorModal(); closeLightbox(); }
    });

    // ── PHOTO UPLOAD ──────────────────────────────────────
    let selectedFiles = [];

    const uploadZone = document.getElementById('uploadZone');
    if (uploadZone) {
        uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
        uploadZone.addEventListener('drop', e => {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            addFiles(Array.from(e.dataTransfer.files));
        });
    }

    function handlePhotoSelect(input) {
        addFiles(Array.from(input.files));
        input.value = '';
    }

    function addFiles(newFiles) {
        const allowed = newFiles.filter(f => ['image/jpeg','image/png','image/webp'].includes(f.type) && f.size <= 4 * 1024 * 1024);
        selectedFiles = [...selectedFiles, ...allowed].slice(0, 10);
        renderPreviews();
    }

    function renderPreviews() {
        const grid    = document.getElementById('uploadPreviewGrid');
        const actions = document.getElementById('uploadActions');
        const count   = document.getElementById('uploadFileCount');
        grid.innerHTML = '';

        if (!selectedFiles.length) {
            grid.style.display = 'none';
            actions.style.display = 'none';
            return;
        }

        grid.style.display = 'grid';
        actions.style.display = 'flex';
        count.textContent = selectedFiles.length + ' photo' + (selectedFiles.length > 1 ? 's' : '') + ' ready to upload';

        selectedFiles.forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'preview-thumb';
                div.innerHTML = `<img src="${e.target.result}" alt="Preview">
                    <button type="button" class="preview-remove" onclick="removePreview(${idx})"><i class="fa-solid fa-xmark"></i></button>`;
                grid.appendChild(div);
            };
            reader.readAsDataURL(file);
        });

        // Sync files to a DataTransfer so the form sends them
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        document.getElementById('photoFileInput').files = dt.files;
    }

    function removePreview(idx) {
        selectedFiles.splice(idx, 1);
        renderPreviews();
    }

    function clearPhotoSelection() {
        selectedFiles = [];
        renderPreviews();
    }

    document.getElementById('photoUploadForm')?.addEventListener('submit', function() {
        const btn = document.getElementById('uploadSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading…';
    });

    // ── LIGHTBOX ──────────────────────────────────────────
    function openLightbox(src) {
        const lb = document.getElementById('photoLightbox');
        document.getElementById('lightboxImg').src = src;
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('photoLightbox').style.display = 'none';
        document.body.style.overflow = '';
    }
</script>
@endpush
