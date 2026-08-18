@extends('layouts.admin')

@section('title', 'Floors')
@section('topbar-title', 'Floors')

@push('styles')
<style>
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

    /* Modal */
    @keyframes modalIn {
        from { opacity: 0; transform: translateY(-12px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
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

{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════ --}}
<div class="m-screen">
    <div class="m-action-row">
        <a href="{{ route('export.floors', array_filter(['building_id' => $buildingId ?? null])) }}" class="m-action-btn green-outline">Export</a>
        <button type="button" class="m-action-btn outline" onclick="openImport_floors()">Import</button>
        <button type="button" class="m-action-btn primary" onclick="openAddFloorModal()">+ Add Floor</button>
    </div>
    <div class="m-chip-row no-sb">
        <a href="{{ route('floors.global') }}" class="m-chip {{ !$buildingId ? 'active' : '' }}">All</a>
        @foreach($buildings as $b)
            <a href="{{ route('floors.global', ['building_id' => $b->id]) }}" class="m-chip {{ (string) $buildingId === (string) $b->id ? 'active' : '' }}">{{ $b->property_code }}</a>
        @endforeach
    </div>
    <div class="m-row-list">
        @forelse($floors as $floor)
            @php $uCount = $floor->total_no_of_units ?? $floor->units_count; @endphp
            <a href="{{ route('buildings.show', $floor->building) }}?tab=floors" class="m-row-card">
                <span class="m-row-chip">{{ $floor->building->property_code }}</span>
                <div style="flex:1;min-width:0;">
                    <div class="m-row-title">{{ $floor->floor_name }}</div>
                    <div class="m-row-sub">{{ $floor->building->property_name }}{{ $floor->block_name ? ' · '.$floor->block_name : '' }}</div>
                </div>
                @if($floor->floor_code)
                    <span class="m-row-badge" style="background:var(--m-line);color:#6B7688;">{{ $floor->floor_code }}</span>
                @endif
            </a>
        @empty
            <div class="m-empty">
                <div class="m-empty-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div class="m-empty-title">No floors found</div>
                <div class="m-empty-sub">Try a different building filter or add a new floor.</div>
            </div>
        @endforelse
    </div>
</div>

@include('components.import-modal', [
    'type'        => 'floors',
    'label'       => 'Floors',
    'icon'        => 'fa-layer-group',
    'routeName'   => 'import.floors',
])

@include('components.import-result')

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
<div class="card m-hide-desktop-index" style="overflow:hidden;">

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
                <tr data-href="{{ route('buildings.show', $floor->building) }}?tab=floors" style="cursor:pointer">
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
                        @php $uCount = $floor->total_no_of_units ?? $floor->units_count; @endphp
                        @if($uCount)
                            <span style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $uCount }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td onclick="event.stopPropagation()">
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
    <div class="modal-box" style="--modal-w:560px">
        <div class="modal-header">
            <div class="modal-header-title">
                <i class="fa-solid fa-layer-group" style="color:var(--accent);"></i>
                Add Floor
            </div>
            <button class="modal-close-btn" onclick="closeAddFloorModal()"><i class="fa-solid fa-xmark"></i></button>
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
