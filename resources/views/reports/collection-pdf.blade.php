@extends('layouts.pdf')

@section('pdf-title', 'Collection Report')
@section('report-title', 'Collection Report')
@section('report-sub')
{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}
@endsection

@section('content')

@if($rows->isEmpty())
    <x-pdf.empty>No payments received in this date range.</x-pdf.empty>
@else
<table class="pdf-table">
    <thead>
        <tr>
            <th style="width:11%">RECEIPT</th>
            <th style="width:10%">DATE</th>
            <th style="width:11%">CHEQUE</th>
            <th style="width:11%">CHQ DATE</th>
            <th style="width:20%">TENANT</th>
            <th style="width:22%">PARTICULARS</th>
            <th class="right" style="width:15%">AMOUNT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td><x-pdf.text :value="$row['receipt_no']" :limit="11" /></td>
            <td>{{ $row['date']->format('d-M-Y') }}</td>
            <td><x-pdf.text :value="$row['cheque_number']" :limit="18" /></td>
            <td>@if($row['cheque_date']){{ $row['cheque_date']->format('d-M-Y') }}@else<span class="pdf-empty">&mdash;</span>@endif</td>
            <td><x-pdf.text :value="$row['tenant_name']" :limit="20" /></td>
            <td><x-pdf.text :value="$row['particulars']" :limit="22" /></td>
            <td class="right">{{ number_format($row['amount'], 3) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="pdf-total">
            <td colspan="6" class="right">Total Collected</td>
            <td class="right">{{ number_format($total, 3) }}</td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
