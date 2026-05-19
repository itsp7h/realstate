@extends('layouts.admin')

@section('title', $building->property_name)
@section('topbar-title', 'Building Detail')

@section('content')

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
        <p class="page-header-sub">{{ $building->property_code }}</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('buildings.edit', $building) }}" class="btn btn-primary">
            <i class="fa-regular fa-pen-to-square"></i> Edit
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-header-icon"><i class="fa-solid fa-building"></i></div>
        <div>
            <h3>{{ $building->property_name }}</h3>
            <p>{{ $building->property_code }}</p>
        </div>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Property Type</label>
                <div>{{ $building->property_type ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Type of Ownership</label>
                <div>{{ $building->type_of_ownership ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Land Lord Name</label>
                <div>{{ $building->land_lord_name ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Building No.</label>
                <div>{{ $building->building_no ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Road</label>
                <div>{{ $building->road ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Block</label>
                <div>{{ $building->block ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Area</label>
                <div>{{ $building->area ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">City</label>
                <div>{{ $building->city ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Total No. of Blocks</label>
                <div>{{ $building->total_no_of_blocks ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Total No. of Floors</label>
                <div>{{ $building->total_no_of_floors ?? '—' }}</div>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Total No. of Units</label>
                <div>{{ $building->total_no_of_units ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

@endsection
