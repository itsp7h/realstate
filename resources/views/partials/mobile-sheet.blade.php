{{-- ═══════════════════════ MOBILE SHEET ═══════════════════════
     The app's one bottom sheet, as a partial. A .modal-overlay, so
     app-mobile.css gives it the drag handle, the spring-in and the
     safe-area padding every other sheet gets; the layout's generic
     [data-sheet-open] / [data-sheet-close] handler opens and closes it.

     Props
       id     required. The overlay's id — the opener points at it.
       title  sheet title.            default "More"
       sub    one line under it.      optional
       icon   header tile glyph.      default fa-ellipsis
       items  the rows:
              [ 'icon'    => 'fa-file-excel',
                'label'   => 'Export to Excel',
                'desc'    => 'The list as it is filtered now',
                'href'    => '…',        // or
                'onclick' => 'openImport_units()',
                'target'  => '_blank',   // optional
                'danger'  => true ]      // optional, red label

     Every row closes the sheet on the way out: the exports navigate
     away, and an import row opens a second dialog that must not appear
     behind this one. --}}
@php
    $sheetTitle = $title ?? 'More';
    $sheetIcon  = $icon ?? 'fa-ellipsis';
    $sheetItems = $items ?? [];
@endphp
<div class="modal-overlay" id="{{ $id }}" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}Title">
    <div class="modal-box" style="--modal-w:440px;">
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon"><i class="fa-solid {{ $sheetIcon }}" aria-hidden="true"></i></div>
                <div class="modal-header-text">
                    <div class="modal-header-title" id="{{ $id }}Title">{{ $sheetTitle }}</div>
                    @isset($sub)<div class="modal-header-sub">{{ $sub }}</div>@endisset
                </div>
                <button type="button" class="modal-close-btn" data-sheet-close aria-label="Close">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="modal-body">
            @foreach($sheetItems as $item)
                @php $isLink = ! empty($item['href']); @endphp
                <{{ $isLink ? 'a' : 'button' }}
                    class="more-sheet-item{{ ! empty($item['danger']) ? ' danger' : '' }}"
                    data-sheet-close
                    @if($isLink)
                        href="{{ $item['href'] }}"
                        @isset($item['target']) target="{{ $item['target'] }}" rel="noopener" @endisset
                    @else
                        type="button" @isset($item['onclick']) onclick="{{ $item['onclick'] }}" @endisset
                    @endif>
                    <div class="more-sheet-icon{{ ! empty($item['danger']) ? ' is-danger' : '' }}">
                        <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="more-sheet-label">{{ $item['label'] }}</div>
                        @isset($item['desc'])<div class="more-sheet-desc">{{ $item['desc'] }}</div>@endisset
                    </div>
                </{{ $isLink ? 'a' : 'button' }}>
            @endforeach
        </div>
    </div>
</div>
