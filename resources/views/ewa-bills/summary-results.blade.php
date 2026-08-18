@extends('layouts.admin')

@section('title', 'EWA Summary Results')
@section('topbar-title', 'EWA Bills')

@push('styles')
<style>
.rt-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap;
}
.rt-badge.actual    { background: var(--tone-success-bg); color: var(--tone-success-fg); }
.rt-badge.estimated { background: var(--tone-warning-bg); color: var(--tone-warning-fg); }

.remark-note { font-size: 11px; color: var(--text-muted); margin-top: 3px; max-width: 220px; }
.error-note  { font-size: 11px; color: var(--tone-danger-fg); margin-top: 3px; max-width: 220px; }

/* ── TABS ──────────────────────────────────────────────────── */
.tab-bar { display: flex; gap: 4px; border-bottom: 2px solid var(--card-border); margin-bottom: 20px; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">EWA Summary Results</h1>
        <p class="page-header-sub">{{ count($rows) }} file(s) processed from this batch</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('ewa-bills.summary.export', $batch) }}" class="btn btn-outline">
            <i class="fa-solid fa-file-excel"></i> Download Excel
        </a>
        <a href="{{ route('ewa-bills.summary.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-rotate"></i> Process Another Batch
        </a>
    </div>
</div>

@include('ewa-bills._tabs')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-file-invoice"></i></div>
        <div><div class="stat-val">{{ count($rows) }}</div><div class="stat-lbl">Total Files</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="stat-val">{{ $succeeded }}</div><div class="stat-lbl">Saved</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
        <div><div class="stat-val">{{ $failed }}</div><div class="stat-lbl">Failed</div></div>
    </div>
</div>

<div class="table-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>File</th>
                    <th>Result</th>
                    <th>Property</th>
                    <th>Flat No.</th>
                    <th>Tenant</th>
                    <th>Account No.</th>
                    <th>Previous Reading</th>
                    <th>Current Reading</th>
                    <th>Electricity</th>
                    <th>Water</th>
                    <th>Total EWA</th>
                    <th>Cap</th>
                    <th>Payable by Tenant</th>
                    <th>Muni. Tax</th>
                    <th>Sanitary Fee</th>
                    <th>Arrears</th>
                    <th>Total Payable</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr @if(in_array($row['status'], ['created', 'updated'])) data-href="{{ route('ewa-bills.show', $row['bill_id']) }}" style="cursor:pointer" @endif>
                    <td style="font-size:11px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $row['file'] }}</td>
                    <td>
                        <span class="status-badge {{ $row['status'] }}">
                            <i class="fa-solid fa-circle" style="font-size:5px"></i>
                            {{ ucfirst($row['status']) }}
                        </span>
                        @if($row['status'] === 'failed')
                            <div class="error-note">{{ $row['error'] }}</div>
                        @elseif($row['remarks'])
                            <div class="remark-note">{{ $row['remarks'] }}</div>
                        @endif
                    </td>
                    <td style="font-size:12px;max-width:170px">
                        {{ $row['property_name'] ?: '—' }}
                        @if($row['address'] ?? null)
                            <div style="font-size:10px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $row['address'] }}">{{ $row['address'] }}</div>
                        @endif
                    </td>
                    <td style="font-size:12px">{{ $row['unit'] ?: '—' }}</td>
                    <td>{{ $row['tenant_name'] ?: '—' }}</td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $row['ewa_account_number'] ?: '—' }}</td>
                    <td style="font-size:11px;white-space:nowrap">
                        @if($row['previous_reading_date'])
                            <div style="color:var(--text-muted)">{{ $row['previous_reading_date'] }}</div>
                            @if($row['previous_reading_type'] ?? null)
                                <span class="rt-badge {{ $row['previous_reading_type'] }}">
                                    <i class="fa-solid fa-circle" style="font-size:5px"></i>
                                    {{ ucfirst($row['previous_reading_type']) }}
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size:11px;white-space:nowrap">
                        @if($row['current_reading_date'])
                            <div style="color:var(--text-muted)">{{ $row['current_reading_date'] }}</div>
                            @if($row['current_reading_type'] ?? null)
                                <span class="rt-badge {{ $row['current_reading_type'] }}">
                                    <i class="fa-solid fa-circle" style="font-size:5px"></i>
                                    {{ ucfirst($row['current_reading_type']) }}
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="amount-col">{{ $row['elec_charges'] !== null ? number_format($row['elec_charges'], 3) : '—' }}</td>
                    <td class="amount-col">{{ $row['water_charges'] !== null ? number_format($row['water_charges'], 3) : '—' }}</td>
                    <td class="amount-col">{{ $row['total_ewa'] !== null ? number_format($row['total_ewa'], 3) : '—' }}</td>
                    <td class="amount-col">{{ $row['ewa_cap'] !== null ? number_format($row['ewa_cap'], 3) : '—' }}</td>
                    <td class="amount-col">{{ $row['payable_by_tenant'] !== null ? number_format($row['payable_by_tenant'], 3) : '—' }}</td>
                    <td class="amount-col">{{ $row['municipality_fee'] !== null ? number_format($row['municipality_fee'], 3) : '—' }}</td>
                    <td class="amount-col">{{ $row['sanitary_fee'] !== null ? number_format($row['sanitary_fee'], 3) : '—' }}</td>
                    <td class="amount-col">{{ $row['arrears'] !== null ? number_format($row['arrears'], 3) : '—' }}</td>
                    <td class="amount-col">{{ $row['payable_incl_arrears'] !== null ? number_format($row['payable_incl_arrears'], 3) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
