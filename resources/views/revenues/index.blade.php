@extends('layouts.admin')

@section('title', 'Revenue')
@section('topbar-title', 'Revenue')
@section('topbar-count', number_format($revenues->total()))

@push('styles')
<style>

</style>
@endpush

@section('content')

@section('page-title', 'Revenue')
@section('page-subtitle', "Log income against a building or unit that isn't tied to an invoice")
@section('page-actions')
    <a href="{{ route('revenues.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> New Revenue
    </a>
@endsection



{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════
     The shared components, same order as every other list screen. ── --}}
@php
    $revCategory = request('category');
    $revChips    = [[
        'label'  => 'All',
        'href'   => route('revenues.index', array_filter(['search' => request('search')])),
        'active' => ! $revCategory,
    ]];
    foreach ($categories as $revVal => $revLabel) {
        $revChips[] = [
            'label'  => $revLabel,
            'href'   => route('revenues.index', array_filter(['search' => request('search'), 'category' => $revVal])),
            'active' => $revCategory === $revVal,
        ];
    }
@endphp
<x-mobile-list
    :actions="['primary' => ['label' => 'Add revenue', 'href' => route('revenues.create')]]"
    :stats="[
        ['value' => $stats['total'],        'label' => 'Entries'],
        ['money' => $stats['total_amount'], 'label' => 'Total BHD'],
        ['money' => $stats['this_month'],   'label' => now()->format('M').' BHD'],
    ]"
    :search="[
        'action'      => route('revenues.index'),
        'placeholder' => 'Search description or source',
        'aria'        => 'Search revenue',
        'keep'        => ['category'],
    ]"
    :chips="$revChips">

    @forelse($revenues as $revenue)
        <a href="{{ route('revenues.edit', $revenue) }}" class="m-row-card ps-reveal">
            <span class="m-row-thumb"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span>
            <span class="m-row-text">
                <span class="m-row-title">{{ $revenue->description ?: $revenue->category_label }}</span>
                <span class="m-row-sub">
                    {{ $revenue->building->property_name ?? 'No building' }}@if($revenue->unit) &middot; Unit {{ $revenue->unit->unit_name }}@endif
                </span>
                <span class="m-row-sub">
                    {{ $revenue->revenue_date->format('d M Y') }}@unless(request('category')) &middot; {{ $revenue->category_label }}@endunless
                </span>
            </span>
            <span class="m-row-amount">BHD {{ number_format($revenue->amount, 0) }}</span>
            <i class="fa-solid fa-chevron-right m-row-chevron" aria-hidden="true"></i>
        </a>
    @empty
        <div class="m-empty">
            <div class="m-empty-icon"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></div>
            <div class="m-empty-title">No revenue yet</div>
            <div class="m-empty-sub">Record money coming in outside rent, and it shows up here.</div>
            <a href="{{ route('revenues.create') }}" class="m-action-btn primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>Add revenue
            </a>
        </div>
    @endforelse
</x-mobile-list>

<div class="stats-grid m-hide-desktop-index">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon gray"><i class="fa-solid fa-receipt"></i></span>
            <span class="stat-lbl">Total Entries</span>
        </div>
        <div class="stat-val">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon green"><i class="fa-solid fa-sack-dollar"></i></span>
            <span class="stat-lbl">Total (BHD)</span>
        </div>
        <div class="stat-val">{{ number_format($stats['total_amount'], 3) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon amber"><i class="fa-solid fa-calendar-days"></i></span>
            <span class="stat-lbl">This Month (BHD)</span>
        </div>
        <div class="stat-val">{{ number_format($stats['this_month'], 3) }}</div>
    </div>
</div>

<div class="table-card m-hide-desktop-index">
    <form method="GET" action="{{ route('revenues.index') }}">
        <div class="filter-bar">
            <div class="filter-group is-search">
                <label for="f_search">Search</label>
                <input type="search" id="f_search" name="search" value="{{ request('search') }}" placeholder="Search description, source…">
            </div>
            <div class="filter-group">
                <label for="f_building_id">Building</label>
                <select id="f_building_id" name="building_id">
                    <option value="">All Buildings</option>
                    @foreach($buildings as $b)
                    <option value="{{ $b->id }}" {{ (string) request('building_id') === (string) $b->id ? 'selected' : '' }}>{{ $b->property_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="f_category">Category</label>
                <select id="f_category" name="category">
                    <option value="">All Categories</option>
                    @foreach($categories as $val => $label)
                    <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="f_date_from">From</label>
                <input type="date" id="f_date_from" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="filter-group">
                <label for="f_date_to">To</label>
                <input type="date" id="f_date_to" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                @if(request()->hasAny(['search','building_id','category','date_from','date_to']))
                <a href="{{ route('revenues.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
                @endif
            </div>
        </div>
    </form>
    @if($revenues->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
        <i class="fa-solid fa-sack-dollar" style="font-size:36px;display:block;margin-bottom:12px;opacity:0.3"></i>
        No revenue recorded yet
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Building</th>
                    <th>Unit</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Source</th>
                    <th>Amount (BHD)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revenues as $revenue)
                <tr data-href="{{ route('revenues.edit', $revenue) }}" style="cursor:pointer">
                    <td style="white-space:nowrap;font-size:12px">{{ $revenue->revenue_date->format('d M Y') }}</td>
                    <td>{{ $revenue->building->property_name ?? '—' }}</td>
                    <td class="cell-muted">{{ $revenue->unit->unit_name ?? '—' }}</td>
                    <td><span class="badge">{{ $revenue->category_label }}</span></td>
                    <td style="font-size:13px">{{ $revenue->description ?: '—' }}</td>
                    <td class="cell-muted">{{ $revenue->source_name ?: '—' }}</td>
                    <td style="font-family:'Outfit',sans-serif;font-weight:700">{{ number_format($revenue->amount, 3) }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center" onclick="event.stopPropagation()">
                            <a href="{{ route('revenues.edit', $revenue) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('revenues.destroy', $revenue) }}"
                                  onsubmit="return confirm('Delete this revenue entry?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" title="Delete" style="color:var(--tone-danger-fg)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $revenues->firstItem() ?? 0 }}–{{ $revenues->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($revenues->total()) }}</strong> entries
        </div>
        {{ $revenues->links() }}
    </div>
    @endif
</div>

@endsection
