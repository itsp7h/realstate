@extends('layouts.pdf')

@section('pdf-title', 'VAT return')
@section('report-title')
VAT RETURN{{ $building ? ' — ' . strtoupper($building->property_name) : '' }}
@endsection
@section('report-sub')
{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}
@endsection

@section('content')

@if($rows->isEmpty())
    <x-pdf.empty>No invoices or EWA bills in this date range.</x-pdf.empty>
@else
<table class="pdf-table is-dense">
    <thead>
        <tr>
            <th style="width:10%">DATE</th>
            <th style="width:13%">REFERENCE</th>
            <th style="width:15%">CUSTOMER</th>
            <th style="width:15%">DESCRIPTION</th>
            <th class="right" style="width:11%">TAXABLE</th>
            <th class="right" style="width:8%">VAT</th>
            <th class="right" style="width:11%">TOTAL</th>
            <th style="width:9%">TAX CODE</th>
            <th style="width:8%">SUPPLY</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['invoice_date']->format('d-M-Y') }}</td>
            <td><x-pdf.text :value="$row['reference']" :limit="13" /></td>
            <td><x-pdf.text :value="$row['customer_name']" :limit="15" /></td>
            <td><x-pdf.text :value="$row['description']" :limit="15" /></td>
            <td class="right">{{ number_format($row['taxable_amount'], 3) }}</td>
            <td class="right">{{ number_format($row['vat_amount'], 3) }}</td>
            <td class="right">{{ number_format($row['total_incl_vat'], 3) }}</td>
            <td><x-pdf.text :value="$row['tax_code']" :limit="9" /></td>
            <td><x-pdf.text :value="$row['place_of_supply']" :limit="8" /></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="pdf-total">
            <td colspan="4" class="right">Total</td>
            <td class="right">{{ number_format($totals['taxable_amount'], 3) }}</td>
            <td class="right">{{ number_format($totals['vat_amount'], 3) }}</td>
            <td class="right">{{ number_format($totals['total_incl_vat'], 3) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
