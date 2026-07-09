@extends('layouts.admin')

@section('title', 'Maintenance Requests')
@section('topbar-title', 'Maintenance Management')

@push('styles')
<style>
/* ── STATS ─────────────────────────────────────────────── */
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
.maint-stat-icon.orange { background: #FFF7ED; color: #EA580C; }
.maint-stat-icon.purple { background: #F5F3FF; color: #7C3AED; }
.maint-stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
.maint-stat-icon.green  { background: #ECFDF5; color: #059669; }
.maint-stat-icon.teal   { background: #F0FDFA; color: #0D9488; }
.maint-stat-icon.gray   { background: #F1F5F9; color: #64748B; }
.maint-stat-val { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.maint-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

/* ── FILTER BAR ─────────────────────────────────────────── */
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

/* ── STATUS BADGES ──────────────────────────────────────── */
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.status-badge.waiting_supervisor { background: #FFF7ED; color: #EA580C; }
.status-badge.waiting_approval   { background: #F5F3FF; color: #7C3AED; }
.status-badge.approved           { background: #ECFDF5; color: #059669; }
.status-badge.in_progress        { background: #EFF6FF; color: #2563EB; }
.status-badge.completed          { background: #F0FDFA; color: #0D9488; }
.status-badge.cancelled          { background: #FEF2F2; color: #DC2626; }

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; }
.actions-cell { display: flex; gap: 6px; align-items: center; }

/* ── MODAL ──────────────────────────────────────────────── */
.modal-overlay {
    position:fixed;inset:0;z-index:1000;
    background:rgba(11,17,32,.55);backdrop-filter:blur(4px);
    display:flex;align-items:center;justify-content:center;padding:20px;
    opacity:0;pointer-events:none;transition:opacity .25s ease;
}
.modal-overlay.open { opacity:1;pointer-events:all; }
.modal-box {
    background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;
    box-shadow:0 24px 60px rgba(0,0,0,.18),0 8px 24px rgba(0,0,0,.10);
    width:100%;max-width:820px;max-height:92vh;
    display:flex;flex-direction:column;
    transform:translateY(20px) scale(.98);
    transition:transform .3s cubic-bezier(.22,1,.36,1);
    overflow:hidden;
}
.modal-overlay.open .modal-box { transform:translateY(0) scale(1); }
.modal-header { padding:18px 24px 0;border-bottom:1px solid var(--card-border);flex-shrink:0; }
.modal-header-top { display:flex;align-items:center;gap:12px;padding-bottom:14px; }
.modal-header-icon {
    width:40px;height:40px;border-radius:10px;
    background:var(--accent-dim);border:1px solid rgba(232,184,109,.25);
    display:flex;align-items:center;justify-content:center;
    color:var(--accent);font-size:16px;flex-shrink:0;
}
.modal-header-title { font-family:'Outfit',sans-serif;font-size:17px;font-weight:800;color:var(--text-primary); }
.modal-header-sub { font-size:12px;color:var(--text-muted);margin-top:2px; }
.modal-close-btn {
    margin-left:auto;width:32px;height:32px;border-radius:var(--radius-sm);
    border:1.5px solid var(--card-border);background:transparent;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    color:var(--text-muted);font-size:13px;transition:all .15s;
}
.modal-close-btn:hover { background:var(--page-bg);color:var(--text-primary); }

/* ── MODAL TABS ─────────────────────────────────────────── */
.modal-tabs { display:flex;gap:0;overflow-x:auto; }
.modal-tabs::-webkit-scrollbar { display:none; }
.mtab-btn {
    padding:10px 16px;font-size:12px;font-weight:700;color:var(--text-muted);
    border:none;background:none;cursor:pointer;
    border-bottom:2px solid transparent;margin-bottom:-1px;
    white-space:nowrap;display:flex;align-items:center;gap:6px;
    transition:color .15s,border-color .15s;
    font-family:'Plus Jakarta Sans',sans-serif;text-transform:uppercase;letter-spacing:.05em;
}
.mtab-btn:hover { color:var(--text-primary); }
.mtab-btn.active { color:var(--accent);border-bottom-color:var(--accent); }
.mtab-btn .err-dot {
    width:6px;height:6px;border-radius:50%;background:var(--danger);
    display:none;
}
.mtab-btn .err-dot.show { display:inline-block; }

.modal-body { padding:20px 24px;overflow-y:auto;flex:1; }
.modal-body::-webkit-scrollbar { width:4px; }
.modal-body::-webkit-scrollbar-thumb { background:#CBD5E1;border-radius:10px; }

.mtab-panel { display:none; }
.mtab-panel.active { display:block; }

.modal-footer {
    padding:14px 24px;border-top:1px solid var(--card-border);
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    flex-shrink:0;
}
.modal-footer-nav { display:flex;gap:8px; }

/* ── MODAL FIELDS ───────────────────────────────────────── */
.mfield-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:14px 20px; }
.mfield-grid .span-full { grid-column:1/-1; }
.mfield-grid .span-2   { grid-column:span 2; }
.mfield-group { display:flex;flex-direction:column; }
.mfield-label {
    font-size:11px;font-weight:700;color:var(--text-secondary);
    letter-spacing:.04em;text-transform:uppercase;margin-bottom:5px;
    display:flex;align-items:center;gap:3px;
}
.mfield-label .req { color:var(--danger);font-size:13px;line-height:1; }
.mfield-input, .mfield-select, .mfield-textarea {
    width:100%;padding:9px 12px;
    border:1.5px solid var(--input-border);border-radius:var(--radius-sm);
    background:#fff;color:var(--text-primary);
    font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;
    outline:none;appearance:none;-webkit-appearance:none;
    transition:border-color .2s,box-shadow .2s;
}
.mfield-input:focus,.mfield-select:focus,.mfield-textarea:focus { border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim);background:#FFFDF8; }
.mfield-input.is-invalid,.mfield-select.is-invalid,.mfield-textarea.is-invalid { border-color:var(--danger);background:#FFF8F8; }
.mfield-select {
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%2394A3B8' d='M5 7L0.669873 2.5L9.33013 2.5L5 7Z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;
}
.mfield-textarea { resize:vertical;min-height:80px; }
.mfield-error { display:flex;align-items:center;gap:4px;margin-top:4px;font-size:11px;color:var(--danger);font-weight:500; }

/* ── JOB LINES IN MODAL ─────────────────────────────────── */
.modal-job-lines-table { width:100%;border-collapse:collapse;margin-top:10px; }
.modal-job-lines-table th {
    padding:8px 10px;font-size:11px;font-weight:700;text-transform:uppercase;
    letter-spacing:.06em;color:var(--text-muted);background:var(--page-bg);
    border-bottom:1px solid var(--card-border);text-align:left;
}
.modal-job-lines-table td { padding:6px 4px;border-bottom:1px solid #F1F5F9;vertical-align:top; }
.modal-job-lines-table tr:last-child td { border-bottom:none; }
.modal-job-lines-table input,.modal-job-lines-table textarea {
    width:100%;padding:7px 9px;font-size:13px;
    border:1.5px solid var(--input-border);border-radius:var(--radius-sm);
    background:#fff;color:var(--text-primary);outline:none;
    transition:border-color .18s;font-family:'Plus Jakarta Sans',sans-serif;
}
.modal-job-lines-table input:focus,.modal-job-lines-table textarea:focus { border-color:var(--accent); }
.modal-job-lines-table textarea { resize:vertical;min-height:54px; }
.modal-remove-line-btn {
    background:none;border:none;color:#DC2626;cursor:pointer;
    font-size:13px;padding:5px;border-radius:6px;transition:background .15s;
}
.modal-remove-line-btn:hover { background:#FEF2F2; }

/* ── QUOTATION INLINE ATTACHMENT IN MODAL ───────────────── */
.mquot-wrap { position:relative; }
.mquot-wrap .mfield-input { padding-right:36px; }
.mquot-clip-btn {
    position:absolute;right:0;top:0;bottom:0;width:34px;
    display:flex;align-items:center;justify-content:center;
    background:none;border:none;border-left:1.5px solid var(--input-border);
    border-radius:0 var(--radius-sm) var(--radius-sm) 0;
    color:var(--text-muted);cursor:pointer;font-size:12px;
    transition:color .15s,background .15s;
}
.mquot-clip-btn:hover { color:var(--accent);background:var(--accent-dim); }
.mquot-clip-btn.has-file { color:var(--accent);background:var(--accent-dim); }
.mquot-pill {
    display:none;align-items:center;gap:5px;margin-top:5px;
    padding:3px 8px 3px 6px;background:var(--accent-dim);
    border-radius:20px;font-size:11px;font-weight:600;color:var(--accent);
    max-width:100%;overflow:hidden;
}
.mquot-pill.show { display:flex; }
.mquot-pill span { overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0; }
.mquot-pill button {
    background:none;border:none;color:var(--accent);cursor:pointer;
    font-size:11px;padding:0;line-height:1;flex-shrink:0;opacity:.7;transition:opacity .15s;
}
.mquot-pill button:hover { opacity:1; }

/* ── QUOTATION RADIO CARDS ──────────────────────────────── */
.quot-radio-card {
    display:flex;align-items:center;gap:12px;
    padding:12px 16px;border:2px solid var(--card-border);
    border-radius:var(--radius-sm);cursor:pointer;
    background:var(--card-bg);transition:border-color .15s,background .15s;
}
.quot-radio-card:hover { border-color:#7C3AED;background:var(--page-bg); }
.quot-radio-card.selected { border-color:#7C3AED;background:#F5F3FF; }
.quot-radio-card.selected .quot-radio-num { background:#7C3AED;border-color:#7C3AED;color:#fff; }
.quot-radio-check {
    width:22px;height:22px;border-radius:50%;
    background:var(--page-bg);border:2px solid var(--card-border);
    display:flex;align-items:center;justify-content:center;
    font-size:10px;color:transparent;flex-shrink:0;transition:all .15s;
}
.quot-radio-card.selected .quot-radio-check { background:#7C3AED;border-color:#7C3AED;color:#fff; }

/* ── SIGNATURE PAD ──────────────────────────────────────── */
.sig-pad-wrap {
    position: relative; border: 1.5px solid var(--input-border);
    border-radius: var(--radius-sm); background: #fff;
    overflow: hidden; cursor: crosshair; touch-action: none;
    transition: border-color 0.18s;
}
.sig-pad-wrap:focus-within,
.sig-pad-wrap.active { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.sig-pad-canvas { display: block; width: 100%; height: 120px; }
.sig-pad-hint {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    font-size: 12px; color: #CBD5E1; pointer-events: none; user-select: none;
    font-style: italic; transition: opacity 0.2s;
}
.sig-pad-hint.hidden { opacity: 0; }

/* ── PROPERTY SEARCHABLE DROPDOWN ───────────────────────── */
.prop-dropdown { position:relative; }
.prop-dropdown-trigger {
    display:flex; align-items:center; justify-content:space-between;
    padding:9px 12px; font-size:13px; border:1.5px solid var(--input-border);
    border-radius:var(--radius-sm); background:var(--card-bg); color:var(--text-primary);
    cursor:pointer; transition:border-color 0.18s, box-shadow 0.18s; user-select:none;
    font-family:'Plus Jakarta Sans',sans-serif; min-height:38px;
}
.prop-dropdown.open .prop-dropdown-trigger,
.prop-dropdown-trigger:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); outline:none; }
.prop-dropdown.is-invalid .prop-dropdown-trigger { border-color:var(--danger); background:#FFF8F8; }
.prop-dropdown-arrow { font-size:10px; color:var(--text-muted); transition:transform 0.2s; flex-shrink:0; margin-left:8px; }
.prop-dropdown.open .prop-dropdown-arrow { transform:rotate(180deg); }
.prop-dropdown-panel {
    display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:200;
    background:var(--card-bg); border:1.5px solid var(--accent); border-radius:var(--radius-sm);
    box-shadow:0 8px 24px rgba(0,0,0,0.12); overflow:hidden;
}
.prop-dropdown.open .prop-dropdown-panel { display:block; }
.prop-dropdown-search-wrap { position:relative; padding:8px; border-bottom:1px solid var(--card-border); }
.prop-dropdown-search {
    width:100%; padding:7px 10px 7px 28px; font-size:12px; border:1.5px solid var(--input-border);
    border-radius:var(--radius-sm); background:var(--page-bg); color:var(--text-primary);
    font-family:'Plus Jakarta Sans',sans-serif; outline:none;
    transition:border-color 0.15s;
}
.prop-dropdown-search:focus { border-color:var(--accent); }
.prop-dropdown-options { max-height:200px; overflow-y:auto; }
.prop-dropdown-options::-webkit-scrollbar { width:4px; }
.prop-dropdown-options::-webkit-scrollbar-thumb { background:#CBD5E1; border-radius:10px; }
.prop-option {
    display:flex; align-items:center; justify-content:space-between;
    padding:9px 12px; cursor:pointer; transition:background 0.12s; gap:8px;
}
.prop-option:hover { background:var(--accent-dim); }
.prop-option.selected { background:var(--accent-dim); }
.prop-option-name { font-size:13px; font-weight:600; color:var(--text-primary); }
.prop-option-code { font-size:11px; font-weight:700; color:var(--text-muted); font-family:'Outfit',sans-serif; flex-shrink:0; }
.prop-option.hidden { display:none; }
.prop-no-results { padding:12px; font-size:12px; color:var(--text-muted); text-align:center; display:none; }

@media (max-width:600px) {
    .modal-box { max-height:100vh;border-radius:0;max-width:100%; }
    .modal-overlay { padding:0;align-items:flex-end; }
    .mfield-grid { grid-template-columns:1fr; }
    .mfield-grid .span-full,.mfield-grid .span-2 { grid-column:span 1; }
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Maintenance Requests</h1>
        <p class="page-header-sub">Track and manage all property maintenance work orders</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" onclick="openMaintenanceModal()">
            <i class="fa-solid fa-plus"></i> New Request
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

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
        <div class="maint-stat-icon orange"><i class="fa-solid fa-user-clock"></i></div>
        <div>
            <div class="maint-stat-val">{{ $stats['waiting_supervisor'] }}</div>
            <div class="maint-stat-lbl">Pending Assessment</div>
        </div>
    </div>
    <div class="maint-stat">
        <div class="maint-stat-icon purple"><i class="fa-solid fa-stamp"></i></div>
        <div>
            <div class="maint-stat-val">{{ $stats['waiting_approval'] }}</div>
            <div class="maint-stat-lbl">Pending Approval</div>
        </div>
    </div>
    <div class="maint-stat">
        <div class="maint-stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="maint-stat-val">{{ $stats['approved'] }}</div>
            <div class="maint-stat-lbl">Approved</div>
        </div>
    </div>
    <div class="maint-stat">
        <div class="maint-stat-icon blue"><i class="fa-solid fa-rotate"></i></div>
        <div>
            <div class="maint-stat-val">{{ $stats['in_progress'] }}</div>
            <div class="maint-stat-lbl">In Progress</div>
        </div>
    </div>
    <div class="maint-stat">
        <div class="maint-stat-icon teal"><i class="fa-solid fa-flag-checkered"></i></div>
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
        @foreach(['waiting_supervisor' => 'Pending Assessment','waiting_approval' => 'Pending Approval','approved' => 'Approved','in_progress' => 'In Progress','completed' => 'Completed','cancelled' => 'Cancelled'] as $val => $label)
        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
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
                <tr data-href="{{ route('maintenance.show', $req) }}"
                    data-status="{{ $req->status }}"
                    data-id="{{ $req->id }}"
                    data-job-order="{{ $req->job_order }}"
                    data-property="{{ $req->property }}"
                    data-tenant="{{ $req->tenant }}"
                    data-flat="{{ $req->flat }}"
                    data-assess-url="{{ route('maintenance.assess', $req) }}"
                    data-approve-url="{{ route('maintenance.approve', $req) }}"
                    data-q1="{{ $req->quotation_1 }}"
                    data-q2="{{ $req->quotation_2 }}"
                    data-q3="{{ $req->quotation_3 }}"
                    data-q1-file="{{ $req->quotation_1_file ? Storage::url($req->quotation_1_file) : '' }}"
                    data-q2-file="{{ $req->quotation_2_file ? Storage::url($req->quotation_2_file) : '' }}"
                    data-q3-file="{{ $req->quotation_3_file ? Storage::url($req->quotation_3_file) : '' }}"
                    data-q1-fname="{{ $req->quotation_1_file ? basename($req->quotation_1_file) : '' }}"
                    data-q2-fname="{{ $req->quotation_2_file ? basename($req->quotation_2_file) : '' }}"
                    data-q3-fname="{{ $req->quotation_3_file ? basename($req->quotation_3_file) : '' }}"
                    data-supervisor-name="{{ $req->supervisor_name }}"
                    data-supervisor-signature="{{ $req->supervisor_signature }}"
                    data-selected-quotation="{{ $req->selected_quotation }}"
                    style="cursor:pointer">
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
                        <div class="actions-cell" onclick="event.stopPropagation()">
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

{{-- ═══════════════════════════════════════════════════════
     NEW MAINTENANCE REQUEST MODAL
═══════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="maintenanceModal" role="dialog" aria-modal="true" aria-labelledby="maintenanceModalTitle">
    <div class="modal-box">

        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-solid fa-wrench"></i></div>
                <div>
                    <div class="modal-header-title" id="maintenanceModalTitle">New Maintenance Request</div>
                    <div class="modal-header-sub">Complete the form to log a new work order</div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeMaintenanceModal()" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-tabs">
                <button type="button" class="mtab-btn active" data-tab="mm-details" onclick="switchMMTab('mm-details')">
                    <i class="fa-solid fa-clipboard" style="color:#C2410C;font-size:11px;"></i> Details
                    <span class="err-dot" id="dot-mm-details"></span>
                </button>
                <button type="button" class="mtab-btn" data-tab="mm-joblines" onclick="switchMMTab('mm-joblines')">
                    <i class="fa-solid fa-list-check" style="color:#1D4ED8;font-size:11px;"></i> Job Lines
                    <span class="err-dot" id="dot-mm-joblines"></span>
                </button>
                <button type="button" class="mtab-btn" data-tab="mm-quotations" onclick="switchMMTab('mm-quotations')">
                    <i class="fa-solid fa-file-invoice-dollar" style="color:#059669;font-size:11px;"></i> Quotations
                    <span class="err-dot" id="dot-mm-quotations"></span>
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('maintenance.store') }}" id="maintenanceForm" novalidate enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="status" value="waiting_supervisor">

            {{-- TAB 1: DETAILS ──────────────────────────────── --}}
            <div class="mtab-panel active" id="mm-details">
                <div class="mfield-grid">

                    <div class="mfield-group">
                        <label class="mfield-label">Date <span class="req">*</span></label>
                        <input type="date" name="date"
                            class="mfield-input {{ $errors->has('date') ? 'is-invalid' : '' }}"
                            value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Job Order #</label>
                        <input type="text" name="job_order"
                            class="mfield-input {{ $errors->has('job_order') ? 'is-invalid' : '' }}"
                            value="{{ old('job_order') }}"
                            placeholder="Auto-generated if blank" maxlength="50">
                        @error('job_order') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Request Date</label>
                        <input type="date" name="request_date"
                            class="mfield-input {{ $errors->has('request_date') ? 'is-invalid' : '' }}"
                            value="{{ old('request_date', date('Y-m-d')) }}">
                        @error('request_date') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Apartment Status <span class="req">*</span></label>
                        <select name="apartment_status" required
                            class="mfield-select {{ $errors->has('apartment_status') ? 'is-invalid' : '' }}">
                            <option value="">— Select —</option>
                            @foreach(['occupied','vacant','furnished','other'] as $s)
                            <option value="{{ $s }}" {{ old('apartment_status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        @error('apartment_status') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group span-full">
                        <label class="mfield-label">Property <span class="req">*</span></label>
                        <input type="hidden" name="property" id="mm-property-val" value="{{ old('property') }}">
                        <div class="prop-dropdown {{ $errors->has('property') ? 'is-invalid' : '' }}" id="mm-prop-dropdown">
                            <div class="prop-dropdown-trigger" id="mm-prop-trigger" onclick="togglePropDropdown()">
                                <span id="mm-prop-label" style="color:{{ old('property') ? 'var(--text-primary)' : 'var(--text-muted)' }}">
                                    {{ old('property') ?: 'Search or select a property…' }}
                                </span>
                                <i class="fa-solid fa-chevron-down prop-dropdown-arrow" id="mm-prop-arrow"></i>
                            </div>
                            <div class="prop-dropdown-panel" id="mm-prop-panel">
                                <div class="prop-dropdown-search-wrap">
                                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--text-muted);pointer-events:none"></i>
                                    <input type="text" class="prop-dropdown-search" id="mm-prop-search"
                                           placeholder="Type to search…" oninput="filterPropOptions(this.value)" autocomplete="off">
                                </div>
                                <div class="prop-dropdown-options" id="mm-prop-options">
                                    @foreach($properties as $prop)
                                    <div class="prop-option {{ old('property') === $prop->property_name ? 'selected' : '' }}"
                                         data-value="{{ $prop->property_name }}"
                                         onclick="selectProp('{{ addslashes($prop->property_name) }}', '{{ $prop->property_code }}')">
                                        <span class="prop-option-name">{{ $prop->property_name }}</span>
                                        <span class="prop-option-code">{{ $prop->property_code }}</span>
                                    </div>
                                    @endforeach
                                    <div id="mm-prop-no-results" class="prop-no-results">No properties found</div>
                                </div>
                            </div>
                        </div>
                        @error('property') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Tenant <span class="req">*</span></label>
                        <input type="hidden" name="tenant" id="mm-tenant-val" value="{{ old('tenant') }}">
                        <div class="prop-dropdown {{ $errors->has('tenant') ? 'is-invalid' : '' }}" id="mm-tenant-dropdown">
                            <div class="prop-dropdown-trigger" onclick="toggleDropdown('mm-tenant-dropdown', 'mm-tenant-search')">
                                <span id="mm-tenant-label" style="color:{{ old('tenant') ? 'var(--text-primary)' : 'var(--text-muted)' }}">
                                    {{ old('tenant') ?: 'Search tenant…' }}
                                </span>
                                <i class="fa-solid fa-chevron-down prop-dropdown-arrow"></i>
                            </div>
                            <div class="prop-dropdown-panel">
                                <div class="prop-dropdown-search-wrap">
                                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--text-muted);pointer-events:none"></i>
                                    <input type="text" class="prop-dropdown-search" id="mm-tenant-search"
                                           placeholder="Type to search…" oninput="filterOptions('mm-tenant-options', 'mm-tenant-no-results', this.value)" autocomplete="off">
                                </div>
                                <div class="prop-dropdown-options" id="mm-tenant-options">
                                    @foreach($tenants as $tenant)
                                    <div class="prop-option {{ old('tenant') === $tenant->name ? 'selected' : '' }}"
                                         data-value="{{ $tenant->name }}"
                                         onclick="selectOption('mm-tenant-dropdown', 'mm-tenant-val', 'mm-tenant-label', '{{ addslashes($tenant->name) }}')">
                                        <span class="prop-option-name">{{ $tenant->name }}</span>
                                    </div>
                                    @endforeach
                                    <div id="mm-tenant-no-results" class="prop-no-results">No tenants found</div>
                                </div>
                            </div>
                        </div>
                        @error('tenant') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Flat / Unit <span class="req">*</span></label>
                        <input type="hidden" name="flat" id="mm-flat-val" value="{{ old('flat') }}">
                        <div class="prop-dropdown {{ $errors->has('flat') ? 'is-invalid' : '' }}" id="mm-flat-dropdown">
                            <div class="prop-dropdown-trigger" onclick="toggleDropdown('mm-flat-dropdown', 'mm-flat-search')">
                                <span id="mm-flat-label" style="color:{{ old('flat') ? 'var(--text-primary)' : 'var(--text-muted)' }}">
                                    {{ old('flat') ?: 'Select a unit…' }}
                                </span>
                                <i class="fa-solid fa-chevron-down prop-dropdown-arrow"></i>
                            </div>
                            <div class="prop-dropdown-panel">
                                <div class="prop-dropdown-search-wrap">
                                    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--text-muted);pointer-events:none"></i>
                                    <input type="text" class="prop-dropdown-search" id="mm-flat-search"
                                           placeholder="Type to search…" oninput="filterOptions('mm-flat-options', 'mm-flat-no-results', this.value)" autocomplete="off">
                                </div>
                                <div class="prop-dropdown-options" id="mm-flat-options">
                                    @foreach($units as $unit)
                                    <div class="prop-option {{ old('flat') === $unit->unit_name ? 'selected' : '' }}"
                                         data-value="{{ $unit->unit_name }}"
                                         data-property="{{ $unit->property_code }}"
                                         onclick="selectOption('mm-flat-dropdown', 'mm-flat-val', 'mm-flat-label', '{{ addslashes($unit->unit_name) }}')">
                                        <span class="prop-option-name">{{ $unit->unit_name }}</span>
                                        <span class="prop-option-code">{{ $unit->property_code }}</span>
                                    </div>
                                    @endforeach
                                    <div id="mm-flat-no-results" class="prop-no-results">No units found</div>
                                </div>
                            </div>
                        </div>
                        @error('flat') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Contact No. <span class="req">*</span></label>
                        <input type="text" name="contact_no"
                            class="mfield-input {{ $errors->has('contact_no') ? 'is-invalid' : '' }}"
                            value="{{ old('contact_no') }}"
                            placeholder="+973 XXXX XXXX" maxlength="30" required>
                        @error('contact_no') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group span-full">
                        <label class="mfield-label">Available Date &amp; Time <span class="req">*</span></label>
                        <input type="datetime-local" name="available_datetime"
                            class="mfield-input {{ $errors->has('available_datetime') ? 'is-invalid' : '' }}"
                            value="{{ old('available_datetime') }}" required>
                        @error('available_datetime') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                </div>
            </div>

            {{-- TAB 2: JOB LINES ────────────────────────────── --}}
            <div class="mtab-panel" id="mm-joblines">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                    <div style="font-size:12px;color:var(--text-muted)">Add one or more work items for this request.</div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addModalJobLine()">
                        <i class="fa-solid fa-plus"></i> Add Line
                    </button>
                </div>
                <div style="overflow-x:auto">
                    <table class="modal-job-lines-table">
                        <thead>
                            <tr>
                                <th style="width:27%">Location</th>
                                <th style="width:37%">Description of Work</th>
                                <th style="width:28%">Supervisor Comment</th>
                                <th style="width:8%"></th>
                            </tr>
                        </thead>
                        <tbody id="modalJobLinesBody">
                            @php $oldLines = old('job_lines', [['location'=>'','description'=>'','supervisor_comment'=>'']]); @endphp
                            @foreach($oldLines as $i => $line)
                            <tr class="modal-job-line-row">
                                <td><input type="text" name="job_lines[{{ $i }}][location]" value="{{ $line['location'] ?? '' }}" placeholder="e.g. Kitchen"></td>
                                <td><textarea name="job_lines[{{ $i }}][description]" placeholder="Describe the issue…">{{ $line['description'] ?? '' }}</textarea></td>
                                <td><textarea name="job_lines[{{ $i }}][supervisor_comment]" placeholder="Supervisor notes">{{ $line['supervisor_comment'] ?? '' }}</textarea></td>
                                <td style="text-align:center">
                                    <button type="button" class="modal-remove-line-btn" onclick="removeModalJobLine(this)" title="Remove">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 3: QUOTATIONS ────────────────────────────── --}}
            <div class="mtab-panel" id="mm-quotations">
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:14px;">Attach quotations from contractors. The supervisor will select one during assessment.</div>
                <div class="mfield-grid">
                    @foreach([1,2,3] as $n)
                    <div class="mfield-group {{ $n === 3 ? 'span-full' : '' }}">
                        <label class="mfield-label">Quotation {{ $n }} (BHD)</label>
                        <div class="mquot-wrap">
                            <input type="number" name="quotation_{{ $n }}" class="mfield-input mm-quot" step="0.001" min="0" placeholder="0.000" value="{{ old('quotation_'.$n) }}">
                            <label for="mm_file_{{ $n }}" class="mquot-clip-btn" title="Attach file">
                                <i class="fa-solid fa-paperclip"></i>
                            </label>
                            <input type="file" id="mm_file_{{ $n }}" name="quotation_{{ $n }}_file"
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                   class="mm-file-input" data-index="{{ $n }}" style="display:none">
                        </div>
                        <div class="mquot-pill" id="mm_pill_{{ $n }}">
                            <i class="fa-solid fa-paperclip" style="flex-shrink:0;font-size:10px"></i>
                            <span id="mm_fname_{{ $n }}"></span>
                            <button type="button" class="mm-pill-clear" data-index="{{ $n }}"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        @error('quotation_'.$n) <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endforeach
                </div>
            </div>

            </form>
        </div>

        <div class="modal-footer">
            <div class="modal-footer-nav">
                <button type="button" class="btn btn-outline btn-sm" id="mm-prev-btn" onclick="prevMMTab()" style="display:none;">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-outline btn-sm" id="mm-next-btn" onclick="nextMMTab()">
                    Next <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-outline" onclick="closeMaintenanceModal()">Cancel</button>
                <button type="submit" form="maintenanceForm" class="btn btn-primary" id="mmSubmitBtn" onclick="handleMMSubmit(this)">
                    <i class="fa-solid fa-paper-plane"></i> Send to Supervisor
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     SUPERVISOR ASSESSMENT MODAL
═══════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="assessModal" role="dialog" aria-modal="true">
    <div class="modal-box" style="max-width:680px">
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon" style="background:#FFF7ED;color:#EA580C"><i class="fa-solid fa-user-clock"></i></div>
                <div>
                    <div class="modal-header-title">Supervisor Assessment</div>
                    <div class="modal-header-sub" id="assessModalSub">Fill in the assessment and quotations</div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeAssessModal()" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="modal-body">
            {{-- read-only request summary --}}
            <div id="assessSummary" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:12px 16px;background:var(--page-bg);border-radius:var(--radius-sm);border:1px solid var(--card-border);margin-bottom:18px;font-size:12px">
                <div><div style="color:var(--text-muted);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px">Property</div><div id="as-property" style="font-weight:600;color:var(--text-primary)">—</div></div>
                <div><div style="color:var(--text-muted);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px">Tenant</div><div id="as-tenant" style="font-weight:600;color:var(--text-primary)">—</div></div>
                <div><div style="color:var(--text-muted);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px">Flat</div><div id="as-flat" style="font-weight:600;color:var(--text-primary)">—</div></div>
            </div>

            <form id="assessForm" method="POST" action="" novalidate>
                @csrf
                <div class="mfield-grid">
                    <div class="mfield-group">
                        <label class="mfield-label">Supervisor Name <span class="req">*</span></label>
                        <input type="text" name="supervisor_name" id="af-supervisor-name" class="mfield-input" placeholder="Full name" maxlength="255" required>
                    </div>
                    <div class="mfield-group">
                        <label class="mfield-label">Assessment Date &amp; Time <span class="req">*</span></label>
                        <input type="datetime-local" name="supervisor_datetime" id="af-supervisor-datetime" class="mfield-input" required>
                    </div>
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Job Assessment</label>
                        <textarea name="job_assessment" rows="3" class="mfield-textarea" placeholder="Assessment notes and findings…"></textarea>
                    </div>
                    {{-- Quotations submitted by requestor — supervisor selects one --}}
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Select Quotation <span class="req">*</span></label>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px" id="assessQuotCards">
                            @foreach([1,2,3] as $n)
                            <label class="quot-radio-card" id="aq-card-{{ $n }}" style="display:none">
                                <input type="radio" name="selected_quotation" value="{{ $n }}" style="display:none" class="aq-radio-input">
                                <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0">
                                    <div style="width:28px;height:28px;border-radius:50%;background:var(--page-bg);border:2px solid var(--card-border);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--text-muted);flex-shrink:0" class="quot-radio-num">{{ $n }}</div>
                                    <div style="flex:1;min-width:0">
                                        <div style="font-size:15px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--text-primary)" id="aq-amount-{{ $n }}">—</div>
                                        <div id="aq-file-{{ $n }}" style="font-size:11px;margin-top:2px"></div>
                                    </div>
                                </div>
                                <div class="quot-radio-check"><i class="fa-solid fa-check"></i></div>
                            </label>
                            @endforeach
                            <div id="aq-no-quotations" style="display:none;font-size:12px;color:var(--text-muted);padding:12px 14px;background:var(--page-bg);border-radius:var(--radius-sm);border:1px dashed var(--card-border);text-align:center">
                                No quotations were attached to this request.
                            </div>
                        </div>
                    </div>
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Supervisor Signature <span class="req">*</span></label>
                        <input type="hidden" name="supervisor_signature" id="af-signature-data">
                        <div class="sig-pad-wrap" id="af-sig-wrap">
                            <canvas id="af-sig-canvas" class="sig-pad-canvas"></canvas>
                            <div class="sig-pad-hint" id="af-sig-hint">Sign here with mouse or touch</div>
                        </div>
                        <div style="display:flex;justify-content:flex-end;margin-top:6px;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="clearSignature()">
                                <i class="fa-solid fa-rotate-left"></i> Clear
                            </button>
                        </div>
                    </div>
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Maintenance Remarks</label>
                        <textarea name="maintenance_remarks" rows="2" class="mfield-textarea" placeholder="Additional remarks…"></textarea>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <div></div>
            <div style="display:flex;gap:8px">
                <button type="button" class="btn btn-outline" onclick="closeAssessModal()">Cancel</button>
                <button type="submit" form="assessForm" class="btn btn-primary" onclick="handleAssessSubmit(this)" style="background:#EA580C;border-color:#EA580C">
                    <i class="fa-solid fa-arrow-right"></i> Submit Assessment
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     DEPARTMENT HEAD APPROVAL MODAL
═══════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="approveModal" role="dialog" aria-modal="true">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon" style="background:#F5F3FF;color:#7C3AED"><i class="fa-solid fa-stamp"></i></div>
                <div>
                    <div class="modal-header-title">Department Approval</div>
                    <div class="modal-header-sub" id="approveModalSub">Review and approve the supervisor's selection</div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeApproveModal()" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form id="approveForm" method="POST" action="" novalidate>
                @csrf
                {{-- All quotations, with the supervisor's selection highlighted --}}
                <div style="margin-bottom:18px">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:10px">Quotations <span style="text-transform:none;font-weight:600;color:var(--text-muted)">— supervisor's pick is highlighted</span></div>
                    <div style="display:flex;flex-direction:column;gap:8px" id="aprQuotCards">
                        @foreach([1,2,3] as $n)
                        <div class="quot-radio-card" id="apr-card-{{ $n }}" style="display:none;cursor:default">
                            <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0">
                                <div style="width:28px;height:28px;border-radius:50%;background:var(--page-bg);border:2px solid var(--card-border);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--text-muted);flex-shrink:0" class="quot-radio-num">{{ $n }}</div>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:15px;font-weight:800;font-family:'Outfit',sans-serif;color:var(--text-primary)" id="apr-amount-{{ $n }}">—</div>
                                    <div id="apr-file-{{ $n }}" style="font-size:11px;margin-top:2px"></div>
                                </div>
                            </div>
                            <div id="apr-badge-{{ $n }}" style="font-size:10px;font-weight:700;color:#7C3AED;text-transform:uppercase;letter-spacing:.04em;display:none">Selected</div>
                            <div class="quot-radio-check"><i class="fa-solid fa-check"></i></div>
                        </div>
                        @endforeach
                        <div id="apr-no-quotations" style="display:none;font-size:12px;color:var(--text-muted);padding:12px 14px;background:var(--page-bg);border-radius:var(--radius-sm);border:1px dashed var(--card-border);text-align:center">
                            No quotations were attached to this request.
                        </div>
                    </div>
                </div>
                <div class="mfield-grid">
                    <div class="mfield-group">
                        <label class="mfield-label">Supervisor</label>
                        <input type="text" name="approved_supervisor" id="apr-supervisor-name" class="mfield-input"
                               readonly style="background:var(--page-bg);color:var(--text-secondary);cursor:default">
                    </div>
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Supervisor's Signature</label>
                        <div id="apr-super-sig-wrap" style="border:1.5px solid var(--input-border);border-radius:var(--radius-sm);background:#fff;padding:8px;display:none">
                            <img id="apr-super-sig-img" src="" alt="Supervisor Signature" style="max-height:80px;display:block">
                        </div>
                        <div id="apr-super-sig-none" style="font-size:12px;color:var(--text-muted);padding:10px 12px;background:var(--page-bg);border-radius:var(--radius-sm);border:1px dashed var(--card-border)">No supervisor signature on file.</div>
                    </div>
                    <div class="mfield-group">
                        <label class="mfield-label">Approved by Dept. Head</label>
                        <input type="text" name="approved_dept_head" id="apr-dept-head" class="mfield-input" placeholder="Dept. head name" maxlength="255">
                    </div>
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Dept. Head Signature <span class="req">*</span></label>
                        <input type="hidden" name="dept_head_signature" id="apr-signature-data">
                        <div class="sig-pad-wrap" id="apr-sig-wrap">
                            <canvas id="apr-sig-canvas" class="sig-pad-canvas"></canvas>
                            <div class="sig-pad-hint" id="apr-sig-hint">Sign here with mouse or touch</div>
                        </div>
                        <div style="display:flex;justify-content:flex-end;margin-top:6px;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="clearAprSignature()">
                                <i class="fa-solid fa-rotate-left"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <div></div>
            <div style="display:flex;gap:8px">
                <button type="button" class="btn btn-outline" onclick="closeApproveModal()">Cancel</button>
                <button type="submit" form="approveForm" class="btn btn-primary" onclick="handleApproveSubmit(this)" style="background:#7C3AED;border-color:#7C3AED">
                    <i class="fa-solid fa-stamp"></i> Approve
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── MODAL OPEN/CLOSE ─────────────────────────────────────────
function openMaintenanceModal() {
    document.getElementById('maintenanceModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        const first = document.querySelector('#mm-details input[name="date"]');
        if (first) first.focus();
    }, 320);
}
function closeMaintenanceModal() {
    document.getElementById('maintenanceModal').classList.remove('open');
    document.body.style.overflow = '';
    document.querySelectorAll('.mm-pill-clear').forEach(b => b.click());
    resetDropdown('mm-prop-dropdown',    'mm-property-val', 'mm-prop-label',   'Search or select a property…');
    resetDropdown('mm-tenant-dropdown',  'mm-tenant-val',   'mm-tenant-label', 'Search tenant…');
    resetDropdown('mm-flat-dropdown',    'mm-flat-val',     'mm-flat-label',   'Select a unit…');
    // un-hide all flat options (were filtered by property)
    document.querySelectorAll('#mm-flat-options .prop-option').forEach(o => o.classList.remove('hidden'));
}
document.getElementById('maintenanceModal').addEventListener('click', function(e) {
    if (e.target === this) closeMaintenanceModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('maintenanceModal').classList.contains('open')) {
        closeMaintenanceModal();
    }
});

// ── ROW CLICK — STATUS-AWARE ─────────────────────────────────
document.addEventListener('click', function(e) {
    const tr = e.target.closest('tr[data-status]');
    if (!tr) return;
    if (e.target.closest('[onclick]') || e.target.closest('form') || e.target.closest('a')) return;
    e.stopImmediatePropagation();
    const s = tr.dataset.status;
    if (s === 'waiting_supervisor') openAssessModal(tr.dataset);
    else if (s === 'waiting_approval') openApproveModal(tr.dataset);
    else window.location.href = tr.dataset.href;
}, true); // capture phase — fires before the global layout handler

// ── ASSESS MODAL ─────────────────────────────────────────────
function openAssessModal(d) {
    document.getElementById('assessModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    document.getElementById('assessModalSub').textContent = d.jobOrder + ' · ' + d.property;
    document.getElementById('as-property').textContent = d.property;
    document.getElementById('as-tenant').textContent = d.tenant;
    document.getElementById('as-flat').textContent = d.flat;
    document.getElementById('assessForm').action = d.assessUrl;
    document.getElementById('af-supervisor-datetime').value = new Date().toISOString().slice(0,16);

    // populate read-only quotation cards
    let visibleCount = 0;
    [1,2,3].forEach(n => {
        const amt   = d['q'+n];
        const fname = d['q'+n+'Fname'];
        const furl  = d['q'+n+'File'];
        const card  = document.getElementById('aq-card-'+n);
        const hasAmt = amt !== '' && amt !== null && amt !== undefined;
        card.style.display = hasAmt ? '' : 'none';
        card.classList.remove('selected');
        card.querySelector('.aq-radio-input').checked = false;
        if (hasAmt) {
            visibleCount++;
            document.getElementById('aq-amount-'+n).textContent = 'BHD ' + parseFloat(amt).toFixed(3);
            const fileEl = document.getElementById('aq-file-'+n);
            if (fname && furl) {
                fileEl.innerHTML = `<a href="${furl}" target="_blank" style="color:var(--accent);text-decoration:none;font-weight:600"><i class="fa-solid fa-paperclip" style="font-size:9px"></i> ${fname}</a>`;
            } else { fileEl.textContent = ''; }
        }
    });
    document.getElementById('aq-no-quotations').style.display = visibleCount === 0 ? '' : 'none';
}
function closeAssessModal() {
    document.getElementById('assessModal').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('assessForm').reset();
    document.querySelectorAll('.aq-radio-input').forEach(r => r.checked = false);
    document.querySelectorAll('#assessQuotCards .quot-radio-card').forEach(c => c.classList.remove('selected'));
    if (window.clearSignature) clearSignature();
}
document.getElementById('assessModal').addEventListener('click', function(e) {
    if (e.target === this) closeAssessModal();
});
document.querySelectorAll('.aq-radio-input').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('#assessQuotCards .quot-radio-card').forEach(c => c.classList.remove('selected'));
        this.closest('.quot-radio-card').classList.add('selected');
    });
});
function handleAssessSubmit(btn) {
    const selected = document.querySelector('.aq-radio-input:checked');
    if (!selected) { alert('Please select one of the quotations.'); return; }
    if (!sigHasContent()) { alert('Please provide your signature before submitting.'); return; }
    document.getElementById('af-signature-data').value = document.getElementById('af-sig-canvas').toDataURL('image/png');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
    document.getElementById('assessForm').submit();
}

// ── SIGNATURE PAD ─────────────────────────────────────────────
(function() {
    const canvas  = document.getElementById('af-sig-canvas');
    const hint    = document.getElementById('af-sig-hint');
    const wrap    = document.getElementById('af-sig-wrap');
    const ctx     = canvas.getContext('2d');
    let drawing   = false;
    let hasMark   = false;

    function resize() {
        const dpr  = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const w    = rect.width  || wrap.offsetWidth;
        const h    = rect.height || 120;
        // save existing drawing
        const tmp  = canvas.toDataURL();
        canvas.width  = w * dpr;
        canvas.height = h * dpr;
        ctx.scale(dpr, dpr);
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth   = 2;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
        if (hasMark) {
            const img = new Image();
            img.onload = () => ctx.drawImage(img, 0, 0, w, h);
            img.src = tmp;
        }
    }

    function pos(e) {
        const r = canvas.getBoundingClientRect();
        if (e.touches) {
            return { x: e.touches[0].clientX - r.left, y: e.touches[0].clientY - r.top };
        }
        return { x: e.clientX - r.left, y: e.clientY - r.top };
    }

    function start(e) {
        e.preventDefault();
        drawing = true;
        wrap.classList.add('active');
        ctx.beginPath();
        const p = pos(e);
        ctx.moveTo(p.x, p.y);
    }

    function move(e) {
        if (!drawing) return;
        e.preventDefault();
        const p = pos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        if (!hasMark) {
            hasMark = true;
            hint.classList.add('hidden');
        }
    }

    function stop(e) {
        drawing = false;
        wrap.classList.remove('active');
    }

    canvas.addEventListener('mousedown',  start);
    canvas.addEventListener('mousemove',  move);
    canvas.addEventListener('mouseup',    stop);
    canvas.addEventListener('mouseleave', stop);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove',  move,  { passive: false });
    canvas.addEventListener('touchend',   stop);

    window.sigHasContent = () => hasMark;
    window.clearSignature = function() {
        const dpr  = window.devicePixelRatio || 1;
        ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
        hasMark = false;
        hint.classList.remove('hidden');
        document.getElementById('af-signature-data').value = '';
    };

    // init after modal first opens so canvas has layout dimensions
    setTimeout(resize, 50);
    window.addEventListener('resize', resize);
})();

// ── APPROVE MODAL ─────────────────────────────────────────────
function openApproveModal(d) {
    document.getElementById('approveModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    document.getElementById('approveModalSub').textContent = d.jobOrder + ' · ' + d.property;
    document.getElementById('approveForm').action = d.approveUrl;
    document.getElementById('apr-supervisor-name').value = d.supervisorName || '—';

    // show all attached quotations, highlighting the supervisor's selection
    const selected = d.selectedQuotation;
    let visibleCount = 0;
    [1,2,3].forEach(n => {
        const amt   = d['q'+n];
        const fname = d['q'+n+'Fname'];
        const furl  = d['q'+n+'File'];
        const card  = document.getElementById('apr-card-'+n);
        const hasAmt = amt !== '' && amt !== null && amt !== undefined;
        card.style.display = hasAmt ? '' : 'none';
        const isSelected = hasAmt && selected && String(selected) === String(n);
        card.classList.toggle('selected', isSelected);
        document.getElementById('apr-badge-'+n).style.display = isSelected ? '' : 'none';
        if (hasAmt) {
            visibleCount++;
            document.getElementById('apr-amount-'+n).textContent = 'BHD ' + parseFloat(amt).toFixed(3);
            const fileEl = document.getElementById('apr-file-'+n);
            if (fname && furl) {
                fileEl.innerHTML = `<a href="${furl}" target="_blank" style="color:var(--accent);text-decoration:none;font-weight:600"><i class="fa-solid fa-paperclip" style="font-size:9px"></i> ${fname}</a>`;
            } else { fileEl.textContent = ''; }
        }
    });
    document.getElementById('apr-no-quotations').style.display = visibleCount === 0 ? '' : 'none';

    // show supervisor's signature
    const sig = d.supervisorSignature;
    if (sig) {
        document.getElementById('apr-super-sig-img').src = sig;
        document.getElementById('apr-super-sig-wrap').style.display = '';
        document.getElementById('apr-super-sig-none').style.display = 'none';
    } else {
        document.getElementById('apr-super-sig-wrap').style.display = 'none';
        document.getElementById('apr-super-sig-none').style.display = '';
    }
}
function closeApproveModal() {
    document.getElementById('approveModal').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('approveForm').reset();
    if (window.clearAprSignature) clearAprSignature();
}
document.getElementById('approveModal').addEventListener('click', function(e) {
    if (e.target === this) closeApproveModal();
});
function handleApproveSubmit(btn) {
    if (!window.aprSigHasContent || !window.aprSigHasContent()) {
        alert('Please provide your signature before approving.');
        return;
    }
    document.getElementById('apr-signature-data').value = document.getElementById('apr-sig-canvas').toDataURL('image/png');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Approving…';
    document.getElementById('approveForm').submit();
}

// ── NEW REQUEST FORM FILE INPUTS ──────────────────────────────
document.querySelectorAll('.mm-file-input').forEach(input => {
    const n    = input.dataset.index;
    const clip = input.previousElementSibling;
    const pill = document.getElementById('mm_pill_' + n);
    const name = document.getElementById('mm_fname_' + n);
    input.addEventListener('change', () => {
        if (input.files.length) {
            const f = input.files[0];
            const size = f.size < 1048576 ? (f.size/1024).toFixed(1)+' KB' : (f.size/1048576).toFixed(1)+' MB';
            name.textContent = f.name + ' (' + size + ')';
            pill.classList.add('show');
            clip.classList.add('has-file');
        }
    });
});
document.querySelectorAll('.mm-pill-clear').forEach(btn => {
    btn.addEventListener('click', () => {
        const n = btn.dataset.index;
        const input = document.getElementById('mm_file_' + n);
        const pill  = document.getElementById('mm_pill_' + n);
        const clip  = input.previousElementSibling;
        input.value = '';
        pill.classList.remove('show');
        clip.classList.remove('has-file');
    });
});

// ── DEPT HEAD SIGNATURE PAD ───────────────────────────────────
(function() {
    const canvas = document.getElementById('apr-sig-canvas');
    const hint   = document.getElementById('apr-sig-hint');
    const wrap   = document.getElementById('apr-sig-wrap');
    const ctx    = canvas.getContext('2d');
    let drawing  = false;
    let hasMark  = false;

    function resize() {
        const dpr  = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const w    = rect.width  || wrap.offsetWidth;
        const h    = rect.height || 120;
        const tmp  = canvas.toDataURL();
        canvas.width  = w * dpr;
        canvas.height = h * dpr;
        ctx.scale(dpr, dpr);
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth   = 2;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
        if (hasMark) {
            const img = new Image();
            img.onload = () => ctx.drawImage(img, 0, 0, w, h);
            img.src = tmp;
        }
    }

    function pos(e) {
        const r = canvas.getBoundingClientRect();
        if (e.touches) return { x: e.touches[0].clientX - r.left, y: e.touches[0].clientY - r.top };
        return { x: e.clientX - r.left, y: e.clientY - r.top };
    }

    canvas.addEventListener('mousedown',  e => { e.preventDefault(); drawing = true; wrap.classList.add('active'); ctx.beginPath(); const p = pos(e); ctx.moveTo(p.x, p.y); });
    canvas.addEventListener('mousemove',  e => { if (!drawing) return; e.preventDefault(); const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); if (!hasMark) { hasMark = true; hint.classList.add('hidden'); } });
    canvas.addEventListener('mouseup',    () => { drawing = false; wrap.classList.remove('active'); });
    canvas.addEventListener('mouseleave', () => { drawing = false; wrap.classList.remove('active'); });
    canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; wrap.classList.add('active'); ctx.beginPath(); const p = pos(e); ctx.moveTo(p.x, p.y); }, { passive: false });
    canvas.addEventListener('touchmove',  e => { if (!drawing) return; e.preventDefault(); const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); if (!hasMark) { hasMark = true; hint.classList.add('hidden'); } }, { passive: false });
    canvas.addEventListener('touchend',   () => { drawing = false; wrap.classList.remove('active'); });

    window.aprSigHasContent = () => hasMark;
    window.clearAprSignature = function() {
        const dpr = window.devicePixelRatio || 1;
        ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
        hasMark = false;
        hint.classList.remove('hidden');
        document.getElementById('apr-signature-data').value = '';
    };

    setTimeout(resize, 50);
    window.addEventListener('resize', resize);
})();

// ── SHARED DROPDOWN HELPERS ───────────────────────────────────
function toggleDropdown(ddId, searchId) {
    const dd = document.getElementById(ddId);
    const isOpen = dd.classList.toggle('open');
    if (isOpen) setTimeout(() => document.getElementById(searchId).focus(), 50);
}
function selectOption(ddId, hiddenId, labelId, value) {
    document.getElementById(hiddenId).value = value;
    const label = document.getElementById(labelId);
    label.textContent = value;
    label.style.color = 'var(--text-primary)';
    document.querySelectorAll('#' + ddId + ' .prop-option').forEach(o => o.classList.toggle('selected', o.dataset.value === value));
    document.getElementById(ddId).classList.remove('open');
}
function filterOptions(optionsId, noResId, query) {
    const q = query.toLowerCase();
    let visible = 0;
    document.querySelectorAll('#' + optionsId + ' .prop-option').forEach(opt => {
        const match = opt.textContent.toLowerCase().includes(q);
        opt.classList.toggle('hidden', !match);
        if (match) visible++;
    });
    const noRes = document.getElementById(noResId);
    if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
}
function resetDropdown(ddId, hiddenId, labelId, placeholder) {
    document.getElementById(hiddenId).value = '';
    const label = document.getElementById(labelId);
    label.textContent = placeholder;
    label.style.color = 'var(--text-muted)';
    document.querySelectorAll('#' + ddId + ' .prop-option').forEach(o => o.classList.remove('selected', 'hidden'));
    const searchInput = document.querySelector('#' + ddId + ' .prop-dropdown-search');
    if (searchInput) searchInput.value = '';
    document.getElementById(ddId).classList.remove('open');
}
// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    ['mm-prop-dropdown','mm-tenant-dropdown','mm-flat-dropdown'].forEach(id => {
        const dd = document.getElementById(id);
        if (dd && !dd.contains(e.target)) dd.classList.remove('open');
    });
});

// ── PROPERTY DROPDOWN ─────────────────────────────────────────
function togglePropDropdown() { toggleDropdown('mm-prop-dropdown', 'mm-prop-search'); }
function selectProp(name, code) {
    selectOption('mm-prop-dropdown', 'mm-property-val', 'mm-prop-label', name);
    // filter units to this property, reset flat selection
    resetDropdown('mm-flat-dropdown', 'mm-flat-val', 'mm-flat-label', 'Select a unit…');
    document.querySelectorAll('#mm-flat-options .prop-option').forEach(opt => {
        opt.classList.toggle('hidden', opt.dataset.property !== code);
    });
    const noRes = document.getElementById('mm-flat-no-results');
    const visible = document.querySelectorAll('#mm-flat-options .prop-option:not(.hidden)').length;
    if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
}
function filterPropOptions(v) { filterOptions('mm-prop-options', 'mm-prop-no-results', v); }

// ── TABS ─────────────────────────────────────────────────────
const MM_TABS = ['mm-details','mm-joblines','mm-quotations'];
let mmCurrentTab = 0;

function switchMMTab(tabId) {
    mmCurrentTab = MM_TABS.indexOf(tabId);
    MM_TABS.forEach((id, i) => {
        const panel = document.getElementById(id);
        const btn   = document.querySelector(`[data-tab="${id}"]`);
        if (i === mmCurrentTab) {
            panel.classList.add('active');
            btn.classList.add('active');
        } else {
            panel.classList.remove('active');
            btn.classList.remove('active');
        }
    });
    document.getElementById('mm-prev-btn').style.display = mmCurrentTab === 0 ? 'none' : '';
    document.getElementById('mm-next-btn').style.display = mmCurrentTab === MM_TABS.length - 1 ? 'none' : '';
}

function nextMMTab() { if (mmCurrentTab < MM_TABS.length - 1) switchMMTab(MM_TABS[mmCurrentTab + 1]); }
function prevMMTab() { if (mmCurrentTab > 0) switchMMTab(MM_TABS[mmCurrentTab - 1]); }

// ── JOB LINES ────────────────────────────────────────────────
let mmLineIndex = {{ count(old('job_lines', [['location'=>'','description'=>'','supervisor_comment'=>'']])) }};

function addModalJobLine() {
    const i = mmLineIndex++;
    const row = document.createElement('tr');
    row.className = 'modal-job-line-row';
    row.innerHTML = `
        <td><input type="text" name="job_lines[${i}][location]" placeholder="e.g. Kitchen"></td>
        <td><textarea name="job_lines[${i}][description]" placeholder="Describe the issue…"></textarea></td>
        <td><textarea name="job_lines[${i}][supervisor_comment]" placeholder="Supervisor notes"></textarea></td>
        <td style="text-align:center">
            <button type="button" class="modal-remove-line-btn" onclick="removeModalJobLine(this)" title="Remove">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('modalJobLinesBody').appendChild(row);
    row.querySelector('input').focus();
}


function removeModalJobLine(btn) {
    const row = btn.closest('tr');
    const tbody = document.getElementById('modalJobLinesBody');
    if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
    } else {
        row.querySelectorAll('input, textarea').forEach(el => el.value = '');
    }
}

// ── SUBMIT ───────────────────────────────────────────────────
function handleMMSubmit(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    document.getElementById('maintenanceForm').submit();
}

// ── ERROR DOTS ───────────────────────────────────────────────
@php
    $mmTabErrorMap = [
        'mm-details'     => ['date','job_order','request_date','apartment_status','property','tenant','flat','contact_no','available_datetime'],
        'mm-joblines'    => ['job_lines'],
        'mm-quotations'  => ['quotation_1','quotation_2','quotation_3','quotation_1_file','quotation_2_file','quotation_3_file'],
    ];
    $mmFirstErrorTab = null;
    foreach($mmTabErrorMap as $tab => $fields) {
        foreach($fields as $f) {
            if($errors->has($f) || $errors->has($f.'.*') || $errors->hasAny(array_map(fn($x) => "{$f}.{$x}.location", range(0,10)))) {
                if(!$mmFirstErrorTab) $mmFirstErrorTab = $tab;
                break;
            }
        }
    }
@endphp

@foreach($mmTabErrorMap as $tab => $fields)
    @php $hasErr = collect($fields)->some(fn($f) => $errors->has($f) || $errors->has($f.'.*')); @endphp
    @if($hasErr)
        document.getElementById('dot-{{ $tab }}').classList.add('show');
    @endif
@endforeach

@if($errors->any())
    openMaintenanceModal();
    @if($mmFirstErrorTab)
        switchMMTab('{{ $mmFirstErrorTab }}');
    @endif
@endif
</script>
@endpush
