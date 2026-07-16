@extends('layouts.admin')

@section('title', 'Tenant Financial Summary')
@section('topbar-title', 'Reports')

@push('styles')
<style>
.filter-bar {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 14px 18px;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px;
}
.filter-bar input {
    padding: 8px 12px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
}
.filter-bar input:focus { border-color: var(--accent); }
.filter-bar input[type="date"] { min-width: 140px; }

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; overflow-x: auto; }
.report-empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.report-empty i { font-size: 36px; display: block; margin-bottom: 12px; opacity: 0.3; }
.right { text-align: right; }
.total-row td { background: var(--page-bg); font-weight: 700; }
.net-owing  { color: #DC2626; }
.net-credit { color: #059669; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Tenant Financial Summary</h1>
        <p class="page-header-sub">Every tenant's balance for the period &mdash; carried-forward opening balance, what was billed and received, and the resulting net balance</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        <a href="{{ route('reports.financial-summary.pdf', request()->only(['date_from','date_to'])) }}"
           target="_blank" class="btn btn-primary"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    </div>
</div>

<form method="GET" action="{{ route('reports.financial-summary') }}" class="filter-bar">
    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" title="From">
    <input type="date" name="date_to"   value="{{ $to->format('Y-m-d') }}"   title="To">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View</button>
</form>

@if($rows->isEmpty())
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-circle-check"></i>No tenant activity or balance in this date range.</div></div>
@else
<div class="table-card">
    <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:720px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Tenant</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Opening Balance (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Amount (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Received Amount (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Net Balance (BHD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr style="border-bottom:1px solid var(--card-border)">
                <td style="padding:9px 14px;font-weight:600">
                    <a href="{{ route('reports.tenant-ledger', ['tenant_id' => $r['tenant']->id, 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}" style="color:var(--text-primary);text-decoration:none">
                        {{ $r['tenant']->name }}
                    </a>
                </td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($r['opening_balance']) }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($r['period_amount']) }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr(-$r['period_received']) }}</td>
                <td class="right {{ $r['net_balance'] > 0.001 ? 'net-owing' : ($r['net_balance'] < -0.001 ? 'net-credit' : '') }}" style="padding:9px 14px;font-family:'Outfit',sans-serif;font-weight:700">{{ \App\Support\MoneyFormat::crDr($r['net_balance']) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td style="padding:12px 14px">Grand Total</td>
                <td class="right" style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($rows->sum('opening_balance')) }}</td>
                <td class="right" style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($rows->sum('period_amount')) }}</td>
                <td class="right" style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr(-$rows->sum('period_received')) }}</td>
                <td class="right" style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($rows->sum('net_balance')) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

@endsection
