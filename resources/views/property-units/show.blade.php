@extends('layouts.admin')

@section('title', $unit->unit_name . ' — Unit Detail')
@section('topbar-title', 'Unit Detail')

@section('page-breadcrumb')
    <a href="{{ url('/dashboard') }}">Home</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="{{ route('property-units.index') }}">Property Units</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>{{ $unit->unit_name }}</span>
@endsection
@section('page-title')
    {{ $unit->unit_name }}
@endsection
@section('page-subtitle')
    @if($unit->property_code)
        <span class="badge badge-gold" style="font-size:12px;">{{ $unit->property_code }}</span>
    @endif
    @if($unit->unit_type)
        <span class="badge badge-blue" style="font-size:12px;">{{ $unit->unit_type }}</span>
    @endif
    @if($unit->unit_condition)
        <span class="badge badge-gray" style="font-size:12px;">{{ $unit->unit_condition }}</span>
    @endif
@endsection
@section('page-actions')
    <a href="{{ route('property-units.index') }}" class="btn btn-outline">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
    <a href="{{ route('property-units.edit', $unit) }}" class="btn btn-primary">
        <i class="fa-regular fa-pen-to-square"></i> Edit Unit
    </a>
@endsection

@push('styles')
<style>
    /* ── HERO STRIP ─────────────────────────────────────── */
    .unit-hero {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1px;
        background: var(--card-border);
        border: 1.5px solid var(--card-border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: var(--sp-6);
        box-shadow: var(--shadow-sm);
    }
    .hero-cell {
        background: var(--card-bg);
        padding: 22px 24px;
        display: flex; align-items: center; gap: 16px;
        position: relative; overflow: hidden;
    }
    .hero-cell::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: var(--accent);
        opacity: 0.6;
    }
    .hero-cell:first-child::before { opacity: 1; }
    .hero-cell-icon {
        width: 48px; height: 48px; border-radius: var(--radius);
        background: var(--accent-dim); color: var(--accent);
        display: flex; align-items: center; justify-content: center;
        font-size: var(--fs-lg); flex-shrink: 0;
    }
    .hero-cell:nth-child(2) .hero-cell-icon { background: var(--tone-info-bg); color: var(--info); }
    .hero-cell:nth-child(3) .hero-cell-icon { background: var(--tone-success-bg); color: var(--success); }
    .hero-val {
        font-family: 'Outfit', sans-serif;
        font-size: 26px; font-weight: 800;
        color: var(--text-primary); line-height: 1;
    }
    .hero-lbl {
        font-size: var(--fs-xs); font-weight: 600;
        color: var(--text-muted); text-transform: uppercase;
        letter-spacing: 0.06em; margin-top: var(--sp-1);
    }
    .hero-sub { font-size: var(--fs-xs); color: var(--text-muted); margin-top: 2px; }

    /* ── TABS ────────────────────────────────────────────── */

    /* ── SECTION HEADINGS ───────────────────────────────── */
    .section-head {
        display: flex; align-items: center; gap: 10px;
        padding-bottom: var(--sp-3);
        border-bottom: 1px solid var(--card-border);
        margin-bottom: 18px;
    }
    .section-head-icon {
        width: 32px; height: 32px; border-radius: var(--radius-sm);
        background: var(--accent-dim); color: var(--accent);
        display: flex; align-items: center; justify-content: center;
        font-size: var(--fs-base); flex-shrink: 0;
    }
    .section-head-title {
        font-family: 'Outfit', sans-serif;
        font-size: var(--fs-base); font-weight: 800;
        color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.07em;
    }

    /* ── FIELD GRID ─────────────────────────────────────── */
    .field-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 18px 24px;
        margin-bottom: var(--sp-7);
    }
    .field-grid.col-2 { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
    .field-item {}
    .field-label {
        font-size: var(--fs-2xs); font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: var(--text-muted); margin-bottom: 5px;
    }
    .field-value {
        font-size: var(--fs-base); font-weight: 600; color: var(--text-primary);
        line-height: 1.4;
    }
    .field-value.empty { color: var(--text-muted); font-weight: 400; font-style: italic; }
    .field-value a { color: var(--info); text-decoration: none; }
    .field-value a:hover { text-decoration: underline; }
    .field-value.span-full { grid-column: 1 / -1; }

    /* ── CONTRACT TABLE ─────────────────────────────────── */


    @media (max-width: 600px) {
        .unit-hero { grid-template-columns: 1fr; }
        .unit-hero .hero-cell::before { display: none; }
        .unit-hero .hero-cell:first-child::before { display: block; }
    }
</style>
@endpush

@section('content')

{{-- PAGE HEADER --}}

{{-- HERO STRIP --}}
<div class="unit-hero">
    <div class="hero-cell">
        <div class="hero-cell-icon"><i class="fa-solid fa-coins"></i></div>
        <div>
            <div class="hero-val">
                @if($unit->rent_per_month)
                    {{ number_format($unit->rent_per_month, 0) }}
                    <span style="font-size:14px;font-weight:500;color:var(--text-muted);">BHD</span>
                @else
                    <span style="font-size:20px;font-weight:400;color:var(--text-muted);">—</span>
                @endif
            </div>
            <div class="hero-lbl">Monthly Rent</div>
        </div>
    </div>
    <div class="hero-cell">
        <div class="hero-cell-icon"><i class="fa-solid fa-vector-square"></i></div>
        <div>
            <div class="hero-val">
                @if($unit->area_inside)
                    {{ number_format($unit->area_inside, 0) }}
                    <span style="font-size:14px;font-weight:500;color:var(--text-muted);">{{ $unit->area_unit ?? 'sqm' }}</span>
                @else
                    <span style="font-size:20px;font-weight:400;color:var(--text-muted);">—</span>
                @endif
            </div>
            <div class="hero-lbl">Inside Area</div>
            @if($unit->area_terrace)
                <div class="hero-sub">+ {{ number_format($unit->area_terrace, 0) }} {{ $unit->area_unit ?? 'sqm' }} terrace</div>
            @endif
        </div>
    </div>
    <div class="hero-cell">
        <div class="hero-cell-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
            <div class="hero-val">
                @if($unit->security_deposit_amount)
                    {{ number_format($unit->security_deposit_amount, 0) }}
                    <span style="font-size:14px;font-weight:500;color:var(--text-muted);">BHD</span>
                @else
                    <span style="font-size:20px;font-weight:400;color:var(--text-muted);">—</span>
                @endif
            </div>
            <div class="hero-lbl">Security Deposit</div>
        </div>
    </div>
</div>

{{-- TABS --}}
<div class="tab-bar">
    <button class="tab-btn" id="tab-details" onclick="switchTab('details')">
        <i class="fa-solid fa-door-open"></i> Details
    </button>
    <button class="tab-btn" id="tab-contracts" onclick="switchTab('contracts')">
        <i class="fa-solid fa-file-contract"></i> Contracts
        <span class="tab-count">{{ $contracts->count() }}</span>
    </button>
</div>

{{-- ═══ DETAILS TAB ═══ --}}
<div class="tab-panel" id="panel-details">
<div class="card">
    <div class="card-body" style="padding:24px;">

        {{-- 1. Unit Information --}}
        <div class="section-head">
            <div class="section-head-icon"><i class="fa-solid fa-door-open"></i></div>
            <div class="section-head-title">Unit Information</div>
        </div>
        <div class="field-grid">
            <div class="field-item">
                <div class="field-label">Unit Name</div>
                <div class="field-value">{{ $unit->unit_name ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Unit Type</div>
                <div class="field-value">
                    @if($unit->unit_type)
                        <span class="badge badge-blue">{{ $unit->unit_type }}</span>
                    @else <span class="empty">—</span> @endif
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Unit Condition</div>
                <div class="field-value">
                    @if($unit->unit_condition)
                        <span class="badge badge-gray">{{ $unit->unit_condition }}</span>
                    @else <span class="empty">—</span> @endif
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">View</div>
                <div class="field-value {{ $unit->view ? '' : 'empty' }}">{{ $unit->view ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Parking (FOC)</div>
                <div class="field-value {{ is_null($unit->no_of_parkings_foc) ? 'empty' : '' }}">
                    {{ is_null($unit->no_of_parkings_foc) ? '—' : $unit->no_of_parkings_foc }}
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Creation Date</div>
                <div class="field-value {{ $unit->creation_date ? '' : 'empty' }}">
                    {{ $unit->creation_date ? $unit->creation_date->format('d M Y') : '—' }}
                </div>
            </div>
            @if($unit->description)
            <div class="field-item" style="grid-column:1/-1;">
                <div class="field-label">Description</div>
                <div class="field-value">{{ $unit->description }}</div>
            </div>
            @endif
        </div>

        {{-- 2. Property & Address --}}
        <div class="section-head">
            <div class="section-head-icon"><i class="fa-solid fa-building"></i></div>
            <div class="section-head-title">Property &amp; Address</div>
        </div>
        <div class="field-grid col-2">
            <div class="field-item">
                <div class="field-label">Property Name</div>
                <div class="field-value">
                    @if($unit->building)
                        <a href="{{ route('buildings.show', $unit->building) }}">{{ $unit->property_name ?: $unit->building->property_name }}</a>
                    @else
                        {{ $unit->property_name ?: '—' }}
                    @endif
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Property Code</div>
                <div class="field-value">
                    @if($unit->property_code)
                        <span class="badge badge-gold">{{ $unit->property_code }}</span>
                    @else <span class="empty">—</span> @endif
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Property Type</div>
                <div class="field-value {{ $unit->property_type ? '' : 'empty' }}">{{ $unit->property_type ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Type of Ownership</div>
                <div class="field-value {{ $unit->type_of_ownership ? '' : 'empty' }}">{{ $unit->type_of_ownership ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Landlord Name</div>
                <div class="field-value {{ $unit->land_lord_name ? '' : 'empty' }}">{{ $unit->land_lord_name ?: '—' }}</div>
            </div>
            @if($unit->floor)
            <div class="field-item">
                <div class="field-label">Floor</div>
                <div class="field-value">{{ $unit->floor->floor_name }}</div>
            </div>
            @endif
            <div class="field-item">
                <div class="field-label">Building No.</div>
                <div class="field-value {{ $unit->building_no ? '' : 'empty' }}">{{ $unit->building_no ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Road</div>
                <div class="field-value {{ $unit->road ? '' : 'empty' }}">{{ $unit->road ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Block</div>
                <div class="field-value {{ $unit->block ? '' : 'empty' }}">{{ $unit->block ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Area / District</div>
                <div class="field-value {{ $unit->area ? '' : 'empty' }}">{{ $unit->area ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">City</div>
                <div class="field-value {{ $unit->city ? '' : 'empty' }}">{{ $unit->city ?: '—' }}</div>
            </div>
        </div>

        {{-- 3. Area & Pricing --}}
        <div class="section-head">
            <div class="section-head-icon"><i class="fa-solid fa-coins"></i></div>
            <div class="section-head-title">Area &amp; Pricing</div>
        </div>
        <div class="field-grid">
            <div class="field-item">
                <div class="field-label">Inside Area</div>
                <div class="field-value {{ $unit->area_inside ? '' : 'empty' }}">
                    @if($unit->area_inside)
                        {{ number_format($unit->area_inside, 2) }} <span style="font-size:12px;color:var(--text-muted);">{{ $unit->area_unit ?? 'sqm' }}</span>
                    @else —  @endif
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Terrace Area</div>
                <div class="field-value {{ $unit->area_terrace ? '' : 'empty' }}">
                    @if($unit->area_terrace)
                        {{ number_format($unit->area_terrace, 2) }} <span style="font-size:12px;color:var(--text-muted);">{{ $unit->area_unit ?? 'sqm' }}</span>
                    @else —  @endif
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Rate / Area Unit</div>
                <div class="field-value {{ $unit->rate_per_area_unit ? '' : 'empty' }}">
                    @if($unit->rate_per_area_unit)
                        {{ number_format($unit->rate_per_area_unit, 2) }} <span style="font-size:12px;color:var(--text-muted);">BHD</span>
                    @else — @endif
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Monthly Rent</div>
                <div class="field-value {{ $unit->rent_per_month ? '' : 'empty' }}" style="{{ $unit->rent_per_month ? 'color:var(--accent);' : '' }}">
                    @if($unit->rent_per_month)
                        {{ number_format($unit->rent_per_month, 2) }} <span style="font-size:12px;color:var(--text-muted);">BHD</span>
                    @else — @endif
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Security Deposit</div>
                <div class="field-value {{ $unit->security_deposit_amount ? '' : 'empty' }}">
                    @if($unit->security_deposit_amount)
                        {{ number_format($unit->security_deposit_amount, 2) }} <span style="font-size:12px;color:var(--text-muted);">BHD</span>
                    @else — @endif
                </div>
            </div>
            @if($unit->municipality_nos)
            <div class="field-item">
                <div class="field-label">Municipality No.</div>
                <div class="field-value">{{ $unit->municipality_nos }}</div>
            </div>
            @endif
        </div>

        {{-- 4. Utilities --}}
        <div class="section-head">
            <div class="section-head-icon"><i class="fa-solid fa-bolt"></i></div>
            <div class="section-head-title">Utilities</div>
        </div>
        <div class="field-grid">
            <div class="field-item">
                <div class="field-label">Electricity Meter No.</div>
                <div class="field-value {{ $unit->electricity_meter_no ? '' : 'empty' }}">{{ $unit->electricity_meter_no ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Electricity Install Date</div>
                <div class="field-value {{ $unit->electricity_installation_date ? '' : 'empty' }}">
                    {{ $unit->electricity_installation_date ? $unit->electricity_installation_date->format('d M Y') : '—' }}
                </div>
            </div>
            <div class="field-item">
                <div class="field-label">Electricity A/C No.</div>
                <div class="field-value {{ $unit->electricity_ac_no ? '' : 'empty' }}">{{ $unit->electricity_ac_no ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Water Meter No.</div>
                <div class="field-value {{ $unit->water_meter_no ? '' : 'empty' }}">{{ $unit->water_meter_no ?: '—' }}</div>
            </div>
            <div class="field-item">
                <div class="field-label">Water Install Date</div>
                <div class="field-value {{ $unit->water_installation_date ? '' : 'empty' }}">
                    {{ $unit->water_installation_date ? $unit->water_installation_date->format('d M Y') : '—' }}
                </div>
            </div>
        </div>

        {{-- Custom Fields --}}
        @if(!empty($unit->custom_fields) && count(array_filter((array)$unit->custom_fields)))
        <div class="section-head">
            <div class="section-head-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
            <div class="section-head-title">Custom Fields</div>
        </div>
        <div class="field-grid">
            @foreach((array)$unit->custom_fields as $key => $val)
                @if($val)
                <div class="field-item">
                    <div class="field-label">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                    <div class="field-value">{{ $val }}</div>
                </div>
                @endif
            @endforeach
        </div>
        @endif

    </div>
</div>
</div>

{{-- ═══ CONTRACTS TAB ═══ --}}
<div class="tab-panel" id="panel-contracts">
<div class="table-card">
    @if($contracts->isEmpty())
        <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-file-contract"></i></div>
            <h4>No contracts found</h4>
            <p>No lease contracts are linked to this unit's property code.</p>
        </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Agreement No.</th>
                    <th>Tenant</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Monthly Rent</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                @php
                    $now = now();
                    $isActive = $contract->lease_start_date <= $now && $contract->lease_end_date >= $now;
                    $isExpired = $contract->lease_end_date < $now;
                @endphp
                <tr data-href="{{ route('lease-contracts.show', $contract) }}" onclick="window.location=this.dataset.href">
                    <td data-label="Agreement No."><span class="badge badge-gold">{{ $contract->agreement_no }}</span></td>
                    <td data-label="Tenant">
                        @if($contract->tenant)
                            <div style="font-weight:600;">{{ $contract->tenant->name }}</div>
                        @elseif($contract->tenant_name)
                            <div style="font-weight:600;">{{ $contract->tenant_name }}</div>
                        @else
                            <span style="color:var(--text-muted);">—</span>
                        @endif
                    </td>
                    <td data-label="Start Date">{{ $contract->lease_start_date ? \Carbon\Carbon::parse($contract->lease_start_date)->format('d M Y') : '—' }}</td>
                    <td data-label="End Date">{{ $contract->lease_end_date ? \Carbon\Carbon::parse($contract->lease_end_date)->format('d M Y') : '—' }}</td>
                    <td data-label="Monthly Rent">
                        @if($contract->rent_per_month)
                            <span style="font-family:'Outfit',sans-serif;font-weight:700;">{{ number_format($contract->rent_per_month, 0) }}</span>
                            <span style="font-size:11px;color:var(--text-muted);"> BHD</span>
                        @else —  @endif
                    </td>
                    <td data-label="Status" onclick="event.stopPropagation()">
                        @if($isActive)
                            <span class="badge badge-green">Active</span>
                        @elseif($isExpired)
                            <span class="badge badge-gray">Expired</span>
                        @else
                            <span class="badge badge-blue">Upcoming</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
</div>

@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
        history.replaceState(null, '', '?tab=' + tab);
    }

    const urlTab = new URLSearchParams(window.location.search).get('tab');
    switchTab(['details','contracts'].includes(urlTab) ? urlTab : 'details');
</script>
@endpush
