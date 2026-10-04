@extends('layouts.pdf')

@section('pdf-title', 'Tenant Ledger')
@section('report-title', 'Tenant Ledger')
@section('report-sub')
{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}
@endsection

@section('content')

<div class="report-tenant">{{ $tenant->name }} @if($tenant->tenant_code)({{ $tenant->tenant_code }})@endif</div>

@if($rows->isEmpty())
    <x-pdf.empty>No transactions in this date range.</x-pdf.empty>
@else
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:12%">DATE</th>
            <th style="width:18%">REFERENCE</th>
            <th style="width:28%">DESCRIPTION</th>
            <th class="right" style="width:14%">DEBIT</th>
            <th class="right" style="width:14%">CREDIT</th>
            <th class="right" style="width:14%">BALANCE</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['date']->format('d-M-Y') }}</td>
            <td><x-pdf.text :value="$row['bill_ref']" :limit="18" /></td>
            <td><x-pdf.text :value="$row['description']" :limit="28" /></td>
            <td class="right"><x-pdf.figure :value="$row['debit']" money /></td>
            <td class="right"><x-pdf.figure :value="$row['credit']" money /></td>
            <td class="right {{ $row['balance'] > 0.001 ? 'owing' : 'settled' }}">
                {{ number_format(abs($row['balance']), 3) }}{{ $row['balance'] > 0.001 ? ' Dr' : ($row['balance'] < -0.001 ? ' Cr' : '') }}
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="closing-row">
            <td colspan="5">Closing Balance</td>
            <td class="right {{ $rows->last()['balance'] > 0.001 ? 'owing' : 'settled' }}">
                {{ number_format(abs($rows->last()['balance']), 3) }}{{ $rows->last()['balance'] > 0.001 ? ' Dr' : ($rows->last()['balance'] < -0.001 ? ' Cr' : '') }}
            </td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
