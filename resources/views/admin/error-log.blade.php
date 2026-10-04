@extends('layouts.admin')

@section('title', 'Error Log')
@section('topbar-title', 'Error Log')

@section('page-title', 'Error Log')
@section('page-subtitle')
    Application errors and warnings from <code>storage/logs/laravel.log</code>
@endsection
@section('page-actions')
    <form method="POST" action="{{ route('admin.error-log.clear') }}"
          onsubmit="return confirm('Clear the entire log file? This cannot be undone.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">
            <i class="fa-solid fa-trash"></i> Clear Log File
        </button>
    </form>
@endsection

@push('styles')
<style>

.log-timeline {
    display: flex; flex-direction: column; gap: 10px;
}
.log-entry { overflow: hidden; transition: box-shadow 0.18s; }
.log-entry:hover { box-shadow: var(--shadow-md); }
.log-entry.level-error   { border-left: 3px solid var(--danger); }
.log-entry.level-warning { border-left: 3px solid var(--warning); }
.log-entry.level-info    { border-left: 3px solid var(--info); }
.log-entry.level-debug   { border-left: 3px solid var(--text-muted); }

.log-entry-header {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 13px 16px; cursor: pointer; user-select: none;
}
.log-entry-header:hover { background: var(--page-bg); }

/* Typographic modifier only — worn alongside .status-badge, which supplies
   the shape and the error/warning/info/debug tones from app-core. */

.log-time {
    font-family: monospace; font-size: var(--fs-xs);
    color: var(--text-muted); flex-shrink: 0; white-space: nowrap; margin-top: 2px;
}
.log-message {
    flex: 1; font-size: var(--fs-base); color: var(--text-primary);
    line-height: 1.5; word-break: break-word;
}
.log-toggle {
    flex-shrink: 0; color: var(--text-muted);
    font-size: var(--fs-xs); margin-top: 2px;
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
    margin-top: 18px; font-size: var(--fs-sm); color: var(--text-muted);
}
.pager-btns { display: flex; gap: 6px; }
</style>
@endpush

@section('content')


{{-- STATS --}}
<div class="stats-grid">
    <div class="stat-card log-entry is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Total Entries</span></div>
        <div class="stat-val">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="stat-card log-entry is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Errors</span></div>
        <div class="stat-val">{{ number_format($stats['error']) }}</div>
    </div>
    <div class="stat-card log-entry is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Warnings</span></div>
        <div class="stat-val">{{ number_format($stats['warning']) }}</div>
    </div>
    <div class="stat-card log-entry is-figure">
        <div class="stat-card-top"><span class="stat-lbl">Info</span></div>
        <div class="stat-val">{{ number_format($stats['info']) }}</div>
    </div>
</div>

{{-- FILTERS — the standard pattern: a .filter-card, because the result below
     is a timeline rather than a table for the bar to sit on top of. --}}
<div class="filter-card">
    <form method="GET" action="{{ route('admin.error-log') }}">
        <div class="filter-bar">
            <div class="filter-group is-search">
                <label for="logSearch">Search</label>
                <input id="logSearch" type="search" name="search" value="{{ request('search') }}" placeholder="Search messages…">
            </div>
            <div class="filter-group">
                <label for="logLevel">Level</label>
                <select id="logLevel" name="level" onchange="this.form.submit()">
                    <option value="">All levels</option>
                    @foreach(['ERROR','WARNING','INFO','DEBUG'] as $lvl)
                        <option value="{{ $lvl }}" {{ request('level') === $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                @if(request()->hasAny(['search','level']))
                    <a href="{{ route('admin.error-log') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
                @endif
                <span class="result-count"><strong>{{ number_format($total) }}</strong> entries</span>
            </div>
        </div>
    </form>
</div>

{{-- TIMELINE --}}
@if(empty($paged))
<div class="empty-state">
    <i class="fa-solid fa-circle-check" style="color:var(--success)"></i>
    No log entries found — looking clean!
</div>
@else
<div class="log-timeline">
    @foreach($paged as $i => $entry)
    @php $levelClass = strtolower($entry['level']); @endphp
    <div class="card log-entry level-{{ $levelClass }}" id="entry-{{ $i }}">
        <div class="card-header" onclick="toggleEntry({{ $i }})">
            <span class="status-badge {{ strtolower($entry['level']) }}">{{ $entry['level'] }}</span>
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
