@extends('layouts.admin')

@section('title', 'Buildings')
@section('topbar-title', 'Buildings')

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
        font-size: 18px;
        flex-shrink: 0;
    }
    .stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
    .stat-icon.green  { background: #ECFDF5; color: var(--success); }
    .stat-icon.blue   { background: #EFF6FF; color: var(--info); }
    .stat-icon.purple { background: #F5F3FF; color: #7C3AED; }
    .stat-val {
        font-family: 'Outfit', sans-serif;
        font-size: 24px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
    }
    .stat-lbl {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 3px;
    }
    .filter-bar {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        padding: 16px 20px;
        background: var(--page-bg);
        border-radius: var(--radius-sm);
        margin-bottom: 0;
        border: 1px solid var(--card-border);
        border-bottom: none;
        border-radius: var(--radius) var(--radius) 0 0;
    }
    .filter-group { display: flex; flex-direction: column; gap: 5px; min-width: 150px; }
    .filter-group label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .filter-group input,
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
        transition: border-color 0.18s, box-shadow 0.18s;
    }
    .filter-group input:focus, .filter-group select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-dim);
    }
    .filter-group select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        padding-right: 32px;
    }
    .filter-actions { display: flex; gap: 8px; align-items: flex-end; margin-left: auto; }
    .bldg-code {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        color: var(--text-primary);
        font-size: 13.5px;
    }
    .bldg-sub {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .action-btns { display: flex; gap: 6px; }
    .table-footer {
        padding: 14px 20px;
        border-top: 1px solid var(--card-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pagination { display: flex; gap: 4px; align-items: center; }
    .page-btn {
        width: 32px; height: 32px;
        border: 1.5px solid var(--card-border);
        background: var(--card-bg);
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s;
    }
    .page-btn:hover { background: var(--page-bg); color: var(--text-primary); }
    .page-btn.active { background: var(--accent); border-color: var(--accent); color: #0B1120; }
    .result-count { font-size: 13px; color: var(--text-muted); }
    .result-count strong { color: var(--text-primary); }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-icon {
        width: 64px; height: 64px;
        background: var(--page-bg);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px;
        color: var(--text-muted);
        margin: 0 auto 16px;
    }
    .empty-state h4 {
        font-family: 'Outfit', sans-serif;
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 6px;
    }
    .empty-state p { font-size: 13px; color: var(--text-muted); }
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
        <a href="{{ route('buildings.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add Building
        </a>
    </div>
</div>

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-building"></i></div>
        <div>
            <div class="stat-val">{{ $stats['total'] ?? 0 }}</div>
            <div class="stat-lbl">Total Buildings</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-house"></i></div>
        <div>
            <div class="stat-val">{{ $stats['residential'] ?? 0 }}</div>
            <div class="stat-lbl">Residential</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-store"></i></div>
        <div>
            <div class="stat-val">{{ $stats['commercial'] ?? 0 }}</div>
            <div class="stat-lbl">Commercial</div>
        </div>
    </div>
</div>

{{-- FILTER BAR + TABLE CARD --}}
<div class="card" style="border-radius: var(--radius); overflow: hidden;">

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('buildings.index') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group" style="flex:1;min-width:200px;">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Property name, code…"
                    oninput="debounceSubmit()">
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

    {{-- TABLE --}}
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
                    <td>
                        <span class="badge badge-gold">{{ $building->property_code }}</span>
                    </td>
                    <td>
                        <div class="bldg-code">{{ $building->property_name }}</div>
                    </td>
                    <td>
                        @if($building->property_type)
                            <span class="badge badge-blue">{{ $building->property_type }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($building->type_of_ownership)
                            <span class="badge badge-gray">{{ $building->type_of_ownership }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($building->land_lord_name)
                            <div style="font-size:13px;">{{ $building->land_lord_name }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
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
                        @if($building->total_no_of_floors !== null)
                            <div style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $building->total_no_of_floors }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($building->total_no_of_units !== null)
                            <div style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $building->total_no_of_units }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns" style="justify-content:flex-end;">
                            <a href="{{ route('buildings.show', $building) }}" class="btn btn-outline btn-sm">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="{{ route('buildings.edit', $building) }}" class="btn btn-outline btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('buildings.destroy', $building) }}"
                                  onsubmit="return confirm('Delete this building?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-building"></i></div>
                            <h4>No buildings found</h4>
                            <p>Try adjusting your filters or <a href="{{ route('buildings.create') }}" style="color:var(--accent);">add a new building</a>.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- TABLE FOOTER --}}
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
