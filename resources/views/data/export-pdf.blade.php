@extends('layouts.pdf')

{{-- Portfolio data export — the PDF half of /data/export.

     The workbook's job is 21 sortable columns; this one's is to be read, so the
     units sit under the building that owns them and the column set is trimmed.
     The workbook still carries every field.

     Page box, letterhead, footer, table anatomy: layouts/pdf.blade.php and
     public/css/pdf.css. Nothing print-related is declared here. --}}

@section('pdf-title', 'Portfolio data export')
@section('report-title', 'Portfolio Data Export')
@section('report-sub', 'Every property, floor and unit on record')

@section('summary')
    <x-pdf.summary :items="[
        ['label' => 'Properties', 'value' => number_format($totals['buildings'])],
        ['label' => 'Floors',     'value' => number_format($totals['floors'])],
        ['label' => 'Units',      'value' => number_format($totals['units'])],
        ['label' => 'Rent / month — BHD', 'value' => $totals['rent'] === null ? null : number_format($totals['rent'], 3)],
    ]" />

    @if(($unlisted['floors'] ?? 0) > 0 || $orphanUnits->isNotEmpty())
        <div class="pdf-note">
            The counts above include
            @if(($unlisted['floors'] ?? 0) > 0){{ $unlisted['floors'] }} {{ \Illuminate\Support\Str::plural('floor', $unlisted['floors']) }}@endif
            @if(($unlisted['floors'] ?? 0) > 0 && $orphanUnits->isNotEmpty()) and @endif
            @if($orphanUnits->isNotEmpty()){{ $orphanUnits->count() }} {{ \Illuminate\Support\Str::plural('unit', $orphanUnits->count()) }}@endif
            attached to no property. Units are listed at the end of this document.
        </div>
    @endif
@endsection

@section('content')

@forelse($buildings as $building)
    @php
        $address = implode(', ', array_filter([
            $building->building_no ? 'Building ' . $building->building_no : null,
            $building->road ? 'Road ' . $building->road : null,
            $building->block ? 'Block ' . $building->block : null,
            $building->area,
            $building->city,
        ]));
        $meta = implode(' · ', array_filter([
            $building->property_type,
            $building->type_of_ownership,
            $building->land_lord ? 'Landlord: ' . $building->land_lord : null,
            $address ?: null,
        ]));
        // No figures at all means unknown, not zero — same rule as the strip,
        // and a stored 0 is nothing here too (blank import cells landed as 0),
        // so a column of dashes can never be totalled into "0.000".
        $rented    = $building->units->filter(
            fn ($unit) => $unit->rent_per_month !== null && (float) $unit->rent_per_month !== 0.0
        );
        $rentTotal = $rented->isEmpty() ? null : (float) $rented->sum('rent_per_month');
    @endphp

    {{-- Natural flow, no forced page per property: a one-unit property used to
         own a whole page and leave most of it blank. --}}
    <div class="pdf-section">
        <x-pdf.section :name="$building->property_name" :code="$building->property_code" :meta="$meta ?: null" />

        <x-pdf.subsection label="Floors" :count="$building->floors->count()">
        {{-- Zero rows is one line, not a header over nothing. --}}
        @if($building->floors->isEmpty())
            <x-pdf.empty>No floors recorded for this property.</x-pdf.empty>
        @else
        <table class="pdf-table">
            <colgroup>
                <col style="width:27%"><col style="width:19%"><col style="width:24%">
                <col style="width:18%"><col style="width:12%">
            </colgroup>
            <thead>
                <tr>
                    <th>FLOOR</th>
                    <th>FLOOR #</th>
                    <th>BLOCK</th>
                    <th>BLOCK #</th>
                    <th class="right">UNITS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($building->floors as $floor)
                    <tr>
                        <td><x-pdf.text :value="$floor->floor_name" :limit="26" /></td>
                        <td><x-pdf.text :value="$floor->floor_code" :limit="18" /></td>
                        <td><x-pdf.text :value="$floor->block_name" :limit="22" /></td>
                        <td><x-pdf.text :value="$floor->block_code" :limit="16" /></td>
                        {{-- The live relation count, so 0 reads as zero units
                             rather than unrecorded — see the withCount in
                             DataController. No fallback query from a view: if
                             the count was not loaded the document says so
                             rather than printing a 0 it did not measure. --}}
                        <td class="right">@isset($floor->units_count){{ number_format($floor->units_count) }}@else<span class="pdf-empty">&mdash;</span>@endisset</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        </x-pdf.subsection>

        <x-pdf.subsection label="Units" :count="$building->units->count()">
        @if($building->units->isEmpty())
            <x-pdf.empty>No units recorded for this property.</x-pdf.empty>
        @else
        {{-- is-dense: nine columns on portrait A4. 8pt is the floor, per spec —
             the paper never turns. --}}
        <table class="pdf-table is-dense">
            <colgroup>
                <col style="width:16%"><col style="width:10%"><col style="width:12%"><col style="width:11%">
                <col style="width:9%"><col style="width:9%"><col style="width:9%">
                <col style="width:12%"><col style="width:12%">
            </colgroup>
            <thead>
                <tr>
                    <th>UNIT</th>
                    <th>TYPE</th>
                    <th>CONDITION</th>
                    <th>VIEW</th>
                    <th class="right">AREA</th>
                    <th class="right">TERRACE</th>
                    <th class="right">RATE</th>
                    <th class="right">RENT/MO</th>
                    <th class="right">DEPOSIT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($building->units as $unit)
                    <tr>
                        <td><x-pdf.text :value="$unit->unit_name" :limit="18" /></td>
                        <td><x-pdf.text :value="$unit->unit_type" :limit="11" /></td>
                        <td><x-pdf.text :value="$unit->unit_condition" :limit="12" /></td>
                        <td><x-pdf.text :value="$unit->view" :limit="11" /></td>
                        <td class="right"><x-pdf.figure :value="$unit->area_inside" :dp="2" /></td>
                        <td class="right"><x-pdf.figure :value="$unit->area_terrace" :dp="2" /></td>
                        <td class="right"><x-pdf.figure :value="$unit->rate_per_area_unit" money /></td>
                        <td class="right"><x-pdf.figure :value="$unit->rent_per_month" money /></td>
                        <td class="right"><x-pdf.figure :value="$unit->security_deposit_amount" money /></td>
                    </tr>
                @endforeach
            </tbody>
            {{-- tfoot, not a last row in tbody: the total is bound to the row
                 above it by the pagination contract in pdf.css, so it can never
                 open a page on its own. --}}
            <tfoot>
                <tr>
                    <td colspan="7" class="right">Total &mdash; BHD</td>
                    <td class="right value"><x-pdf.figure :value="$rentTotal" money /></td>
                    {{-- The deposit column had no total, so the row asserted a
                         sum for rent and stayed silent about the money beside
                         it. Same null rule: no figures at all means unknown. --}}
                    <td class="right value">
                        <x-pdf.figure :value="$building->units->whereNotNull('security_deposit_amount')->isEmpty()
                            ? null
                            : $building->units->sum('security_deposit_amount')" money />
                    </td>
                </tr>
            </tfoot>
        </table>
        @endif
        </x-pdf.subsection>
    </div>
@empty
    <div class="pdf-blank" style="margin-top:24pt;">
        There is nothing to export yet &mdash; no properties have been added.
    </div>
@endforelse

{{-- A unit whose property_code matches no building would otherwise vanish from
     a document that claims to hold everything. --}}
@if($orphanUnits->isNotEmpty())
    <div class="pdf-section">
        <x-pdf.section
            name="Units without a property"
            :code="(string) $orphanUnits->count()"
            meta="These units carry a property code that matches no building on record." />

        <table class="pdf-table">
            <colgroup>
                <col style="width:20%"><col style="width:24%"><col style="width:18%">
                <col style="width:19%"><col style="width:19%">
            </colgroup>
            <thead>
                <tr>
                    <th>CODE</th>
                    <th>UNIT</th>
                    <th>TYPE</th>
                    <th class="right">RENT/MO</th>
                    <th class="right">DEPOSIT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orphanUnits as $unit)
                    <tr>
                        <td><x-pdf.text :value="$unit->property_code" :limit="20" /></td>
                        <td><x-pdf.text :value="$unit->unit_name" :limit="24" /></td>
                        <td><x-pdf.text :value="$unit->unit_type" :limit="18" /></td>
                        <td class="right"><x-pdf.figure :value="$unit->rent_per_month" money /></td>
                        <td class="right"><x-pdf.figure :value="$unit->security_deposit_amount" money /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
