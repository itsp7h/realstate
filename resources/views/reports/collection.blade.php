@extends('layouts.admin')

@section('title', 'Collection Report')
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
.money-col { font-family: 'Outfit', sans-serif; }
</style>
@endpush

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

<form method="GET" action="{{ route('reports.collection') }}" class="filter-bar">
    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" title="From">
    <input type="date" name="date_to"   value="{{ $to->format('Y-m-d') }}"   title="To">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View</button>
</form>

@if($rows->isEmpty())
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-receipt"></i>No payments received in this date range.</div></div>
@else
<div class="table-card">
    <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:920px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Receipt No</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Date</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Cheque No</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Cheque Date</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Tenant / Ledger Name</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Particulars</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Amount (BHD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr style="border-bottom:1px solid var(--card-border)">
                <td style="padding:9px 14px;font-weight:600">{{ $row['receipt_no'] }}</td>
                <td style="padding:9px 14px;white-space:nowrap">{{ $row['date']->format('d M Y') }}</td>
                <td style="padding:9px 14px;color:var(--text-muted)">{{ $row['cheque_number'] ?: '—' }}</td>
                <td style="padding:9px 14px;white-space:nowrap;color:var(--text-muted)">{{ $row['cheque_date']?->format('d M Y') ?? '—' }}</td>
                <td style="padding:9px 14px">{{ $row['tenant_name'] }}</td>
                <td style="padding:9px 14px;color:var(--text-muted)">{{ $row['particulars'] }}</td>
                <td class="right money-col" style="padding:9px 14px;font-weight:700">{{ number_format($row['amount'], 3) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" style="padding:12px 14px;text-align:right">Total Collected</td>
                <td class="right money-col" style="padding:12px 14px">{{ number_format($total, 3) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
