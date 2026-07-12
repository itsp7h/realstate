@extends('layouts.admin')

@section('title', 'Rent Payment Schedule')
@section('topbar-title', 'Reports')

@push('styles')
<style>
.filter-bar {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 14px 18px;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px;
}
.filter-bar select, .filter-bar input {
    padding: 8px 12px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
}
.filter-bar select:focus, .filter-bar input:focus { border-color: var(--accent); }
.filter-bar select[name="tenant_id"] { flex: 1; min-width: 220px; }
.filter-bar input[type="date"] { min-width: 140px; }

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; }
.report-empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.report-empty i { font-size: 36px; display: block; margin-bottom: 12px; opacity: 0.3; }
.right { text-align: right; }

.rs-status {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
}
.rs-status.paid          { background: #ECFDF5; color: #059669; }
.rs-status.partial       { background: #FFFBEB; color: #D97706; }
.rs-status.unpaid        { background: #FEF2F2; color: #DC2626; }
.rs-status.not_invoiced  { background: #F1F5F9; color: #64748B; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Rent Payment Schedule</h1>
        <p class="page-header-sub">Month-by-month rent history for a single tenant</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        @if($tenant)
        <a href="{{ route('reports.rent-schedule.pdf', request()->only(['tenant_id','date_from','date_to'])) }}"
           target="_blank" class="btn btn-primary"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        @endif
    </div>
</div>

<form method="GET" action="{{ route('reports.rent-schedule') }}" class="filter-bar">
    <select name="tenant_id" required>
        <option value="">Select a tenant…</option>
        @foreach($tenants as $t)
        <option value="{{ $t->id }}" {{ $tenant && $tenant->id === $t->id ? 'selected' : '' }}>{{ $t->name }} @if($t->tenant_code)({{ $t->tenant_code }})@endif</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ $from }}" title="From (optional — defaults to full rent history)">
    <input type="date" name="date_to"   value="{{ $to }}"   title="To (optional)">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Schedule</button>
</form>

@if(!$tenant)
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-calendar-check"></i>Select a tenant to view their rent payment schedule.</div></div>
@elseif($rows->isEmpty())
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-circle-info"></i>{{ $tenant->name }} has no rent-bearing lease contracts on file.</div></div>
@else
<div class="table-card">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Month</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Expected (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Invoiced (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Paid (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Remaining (BHD)</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr style="border-bottom:1px solid var(--card-border)">
                <td style="padding:9px 14px;font-weight:600">{{ $row['month']->format('F Y') }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ number_format($row['expected'], 3) }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ number_format($row['invoiced'], 3) }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ number_format($row['paid'], 3) }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif;font-weight:700;color:{{ $row['remaining'] > 0.001 ? '#DC2626' : '#059669' }}">{{ number_format($row['remaining'], 3) }}</td>
                <td style="padding:9px 14px">
                    <span class="rs-status {{ $row['status'] }}">
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
@endif

<div style="margin-top:20px;font-size:12px;color:var(--text-muted);max-width:640px">
    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
    "Not Invoiced" means no rent invoice was ever raised for that month — distinct from "Unpaid," where an invoice exists but nothing's been paid against it.
</div>

@endsection
