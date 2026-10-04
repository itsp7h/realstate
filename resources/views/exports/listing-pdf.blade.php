@extends('layouts.pdf')

{{-- One document for every list export — buildings, floors, units, tenants,
     lease contracts, EWA batch results.

     Fed by the SAME Export class the XLSX comes from (headings(), query(),
     map()), so the two formats cannot drift on which columns exist. What the
     export declares separately, in pdfColumns(), is what paper needs and a
     spreadsheet does not: a short label, an explicit alignment, and a width.

     Portrait like every other export. A wide table pays for that in truncation
     and 8pt type — App\Support\ListingPdf caps each value to its own column's
     width — never in turning the paper. --}}

@section('pdf-title', $title)
@section('report-title', $title)
@section('report-sub')
{{ number_format($count) }} {{ \Illuminate\Support\Str::plural($noun, $count) }}
@endsection

@section('content')

@if($rows->isEmpty())
    {{-- No header row over nothing, and no totals rule under it. --}}
    <div class="pdf-none">
        {{ empty($applied)
            ? 'No ' . \Illuminate\Support\Str::plural($noun) . ' recorded.'
            : 'No ' . \Illuminate\Support\Str::plural($noun) . ' match the filters applied.' }}
        @if(! empty($applied))
            <br>{{ collect($applied)->map(fn ($v, $k) => $k . ' = ' . $v)->implode('  ·  ') }}
        @endif
    </div>
@else
    @if(! empty($applied) || $trimmed)
        {{-- Directly above the table, because it qualifies the table. A PDF
             outlives the URL that made it, so "3 of 240" with no criteria on
             the page is a trap. --}}
        <div class="pdf-note">
            @if(! empty($applied))
                <strong>Filters applied:</strong>
                {{ collect($applied)->map(fn ($v, $k) => $k . ' = ' . $v)->implode('  ·  ') }}
                @if($trimmed)<br>@endif
            @endif
            @if($trimmed)
                <strong>Columns:</strong> {{ count($columns) }} of {{ $totalColumns }} shown &mdash;
                the XLSX export carries every field.
            @endif
        </div>
    @endif

    <table class="pdf-table @if(count($columns) > 8) is-dense @endif">
        {{-- Proportional widths, declared, so table-layout: fixed decides each
             column rather than the longest value in it.

             On BOTH the col and the th: DomPDF ignores <col> widths — the
             columns collapsed to their content and long names were clipped
             mid-glyph by the next cell's background, with no ellipsis to say
             so — but it does honour a width on the header cell. --}}
        <colgroup>
            @foreach($columns as $column)
                <col @if($column['width']) style="width:{{ $column['width'] }}%" @endif>
            @endforeach
        </colgroup>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th class="{{ $column['align'] }}" @if($column['width']) style="width:{{ $column['width'] }}%" @endif>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($columns as $i => $column)
                        <td class="{{ $column['align'] }}">
                            @if(($row[$i] ?? null) === null || $row[$i] === '')<span class="pdf-empty">&mdash;</span>@else{{ $row[$i] }}@endif
                        </td>
                    @endforeach
                </tr>
            @endforeach

        </tbody>
        @if(! empty($totals))
            {{-- tfoot, not a last row in tbody: the total is bound to the table
                 rather than floating in the body, and DomPDF does not repeat it
                 per page (verified on a five-page export). --}}
            <tfoot>
                <tr>
                    @foreach($columns as $i => $column)
                        @if($i === 0)
                            <td class="label">Total</td>
                        @else
                            <td class="value {{ $column['align'] }}">
                                @if(array_key_exists($column['label'], $totals))
                                    @if($totals[$column['label']] === null)<span class="pdf-empty">&mdash;</span>@else{{ $totals[$column['label']] }}@endif
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
@endif

@endsection
