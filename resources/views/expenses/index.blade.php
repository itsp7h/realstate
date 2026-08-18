@extends('layouts.admin')

@section('title', 'Expenses')
@section('topbar-title', 'Expenses')

@push('styles')
<style>
.exp-stats {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px; margin-bottom: 24px;
}
.exp-stat {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
}
.exp-stat-icon {
    width: 40px; height: 40px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
}
.exp-stat-icon.red   { background: #FEF2F2; color: #DC2626; }
.exp-stat-icon.gray  { background: #F1F5F9; color: #64748B; }
.exp-stat-icon.amber { background: #FFFBEB; color: #D97706; }
.exp-stat-val { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.exp-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

.category-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
    background: #FEF2F2; color: #DC2626;
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Expenses</h1>
        <p class="page-header-sub">Log one-off costs against a building or unit</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('expenses.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Expense
        </a>
    </div>
</div>

<div class="exp-stats">
    <div class="exp-stat">
        <div class="exp-stat-icon gray"><i class="fa-solid fa-receipt"></i></div>
        <div><div class="exp-stat-val">{{ $stats['total'] }}</div><div class="exp-stat-lbl">Total Entries</div></div>
    </div>
    <div class="exp-stat">
        <div class="exp-stat-icon red"><i class="fa-solid fa-sack-dollar"></i></div>
        <div><div class="exp-stat-val">{{ number_format($stats['total_amount'], 3) }}</div><div class="exp-stat-lbl">Total (BHD)</div></div>
    </div>
    <div class="exp-stat">
        <div class="exp-stat-icon amber"><i class="fa-solid fa-calendar-days"></i></div>
        <div><div class="exp-stat-val">{{ number_format($stats['this_month'], 3) }}</div><div class="exp-stat-lbl">This Month (BHD)</div></div>
    </div>
</div>

<div class="table-card">
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
                    <td style="font-size:12px;color:var(--text-muted)">{{ $expense->unit->unit_name ?? '—' }}</td>
                    <td><span class="category-badge">{{ $expense->category_label }}</span></td>
                    <td style="font-size:13px">{{ $expense->description ?: '—' }}</td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $expense->vendor_name ?: '—' }}</td>
                    <td style="font-family:'Outfit',sans-serif;font-weight:700">{{ number_format($expense->amount, 3) }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center" onclick="event.stopPropagation()">
                            <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                  onsubmit="return confirm('Delete this expense entry?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm" title="Delete" style="color:#DC2626">
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
        {{ $expenses->links() }}
    </div>
    @endif
</div>

@endsection
