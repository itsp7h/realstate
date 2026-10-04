@extends('layouts.admin')

@section('title', 'Tenant Financial Summary')
@section('topbar-title', 'Reports')

@section('content')

@section('page-title', 'Tenant Financial Summary')
@section('page-subtitle')
    Every tenant's balance for the period &mdash; carried-forward opening balance, what was billed and received, and the resulting net balance
@endsection
@section('page-back')
    <a href="{{ route('reports.index') }}" class="btn btn-outline" aria-label="Back to Reports">
        <i class="fa-solid fa-arrow-left"></i><span class="pagehead-back-label"> Reports</span>
    </a>
@endsection

@section('page-actions')
    <button type="button" class="btn btn-outline"
            onclick="openReportPdf('{{ route('reports.financial-summary.pdf', request()->only(['date_from','date_to'])) }}', 'Tenant Financial Summary')">
        <i class="fa-solid fa-eye"></i> Preview
    </button>
    <a href="{{ route('reports.financial-summary.pdf', request()->only(['date_from','date_to'])) }}"
       target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    <x-export-button href="{{ route('reports.financial-summary.export', request()->only(['date_from','date_to'])) }}" label="Export XLSX" icon="fa-file-excel" />
@endsection


<form method="GET" action="{{ route('reports.financial-summary') }}" class="filter-card">
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
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
        <h4>Nothing to summarise</h4>
        <p>No tenant had activity or a balance in this range. Try wider dates.</p>
    </div>
</div>
@else
<div class="table-card">
    <div class="table-wrap is-scroll" style="--table-min:720px">
        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th class="right">Opening Balance (BHD)</th>
                    <th class="right">Amount (BHD)</th>
                    <th class="right">Received Amount (BHD)</th>
                    <th class="right">Net Balance (BHD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td class="cell-title">
                        <a href="{{ route('reports.tenant-ledger', ['tenant_id' => $r['tenant']->id, 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}" style="color:var(--text-primary);text-decoration:none">
                            {{ $r['tenant']->name }}
                        </a>
                    </td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($r['opening_balance']) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($r['period_amount']) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr(-$r['period_received']) }}</td>
                    <td class="right {{ $r['net_balance'] > 0.001 ? 'val-negative' : ($r['net_balance'] < -0.001 ? 'val-positive' : '') }} cell-title" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($r['net_balance']) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>Grand Total</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($rows->sum('opening_balance')) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($rows->sum('period_amount')) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr(-$rows->sum('period_received')) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($rows->sum('net_balance')) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
