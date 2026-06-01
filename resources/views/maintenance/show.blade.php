@extends('layouts.admin')

@section('title', 'Maintenance Request — ' . ($record->job_order ?? ''))
@section('topbar-title', 'Maintenance Request')

@push('styles')
<style>
.maint-section {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); overflow: hidden; margin-bottom: 20px;
}
.maint-section-header {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 20px; border-bottom: 1px solid var(--card-border);
    border-left: 3px solid var(--accent);
    background: linear-gradient(90deg, var(--accent-dim) 0%, transparent 60%);
}
.maint-section-icon {
    width: 34px; height: 34px; border-radius: var(--radius-sm);
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;
}
.maint-section-title { font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 800; color: var(--text-primary); }
.maint-section-body  { padding: 20px; }

.detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.detail-item {}
.detail-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; }
.detail-value { font-size: 14px; color: var(--text-primary); font-weight: 500; }
.detail-value.mono { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 15px; }

.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
}
.status-badge.open        { background: #EFF6FF; color: #2563EB; }
.status-badge.in_progress { background: var(--accent-dim); color: var(--accent); }
.status-badge.completed   { background: #ECFDF5; color: #059669; }
.status-badge.cancelled   { background: #FEF2F2; color: #DC2626; }

.job-lines-table { width: 100%; border-collapse: collapse; }
.job-lines-table th {
    padding: 9px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: var(--text-muted); background: var(--page-bg);
    border-bottom: 1px solid var(--card-border); text-align: left;
}
.job-lines-table td { padding: 12px 14px; font-size: 13px; color: var(--text-secondary); border-bottom: 1px solid #F1F5F9; vertical-align: top; }
.job-lines-table tr:last-child td { border-bottom: none; }

@media print {
    .sidebar, .topbar, .page-header-actions, .no-print { display: none !important; }
    .main-wrap { margin-left: 0 !important; }
    .maint-section { break-inside: avoid; }
    body { background: white; }
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ route('maintenance.index') }}">Maintenance</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>{{ $record->job_order ?? "#{$record->id}" }}</span>
        </div>
        <h1 class="page-header-title" style="display:flex;align-items:center;gap:12px">
            {{ $record->job_order ?? "Request #{$record->id}" }}
            <span class="status-badge {{ $record->status }}">
                <i class="fa-solid fa-circle" style="font-size:6px"></i>
                {{ $record->status_label }}
            </span>
        </h1>
    </div>
    <div class="page-header-actions no-print" style="display:flex;gap:8px">
        <button onclick="window.print()" class="btn btn-outline">
            <i class="fa-solid fa-print"></i> Print
        </button>
        <a href="{{ route('maintenance.edit', $record) }}" class="btn btn-primary">
            <i class="fa-solid fa-pen"></i> Edit
        </a>
        <a href="{{ route('maintenance.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- ── SECTION 1: REQUEST DETAILS ───────────────────────── --}}
<div class="maint-section">
    <div class="maint-section-header">
        <div class="maint-section-icon"><i class="fa-solid fa-clipboard"></i></div>
        <div class="maint-section-title">Request Details</div>
    </div>
    <div class="maint-section-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Job Order</div>
                <div class="detail-value mono">{{ $record->job_order ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Date</div>
                <div class="detail-value">{{ $record->date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Request Date</div>
                <div class="detail-value">{{ $record->request_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Property</div>
                <div class="detail-value">{{ $record->property }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Tenant</div>
                <div class="detail-value">{{ $record->tenant }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Flat / Unit</div>
                <div class="detail-value mono">{{ $record->flat }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Contact No.</div>
                <div class="detail-value">{{ $record->contact_no }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Available Date &amp; Time</div>
                <div class="detail-value">{{ $record->available_datetime?->format('d M Y, H:i') ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Apartment Status</div>
                <div class="detail-value">{{ ucfirst($record->apartment_status) }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── SECTION 2: JOB LINES ──────────────────────────────── --}}
<div class="maint-section">
    <div class="maint-section-header">
        <div class="maint-section-icon"><i class="fa-solid fa-list-check"></i></div>
        <div class="maint-section-title">Job Lines</div>
    </div>
    @if($record->job_lines && count($record->job_lines) > 0)
    <div class="table-wrap">
        <table class="job-lines-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Location</th>
                    <th>Description of Work</th>
                    <th>Supervisor Comment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($record->job_lines as $i => $line)
                @if(!empty($line['location']) || !empty($line['description']))
                <tr>
                    <td style="color:var(--text-muted);font-size:12px;width:40px">{{ $i + 1 }}</td>
                    <td style="font-weight:600;color:var(--text-primary)">{{ $line['location'] ?? '—' }}</td>
                    <td>{{ $line['description'] ?? '—' }}</td>
                    <td>{{ $line['supervisor_comment'] ?? '—' }}</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="maint-section-body" style="color:var(--text-muted);font-size:13px">No job lines recorded.</div>
    @endif
</div>

{{-- ── SECTION 3: SUPERVISOR ────────────────────────────── --}}
<div class="maint-section">
    <div class="maint-section-header">
        <div class="maint-section-icon"><i class="fa-solid fa-user-tie"></i></div>
        <div class="maint-section-title">Supervisor</div>
    </div>
    <div class="maint-section-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Supervisor Name</div>
                <div class="detail-value">{{ $record->supervisor_name ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Supervisor Date &amp; Time</div>
                <div class="detail-value">{{ $record->supervisor_datetime?->format('d M Y, H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── SECTION 4: MAINTENANCE USE ONLY ─────────────────── --}}
<div class="maint-section">
    <div class="maint-section-header">
        <div class="maint-section-icon"><i class="fa-solid fa-wrench"></i></div>
        <div class="maint-section-title">Maintenance Use Only</div>
    </div>
    <div class="maint-section-body">
        @if($record->job_assessment)
        <div style="margin-bottom:18px">
            <div class="detail-label" style="margin-bottom:6px">Job Assessment</div>
            <div style="font-size:13.5px;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap">{{ $record->job_assessment }}</div>
        </div>
        @endif
        <div class="detail-grid" style="margin-bottom:{{ $record->maintenance_remarks ? '18px' : '0' }}">
            @foreach([1,2,3] as $n)
            @php $fileField = "quotation_{$n}_file"; @endphp
            <div class="detail-item">
                <div class="detail-label">Quotation {{ $n }}</div>
                <div class="detail-value mono">{{ $record->{"quotation_{$n}"} ? 'BHD '.number_format($record->{"quotation_{$n}"}, 3) : '—' }}</div>
                @if($record->$fileField)
                <div style="margin-top:6px">
                    <a href="{{ Storage::url($record->$fileField) }}" target="_blank"
                       style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;padding:4px 8px;border-radius:var(--radius-sm);background:var(--accent-dim);transition:opacity .15s"
                       onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                        <i class="fa-solid fa-paperclip" style="font-size:10px"></i>
                        {{ basename($record->$fileField) }}
                    </a>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @if($record->maintenance_remarks)
        <div>
            <div class="detail-label" style="margin-bottom:6px">Remarks</div>
            <div style="font-size:13.5px;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap">{{ $record->maintenance_remarks }}</div>
        </div>
        @endif
    </div>
</div>

{{-- ── SECTION 5: APPROVAL ──────────────────────────────── --}}
<div class="maint-section">
    <div class="maint-section-header">
        <div class="maint-section-icon"><i class="fa-solid fa-signature"></i></div>
        <div class="maint-section-title">Approval</div>
    </div>
    <div class="maint-section-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Approved by Supervisor</div>
                <div class="detail-value">{{ $record->approved_supervisor ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Approved by Dept. Head</div>
                <div class="detail-value">{{ $record->approved_dept_head ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

@endsection
