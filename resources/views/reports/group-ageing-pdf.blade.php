@extends('layouts.pdf')

@section('pdf-title', 'Group ageing report')
@section('report-title')
Group Outstanding &mdash; Ageing Report
@endsection
@section('report-sub')
{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}
@endsection

@section('content')

@if($groups->isEmpty())
    <x-pdf.empty>No outstanding balances in this date range.</x-pdf.empty>
@else
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:30%">TENANT</th>
            <th class="right" style="width:15%">PENDING</th>
            <th class="right" style="width:13%">&lt; 60 D</th>
            <th class="right" style="width:13%">60–120 D</th>
            <th class="right" style="width:13%">&gt; 120 D</th>
            <th class="right" style="width:16%">ON ACCOUNT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($groups as $g)
        <tr>
            <td><x-pdf.text :value="$g['tenant']->name" :limit="30" /></td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($g['pending']) }}</td>
            <td class="right bucket-lt60">{{ \App\Support\MoneyFormat::crDr($g['lt60']) }}</td>
            <td class="right bucket-b60120">{{ \App\Support\MoneyFormat::crDr($g['b60_120']) }}</td>
            <td class="right bucket-gt120">{{ \App\Support\MoneyFormat::crDr($g['gt120']) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr(-$g['on_account']) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="pdf-total">
            <td>Grand Total</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($groups->sum('pending')) }}</td>
            <td class="right bucket-lt60">{{ \App\Support\MoneyFormat::crDr($groups->sum('lt60')) }}</td>
            <td class="right bucket-b60120">{{ \App\Support\MoneyFormat::crDr($groups->sum('b60_120')) }}</td>
            <td class="right bucket-gt120">{{ \App\Support\MoneyFormat::crDr($groups->sum('gt120')) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr(-$groups->sum('on_account')) }}</td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
