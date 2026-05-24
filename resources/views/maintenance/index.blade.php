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
.maint-stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
.maint-stat-icon.green  { background: #ECFDF5; color: #059669; }
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
.status-badge.open        { background: #EFF6FF; color: #2563EB; }
.status-badge.in_progress { background: var(--accent-dim); color: var(--accent); }
.status-badge.completed   { background: #ECFDF5; color: #059669; }
.status-badge.cancelled   { background: #FEF2F2; color: #DC2626; }

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
                <tr data-href="{{ route('maintenance.show', $req) }}" style="cursor:pointer">
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
                <button type="button" class="mtab-btn" data-tab="mm-maintenance" onclick="switchMMTab('mm-maintenance')">
                    <i class="fa-solid fa-screwdriver-wrench" style="color:#6D28D9;font-size:11px;"></i> Maintenance
                    <span class="err-dot" id="dot-mm-maintenance"></span>
                </button>
                <button type="button" class="mtab-btn" data-tab="mm-approval" onclick="switchMMTab('mm-approval')">
                    <i class="fa-solid fa-signature" style="color:#15803D;font-size:11px;"></i> Approval
                    <span class="err-dot" id="dot-mm-approval"></span>
                </button>
            </div>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('maintenance.store') }}" id="maintenanceForm" novalidate>
            @csrf
            <input type="hidden" name="status" value="open">

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
                        <input type="text" name="property"
                            class="mfield-input {{ $errors->has('property') ? 'is-invalid' : '' }}"
                            value="{{ old('property') }}"
                            placeholder="Property name or address" maxlength="255" required>
                        @error('property') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Tenant <span class="req">*</span></label>
                        <input type="text" name="tenant"
                            class="mfield-input {{ $errors->has('tenant') ? 'is-invalid' : '' }}"
                            value="{{ old('tenant') }}"
                            placeholder="Tenant full name" maxlength="255" required>
                        @error('tenant') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mfield-group">
                        <label class="mfield-label">Flat / Unit <span class="req">*</span></label>
                        <input type="text" name="flat"
                            class="mfield-input {{ $errors->has('flat') ? 'is-invalid' : '' }}"
                            value="{{ old('flat') }}"
                            placeholder="e.g. 3B" maxlength="50" required>
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

            {{-- TAB 3: MAINTENANCE ──────────────────────────── --}}
            <div class="mtab-panel" id="mm-maintenance">

                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--card-border)">
                    Supervisor
                </div>
                <div class="mfield-grid" style="margin-bottom:20px">
                    <div class="mfield-group">
                        <label class="mfield-label">Supervisor Name</label>
                        <input type="text" name="supervisor_name"
                            class="mfield-input {{ $errors->has('supervisor_name') ? 'is-invalid' : '' }}"
                            value="{{ old('supervisor_name') }}" placeholder="Full name" maxlength="255">
                        @error('supervisor_name') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    <div class="mfield-group">
                        <label class="mfield-label">Supervisor Date &amp; Time</label>
                        <input type="datetime-local" name="supervisor_datetime"
                            class="mfield-input {{ $errors->has('supervisor_datetime') ? 'is-invalid' : '' }}"
                            value="{{ old('supervisor_datetime') }}">
                        @error('supervisor_datetime') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                </div>

                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--card-border)">
                    Maintenance Use Only
                </div>
                <div class="mfield-grid">
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Job Assessment</label>
                        <textarea name="job_assessment" rows="3"
                            class="mfield-textarea {{ $errors->has('job_assessment') ? 'is-invalid' : '' }}"
                            placeholder="Assessment notes and findings…">{{ old('job_assessment') }}</textarea>
                        @error('job_assessment') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    <div class="mfield-group">
                        <label class="mfield-label">Quotation 1 (BHD)</label>
                        <input type="number" name="quotation_1"
                            class="mfield-input {{ $errors->has('quotation_1') ? 'is-invalid' : '' }}"
                            value="{{ old('quotation_1') }}" step="0.001" min="0" placeholder="0.000">
                        @error('quotation_1') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    <div class="mfield-group">
                        <label class="mfield-label">Quotation 2 (BHD)</label>
                        <input type="number" name="quotation_2"
                            class="mfield-input {{ $errors->has('quotation_2') ? 'is-invalid' : '' }}"
                            value="{{ old('quotation_2') }}" step="0.001" min="0" placeholder="0.000">
                        @error('quotation_2') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Quotation 3 (BHD)</label>
                        <input type="number" name="quotation_3"
                            class="mfield-input {{ $errors->has('quotation_3') ? 'is-invalid' : '' }}"
                            value="{{ old('quotation_3') }}" step="0.001" min="0" placeholder="0.000">
                        @error('quotation_3') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    <div class="mfield-group span-full">
                        <label class="mfield-label">Maintenance Remarks</label>
                        <textarea name="maintenance_remarks" rows="3"
                            class="mfield-textarea {{ $errors->has('maintenance_remarks') ? 'is-invalid' : '' }}"
                            placeholder="Additional remarks…">{{ old('maintenance_remarks') }}</textarea>
                        @error('maintenance_remarks') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                </div>

            </div>

            {{-- TAB 4: APPROVAL ─────────────────────────────── --}}
            <div class="mtab-panel" id="mm-approval">
                <div class="mfield-grid">
                    <div class="mfield-group">
                        <label class="mfield-label">Approved by Supervisor</label>
                        <input type="text" name="approved_supervisor"
                            class="mfield-input {{ $errors->has('approved_supervisor') ? 'is-invalid' : '' }}"
                            value="{{ old('approved_supervisor') }}"
                            placeholder="Supervisor name / signature" maxlength="255">
                        @error('approved_supervisor') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    <div class="mfield-group">
                        <label class="mfield-label">Approved by Dept. Head</label>
                        <input type="text" name="approved_dept_head"
                            class="mfield-input {{ $errors->has('approved_dept_head') ? 'is-invalid' : '' }}"
                            value="{{ old('approved_dept_head') }}"
                            placeholder="Dept. head name / signature" maxlength="255">
                        @error('approved_dept_head') <div class="mfield-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
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
                    <i class="fa-solid fa-paper-plane"></i> Submit Request
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
}
document.getElementById('maintenanceModal').addEventListener('click', function(e) {
    if (e.target === this) closeMaintenanceModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('maintenanceModal').classList.contains('open')) {
        closeMaintenanceModal();
    }
});

// ── TABS ─────────────────────────────────────────────────────
const MM_TABS = ['mm-details','mm-joblines','mm-maintenance','mm-approval'];
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
        'mm-maintenance' => ['supervisor_name','supervisor_datetime','job_assessment','quotation_1','quotation_2','quotation_3','maintenance_remarks'],
        'mm-approval'    => ['approved_supervisor','approved_dept_head'],
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
