@extends('layouts.admin')

@section('title', 'Collection Report')
@section('topbar-title', 'Reports')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Collection Report</h1>
        <p class="page-header-sub">Every rent and EWA payment received in a date range, receipt by receipt</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        <button type="button" class="btn btn-outline"
                onclick="openReportPdf('{{ route('reports.collection.pdf', request()->only(['date_from','date_to'])) }}', 'Collection Report')">
            <i class="fa-solid fa-eye"></i> Preview
        </button>
        <a href="{{ route('reports.collection.pdf', request()->only(['date_from','date_to'])) }}"
           target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        <a href="{{ route('reports.collection.export', request()->only(['date_from','date_to'])) }}"
           class="btn btn-primary"><i class="fa-solid fa-file-excel"></i> Export XLSX</a>
    </div>
</div>

<form method="GET" action="{{ route('reports.collection') }}" class="filter-card">
    <div class="filter-bar">
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

@if($rows->isEmpty())
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-receipt"></i></div>
        <h4>No payments in this range</h4>
        <p>Widen the dates above to look further back.</p>
    </div>
</div>
@else
<div class="table-card">
    <div class="table-wrap is-scroll" style="--table-min:920px">
        <table>
            <thead>
                <tr>
                    <th>Receipt No</th>
                    <th>Date</th>
                    <th>Cheque No</th>
                    <th>Cheque Date</th>
                    <th>Tenant / Ledger Name</th>
                    <th>Particulars</th>
                    <th class="right">Amount (BHD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td class="cell-title">{{ $row['receipt_no'] }}</td>
                    <td class="nowrap">{{ $row['date']->format('d M Y') }}</td>
                    <td class="val-muted">{{ $row['cheque_number'] ?: '—' }}</td>
                    <td class="nowrap val-muted">{{ $row['cheque_date']?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $row['tenant_name'] }}</td>
                    <td class="val-muted">{{ $row['particulars'] }}</td>
                    <td class="right num num-strong">{{ number_format($row['amount'], 3) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="right" colspan="6">Total Collected</td>
                    <td class="right num">{{ number_format($total, 3) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
