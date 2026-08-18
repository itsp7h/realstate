@extends('layouts.admin')

@section('title', $building->property_name)
@section('topbar-title', 'Building Detail')

{{-- Bootstrap is here only for this page's grid, tab and modal JS. It goes in
     the vendor slot, which loads BEFORE app-core.css, so the design system's
     .card / .btn / .badge / .table / .breadcrumb win. When it sat in
     @push('styles') it loaded after the design system and beat it, which is
     why this page used to carry ~200 lines of CSS re-implementing the shared
     page header, buttons and table just to look like everywhere else. --}}
@push('vendor-styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
@endpush

@push('styles')
<style>
    /* Headings use the display face, as they do everywhere else. Note there is
       deliberately no `body` override here: this page used to set
       background-color: #f8fafc and font-family: Outfit on body, which broke
       dark mode on this page alone and set body copy in the display face. */
    h1, h2, h3, h4, h5, h6 {
        font-family: var(--font-display);
        font-weight: 700;
    }

    /* Building Tabs — segmented pill bar */
    .building-tabs {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: nowrap;
        overflow-x: auto;
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 14px;
        padding: 6px;
        margin-bottom: var(--sp-6);
        box-shadow: var(--shadow-sm));
    }
    .building-tabs .nav-item.ms-auto-tab { margin-left: auto; }
    .building-tabs .nav-item.ms-auto-tab::before {
        content: "";
        display: inline-block;
        width: 1px;
        height: 22px;
        background: var(--card-border);
        margin-right: 4px;
        vertical-align: middle;
    }
    .building-tabs .nav-link {
        display: flex;
        align-items: center;
        white-space: nowrap;
        color: var(--text-secondary);
        background-color: transparent;
        font-weight: 600;
        font-size: var(--fs-base);
        border-radius: 10px;
        padding: var(--sp-2) 18px;
        min-height: var(--h-control);
        border: none;
        transition: background-color 0.16s ease, color 0.16s ease;
    }
    .building-tabs .nav-link:hover {
        color: var(--text-primary);
        background-color: var(--page-bg);
    }
    .building-tabs .nav-link.active {
        color: #fff;
        background: var(--accent);
        box-shadow: 0 4px 10px -2px var(--accent-glow));
    }
    .building-tabs .nav-link.active:hover {
        color: #fff;
        background: var(--accent);
    }
    .building-tabs .nav-link:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px var(--accent-dim));
    }
    .building-tabs .nav-link .badge {
        font-size: 0.68rem;
        font-weight: 700;
        margin-left: 0.5rem;
        padding: 0.2rem 0.45rem;
        border-radius: 999px;
        background-color: rgba(255, 255, 255, 0.35);
        color: inherit;
    }
    .building-tabs .nav-link:not(.active) .badge {
        background-color: var(--page-bg);
        color: var(--text-muted);
    }

    /* File Upload Dropzone */
    .upload-zone {
        border: 2px dashed var(--input-border);
        border-radius: var(--radius);
        padding: var(--sp-10) var(--sp-6);
        text-align: center;
        background: var(--page-bg-alt);
        color: var(--text-secondary);
        cursor: pointer;
        transition: border-color var(--dur-base) ease, background var(--dur-base) ease;
    }
    .upload-zone:hover, .upload-zone.drag-over {
        border-color: var(--accent);
        background: var(--tone-accent-bg);
    }
    .upload-zone:focus-visible { outline: var(--focus-outline); outline-offset: 2px; }

    /* Photo Thumbnails */
    .photo-thumb {
        position: relative;
        border-radius: var(--radius-sm);
        overflow: hidden;
        aspect-ratio: 4/3;
        border: 1px solid var(--card-border);
    }
    .photo-thumb img {
        width: 100%; height: 100%; object-fit: cover;
        transition: transform 0.3s ease;
        cursor: zoom-in;
    }
    .photo-thumb:hover img { transform: scale(1.05); }
    .photo-delete-btn {
        position: absolute; top: 8px; right: 8px;
        width: 28px; height: 28px; border-radius: 50%;
        background: var(--danger); border: none; color: #fff;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transform: scale(0.8); transition: all 0.2s;
    }
    .photo-thumb:hover .photo-delete-btn,
    .photo-thumb:focus-within .photo-delete-btn {
        opacity: 1; transform: scale(1);
    }
    /* Hover-only reveal leaves this unreachable by keyboard and on touch. */
    @media (hover: none) {
        .photo-delete-btn { opacity: 1; transform: scale(1); }
    }

    /* Clip children to the card's radius, so a photo or a table that runs to
       the card's edge follows its corners. The .rounded-4 utilities still in
       this page's markup are inert now that the design system's .card loads
       after Bootstrap and sets the radius itself. */
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
        'let'     => ['label' => 'Let',     'tint' => 'var(--pm-page)',        'tone' => 'var(--pm-text-2)'],
        'paid'    => ['label' => 'Paid',     'tint' => 'var(--pm-green-tint)', 'tone' => 'var(--pm-green-text)'],
        'overdue' => ['label' => 'Overdue', 'tint' => 'var(--pm-red-tint)',    'tone' => 'var(--pm-red)'],
        'vacant'  => ['label' => 'Vacant',   'tint' => 'var(--pm-page)',       'tone' => 'var(--pm-text-3)'],
    ];
@endphp

{{-- MOBILE: pushed-screen header with a back chevron --}}
<div class="pm-push-header">
    <a href="{{ route('buildings.index') }}" class="pm-push-back"><i class="fa-solid fa-chevron-left"></i></a>
    <div class="pm-header-text">
        <div class="pm-title" style="font-size:19px;">{{ $building->property_name }}</div>
        <div class="pm-subtitle">{{ $building->property_type ?? 'Property' }}</div>
    </div>
    <button type="button" class="pm-icon-btn" title="Notifications — coming soon"><i class="fa-regular fa-bell"></i></button>
    <div class="pm-avatar" style="font-size:14px;">{{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}</div>
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

    <div style="background:linear-gradient(135deg,#10141F,#232B42);border-radius:18px;padding:20px;display:flex;gap:24px;">
        <div style="flex:1;"><div style="font-size:10px;letter-spacing:1px;font-weight:600;color:#9FB0CE;">INCOME &middot; {{ now()->format('M') }}</div><div style="font-size:21px;font-weight:800;color:#7ED8AC;">BHD {{ number_format($dashboard['kpis']['month_income'], 0) }}</div></div>
        <div style="flex:1;"><div style="font-size:10px;letter-spacing:1px;font-weight:600;color:#9FB0CE;">NET PROFIT</div><div style="font-size:21px;font-weight:800;color:{{ $dashboard['kpis']['month_profit'] < 0 ? '#F0A5A5' : '#E7B266' }};">BHD {{ number_format($dashboard['kpis']['month_profit'], 0) }}</div></div>
    </div>

    <div class="m-mini-row">
        <div class="m-mini-stat"><div class="v">{{ $dashboard['kpis']['occupancy_percent'] }}%</div><div class="l">OCCUPANCY</div></div>
        <div class="m-mini-stat"><div class="v">{{ $dashboard['kpis']['occupied_units'] }}/{{ $dashboard['kpis']['total_units'] }}</div><div class="l">UNITS LET</div></div>
        <div class="m-mini-stat"><div class="v" style="font-size:14px;">{{ $building->property_type ?? '—' }}</div><div class="l">TYPE</div></div>
    </div>

    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <div class="pm-section-label" style="margin-bottom:0;">FLOORS &amp; UNITS</div>
            <div style="font-size:11px;font-weight:600;color:var(--pm-text-2);">
                Units <strong style="color:var(--pm-text);">{{ $dashboard['kpis']['total_units'] }}</strong>
                &nbsp;Let <strong style="color:var(--pm-green-text);">{{ $dashboard['kpis']['occupied_units'] }}</strong>
                &nbsp;Vacant <strong style="color:var(--pm-red);">{{ $dashboard['kpis']['vacant_units'] }}</strong>
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
                        <i class="fa-solid fa-layer-group" style="color:var(--pm-gold);font-size:13px;width:16px;text-align:center;"></i>
                        <div style="flex:1;min-width:0;">
                            <div class="pm-floor-name">{{ $group['name'] }}</div>
                            <div class="pm-floor-meta">{{ $rows->count() }} units &middot; {{ $group['letCount'] }} let</div>
                        </div>
                        <i class="fa-solid fa-chevron-{{ $isOpen ? 'up' : 'down' }}" style="color:var(--pm-border-strong);font-size:12px;" data-floor-chevron="{{ $group['id'] }}"></i>
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

    {{-- PAGE HEADER --}}
    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <a href="{{ url('/dashboard') }}">Home</a>
                <i class="fa-solid fa-chevron-right"></i>
                <a href="{{ route('buildings.index') }}">Buildings</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>{{ $building->property_name }}</span>
            </div>
            <h1 class="page-header-title">{{ $building->property_name }}</h1>
            <p class="page-header-sub">{{ $building->property_code }} &middot; {{ $building->full_address ?? 'No address on file' }}</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('buildings.edit', $building) }}" class="btn btn-primary">
                <i class="fa-regular fa-pen-to-square"></i> Edit Building
            </a>
        </div>
    </div>

    {{-- BUILDING INFO CARD --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">

            <div class="d-flex align-items-center gap-4">
                {{-- Building Thumbnail --}}
                @php $firstImage = $building->images->first(); @endphp
                @if($firstImage)
                    <img src="{{ $firstImage->url }}" alt="{{ $building->property_name }}" class="rounded-4 shadow-sm" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #F1F5F9;">
                @else
                    <div class="rounded-4 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; background: #F1F5F9; border: 3px solid #F1F5F9;">
                        <i class="fa-solid fa-building fs-1 text-muted"></i>
                    </div>
                @endif

                {{-- Meta --}}
                <div>
                    <div class="text-muted small fw-semibold text-uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-location-dot me-1"></i> {{ $building->full_address ?? $building->property_code }}
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-warning text-dark rounded-pill px-3">{{ $building->property_code }}</span>
                        @if($building->property_type)
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">{{ $building->property_type }}</span>
                        @endif
                        <span class="text-muted small ms-1"><i class="fa-solid fa-door-open me-1"></i> {{ $dashboard['kpis']['total_units'] }} Units &nbsp;&bull;&nbsp; <i class="fa-solid fa-layer-group me-1"></i> {{ $dashboard['kpis']['total_floors'] }} Floors</span>
                    </div>
                </div>
            </div>

            {{-- Occupancy --}}
            <div class="text-center d-none d-sm-block">
                <div class="fw-bold fs-3 text-success">{{ $dashboard['kpis']['occupancy_percent'] }}%</div>
                <div class="text-muted small text-uppercase" style="letter-spacing: 1px;">Occupied</div>
            </div>
        </div>
    </div>

    {{-- BUILDING TABS --}}
    <ul class="nav building-tabs" id="buildingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="dashboard-tab" data-bs-toggle="pill" data-bs-target="#panel-dashboard" type="button" role="tab">
                <i class="fa-solid fa-chart-pie me-2"></i> Dashboard
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="floors-tab" data-bs-toggle="pill" data-bs-target="#panel-floors" type="button" role="tab">
                <i class="fa-solid fa-layer-group me-2"></i> Floors
                <span class="badge rounded-pill">{{ $floors->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="units-tab" data-bs-toggle="pill" data-bs-target="#panel-units" type="button" role="tab">
                <i class="fa-solid fa-door-open me-2"></i> Units
                <span class="badge rounded-pill">{{ $units->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tenants-tab" data-bs-toggle="pill" data-bs-target="#panel-tenants" type="button" role="tab">
                <i class="fa-solid fa-users me-2"></i> Tenants
                <span class="badge rounded-pill">{{ $tenants->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="agreements-tab" data-bs-toggle="pill" data-bs-target="#panel-agreements" type="button" role="tab">
                <i class="fa-solid fa-file-contract me-2"></i> Agreements
                <span class="badge rounded-pill">{{ $contracts->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="photos-tab" data-bs-toggle="pill" data-bs-target="#panel-photos" type="button" role="tab">
                <i class="fa-solid fa-images me-2"></i> Photos
                <span class="badge rounded-pill">{{ $building->images->count() }}</span>
            </button>
        </li>
        <li class="nav-item ms-auto-tab" role="presentation">
            <button class="nav-link" id="settings-tab" data-bs-toggle="pill" data-bs-target="#panel-settings" type="button" role="tab">
                <i class="fa-solid fa-gear me-2"></i> Settings
            </button>
        </li>
    </ul>

    {{-- TAB CONTENT WRAPPER --}}
    <div class="tab-content" id="buildingTabsContent">

        {{-- ===================== 1. DASHBOARD TAB ===================== --}}
        <div class="tab-pane fade show active" id="panel-dashboard" role="tabpanel" tabindex="0">

            @php
                $profitLossUrl = route('reports.profit-loss', ['building_id' => $building->id]);
                $unitsUrl = fn ($extra = []) => route('property-units.index', array_merge(['property_code' => $building->property_code], $extra));
                $conditionUrls = collect($dashboard['unit_conditions'])->keys()->map(fn ($l) => $unitsUrl(['unit_condition' => $l]))->values()->all();
                $leaseUrl = fn ($status) => route('lease-contracts.index', ['property_code' => $building->property_code, 'status' => $status]);
                $leaseStatusUrls = [$leaseUrl('active'), $leaseUrl('expiring'), $leaseUrl('upcoming'), $leaseUrl('expired')];
            @endphp

            {{-- KPI Grid --}}
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="document.getElementById('units-tab').click()">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-4 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;"><i class="fa-solid fa-door-open fs-4"></i></div>
                            <div>
                                <div class="fs-3 fw-bold lh-1 text-dark">{{ $dashboard['kpis']['total_units'] }}</div>
                                <div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">Total Units</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="document.getElementById('units-tab').click()">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="bg-success bg-opacity-10 text-success rounded-4 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;"><i class="fa-solid fa-key fs-4"></i></div>
                            <div>
                                <div class="fs-3 fw-bold lh-1 text-dark">{{ $dashboard['kpis']['occupied_units'] }}</div>
                                <div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">Occupied</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="document.getElementById('units-tab').click()">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-4 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;"><i class="fa-regular fa-square fs-4"></i></div>
                            <div>
                                <div class="fs-3 fw-bold lh-1 text-dark">{{ $dashboard['kpis']['vacant_units'] }}</div>
                                <div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">Vacant</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="document.getElementById('tenants-tab').click()">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;"><i class="fa-solid fa-users fs-4"></i></div>
                            <div>
                                <div class="fs-3 fw-bold lh-1 text-dark">{{ $dashboard['kpis']['tenant_count'] }}</div>
                                <div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">Tenants</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="document.getElementById('floors-tab').click()">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="bg-info bg-opacity-10 text-info rounded-4 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;"><i class="fa-solid fa-layer-group fs-4"></i></div>
                            <div>
                                <div class="fs-3 fw-bold lh-1 text-dark">{{ $dashboard['kpis']['total_floors'] }}</div>
                                <div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">Floors</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Financial KPIs --}}
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="window.location='{{ $profitLossUrl }}'">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="bg-success bg-opacity-10 text-success rounded-4 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;"><i class="fa-solid fa-arrow-trend-up fs-4"></i></div>
                            <div>
                                <div class="fs-4 fw-bold lh-1 text-dark">{{ number_format($dashboard['kpis']['month_income'], 0) }} <span class="fs-6 text-muted fw-normal">BHD</span></div>
                                <div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">Income (Month)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="window.location='{{ $profitLossUrl }}'">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-4 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;"><i class="fa-solid fa-file-invoice-dollar fs-4"></i></div>
                            <div>
                                <div class="fs-4 fw-bold lh-1 text-dark">{{ number_format($dashboard['kpis']['month_expense'], 0) }} <span class="fs-6 text-muted fw-normal">BHD</span></div>
                                <div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">Expense (Month)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="window.location='{{ $profitLossUrl }}'">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            @php $isProfit = $dashboard['kpis']['month_profit'] >= 0; @endphp
                            <div class="bg-{{ $isProfit ? 'primary' : 'danger' }} bg-opacity-10 text-{{ $isProfit ? 'primary' : 'danger' }} rounded-4 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;"><i class="fa-solid fa-sack-dollar fs-4"></i></div>
                            <div>
                                <div class="fs-4 fw-bold lh-1 text-{{ $isProfit ? 'primary' : 'danger' }}">{{ number_format($dashboard['kpis']['month_profit'], 0) }} <span class="fs-6 fw-normal">BHD</span></div>
                                <div class="text-muted small mt-1 fw-semibold text-uppercase tracking-wider">Net Profit (Month)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- THE LINE CHART (Fixed & Beautified) --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-chart-area text-primary me-2"></i> Financial Performance</h5>
                        <p class="text-muted small mb-0">Income, expenses & net profit for {{ now()->year }}</p>
                    </div>
                    <a href="{{ $profitLossUrl }}" class="btn btn-sm btn-light border shadow-sm">View Report</a>
                </div>
                <div class="card-body p-4">
                    @php
                        $hasFinancialActivity = collect(array_merge($dashboard['monthly']['income'], $dashboard['monthly']['expenses'], $dashboard['monthly']['profit']))
                            ->filter(fn ($v) => $v !== null && $v != 0)->isNotEmpty();
                    @endphp
                    @if(! $hasFinancialActivity)
                        <div class="text-center py-5">
                            <div class="text-muted mb-3"><i class="fa-solid fa-chart-line fa-3x opacity-25"></i></div>
                            <h6 class="text-muted fw-bold">No financial activity recorded yet</h6>
                            <p class="text-muted small">Invoices and expenses logged against this building will appear here.</p>
                        </div>
                    @else
                        <div style="position: relative; height: 320px; width: 100%;">
                            <canvas id="financeChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 4-COLUMN CHARTS --}}
            <div class="row g-4 mb-4">
                <!-- Occupancy Donut -->
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 is-interactive cursor-pointer" onclick="document.getElementById('units-tab').click()">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <h6 class="fw-bold mb-1 text-dark">Occupancy</h6>
                            <p class="text-muted small mb-0">Occupied vs vacant units</p>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center pb-4 px-4">
                            @if($dashboard['kpis']['total_units'] > 0)
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="occupancyChart"></canvas></div>
                            @else
                                <div class="text-center py-4 mt-auto mb-auto">
                                    <i class="fa-solid fa-door-open fs-1 text-muted opacity-25 mb-2"></i>
                                    <div class="text-muted small">No units defined</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Expense Donut -->
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <h6 class="fw-bold mb-1 text-dark">Expense Breakdown</h6>
                            <p class="text-muted small mb-0">{{ now()->format('M Y') }} Expenses</p>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center pb-4 px-4">
                            @if(array_sum($dashboard['expenses']) > 0)
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="expenseChart"></canvas></div>
                            @else
                                <div class="text-center py-4 mt-auto mb-auto">
                                    <i class="fa-solid fa-receipt fs-1 text-muted opacity-25 mb-2"></i>
                                    <div class="text-muted small">No expenses this month</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Unit Condition Donut -->
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <h6 class="fw-bold mb-1 text-dark">Unit Condition</h6>
                            <p class="text-muted small mb-0">Mix across all units</p>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center pb-4 px-4">
                            @if($dashboard['unit_conditions']->isNotEmpty())
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="conditionChart"></canvas></div>
                            @else
                                <div class="text-center py-4 mt-auto mb-auto">
                                    <i class="fa-solid fa-couch fs-1 text-muted opacity-25 mb-2"></i>
                                    <div class="text-muted small">No units defined</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Agreement Status Donut -->
                <div class="col-lg-3 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                            <h6 class="fw-bold mb-1 text-dark">Agreement Status</h6>
                            <p class="text-muted small mb-0">Lease lifecycle tracking</p>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center pb-4 px-4">
                            @if($dashboard['lease_status_counts']->sum() > 0)
                                <div style="position: relative; height: 200px; width: 100%;"><canvas id="leaseChart"></canvas></div>
                            @else
                                <div class="text-center py-4 mt-auto mb-auto">
                                    <i class="fa-solid fa-file-contract fs-1 text-muted opacity-25 mb-2"></i>
                                    <div class="text-muted small">No active agreements</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2-COLUMN LISTS (Leases & Maintenance) --}}
            <div class="row g-4 mb-4">
                <!-- Upcoming Expirations -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white pt-4 px-4 pb-3 border-bottom border-light">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-hourglass-half text-warning me-2"></i> Upcoming Expirations (60 Days)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush rounded-bottom-4">
                                @forelse($dashboard['upcoming_expirations'] as $contract)
                                    @php $daysLeft = now()->startOfDay()->diffInDays($contract->lease_end_date, false); @endphp
                                    <a href="{{ route('lease-contracts.show', $contract) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-3 border-light">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa-solid fa-file-contract"></i></div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6">{{ $contract->tenant_name ?? $contract->tenant?->name }}</div>
                                            <div class="text-muted small">{{ $contract->unit ?? '—' }} &middot; Ends {{ $contract->lease_end_date->format('d M Y') }}</div>
                                        </div>
                                        <span class="badge {{ $daysLeft <= 14 ? 'badge-red' : 'badge-amber' }} rounded-pill ms-auto py-2 px-3">{{ $daysLeft }}d left</span>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-muted small">No agreements expiring in the next 60 days.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Maintenance -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white pt-4 px-4 pb-3 border-bottom border-light">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-wrench text-info me-2"></i> Recent Maintenance</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush rounded-bottom-4">
                                @forelse($dashboard['recent_maintenance'] as $req)
                                    @php
                                        $badgeClass = match($req->status) {
                                            'completed' => 'badge-green',
                                            'in_progress', 'approved' => 'badge-blue',
                                            'cancelled' => 'badge-gray',
                                            default => 'badge-amber',
                                        };
                                    @endphp
                                    <a href="{{ route('maintenance.show', $req) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 p-3 border-light">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa-solid fa-toolbox"></i></div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6">{{ $req->job_order }} <span class="text-muted fw-normal ms-1">{{ $req->flat ?? '' }}</span></div>
                                            <div class="text-muted small">{{ $req->tenant ?? '—' }} &middot; {{ $req->date?->format('d M Y') }}</div>
                                        </div>
                                        <span class="badge {{ $badgeClass }} rounded-pill ms-auto py-2 px-3">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</span>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-muted small">No recent maintenance requests.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== 2. FLOORS TAB ===================== --}}
        <div class="tab-pane fade" id="panel-floors" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Building Floors</h5>
                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addFloorModal">
                        <i class="fa-solid fa-plus me-1"></i> Add Floor
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($floors->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Floor Name</th>
                                        <th>Code</th>
                                        <th>Block</th>
                                        <th>Units</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($floors as $floor)
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">{{ $floor->floor_name }}</td>
                                        <td>@if($floor->floor_code)<span class="badge badge-gray">{{ $floor->floor_code }}</span>@else<span class="text-muted">—</span>@endif</td>
                                        <td>{{ $floor->block_name ?? '—' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $floor->total_no_of_units ?? '—' }}</span></td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="{{ route('floors.edit', $floor) }}" class="btn btn-sm btn-light border text-secondary"><i class="fa-regular fa-pen-to-square"></i></a>
                                                <form method="POST" action="{{ route('floors.destroy', $floor) }}" onsubmit="return confirm('Delete this floor?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-light border text-danger"><i class="fa-regular fa-trash-can"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-layer-group fs-1 text-muted opacity-25 mb-3"></i>
                            <h6 class="fw-bold text-dark">No floors yet</h6>
                            <p class="text-muted small mb-4">Click Add Floor to define the first floor.</p>
                            <button class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addFloorModal">Create Floor</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== 3. UNITS TAB ===================== --}}
        <div class="tab-pane fade" id="panel-units" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Property Units</h5>
                    <a href="{{ route('property-units.index', ['property_code' => $building->property_code]) }}" class="btn btn-light border btn-sm rounded-pill px-3">View All</a>
                </div>
                <div class="card-body p-0">
                    @if($units->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Unit Name</th>
                                        <th>Floor / Type</th>
                                        <th>Condition</th>
                                        <th>Occupancy</th>
                                        <th class="text-end pe-4">Rent / Month</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($units as $unit)
                                    <tr class="cursor-pointer" onclick="window.location='{{ route('property-units.show', $unit) }}'">
                                        <td class="ps-4 fw-bold text-dark">{{ $unit->unit_name }}</td>
                                        <td>
                                            <div class="small text-muted mb-1">{{ $unit->floor?->floor_name ?? '—' }}</div>
                                            @if($unit->unit_type)<span class="badge badge-blue">{{ $unit->unit_type }}</span>@endif
                                        </td>
                                        <td>@if($unit->unit_condition)<span class="badge badge-gray">{{ $unit->unit_condition }}</span>@else<span class="text-muted">—</span>@endif</td>
                                        <td>
                                            @if($unit->activeContract)
                                                <span class="badge badge-green"><i class="fa-solid fa-circle text-success" style="font-size:6px; vertical-align:middle; margin-right:4px;"></i>Occupied</span>
                                                <div class="small text-muted mt-1">{{ $unit->activeContract->tenant_name }}</div>
                                            @else
                                                <span class="badge badge-gray"><i class="fa-regular fa-circle text-secondary" style="font-size:6px; vertical-align:middle; margin-right:4px;"></i>Vacant</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4 fw-bold text-dark">
                                            {{ $unit->rent_per_month ? 'BHD '.number_format($unit->rent_per_month, 3) : '—' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-door-open fs-1 text-muted opacity-25 mb-3"></i>
                            <h6 class="fw-bold text-dark">No units yet</h6>
                            <p class="text-muted small">Units linked to this building will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== 4. TENANTS TAB ===================== --}}
        <div class="tab-pane fade" id="panel-tenants" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Active Tenants</h5>
                    <a href="{{ route('tenants.index') }}" class="btn btn-light border btn-sm rounded-pill px-3">All Tenants</a>
                </div>
                <div class="card-body p-0">
                    @if($tenants->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Tenant Name</th>
                                        <th>Contact Info</th>
                                        <th>Type</th>
                                        <th class="text-end pe-4">Active Agreements</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tenants as $t)
                                    @php $activeContracts = $contracts->where('tenant_id', $t->id)->filter(fn($c) => in_array($c->status, ['active','expiring','upcoming']))->count(); @endphp
                                    <tr class="cursor-pointer" onclick="window.location='{{ route('tenants.show', $t) }}'">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                                                    {{ strtoupper(substr($t->name, 0, 1)) }}
                                                </div>
                                                <span class="fw-bold text-dark">{{ $t->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small text-dark mb-1"><i class="fa-solid fa-phone text-muted me-1" style="width:14px;"></i> {{ $t->phone ?? '—' }}</div>
                                            <div class="small text-muted"><i class="fa-solid fa-envelope text-muted me-1" style="width:14px;"></i> {{ $t->email ?? '—' }}</div>
                                        </td>
                                        <td>@if($t->tenant_type)<span class="badge badge-gray">{{ ucfirst($t->tenant_type) }}</span>@else—@endif</td>
                                        <td class="text-end pe-4">
                                            {!! $activeContracts > 0 ? "<span class='badge badge-green rounded-pill px-3 py-2'>$activeContracts active</span>" : '<span class="text-muted">—</span>' !!}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-users fs-1 text-muted opacity-25 mb-3"></i>
                            <h6 class="fw-bold text-dark">No tenants yet</h6>
                            <p class="text-muted small">Tenants with lease agreements here will appear automatically.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== 5. AGREEMENTS TAB ===================== --}}
        <div class="tab-pane fade" id="panel-agreements" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Lease Agreements</h5>
                    <a href="{{ route('lease-contracts.index', ['property_code' => $building->property_code]) }}" class="btn btn-light border btn-sm rounded-pill px-3">View All</a>
                </div>
                <div class="card-body p-0">
                    @if($contracts->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Agreement No.</th>
                                        <th>Tenant / Unit</th>
                                        <th>Lease Period</th>
                                        <th>Rent / Month</th>
                                        <th class="text-end pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contracts as $c)
                                    @php
                                        $badgeClass = match($c->status) {
                                            'active' => 'badge-green',
                                            'expiring' => 'badge-amber',
                                            'upcoming' => 'badge-blue',
                                            default => 'badge-gray',
                                        };
                                    @endphp
                                    <tr class="cursor-pointer" onclick="window.location='{{ route('lease-contracts.show', $c) }}'">
                                        <td class="ps-4 fw-bold text-dark">{{ $c->lease_agreement_no }}</td>
                                        <td>
                                            <div class="small fw-semibold text-dark mb-1">{{ $c->tenant_name ?? $c->tenant?->name ?? '—' }}</div>
                                            <div class="small text-muted"><i class="fa-solid fa-door-open me-1"></i> {{ $c->unit ?? '—' }}</div>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $c->lease_start_date?->format('d M Y') }} &nbsp;<i class="fa-solid fa-arrow-right mx-1 opacity-50"></i>&nbsp; {{ $c->lease_end_date?->format('d M Y') }}
                                        </td>
                                        <td class="fw-bold text-dark">{{ $c->rent_per_month ? ($c->currency ?? 'BHD').' '.number_format($c->rent_per_month, 3) : '—' }}</td>
                                        <td class="text-end pe-4">
                                            <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2">{{ ucfirst($c->status) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-file-contract fs-1 text-muted opacity-25 mb-3"></i>
                            <h6 class="fw-bold text-dark">No agreements yet</h6>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================== 6. PHOTOS TAB ===================== --}}
        <div class="tab-pane fade" id="panel-photos" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Building Photos</h5>
                </div>
                <div class="card-body p-4">

                    @if($building->images->isNotEmpty())
                        <div class="row g-3 mb-4">
                            @foreach($building->images as $img)
                            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                <div class="photo-thumb">
                                    <img src="{{ $img->url }}" alt="Photo" onclick="openLightbox('{{ $img->url }}')">
                                    <form method="POST" action="{{ route('buildings.images.destroy', [$building, $img]) }}" onsubmit="return confirm('Remove photo?')">
                                        @csrf @method('DELETE')
                                        <button class="photo-delete-btn"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('buildings.images.store', $building) }}" enctype="multipart/form-data" id="photoUploadForm">
                        @csrf
                        <div class="upload-zone" onclick="document.getElementById('photoFileInput').click()">
                            <i class="fa-solid fa-cloud-arrow-up fs-1 text-primary opacity-75 mb-3"></i>
                            <h6 class="fw-bold text-dark">Drop photos here or click to browse</h6>
                            <p class="text-muted small mb-0">JPG, PNG or WEBP &middot; Max 4 MB &middot; Up to 10 photos</p>
                            <input type="file" id="photoFileInput" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="d-none" onchange="document.getElementById('photoUploadForm').submit();">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===================== 7. SETTINGS TAB ===================== --}}
        <div class="tab-pane fade" id="panel-settings" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 mb-4 max-w-3xl">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-receipt text-muted me-2"></i> Tax & VAT Settings</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('buildings.settings.update', $building) }}">
                        @csrf @method('PUT')

                        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Charge VAT on this building</h6>
                                <p class="text-muted small mb-3" style="max-width: 500px;">When active, invoices for units in this building will default to the VAT rate defined below. Otherwise, invoices are VAT-exempt.</p>

                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" name="vat_rate" id="vatRateInput" step="0.01" min="0" max="100" class="form-control fw-bold" style="width: 120px;" value="{{ old('vat_rate', $building->vat_rate ?: 0) }}" {{ $building->vat_enabled ? '' : 'disabled' }}>
                                    <span class="fw-bold text-muted">%</span>
                                </div>
                                @error('vat_rate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-check form-switch fs-4">
                                <input type="hidden" name="vat_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="vatToggle" name="vat_enabled" value="1" {{ $building->vat_enabled ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm"><i class="fa-solid fa-floppy-disk me-2"></i> Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- MODALS & LIGHTBOX --}}
<!-- Add Floor Modal -->
<div class="modal fade" id="addFloorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-layer-group text-primary me-2"></i> Add Floor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('buildings.floors.store', $building) }}">
                @csrf
                <input type="hidden" name="_modal" value="add_floor">
                <div class="modal-body p-4">
                    @if($errors->any() && old('_modal') === 'add_floor')
                        <div class="alert alert-danger py-2 px-3 small rounded-3"><i class="fa-solid fa-circle-exclamation me-1"></i> Please fix errors below.</div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Floor Name <span class="text-danger">*</span></label>
                        <input type="text" name="floor_name" class="form-control form-control-lg fs-6 @error('floor_name') is-invalid @enderror" value="{{ old('floor_name') }}" placeholder="e.g. Ground Floor" required>
                        @error('floor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Floor Code</label>
                            <input type="text" name="floor_code" class="form-control @error('floor_code') is-invalid @enderror" value="{{ old('floor_code') }}" placeholder="e.g. GF">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Total Units</label>
                            <input type="number" name="total_no_of_units" class="form-control" value="{{ old('total_no_of_units') }}" min="1">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Block Name</label>
                            <input type="text" name="block_name" class="form-control" value="{{ old('block_name') }}" placeholder="Optional">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Block Code</label>
                            <input type="text" name="block_code" class="form-control" value="{{ old('block_code') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Floor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Photo Lightbox -->
<div id="photoLightbox" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75" style="z-index: 9999; backdrop-filter: blur(4px);" onclick="closeLightbox()">
    <div class="w-100 h-100 d-flex align-items-center justify-content-center p-4">
        <img id="lightboxImg" src="" class="rounded-3 shadow-lg" style="max-width: 100%; max-height: 90vh; object-fit: contain;">
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    // URL-based Tab Selection using Bootstrap Native JS
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        let tabParam = urlParams.get('tab');

        if (tabParam) {
            let triggerEl = document.querySelector(`#${tabParam}-tab`);
            if (triggerEl) {
                let tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }

        // Update URL on tab change without reloading
        const tabElements = document.querySelectorAll('button[data-bs-toggle="pill"]');
        tabElements.forEach(el => {
            el.addEventListener('shown.bs.tab', function (event) {
                let currentTabId = event.target.id.replace('-tab', '');
                let url = new URL(window.location);
                url.searchParams.set('tab', currentTabId);
                window.history.replaceState({}, '', url);
            });
        });

        // Open Modal if Validation Errors exist
        @if($errors->any() && old('_modal') === 'add_floor')
            let addFloorModal = new bootstrap.Modal(document.getElementById('addFloorModal'));
            addFloorModal.show();
        @endif

        // VAT Toggle Logic
        const vatToggle = document.getElementById('vatToggle');
        const vatRateInput = document.getElementById('vatRateInput');
        if(vatToggle && vatRateInput) {
            vatToggle.addEventListener('change', function() {
                vatRateInput.disabled = !this.checked;
                if(this.checked) vatRateInput.focus();
            });
        }
    });

    // Lightbox Logic
    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('photoLightbox').classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('photoLightbox').classList.add('d-none');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

    // Chart.js Configuration
    (function () {
        if (typeof Chart === 'undefined') return;
        Chart.defaults.font.family = "'Outfit', sans-serif";
        Chart.defaults.color = '#64748b';

        // Helper for Donut Charts
        const buildDonut = (elementId, labels, data, colors) => {
            const el = document.getElementById(elementId);
            if (!el) return;
            new Chart(el.getContext('2d'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 4 }] },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#0f172a', padding: 12, bodyFont: {size: 13, weight: 'bold'}, cornerRadius: 8 }
                    }
                }
            });
        };

        // Render Donuts
        buildDonut('expenseChart', ['Electricity', 'Water', 'Maintenance', 'Other'], [{{ $dashboard['expenses']['electricity'] ?? 0 }}, {{ $dashboard['expenses']['water'] ?? 0 }}, {{ $dashboard['expenses']['maintenance'] ?? 0 }}, {{ $dashboard['expenses']['other'] ?? 0 }}], ['#f59e0b', '#3b82f6', '#ef4444', '#64748b']);
        buildDonut('occupancyChart', ['Occupied', 'Vacant'], [{{ $dashboard['kpis']['occupied_units'] }}, {{ $dashboard['kpis']['vacant_units'] }}], ['#10b981', '#e2e8f0']);
        buildDonut('leaseChart', ['Active', 'Expiring', 'Upcoming', 'Expired'], [{{ $dashboard['lease_status_counts']['active'] }}, {{ $dashboard['lease_status_counts']['expiring'] }}, {{ $dashboard['lease_status_counts']['upcoming'] }}, {{ $dashboard['lease_status_counts']['expired'] }}], ['#10b981', '#f59e0b', '#3b82f6', '#94a3b8']);

        // Unit Condition Donut Data mapping
        const conditionLabels = {!! json_encode(array_keys($dashboard['unit_conditions']->toArray())) !!};
        const conditionData = {!! json_encode(array_values($dashboard['unit_conditions']->toArray())) !!};
        const conditionColors = ['#e8b86d', '#3b82f6', '#10b981', '#8b5cf6', '#f43f5e', '#f59e0b'];
        buildDonut('conditionChart', conditionLabels, conditionData, conditionColors);

        // MAIN FINANCIAL CHART (The Neat Version)
        const finCanvas = document.getElementById('financeChart');
        if (finCanvas) {
            const ctx = finCanvas.getContext('2d');

            // Create a gorgeous fade gradient for the profit line fill
            const profitGradient = ctx.createLinearGradient(0, 0, 0, 320);
            profitGradient.addColorStop(0, 'rgba(99, 102, 241, 0.25)'); // Violet fade
            profitGradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($dashboard['monthly']['labels']) !!},
                    datasets: [
                        {
                            label: 'Income',
                            data: {!! json_encode($dashboard['monthly']['income']) !!},
                            backgroundColor: '#10b981',
                            borderRadius: 4,
                            barPercentage: 0.5,
                            categoryPercentage: 0.7,
                            order: 2
                        },
                        {
                            label: 'Expenses',
                            data: {!! json_encode($dashboard['monthly']['expenses']) !!},
                            backgroundColor: '#ef4444',
                            borderRadius: 4,
                            barPercentage: 0.5,
                            categoryPercentage: 0.7,
                            order: 3
                        },
                        {
                            label: 'Net Profit',
                            data: {!! json_encode($dashboard['monthly']['profit']) !!},
                            type: 'line',
                            borderColor: '#6366f1',
                            borderWidth: 3,
                            backgroundColor: profitGradient,
                            fill: true,
                            tension: 0.4, // Smooth curved lines
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#6366f1',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            order: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'top', align: 'end',
                            labels: { usePointStyle: true, boxWidth: 8, font: { weight: '600' } }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a', titleFont: { size: 13 }, bodyFont: { size: 13, weight: 'bold' },
                            padding: 12, cornerRadius: 8,
                            callbacks: { label: (c) => ` ${c.dataset.label}: BHD ` + c.parsed.y.toLocaleString() }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { weight: '600' } },
                            border: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: '#f1f5f9' }, // Very faint horizontal lines
                            ticks: {
                                maxTicksLimit: 6,
                                callback: (v) => Math.abs(v) >= 1000 ? (v/1000)+'k' : v
                            }
                        }
                    }
                }
            });
        }
    })();
</script>
@endpush
