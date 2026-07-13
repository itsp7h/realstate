@extends('layouts.admin')

@section('title', 'Tenant Ledger')
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
.money-col { font-family: 'Outfit', sans-serif; }
.balance-row-owing  { color: #DC2626; font-weight: 700; }
.balance-row-settled { color: #059669; font-weight: 700; }
.closing-row td { background: var(--page-bg); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Tenant Ledger</h1>
        <p class="page-header-sub">Full transaction history for a single tenant, with a running balance</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        @if($tenant)
        <a href="{{ route('reports.tenant-ledger.pdf', request()->only(['tenant_id','date_from','date_to'])) }}"
           target="_blank" class="btn btn-primary"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        @endif
    </div>
</div>

<form method="GET" action="{{ route('reports.tenant-ledger') }}" class="filter-bar">
    <select name="tenant_id" required>
        <option value="">Select a tenant…</option>
        @foreach($tenants as $t)
        <option value="{{ $t->id }}" {{ $tenant && $tenant->id === $t->id ? 'selected' : '' }}>{{ $t->name }} @if($t->tenant_code)({{ $t->tenant_code }})@endif</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" title="From">
    <input type="date" name="date_to"   value="{{ $to->format('Y-m-d') }}"   title="To">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Ledger</button>
</form>

@if(!$tenant)
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-book"></i>Select a tenant to view their ledger.</div></div>
@elseif($rows->isEmpty())
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-circle-check"></i>{{ $tenant->name }} has no transactions in this date range.</div></div>
@else
<div class="table-card">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Date</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Reference</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Description</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Debit (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Credit (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Balance (BHD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr style="border-bottom:1px solid var(--card-border)">
                <td style="padding:9px 14px;white-space:nowrap">{{ $row['date']->format('d M Y') }}</td>
                <td style="padding:9px 14px;font-weight:600">{{ $row['bill_ref'] }}</td>
                <td style="padding:9px 14px;color:var(--text-muted)">{{ $row['description'] }}</td>
                <td class="right money-col" style="padding:9px 14px">{{ $row['debit'] > 0.001 ? number_format($row['debit'], 3) : '—' }}</td>
                <td class="right money-col" style="padding:9px 14px">{{ $row['credit'] > 0.001 ? number_format($row['credit'], 3) : '—' }}</td>
                <td class="right money-col {{ $row['balance'] > 0.001 ? 'balance-row-owing' : 'balance-row-settled' }}" style="padding:9px 14px">
                    {{ number_format(abs($row['balance']), 3) }}{{ $row['balance'] > 0.001 ? ' Dr' : ($row['balance'] < -0.001 ? ' Cr' : '') }}
                </td>
            </tr>
            @endforeach
            <tr class="closing-row">
                <td colspan="5" style="padding:12px 14px;text-align:right;font-weight:700">Closing Balance</td>
                <td class="right money-col {{ $rows->last()['balance'] > 0.001 ? 'balance-row-owing' : 'balance-row-settled' }}" style="padding:12px 14px">
                    {{ number_format(abs($rows->last()['balance']), 3) }}{{ $rows->last()['balance'] > 0.001 ? ' Dr' : ($rows->last()['balance'] < -0.001 ? ' Cr' : '') }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endif

@endsection
