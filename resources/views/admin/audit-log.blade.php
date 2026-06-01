@extends('layouts.admin')

@section('title', 'Audit Log')
@section('topbar-title', 'Audit Log')

@push('styles')
<style>
.audit-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.audit-stat {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 16px 18px;
    display: flex; flex-direction: column; gap: 4px;
}
.audit-stat-val {
    font-family: 'Outfit', sans-serif;
    font-size: 28px; font-weight: 800;
    color: var(--text-primary); line-height: 1;
}
.audit-stat-lbl { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }

.filter-bar {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 14px 18px;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
    margin-bottom: 18px;
}
.filter-bar input, .filter-bar select {
    padding: 8px 12px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary);
    outline: none; transition: border-color 0.18s;
}
.filter-bar input:focus, .filter-bar select:focus { border-color: var(--accent); }
.filter-bar input[type="search"] { flex: 1; min-width: 200px; }
.filter-bar select { min-width: 130px; }

.audit-table-wrap {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    overflow: hidden;
}
.audit-table { width: 100%; border-collapse: collapse; }
.audit-table th {
    padding: 10px 16px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.07em;
    color: var(--text-muted); background: var(--page-bg);
    border-bottom: 1px solid var(--card-border); text-align: left; white-space: nowrap;
}
.audit-table td {
    padding: 11px 16px; font-size: 13px;
    color: var(--text-secondary); border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
}
.audit-table tr:last-child td { border-bottom: none; }
.audit-table tr:hover td { background: #FAFBFC; }

.action-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; text-transform: capitalize; white-space: nowrap;
}
.action-badge.created  { background: #ECFDF5; color: #059669; }
.action-badge.updated  { background: #EFF6FF; color: #2563EB; }
.action-badge.deleted  { background: #FEF2F2; color: #DC2626; }
.action-badge.imported { background: var(--accent-dim); color: var(--accent); }

.entity-pill {
    display: inline-block;
    padding: 2px 8px; border-radius: 6px;
    font-size: 11px; font-weight: 600;
    background: var(--page-bg); color: var(--text-muted);
    border: 1px solid var(--card-border);
}

.changes-preview {
    font-size: 11.5px; color: var(--text-muted);
    max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    cursor: default;
}
.changes-preview:hover { white-space: normal; overflow: visible; }

.time-cell { white-space: nowrap; font-size: 12px; color: var(--text-muted); }
.time-cell strong { display: block; font-size: 13px; color: var(--text-primary); }

.empty-state {
    text-align: center; padding: 60px 20px;
    color: var(--text-muted); font-size: 13px;
}
.empty-state i { font-size: 36px; display: block; margin-bottom: 12px; opacity: 0.4; }

.pagination-wrap {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-top: 1px solid var(--card-border);
    font-size: 12px; color: var(--text-muted);
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Audit Log</h1>
        <p class="page-header-sub">All create, update, delete, and import activity across the system</p>
    </div>
    <div class="page-header-actions">
        <form method="POST" action="{{ route('admin.audit-log.clear') }}"
              onsubmit="return confirm('Clear all audit log entries? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="fa-solid fa-trash"></i> Clear Log
            </button>
        </form>
    </div>
</div>

{{-- STATS --}}
<div class="audit-stats">
    <div class="audit-stat">
        <div class="audit-stat-val">{{ number_format($stats['total']) }}</div>
        <div class="audit-stat-lbl">Total Events</div>
    </div>
    <div class="audit-stat">
        <div class="audit-stat-val" style="color:#059669">{{ number_format($stats['created']) }}</div>
        <div class="audit-stat-lbl">Created</div>
    </div>
    <div class="audit-stat">
        <div class="audit-stat-val" style="color:#2563EB">{{ number_format($stats['updated']) }}</div>
        <div class="audit-stat-lbl">Updated</div>
    </div>
    <div class="audit-stat">
        <div class="audit-stat-val" style="color:#DC2626">{{ number_format($stats['deleted']) }}</div>
        <div class="audit-stat-lbl">Deleted</div>
    </div>
    <div class="audit-stat">
        <div class="audit-stat-val" style="color:var(--accent)">{{ number_format($stats['imported']) }}</div>
        <div class="audit-stat-lbl">Imported</div>
    </div>
</div>

{{-- FILTERS --}}
<form method="GET" action="{{ route('admin.audit-log') }}" class="filter-bar">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search entity name or IP…">
    <select name="action" onchange="this.form.submit()">
        <option value="">All Actions</option>
        @foreach(['created','updated','deleted','imported'] as $a)
        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
        @endforeach
    </select>
    <select name="entity_type" onchange="this.form.submit()">
        <option value="">All Entities</option>
        @foreach($entityTypes as $et)
        <option value="{{ $et }}" {{ request('entity_type') === $et ? 'selected' : '' }}>{{ $et }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    @if(request()->hasAny(['search','action','entity_type']))
    <a href="{{ route('admin.audit-log') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
    @endif
</form>

{{-- TABLE --}}
<div class="audit-table-wrap">
    @if($logs->isEmpty())
    <div class="empty-state">
        <i class="fa-solid fa-clock-rotate-left"></i>
        No audit events found
        @if(request()->hasAny(['search','action','entity_type']))
            — try adjusting your filters
        @endif
    </div>
    @else
    <div class="table-wrap">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Name / ID</th>
                    <th>Changes</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td class="time-cell">
                        <strong>{{ $log->created_at->format('d M Y') }}</strong>
                        {{ $log->created_at->format('H:i:s') }}
                    </td>
                    <td>
                        <span class="action-badge {{ $log->action }}">
                            @php
                                $icons = ['created'=>'fa-plus','updated'=>'fa-pen','deleted'=>'fa-trash','imported'=>'fa-file-import'];
                            @endphp
                            <i class="fa-solid {{ $icons[$log->action] ?? 'fa-circle' }}"></i>
                            {{ $log->action }}
                        </span>
                    </td>
                    <td><span class="entity-pill">{{ $log->entity_type }}</span></td>
                    <td style="color:var(--text-primary);font-weight:600;font-size:13px;">
                        {{ $log->entity_name ?? '—' }}
                        @if($log->entity_id)
                        <span style="font-weight:400;color:var(--text-muted);font-size:11px;">#{{ $log->entity_id }}</span>
                        @endif
                    </td>
                    <td>
                        @if($log->changes)
                        <div class="changes-preview" title="{{ json_encode($log->changes, JSON_PRETTY_PRINT) }}">
                            @foreach($log->changes as $field => $change)
                            <span style="color:var(--text-primary)">{{ $field }}</span>:
                            <span style="color:#DC2626;text-decoration:line-through">{{ $change['from'] ?? '—' }}</span>
                            → <span style="color:#059669">{{ $change['to'] ?? '—' }}</span>
                            @if(!$loop->last) &nbsp;·&nbsp; @endif
                            @endforeach
                        </div>
                        @else
                        <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td style="font-family:monospace;font-size:12px;">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">
        <div>Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} events</div>
        <div>{{ $logs->links() }}</div>
    </div>
    @endif
</div>

@endsection
