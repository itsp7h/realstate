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
    switchTab(urlTab === 'floors' ? 'floors' : 'details');

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
        if (e.key === 'Escape') closeAddFloorModal();
    });
</script>
@endpush
