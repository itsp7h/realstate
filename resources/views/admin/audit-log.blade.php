@extends('layouts.admin')

@section('title', 'Audit Log')
@section('topbar-title', 'Audit Log')

@section('page-title', 'Audit Log')
@section('page-subtitle', 'Every record change and every sign-in attempt across the system')
@section('page-actions')
    <form method="POST" action="{{ route('admin.audit-log.clear') }}"
          onsubmit="return confirm('Clear all audit log entries? This cannot be undone.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">
            <i class="fa-solid fa-trash"></i> Clear Log
        </button>
    </form>
@endsection

@push('styles')
<style>



.changes-preview {
    font-size: var(--fs-xs); color: var(--text-muted);
    max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    cursor: default;
}
.changes-preview:hover { white-space: normal; overflow: visible; }

.time-cell { white-space: nowrap; font-size: var(--fs-sm); color: var(--text-muted); }
.time-cell strong { display: block; font-size: var(--fs-base); color: var(--text-primary); }

.pagination-wrap {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-top: 1px solid var(--card-border);
    font-size: var(--fs-sm); color: var(--text-muted);
}
</style>
@endpush

@section('content')


{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Total Events</span></div>
        <div class="stat-val">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="stat-card is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Created</span></div>
        <div class="stat-val">{{ number_format($stats['created']) }}</div>
    </div>
    <div class="stat-card is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Updated</span></div>
        <div class="stat-val">{{ number_format($stats['updated']) }}</div>
    </div>
    <div class="stat-card is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Deleted</span></div>
        <div class="stat-val">{{ number_format($stats['deleted']) }}</div>
    </div>
    <div class="stat-card is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Imported</span></div>
        <div class="stat-val">{{ number_format($stats['imported']) }}</div>
    </div>
    <div class="stat-card is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Sign-ins</span></div>
        <div class="stat-val">{{ number_format($stats['signed_in']) }}</div>
    </div>
    <div class="stat-card is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Failed sign-ins</span></div>
        <div class="stat-val {{ $stats['sign_in_failed'] + $stats['locked_out'] > 0 ? 'val-negative' : '' }}">{{ number_format($stats['sign_in_failed'] + $stats['locked_out']) }}</div>
    </div>
</div>

{{-- FILTERS --}}

{{-- TABLE --}}
<div class="table-card">
    <form method="GET" action="{{ route('admin.audit-log') }}">
        <div class="filter-bar">
            <div class="filter-group is-search">
                <label for="f_search">Search</label>
                <input type="search" id="f_search" name="search" value="{{ request('search') }}" placeholder="Search entity name or IP…">
            </div>
            <div class="filter-group">
                <label for="f_action">Action</label>
                <select id="f_action" name="action" onchange="this.form.submit()">
                    <option value="">All Actions</option>
                    @foreach(\App\Models\AuditLog::ACTIONS as $a)
                    <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ \Illuminate\Support\Str::ucfirst(str_replace('_', ' ', $a)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="f_entity_type">Entity</label>
                <select id="f_entity_type" name="entity_type" onchange="this.form.submit()">
                    <option value="">All Entities</option>
                    @foreach($entityTypes as $et)
                    <option value="{{ $et }}" {{ request('entity_type') === $et ? 'selected' : '' }}>{{ $et }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                @if(request()->hasAny(['search','action','entity_type']))
                <a href="{{ route('admin.audit-log') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
                @endif
            </div>
        </div>
    </form>
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
        <table>
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
                    <td data-label="Time" class="time-cell">
                        <strong>{{ $log->created_at->format('d M Y') }}</strong>
                        {{ $log->created_at->format('H:i:s') }}
                    </td>
                    <td data-label="Action">
                        <span class="status-badge {{ $log->action }}">
                            <i class="fa-solid {{ $log->action_icon }}"></i>
                            {{ $log->action_label }}
                        </span>
                    </td>
                    <td data-label="Entity"><span class="badge">{{ $log->entity_type }}</span></td>
                    <td data-label="Name / ID" style="color:var(--text-primary);font-weight:600;font-size:13px;">
                        {{ $log->entity_name ?? '—' }}
                        @if($log->entity_id)
                        <span style="font-weight:400;color:var(--text-muted);font-size:11px;">#{{ $log->entity_id }}</span>
                        @endif
                    </td>
                    <td data-label="Changes">
                        @if($log->changes)
                        <div class="changes-preview" title="{{ json_encode($log->changes, JSON_PRETTY_PRINT) }}">
                            @foreach($log->changes as $field => $change)
                            <span style="color:var(--text-primary)">{{ $field }}</span>:
                            <span style="color:var(--tone-danger-fg);text-decoration:line-through">{{ $change['from'] ?? '—' }}</span>
                            → <span style="color:var(--tone-success-fg)">{{ $change['to'] ?? '—' }}</span>
                            @if(!$loop->last) &nbsp;·&nbsp; @endif
                            @endforeach
                        </div>
                        @else
                        <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td data-label="IP Address" style="font-family:monospace;font-size:12px;">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($logs->total()) }}</strong> events
        </div>
        {{ $logs->links() }}
    </div>
    @endif
</div>

@endsection
