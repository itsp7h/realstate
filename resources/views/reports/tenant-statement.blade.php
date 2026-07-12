@extends('layouts.admin')

@section('title', 'Tenant Statement')
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
.total-row td { background: var(--page-bg); font-weight: 700; }
.overdue-pos { color: #DC2626; font-weight: 700; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Tenant Statement</h1>
        <p class="page-header-sub">Bill-wise outstanding statement for a single tenant</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        @if($tenant)
        <a href="{{ route('reports.tenant-statement.pdf', request()->only(['tenant_id','date_from','date_to'])) }}"
           target="_blank" class="btn btn-primary"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        @endif
    </div>
</div>

<form method="GET" action="{{ route('reports.tenant-statement') }}" class="filter-bar">
    <select name="tenant_id" required>
        <option value="">Select a tenant…</option>
        @foreach($tenants as $t)
        <option value="{{ $t->id }}" {{ $tenant && $tenant->id === $t->id ? 'selected' : '' }}>{{ $t->name }} @if($t->tenant_code)({{ $t->tenant_code }})@endif</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" title="From">
    <input type="date" name="date_to"   value="{{ $to->format('Y-m-d') }}"   title="To">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Statement</button>
</form>

@if(!$tenant)
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-file-invoice"></i>Select a tenant to view their statement.</div></div>
@elseif($rows->isEmpty())
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-circle-check"></i>{{ $tenant->name }} has no outstanding bills in this date range.</div></div>
@else
<div class="table-card">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Date</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Bill Ref.</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Description</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Opening (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Pending (BHD)</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Due On</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Overdue (days)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr style="border-bottom:1px solid var(--card-border)">
                <td style="padding:9px 14px;white-space:nowrap">{{ $row['date']->format('d M Y') }}</td>
                <td style="padding:9px 14px;font-weight:600">{{ $row['bill_ref'] }}</td>
                <td style="padding:9px 14px;color:var(--text-muted)">{{ $row['description'] }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($row['opening_amount']) }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif;font-weight:700">{{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}</td>
                <td style="padding:9px 14px;white-space:nowrap">{{ $row['due_on']->format('d M Y') }}</td>
                <td class="right" style="padding:9px 14px">
                    <span class="{{ $row['overdue_days'] > 0 ? 'overdue-pos' : '' }}">{{ $row['overdue_days'] }}</span>
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="padding:12px 14px;text-align:right">Total Outstanding</td>
                <td class="right" style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($total) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
</div>
@endif

@endsection
