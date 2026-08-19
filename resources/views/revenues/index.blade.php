@extends('layouts.admin')

@section('title', 'Revenue')
@section('topbar-title', 'Revenue')

@push('styles')
<style>

.category-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
    background: var(--tone-success-bg); color: var(--tone-success-fg);
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Revenue</h1>
        <p class="page-header-sub">Log income against a building or unit that isn't tied to an invoice</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('revenues.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Revenue
        </a>
    </div>
</div>


{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════
     Same .m-screen / .m-row-card architecture as Payments, Invoices and
     Lease Contracts. Every value carries a visible label so nothing
     renders as a bare figure. ── --}}
<div class="m-screen">
    <div class="m-mini-row">
        <div class="m-mini-stat">
            <div class="v">{{ $stats['total'] }}</div>
            <div class="l">Entries</div>
        </div>
        <div class="m-mini-stat">
            <div class="v">{{ number_format($stats['total_amount'], 0) }}</div>
            <div class="l">Total (BHD)</div>
        </div>
        <div class="m-mini-stat">
            <div class="v">{{ number_format($stats['this_month'], 0) }}</div>
            <div class="l">This Month (BHD)</div>
        </div>
    </div>

    <div class="m-chip-row no-sb">
        <a href="{{ route('revenues.index') }}" class="m-chip {{ !request('category') ? 'active' : '' }}">All</a>
        @foreach($categories as $val => $label)
            <a href="{{ route('revenues.index', ['category' => $val]) }}"
               class="m-chip {{ request('category') === $val ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="m-row-list">
        @forelse($revenues as $revenue)
            <a href="{{ route('revenues.edit', $revenue) }}" class="m-row-card">
                <div class="m-row-icon" style="background:var(--tone-success-bg);color:var(--tone-success-fg);">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="m-row-title">{{ $revenue->description ?: $revenue->category_label }}</div>
                    <div class="m-row-sub">
                        {{ $revenue->building->property_name ?? 'No building' }}@if($revenue->unit) &middot; Unit {{ $revenue->unit->unit_name }}@endif
                    </div>
                    <div class="m-row-sub">
                        Dated {{ $revenue->revenue_date->format('d M Y') }}@if($revenue->source_name) &middot; Source {{ $revenue->source_name }}@endif
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                    <div style="font-family:'Outfit',sans-serif;font-size:13.5px;font-weight:800;color:var(--tone-success-fg);">
                        BHD {{ number_format($revenue->amount, 3) }}
                    </div>
                    <span class="m-row-badge" style="background:var(--tone-success-bg);color:var(--tone-success-fg);">
                        {{ $revenue->category_label }}
                    </span>
                </div>
            </a>
        @empty
            <div class="m-empty">
                <div class="m-empty-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="m-empty-title">No revenue recorded yet</div>
                <div class="m-empty-sub">Try adjusting your filters.</div>
            </div>
        @endforelse
    </div>
</div>

<div class="stats-grid m-hide-desktop-index">
    <div class="stat-card">
        <div class="stat-icon gray"><i class="fa-solid fa-receipt"></i></div>
        <div><div class="stat-val">{{ $stats['total'] }}</div><div class="stat-lbl">Total Entries</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-sack-dollar"></i></div>
        <div><div class="stat-val">{{ number_format($stats['total_amount'], 3) }}</div><div class="stat-lbl">Total (BHD)</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fa-solid fa-calendar-days"></i></div>
        <div><div class="stat-val">{{ number_format($stats['this_month'], 3) }}</div><div class="stat-lbl">This Month (BHD)</div></div>
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
                    <td style="font-size:12px;color:var(--text-muted)">{{ $revenue->unit->unit_name ?? '—' }}</td>
                    <td><span class="category-badge">{{ $revenue->category_label }}</span></td>
                    <td style="font-size:13px">{{ $revenue->description ?: '—' }}</td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $revenue->source_name ?: '—' }}</td>
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
    <div style="padding:16px 20px;border-top:1px solid var(--card-border)">
        {{ $revenues->links() }}
    </div>
    @endif
</div>

@endsection
