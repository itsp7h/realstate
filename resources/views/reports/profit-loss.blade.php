@extends('layouts.admin')

@section('title', 'Profit & Loss Statement')
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
.filter-bar select { min-width: 190px; }
.filter-bar input[type="date"] { min-width: 140px; }

.pl-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
@media (max-width: 820px) { .pl-stats { grid-template-columns: 1fr; } }

.pl-stat {
    background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius);
    padding: 20px 22px; border-left: 3px solid var(--stat-accent, var(--card-border));
    display: flex; flex-direction: column; gap: 6px;
}
.pl-stat-label {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted);
    display: flex; align-items: center; gap: 6px;
}
.pl-stat-value {
    font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 700; color: var(--stat-color, var(--text-primary));
    line-height: 1.15;
}
.pl-stat-value sup { font-size: 12px; font-weight: 600; margin-left: 4px; }
.pl-stat--revenue { --stat-accent: #059669; }
.pl-stat--revenue .pl-stat-value { --stat-color: #059669; }
.pl-stat--expense { --stat-accent: #D97706; }
.pl-stat--expense .pl-stat-value { --stat-color: #D97706; }
.pl-stat--net.is-profit { --stat-accent: #059669; }
.pl-stat--net.is-profit .pl-stat-value { --stat-color: #059669; }
.pl-stat--net.is-loss { --stat-accent: #DC2626; }
.pl-stat--net.is-loss .pl-stat-value { --stat-color: #DC2626; }
.pl-stat--net .pl-stat-value {
    padding-bottom: 6px; border-bottom: 2px solid currentColor; box-shadow: 0 4px 0 -3px currentColor;
    display: inline-block;
}

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; margin-bottom: 20px; }
.table-card-title { padding: 14px 18px 0; font-family: 'Outfit', sans-serif; font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
.report-empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.report-empty i { font-size: 36px; display: block; margin-bottom: 12px; opacity: 0.3; }
.right { text-align: right; }
.subtotal-row td { background: var(--page-bg); font-weight: 700; }
.net-row td { border-top: 2px solid var(--text-primary); font-weight: 700; padding-top: 12px; }
.pl-money { font-family: 'Outfit', sans-serif; }
.pl-note { margin-top: 4px; font-size: 12px; color: var(--text-muted); max-width: 720px; }
</style>
@endpush

@section('content')

@php
    $net = $statement['net_profit'];
    $isProfit = $net >= 0;
    $fmt = fn ($v) => number_format($v, 3);
@endphp

<div class="page-header">
    <div>
        <h1 class="page-header-title">Profit &amp; Loss Statement</h1>
        <p class="page-header-sub">Cash collected against costs incurred, per building, tenant, or unit</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        <a href="{{ route('reports.profit-loss.pdf', request()->only(['building_id','tenant_id','unit_id','date_from','date_to'])) }}"
           target="_blank" class="btn btn-primary"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    </div>
</div>

<form method="GET" action="{{ route('reports.profit-loss') }}" class="filter-bar">
    <select name="building_id">
        <option value="">All buildings</option>
        @foreach($buildings as $b)
        <option value="{{ $b->id }}" {{ $buildingId === $b->id ? 'selected' : '' }}>{{ $b->property_name }}</option>
        @endforeach
    </select>
    <select name="unit_id">
        <option value="">All units</option>
        @foreach($units as $u)
        <option value="{{ $u->id }}" {{ $unitId === $u->id ? 'selected' : '' }}>{{ $u->unit_name }}</option>
        @endforeach
    </select>
    <select name="tenant_id">
        <option value="">All tenants</option>
        @foreach($tenants as $t)
        <option value="{{ $t->id }}" {{ $tenantId === $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" title="From">
    <input type="date" name="date_to"   value="{{ $to->format('Y-m-d') }}"   title="To">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View</button>
</form>

<div class="pl-stats">
    <div class="pl-stat pl-stat--revenue">
        <div class="pl-stat-label"><i class="fa-solid fa-arrow-trend-up"></i> Total Revenue</div>
        <div class="pl-stat-value">{{ $fmt($statement['total_revenue']) }}<sup>BHD</sup></div>
    </div>
    <div class="pl-stat pl-stat--expense">
        <div class="pl-stat-label"><i class="fa-solid fa-arrow-trend-down"></i> Total Expense</div>
        <div class="pl-stat-value">{{ $fmt($statement['total_expense']) }}<sup>BHD</sup></div>
    </div>
    <div class="pl-stat pl-stat--net {{ $isProfit ? 'is-profit' : 'is-loss' }}">
        <div class="pl-stat-label"><i class="fa-solid {{ $isProfit ? 'fa-circle-up' : 'fa-circle-down' }}"></i> Net {{ $isProfit ? 'Profit' : 'Loss' }}</div>
        <div class="pl-stat-value">{{ $fmt(abs($net)) }}<sup>BHD</sup></div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-title">Revenue &amp; Expense Breakdown</div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:10px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 18px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Line Item</th>
                <th class="right" style="padding:10px 18px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Amount (BHD)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom:1px solid var(--card-border)"><td style="padding:9px 18px;color:var(--text-muted)">Rent collected</td><td class="right pl-money" style="padding:9px 18px">{{ $fmt($statement['revenue']['rent_collected']) }}</td></tr>
            <tr style="border-bottom:1px solid var(--card-border)"><td style="padding:9px 18px;color:var(--text-muted)">Utilities collected</td><td class="right pl-money" style="padding:9px 18px">{{ $fmt($statement['revenue']['utilities_collected']) }}</td></tr>
            <tr style="border-bottom:1px solid var(--card-border)"><td style="padding:9px 18px;color:var(--text-muted)">Other invoices collected</td><td class="right pl-money" style="padding:9px 18px">{{ $fmt($statement['revenue']['other_collected']) }}</td></tr>
            <tr style="border-bottom:1px solid var(--card-border)"><td style="padding:9px 18px;color:var(--text-muted)">EWA collected from tenants</td><td class="right pl-money" style="padding:9px 18px">{{ $fmt($statement['revenue']['ewa_collected']) }}</td></tr>
            <tr class="subtotal-row"><td style="padding:10px 18px">Total Revenue</td><td class="right pl-money" style="padding:10px 18px">{{ $fmt($statement['total_revenue']) }}</td></tr>

            <tr style="border-bottom:1px solid var(--card-border)"><td style="padding:9px 18px;color:var(--text-muted)">EWA charges not recovered from tenant</td><td class="right pl-money" style="padding:9px 18px">{{ $fmt($statement['expenses']['ewa_landlord_expense']) }}</td></tr>
            <tr style="border-bottom:1px solid var(--card-border)"><td style="padding:9px 18px;color:var(--text-muted)">Approved maintenance cost</td><td class="right pl-money" style="padding:9px 18px">{{ $fmt($statement['expenses']['maintenance_expense']) }}</td></tr>
            <tr class="subtotal-row"><td style="padding:10px 18px">Total Expense</td><td class="right pl-money" style="padding:10px 18px">{{ $fmt($statement['total_expense']) }}</td></tr>

            <tr class="net-row">
                <td style="padding:12px 18px">Net {{ $isProfit ? 'Profit' : 'Loss' }}</td>
                <td class="right pl-money" style="padding:12px 18px;color:{{ $isProfit ? '#059669' : '#DC2626' }}">{{ $fmt(abs($net)) }}</td>
            </tr>
        </tbody>
    </table>
</div>

@if($breakdown->isNotEmpty())
<div class="table-card">
    <div class="table-card-title">By Building</div>
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:10px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 18px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Building</th>
                <th class="right" style="padding:10px 18px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Revenue (BHD)</th>
                <th class="right" style="padding:10px 18px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Expense (BHD)</th>
                <th class="right" style="padding:10px 18px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Net (BHD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($breakdown as $row)
            <tr style="border-bottom:1px solid var(--card-border)">
                <td style="padding:9px 18px;font-weight:600">
                    <a href="{{ route('reports.profit-loss', ['building_id' => $row['building']->id, 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}" style="color:var(--text-primary);text-decoration:none">
                        {{ $row['building']->property_name }}
                    </a>
                </td>
                <td class="right pl-money" style="padding:9px 18px">{{ $fmt($row['total_revenue']) }}</td>
                <td class="right pl-money" style="padding:9px 18px">{{ $fmt($row['total_expense']) }}</td>
                <td class="right pl-money" style="padding:9px 18px;font-weight:700;color:{{ $row['net_profit'] >= 0 ? '#059669' : '#DC2626' }}">{{ $fmt($row['net_profit']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="pl-note">
    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
    Cash-basis for revenue (payments actually received). Expenses are recognised when incurred — EWA bills on their reading date, maintenance costs once department-head approved — since the system doesn't track a "paid to EWA authority" or "paid to contractor" event. Maintenance costs currently only roll up by building, not by tenant, since maintenance requests aren't linked to a tenant record.
</div>

@endsection
