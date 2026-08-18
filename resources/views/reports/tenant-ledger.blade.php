@extends('layouts.admin')

@section('title', 'Tenant Ledger')
@section('topbar-title', 'Reports')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Tenant Ledger</h1>
        <p class="page-header-sub">Full transaction history for a single tenant, with a running balance</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        @if($tenant)
        <button type="button" class="btn btn-outline"
                onclick="openReportPdf('{{ route('reports.tenant-ledger.pdf', request()->only(['tenant_id','date_from','date_to'])) }}', 'Tenant Ledger — {{ $tenant->name }}')">
            <i class="fa-solid fa-eye"></i> Preview
        </button>
        <a href="{{ route('reports.tenant-ledger.pdf', request()->only(['tenant_id','date_from','date_to'])) }}"
           target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        <a href="{{ route('reports.tenant-ledger.export', request()->only(['tenant_id','date_from','date_to'])) }}"
           class="btn btn-primary"><i class="fa-solid fa-file-excel"></i> Export XLSX</a>
        @endif
    </div>
</div>

<form method="GET" action="{{ route('reports.tenant-ledger') }}" class="filter-card">
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
            <input type="date" id="f_date_from" name="date_from" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="filter-group">
            <label for="f_date_to">To</label>
            <input type="date" id="f_date_to" name="date_to"   value="{{ $to->format('Y-m-d') }}">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Ledger</button>
        </div>
    </div>
</form>

@if(!$tenant)
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-book"></i></div>
        <h4>Pick a tenant</h4>
        <p>Choose a tenant above to see every charge and payment on their account.</p>
    </div>
</div>
@elseif($rows->isEmpty())
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
        <h4>No transactions</h4>
        <p>Nothing was charged to or paid by {{ $tenant->name }} in this range.</p>
    </div>
</div>
@else
<div class="table-card">
    <div class="table-wrap is-scroll">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th class="right">Debit (BHD)</th>
                    <th class="right">Credit (BHD)</th>
                    <th class="right">Balance (BHD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td class="nowrap">{{ $row['date']->format('d M Y') }}</td>
                    <td class="cell-title">{{ $row['bill_ref'] }}</td>
                    <td class="val-muted">{{ $row['description'] }}</td>
                    <td class="right num">{{ $row['debit'] > 0.001 ? number_format($row['debit'], 3) : '—' }}</td>
                    <td class="right num">{{ $row['credit'] > 0.001 ? number_format($row['credit'], 3) : '—' }}</td>
                    <td class="right num {{ $row['balance'] > 0.001 ? 'val-negative' : 'val-positive' }}">
                        {{ number_format(abs($row['balance']), 3) }}{{ $row['balance'] > 0.001 ? ' Dr' : ($row['balance'] < -0.001 ? ' Cr' : '') }}
                    </td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="right cell-title" colspan="5">Closing Balance</td>
                    <td class="right num {{ $rows->last()['balance'] > 0.001 ? 'val-negative' : 'val-positive' }}">
                        {{ number_format(abs($rows->last()['balance']), 3) }}{{ $rows->last()['balance'] > 0.001 ? ' Dr' : ($rows->last()['balance'] < -0.001 ? ' Cr' : '') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
