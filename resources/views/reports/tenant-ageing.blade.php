@extends('layouts.admin')

@section('title', 'Tenant Ageing')
@section('topbar-title', 'Reports')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Tenant Ageing</h1>
        <p class="page-header-sub">Outstanding bills for one tenant, split by how overdue they are</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        @if($tenant)
        <button type="button" class="btn btn-outline"
                onclick="openReportPdf('{{ route('reports.tenant-ageing.pdf', request()->only(['tenant_id','date_from','date_to'])) }}', 'Tenant Ageing — {{ $tenant->name }}')">
            <i class="fa-solid fa-eye"></i> Preview
        </button>
        <a href="{{ route('reports.tenant-ageing.pdf', request()->only(['tenant_id','date_from','date_to'])) }}"
           target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        <a href="{{ route('reports.tenant-ageing.export', request()->only(['tenant_id','date_from','date_to'])) }}"
           class="btn btn-primary"><i class="fa-solid fa-file-excel"></i> Export XLSX</a>
        @endif
    </div>
</div>

<form method="GET" action="{{ route('reports.tenant-ageing') }}" class="filter-card">
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
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Ageing</button>
        </div>
    </div>
</form>

@if(!$tenant)
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></div>
        <h4>Pick a tenant</h4>
        <p>Choose a tenant above to see how long their balance has been outstanding.</p>
    </div>
</div>
@elseif($rows->isEmpty())
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
        <h4>Nothing outstanding</h4>
        <p>{{ $tenant->name }} has no unpaid bills in this range.</p>
    </div>
</div>
@else
@php
    $lt60 = $rows->where('bucket', 'lt60')->sum('pending_amount');
    $b60120 = $rows->where('bucket', 'b60_120')->sum('pending_amount');
    $gt120 = $rows->where('bucket', 'gt120')->sum('pending_amount');
@endphp
<div class="table-card">
    <div class="table-wrap is-scroll">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Bill Ref.</th>
                    <th>Description</th>
                    <th class="right">Opening (BHD)</th>
                    <th class="right">Pending (BHD)</th>
                    <th class="right" style="color:#059669">&lt; 60 Days</th>
                    <th class="right" style="color:#D97706">60&ndash;120 Days</th>
                    <th class="right" style="color:#DC2626">&gt; 120 Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td class="nowrap">{{ $row['date']->format('d M Y') }}</td>
                    <td class="cell-title">{{ $row['bill_ref'] }}</td>
                    <td class="val-muted">{{ $row['description'] }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($row['opening_amount']) }}</td>
                    <td class="right cell-title" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}</td>
                    <td class="right val-positive" style="font-family:'Outfit',sans-serif">{{ $row['bucket'] === 'lt60'    ? \App\Support\MoneyFormat::crDr($row['pending_amount']) : '—' }}</td>
                    <td class="right val-warning" style="font-family:'Outfit',sans-serif">{{ $row['bucket'] === 'b60_120' ? \App\Support\MoneyFormat::crDr($row['pending_amount']) : '—' }}</td>
                    <td class="right val-negative" style="font-family:'Outfit',sans-serif">{{ $row['bucket'] === 'gt120'   ? \App\Support\MoneyFormat::crDr($row['pending_amount']) : '—' }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="right" colspan="3">Total</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($rows->sum('opening_amount')) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($rows->sum('pending_amount')) }}</td>
                    <td class="right val-positive" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($lt60) }}</td>
                    <td class="right val-warning" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($b60120) }}</td>
                    <td class="right val-negative" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($gt120) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
