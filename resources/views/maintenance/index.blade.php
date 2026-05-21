@extends('layouts.admin')

@section('title', 'Maintenance Requests')
@section('topbar-title', 'Maintenance Management')

@push('styles')
<style>
.maint-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 14px; margin-bottom: 24px;
}
.maint-stat {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
}
.maint-stat-icon {
    width: 40px; height: 40px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
}
.maint-stat-icon.blue   { background: #EFF6FF; color: #2563EB; }
.maint-stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
.maint-stat-icon.green  { background: #ECFDF5; color: #059669; }
.maint-stat-icon.gray   { background: #F1F5F9; color: #64748B; }
.maint-stat-val { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.maint-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

.filter-bar {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 14px 18px;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px;
}
.filter-bar input, .filter-bar select {
    padding: 8px 12px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
    transition: border-color 0.18s;
}
.filter-bar input:focus, .filter-bar select:focus { border-color: var(--accent); }
.filter-bar input[type="search"] { flex: 1; min-width: 180px; }
.filter-bar input[type="date"]   { min-width: 140px; }

.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.status-badge.open        { background: #EFF6FF; color: #2563EB; }
.status-badge.in_progress { background: var(--accent-dim); color: var(--accent); }
.status-badge.completed   { background: #ECFDF5; color: #059669; }
.status-badge.cancelled   { background: #FEF2F2; color: #DC2626; }

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; }

.actions-cell { display: flex; gap: 6px; align-items: center; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Maintenance Requests</h1>
        <p class="page-header-sub">Track and manage all property maintenance work orders</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('maintenance.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Request
        </a>
    </div>
</div>

{{-- STATS --}}
<div class="maint-stats">
    <div class="maint-stat">
        <div class="maint-stat-icon gray"><i class="fa-solid fa-clipboard-list"></i></div>
        <div>
            <div class="maint-stat-val">{{ $stats['total'] }}</div>
            <div class="maint-stat-lbl">Total</div>
        </div>
    </div>
    <div class="maint-stat">
        <div class="maint-stat-icon blue"><i class="fa-solid fa-circle-dot"></i></div>
        <div>
            <div class="maint-stat-val">{{ $stats['open'] }}</div>
            <div class="maint-stat-lbl">Open</div>
        </div>
    </div>
    <div class="maint-stat">
        <div class="maint-stat-icon gold"><i class="fa-solid fa-rotate"></i></div>
        <div>
            <div class="maint-stat-val">{{ $stats['in_progress'] }}</div>
            <div class="maint-stat-lbl">In Progress</div>
        </div>
    </div>
    <div class="maint-stat">
        <div class="maint-stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="maint-stat-val">{{ $stats['completed'] }}</div>
            <div class="maint-stat-lbl">Completed</div>
        </div>
    </div>
</div>

{{-- FILTERS --}}
<form method="GET" action="{{ route('maintenance.index') }}" class="filter-bar">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search job order, property, tenant…">
    <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        @foreach(['open','in_progress','completed','cancelled'] as $s)
        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ request('date_from') }}" title="From date">
    <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="To date">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    @if(request()->hasAny(['search','status','date_from','date_to']))
    <a href="{{ route('maintenance.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
    @endif
</form>

{{-- TABLE --}}
<div class="table-card">
    @if($requests->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
        <i class="fa-solid fa-wrench" style="font-size:36px;display:block;margin-bottom:12px;opacity:0.3"></i>
        No maintenance requests found
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Job Order</th>
                    <th>Date</th>
                    <th>Property</th>
                    <th>Tenant</th>
                    <th>Flat</th>
                    <th>Apt. Status</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr>
                    <td style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--text-primary)">
                        {{ $req->job_order ?? '—' }}
                    </td>
                    <td style="white-space:nowrap;font-size:12px">{{ $req->date?->format('d M Y') }}</td>
                    <td>{{ $req->property }}</td>
                    <td>{{ $req->tenant }}</td>
                    <td style="font-weight:600">{{ $req->flat }}</td>
                    <td><span class="badge badge-gray">{{ ucfirst($req->apartment_status) }}</span></td>
                    <td>
                        <span class="status-badge {{ $req->status }}">
                            <i class="fa-solid fa-circle" style="font-size:6px"></i>
                            {{ $req->status_label }}
                        </span>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <a href="{{ route('maintenance.show', $req) }}" class="btn btn-outline btn-sm" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('maintenance.edit', $req) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('maintenance.destroy', $req) }}"
                                  onsubmit="return confirm('Delete this maintenance request?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
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
    <div style="padding:14px 18px;border-top:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--text-muted)">
        <div>Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }}</div>
        <div>{{ $requests->links() }}</div>
    </div>
    @endif
</div>

@endsection
