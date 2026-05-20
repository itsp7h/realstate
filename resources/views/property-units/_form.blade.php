{{--
    Shared form partial for create & edit.
    $unit, $action, $method, $formFields, $customFieldDefs, $buildings injected by parent.
--}}

@push('styles')
<style>
/* ── SECTION CARDS ─────────────────────────────────────── */
.u-section-stack { display: flex; flex-direction: column; gap: 20px; }

.u-section-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    opacity: 0;
    transform: translateY(16px);
    animation: uCardReveal 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
.u-section-card:nth-child(1) { animation-delay: 0.04s; }
.u-section-card:nth-child(2) { animation-delay: 0.10s; }
.u-section-card:nth-child(3) { animation-delay: 0.16s; }
.u-section-card:nth-child(4) { animation-delay: 0.22s; }
.u-section-card:nth-child(5) { animation-delay: 0.28s; }
.u-section-card:nth-child(6) { animation-delay: 0.34s; }
.u-section-card:nth-child(7) { animation-delay: 0.40s; }
.u-section-card:nth-child(8) { animation-delay: 0.46s; }

@keyframes uCardReveal {
    to { opacity: 1; transform: translateY(0); }
}

.u-section-header {
    padding: 16px 22px;
    border-bottom: 1px solid var(--card-border);
    display: flex; align-items: center; gap: 12px;
    background: linear-gradient(to right, rgba(232,184,109,0.04), transparent);
}
.u-section-icon {
    width: 38px; height: 38px; border-radius: 9px;
    background: var(--accent-dim); border: 1px solid rgba(232,184,109,0.2);
    display: flex; align-items: center; justify-content: center;
    color: var(--accent); font-size: 15px; flex-shrink: 0;
}
.u-section-meta { flex: 1; }
.u-section-title { font-family: 'Outfit', sans-serif; font-size: 14.5px; font-weight: 700; color: var(--text-primary); line-height: 1; }
.u-section-sub   { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
.u-step-badge {
    width: 24px; height: 24px; border-radius: 50%;
    background: var(--accent); color: #0B1120;
    font-family: 'Outfit', sans-serif; font-size: 10px; font-weight: 800;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.u-section-body { padding: 22px; }

/* ── FIELD GRID ────────────────────────────────────────── */
.u-field-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px 22px;
}
.u-field-grid .u-span-2    { grid-column: span 2; }
.u-field-grid .u-span-full { grid-column: 1 / -1; }

.u-field-group { display: flex; flex-direction: column; }
.u-field-label {
    font-size: 11px; font-weight: 700; color: var(--text-secondary);
    letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 7px;
    display: flex; align-items: center; gap: 3px;
}
.u-field-label .req { color: var(--danger); font-size: 13px; line-height: 1; }

.u-field-wrap { position: relative; }
.u-field-icon {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: 12px; pointer-events: none; transition: color 0.2s;
}
.u-has-icon input, .u-has-icon select { padding-left: 36px !important; }
.u-field-wrap:focus-within .u-field-icon { color: var(--accent); }

.u-input, .u-select, .u-textarea {
    width: 100%; padding: 10px 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: #fff; color: var(--text-primary);
    font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px;
    outline: none; appearance: none; -webkit-appearance: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    line-height: 1.5;
}
.u-input::placeholder { color: var(--text-muted); opacity: 0.65; }
.u-input:hover, .u-select:hover  { border-color: #B0BCCF; }
.u-input:focus, .u-select:focus, .u-textarea:focus {
    border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: #FFFDF8;
}
.u-input.is-invalid, .u-select.is-invalid { border-color: var(--danger); background: #FFF8F8; }
.u-input.is-invalid:focus, .u-select.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.12); }

.u-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%2394A3B8' d='M5 7L0.669873 2.5L9.33013 2.5L5 7Z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; cursor: pointer;
}
.u-textarea { resize: vertical; min-height: 80px; }

.u-field-error {
    display: flex; align-items: center; gap: 4px; margin-top: 5px;
    font-size: 11px; color: var(--danger); font-weight: 500;
}
.u-field-error i { font-size: 10px; }

/* ── BUILDING-LOCKED STATE ─────────────────────────────── */
.u-locked-section { position: relative; }
.u-locked-section .u-input[readonly],
.u-locked-section .u-select[readonly] {
    background: var(--page-bg) !important;
    color: var(--text-muted) !important;
    cursor: not-allowed;
    border-color: var(--card-border) !important;
    box-shadow: none !important;
}
.u-lock-hint {
    display: none; align-items: center; gap: 4px;
    font-size: 10.5px; color: var(--text-muted); margin-top: 5px;
}
.u-lock-hint i { font-size: 9px; }
.u-locked-section .u-lock-hint { display: flex; }

/* ── BUILDING PREVIEW CARD ─────────────────────────────── */
.building-preview {
    display: none;
    background: linear-gradient(135deg, var(--accent-dim), rgba(232,184,109,0.04));
    border: 1px solid rgba(232,184,109,0.25);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    margin-top: 14px;
    gap: 12px;
    align-items: center;
}
.building-preview.visible { display: flex; }
.building-preview-icon {
    width: 36px; height: 36px; border-radius: 8px;
    background: var(--accent); color: #0B1120;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.building-preview-name { font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: var(--text-primary); }
.building-preview-sub  { font-size: 11.5px; color: var(--text-secondary); margin-top: 1px; }
.building-preview-badge {
    margin-left: auto;
    display: flex; align-items: center; gap: 4px;
    font-size: 10.5px; color: var(--accent); font-weight: 600;
    padding: 3px 9px; background: var(--accent-dim); border-radius: 20px;
}

/* ── SUB-SECTION DIVIDER ───────────────────────────────── */
.u-sub-divider {
    display: flex; align-items: center; gap: 8px;
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--text-muted);
    margin: 18px 0 14px;
}
.u-sub-divider::after { content: ''; flex: 1; height: 1px; background: var(--card-border); }

/* ── STICKY ACTIONS ────────────────────────────────────── */
.u-actions-bar {
    position: sticky; bottom: 0;
    background: var(--card-bg); border-top: 1px solid var(--card-border);
    padding: 14px 22px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    z-index: 50; margin-top: 20px;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.06);
}
.u-actions-hint { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }
.u-actions-hint i { color: var(--accent); }
.u-actions-right { display: flex; gap: 10px; }

@media (max-width: 768px) {
    .u-field-grid { grid-template-columns: 1fr; }
    .u-field-grid .u-span-2 { grid-column: span 1; }
    .u-actions-hint { display: none; }
}
</style>
@endpush

@php
    $visibleFields = collect($formFields ?? [])
        ->filter(fn($f) => !empty($f['visible']))
        ->pluck('name')->all();
    $showAll = empty($visibleFields);
    $ushow = fn(string $f) => $showAll || in_array($f, $visibleFields);
    $uval  = fn(string $f, $d = '') => old($f, $unit->{$f} ?? $d);
    $selectedBuildingId = old('building_id', $unit->building_id ?? null);
    $selectedFloorId    = old('floor_id',    $unit->floor_id    ?? null);
@endphp

<form method="POST" action="{{ $action }}" id="unitForm" novalidate>
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div class="u-section-stack">

        {{-- ── BUILDING & FLOOR ───────────────────────────────── --}}
        <div class="u-section-card">
            <div class="u-section-header">
                <div class="u-section-icon"><i class="fa-solid fa-link"></i></div>
                <div class="u-section-meta">
                    <div class="u-section-title">Building & Floor</div>
                    <div class="u-section-sub">Property-level fields auto-fill from the selected building</div>
                </div>
            </div>
            <div class="u-section-body">
                <div class="u-field-grid">
                    <div class="u-field-group u-span-full">
                        <label class="u-field-label">Building</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-building u-field-icon"></i>
                            <select name="building_id" id="buildingSelect"
                                class="u-select {{ $errors->has('building_id') ? 'is-invalid' : '' }}"
                                onchange="uLoadBuildingData(this.value)">
                                <option value="">— Select a building to auto-fill —</option>
                                @foreach($buildings ?? [] as $b)
                                    <option value="{{ $b->id }}" {{ $selectedBuildingId == $b->id ? 'selected' : '' }}>
                                        {{ $b->property_code }} — {{ $b->property_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('building_id') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror

                        <div class="building-preview" id="buildingPreview">
                            <div class="building-preview-icon"><i class="fa-solid fa-building"></i></div>
                            <div>
                                <div class="building-preview-name" id="previewName">—</div>
                                <div class="building-preview-sub" id="previewSub">—</div>
                            </div>
                            <div class="building-preview-badge"><i class="fa-solid fa-lock"></i> Auto-filled</div>
                        </div>
                    </div>

                    <div class="u-field-group u-span-full" id="floorFieldWrap">
                        <label class="u-field-label">Floor <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;color:var(--text-muted);">(optional)</span></label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-layer-group u-field-icon"></i>
                            <select name="floor_id" id="floorSelect"
                                class="u-select {{ $errors->has('floor_id') ? 'is-invalid' : '' }}"
                                disabled>
                                <option value="">— Select a building first —</option>
                            </select>
                        </div>
                        @error('floor_id') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── PROPERTY INFORMATION ────────────────────────────── --}}
        @php $showPropSection = $showAll || count(array_intersect(['property_name','property_code','type_of_ownership','property_type','land_lord_name'], $visibleFields)) > 0; @endphp
        @if($showPropSection)
        <div class="u-section-card" id="property-section">
            <div class="u-section-header">
                <div class="u-section-icon"><i class="fa-solid fa-building"></i></div>
                <div class="u-section-meta">
                    <div class="u-section-title">Property Information</div>
                    <div class="u-section-sub">Auto-filled when a building is selected above</div>
                </div>
                <div class="u-step-badge">1</div>
            </div>
            <div class="u-section-body u-locked-section" id="propSectionInner">
                <div class="u-field-grid">
                    @if($ushow('property_name'))
                    <div class="u-field-group">
                        <label class="u-field-label">Property Name <span class="req">*</span></label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-tag u-field-icon"></i>
                            <input type="text" name="property_name" id="f_property_name"
                                class="u-input {{ $errors->has('property_name') ? 'is-invalid' : '' }}"
                                value="{{ $uval('property_name') }}" placeholder="e.g. Miknas Plaza 2" required maxlength="255">
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('property_name') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('property_code'))
                    <div class="u-field-group">
                        <label class="u-field-label">Property Code <span class="req">*</span></label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-barcode u-field-icon"></i>
                            <select name="property_code" id="f_property_code"
                                class="u-select {{ $errors->has('property_code') ? 'is-invalid' : '' }}" required>
                                <option value="">Select code…</option>
                                @foreach(['AAL','MP1','MP2','MP3','MP4','MP5'] as $code)
                                    <option value="{{ $code }}" {{ $uval('property_code') == $code ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('property_code') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('type_of_ownership'))
                    <div class="u-field-group">
                        <label class="u-field-label">Type of Ownership</label>
                        <div class="u-field-wrap">
                            <select name="type_of_ownership" id="f_type_of_ownership" class="u-select">
                                <option value="">Select…</option>
                                @foreach(['Owned','Leased','Joint Venture','Managed'] as $opt)
                                    <option value="{{ $opt }}" {{ $uval('type_of_ownership') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('type_of_ownership') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('property_type'))
                    <div class="u-field-group">
                        <label class="u-field-label">Property Type</label>
                        <div class="u-field-wrap">
                            <select name="property_type" id="f_property_type" class="u-select">
                                <option value="">Select…</option>
                                @foreach(['Residential','Commercial','Mixed Use','Industrial','Retail'] as $opt)
                                    <option value="{{ $opt }}" {{ $uval('property_type') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('property_type') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('land_lord_name'))
                    <div class="u-field-group u-span-2">
                        <label class="u-field-label">Landlord Name</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-user-tie u-field-icon"></i>
                            <input type="text" name="land_lord_name" id="f_land_lord_name"
                                class="u-input" value="{{ $uval('land_lord_name') }}"
                                placeholder="e.g. Akram Miknas" maxlength="255">
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('land_lord_name') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ── ADDRESS ─────────────────────────────────────────── --}}
        @php $showAddrSection = $showAll || count(array_intersect(['building_no','road','block','area','city'], $visibleFields)) > 0; @endphp
        @if($showAddrSection)
        <div class="u-section-card">
            <div class="u-section-header">
                <div class="u-section-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div class="u-section-meta">
                    <div class="u-section-title">Address</div>
                    <div class="u-section-sub">Physical location — auto-filled from building</div>
                </div>
                <div class="u-step-badge">2</div>
            </div>
            <div class="u-section-body u-locked-section" id="addrSectionInner">
                <div class="u-field-grid">
                    @if($ushow('building_no'))
                    <div class="u-field-group">
                        <label class="u-field-label">Building No.</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-hashtag u-field-icon"></i>
                            <input type="number" name="building_no" id="f_building_no"
                                class="u-input {{ $errors->has('building_no') ? 'is-invalid' : '' }}"
                                value="{{ $uval('building_no') }}" placeholder="202" min="0">
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('building_no') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('block'))
                    <div class="u-field-group">
                        <label class="u-field-label">Block</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-table-cells u-field-icon"></i>
                            <input type="number" name="block" id="f_block"
                                class="u-input {{ $errors->has('block') ? 'is-invalid' : '' }}"
                                value="{{ $uval('block') }}" placeholder="324" min="0">
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('block') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('road'))
                    <div class="u-field-group u-span-2">
                        <label class="u-field-label">Road / Street</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-road u-field-icon"></i>
                            <input type="text" name="road" id="f_road"
                                class="u-input {{ $errors->has('road') ? 'is-invalid' : '' }}"
                                value="{{ $uval('road') }}" placeholder="e.g. Avenue 0022" maxlength="255">
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('road') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('area'))
                    <div class="u-field-group">
                        <label class="u-field-label">Area / District</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-map u-field-icon"></i>
                            <input type="text" name="area" id="f_area"
                                class="u-input {{ $errors->has('area') ? 'is-invalid' : '' }}"
                                value="{{ $uval('area') }}" placeholder="e.g. Capital Governorate" maxlength="255">
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('area') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('city'))
                    <div class="u-field-group">
                        <label class="u-field-label">City</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-city u-field-icon"></i>
                            <input type="text" name="city" id="f_city"
                                class="u-input {{ $errors->has('city') ? 'is-invalid' : '' }}"
                                value="{{ $uval('city') }}" placeholder="e.g. Manama" maxlength="255">
                        </div>
                        <div class="u-lock-hint"><i class="fa-solid fa-lock"></i> Auto-filled from building</div>
                        @error('city') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ── UNIT DETAILS ────────────────────────────────────── --}}
        @php $showUnitSection = $showAll || count(array_intersect(['unit_name','description','unit_type','creation_date','unit_condition','view','no_of_parkings_foc'], $visibleFields)) > 0; @endphp
        @if($showUnitSection)
        <div class="u-section-card">
            <div class="u-section-header">
                <div class="u-section-icon"><i class="fa-solid fa-door-open"></i></div>
                <div class="u-section-meta">
                    <div class="u-section-title">Unit Details</div>
                    <div class="u-section-sub">Individual unit configuration and attributes</div>
                </div>
                <div class="u-step-badge">3</div>
            </div>
            <div class="u-section-body">
                <div class="u-field-grid">
                    @if($ushow('unit_name'))
                    <div class="u-field-group">
                        <label class="u-field-label">Unit Name <span class="req">*</span></label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-key u-field-icon"></i>
                            <input type="text" name="unit_name"
                                class="u-input {{ $errors->has('unit_name') ? 'is-invalid' : '' }}"
                                value="{{ $uval('unit_name') }}" placeholder="e.g. MP2 - 11" required maxlength="255">
                        </div>
                        @error('unit_name') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('description'))
                    <div class="u-field-group u-span-2">
                        <label class="u-field-label">Description</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-align-left u-field-icon"></i>
                            <input type="text" name="description"
                                class="u-input {{ $errors->has('description') ? 'is-invalid' : '' }}"
                                value="{{ $uval('description') }}" placeholder="e.g. Miknas Plaza 2 - Flat 11" maxlength="500">
                        </div>
                        @error('description') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('unit_type'))
                    <div class="u-field-group">
                        <label class="u-field-label">Unit Type</label>
                        <div class="u-field-wrap">
                            <select name="unit_type" class="u-select {{ $errors->has('unit_type') ? 'is-invalid' : '' }}">
                                <option value="">Select type…</option>
                                @foreach(['Studio','1BHK','2BHK','3BHK','4BHK','Penthouse','Commercial','Office'] as $opt)
                                    <option value="{{ $opt }}" {{ $uval('unit_type') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('unit_type') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('unit_condition'))
                    <div class="u-field-group">
                        <label class="u-field-label">Unit Condition</label>
                        <div class="u-field-wrap">
                            <select name="unit_condition" class="u-select {{ $errors->has('unit_condition') ? 'is-invalid' : '' }}">
                                <option value="">Select condition…</option>
                                @foreach(['Furnished','Fitted','Semi-Furnished','Unfurnished','Shell & Core'] as $opt)
                                    <option value="{{ $opt }}" {{ $uval('unit_condition') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('unit_condition') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('view'))
                    <div class="u-field-group">
                        <label class="u-field-label">View</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-regular fa-eye u-field-icon"></i>
                            <input type="text" name="view"
                                class="u-input {{ $errors->has('view') ? 'is-invalid' : '' }}"
                                value="{{ $uval('view') }}" placeholder="e.g. Sea View" maxlength="100">
                        </div>
                        @error('view') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('creation_date'))
                    <div class="u-field-group">
                        <label class="u-field-label">Creation Date</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-regular fa-calendar u-field-icon"></i>
                            <input type="date" name="creation_date"
                                class="u-input {{ $errors->has('creation_date') ? 'is-invalid' : '' }}"
                                value="{{ old('creation_date', isset($unit->creation_date) ? \Carbon\Carbon::parse($unit->creation_date)->format('Y-m-d') : '') }}">
                        </div>
                        @error('creation_date') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('no_of_parkings_foc'))
                    <div class="u-field-group">
                        <label class="u-field-label">Parkings (FOC)</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-square-parking u-field-icon"></i>
                            <input type="number" name="no_of_parkings_foc"
                                class="u-input {{ $errors->has('no_of_parkings_foc') ? 'is-invalid' : '' }}"
                                value="{{ $uval('no_of_parkings_foc') }}" placeholder="0" min="0">
                        </div>
                        @error('no_of_parkings_foc') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ── AREA & PRICING ──────────────────────────────────── --}}
        @php $showPriceSection = $showAll || count(array_intersect(['area_unit','area_inside','area_terrace','rate_per_area_unit','rent_per_month','security_deposit_amount'], $visibleFields)) > 0; @endphp
        @if($showPriceSection)
        <div class="u-section-card">
            <div class="u-section-header">
                <div class="u-section-icon"><i class="fa-solid fa-coins"></i></div>
                <div class="u-section-meta">
                    <div class="u-section-title">Area &amp; Pricing</div>
                    <div class="u-section-sub">Size measurements and financial details</div>
                </div>
                <div class="u-step-badge">4</div>
            </div>
            <div class="u-section-body">
                <div class="u-field-grid">
                    @if($ushow('area_unit'))
                    <div class="u-field-group">
                        <label class="u-field-label">Area Unit</label>
                        <div class="u-field-wrap">
                            <select name="area_unit" class="u-select {{ $errors->has('area_unit') ? 'is-invalid' : '' }}">
                                <option value="">Sq. Mt. / Sq. Ft.</option>
                                @foreach(['Sq. Mt.','Sq. Ft.'] as $opt)
                                    <option value="{{ $opt }}" {{ $uval('area_unit') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('area_unit') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('area_inside'))
                    <div class="u-field-group">
                        <label class="u-field-label">Area Inside</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-ruler-combined u-field-icon"></i>
                            <input type="number" name="area_inside" step="0.01" min="0"
                                class="u-input {{ $errors->has('area_inside') ? 'is-invalid' : '' }}"
                                value="{{ $uval('area_inside') }}" placeholder="0.00">
                        </div>
                        @error('area_inside') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('area_terrace'))
                    <div class="u-field-group">
                        <label class="u-field-label">Area Terrace</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-ruler u-field-icon"></i>
                            <input type="number" name="area_terrace" step="0.01" min="0"
                                class="u-input {{ $errors->has('area_terrace') ? 'is-invalid' : '' }}"
                                value="{{ $uval('area_terrace') }}" placeholder="0.00">
                        </div>
                        @error('area_terrace') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('rate_per_area_unit'))
                    <div class="u-field-group">
                        <label class="u-field-label">Rate per Area Unit</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-tag u-field-icon"></i>
                            <input type="number" name="rate_per_area_unit" step="0.01" min="0"
                                class="u-input {{ $errors->has('rate_per_area_unit') ? 'is-invalid' : '' }}"
                                value="{{ $uval('rate_per_area_unit') }}" placeholder="0.00">
                        </div>
                        @error('rate_per_area_unit') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('rent_per_month'))
                    <div class="u-field-group">
                        <label class="u-field-label">Rent / Month (BHD)</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-money-bill-wave u-field-icon"></i>
                            <input type="number" name="rent_per_month" step="0.01" min="0"
                                class="u-input {{ $errors->has('rent_per_month') ? 'is-invalid' : '' }}"
                                value="{{ $uval('rent_per_month') }}" placeholder="0.00">
                        </div>
                        @error('rent_per_month') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif

                    @if($ushow('security_deposit_amount'))
                    <div class="u-field-group">
                        <label class="u-field-label">Security Deposit (BHD)</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-shield-halved u-field-icon"></i>
                            <input type="number" name="security_deposit_amount" step="0.01" min="0"
                                class="u-input {{ $errors->has('security_deposit_amount') ? 'is-invalid' : '' }}"
                                value="{{ $uval('security_deposit_amount') }}" placeholder="0.00">
                        </div>
                        @error('security_deposit_amount') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ── LEGAL & UTILITIES ───────────────────────────────── --}}
        <div class="u-section-card">
            <div class="u-section-header">
                <div class="u-section-icon"><i class="fa-solid fa-bolt"></i></div>
                <div class="u-section-meta">
                    <div class="u-section-title">Legal &amp; Utilities</div>
                    <div class="u-section-sub">Municipality reference and meter information</div>
                </div>
                <div class="u-step-badge">5</div>
            </div>
            <div class="u-section-body">

                @if($ushow('municipality_nos'))
                <div class="u-sub-divider"><i class="fa-solid fa-scale-balanced" style="font-size:9px;"></i> Legal</div>
                <div class="u-field-grid" style="margin-bottom:8px;">
                    <div class="u-field-group u-span-2">
                        <label class="u-field-label">Municipality Nos.</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-file-contract u-field-icon"></i>
                            <input type="text" name="municipality_nos"
                                class="u-input {{ $errors->has('municipality_nos') ? 'is-invalid' : '' }}"
                                value="{{ $uval('municipality_nos') }}" placeholder="e.g. MUN-2024-001" maxlength="255">
                        </div>
                        @error('municipality_nos') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                </div>
                @endif

                @php $showElec = $showAll || count(array_intersect(['electricity_installation_date','electricity_meter_no','electricity_ac_no'], $visibleFields)) > 0; @endphp
                @if($showElec)
                <div class="u-sub-divider"><i class="fa-solid fa-bolt" style="font-size:9px;"></i> Electricity</div>
                <div class="u-field-grid" style="margin-bottom:8px;">
                    @if($ushow('electricity_installation_date'))
                    <div class="u-field-group">
                        <label class="u-field-label">Installation Date</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-regular fa-calendar u-field-icon"></i>
                            <input type="date" name="electricity_installation_date"
                                class="u-input {{ $errors->has('electricity_installation_date') ? 'is-invalid' : '' }}"
                                value="{{ old('electricity_installation_date', isset($unit->electricity_installation_date) ? \Carbon\Carbon::parse($unit->electricity_installation_date)->format('Y-m-d') : '') }}">
                        </div>
                        @error('electricity_installation_date') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                    @if($ushow('electricity_meter_no'))
                    <div class="u-field-group">
                        <label class="u-field-label">Meter No.</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-gauge u-field-icon"></i>
                            <input type="text" name="electricity_meter_no"
                                class="u-input {{ $errors->has('electricity_meter_no') ? 'is-invalid' : '' }}"
                                value="{{ $uval('electricity_meter_no') }}" placeholder="e.g. KS003472" maxlength="100">
                        </div>
                        @error('electricity_meter_no') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                    @if($ushow('electricity_ac_no'))
                    <div class="u-field-group">
                        <label class="u-field-label">Electricity A/c No.</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-plug u-field-icon"></i>
                            <input type="text" name="electricity_ac_no"
                                class="u-input {{ $errors->has('electricity_ac_no') ? 'is-invalid' : '' }}"
                                value="{{ $uval('electricity_ac_no') }}" placeholder="Account number" maxlength="100">
                        </div>
                        @error('electricity_ac_no') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                </div>
                @endif

                @php $showWater = $showAll || count(array_intersect(['water_installation_date','water_meter_no'], $visibleFields)) > 0; @endphp
                @if($showWater)
                <div class="u-sub-divider"><i class="fa-solid fa-droplet" style="font-size:9px;"></i> Water</div>
                <div class="u-field-grid">
                    @if($ushow('water_installation_date'))
                    <div class="u-field-group">
                        <label class="u-field-label">Installation Date</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-regular fa-calendar u-field-icon"></i>
                            <input type="date" name="water_installation_date"
                                class="u-input {{ $errors->has('water_installation_date') ? 'is-invalid' : '' }}"
                                value="{{ old('water_installation_date', isset($unit->water_installation_date) ? \Carbon\Carbon::parse($unit->water_installation_date)->format('Y-m-d') : '') }}">
                        </div>
                        @error('water_installation_date') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                    @if($ushow('water_meter_no'))
                    <div class="u-field-group">
                        <label class="u-field-label">Water Meter No.</label>
                        <div class="u-field-wrap u-has-icon">
                            <i class="fa-solid fa-gauge u-field-icon"></i>
                            <input type="text" name="water_meter_no"
                                class="u-input {{ $errors->has('water_meter_no') ? 'is-invalid' : '' }}"
                                value="{{ $uval('water_meter_no') }}" placeholder="e.g. 23H163009453" maxlength="100">
                        </div>
                        @error('water_meter_no') <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div> @enderror
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- ── CUSTOM FIELDS ──────────────────────────────────── --}}
        @if(count($customFieldDefs ?? []) > 0)
        <div class="u-section-card">
            <div class="u-section-header">
                <div class="u-section-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                <div class="u-section-meta">
                    <div class="u-section-title">Custom Fields</div>
                    <div class="u-section-sub">Additional fields configured for this form</div>
                </div>
            </div>
            <div class="u-section-body">
                <div class="u-field-grid">
                    @foreach($customFieldDefs as $def)
                        @if($showAll || in_array($def->name, $visibleFields))
                        @php $cfVal = old('custom_fields.'.$def->name, ($unit->custom_fields[$def->name] ?? '')); @endphp
                        <div class="u-field-group {{ $def->field_type === 'textarea' ? 'u-span-full' : '' }}">
                            <label class="u-field-label">
                                {{ $def->label }}
                                @if($def->is_required) <span class="req">*</span> @endif
                            </label>
                            <div class="u-field-wrap">
                                @if($def->field_type === 'text')
                                    <input type="text" name="custom_fields[{{ $def->name }}]" class="u-input" value="{{ $cfVal }}" {{ $def->is_required ? 'required' : '' }}>
                                @elseif($def->field_type === 'number')
                                    <input type="number" name="custom_fields[{{ $def->name }}]" class="u-input" value="{{ $cfVal }}" {{ $def->is_required ? 'required' : '' }}>
                                @elseif($def->field_type === 'date')
                                    <input type="date" name="custom_fields[{{ $def->name }}]" class="u-input" value="{{ $cfVal }}" {{ $def->is_required ? 'required' : '' }}>
                                @elseif($def->field_type === 'textarea')
                                    <textarea name="custom_fields[{{ $def->name }}]" class="u-textarea" {{ $def->is_required ? 'required' : '' }}>{{ $cfVal }}</textarea>
                                @elseif($def->field_type === 'select')
                                    <select name="custom_fields[{{ $def->name }}]" class="u-select" {{ $def->is_required ? 'required' : '' }}>
                                        <option value="">Select…</option>
                                        @foreach($def->options ?? [] as $opt)
                                            <option value="{{ $opt }}" {{ $cfVal == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            @error('custom_fields.'.$def->name)
                                <div class="u-field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /u-section-stack --}}

    {{-- STICKY ACTIONS BAR --}}
    <div class="u-actions-bar">
        <div class="u-actions-hint">
            <i class="fa-solid fa-circle-info"></i>
            Fields marked <span style="color:var(--danger);font-weight:700;margin:0 2px;">*</span> are required
        </div>
        <div class="u-actions-right">
            <a href="{{ route('property-units.index') }}" class="btn btn-outline">
                <i class="fa-solid fa-xmark"></i> Cancel
            </a>
            <button type="reset" class="btn btn-outline" onclick="return confirm('Reset all fields?')">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
            <button type="submit" class="btn btn-primary btn-lg" id="uSubmitBtn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ isset($unit->id) ? 'Save Changes' : 'Create Unit' }}
            </button>
        </div>
    </div>

</form>

@push('scripts')
<script>
const U_BUILDING_DATA_URL = '{{ url("/property-units/building") }}';
const U_SELECTED_FLOOR_ID = '{{ $selectedFloorId ?? "" }}';

const U_BUILDING_TEXT_FIELDS = ['property_name','land_lord_name','road','area','city'];
const U_BUILDING_NUM_FIELDS  = ['building_no','block'];
const U_BUILDING_SEL_FIELDS  = ['property_code','type_of_ownership','property_type'];

async function uLoadBuildingData(buildingId) {
    const preview    = document.getElementById('buildingPreview');
    const floorSel   = document.getElementById('floorSelect');
    const propSec    = document.getElementById('propSectionInner');
    const addrSec    = document.getElementById('addrSectionInner');

    if (!buildingId) {
        uUnlockBuildingFields();
        uResetFloorSelect();
        if (preview) preview.classList.remove('visible');
        return;
    }

    try {
        const [dataRes, floorsRes] = await Promise.all([
            fetch(`${U_BUILDING_DATA_URL}/${buildingId}/data`),
            fetch(`${U_BUILDING_DATA_URL}/${buildingId}/floors`),
        ]);
        const data   = await dataRes.json();
        const floors = await floorsRes.json();

        // Fill text inputs
        U_BUILDING_TEXT_FIELDS.forEach(f => {
            const el = document.getElementById('f_' + f);
            if (el) { el.value = data[f] ?? ''; el.setAttribute('readonly', true); }
        });
        U_BUILDING_NUM_FIELDS.forEach(f => {
            const el = document.getElementById('f_' + f);
            if (el) { el.value = data[f] ?? ''; el.setAttribute('readonly', true); }
        });
        // Fill selects (can't readonly a select, so we just set value)
        U_BUILDING_SEL_FIELDS.forEach(f => {
            const el = document.getElementById('f_' + f);
            if (el) el.value = data[f] ?? '';
        });

        if (propSec) propSec.classList.add('u-locked-section');
        if (addrSec) addrSec.classList.add('u-locked-section');

        // Show preview card
        if (preview) {
            document.getElementById('previewName').textContent = data.property_name || '—';
            document.getElementById('previewSub').textContent  =
                [data.property_type, data.type_of_ownership, data.city].filter(Boolean).join(' · ') || '—';
            preview.classList.add('visible');
        }

        // Populate floors
        floorSel.innerHTML = '<option value="">— No floor (optional) —</option>';
        floors.forEach(fl => {
            const label  = fl.floor_name + (fl.floor_code ? ` (${fl.floor_code})` : '') + (fl.block_name ? ` — ${fl.block_name}` : '');
            const option = new Option(label, fl.id);
            if (String(fl.id) === String(U_SELECTED_FLOOR_ID)) option.selected = true;
            floorSel.add(option);
        });
        floorSel.disabled = false;

    } catch (e) {
        console.error('Failed to load building data', e);
    }
}

function uResetFloorSelect() {
    const floorSel = document.getElementById('floorSelect');
    floorSel.innerHTML = '<option value="">— Select a building first —</option>';
    floorSel.disabled = true;
}

function uUnlockBuildingFields() {
    [...U_BUILDING_TEXT_FIELDS, ...U_BUILDING_NUM_FIELDS].forEach(f => {
        const el = document.getElementById('f_' + f);
        if (el) el.removeAttribute('readonly');
    });
    const propSec = document.getElementById('propSectionInner');
    const addrSec = document.getElementById('addrSectionInner');
    if (propSec) propSec.classList.remove('u-locked-section');
    if (addrSec) addrSec.classList.remove('u-locked-section');
}

// Submit loading state
document.getElementById('uSubmitBtn')?.addEventListener('click', function() {
    if (document.getElementById('unitForm').checkValidity()) {
        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('buildingSelect');
    if (sel && sel.value) uLoadBuildingData(sel.value);
});
</script>
@endpush
