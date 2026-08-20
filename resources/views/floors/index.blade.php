@extends('layouts.admin')

@section('title', $building->property_name . ' — Floors')
@section('topbar-title', 'Floors')

@section('content')

@section('page-breadcrumb')
    <a href="{{ url('/dashboard') }}">Home</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="{{ route('buildings.index') }}">Buildings</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="{{ route('buildings.show', $building) }}">{{ $building->property_name }}</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>Floors</span>
@endsection
@section('page-title')
    {{ $building->property_name }} — Floors
@endsection
@section('page-subtitle')
    <span class="badge badge-gold">{{ $building->property_code }}</span>
    &nbsp;Manage floors for this building
@endsection
@section('page-actions')
    <a href="{{ route('buildings.floors.create', $building) }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add Floor
    </a>
    <a href="{{ route('buildings.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back to Buildings
    </a>
@endsection

{{-- PAGE HEADER --}}

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon gold"><i class="fa-solid fa-layer-group"></i></span>
            <span class="stat-lbl">Total Floors</span>
        </div>
        <div class="stat-val">{{ $stats['total_floors'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon blue"><i class="fa-solid fa-door-open"></i></span>
            <span class="stat-lbl">Total Units (across floors)</span>
        </div>
        <div class="stat-val">{{ $stats['total_units'] ?? 0 }}</div>
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
                    <td data-label="Floor Name">
                        <div style="font-family:'Outfit',sans-serif;font-weight:700;font-size:14px;">
                            {{ $floor->floor_name }}
                        </div>
                    </td>
                    <td data-label="Floor Code">
                        @if($floor->floor_code)
                            <span class="badge badge-gold">{{ $floor->floor_code }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td data-label="Block">
                        @if($floor->block_name)
                            <span style="font-size:13px;">{{ $floor->block_name }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td data-label="Block Code">
                        @if($floor->block_code)
                            <span class="badge badge-gray">{{ $floor->block_code }}</span>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td data-label="Total Units">
                        @if($floor->total_no_of_units !== null)
                            <div style="font-family:'Outfit',sans-serif;font-weight:700;">{{ $floor->total_no_of_units }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td data-label="Actions">
                        @include('partials.row-actions', ['label' => 'Actions for '.$floor->floor_name, 'items' => [
                            ['label' => 'Edit floor',   'icon' => 'fa-pen-to-square', 'url' => route('floors.edit', $floor)],
                            ['sep' => true],
                            ['label' => 'Delete floor', 'icon' => 'fa-trash-can', 'tone' => 'danger',
                             'action' => route('floors.destroy', $floor), 'method' => 'DELETE',
                             'confirm' => 'Delete this floor? This will only work if no units are linked.'],
                        ]])
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
        {{ $floors->links() }}
    </div>
</div>

@endsection
