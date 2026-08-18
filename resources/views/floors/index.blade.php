@extends('layouts.admin')

@section('title', $building->property_name . ' — Floors')
@section('topbar-title', 'Floors')

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.index') }}">Buildings</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('buildings.show', $building) }}">{{ $building->property_name }}</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Floors</span>
        </div>
        <h1 class="page-header-title">{{ $building->property_name }} — Floors</h1>
        <p class="page-header-sub">
            <span class="badge badge-gold">{{ $building->property_code }}</span>
            &nbsp;Manage floors for this building
        </p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('buildings.floors.create', $building) }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add Floor
        </a>
        <a href="{{ route('buildings.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back to Buildings
        </a>
    </div>
</div>

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <div class="stat-val">{{ $stats['total_floors'] }}</div>
            <div class="stat-lbl">Total Floors</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-door-open"></i></div>
        <div>
            <div class="stat-val">{{ $stats['total_units'] ?? 0 }}</div>
            <div class="stat-lbl">Total Units (across floors)</div>
        </div>
    </div>
</div>

{{-- TABLE CARD --}}
<div class="card" style="border-radius: var(--radius); overflow: hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Floor Name</th>
                    <th>Floor Code</th>
                    <th>Block</th>
                    <th>Block Code</th>
                    <th>Total Units</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($floors as $floor)
                <tr>
                    <td>
                        <div style="font-family:'Outfit',sans-serif;font-weight:700;font-size:14px;">
                            {{ $floor->floor_name }}
                        </div>
                    </td>
                    <td>
                        @if($floor->floor_code)
                            <span class="badge badge-gold">{{ $floor->floor_code }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($floor->block_name)
                            <span style="font-size:13px;">{{ $floor->block_name }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($floor->block_code)
                            <span class="badge badge-gray">{{ $floor->block_code }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($floor->total_no_of_units !== null)
                            <div style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $floor->total_no_of_units }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns" style="justify-content:flex-end;">
                            <a href="{{ route('floors.edit', $floor) }}" class="btn btn-outline btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('floors.destroy', $floor) }}"
                                  onsubmit="return confirm('Delete this floor? This will only work if no units are linked.')">
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
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-layer-group"></i></div>
                            <h4>No floors yet</h4>
                            <p>
                                <a href="{{ route('buildings.floors.create', $building) }}" style="color:var(--accent);">
                                    Add the first floor
                                </a> to this building.
                            </p>
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

@endsection
