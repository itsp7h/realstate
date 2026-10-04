@extends('layouts.pdf')

@section('pdf-title', 'Tenant Ageing Report')
@section('report-title', 'Tenant Ageing Report')
@section('report-sub')
{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}
@endsection

@section('content')

<div class="report-tenant">{{ $tenant->name }} @if($tenant->tenant_code)({{ $tenant->tenant_code }})@endif</div>

@php
    $lt60   = $rows->where('bucket', 'lt60')->sum('pending_amount');
    $b60120 = $rows->where('bucket', 'b60_120')->sum('pending_amount');
    $gt120  = $rows->where('bucket', 'gt120')->sum('pending_amount');
@endphp

@if($rows->isEmpty())
    <x-pdf.empty>No outstanding bills in this date range.</x-pdf.empty>
@else
<table class="pdf-table is-dense">
    <thead>
        <tr>
            <th style="width:11%">DATE</th>
            <th style="width:13%">BILL REF</th>
            <th style="width:22%">DESCRIPTION</th>
            <th class="right" style="width:11%">OPENING</th>
            <th class="right" style="width:11%">PENDING</th>
            <th class="right" style="width:10%">&lt; 60 D</th>
            <th class="right" style="width:11%">60–120 D</th>
            <th class="right" style="width:11%">&gt; 120 D</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['date']->format('d-M-Y') }}</td>
            <td><x-pdf.text :value="$row['bill_ref']" :limit="13" /></td>
            <td><x-pdf.text :value="$row['description']" :limit="22" /></td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($row['opening_amount']) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}</td>
            <td class="right bucket-lt60">@if($row['bucket'] === 'lt60'){{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}@else<span class="pdf-empty">&mdash;</span>@endif</td>
            <td class="right bucket-b60120">@if($row['bucket'] === 'b60_120'){{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}@else<span class="pdf-empty">&mdash;</span>@endif</td>
            <td class="right bucket-gt120">@if($row['bucket'] === 'gt120'){{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}@else<span class="pdf-empty">&mdash;</span>@endif</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="pdf-total">
            <td colspan="3">Total</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($rows->sum('opening_amount')) }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($rows->sum('pending_amount')) }}</td>
            <td class="right bucket-lt60">{{ \App\Support\MoneyFormat::crDr($lt60) }}</td>
            <td class="right bucket-b60120">{{ \App\Support\MoneyFormat::crDr($b60120) }}</td>
            <td class="right bucket-gt120">{{ \App\Support\MoneyFormat::crDr($gt120) }}</td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
