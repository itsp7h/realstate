@extends('layouts.pdf')

@section('pdf-title', 'Tenant Statement')
@section('report-title', 'Tenant Statement')
@section('report-sub')
{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}
@endsection

@section('content')

<div class="report-tenant">{{ $tenant->name }} @if($tenant->tenant_code)({{ $tenant->tenant_code }})@endif</div>

@if($rows->isEmpty())
    <x-pdf.empty>No outstanding bills in this date range.</x-pdf.empty>
@else
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:11%">DATE</th>
            <th style="width:14%">BILL REF</th>
            <th style="width:26%">DESCRIPTION</th>
            <th class="right" style="width:13%">OPENING</th>
            <th class="right" style="width:13%">PENDING</th>
            <th style="width:11%">DUE ON</th>
            <th class="right" style="width:12%">OVERDUE</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['date']->format('d-M-Y') }}</td>
            <td><x-pdf.text :value="$row['bill_ref']" :limit="14" /></td>
            <td><x-pdf.text :value="$row['description']" :limit="26" /></td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($row['opening_amount']) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}</td>
            <td>{{ $row['due_on']->format('d-M-Y') }}</td>
            <td class="right {{ $row['overdue_days'] > 0 ? 'overdue' : '' }}">{{ $row['overdue_days'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="pdf-total">
            <td colspan="4">Total Outstanding</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($total) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
