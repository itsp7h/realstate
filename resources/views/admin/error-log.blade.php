@extends('layouts.admin')

@section('title', 'Error Log')
@section('topbar-title', 'Error Log')

@push('styles')
<style>

.log-timeline {
    display: flex; flex-direction: column; gap: 10px;
}
.log-entry { overflow: hidden; transition: box-shadow 0.18s; }
.log-entry:hover { box-shadow: var(--shadow-md); }
.log-entry.level-error   { border-left: 3px solid #EF4444; }
.log-entry.level-warning { border-left: 3px solid #F59E0B; }
.log-entry.level-info    { border-left: 3px solid #3B82F6; }
.log-entry.level-debug   { border-left: 3px solid #94A3B8; }

.log-entry-header {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 13px 16px; cursor: pointer; user-select: none;
}
.log-entry-header:hover { background: var(--page-bg); }

/* Typographic modifier only — worn alongside .status-badge, which supplies
   the shape and the error/warning/info/debug tones from app-core. */
.level-badge {
    font-size: var(--fs-2xs); font-weight: 800; letter-spacing: 0.05em;
    flex-shrink: 0; margin-top: 1px;
}

.log-time {
    font-family: monospace; font-size: 11.5px;
    color: var(--text-muted); flex-shrink: 0; white-space: nowrap; margin-top: 2px;
}
.log-message {
    flex: 1; font-size: 13px; color: var(--text-primary);
    line-height: 1.5; word-break: break-word;
}
.log-toggle {
    flex-shrink: 0; color: var(--text-muted);
    font-size: 11px; margin-top: 2px;
    transition: transform 0.2s;
}
.log-entry.open .log-toggle { transform: rotate(180deg); }

.log-trace {
    display: none;
    padding: 0 16px 14px;
    border-top: 1px solid var(--card-border);
}
.log-entry.open .log-trace { display: block; }

.pager {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 18px; font-size: 12px; color: var(--text-muted);
}
.pager-btns { display: flex; gap: 6px; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Error Log</h1>
        <p class="page-header-sub">Application errors and warnings from <code>storage/logs/laravel.log</code></p>
    </div>
    <div class="page-header-actions">
        <form method="POST" action="{{ route('admin.error-log.clear') }}"
              onsubmit="return confirm('Clear the entire log file? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="fa-solid fa-trash"></i> Clear Log File
            </button>
        </form>
    </div>
</div>

{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card log-entry is-figure">
        <div class="stat-val" style="color:var(--text-primary)">{{ number_format($stats['total']) }}</div>
        <div class="stat-lbl">Total Entries</div>
    </div>
    <div class="stat-card log-entry is-figure">
        <div class="stat-val" style="color:var(--tone-danger-fg)">{{ number_format($stats['error']) }}</div>
        <div class="stat-lbl">Errors</div>
    </div>
    <div class="stat-card log-entry is-figure">
        <div class="stat-val" style="color:var(--tone-warning-fg)">{{ number_format($stats['warning']) }}</div>
        <div class="stat-lbl">Warnings</div>
    </div>
    <div class="stat-card log-entry is-figure">
        <div class="stat-val" style="color:var(--tone-info-fg)">{{ number_format($stats['info']) }}</div>
        <div class="stat-lbl">Info</div>
    </div>
</div>

{{-- FILTERS --}}
<form method="GET" action="{{ route('admin.error-log') }}" class="filter-bar">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search messages…">
    <select name="level" onchange="this.form.submit()">
        <option value="">All Levels</option>
        @foreach(['ERROR','WARNING','INFO','DEBUG'] as $lvl)
        <option value="{{ $lvl }}" {{ request('level') === $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    @if(request()->hasAny(['search','level']))
    <a href="{{ route('admin.error-log') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
    @endif
    <span style="margin-left:auto;font-size:12px;color:var(--text-muted)">{{ number_format($total) }} entries</span>
</form>

{{-- TIMELINE --}}
@if(empty($paged))
<div class="empty-state">
    <i class="fa-solid fa-circle-check" style="color:#10B981"></i>
    No log entries found — looking clean!
</div>
@else
<div class="log-timeline">
    @foreach($paged as $i => $entry)
    @php $levelClass = strtolower($entry['level']); @endphp
    <div class="card log-entry level-{{ $levelClass }}" id="entry-{{ $i }}">
        <div class="card-header" onclick="toggleEntry({{ $i }})">
            <span class="status-badge level-badge {{ strtolower($entry['level']) }}">{{ $entry['level'] }}</span>
            <span class="log-time">{{ $entry['timestamp'] }}</span>
            <div class="log-message">{{ $entry['message'] }}</div>
            @if($entry['trace'])
            <i class="fa-solid fa-chevron-down log-toggle"></i>
            @endif
        </div>
        @if($entry['trace'])
        <div class="log-trace">
            <pre class="code-block">{{ $entry['trace'] }}</pre>
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- PAGINATION --}}
@if($pages > 1)
<div class="pager">
    <div>Page {{ $page }} of {{ $pages }}</div>
    <div class="pager-btns">
        @if($page > 1)
        <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-chevron-left"></i> Previous
        </a>
        @endif
        @if($page < $pages)
        <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}" class="btn btn-primary btn-sm">
            Next <i class="fa-solid fa-chevron-right"></i>
        </a>
        @endif
    </div>
</div>
@endif
@endif

@endsection

@push('scripts')
<script>
function toggleEntry(i) {
    const el = document.getElementById('entry-' + i);
    el.classList.toggle('open');
}
</script>
@endpush
