@extends('layouts.admin')

@section('title', 'Property Units')
@section('topbar-title', 'Property Units')

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
    .unit-code {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        color: var(--text-primary);
        font-size: 13.5px;
    }
    .unit-prop {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .rent-val {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        color: var(--text-primary);
    }
    .rent-per {
        font-size: 11px;
        color: var(--text-muted);
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
            <span>Property Units</span>
        </div>
        <h1 class="page-header-title">Property Units</h1>
        <p class="page-header-sub">Manage all property unit records across 6 properties</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('property-units.export') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success">
            <i class="fa-solid fa-file-excel"></i> Export to Excel
        </a>
        <a href="{{ route('property-units.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add Unit
        </a>
    </div>
</div>

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-building"></i></div>
        <div>
            <div class="stat-val">{{ $stats['total'] ?? 0 }}</div>
            <div class="stat-lbl">Total Units</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="stat-val">{{ $stats['furnished'] ?? 0 }}</div>
            <div class="stat-lbl">Furnished</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-wrench"></i></div>
        <div>
            <div class="stat-val">{{ $stats['fitted'] ?? 0 }}</div>
            <div class="stat-lbl">Fitted</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <div class="stat-val">{{ $stats['properties'] ?? 0 }}</div>
            <div class="stat-lbl">Properties</div>
        </div>
    </div>
</div>

{{-- FILTER BAR + TABLE CARD --}}
<div class="card" style="border-radius: var(--radius); overflow: hidden;">

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('property-units.index') }}" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group" style="flex:1;min-width:200px;">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Unit name, description…"
                    oninput="debounceSubmit()">
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
                <label>Floor</label>
                <input type="text" name="floor_name" value="{{ request('floor_name') }}"
                    placeholder="e.g. Floor 1"
                    oninput="debounceSubmit()">
            </div>
            <div class="filter-actions">
                @if(request()->hasAny(['search','property_code','unit_type','unit_condition','floor_name']))
                    <a href="{{ route('property-units.index') }}" class="btn btn-outline btn-sm">
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
                    <th>Unit</th>
                    <th>Property</th>
                    <th>Land Lord Name</th>
                    <th>Floor / Block</th>
                    <th>Type</th>
                    <th>Condition</th>
                    <th>Area</th>
                    <th>Rent/Month</th>
                    <th>Security Deposit Amount</th>
                    <th>Electricity A/c No</th>
                    <th>View</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                <tr>
                    <td>
                        <div class="unit-code">{{ $unit->unit_name }}</div>
                        <div class="unit-prop">{{ $unit->description }}</div>
                    </td>
                    <td>
                        <span class="badge badge-gold">{{ $unit->property_code }}</span>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">{{ $unit->property_name }}</div>
                    </td>
                    <td>
                        @if($unit->land_lord_name)
                            <div style="font-size:13px;">{{ $unit->land_lord_name }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:13px;">{{ $unit->floor_name }}</div>
                        <div style="font-size:11px;color:var(--text-muted);">{{ $unit->block_name }}</div>
                    </td>
                    <td>
                        <span class="badge badge-blue">{{ $unit->unit_type }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $unit->unit_condition === 'Furnished' ? 'badge-green' : 'badge-gray' }}">
                            {{ $unit->unit_condition }}
                        </span>
                    </td>
                    <td>
                        @if($unit->area_inside)
                            <div>{{ number_format($unit->area_inside, 1) }}</div>
                            <div style="font-size:11px;color:var(--text-muted);">{{ $unit->area_unit }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($unit->rent_per_month)
                            <div class="rent-val">{{ number_format($unit->rent_per_month) }}</div>
                            <div class="rent-per">BHD / mo</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($unit->security_deposit_amount)
                            <div class="rent-val">{{ number_format($unit->security_deposit_amount) }}</div>
                            <div class="rent-per">BHD</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($unit->electricity_ac_no)
                            <div style="font-size:13px;font-family:'Outfit',sans-serif;">{{ $unit->electricity_ac_no }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($unit->view)
                            <span class="badge badge-gray">{{ $unit->view }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns" style="justify-content:flex-end;">
                            <a href="{{ route('property-units.show', $unit) }}" class="btn btn-outline btn-sm">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="{{ route('property-units.edit', $unit) }}" class="btn btn-outline btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('property-units.destroy', $unit) }}"
                                  onsubmit="return confirm('Delete this unit?')">
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
                    <td colspan="12">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-building"></i></div>
                            <h4>No units found</h4>
                            <p>Try adjusting your filters or <a href="{{ route('property-units.create') }}" style="color:var(--accent);">add a new unit</a>.</p>
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
            Showing <strong>{{ $units->firstItem() ?? 0 }}–{{ $units->lastItem() ?? 0 }}</strong>
            of <strong>{{ $units->total() }}</strong> units
        </div>
        <div class="pagination">
            @if($units->onFirstPage())
                <span class="page-btn" style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></span>
            @else
                <a href="{{ $units->previousPageUrl() }}" class="page-btn"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i></a>
            @endif

            @foreach($units->getUrlRange(max(1, $units->currentPage()-2), min($units->lastPage(), $units->currentPage()+2)) as $page => $url)
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
