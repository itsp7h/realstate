{{-- The Export button — the only one in the app.

     Export used to be whatever the page felt like: soft green on Buildings,
     Units and Floors, outline on Tenants, Leases and the EWA summary, gold on
     all ten reports. Same word, same job, three weights. This component is the
     single answer, and .btn-export (app-core §4.1) is its only styling — no
     page passes a class of its own, so no page can drift again.

     Props
       href      when set the button is a direct download and renders as <a>;
                 omit it for a menu trigger (partials/export-menu) or a button
                 that runs JS, which render as <button type="button">
       label     visible text — "Export", "Export XLSX", "Export PDF"
       icon      Font Awesome glyph; the format-specific ones say which file
                 comes back before you click
       caret     adds the chevron that marks a menu trigger
       disabled  nothing to export yet. A disabled control renders as a real
                 <button disabled> even when an href was passed, because a
                 dimmed <a> is still focusable and still follows on Enter.

     Any other attribute passes straight through, which is how the menu trigger
     gets its data-pop-toggle / aria-controls without this component having to
     know about the popover machinery. --}}
@props([
    'href'     => null,
    'label'    => 'Export',
    'icon'     => 'fa-file-export',
    'caret'    => false,
    'disabled' => false,
])

@php
    $tag = $href && ! $disabled ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($tag === 'a') href="{{ $href }}" @else type="button" @disabled($disabled) @endif
    {{ $attributes->class(['btn', 'btn-export']) }}>
    <i class="fa-solid {{ $icon }}" aria-hidden="true"></i> {{ $label }}
    @if ($caret)
        <i class="fa-solid fa-chevron-down btn-caret" aria-hidden="true"></i>
    @endif
</{{ $tag }}>
