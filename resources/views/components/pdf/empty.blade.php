{{-- Empty subsection, as one line.

     A table head over zero rows reads as data that failed to load, and a total
     under zero rows states a figure nothing supports — so an empty subsection
     prints neither. The caller renders this INSTEAD of the whole table.

     <x-pdf.empty>No floors recorded for this property.</x-pdf.empty> --}}
<div class="pdf-blank-line">{{ $slot }}</div>
