{{-- A labelled subsection of a section: "FLOORS — 7" and its table.

     Exists for the pagination, not the label. A subsection short enough to fit
     a page is wrapped in .pdf-keep so heading, column header and rows travel as
     one block — otherwise DomPDF is free to leave the heading and a bare header
     at the foot of a page with every row overleaf, and it does not re-draw the
     header unless the break fell inside the body. Longer subsections are left
     to split, because a break inside the body does repeat the header, and
     moving twenty rows wholesale would leave half a page white.

     @param string   $label  the heading, e.g. 'Floors'
     @param int|null $count  shown after the label — omit where a count says
                             nothing, as on a fixed statement
     @param int|null $rows   how many rows follow, for the wrap decision;
                             defaults to $count --}}
@props(['label', 'count' => null, 'rows' => null])
@php
    // Five rows plus a heading and a header is about an inch and a half: worth
    // moving whole. Beyond that the blank tail it would leave costs more than a
    // split does, and a split inside the body repeats the column header anyway.
    // Measured, not guessed: at ten the portfolio export left 3.7in white on
    // page 1 to keep a seven-row table together.
    $travelsWhole = (int) ($rows ?? $count ?? 0) <= 5;
@endphp
<div @class(['pdf-keep' => $travelsWhole])>
    <div class="pdf-label">{{ $label }}@if($count !== null) &mdash; {{ number_format((int) $count) }}@endif</div>
    {{ $slot }}
</div>
