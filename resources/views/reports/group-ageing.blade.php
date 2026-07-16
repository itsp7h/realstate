@extends('layouts.admin')

@section('title', 'Group Outstanding — Ageing')
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
.bucket-lt60   { color: #059669; }
.bucket-b60120 { color: #D97706; }
.bucket-gt120  { color: #DC2626; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Group Outstanding — Ageing</h1>
        <p class="page-header-sub">Every tenant with an outstanding balance, bucketed by how overdue it is</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
        <button type="button" class="btn btn-outline"
                onclick="openReportPdf('{{ route('reports.group-ageing.pdf', request()->only(['date_from','date_to'])) }}', 'Group Outstanding — Ageing')">
            <i class="fa-solid fa-eye"></i> Preview
        </button>
        <a href="{{ route('reports.group-ageing.pdf', request()->only(['date_from','date_to'])) }}"
           target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
        <a href="{{ route('reports.group-ageing.export', request()->only(['date_from','date_to'])) }}"
           class="btn btn-primary"><i class="fa-solid fa-file-excel"></i> Export XLSX</a>
    </div>
</div>

<form method="GET" action="{{ route('reports.group-ageing') }}" class="filter-bar">
    <input type="date" name="date_from" value="{{ $from->format('Y-m-d') }}" title="From">
    <input type="date" name="date_to"   value="{{ $to->format('Y-m-d') }}"   title="To">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View</button>
</form>

@if($groups->isEmpty())
<div class="table-card"><div class="report-empty"><i class="fa-solid fa-circle-check"></i>No outstanding balances in this date range.</div></div>
@else
<div class="table-card">
    <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:720px">
        <thead>
            <tr style="background:var(--page-bg)">
                <th style="text-align:left;padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Tenant</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Pending Bills (BHD)</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#059669">&lt; 60 Days</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#D97706">60&ndash;120 Days</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#DC2626">&gt; 120 Days</th>
                <th class="right" style="padding:10px 14px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">On Account</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $g)
            <tr style="border-bottom:1px solid var(--card-border)">
                <td style="padding:9px 14px;font-weight:600">
                    <a href="{{ route('reports.tenant-ageing', ['tenant_id' => $g['tenant']->id, 'date_from' => $from->format('Y-m-d'), 'date_to' => $to->format('Y-m-d')]) }}" style="color:var(--text-primary);text-decoration:none">
                        {{ $g['tenant']->name }}
                    </a>
                </td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif;font-weight:700">{{ \App\Support\MoneyFormat::crDr($g['pending']) }}</td>
                <td class="right bucket-lt60"   style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($g['lt60']) }}</td>
                <td class="right bucket-b60120" style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($g['b60_120']) }}</td>
                <td class="right bucket-gt120"  style="padding:9px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($g['gt120']) }}</td>
                <td class="right" style="padding:9px 14px;font-family:'Outfit',sans-serif;color:var(--text-muted)">{{ \App\Support\MoneyFormat::crDr(-$g['on_account']) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td style="padding:12px 14px">Grand Total</td>
                <td class="right" style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($groups->sum('pending')) }}</td>
                <td class="right bucket-lt60"   style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($groups->sum('lt60')) }}</td>
                <td class="right bucket-b60120" style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($groups->sum('b60_120')) }}</td>
                <td class="right bucket-gt120"  style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($groups->sum('gt120')) }}</td>
                <td class="right" style="padding:12px 14px;font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr(-$groups->sum('on_account')) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

@include('reports._pdf-preview-modal')

@endsection
