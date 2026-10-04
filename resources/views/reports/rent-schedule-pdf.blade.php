@extends('layouts.pdf')

@section('pdf-title', 'Rent Payment Schedule')
@section('report-title', 'Rent Payment Schedule')
@section('report-sub')
{{-- Guarded: with no rent-bearing contract the schedule is empty, and
     ->first()['month'] on an empty collection took the whole export down with
     "Call to a member function format() on null". --}}
@if($rows->isNotEmpty()){{ $rows->first()['month']->format('M Y') }} to {{ $rows->last()['month']->format('M Y') }}@else No rent-bearing period @endif
@endsection

@section('content')

{{-- No x-pdf.text or pdf-empty in this table on purpose: every cell is a
     formatted figure, a formatted month, or one of four fixed status labels, so
     there is no value here that can be absent or overrun its column. --}}

<div class="report-tenant">{{ $tenant->name }} @if($tenant->tenant_code)({{ $tenant->tenant_code }})@endif</div>

@if($rows->isEmpty())
    <x-pdf.empty>No rent-bearing lease contracts on file.</x-pdf.empty>
@else
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:16%">MONTH</th>
            <th class="right" style="width:15%">EXPECTED</th>
            <th class="right" style="width:15%">INVOICED</th>
            <th class="right" style="width:15%">PAID</th>
            <th class="right" style="width:15%">REMAINING</th>
            <th style="width:24%">STATUS</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['month']->format('F Y') }}</td>
            <td class="right">{{ number_format($row['expected'], 3) }}</td>
            <td class="right">{{ number_format($row['invoiced'], 3) }}</td>
            <td class="right">{{ number_format($row['paid'], 3) }}</td>
            <td class="right">{{ number_format($row['remaining'], 3) }}</td>
            <td class="status-{{ $row['status'] }}">
                {{ match($row['status']) {
                    'paid'         => 'Paid',
                    'partial'      => 'Partially Paid',
                    'unpaid'       => 'Unpaid',
                    'not_invoiced' => 'Not Invoiced',
                } }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@endsection
