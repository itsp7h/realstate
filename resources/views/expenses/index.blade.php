@extends('layouts.admin')

@section('title', 'Expenses')
@section('topbar-title', 'Expenses')
@section('topbar-count', number_format($expenses->total()))

@push('styles')
<style>

</style>
@endpush

@section('content')

@section('page-title', 'Expenses')
@section('page-subtitle', 'Log one-off costs against a building or unit')
@section('page-actions')
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> New Expense
    </a>
@endsection



{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════
     header → actions → stats → search → chips → rows, on the shared
     components. Nothing on this screen is expenses-specific except the
     copy and the route names. ── --}}
@php
    $expCategory = request('category');
    $expChips    = [[
        'label'  => 'All',
        'href'   => route('expenses.index', array_filter(['search' => request('search')])),
        'active' => ! $expCategory,
    ]];
    foreach ($categories as $expVal => $expLabel) {
        $expChips[] = [
            'label'  => $expLabel,
            'href'   => route('expenses.index', array_filter(['search' => request('search'), 'category' => $expVal])),
            'active' => $expCategory === $expVal,
        ];
    }
@endphp
<x-mobile-list
    :actions="['primary' => ['label' => 'Add expense', 'href' => route('expenses.create')]]"
    :stats="[
        ['value' => $stats['total'],        'label' => 'Entries'],
        ['money' => $stats['total_amount'], 'label' => 'Total BHD'],
        ['money' => $stats['this_month'],   'label' => now()->format('M').' BHD'],
    ]"
    :search="[
        'action'      => route('expenses.index'),
        'placeholder' => 'Search description or vendor',
        'aria'        => 'Search expenses',
        'keep'        => ['category'],
    ]"
    :chips="$expChips">

    @forelse($expenses as $expense)
        <a href="{{ route('expenses.edit', $expense) }}" class="m-row-card ps-reveal">
            <span class="m-row-thumb"><i class="fa-solid fa-receipt" aria-hidden="true"></i></span>
            <span class="m-row-text">
                <span class="m-row-title">{{ $expense->description ?: $expense->category_label }}</span>
                <span class="m-row-sub">
                    {{ $expense->building->property_name ?? 'No building' }}@if($expense->unit) &middot; Unit {{ $expense->unit->unit_name }}@endif
                </span>
                {{-- The category repeats the active chip, so it only earns
                     a line when no chip is filtering by it. --}}
                <span class="m-row-sub">
                    {{ $expense->expense_date->format('d M Y') }}@unless(request('category')) &middot; {{ $expense->category_label }}@endunless
                </span>
            </span>
            <span class="m-row-amount">BHD {{ number_format($expense->amount, 0) }}</span>
            <i class="fa-solid fa-chevron-right m-row-chevron" aria-hidden="true"></i>
        </a>
    @empty
        <div class="m-empty">
            <div class="m-empty-icon"><i class="fa-solid fa-receipt" aria-hidden="true"></i></div>
            <div class="m-empty-title">No expenses yet</div>
            <div class="m-empty-sub">Record what a property cost you, and it shows up here.</div>
            <a href="{{ route('expenses.create') }}" class="m-action-btn primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>Add expense
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
            <span class="stat-icon red"><i class="fa-solid fa-sack-dollar"></i></span>
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
    <form method="GET" action="{{ route('expenses.index') }}">
        <div class="filter-bar">
            <div class="filter-group is-search">
                <label for="f_search">Search</label>
                <input type="search" id="f_search" name="search" value="{{ request('search') }}" placeholder="Search description, vendor…">
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
                <a href="{{ route('expenses.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
                @endif
            </div>
        </div>
    </form>
    @if($expenses->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
        <i class="fa-solid fa-receipt" style="font-size:36px;display:block;margin-bottom:12px;opacity:0.3"></i>
        No expenses recorded yet
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
                    <th>Vendor</th>
                    <th>Amount (BHD)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $expense)
                <tr data-href="{{ route('expenses.edit', $expense) }}" style="cursor:pointer">
                    <td style="white-space:nowrap;font-size:12px">{{ $expense->expense_date->format('d M Y') }}</td>
                    <td>{{ $expense->building->property_name ?? '—' }}</td>
                    <td class="cell-muted">{{ $expense->unit->unit_name ?? '—' }}</td>
                    <td><span class="badge">{{ $expense->category_label }}</span></td>
                    <td style="font-size:13px">{{ $expense->description ?: '—' }}</td>
                    <td class="cell-muted">{{ $expense->vendor_name ?: '—' }}</td>
                    <td style="font-family:'Outfit',sans-serif;font-weight:700">{{ number_format($expense->amount, 3) }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center" onclick="event.stopPropagation()">
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                  onsubmit="return confirm('Delete this expense entry?')">
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
            Showing <strong>{{ $expenses->firstItem() ?? 0 }}–{{ $expenses->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($expenses->total()) }}</strong> expenses
        </div>
        {{ $expenses->links() }}
    </div>
    @endif
</div>

@endsection
