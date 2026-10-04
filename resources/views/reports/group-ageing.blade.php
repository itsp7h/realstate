@extends('layouts.admin')

@section('title', 'Group Outstanding — Ageing')
@section('topbar-title', 'Reports')

@section('content')

@section('page-title', 'Group Outstanding — Ageing')
@section('page-subtitle', 'Every tenant with an outstanding balance, bucketed by how overdue it is')
@section('page-back')
    <a href="{{ route('reports.index') }}" class="btn btn-outline" aria-label="Back to Reports">
        <i class="fa-solid fa-arrow-left"></i><span class="pagehead-back-label"> Reports</span>
    </a>
@endsection

@section('page-actions')
    <button type="button" class="btn btn-outline"
            onclick="openReportPdf('{{ route('reports.group-ageing.pdf', request()->only(['date_from','date_to'])) }}', 'Group Outstanding — Ageing')">
        <i class="fa-solid fa-eye"></i> Preview
    </button>
    <a href="{{ route('reports.group-ageing.pdf', request()->only(['date_from','date_to'])) }}"
       target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    <x-export-button href="{{ route('reports.group-ageing.export', request()->only(['date_from','date_to'])) }}" label="Export XLSX" icon="fa-file-excel" />
@endsection


<form method="GET" action="{{ route('reports.group-ageing') }}" class="filter-card">
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

@if($groups->isEmpty())
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
        <h4>Everything is settled</h4>
        <p>No group carried an outstanding balance in this range.</p>
    </div>
</div>
@else
<div class="table-card">
    <div class="table-wrap is-scroll" style="--table-min:720px">
        <table>
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th class="right">Pending Bills (BHD)</th>
                    <th class="right" style="color:var(--tone-success-fg)">&lt; 60 Days</th>
                    <th class="right" style="color:var(--tone-warning-fg)">60&ndash;120 Days</th>
                    <th class="right" style="color:var(--tone-danger-fg)">&gt; 120 Days</th>
                    <th class="right">On Account</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $g)
                <tr>
                    <td class="cell-title">
                        <a href="{{ route('reports.tenant-ageing', ['tenant_id' => $g['tenant']->id, 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}" style="color:var(--text-primary);text-decoration:none">
                            {{ $g['tenant']->name }}
                        </a>
                    </td>
                    <td class="right cell-title" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($g['pending']) }}</td>
                    <td class="right val-positive" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($g['lt60']) }}</td>
                    <td class="right val-warning" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($g['b60_120']) }}</td>
                    <td class="right val-negative" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($g['gt120']) }}</td>
                    <td class="right val-muted" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr(-$g['on_account']) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>Grand Total</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($groups->sum('pending')) }}</td>
                    <td class="right val-positive" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($groups->sum('lt60')) }}</td>
                    <td class="right val-warning" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($groups->sum('b60_120')) }}</td>
                    <td class="right val-negative" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($groups->sum('gt120')) }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr(-$groups->sum('on_account')) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
