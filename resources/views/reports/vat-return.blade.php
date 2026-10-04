@extends('layouts.admin')

@section('title', 'VAT Return')
@section('topbar-title', 'Reports')

@section('content')

@section('page-title', 'VAT Return')
@section('page-subtitle', 'Invoice-level VAT schedule, ready to hand to the accountant for filing')
@section('page-back')
    <a href="{{ route('reports.index') }}" class="btn btn-outline" aria-label="Back to Reports">
        <i class="fa-solid fa-arrow-left"></i><span class="pagehead-back-label"> Reports</span>
    </a>
@endsection

@section('page-actions')
    @if($rows->isNotEmpty())
    <button type="button" class="btn btn-outline"
            onclick="openReportPdf('{{ route('reports.vat-return.pdf', request()->only(['building_id','date_from','date_to'])) }}', 'VAT Return{{ $building ? ' — '.$building->property_name : '' }}')">
        <i class="fa-solid fa-eye"></i> Preview
    </button>
    <a href="{{ route('reports.vat-return.pdf', request()->only(['building_id','date_from','date_to'])) }}"
       target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    <x-export-button href="{{ route('reports.vat-return.export', request()->only(['building_id','date_from','date_to'])) }}" label="Export XLSX" icon="fa-file-excel" />
    @endif
@endsection


<form method="GET" action="{{ route('reports.vat-return') }}" class="filter-card">
    <div class="filter-bar">
        <div class="filter-group is-search">
            <label for="f_building_id">Property</label>
            <select id="f_building_id" name="building_id">
                <option value="">All properties</option>
                @foreach($buildings as $b)
                <option value="{{ $b->id }}" {{ $buildingId === $b->id ? 'selected' : '' }}>{{ $b->property_name }}</option>
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
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Schedule</button>
        </div>
    </div>
</form>

@if($rows->isEmpty())
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <h4>No taxable activity</h4>
        <p>No invoices or EWA bills fell in this range. Try wider dates.</p>
    </div>
</div>
@else
<div class="table-card">
    <div class="table-wrap is-scroll">
        <table>
            <thead>
                <tr>
                    <th>Invoice Date</th>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Description</th>
                    <th class="right">Taxable (BHD)</th>
                    <th class="right">VAT (BHD)</th>
                    <th class="right">Total (BHD)</th>
                    <th>Tax Code</th>
                    <th>Place of Supply</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td class="nowrap">{{ $row['invoice_date']->format('d M Y') }}</td>
                    <td class="cell-title">{{ $row['reference'] }}</td>
                    <td>{{ $row['customer_name'] }}</td>
                    <td class="val-muted">{{ $row['description'] }}</td>
                    <td class="right num">{{ number_format($row['taxable_amount'], 3) }}</td>
                    <td class="right num">{{ number_format($row['vat_amount'], 3) }}</td>
                    <td class="right num num-strong">{{ number_format($row['total_incl_vat'], 3) }}</td>
                    <td>
                        <span class="badge {{ $row['tax_code'] === 'EXM-S' ? 'badge-gray' : 'badge-gold' }}">{{ $row['tax_code'] }}</span>
                    </td>
                    <td class="val-muted">{{ $row['place_of_supply'] }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="right" colspan="4">Total</td>
                    <td class="right num">{{ number_format($totals['taxable_amount'], 3) }}</td>
                    <td class="right num">{{ number_format($totals['vat_amount'], 3) }}</td>
                    <td class="right num">{{ number_format($totals['total_incl_vat'], 3) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
