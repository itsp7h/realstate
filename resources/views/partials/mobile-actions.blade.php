{{-- ═══════════════════ MOBILE PAGE ACTIONS ═══════════════════
     The row directly under the header on a list screen. Exactly two
     controls: ONE primary create verb, and ONE "More" that opens a
     sheet with everything else. Screens used to line up four peers
     here — Add, XLSX, PDF, Import, three of them as bare gold text —
     which left no primary at all.

     Props
       primary  ['label' =>, 'icon' => 'fa-plus',
                 'onclick' => '…'  or  'href' => '…']
       sheet    the sheet this row's More button opens, as accepted by
                partials/mobile-sheet (id, title, sub, items). Omit it
                and the row renders the primary alone.
       label    the More button's own label.  default "More"
       step     staggered-reveal step for .ps-reveal. optional --}}
@php
    $paIcon  = $primary['icon'] ?? 'fa-plus';
    $paLink  = ! empty($primary['href']);
    $paMore  = $label ?? 'More';
@endphp
<div class="m-action-row{{ isset($step) ? ' ps-reveal' : '' }}" @isset($step) style="--ps-step:{{ $step }}" @endisset>
    <{{ $paLink ? 'a' : 'button' }} class="m-action-btn primary"
        @if($paLink) href="{{ $primary['href'] }}" @else type="button" @isset($primary['onclick']) onclick="{{ $primary['onclick'] }}" @endisset @endif>
        <i class="fa-solid {{ $paIcon }}" aria-hidden="true"></i>{{ $primary['label'] }}
    </{{ $paLink ? 'a' : 'button' }}>

    @isset($sheet)
        <button type="button" class="m-action-btn more" data-sheet-open="{{ $sheet['id'] }}"
                aria-haspopup="dialog" aria-controls="{{ $sheet['id'] }}" aria-expanded="false">
            {{ $paMore }} <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
    @endisset
</div>

@isset($sheet)
    @include('partials.mobile-sheet', $sheet)
@endisset
