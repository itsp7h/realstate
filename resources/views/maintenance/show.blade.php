@extends('layouts.admin')

@section('title', 'Maintenance Request — ' . ($record->job_order ?? ''))
@section('topbar-title', 'Maintenance Request')

@push('styles')
<style>
.job-lines-table { width: 100%; border-collapse: collapse; }
.job-lines-table th {
    padding: 9px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: var(--text-muted); background: var(--page-bg);
    border-bottom: 1px solid var(--card-border); text-align: left;
}
.job-lines-table td { padding: 12px 14px; font-size: 13px; color: var(--text-secondary); border-bottom: 1px solid var(--tone-neutral-border); vertical-align: top; }
.job-lines-table tr:last-child td { border-bottom: none; }

@media print {
    .sidebar, .topbar, .page-header-actions, .no-print { display: none !important; }
    .main-wrap { margin-left: 0 !important; }
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
<div class="card">
    <div class="card-header">
        <div class="card-header-icon"><i class="fa-solid fa-clipboard"></i></div>
        <div class="card-title">Request Details</div>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Job Order</div>
                <div class="detail-value is-num">{{ $record->job_order ?? '—' }}</div>
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
                <div class="detail-value is-num">{{ $record->flat }}</div>
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
<div class="card">
    <div class="card-header">
        <div class="card-header-icon"><i class="fa-solid fa-list-check"></i></div>
        <div class="card-title">Job Lines</div>
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
    <div class="card-body" style="color:var(--text-muted);font-size:13px">No job lines recorded.</div>
    @endif
</div>

{{-- ── SECTION 3: SUPERVISOR ────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <div class="card-header-icon"><i class="fa-solid fa-user-tie"></i></div>
        <div class="card-title">Supervisor</div>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Supervisor Name</div>
                <div class="detail-value">{{ $record->supervisor_name ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Supervisor Date &amp; Time</div>
                <div class="detail-value">{{ $record->supervisor_datetime?->format('d M Y, H:i') ?? '—' }}</div>
            </div>
            @if($record->supervisor_signature)
            <div class="detail-item" style="grid-column:1/-1">
                <div class="detail-label">Supervisor Signature</div>
                <div style="margin-top:6px;padding:12px;background:#fff;border:1.5px solid var(--card-border);border-radius:var(--radius-sm);display:inline-block;">
                    <img src="{{ $record->supervisor_signature }}" alt="Supervisor Signature"
                         style="max-height:80px;max-width:300px;display:block;">
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ── SECTION 4: MAINTENANCE USE ONLY ─────────────────── --}}
<div class="card">
    <div class="card-header">
        <div class="card-header-icon"><i class="fa-solid fa-wrench"></i></div>
        <div class="card-title">Maintenance Use Only</div>
    </div>
    <div class="card-body">
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
                <div class="detail-value is-num">{{ $record->{"quotation_{$n}"} ? 'BHD '.number_format($record->{"quotation_{$n}"}, 3) : '—' }}</div>
                @if($record->$fileField)
                <div style="margin-top:6px;max-width:100%">
                    <a href="{{ Storage::url($record->$fileField) }}" target="_blank" title="{{ basename($record->$fileField) }}"
                       style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;padding:4px 8px;border-radius:var(--radius-sm);background:var(--accent-dim);transition:opacity .15s;max-width:100%;overflow:hidden"
                       onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                        <i class="fa-solid fa-paperclip" style="font-size:10px;flex-shrink:0"></i>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ basename($record->$fileField) }}</span>
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
<div class="card">
    <div class="card-header">
        <div class="card-header-icon"><i class="fa-solid fa-signature"></i></div>
        <div class="card-title">Approval</div>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            @if($record->selected_quotation)
            <div class="detail-item">
                <div class="detail-label">Approved Quotation</div>
                <div class="detail-value">
                    <span style="display:inline-flex;align-items:center;gap:6px;background:#F5F3FF;color:#7C3AED;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">
                        <i class="fa-solid fa-stamp" style="font-size:10px"></i>
                        Q{{ $record->selected_quotation }} — {{ $record->selected_quotation_amount ?? '—' }}
                    </span>
                </div>
            </div>
            @endif
            <div class="detail-item">
                <div class="detail-label">Approved by Supervisor</div>
                <div class="detail-value">{{ $record->approved_supervisor ?? '—' }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Approved by Dept. Head</div>
                <div class="detail-value">{{ $record->approved_dept_head ?? '—' }}</div>
            </div>
            @if($record->dept_head_signature)
            <div class="detail-item" style="grid-column:1/-1">
                <div class="detail-label">Dept. Head Signature</div>
                <div style="margin-top:6px;padding:12px;background:#fff;border:1.5px solid var(--card-border);border-radius:var(--radius-sm);display:inline-block;">
                    <img src="{{ $record->dept_head_signature }}" alt="Dept. Head Signature"
                         style="max-height:80px;max-width:300px;display:block;">
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
