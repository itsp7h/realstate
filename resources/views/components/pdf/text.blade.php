{{-- A text value, or the absence of one.

     The text counterpart of x-pdf.figure: truncates in PHP (DomPDF has no
     text-overflow) and renders absence as the same grey em dash the figures
     use, so a document has exactly one way of saying "nothing recorded".
     A bare `?: '—'` printed the dash in body ink, which read as content.

     @param mixed $value  the text; null/'' renders as absent
     @param int   $limit  truncation length, matched to the column width --}}
@props(['value' => null, 'limit' => 24])
@php
    $text = trim((string) $value);
@endphp
@if($text !== ''){{ \Illuminate\Support\Str::limit($text, $limit) }}@else<span class="pdf-empty">&mdash;</span>@endif
