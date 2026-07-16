@extends('layouts.admin')

@section('title', 'VAT Return')
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
.filter-bar select[name="building_id"] { flex: 1; min-width: 220px; }
.filter-bar input[type="date"] { min-width: 140px; }

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; }
.report-empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.report-empty i { font-size: 36px; display: block; margin-bottom: 12px; opacity: 0.3; }
.right { text-align: right; }
.money-col { font-family: 'Outfit', sans-serif; }
.total-row td { background: var(--page-bg); font-weight: 700; }

.tax-badge {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
    padding: 3px 8px; border-radius: 20px; white-space: nowrap;
}
.tax-badge-exempt { color: var(--text-muted); background: var(--page-bg); border: 1px solid var(--card-border); }
.tax-badge-standard { color: var(--accent); background: var(--accent-dim); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">VAT Return</h1>
        <p class="page-header-sub">Invoice-level VAT schedule, ready to hand to the accountant for filing</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        @if($rows->isNotEmpty())
        <button type="button" class="btn btn-outline"
                onclick="openReportPdf('{{ route('reports.vat-return.pdf', request()->only(['building_id','date_from','date_to'])) }}', 'VAT Return{{ $building ? ' — '.$building->property_name : '' }}')">
            <i class="fa-solid fa-eye"></i> Preview
        </button>
        <a href="{{ route('reports.vat-return.pdf', request()->only(['building_id','date_from','date_to'])) }}"
           target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        <a href="{{ route('reports.vat-return.export', request()->only(['building_id','date_from','date_to'])) }}"
           class="btn btn-primary"><i class="fa-solid fa-file-excel"></i> Export XLSX</a>
        @endif
    </div>
</div>

<form method="GET" action="{{ route('reports.vat-return') }}" class="filter-bar">
    <select name="building_id">
        <option value="">All properties</option>
        @foreach($buildings as $b)
        <option value="{{ $b->id }}" {{ $buildingId === $b->id ? 'selected' : '' }}>{{ $b->property_name }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" title="From">
    <input type="date" name="date_to"   value="{{ $to->format('Y-m-d') }}"   title="To">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Schedule</button>
</form>

@if($rows->isEmpty())
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-file-invoice-dollar"></i>No invoices or EWA bills in this date range.</div></div>
@else
<div class="table-card">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Invoice Date</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Reference</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Customer</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Description</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Taxable (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">VAT (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Total (BHD)</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Tax Code</th>
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Place of Supply</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr style="border-bottom:1px solid var(--card-border)">
                <td style="padding:9px 14px;white-space:nowrap">{{ $row['invoice_date']->format('d M Y') }}</td>
                <td style="padding:9px 14px;font-weight:600">{{ $row['reference'] }}</td>
                <td style="padding:9px 14px">{{ $row['customer_name'] }}</td>
                <td style="padding:9px 14px;color:var(--text-muted)">{{ $row['description'] }}</td>
                <td class="right money-col" style="padding:9px 14px">{{ number_format($row['taxable_amount'], 3) }}</td>
                <td class="right money-col" style="padding:9px 14px">{{ number_format($row['vat_amount'], 3) }}</td>
                <td class="right money-col" style="padding:9px 14px;font-weight:700">{{ number_format($row['total_incl_vat'], 3) }}</td>
                <td style="padding:9px 14px">
                    <span class="tax-badge {{ $row['tax_code'] === 'EXM-S' ? 'tax-badge-exempt' : 'tax-badge-standard' }}">{{ $row['tax_code'] }}</span>
                </td>
                <td style="padding:9px 14px;color:var(--text-muted)">{{ $row['place_of_supply'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="padding:12px 14px;text-align:right">Total</td>
                <td class="right money-col" style="padding:12px 14px">{{ number_format($totals['taxable_amount'], 3) }}</td>
                <td class="right money-col" style="padding:12px 14px">{{ number_format($totals['vat_amount'], 3) }}</td>
                <td class="right money-col" style="padding:12px 14px">{{ number_format($totals['total_incl_vat'], 3) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
