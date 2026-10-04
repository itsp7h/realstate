@extends('layouts.pdf')

@section('pdf-title', 'Profit and loss statement')
@section('report-title')
Profit &amp; Loss Statement
@endsection
@section('report-sub')
@if($unit){{ $unit->unit_name }} &mdash; {{ $unit->building?->property_name ?? $unit->property_name }}
@elseif($building){{ $building->property_name }}
@elseif($tenant){{ $tenant->name }}
@else All buildings @endif
&middot; {{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}
@endsection

@php
    $net = $statement['net_profit'];
    $isProfit = $net >= 0;
    $fmt = fn ($v) => number_format($v, 3);
@endphp

@section('summary')
    {{-- The spec's KPI strip, replacing a hand-rolled three-cell div whose CSS
         lived in this file. --}}
    <x-pdf.summary :items="[
        ['label' => 'Total Revenue', 'value' => $fmt($statement['total_revenue'])],
        ['label' => 'Total Expense', 'value' => $fmt($statement['total_expense'])],
        ['label' => 'Net ' . ($isProfit ? 'Profit' : 'Loss'), 'value' => $fmt(abs($net))],
    ]" />
@endsection

@section('content')
{{-- The KPI figures live in the summary strip above; the hand-rolled stat
     divs that used to sit here were left behind by that migration and printed
     two bare numbers and two unclosed divs over the statement. --}}

<x-pdf.subsection label="Statement" :rows="9">
<table class="pdf-table">
    <thead>
        <tr><th style="width:72%">LINE ITEM</th><th class="right" style="width:28%">AMOUNT (BHD)</th></tr>
    </thead>
    <tbody>
        <tr><td>Rent collected</td><td class="right">{{ $fmt($statement['revenue']['rent_collected']) }}</td></tr>
        <tr><td>Utilities collected</td><td class="right">{{ $fmt($statement['revenue']['utilities_collected']) }}</td></tr>
        <tr><td>Other invoices collected</td><td class="right">{{ $fmt($statement['revenue']['other_collected']) }}</td></tr>
        <tr><td>EWA collected from tenants</td><td class="right">{{ $fmt($statement['revenue']['ewa_collected']) }}</td></tr>
        <tr><td>Manually recorded revenue</td><td class="right">{{ $fmt($statement['revenue']['manual_revenue']) }}</td></tr>
        <tr class="subtotal-row"><td>Total Revenue</td><td class="right">{{ $fmt($statement['total_revenue']) }}</td></tr>

        <tr><td>EWA charges not recovered from tenant</td><td class="right">{{ $fmt($statement['expenses']['ewa_landlord_expense']) }}</td></tr>
        <tr><td>Approved maintenance cost</td><td class="right">{{ $fmt($statement['expenses']['maintenance_expense']) }}</td></tr>
        <tr><td>Manually recorded expenses</td><td class="right">{{ $fmt($statement['expenses']['manual_expense']) }}</td></tr>
        <tr class="subtotal-row"><td>Total Expense</td><td class="right">{{ $fmt($statement['total_expense']) }}</td></tr>

    </tbody>
    {{-- The net line closes the statement, so it is the table's foot: bound to
         the row above it rather than free to open a page alone. --}}
    <tfoot>
        <tr class="net-row"><td>Net {{ $isProfit ? 'Profit' : 'Loss' }}</td><td class="right {{ $isProfit ? 'profit' : 'loss' }}">{{ $fmt(abs($net)) }}</td></tr>
    </tfoot>
</table>
</x-pdf.subsection>

{{-- Per-building breakdown, only when the statement spans more than one. --}}
@if($breakdown->isNotEmpty())
<x-pdf.subsection label="By property" :count="$breakdown->count()">
<table class="pdf-table">
    <thead>
        <tr><th style="width:40%">BUILDING</th><th class="right" style="width:20%">REVENUE</th><th class="right" style="width:20%">EXPENSE</th><th class="right" style="width:20%">NET</th></tr>
    </thead>
    <tbody>
        @foreach($breakdown as $row)
        <tr>
            <td><x-pdf.text :value="$row['building']->property_name" :limit="40" /></td>
            <td class="right">{{ $fmt($row['total_revenue']) }}</td>
            <td class="right">{{ $fmt($row['total_expense']) }}</td>
            <td class="right {{ $row['net_profit'] >= 0 ? 'profit' : 'loss' }}">{{ $fmt(abs($row['net_profit'])) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</x-pdf.subsection>
@endif

@endsection
