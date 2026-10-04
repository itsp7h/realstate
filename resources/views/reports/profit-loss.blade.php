@extends('layouts.admin')

@section('title', 'Profit & Loss Statement')
@section('topbar-title', 'Reports')

@section('content')

@section('page-title', 'Profit & Loss Statement')
@section('page-subtitle', 'Cash collected against costs incurred, per building, tenant, or unit')
@section('page-back')
    <a href="{{ route('reports.index') }}" class="btn btn-outline" aria-label="Back to Reports">
        <i class="fa-solid fa-arrow-left"></i><span class="pagehead-back-label"> Reports</span>
    </a>
@endsection

@section('page-actions')
    <button type="button" class="btn btn-outline"
            onclick="openReportPdf('{{ route('reports.profit-loss.pdf', request()->only(['building_id','tenant_id','unit_id','date_from','date_to'])) }}', 'Profit &amp; Loss Statement')">
        <i class="fa-solid fa-eye"></i> Preview
    </button>
    <a href="{{ route('reports.profit-loss.pdf', request()->only(['building_id','tenant_id','unit_id','date_from','date_to'])) }}"
       target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    <x-export-button href="{{ route('reports.profit-loss.export', request()->only(['building_id','tenant_id','unit_id','date_from','date_to'])) }}" label="Export XLSX" icon="fa-file-excel" />
@endsection

@php
    $net = $statement['net_profit'];
    $isProfit = $net >= 0;
    $fmt = fn ($v) => number_format($v, 3);
@endphp


<form method="GET" action="{{ route('reports.profit-loss') }}" class="filter-card">
    <div class="filter-bar">
        <div class="filter-group is-search">
            <label for="f_building_id">Building</label>
            <select id="f_building_id" name="building_id">
                <option value="">All buildings</option>
                @foreach($buildings as $b)
                <option value="{{ $b->id }}" {{ $buildingId === $b->id ? 'selected' : '' }}>{{ $b->property_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label for="f_unit_id">Unit</label>
            <select id="f_unit_id" name="unit_id">
                <option value="">All units</option>
                @foreach($units as $u)
                <option value="{{ $u->id }}" {{ $unitId === $u->id ? 'selected' : '' }}>{{ $u->unit_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group is-search">
            <label for="f_tenant_id">Tenant</label>
            <select id="f_tenant_id" name="tenant_id">
                <option value="">All tenants</option>
                @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ $tenantId === $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label for="f_date_from">From</label>
            <input type="date" id="f_date_from" name="date_from" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="filter-group">
            <label for="f_date_to">To</label>
            <input type="date" id="f_date_to" name="date_to"   value="{{ $to->format('Y-m-d') }}">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View</button>
        </div>
    </div>
</form>

<div class="stats-grid is-triple">
    <div class="stat-tile is-success">
        <div class="stat-tile-label"><i class="fa-solid fa-arrow-trend-up"></i> Total Revenue</div>
        <div class="stat-tile-value">{{ $fmt($statement['total_revenue']) }}<sup>BHD</sup></div>
    </div>
    <div class="stat-tile is-warning">
        <div class="stat-tile-label"><i class="fa-solid fa-arrow-trend-down"></i> Total Expense</div>
        <div class="stat-tile-value">{{ $fmt($statement['total_expense']) }}<sup>BHD</sup></div>
    </div>
    <div class="stat-tile is-total {{ $isProfit ? 'is-profit' : 'is-loss' }}">
        <div class="stat-tile-label"><i class="fa-solid {{ $isProfit ? 'fa-circle-up' : 'fa-circle-down' }}"></i> Net {{ $isProfit ? 'Profit' : 'Loss' }}</div>
        <div class="stat-tile-value">{{ $fmt(abs($net)) }}<sup>BHD</sup></div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-title">Revenue &amp; Expense Breakdown</div>
    <div class="table-wrap is-scroll">
        <table>
            <thead>
                <tr>
                    <th>Line Item</th>
                    <th class="right">Amount (BHD)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td class="val-muted">Rent collected</td><td class="right num">{{ $fmt($statement['revenue']['rent_collected']) }}</td></tr>
                <tr><td class="val-muted">Utilities collected</td><td class="right num">{{ $fmt($statement['revenue']['utilities_collected']) }}</td></tr>
                <tr><td class="val-muted">Other invoices collected</td><td class="right num">{{ $fmt($statement['revenue']['other_collected']) }}</td></tr>
                <tr><td class="val-muted">EWA collected from tenants</td><td class="right num">{{ $fmt($statement['revenue']['ewa_collected']) }}</td></tr>
                <tr class="subtotal-row"><td>Total Revenue</td><td class="right num">{{ $fmt($statement['total_revenue']) }}</td></tr>

                <tr><td class="val-muted">EWA charges not recovered from tenant</td><td class="right num">{{ $fmt($statement['expenses']['ewa_landlord_expense']) }}</td></tr>
                <tr><td class="val-muted">Approved maintenance cost</td><td class="right num">{{ $fmt($statement['expenses']['maintenance_expense']) }}</td></tr>
                <tr class="subtotal-row"><td>Total Expense</td><td class="right num">{{ $fmt($statement['total_expense']) }}</td></tr>

                <tr class="grand-total-row">
                    <td>Net {{ $isProfit ? 'Profit' : 'Loss' }}</td>
                    <td class="right num" style="color:{{ $isProfit ? 'var(--tone-success-fg)' : 'var(--tone-danger-fg)' }}">{{ $fmt(abs($net)) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@if($breakdown->isNotEmpty())
<div class="table-card">
    <div class="table-card-title">By Building</div>
    <div class="table-wrap is-scroll">
        <table>
            <thead>
                <tr>
                    <th>Building</th>
                    <th class="right">Revenue (BHD)</th>
                    <th class="right">Expense (BHD)</th>
                    <th class="right">Net (BHD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown as $row)
                <tr>
                    <td class="cell-title">
                        <a href="{{ route('reports.profit-loss', ['building_id' => $row['building']->id, 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}" style="color:var(--text-primary);text-decoration:none">
                            {{ $row['building']->property_name }}
                        </a>
                    </td>
                    <td class="right num">{{ $fmt($row['total_revenue']) }}</td>
                    <td class="right num">{{ $fmt($row['total_expense']) }}</td>
                    <td class="right num num-strong" style="color:{{ $row['net_profit'] >= 0 ? 'var(--tone-success-fg)' : 'var(--tone-danger-fg)' }}">{{ $fmt($row['net_profit']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="section-note">
    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
    Cash-basis for revenue (payments actually received). Expenses are recognised when incurred — EWA bills on their reading date, maintenance costs once department-head approved — since the system doesn't track a "paid to EWA authority" or "paid to contractor" event. Maintenance costs currently only roll up by building, not by tenant, since maintenance requests aren't linked to a tenant record.
</div>

@include('reports._pdf-preview-modal')

@endsection
