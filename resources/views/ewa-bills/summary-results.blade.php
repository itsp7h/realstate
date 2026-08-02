@extends('layouts.admin')

@section('title', 'EWA Summary Results')
@section('topbar-title', 'EWA Bills')

@push('styles')
<style>
.ewa-stats {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(160px,1fr));
    gap: 14px; margin-bottom: 24px;
}
.ewa-stat {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
}
.ewa-stat-icon {
    width: 40px; height: 40px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
}
.ewa-stat-icon.teal  { background: #F0FDFA; color: #0D9488; }
.ewa-stat-icon.green { background: #ECFDF5; color: #059669; }
.ewa-stat-icon.red   { background: #FEF2F2; color: #DC2626; }
.ewa-stat-val { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.ewa-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; }
.amount-col { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 13px; white-space: nowrap; }

.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap;
}
.status-badge.created { background: #ECFDF5; color: #059669; }
.status-badge.updated { background: #EFF6FF; color: #2563EB; }
.status-badge.failed  { background: #FEF2F2; color: #DC2626; }

.rt-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap;
}
.rt-badge.actual    { background: #ECFDF5; color: #059669; }
.rt-badge.estimated { background: #FFFBEB; color: #D97706; }

.remark-note { font-size: 11px; color: var(--text-muted); margin-top: 3px; max-width: 220px; }
.error-note  { font-size: 11px; color: #DC2626; margin-top: 3px; max-width: 220px; }

/* ── TABS ──────────────────────────────────────────────────── */
.tab-bar { display: flex; gap: 4px; border-bottom: 2px solid var(--card-border); margin-bottom: 20px; }
.tab-btn {
    padding: 11px 22px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; font-weight: 600;
    color: var(--text-muted); border: none; background: none; cursor: pointer;
    border-bottom: 2px solid transparent; margin-bottom: -2px; transition: color 0.18s, border-color 0.18s;
    display: flex; align-items: center; gap: 8px; text-decoration: none;
}
.tab-btn:hover { color: var(--text-primary); }
.tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
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

<div class="ewa-stats">
    <div class="ewa-stat">
        <div class="ewa-stat-icon teal"><i class="fa-solid fa-file-invoice"></i></div>
        <div><div class="ewa-stat-val">{{ count($rows) }}</div><div class="ewa-stat-lbl">Total Files</div></div>
    </div>
    <div class="ewa-stat">
        <div class="ewa-stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="ewa-stat-val">{{ $succeeded }}</div><div class="ewa-stat-lbl">Saved</div></div>
    </div>
    <div class="ewa-stat">
        <div class="ewa-stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
        <div><div class="ewa-stat-val">{{ $failed }}</div><div class="ewa-stat-lbl">Failed</div></div>
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
