{{-- ═══════════════════════ MOBILE LIST SCREEN ═══════════════════════
     Every list screen on the phone, in one order:

         header → actions (Add + More) → stats → search → chips → rows

     Eleven screens used to write that order out by hand, and one of them
     had drifted: Buildings put the search pill above the actions row, so
     the primary verb sat in a different place there than on the other
     ten. The order is not a page's decision any more — a page says what
     it has, this file says where it goes.

     It also owns the reveal queue. Each section takes the next step as it
     is rendered and the rows inherit the one after (see --ps-row-step in
     app-mobile.css), so a screen with no stat strip closes the gap
     instead of leaving a 40ms hole in the middle of its own animation.

     Props — every one optional; omit what the screen doesn't have.
       actions  as accepted by partials/mobile-actions:
                ['primary' => ['label' =>, 'icon' =>, 'href'|'onclick' =>],
                 'sheet'   => [...], 'label' => 'More']
       stats    [['value' => '12',   'label' => 'Total', 'word' => false],
                 ['money' => $total, 'label' => 'Total BHD'], …]
                `value` is rendered as given — counts, number_format.
                `money` is a raw amount: the strip formats it and mutes it
                when it is a zero, so no page repeats either decision.
                `word` for a value that is a word, not a number.
       search   ['action'      => route('buildings.index'),
                 'placeholder' => 'Search property name or code',
                 'aria'        => 'Search properties',
                 'keep'        => ['property_type', 'type_of_ownership']]
                `keep` are the query keys a search must not drop — the
                chips' state survives typing because of them.
       chips    [['label' => 'All', 'href' => …, 'active' => true],
                 ['label' => 'Paid', 'href' => …, 'active' => false,
                  'count' => 12],                 // shown only when active
                 ['sep' => true], …]              // hairline between facets
       slot     the rows: @forelse … .m-row-card … @empty … .m-empty --}}
@props([
    'actions'   => null,
    'stats'     => null,
    'statsNavy' => true,
    'search'    => null,
    'chips'     => null,
])
@php $mlStep = 0; @endphp
<div class="m-screen">
    @if($actions)
        @include('partials.mobile-actions', $actions + ['step' => $mlStep++])
    @endif

    {{-- The strip is the dark plate (.ps-stat-strip.is-navy) on every list
         screen — one plate per screen, directly under the header, so the band
         of figures is the same object everywhere and the white cards below it
         are the list. A screen that wants the light strip instead passes
         :stats-navy="false".

         `owed` marks the one figure that asks for something (gold on the
         plate); a zero takes the quiet ink back, because a zero owed is good
         news and must not wear an alert colour. --}}
    @if($stats)
        <div class="ps-stat-strip{{ $statsNavy ? ' is-navy' : '' }} ps-reveal" style="--ps-step:{{ $mlStep++ }}">
            @foreach($stats as $mlStat)
                @php
                    $mlMoney = array_key_exists('money', $mlStat);
                    $mlValue = $mlMoney ? \App\Support\MoneyFormat::figure($mlStat['money']) : $mlStat['value'];
                    /* A zero is muted on the plate whether it is money or a
                       count: white on navy is the brightest ink in the app, and
                       "0 COMMERCIAL" has no business being the first thing read
                       in the card. On a white strip a zero is already quiet in
                       body ink, so nothing changes there. */
                    $mlZero  = $mlMoney
                        ? \App\Support\MoneyFormat::isZero($mlStat['money'])
                        : ($statsNavy && is_numeric(str_replace(',', '', (string) $mlValue))
                           && \App\Support\MoneyFormat::isZero((float) str_replace(',', '', (string) $mlValue)));
                    $mlOwed  = (bool) ($mlStat['owed'] ?? false);
                @endphp
                <div class="ps-stat">
                    {{-- is-owed before is-zero: both are declared at equal
                         specificity and the later one wins, so a zero owed
                         comes back to quiet instead of staying gold. --}}
                    <div class="ps-stat-value{{ ($mlStat['word'] ?? false) ? ' is-word' : '' }}{{ $mlOwed ? ' is-owed' : '' }}{{ $mlZero ? ' is-zero' : '' }}">{{ $mlValue }}</div>
                    <div class="ps-stat-label">{{ $mlStat['label'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if($search)
        <div class="m-search ps-reveal" style="--ps-step:{{ $mlStep++ }}">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <form method="GET" action="{{ $search['action'] }}">
                @foreach($search['keep'] ?? [] as $mlKeep)
                    @if(request($mlKeep))
                        <input type="hidden" name="{{ $mlKeep }}" value="{{ request($mlKeep) }}">
                    @endif
                @endforeach
                <input type="search" name="search" value="{{ request('search') }}"
                       placeholder="{{ $search['placeholder'] }}"
                       aria-label="{{ $search['aria'] ?? $search['placeholder'] }}"
                       oninput="mDebounceSubmit(this)">
            </form>
        </div>
    @endif

    @if($chips)
        <div class="m-chip-row ps-reveal" style="--ps-step:{{ $mlStep++ }}">
            @foreach($chips as $mlChip)
                @if($mlChip['sep'] ?? false)
                    <span class="m-chip-sep" aria-hidden="true"></span>
                @else
                    @php $mlOn = (bool) ($mlChip['active'] ?? false); @endphp
                    <a href="{{ $mlChip['href'] }}" class="m-chip{{ $mlOn ? ' active' : '' }}"
                       @if($mlOn) aria-current="true" @endif>
                        {{ $mlChip['label'] }}
                        @if($mlOn && isset($mlChip['count']))
                            <span class="m-chip-count">&middot; {{ $mlChip['count'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    <div class="m-row-list" style="--ps-row-step:{{ $mlStep }}">
        {{ $slot }}
    </div>
</div>
