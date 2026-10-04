@extends('layouts.pdf')

@section('pdf-title', 'Tenant Financial Summary')
@section('report-title', 'Tenant Financial Summary')
@section('report-sub')
{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}
@endsection

@section('content')

@if($rows->isEmpty())
    <x-pdf.empty>No tenant activity or balance in this date range.</x-pdf.empty>
@else
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:32%">TENANT</th>
            <th class="right" style="width:17%">OPENING</th>
            <th class="right" style="width:17%">BILLED</th>
            <th class="right" style="width:17%">RECEIVED</th>
            <th class="right" style="width:17%">NET</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $r)
        <tr>
            <td><x-pdf.text :value="$r['tenant']->name" :limit="32" /></td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($r['opening_balance']) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($r['period_amount']) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr(-$r['period_received']) }}</td>
            <td class="right {{ $r['net_balance'] > 0.001 ? 'net-owing' : ($r['net_balance'] < -0.001 ? 'net-credit' : '') }}">{{ \App\Support\MoneyFormat::crDr($r['net_balance']) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="pdf-total">
            <td>Grand Total</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($rows->sum('opening_balance')) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($rows->sum('period_amount')) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr(-$rows->sum('period_received')) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($rows->sum('net_balance')) }}</td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
