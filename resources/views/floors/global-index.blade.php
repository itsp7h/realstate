@extends('layouts.admin')

@section('title', 'Floors')
@section('topbar-title', 'Floors')

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
    }
    .stat-icon {
        width: 44px; height: 44px;
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
    .stat-icon.blue   { background: #EFF6FF; color: var(--info); }
    .stat-val {
        font-family: 'Outfit', sans-serif;
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
    }
    .stat-lbl { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

    .filter-bar {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        padding: 16px 20px;
        border-bottom: 1px solid var(--card-border);
    }
    .filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 180px; }
    .filter-group label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .filter-group select {
        padding: 8px 12px;
        font-size: 13px;
        border: 1.5px solid var(--input-border);
        border-radius: var(--radius-sm);
        background: var(--card-bg);
        color: var(--text-primary);
        font-family: 'Plus Jakarta Sans', sans-serif;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        transition: border-color 0.18s;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        padding-right: 32px;
    }
    .filter-group select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
    .filter-actions { display: flex; gap: 8px; align-items: flex-end; margin-left: auto; }

    .bldg-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: var(--accent-dim);
        color: var(--accent);
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .action-btns { display: flex; gap: 6px; justify-content: flex-end; }
    .table-footer {
        padding: 14px 20px;
        border-top: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pagination { display: flex; gap: 4px; }
    .page-btn {
        width: 32px; height: 32px;
        border: 1.5px solid var(--card-border);
        background: var(--card-bg);
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s;
    }
    .page-btn:hover { background: var(--page-bg); color: var(--text-primary); }
    .page-btn.active { background: var(--accent); border-color: var(--accent); color: #0B1120; }
    .result-count { font-size: 13px; color: var(--text-muted); }
    .result-count strong { color: var(--text-primary); }
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-icon {
        width: 64px; height: 64px;
        background: var(--page-bg);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; color: var(--text-muted);
        margin: 0 auto 16px;
    }
    .empty-state h4 { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; }
    .empty-state p { font-size: 13px; color: var(--text-muted); }

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
        display: flex; align-items: center; justify-content: center;
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
</style>
@endpush

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Floors</span>
        </div>
        <h1 class="page-header-title">Floors</h1>
        <p class="page-header-sub">All floors across all buildings</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('export.floors', array_filter(['building_id' => $buildingId ?? null])) }}" class="btn btn-success">
            <i class="fa-solid fa-file-excel"></i> Export
        </a>
        <button type="button" class="btn btn-outline" onclick="openImport_floors()">
            <i class="fa-solid fa-file-import"></i> Import
        </button>
        <button class="btn btn-primary" onclick="openAddFloorModal()">
            <i class="fa-solid fa-plus"></i> Add Floor
        </button>
    </div>
</div>

@include('components.import-modal', [
    'type'        => 'floors',
    'label'       => 'Floors',
    'icon'        => 'fa-layer-group',
    'routeName'   => 'import.floors',
])

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <div class="stat-val">{{ $stats['total'] }}</div>
            <div class="stat-lbl">Total Floors</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-building"></i></div>
        <div>
            <div class="stat-val">{{ $buildings->count() }}</div>
            <div class="stat-lbl">Buildings</div>
        </div>
    </div>
</div>

{{-- FILTER + TABLE --}}
<div class="card" style="overflow:hidden;">

    {{-- FILTER --}}
    <form method="GET" action="{{ route('floors.global') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group">
                <label>Building</label>
                <select name="building_id" onchange="this.form.submit()">
                    <option value="">All Buildings</option>
                    @foreach($buildings as $b)
                        <option value="{{ $b->id }}" {{ $buildingId == $b->id ? 'selected' : '' }}>
                            {{ $b->property_name }} ({{ $b->property_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                @if($buildingId)
                    <a href="{{ route('floors.global') }}" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- TABLE --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Building</th>
                    <th>Floor Name</th>
                    <th>Floor Code</th>
                    <th>Block Name</th>
                    <th>Block Code</th>
                    <th>Units</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($floors as $floor)
                <tr>
                    <td>
                        <a href="{{ route('buildings.show', $floor->building) }}?tab=floors"
                           style="text-decoration:none;">
                            <span class="bldg-pill">
                                <i class="fa-solid fa-building" style="font-size:9px;"></i>
                                {{ $floor->building->property_code }}
                            </span>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">
                                {{ $floor->building->property_name }}
                            </div>
                        </a>
                    </td>
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
                        <div class="action-btns">
                            <a href="{{ route('buildings.show', $floor->building) }}?tab=floors"
                               class="btn btn-outline btn-sm" title="View in building">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="{{ route('floors.edit', $floor) }}"
                               class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('floors.destroy', $floor) }}"
                                  onsubmit="return confirm('Delete floor {{ addslashes($floor->floor_name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-layer-group"></i></div>
                            <h4>No floors found</h4>
                            <p>
                                @if($buildingId)
                                    No floors for this building yet. <a href="{{ route('buildings.show', $buildingId) }}?tab=floors" style="color:var(--accent);">Go to building</a> to add one.
                                @else
                                    Open a building and use the Floors tab to add floors.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $floors->firstItem() ?? 0 }}–{{ $floors->lastItem() ?? 0 }}</strong>
            of <strong>{{ $floors->total() }}</strong> floors
        </div>
        <div class="pagination">
            @if($floors->onFirstPage())
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></span>
            @else
                <a href="{{ $floors->previousPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></a>
            @endif

            @foreach($floors->getUrlRange(max(1, $floors->currentPage()-2), min($floors->lastPage(), $floors->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="page-btn {{ $page == $floors->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach

            @if($floors->hasMorePages())
                <a href="{{ $floors->nextPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></a>
            @else
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
            @endif
        </div>
    </div>

</div>

{{-- ADD FLOOR MODAL --}}
<div class="modal-overlay" id="addFloorModal" onclick="closeOnOverlay(event)">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-layer-group" style="color:var(--accent);"></i>
                Add Floor
            </div>
            <button class="modal-close" onclick="closeAddFloorModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form method="POST" action="" id="addFloorForm" novalidate>
            @csrf
            <input type="hidden" name="_modal" value="add_floor">

            <div class="modal-body">
                @if($errors->any() && old('_modal') === 'add_floor')
                    <div class="alert alert-danger" style="margin-bottom:16px;font-size:13px;">
                        <i class="fa-solid fa-circle-exclamation"></i> Please fix the errors below.
                    </div>
                @endif

                <div class="form-grid">
                    <div class="form-group col-span-full">
                        <label>Building <span class="required">*</span></label>
                        <select name="_building_id" id="modalBuildingSelect" required
                            class="{{ $errors->has('_building_id') ? 'error' : '' }}"
                            onchange="updateFormAction(this.value)">
                            <option value="">— Select a building —</option>
                            @foreach($buildings as $b)
                                <option value="{{ $b->id }}"
                                    data-action="{{ route('buildings.floors.store', $b) }}"
                                    {{ old('_building_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->property_name }} ({{ $b->property_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('_building_id') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

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
    function openAddFloorModal() {
        document.getElementById('addFloorModal').classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('modalBuildingSelect').focus(), 100);
    }
    function closeAddFloorModal() {
        document.getElementById('addFloorModal').classList.remove('open');
        document.body.style.overflow = '';
    }
    function closeOnOverlay(e) {
        if (e.target === document.getElementById('addFloorModal')) closeAddFloorModal();
    }
    function updateFormAction(buildingId) {
        const option = document.querySelector('#modalBuildingSelect option[value="' + buildingId + '"]');
        document.getElementById('addFloorForm').action = option ? option.dataset.action : '';
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAddFloorModal(); });

    @if($errors->any() && old('_modal') === 'add_floor')
        openAddFloorModal();
        const sel = document.getElementById('modalBuildingSelect');
        if (sel.value) updateFormAction(sel.value);
    @endif
</script>
@endpush
