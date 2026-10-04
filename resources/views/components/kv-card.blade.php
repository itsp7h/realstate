{{-- ═══════════════════════ KEY/VALUE CARD ═══════════════════════
     A record's fields as a list — label left, value right, one hairline
     between rows. See app-core.css §4.3c for what this replaced and when
     to reach for it instead of .detail-grid.

     The one rule worth stating: an empty field never reports its
     emptiness. "Not provided" tells the reader something they can do
     nothing with; "Add ›" is the same fact and a way to fix it. So a row
     with no value renders its `add` link, and a row that is only
     partly filled renders both — "Bahraini · Add ›" — which is how the
     card says "there is more to this field" without a second row.

     Props
       title      the card's heading. optional — a card can be rows alone
       edit       href for the one verb that acts on every row. optional
       editLabel  its label.                          default "Edit"
       rows       [[ 'icon'  => 'fa-phone',            // optional glyph
                     'label' => 'Phone',
                     'value' => $tenant->phone,        // null when empty
                     'href'  => 'tel:…',               // optional, wraps value
                     'add'   => route('tenants.edit', …) ]]  // when incomplete
       meta       [['label' => 'Created', 'value' => '12 Jul 2026'], …]
                  provenance, set as a footer row of eyebrow pairs --}}
@props([
    'title'     => null,
    'edit'      => null,
    'editLabel' => 'Edit',
    'rows'      => [],
    'meta'      => null,
])
<div class="kv-card">
    @if($title || $edit)
        <div class="kv-head">
            <div class="kv-title">{{ $title }}</div>
            @if($edit)
                <a class="kv-edit" href="{{ $edit }}">{{ $editLabel }}</a>
            @endif
        </div>
    @endif

    @foreach($rows as $kvRow)
        @php
            $kvValue = $kvRow['value'] ?? null;
            $kvHas   = filled($kvValue);
            $kvAdd   = $kvRow['add'] ?? null;
        @endphp
        <div class="kv-row">
            @isset($kvRow['icon'])
                <i class="fa-solid {{ $kvRow['icon'] }} kv-icon" aria-hidden="true"></i>
            @endisset
            <span class="kv-label">{{ $kvRow['label'] }}</span>
            <span class="kv-value">
                @if($kvHas)
                    @isset($kvRow['href'])
                        <a href="{{ $kvRow['href'] }}">{{ $kvValue }}</a>
                    @else
                        {{ $kvValue }}
                    @endisset
                @endif
                @if($kvAdd)
                    @if($kvHas)<span aria-hidden="true">&middot;</span>@endif
                    <a class="kv-add" href="{{ $kvAdd }}">
                        Add<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        <span class="sr-only">{{ strtolower($kvRow['label']) }}</span>
                    </a>
                @endif
            </span>
        </div>
    @endforeach

    @if($meta)
        <div class="kv-meta">
            @foreach($meta as $kvPair)
                <div>
                    <div class="kv-meta-label">{{ $kvPair['label'] }}</div>
                    <div class="kv-meta-value">{{ $kvPair['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
