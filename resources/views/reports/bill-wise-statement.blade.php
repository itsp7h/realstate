@extends('layouts.admin')

@section('title', 'Bill-wise Statement')
@section('topbar-title', 'Reports')

@section('content')

@section('page-title', 'Bill-wise Statement')
@section('page-subtitle', 'One row per outstanding bill for a tenant, with due date and days overdue')
@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Reports</a>
    @if($tenant)
    <button type="button" class="btn btn-outline"
            onclick="openReportPdf('{{ route('reports.bill-wise-statement.pdf', request()->only(['tenant_id','date_from','date_to'])) }}', 'Bill-wise Statement — {{ $tenant->name }}')">
        <i class="fa-solid fa-eye"></i> Preview
    </button>
    <a href="{{ route('reports.bill-wise-statement.pdf', request()->only(['tenant_id','date_from','date_to'])) }}"
       target="_blank" class="btn btn-outline"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    <a href="{{ route('reports.bill-wise-statement.export', request()->only(['tenant_id','date_from','date_to'])) }}"
       class="btn btn-primary"><i class="fa-solid fa-file-excel"></i> Export XLSX</a>
    @endif
@endsection


<form method="GET" action="{{ route('reports.bill-wise-statement') }}" class="filter-card">
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
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> View Statement</button>
        </div>
    </div>
</form>

@if(!$tenant)
<div class="table-card">
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="fa-solid fa-file-invoice"></i></div>
        <h4>Pick a tenant</h4>
        <p>Choose a tenant above to build their bill-wise statement.</p>
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
<div class="table-card">
    <div class="table-wrap is-scroll">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Bill Ref.</th>
                    <th>Ref. / LPO No.</th>
                    <th class="right">Opening (BHD)</th>
                    <th class="right">Post-Dated (BHD)</th>
                    <th class="right">Final Balance (BHD)</th>
                    <th>Due On</th>
                    <th class="right">Overdue (days)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td class="nowrap">{{ $row['date']->format('d M Y') }}</td>
                    <td class="cell-title">{{ $row['bill_ref'] }}</td>
                    <td class="val-muted">{{ $row['description'] }}</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($row['opening_amount']) }}</td>
                    <td class="right val-muted" style="font-family:'Outfit',sans-serif">&mdash;</td>
                    <td class="right cell-title" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}</td>
                    <td class="nowrap">{{ $row['due_on']->format('d M Y') }}</td>
                    <td class="right">
                        <span class="{{ $row['overdue_days'] > 0 ? 'val-negative is-emphatic' : 'val-muted' }}">{{ $row['overdue_days'] }}</span>
                    </td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="right" colspan="5">Total</td>
                    <td class="right" style="font-family:'Outfit',sans-serif">{{ \App\Support\MoneyFormat::crDr($total) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

<div style="margin-top:20px;font-size:12px;color:var(--text-muted);max-width:640px">
    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
    "Post-Dated" cheques aren't tracked in the system yet, so that column always shows as empty.
</div>

@include('reports._pdf-preview-modal')

@endsection
