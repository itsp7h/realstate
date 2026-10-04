{{-- A figure, or the absence of one.

     One rule for every number in every export: print it only when the value
     truly exists, otherwise print a grey em dash. A zero printed where nothing
     was recorded ("0.000" for a deposit nobody entered) asserts a fact the data
     does not have, and it reads as noise beside the dashes around it.

     @param mixed  $value  the figure; null/'' renders as absent
     @param int    $dp     decimal places — 3 for BHD money, 2 for areas, 0 for counts
     @param bool   $money  treat a stored 0 as absent too. Blank money cells in
                           the import landed in the database as 0, and this app
                           has no meaning for a rent or deposit of exactly zero
                           — it means nobody filled it in. Counts are the
                           opposite: 0 units is a fact, so they leave this off.
     @param string $suffix optional unit, e.g. 'BHD'

     The whole value is composed in PHP rather than nested @if directives:
     Blade's directive regex needs a non-word character before an @, so
     `@endif@else` compiles to a literal "@else" in the document. --}}
@props(['value' => null, 'dp' => 3, 'money' => false, 'suffix' => null])
@php
    $exists = $value !== null && $value !== ''
        && ! ($money && (float) $value === 0.0);

    $figure = $exists
        ? number_format((float) $value, $dp) . ($suffix ? ' ' . $suffix : '')
        : null;
@endphp
@if($figure !== null){{ $figure }}@else<span class="pdf-empty">&mdash;</span>@endif
