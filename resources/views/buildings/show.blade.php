@extends('layouts.admin')

@section('title', $building->property_name)
@section('topbar-title', 'Building Detail')

@section('page-breadcrumb')
    <a href="{{ url('/dashboard') }}">Home</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="{{ route('buildings.index') }}">Buildings</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>{{ $building->property_name }}</span>
@endsection
@section('page-title')
    {{ $building->property_name }}
@endsection
@section('page-subtitle')
    {{ $building->property_code }} &middot; {{ $building->full_address ?? 'No address on file' }}
@endsection
@section('page-actions')
    <a href="{{ route('buildings.edit', $building) }}" class="shell-headbtn is-primary">
        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i> Edit Building
    </a>
@endsection

@push('styles')
<style>
    /* ── Building detail — page-local only ──────────────────────────────────
       Everything shared lives in app-core: the ring, the breakdown meter, the
       identity block, the days-left tile, the compact KPI strip, the tab bar,
       the tables. What is left here is genuinely unique to this page — the
       photo library, the financial plot's box, and the settings column. ── */

    /* The financial plot. 128px of bars plus the month labels beneath them,
       per the design; the card, not the canvas, owns the padding. */
    .bd-plot { position: relative; height: 168px; }

    /* A chart's key, in the card header rather than on the canvas, so it
       inherits the page's type and re-themes for free. */
    .bd-key { display: flex; align-items: center; gap: var(--sp-4); }
    .bd-key-item { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; color: var(--text-secondary); }
    .bd-key-swatch { width: 9px; height: 9px; border-radius: 3px; flex: none; }
    .bd-key-swatch.is-income  { background: var(--chart-primary); }
    .bd-key-swatch.is-expense { background: var(--chart-accent); }

    /* An initial standing in for a tenant's photo. Circular, so it never
       reads as one of the square icon tiles. */
    .bd-initial {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: var(--page-bg);
        color: var(--text-primary);
        font-size: 11px; font-weight: 600;
        display: flex; align-items: center; justify-content: center;
        flex: none;
    }

    /* Photo tiles: their own grid, since a thumbnail wants a much smaller
       minimum than a card grid's 240px. */
    .bd-photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: var(--sp-4); }
    .bd-thumb {
        position: relative;
        border-radius: var(--radius-sm);
        overflow: hidden;
        aspect-ratio: 4/3;
        border: 1px solid var(--card-border);
    }
    .bd-thumb img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform var(--dur-slow) var(--ease-out);
        cursor: zoom-in;
    }
    .bd-thumb:hover img { transform: scale(1.05); }
    .bd-thumb-remove {
        position: absolute; top: 8px; right: 8px;
        width: 28px; height: 28px; border-radius: 50%;
        background: var(--danger); border: none; color: var(--ink-on-fill);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        opacity: 0; transform: scale(0.8);
        transition: opacity var(--dur-base) ease, transform var(--dur-base) ease;
    }
    .bd-thumb:hover .bd-thumb-remove,
    .bd-thumb:focus-within .bd-thumb-remove { opacity: 1; transform: scale(1); }
    /* Hover-only reveal leaves this unreachable by keyboard and on touch. */
    @media (hover: none) { .bd-thumb-remove { opacity: 1; transform: scale(1); } }
    @media (prefers-reduced-motion: reduce) {
        .bd-thumb img, .bd-thumb-remove { transition: none; }
        .bd-thumb:hover img { transform: none; }
    }

    .bd-drop {
        width: 100%;
        border: 2px dashed var(--input-border);
        border-radius: var(--radius);
        padding: var(--sp-10) var(--sp-6);
        text-align: center;
        background: var(--page-bg-alt);
        color: var(--text-secondary);
        cursor: pointer;
        transition: border-color var(--dur-base) ease, background var(--dur-base) ease;
    }
    .bd-drop:hover, .bd-drop.is-over { border-color: var(--accent); background: var(--tone-accent-bg); }
    .bd-drop:focus-visible { outline: var(--focus-outline); outline-offset: 2px; }
    /* Sitting under a filled grid rather than standing alone. */
    .bd-drop.is-secondary { margin-top: var(--sp-6); padding: var(--sp-6); }
    .bd-drop-title { font-size: var(--fs-md); font-weight: 600; color: var(--text-primary); margin-bottom: var(--sp-1); }
    .bd-drop-sub { font-size: var(--fs-sm); color: var(--text-muted); }

    /* The settings column: a form is read down one line at a time, so it is
       capped rather than stretched across a 1400px content column. */
    .bd-settings { max-width: 640px; }
    .bd-switch-row { display: flex; align-items: flex-start; gap: var(--sp-6); }
    .bd-switch-copy { flex: 1; min-width: 0; }
    .bd-rate { display: flex; align-items: center; gap: var(--sp-2); }
    .bd-rate input { width: 120px; }
    .bd-rate-unit { font-weight: 600; color: var(--text-secondary); }
    .bd-rate-grid { margin-top: var(--sp-6); }
    /* A destructive action inside a row of quiet icon buttons: red ink, not a
       red fill, so the row still reads as a row. */
    .bd-danger-ink { color: var(--tone-danger-fg); }

    /* A table that runs to the card's edge has to follow its corners. */
    .card { overflow: hidden; }
</style>
@endpush

@section('content')

@php
    $mobilePhoto = $building->images->first()?->url;
    $mobileAddress = trim(implode(', ', array_filter([$building->area, $building->city])));
    $mobileUnitFilters = [
        ['id' => 'all',     'label' => 'All'],
        ['id' => 'let',     'label' => 'Let'],
        ['id' => 'vacant',  'label' => 'Vacant'],
        ['id' => 'overdue', 'label' => 'Overdue'],
    ];
    $mobileStatusMeta = [
        'let'     => ['label' => 'Let',     'tint' => 'var(--ps-bg)',         'tone' => 'var(--ps-muted-deep)'],
        'paid'    => ['label' => 'Paid',    'tint' => 'var(--ps-success-bg)', 'tone' => 'var(--ps-success)'],
        'overdue' => ['label' => 'Overdue', 'tint' => 'var(--ps-danger-bg)',  'tone' => 'var(--ps-danger)'],
        'vacant'  => ['label' => 'Vacant',  'tint' => 'var(--ps-warning-bg)', 'tone' => 'var(--ps-warning)'],
    ];
@endphp

{{-- MOBILE: pushed-screen header with a back chevron --}}
<div class="pm-push-header">
    <a href="{{ route('buildings.index') }}" class="pm-push-back"><i class="fa-solid fa-chevron-left"></i></a>
    <div class="pm-header-text">
        <div class="pm-title">{{ $building->property_name }}</div>
        <div class="pm-subtitle">{{ $building->property_type ?? 'Property' }}</div>
    </div>
    <button type="button" class="pm-icon-btn" title="Notifications — coming soon"><i class="fa-regular fa-bell"></i></button>
    <div class="pm-avatar">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</div>
</div>

{{-- MOBILE: property detail (native app-style hero + row-card floors/units).
     `.m-screen`'s default 18px top padding is kept here (not zeroed) so there's
     breathing room between the pm-push-header above and the hero photo below —
     `.pm-hero-wrap` no longer pulls itself up on top of it (see admin.blade.php). --}}
<div class="m-screen">
    <div class="pm-hero-wrap">
        <div class="pm-hero-photo" id="pmHeroPhoto">
            <div class="pm-hero-photo-img has-parallax" id="pmHeroPhotoImg" @if($mobilePhoto) style="background-image:url('{{ $mobilePhoto }}')" @endif></div>
            @unless($mobilePhoto)
                <div class="pm-property-photo-fallback"><i class="fa-solid fa-building"></i></div>
            @endunless
            <div class="pm-hero-topbar">
                <span class="pm-hero-occ-pill"><i class="fa-solid fa-door-open"></i> {{ $dashboard['kpis']['occupancy_percent'] }}% occupied</span>
                <a href="{{ route('buildings.edit', $building) }}" class="pm-hero-edit-btn" title="Edit building"><i class="fa-regular fa-pen-to-square"></i></a>
            </div>
            <div class="pm-hero-scrim">
                <div class="pm-hero-name">{{ $building->property_name }}</div>
                @if($mobileAddress)
                    <div class="pm-hero-address"><i class="fa-solid fa-location-dot"></i> {{ $mobileAddress }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="ps-stat-strip">
        <div class="ps-stat"><div class="ps-stat-label">Income &middot; {{ now()->format('M') }}</div><div class="ps-stat-value">BHD {{ number_format($dashboard['kpis']['month_income'], 0) }}</div></div>
        <div class="ps-stat"><div class="ps-stat-label">Net profit</div><div class="ps-stat-value {{ $dashboard['kpis']['month_profit'] < 0 ? 'is-danger' : 'is-gold' }}">BHD {{ number_format($dashboard['kpis']['month_profit'], 0) }}</div></div>
    </div>

    <div class="m-mini-row">
        <div class="m-mini-stat"><div class="v">{{ $dashboard['kpis']['occupancy_percent'] }}%</div><div class="l">Occupancy</div></div>
        <div class="m-mini-stat"><div class="v">{{ $dashboard['kpis']['occupied_units'] }}/{{ $dashboard['kpis']['total_units'] }}</div><div class="l">Units let</div></div>
        <div class="m-mini-stat"><div class="v is-word">{{ $building->property_type ?? '—' }}</div><div class="l">Type</div></div>
    </div>

    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <div class="pm-section-label" style="margin-bottom:0;">Floors &amp; units</div>
            <div style="font-size:.8rem;font-weight:500;color:var(--ps-muted-deep);">
                Units <strong style="color:var(--ps-navy);">{{ $dashboard['kpis']['total_units'] }}</strong>
                &nbsp;Let <strong style="color:var(--ps-success);">{{ $dashboard['kpis']['occupied_units'] }}</strong>
                &nbsp;Vacant <strong style="color:var(--ps-danger);">{{ $dashboard['kpis']['vacant_units'] }}</strong>
            </div>
        </div>

        <form method="GET" action="{{ route('buildings.show', $building) }}" class="pm-search-field" style="margin-bottom:8px;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="unit_search" value="{{ $unitSearch }}" placeholder="Search unit number or occupant" oninput="mDebounceSubmit(this)">
            @if($unitFilter !== 'all')<input type="hidden" name="unit_filter" value="{{ $unitFilter }}">@endif
        </form>

        <div class="pm-chip-row" style="margin-bottom:12px;">
            @foreach($mobileUnitFilters as $f)
                <a href="{{ route('buildings.show', array_merge(['building' => $building], array_filter(['unit_search' => $unitSearch, 'unit_filter' => $f['id'] === 'all' ? null : $f['id']]))) }}"
                   class="pm-chip {{ $unitFilter === $f['id'] ? 'active' : '' }}">{{ $f['label'] }}</a>
            @endforeach
        </div>

        @if($floors->count() > 6)
        <div class="pm-chip-row" style="margin-bottom:12px;">
            @foreach($floors as $jf)
                <a href="#pm-floor-{{ $jf->id }}" class="pm-chip" style="min-width:34px;text-align:center;">{{ $jf->floor_name }}</a>
            @endforeach
        </div>
        @endif

        <div>
            @forelse($floorGroups as $group)
                @php
                    $isOpen = $loop->first || $unitSearch !== '' || $unitFilter !== 'all';
                    $rows = $group['rows'];
                    $capped = !$isOpen ? false : ($rows->count() > 8 && $showAllFloorId !== $group['id']);
                    $visibleRows = $capped ? $rows->take(8) : $rows;
                @endphp
                <div id="pm-floor-{{ $group['id'] }}" class="pm-floor-section">
                    <button type="button" class="pm-floor-row pm-ripple {{ $isOpen ? 'is-open' : '' }}" onclick="pmToggleFloor({{ $group['id'] }})" data-floor-toggle="{{ $group['id'] }}">
                        <i class="fa-solid fa-layer-group" style="color:var(--ps-gold-dark);font-size:13px;width:16px;text-align:center;"></i>
                        <div style="flex:1;min-width:0;">
                            <div class="pm-floor-name">{{ $group['name'] }}</div>
                            <div class="pm-floor-meta">{{ $rows->count() }} units &middot; {{ $group['letCount'] }} let</div>
                        </div>
                        <i class="fa-solid fa-chevron-{{ $isOpen ? 'up' : 'down' }}" style="color:var(--ps-faint);font-size:12px;" data-floor-chevron="{{ $group['id'] }}"></i>
                    </button>
                    <div data-floor-body="{{ $group['id'] }}" class="pm-floor-units" style="{{ $isOpen ? '' : 'display:none;' }}">
                        @foreach($visibleRows as $row)
                            @php
                                $meta = $mobileStatusMeta[$row['status']];
                                $unitHref = $row['status'] === 'vacant' ? null : ($row['unit']->activeContract?->tenant_id ? route('tenants.show', $row['unit']->activeContract->tenant_id) : null);
                            @endphp
                            @if($unitHref)
                            <a href="{{ $unitHref }}" class="pm-unit-card pm-ripple">
                            @else
                            <div class="pm-unit-card" title="Unit {{ $row['unit']->unit_name }} is vacant">
                            @endif
                                <div class="pm-unit-tile {{ $row['status'] === 'vacant' ? 'is-vacant' : 'is-let' }}">{{ $row['unit']->unit_name }}</div>
                                <div style="flex:1;min-width:0;">
                                    <div class="pm-unit-name">{{ $row['occupant'] ?? 'Vacant unit' }}</div>
                                    @if(!is_null($row['rent']))
                                        <div class="pm-unit-rent">BHD {{ number_format($row['rent'], 0) }} / mo</div>
                                    @endif
                                </div>
                                <span class="pm-unit-badge" style="background:{{ $meta['tint'] }};color:{{ $meta['tone'] }};">{{ $meta['label'] }}</span>
                            @if($unitHref)
                            </a>
                            @else
                            </div>
                            @endif
                        @endforeach
                        @if($capped)
                            <a href="{{ route('buildings.show', array_merge(['building' => $building], array_filter(['unit_search' => $unitSearch, 'unit_filter' => $unitFilter !== 'all' ? $unitFilter : null]), ['show_all_floor' => $group['id']])) }}"
                               class="pm-unit-more">
                                Show all {{ $rows->count() }} units
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="pm-empty">No units match this search.</div>
            @endforelse
        </div>
    </div>

    <button type="button" class="pm-fab" style="position:fixed;border:0;" onclick="openExpenseSheet()" title="Record expense"><i class="fa-solid fa-plus"></i></button>
</div>

@include('components.expense-sheet', ['presetBuildingId' => $building->id])

<script>
function pmToggleFloor(id) {
    document.querySelectorAll('[data-floor-body]').forEach(function (el) {
        if (Number(el.dataset.floorBody) !== id) {
            el.style.display = 'none';
            document.querySelector('[data-floor-toggle="' + el.dataset.floorBody + '"]')?.classList.remove('is-open');
            const chev = document.querySelector('[data-floor-chevron="' + el.dataset.floorBody + '"]');
            if (chev) chev.className = 'fa-solid fa-chevron-down';
        }
    });
    const body = document.querySelector('[data-floor-body="' + id + '"]');
    const chevron = document.querySelector('[data-floor-chevron="' + id + '"]');
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : '';
    document.querySelector('[data-floor-toggle="' + id + '"]').classList.toggle('is-open', !isOpen);
    if (chevron) chevron.className = isOpen ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up';
}

if (window.pmInitHeroParallax) {
    window.pmInitHeroParallax(document.getElementById('pmHeroPhotoImg'), window);
}
</script>

<div class="container-fluid px-0 m-hide-desktop-index">

@php
    /* Presentation-only derivations from collections the controller already
       loaded — no new query is issued here. */
    $k = $dashboard['kpis'];

    $profitLossUrl = route('reports.profit-loss', ['building_id' => $building->id]);

    /* r=52 ring, so the full circumference is 2πr. An arc is drawn by giving
       stroke-dasharray its share of that length. */
    $ringC = 326.726;
    $arc = fn ($fraction) => round($ringC * max(0, min(1, $fraction)), 2).' '.$ringC;

    $occupancyFraction = $k['total_units'] > 0 ? $k['occupied_units'] / $k['total_units'] : 0;

    $leaseCounts = $dashboard['lease_status_counts'];
    $leaseTotal  = $leaseCounts->sum();
    $activeLeasePercent = $leaseTotal ? round($leaseCounts['active'] / $leaseTotal * 100) : 0;

    /* The expense breakdown, ranked. A bar the width of its share reads
       better than a four-slice donut when the amounts are the point. */
    $expenseTotal = array_sum($dashboard['expenses']);
    $expenseRows = collect([
        'Utilities — electricity' => $dashboard['expenses']['electricity'] ?? 0,
        'Utilities — water'       => $dashboard['expenses']['water'] ?? 0,
        'Repairs & maintenance'   => $dashboard['expenses']['maintenance'] ?? 0,
        'Other expenses'          => $dashboard['expenses']['other'] ?? 0,
    ])->sortDesc();

    /* Per-floor occupancy, from the units already in memory. */
    $unitsByFloor = $units->groupBy('floor_id');

    $hasFinancialActivity = collect(array_merge(
        $dashboard['monthly']['income'],
        $dashboard['monthly']['expenses'],
    ))->filter(fn ($v) => $v !== null && $v != 0)->isNotEmpty();
@endphp

    {{-- IDENTITY — what the record is, above the tabs, so it stays on screen
         whichever tab is open. --}}
    <div class="card">
        <div class="ident">
            <div class="ident-mark">
                @if($firstImage = $building->images->first())
                    <img src="{{ $firstImage->url }}" alt="{{ $building->property_name }}">
                @else
                    <i class="fa-solid fa-building" aria-hidden="true"></i>
                @endif
            </div>

            <div class="ident-body">
                <div class="ident-place">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    <span>{{ $building->full_address ?? 'No address on file' }}</span>
                </div>
                <div class="ident-meta">
                    <span class="badge badge-amber">{{ $building->property_code }}</span>
                    @if($building->property_type)
                        <span class="badge badge-blue">{{ $building->property_type }}</span>
                    @endif
                    <span class="ident-meta-item"><i class="fa-solid fa-door-open" aria-hidden="true"></i> {{ $k['total_units'] }} Units</span>
                    <span class="ident-meta-sep" aria-hidden="true">&bull;</span>
                    <span class="ident-meta-item"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> {{ $k['total_floors'] }} Floors</span>
                </div>
            </div>

            <div class="ident-figure">
                <div class="ident-figure-val {{ $k['occupancy_percent'] >= 50 ? 'is-success' : '' }}">{{ $k['occupancy_percent'] }}%</div>
                <div class="ident-figure-cap">Occupied</div>
            </div>
        </div>
    </div>

    {{-- BUILDING TABS --}}
    <div class="tab-bar" id="buildingTabs" role="tablist">
        <button type="button" class="tab-btn active" id="dashboard-tab" data-tab-target="#panel-dashboard" role="tab" aria-selected="true" aria-controls="panel-dashboard">
            <i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Dashboard
        </button>
        <button type="button" class="tab-btn" id="floors-tab" data-tab-target="#panel-floors" role="tab" aria-selected="false" aria-controls="panel-floors">
            <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Floors
            <span class="tab-count">{{ $floors->count() }}</span>
        </button>
        <button type="button" class="tab-btn" id="units-tab" data-tab-target="#panel-units" role="tab" aria-selected="false" aria-controls="panel-units">
            <i class="fa-solid fa-door-open" aria-hidden="true"></i> Units
            <span class="tab-count">{{ $units->count() }}</span>
        </button>
        <button type="button" class="tab-btn" id="tenants-tab" data-tab-target="#panel-tenants" role="tab" aria-selected="false" aria-controls="panel-tenants">
            <i class="fa-solid fa-users" aria-hidden="true"></i> Tenants
            <span class="tab-count">{{ $tenants->count() }}</span>
        </button>
        <button type="button" class="tab-btn" id="agreements-tab" data-tab-target="#panel-agreements" role="tab" aria-selected="false" aria-controls="panel-agreements">
            <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Agreements
            <span class="tab-count">{{ $contracts->count() }}</span>
        </button>
        <button type="button" class="tab-btn" id="photos-tab" data-tab-target="#panel-photos" role="tab" aria-selected="false" aria-controls="panel-photos">
            <i class="fa-solid fa-images" aria-hidden="true"></i> Photos
            <span class="tab-count">{{ $building->images->count() }}</span>
        </button>
        <span class="tab-bar-spacer" aria-hidden="true"></span>
        <button type="button" class="tab-btn" id="settings-tab" data-tab-target="#panel-settings" role="tab" aria-selected="false" aria-controls="panel-settings">
            <i class="fa-solid fa-gear" aria-hidden="true"></i> Settings
        </button>
    </div>

    <div id="buildingTabsContent">

        {{-- ===================== 1. DASHBOARD ===================== --}}
        <div class="tab-panel active" id="panel-dashboard" role="tabpanel" aria-labelledby="dashboard-tab" tabindex="0">

            {{-- The building's whole position, read as one block. --}}
            <div class="stats-grid is-compact">
                <button type="button" class="stat-card is-compact" onclick="document.getElementById('units-tab').click()">
                    <div class="stat-card-top"><span class="stat-lbl">Total Units</span></div>
                    <div class="stat-val">{{ $k['total_units'] }}</div>
                </button>
                <button type="button" class="stat-card is-compact" onclick="document.getElementById('units-tab').click()">
                    <div class="stat-card-top"><span class="stat-lbl">Occupied</span></div>
                    <div class="stat-val val-positive">{{ $k['occupied_units'] }}</div>
                </button>
                <button type="button" class="stat-card is-compact" onclick="document.getElementById('units-tab').click()">
                    <div class="stat-card-top"><span class="stat-lbl">Vacant</span></div>
                    <div class="stat-val {{ $k['vacant_units'] > 0 ? 'val-negative' : '' }}">{{ $k['vacant_units'] }}</div>
                </button>
                <button type="button" class="stat-card is-compact" onclick="document.getElementById('tenants-tab').click()">
                    <div class="stat-card-top"><span class="stat-lbl">Tenants</span></div>
                    <div class="stat-val">{{ $k['tenant_count'] }}</div>
                </button>
                <button type="button" class="stat-card is-compact" onclick="document.getElementById('floors-tab').click()">
                    <div class="stat-card-top"><span class="stat-lbl">Floors</span></div>
                    <div class="stat-val">{{ $k['total_floors'] }}</div>
                </button>
                <button type="button" class="stat-card is-compact" onclick="window.location='{{ $profitLossUrl }}'">
                    <div class="stat-card-top"><span class="stat-lbl">Income (Mo)</span></div>
                    <div class="stat-val"><span class="stat-cur">BHD</span>{{ number_format($k['month_income'], 0) }}</div>
                </button>
                <button type="button" class="stat-card is-compact" onclick="window.location='{{ $profitLossUrl }}'">
                    <div class="stat-card-top"><span class="stat-lbl">Expense (Mo)</span></div>
                    <div class="stat-val val-negative"><span class="stat-cur">BHD</span>{{ number_format($k['month_expense'], 0) }}</div>
                </button>
                <button type="button" class="stat-card is-compact" onclick="window.location='{{ $profitLossUrl }}'">
                    <div class="stat-card-top"><span class="stat-lbl">Net Profit (Mo)</span></div>
                    <div class="stat-val {{ $k['month_profit'] >= 0 ? 'val-positive' : 'val-negative' }}"><span class="stat-cur">BHD</span>{{ number_format($k['month_profit'], 0) }}</div>
                </button>
            </div>

            {{-- FINANCIAL PERFORMANCE — the one card on the page that carries
                 the gold rule, because money is what the page is ultimately
                 about. --}}
            <div class="card has-accent-rule">
                <div class="card-header">
                    <div class="card-header-text">
                        <h3 class="card-title">Financial Performance</h3>
                        <p class="card-subtitle">Income and expenses, month by month &middot; {{ now()->year }}</p>
                    </div>
                    <div class="bd-key" aria-hidden="true">
                        <span class="bd-key-item"><span class="bd-key-swatch is-income"></span>Income</span>
                        <span class="bd-key-item"><span class="bd-key-swatch is-expense"></span>Expenses</span>
                    </div>
                    <a href="{{ $profitLossUrl }}" class="btn btn-outline btn-sm">View Report</a>
                </div>
                <div class="card-body">
                    @if(! $hasFinancialActivity)
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></div>
                            <h4>No financial activity recorded yet</h4>
                            <p>Invoices and expenses logged against this building will appear here.</p>
                        </div>
                    @else
                        <div class="bd-plot">
                            <canvas id="financeChart" role="img"
                                    aria-label="Income and expenses for {{ $building->property_name }}, by month, {{ now()->year }}. The figures are listed below the chart."></canvas>
                        </div>
                        <p class="sr-only">
                            @foreach($dashboard['monthly']['labels'] as $i => $label)
                                @continue(($dashboard['monthly']['income'][$i] ?? null) === null)
                                {{ $label }}: income BHD {{ number_format($dashboard['monthly']['income'][$i], 0) }},
                                expenses BHD {{ number_format($dashboard['monthly']['expenses'][$i], 0) }}.
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>

            {{-- Three panels: how full it is, what it costs, how sound the
                 agreements are. --}}
            <div class="card-grid is-3">

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-text">
                            <h3 class="card-title">Occupancy</h3>
                            <p class="card-subtitle">Occupied vs vacant units</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($k['total_units'] > 0)
                            <div class="ring">
                                <svg class="ring-svg" viewBox="0 0 128 128" aria-hidden="true" focusable="false">
                                    <circle class="ring-track" cx="64" cy="64" r="52"></circle>
                                    <circle class="ring-arc is-success" cx="64" cy="64" r="52" stroke-dasharray="{{ $arc($occupancyFraction) }}"></circle>
                                </svg>
                                <div class="ring-centre">
                                    <div class="ring-figure {{ $k['occupancy_percent'] >= 50 ? 'is-success' : '' }}">{{ $k['occupancy_percent'] }}%</div>
                                    <div class="ring-caption">Occupied</div>
                                </div>
                            </div>
                            <div class="ring-key">
                                <div class="ring-key-row">
                                    <span class="ring-key-dot is-success" aria-hidden="true"></span>
                                    <span class="ring-key-label">Occupied</span>
                                    <span class="ring-key-val">{{ $k['occupied_units'] }}</span>
                                    <span class="ring-key-pct">{{ $k['occupancy_percent'] }}%</span>
                                </div>
                                <div class="ring-key-row">
                                    <span class="ring-key-dot is-faint" aria-hidden="true"></span>
                                    <span class="ring-key-label">Vacant</span>
                                    <span class="ring-key-val">{{ $k['vacant_units'] }}</span>
                                    <span class="ring-key-pct">{{ 100 - $k['occupancy_percent'] }}%</span>
                                </div>
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-door-open" aria-hidden="true"></i></div>
                                <h4>No units defined</h4>
                                <p>Add units to this building to track occupancy.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-text">
                            <h3 class="card-title">Expense Breakdown</h3>
                            <p class="card-subtitle">{{ now()->format('M Y') }} &middot; BHD {{ number_format($expenseTotal, 0) }} total</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($expenseTotal > 0)
                            <div class="meter-list">
                                @foreach($expenseRows as $label => $amount)
                                    <div class="meter-row">
                                        <div class="meter-head">
                                            <span class="meter-label">{{ $label }}</span>
                                            <span class="meter-val">BHD {{ number_format($amount, 0) }}</span>
                                        </div>
                                        <div class="meter-track">
                                            <div class="meter-fill" style="width: {{ round($amount / $expenseTotal * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-receipt" aria-hidden="true"></i></div>
                                <h4>No expenses this month</h4>
                                <p>Utility bills and logged expenses will break down here.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-header-text">
                            <h3 class="card-title">Agreement Status</h3>
                            <p class="card-subtitle">Lease lifecycle tracking</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($leaseTotal > 0)
                            {{-- Two arcs: the gold one behind carries active +
                                 expiring, the green one in front just active,
                                 so the gap between them IS the expiring set. --}}
                            <div class="ring">
                                <svg class="ring-svg" viewBox="0 0 128 128" aria-hidden="true" focusable="false">
                                    <circle class="ring-track" cx="64" cy="64" r="52"></circle>
                                    <circle class="ring-arc is-accent is-behind" cx="64" cy="64" r="52"
                                            stroke-dasharray="{{ $arc(($leaseCounts['active'] + $leaseCounts['expiring']) / $leaseTotal) }}"></circle>
                                    <circle class="ring-arc is-success" cx="64" cy="64" r="52"
                                            stroke-dasharray="{{ $arc($leaseCounts['active'] / $leaseTotal) }}"></circle>
                                </svg>
                                <div class="ring-centre">
                                    <div class="ring-figure {{ $activeLeasePercent >= 50 ? 'is-success' : '' }}">{{ $activeLeasePercent }}%</div>
                                    <div class="ring-caption">Active</div>
                                </div>
                            </div>
                            <div class="ring-key">
                                @foreach([
                                    ['Active', 'is-success', $leaseCounts['active']],
                                    ['Expiring soon', 'is-accent', $leaseCounts['expiring']],
                                    ['Upcoming', 'is-faint', $leaseCounts['upcoming']],
                                    ['Expired', 'is-danger', $leaseCounts['expired']],
                                ] as [$label, $tone, $count])
                                    <div class="ring-key-row">
                                        <span class="ring-key-dot {{ $tone }}" aria-hidden="true"></span>
                                        <span class="ring-key-label">{{ $label }}</span>
                                        <span class="ring-key-val">{{ $count }}</span>
                                        <span class="ring-key-pct">{{ round($count / $leaseTotal * 100) }}%</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-file-contract" aria-hidden="true"></i></div>
                                <h4>No agreements yet</h4>
                                <p>Leases raised against this building will be tracked here.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- What needs attention. --}}
            <div class="card-grid is-pair">

                <div class="card">
                    <div class="card-header">
                        <span class="card-header-icon is-warning" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></span>
                        <div class="card-header-text">
                            <h3 class="card-title">Upcoming Expirations</h3>
                            <p class="card-subtitle">Agreements ending within 60 days</p>
                        </div>
                    </div>
                    <div class="card-body is-flush">
                        @if($dashboard['upcoming_expirations']->isEmpty())
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></div>
                                <h4>Nothing expiring soon</h4>
                                <p>No agreements end in the next 60 days.</p>
                            </div>
                        @else
                            <div class="entity-list">
                                @foreach($dashboard['upcoming_expirations'] as $contract)
                                    @php $daysLeft = (int) now()->startOfDay()->diffInDays($contract->lease_end_date, false); @endphp
                                    <a href="{{ route('lease-contracts.show', $contract) }}" class="entity-row">
                                        <span class="daytile {{ $daysLeft <= 14 ? 'is-urgent' : '' }}" aria-hidden="true">{{ $daysLeft }}d</span>
                                        <span class="entity-row-text">
                                            <span class="entity-row-title">{{ $contract->tenant_name ?? $contract->tenant?->name ?? '—' }}</span>
                                            <span class="entity-row-sub">Unit {{ $contract->unit ?? '—' }} &middot; ends {{ $contract->lease_end_date->format('d M Y') }}</span>
                                        </span>
                                        <span class="badge {{ $daysLeft <= 14 ? 'badge-red' : 'badge-amber' }}">{{ $daysLeft }} days left</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-header-icon is-info" aria-hidden="true"><i class="fa-solid fa-wrench"></i></span>
                        <div class="card-header-text">
                            <h3 class="card-title">Recent Maintenance</h3>
                            <p class="card-subtitle">Latest requests for this building</p>
                        </div>
                    </div>
                    <div class="card-body is-flush">
                        @if($dashboard['recent_maintenance']->isEmpty())
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-wrench" aria-hidden="true"></i></div>
                                <h4>No maintenance yet</h4>
                                <p>No requests have been raised for this building.</p>
                            </div>
                        @else
                            <div class="entity-list">
                                @foreach($dashboard['recent_maintenance'] as $req)
                                    @php
                                        $badgeClass = match($req->status) {
                                            'completed' => 'badge-green',
                                            'in_progress', 'approved' => 'badge-blue',
                                            'cancelled' => 'badge-gray',
                                            default => 'badge-amber',
                                        };
                                    @endphp
                                    <a href="{{ route('maintenance.show', $req) }}" class="entity-row">
                                        <span class="stat-icon blue" aria-hidden="true"><i class="fa-solid fa-toolbox"></i></span>
                                        <span class="entity-row-text">
                                            <span class="entity-row-title">{{ $req->job_order }}</span>
                                            <span class="entity-row-sub">{{ $req->flat ? 'Unit '.$req->flat.' · ' : '' }}{{ $req->date?->format('d M Y') ?? '—' }}</span>
                                        </span>
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== 2. FLOORS ===================== --}}
        <div class="tab-panel" id="panel-floors" role="tabpanel" aria-labelledby="floors-tab" tabindex="0">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-text">
                        <h3 class="card-title">Building Floors</h3>
                        <p class="card-subtitle">Occupancy by floor</p>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="openAddFloor()">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Floor
                    </button>
                </div>
                <div class="card-body is-flush">
                    @if($floors->isNotEmpty())
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Floor</th>
                                        <th>Code</th>
                                        <th>Block</th>
                                        <th class="num">Units</th>
                                        <th class="num">Occupied</th>
                                        <th>Occupancy</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($floors as $floor)
                                        @php
                                            $floorUnits = $unitsByFloor->get($floor->id, collect());
                                            $floorTotal = $floorUnits->count();
                                            $floorLet   = $floorUnits->filter(fn ($u) => $u->activeContract !== null)->count();
                                            $floorPct   = $floorTotal > 0 ? round($floorLet / $floorTotal * 100) : 0;
                                        @endphp
                                        <tr data-href="{{ route('floors.edit', $floor) }}">
                                            <td data-label="Floor" class="cell-title">{{ $floor->floor_name }}</td>
                                            <td data-label="Code">@if($floor->floor_code)<span class="badge badge-gray">{{ $floor->floor_code }}</span>@else<span class="cell-muted">—</span>@endif</td>
                                            <td data-label="Block">{{ $floor->block_name ?? '—' }}</td>
                                            <td data-label="Units" class="num">{{ $floorTotal ?: ($floor->total_no_of_units ?? '—') }}</td>
                                            <td data-label="Occupied" class="num">{{ $floorLet }}</td>
                                            <td data-label="Occupancy">
                                                <div class="meter-head">
                                                    <span class="meter-label">{{ $floorPct }}% let</span>
                                                </div>
                                                <div class="meter-track">
                                                    <div class="meter-fill {{ $floorPct === 100 ? 'is-success' : '' }}" style="width: {{ $floorPct }}%"></div>
                                                </div>
                                            </td>
                                            <td data-label="Actions" class="col-actions" onclick="event.stopPropagation()">
                                                <div class="action-btns">
                                                    <a href="{{ route('floors.edit', $floor) }}" class="btn btn-ghost btn-sm btn-icon" aria-label="Edit {{ $floor->floor_name }}"><i class="fa-regular fa-pen-to-square"></i></a>
                                                    <form method="POST" action="{{ route('floors.destroy', $floor) }}" onsubmit="return confirm('Delete this floor?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-ghost btn-sm btn-icon bd-danger-ink" aria-label="Delete {{ $floor->floor_name }}"><i class="fa-regular fa-trash-can"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></div>
                            <h4>No floors yet</h4>
                            <p>Define the building's floors to track occupancy level by level.</p>
                            <button type="button" class="btn btn-primary" onclick="openAddFloor()">Create Floor</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== 3. UNITS ===================== --}}
        <div class="tab-panel" id="panel-units" role="tabpanel" aria-labelledby="units-tab" tabindex="0">

            @if($dashboard['unit_conditions']->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-text">
                            <h3 class="card-title">Unit Condition</h3>
                            <p class="card-subtitle">Mix across all {{ $units->count() }} units</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="meter-list">
                            @foreach($dashboard['unit_conditions'] as $condition => $count)
                                <div class="meter-row">
                                    <div class="meter-head">
                                        <span class="meter-label">{{ $condition }}</span>
                                        <span class="meter-val">{{ $count }}</span>
                                    </div>
                                    <div class="meter-track">
                                        <div class="meter-fill" style="width: {{ round($count / max(1, $units->count()) * 100) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <div class="card-header-text">
                        <h3 class="card-title">Property Units</h3>
                        <p class="card-subtitle">{{ $k['occupied_units'] }} let &middot; {{ $k['vacant_units'] }} vacant</p>
                    </div>
                    <a href="{{ route('property-units.index', ['property_code' => $building->property_code]) }}" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="card-body is-flush">
                    @if($units->isNotEmpty())
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Floor</th>
                                        <th>Type</th>
                                        <th>Condition</th>
                                        <th class="num">Rent / month</th>
                                        <th>Tenant</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($units as $unit)
                                        <tr data-href="{{ route('property-units.show', $unit) }}">
                                            <td data-label="Unit" class="cell-title">{{ $unit->unit_name }}</td>
                                            <td data-label="Floor" class="cell-muted">{{ $unit->floor?->floor_name ?? '—' }}</td>
                                            <td data-label="Type" class="cell-muted">{{ $unit->unit_type ?? '—' }}</td>
                                            <td data-label="Condition">@if($unit->unit_condition)<span class="badge badge-gray">{{ $unit->unit_condition }}</span>@else<span class="cell-muted">—</span>@endif</td>
                                            <td data-label="Rent / month" class="num num-strong">{{ $unit->rent_per_month ? 'BHD '.number_format($unit->rent_per_month, 3) : '—' }}</td>
                                            <td data-label="Tenant" class="cell-muted">{{ $unit->activeContract?->tenant_name ?? '—' }}</td>
                                            <td data-label="Status">
                                                @if($unit->activeContract)
                                                    <span class="badge badge-green">Occupied</span>
                                                @else
                                                    <span class="badge badge-gray">Vacant</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-door-open" aria-hidden="true"></i></div>
                            <h4>No units yet</h4>
                            <p>Units linked to this building will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== 4. TENANTS ===================== --}}
        <div class="tab-panel" id="panel-tenants" role="tabpanel" aria-labelledby="tenants-tab" tabindex="0">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-text">
                        <h3 class="card-title">Tenants</h3>
                        <p class="card-subtitle">Everyone holding an agreement in this building</p>
                    </div>
                    <a href="{{ route('tenants.index') }}" class="btn btn-outline btn-sm">All Tenants</a>
                </div>
                <div class="card-body is-flush">
                    @if($tenants->isNotEmpty())
                        <div class="entity-list">
                            @foreach($tenants as $t)
                                @php
                                    $tenantContracts = $contracts->where('tenant_id', $t->id);
                                    $activeContracts = $tenantContracts->filter(fn ($c) => in_array($c->status, ['active', 'expiring', 'upcoming'], true))->count();
                                    $tenantUnits = $tenantContracts->pluck('unit')->filter()->unique()->implode(', ');
                                @endphp
                                <a href="{{ route('tenants.show', $t) }}" class="entity-row">
                                    <span class="bd-initial" aria-hidden="true">{{ strtoupper(mb_substr($t->name, 0, 1)) }}</span>
                                    <span class="entity-row-text">
                                        <span class="entity-row-title">{{ $t->name }}</span>
                                        <span class="entity-row-sub">{{ $tenantUnits ? 'Unit '.$tenantUnits : 'No unit on file' }} &middot; {{ $t->phone ?? 'No phone' }}</span>
                                    </span>
                                    <span class="badge {{ $activeContracts > 0 ? 'badge-green' : 'badge-gray' }}">{{ $activeContracts > 0 ? $activeContracts.' active' : 'None active' }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
                            <h4>No tenants yet</h4>
                            <p>Tenants with lease agreements here will appear automatically.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== 5. AGREEMENTS ===================== --}}
        <div class="tab-panel" id="panel-agreements" role="tabpanel" aria-labelledby="agreements-tab" tabindex="0">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-text">
                        <h3 class="card-title">Lease Agreements</h3>
                        <p class="card-subtitle">{{ $leaseCounts['active'] }} active &middot; {{ $leaseCounts['expiring'] }} expiring &middot; {{ $leaseCounts['expired'] }} expired</p>
                    </div>
                    <a href="{{ route('lease-contracts.index', ['property_code' => $building->property_code]) }}" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="card-body is-flush">
                    @if($contracts->isNotEmpty())
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ref</th>
                                        <th>Tenant</th>
                                        <th>Unit</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th class="num">Rent / month</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contracts as $c)
                                        @php
                                            $badgeClass = match($c->status) {
                                                'active' => 'badge-green',
                                                'expiring' => 'badge-amber',
                                                'upcoming' => 'badge-blue',
                                                'expired' => 'badge-red',
                                                default => 'badge-gray',
                                            };
                                        @endphp
                                        <tr data-href="{{ route('lease-contracts.show', $c) }}">
                                            <td data-label="Ref" class="cell-title">{{ $c->lease_agreement_no }}</td>
                                            <td data-label="Tenant">{{ $c->tenant_name ?? $c->tenant?->name ?? '—' }}</td>
                                            <td data-label="Unit" class="cell-muted">{{ $c->unit ?? '—' }}</td>
                                            <td data-label="Start" class="cell-muted">{{ $c->lease_start_date?->format('d M Y') ?? '—' }}</td>
                                            <td data-label="End" class="cell-muted">{{ $c->lease_end_date?->format('d M Y') ?? '—' }}</td>
                                            <td data-label="Rent / month" class="num num-strong">{{ $c->rent_per_month ? ($c->currency ?? 'BHD').' '.number_format($c->rent_per_month, 3) : '—' }}</td>
                                            <td data-label="Status"><span class="badge {{ $badgeClass }}">{{ ucfirst($c->status) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-file-contract" aria-hidden="true"></i></div>
                            <h4>No agreements yet</h4>
                            <p>Leases raised against this building will be listed here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== 6. PHOTOS ===================== --}}
        <div class="tab-panel" id="panel-photos" role="tabpanel" aria-labelledby="photos-tab" tabindex="0">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-text">
                        <h3 class="card-title">Building Photos</h3>
                        <p class="card-subtitle">The first photo is used as the building's mark</p>
                    </div>
                </div>
                <div class="card-body">
                    @if($building->images->isNotEmpty())
                        <div class="bd-photo-grid">
                            @foreach($building->images as $img)
                                <div class="bd-thumb">
                                    <img src="{{ $img->url }}" alt="Photo of {{ $building->property_name }}" onclick="openLightbox('{{ $img->url }}')">
                                    <form method="POST" action="{{ route('buildings.images.destroy', [$building, $img]) }}" onsubmit="return confirm('Remove photo?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bd-thumb-remove" aria-label="Remove photo"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('buildings.images.store', $building) }}" enctype="multipart/form-data" id="photoUploadForm">
                        @csrf
                        <button type="button" class="bd-drop {{ $building->images->isNotEmpty() ? 'is-secondary' : '' }}"
                                onclick="document.getElementById('photoFileInput').click()">
                            <div class="empty-icon"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></div>
                            <div class="bd-drop-title">{{ $building->images->isEmpty() ? 'No photos yet' : 'Add more photos' }}</div>
                            <div class="bd-drop-sub">Drop files here or click to browse &middot; JPG, PNG or WEBP &middot; max 4 MB &middot; up to 10 photos</div>
                        </button>
                        <input type="file" id="photoFileInput" name="images[]" multiple accept="image/jpeg,image/png,image/webp" hidden
                               onchange="document.getElementById('photoUploadForm').submit();">
                    </form>
                </div>
            </div>
        </div>

        {{-- ===================== 7. SETTINGS ===================== --}}
        <div class="tab-panel" id="panel-settings" role="tabpanel" aria-labelledby="settings-tab" tabindex="0">
            <div class="card bd-settings">
                <div class="card-header">
                    <div class="card-header-text">
                        <h3 class="card-title">Tax &amp; VAT</h3>
                        <p class="card-subtitle">Applied to invoices raised for units in this building</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('buildings.settings.update', $building) }}">
                    @csrf @method('PUT')
                    <div class="card-body">
                        <div class="bd-switch-row">
                            <div class="bd-switch-copy">
                                <div class="bd-drop-title">Charge VAT on this building</div>
                                <p class="bd-drop-sub">When active, invoices for units here default to the rate below. Otherwise they are raised VAT-exempt.</p>
                            </div>
                            <div class="form-check">
                                <input type="hidden" name="vat_enabled" value="0">
                                <input type="checkbox" id="vatToggle" name="vat_enabled" value="1" {{ $building->vat_enabled ? 'checked' : '' }}>
                                <label for="vatToggle">Enabled</label>
                            </div>
                        </div>

                        <div class="form-grid bd-rate-grid">
                            <div class="form-group">
                                <label for="vatRateInput">VAT rate</label>
                                <div class="bd-rate">
                                    <input id="vatRateInput" type="number" name="vat_rate" step="0.01" min="0" max="100"
                                           value="{{ old('vat_rate', $building->vat_rate ?: 0) }}" {{ $building->vat_enabled ? '' : 'disabled' }}>
                                    <span class="bd-rate-unit">%</span>
                                </div>
                                @error('vat_rate') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer is-split">
                        <a href="{{ route('buildings.index') }}" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check" aria-hidden="true"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

{{-- MODALS & LIGHTBOX --}}
<!-- Add Floor Modal — the app's .modal-overlay / .modal-box, opened and
     closed by the same openX()/closeX() convention every other page uses. -->
<div class="modal-overlay" id="addFloorModal" role="dialog" aria-modal="true" aria-labelledby="addFloorTitle">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-header-top">
                <span class="modal-header-icon"><i class="fa-solid fa-layer-group"></i></span>
                <div class="modal-header-text">
                    <div class="modal-header-title" id="addFloorTitle">Add Floor</div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeAddFloor()" title="Close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <form method="POST" action="{{ route('buildings.floors.store', $building) }}">
            @csrf
            <input type="hidden" name="_modal" value="add_floor">
            <div class="modal-body">
                @if($errors->any() && old('_modal') === 'add_floor')
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> Please fix the errors below.
                    </div>
                @endif
                <div class="form-grid">
                    <div class="form-group col-span-full">
                        <label for="af_floor_name">Floor Name <span class="req">*</span></label>
                        <input id="af_floor_name" type="text" name="floor_name" value="{{ old('floor_name') }}" placeholder="e.g. Ground Floor" required>
                        @error('floor_name') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="af_floor_code">Floor Code</label>
                        <input id="af_floor_code" type="text" name="floor_code" value="{{ old('floor_code') }}" placeholder="e.g. GF">
                        @error('floor_code') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label for="af_total_units">Total Units</label>
                        <input id="af_total_units" type="number" name="total_no_of_units" value="{{ old('total_no_of_units') }}" min="1">
                    </div>
                    <div class="form-group">
                        <label for="af_block_name">Block Name</label>
                        <input id="af_block_name" type="text" name="block_name" value="{{ old('block_name') }}" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label for="af_block_code">Block Code</label>
                        <input id="af_block_code" type="text" name="block_code" value="{{ old('block_code') }}">
                    </div>
                </div>
            </div>
            <div class="card-footer is-split">
                <button type="button" class="btn btn-outline" onclick="closeAddFloor()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Floor</button>
            </div>
        </form>
    </div>
</div>

<!-- Photo Lightbox — same overlay component, no backdrop utilities -->
<div id="photoLightbox" class="modal-overlay is-lightbox" role="dialog" aria-modal="true" aria-label="Photo" onclick="closeLightbox()">
    <img id="lightboxImg" src="" alt="" class="lightbox-img">
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    // ── Tabs ────────────────────────────────────────────────────────────
    // Each .tab-btn carries data-tab-target, panels are hidden until shown,
    // ?tab=<id> deep-links, and a programmatic .click() on a tab button still
    // works — several KPI cards on this page navigate that way.
    (function () {
        const bar = document.getElementById('buildingTabs');
        if (!bar) return;
        const tabs = [...bar.querySelectorAll('.tab-btn')];

        function show(btn, pushUrl) {
            const target = document.querySelector(btn.dataset.tabTarget);
            if (!target) return;
            tabs.forEach((t) => {
                const on = t === btn;
                t.classList.toggle('active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                const panel = document.querySelector(t.dataset.tabTarget);
                if (panel) panel.classList.toggle('active', on);
            });
            if (pushUrl) {
                const url = new URL(window.location);
                url.searchParams.set('tab', btn.id.replace('-tab', ''));
                window.history.replaceState({}, '', url);
            }
            // A chart inside a panel that was hidden at draw time needs a nudge.
            window.dispatchEvent(new Event('resize'));
        }

        tabs.forEach((t) => t.addEventListener('click', () => show(t, true)));

        const wanted = new URLSearchParams(window.location.search).get('tab');
        const initial = wanted && document.getElementById(wanted + '-tab');
        if (initial) show(initial, false);
    })();

    document.addEventListener('DOMContentLoaded', function () {
        // Re-open the Add Floor modal when its own submit failed validation.
        @if($errors->any() && old('_modal') === 'add_floor')
            openAddFloor();
        @endif

        const vatToggle = document.getElementById('vatToggle');
        const vatRateInput = document.getElementById('vatRateInput');
        if (vatToggle && vatRateInput) {
            vatToggle.addEventListener('change', function () {
                vatRateInput.disabled = !this.checked;
                if (this.checked) vatRateInput.focus();
            });
        }
    });

    // ── Add Floor modal ────────────────────────────────────────────────
    function openAddFloor() {
        document.getElementById('addFloorModal').classList.add('open');
        document.body.style.overflow = 'hidden';
        document.getElementById('af_floor_name')?.focus();
    }
    function closeAddFloor() {
        document.getElementById('addFloorModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    // ── Photo lightbox ─────────────────────────────────────────────────
    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('photoLightbox').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('photoLightbox').classList.remove('open');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeLightbox();
        closeAddFloor();
    });

    // ── Financial performance ──────────────────────────────────────────
    // The only canvas left on this page. The four donuts it used to draw are
    // now SVG rings (app-core §4.14): they need no JavaScript, re-theme with
    // the tokens, and their centre figure is real text.
    //
    // A canvas still cannot read a CSS custom property, so every colour handed
    // to Chart.js is resolved here first and re-resolved when the theme flips.
    (function () {
        const canvas = document.getElementById('financeChart');
        if (!canvas || typeof Chart === 'undefined') return;

        const ink = (token, fallback) =>
            getComputedStyle(document.documentElement).getPropertyValue(token).trim() || fallback;

        Chart.defaults.font.family = "'Poppins', sans-serif";

        /* A rounded top on a zero-height bar draws a 2px nub on the baseline —
           eight of them, in a year that has only started. Round only the bars
           that have a height. */
        const roundedTop = (c) => (Number(c.raw) > 0 ? { topLeft: 4, topRight: 4 } : 0);

        // Paired bars, navy against gold — the brand's two-series pairing, not
        // the semantic green/red, because neither series is "bad" here.
        // No y axis and no gridlines: the design reads this card as a shape,
        // and the exact figures are in the KPI strip above, the tooltips, and
        // the visually-hidden list beneath the canvas.
        const chart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($dashboard['monthly']['labels']) !!},
                datasets: [
                    {
                        label: 'Income',
                        data: {!! json_encode($dashboard['monthly']['income']) !!},
                        backgroundColor: ink('--chart-primary', '#1E2C4F'),
                        borderRadius: roundedTop,
                    },
                    {
                        label: 'Expenses',
                        data: {!! json_encode($dashboard['monthly']['expenses']) !!},
                        backgroundColor: ink('--chart-accent', '#D8B25F'),
                        borderRadius: roundedTop,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                barThickness: 13,
                interaction: { mode: 'index', intersect: false },
                layout: { padding: { top: 8 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: ink('--text-primary', '#1E2C4F'),
                        titleColor: ink('--card-bg', '#FFFFFF'),
                        bodyColor: ink('--card-bg', '#FFFFFF'),
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        bodyFont: { size: 12, weight: 'bold' },
                        callbacks: {
                            label: (c) => c.dataset.label + ': BHD ' + Number(c.parsed.y).toLocaleString(),
                            footer: (items) => {
                                const income = items.find((i) => i.dataset.label === 'Income')?.parsed.y ?? 0;
                                const spend  = items.find((i) => i.dataset.label === 'Expenses')?.parsed.y ?? 0;
                                return 'Net: BHD ' + Number(income - spend).toLocaleString();
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false, drawTicks: false, tickLength: 0 },
                        border: { color: ink('--row-border', '#EEF1F6') },
                        offset: true,
                        ticks: { font: { size: 10 }, color: ink('--text-muted', '#626E85'), padding: 8 },
                    },
                    y: { display: false, beginAtZero: true },
                },
            },
        });

        // Chart.js holds resolved colours, so a theme toggle repaints the
        // tokens but not the canvas.
        new MutationObserver(function () {
            chart.data.datasets[0].backgroundColor = ink('--chart-primary');
            chart.data.datasets[1].backgroundColor = ink('--chart-accent');
            chart.options.plugins.tooltip.backgroundColor = ink('--text-primary');
            chart.options.plugins.tooltip.titleColor = ink('--card-bg');
            chart.options.plugins.tooltip.bodyColor = ink('--card-bg');
            chart.options.scales.x.border.color = ink('--row-border');
            chart.options.scales.x.ticks.color = ink('--text-muted');
            chart.update('none');
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    })();
</script>
@endpush
