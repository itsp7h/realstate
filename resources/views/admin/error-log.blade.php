@extends('layouts.admin')

@section('title', 'Error Log')
@section('topbar-title', 'Error Log')

@push('styles')
<style>
.error-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.error-stat {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 16px 18px;
    display: flex; flex-direction: column; gap: 4px;
}
.error-stat-val { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; line-height: 1; }
.error-stat-lbl { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }

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

.log-timeline {
    display: flex; flex-direction: column; gap: 10px;
}
.log-entry {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: box-shadow 0.18s;
}
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

.level-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px;
    font-size: 10px; font-weight: 800; letter-spacing: 0.05em;
    flex-shrink: 0; margin-top: 1px;
}
.level-badge.ERROR   { background: #FEF2F2; color: #DC2626; }
.level-badge.WARNING { background: #FFFBEB; color: #D97706; }
.level-badge.INFO    { background: #EFF6FF; color: #2563EB; }
.level-badge.DEBUG   { background: #F1F5F9; color: #64748B; }

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
.log-trace pre {
    margin: 10px 0 0;
    font-family: 'Fira Code', 'Courier New', monospace;
    font-size: 11.5px;
    color: var(--text-secondary);
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 12px 14px;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-word;
    max-height: 400px;
    overflow-y: auto;
    line-height: 1.6;
}

.empty-state {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    text-align: center; padding: 60px 20px;
    color: var(--text-muted); font-size: 13px;
}
.empty-state i { font-size: 36px; display: block; margin-bottom: 12px; opacity: 0.4; }

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
<div class="error-stats">
    <div class="error-stat">
        <div class="error-stat-val" style="color:var(--text-primary)">{{ number_format($stats['total']) }}</div>
        <div class="error-stat-lbl">Total Entries</div>
    </div>
    <div class="error-stat">
        <div class="error-stat-val" style="color:#DC2626">{{ number_format($stats['error']) }}</div>
        <div class="error-stat-lbl">Errors</div>
    </div>
    <div class="error-stat">
        <div class="error-stat-val" style="color:#D97706">{{ number_format($stats['warning']) }}</div>
        <div class="error-stat-lbl">Warnings</div>
    </div>
    <div class="error-stat">
        <div class="error-stat-val" style="color:#2563EB">{{ number_format($stats['info']) }}</div>
        <div class="error-stat-lbl">Info</div>
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
    <div class="log-entry level-{{ $levelClass }}" id="entry-{{ $i }}">
        <div class="log-entry-header" onclick="toggleEntry({{ $i }})">
            <span class="level-badge {{ $entry['level'] }}">{{ $entry['level'] }}</span>
            <span class="log-time">{{ $entry['timestamp'] }}</span>
            <div class="log-message">{{ $entry['message'] }}</div>
            @if($entry['trace'])
            <i class="fa-solid fa-chevron-down log-toggle"></i>
            @endif
        </div>
        @if($entry['trace'])
        <div class="log-trace">
            <pre>{{ $entry['trace'] }}</pre>
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
