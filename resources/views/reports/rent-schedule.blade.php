@extends('layouts.admin')

@section('title', 'Rent Payment Schedule')
@section('topbar-title', 'Reports')

@section('content')

@section('page-title', 'Rent Payment Schedule')
@section('page-subtitle', 'Month-by-month rent history for a single tenant')
@section('page-back')
    <a href="{{ route('reports.index') }}" class="btn btn-outline" aria-label="Back to Reports">
        <i class="fa-solid fa-arrow-left"></i><span class="pagehead-back-label"> Reports</span>
    </a>
@endsection

@section('page-actions')
    @if($tenant)
    <button type="button" class="btn btn-outline"
            onclick="openReportPdf('{{ route('reports.rent-schedule.pdf', request()->only(['tenant_id','date_from','date_to'])) }}', 'Rent Payment Schedule — {{ $tenant->name }}')">
        <i class="fa-solid fa-eye"></i> Preview
    </button>
    <a href="{{ route('reports.rent-schedule.pdf', request()->only(['tenant_id','date_from','date_to'])) }}"
       target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    <x-export-button href="{{ route('reports.rent-schedule.export', request()->only(['tenant_id','date_from','date_to'])) }}" label="Export XLSX" icon="fa-file-excel" />
    @endif
@endsection


<form method="GET" action="{{ route('reports.rent-schedule') }}" class="filter-card">
    <div class="filter-bar">
        <div class="filter-group is-search">
            <label for="f_tenant_id">Tenant</label>
            <select id="f_tenant_id" name="tenant_id" required>
                <option value="">Select a tenant…</option>
                @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ $tenant && $tenant->id === $t->id ? 'selected' : '' }}>{{ $t->name }} @if($t->tenant_code)({{ $t->tenant_code }})@endif</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label for="f_date_from">From</label>
            <input type="date" id="f_date_from" name="date_from" value="{{ $from }}"
                   aria-describedby="f_date_from_help">
            <span class="field-help" id="f_date_from_help">Optional — defaults to the full rent history</span>
        </div>
        <div class="filter-group">
            <label for="f_date_to">To</label>
            <input type="date" id="f_date_to" name="date_to" value="{{ $to }}"
                   aria-describedby="f_date_to_help">
            <span class="field-help" id="f_date_to_help">Optional</span>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Schedule</button>
        </div>
    </div>
</form>

@if(!$tenant)
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-calendar-check"></i></div>
        <h4>Pick a tenant</h4>
        <p>Choose a tenant above to see every rent instalment on their lease.</p>
    </div>
</div>
@elseif($rows->isEmpty())
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></div>
        <h4>No rent on file</h4>
        <p>{{ $tenant->name }} has no lease contract that charges rent.</p>
    </div>
</div>
@else
<div class="table-card">
    <div class="table-wrap is-scroll">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="right">Expected (BHD)</th>
                    <th class="right">Invoiced (BHD)</th>
                    <th class="right">Paid (BHD)</th>
                    <th class="right">Remaining (BHD)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td class="cell-title">{{ $row['month']->format('F Y') }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ number_format($row['expected'], 3) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ number_format($row['invoiced'], 3) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ number_format($row['paid'], 3) }}</td>
                    <td class="right cell-title" style="font-family:'Outfit',sans-serif; color:{{ $row['remaining'] > 0.001 ? 'var(--tone-danger-fg)' : 'var(--tone-success-fg)' }}">{{ number_format($row['remaining'], 3) }}</td>
                    <td>
                        <span class="status-badge {{ $row['status'] }}">
                            {{ match($row['status']) {
                                'paid'         => 'Paid',
                                'partial'      => 'Partially Paid',
                                'unpaid'       => 'Unpaid',
                                'not_invoiced' => 'Not Invoiced',
                            } }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div style="margin-top:20px;font-size:12px;color:var(--text-muted);max-width:640px">
    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
    "Not Invoiced" means no rent invoice was ever raised for that month — distinct from "Unpaid," where an invoice exists but nothing's been paid against it.
</div>

@include('reports._pdf-preview-modal')

@endsection
