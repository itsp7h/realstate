@extends('layouts.admin')

@section('title', 'EWA Summary Results')
@section('topbar-title', 'EWA Bills')

@push('styles')
<style>

.remark-note { font-size: var(--fs-xs); color: var(--text-muted); margin-top: 3px; max-width: 220px; }
.error-note  { font-size: var(--fs-xs); color: var(--tone-danger-fg); margin-top: 3px; max-width: 220px; }

/* ── TABS ──────────────────────────────────────────────────── */
</style>
@endpush

@section('content')

@section('page-title', 'EWA Summary Results')
@section('page-subtitle')
    {{ count($rows) }} file(s) processed from this batch
@endsection
@section('page-actions')
    @include('partials.export-menu', [
        'route'  => 'ewa-bills.summary.export',
        'params' => ['batch' => $batch],
        'sub'    => 'All 22 columns of the batch',
    ])
    <a href="{{ route('ewa-bills.summary.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-rotate"></i> Process Another Batch
    </a>
@endsection


@include('ewa-bills._tabs')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon teal"><i class="fa-solid fa-file-invoice"></i></span>
            <span class="stat-lbl">Total Files</span>
        </div>
        <div class="stat-val">{{ count($rows) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon green"><i class="fa-solid fa-circle-check"></i></span>
            <span class="stat-lbl">Saved</span>
        </div>
        <div class="stat-val">{{ $succeeded }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></span>
            <span class="stat-lbl">Failed</span>
        </div>
        <div class="stat-val">{{ $failed }}</div>
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
                    <td data-label="File" style="font-size:11px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $row['file'] }}</td>
                    <td data-label="Result">
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
                    <td data-label="Property" style="font-size:12px;max-width:170px">
                        {{ $row['property_name'] ?: '—' }}
                        @if($row['address'] ?? null)
                            <div style="font-size:10px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $row['address'] }}">{{ $row['address'] }}</div>
                        @endif
                    </td>
                    <td data-label="Flat No." style="font-size:12px">{{ $row['unit'] ?: '—' }}</td>
                    <td data-label="Tenant">{{ $row['tenant_name'] ?: '—' }}</td>
                    <td data-label="Account No." class="cell-muted">{{ $row['ewa_account_number'] ?: '—' }}</td>
                    <td data-label="Previous Reading" style="font-size:11px;white-space:nowrap">
                        @if($row['previous_reading_date'])
                            <div style="color:var(--text-muted)">{{ $row['previous_reading_date'] }}</div>
                            @if($row['previous_reading_type'] ?? null)
                                <span class="status-badge {{ $row['previous_reading_type'] }}">
                                    <i class="fa-solid fa-circle" style="font-size:5px"></i>
                                    {{ ucfirst($row['previous_reading_type']) }}
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td data-label="Current Reading" style="font-size:11px;white-space:nowrap">
                        @if($row['current_reading_date'])
                            <div style="color:var(--text-muted)">{{ $row['current_reading_date'] }}</div>
                            @if($row['current_reading_type'] ?? null)
                                <span class="status-badge {{ $row['current_reading_type'] }}">
                                    <i class="fa-solid fa-circle" style="font-size:5px"></i>
                                    {{ ucfirst($row['current_reading_type']) }}
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td data-label="Electricity" class="amount-col">{{ $row['elec_charges'] !== null ? number_format($row['elec_charges'], 3) : '—' }}</td>
                    <td data-label="Water" class="amount-col">{{ $row['water_charges'] !== null ? number_format($row['water_charges'], 3) : '—' }}</td>
                    <td data-label="Total EWA" class="amount-col">{{ $row['total_ewa'] !== null ? number_format($row['total_ewa'], 3) : '—' }}</td>
                    <td data-label="Cap" class="amount-col">{{ $row['ewa_cap'] !== null ? number_format($row['ewa_cap'], 3) : '—' }}</td>
                    <td data-label="Payable by Tenant" class="amount-col">{{ $row['payable_by_tenant'] !== null ? number_format($row['payable_by_tenant'], 3) : '—' }}</td>
                    <td data-label="Muni. Tax" class="amount-col">{{ $row['municipality_fee'] !== null ? number_format($row['municipality_fee'], 3) : '—' }}</td>
                    <td data-label="Sanitary Fee" class="amount-col">{{ $row['sanitary_fee'] !== null ? number_format($row['sanitary_fee'], 3) : '—' }}</td>
                    <td data-label="Arrears" class="amount-col">{{ $row['arrears'] !== null ? number_format($row['arrears'], 3) : '—' }}</td>
                    <td data-label="Total Payable" class="amount-col">{{ $row['payable_incl_arrears'] !== null ? number_format($row['payable_incl_arrears'], 3) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
