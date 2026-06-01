{{--
    Shared form partial for create & edit.
    $building, $action, $method, $formFields, $customFieldDefs injected by parent views.
--}}

@push('styles')
<style>
/* ── FORM PROGRESS TRACKER ─────────────────────────────── */
.form-progress {
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 28px;
    padding: 20px 24px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
}
.progress-step {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    position: relative;
}
.progress-step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: calc(20px + 12px);
    right: -12px;
    top: 50%;
    transform: translateY(-50%);
    height: 1.5px;
    background: var(--card-border);
    z-index: 0;
    transition: background 0.4s ease;
}
.progress-step.done:not(:last-child)::after {
    background: var(--accent);
}
.step-bubble {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid var(--card-border);
    background: var(--card-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-muted);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}
.progress-step.active .step-bubble {
    border-color: var(--accent);
    background: var(--accent);
    color: #0B1120;
    box-shadow: 0 0 0 4px var(--accent-dim);
}
.progress-step.done .step-bubble {
    border-color: var(--accent);
    background: var(--accent-dim);
    color: var(--accent);
}
.step-meta { display: flex; flex-direction: column; gap: 1px; }
.step-label {
    font-family: 'Outfit', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-primary);
}
.step-sub {
    font-size: 11px;
    color: var(--text-muted);
    white-space: nowrap;
}
.progress-step.active .step-label { color: var(--accent); }

/* ── SECTION CARDS ─────────────────────────────────────── */
.form-section-stack {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.section-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    opacity: 0;
    transform: translateY(18px);
    animation: cardReveal 0.45s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
.section-card:nth-child(1) { animation-delay: 0.05s; }
.section-card:nth-child(2) { animation-delay: 0.15s; }
.section-card:nth-child(3) { animation-delay: 0.25s; }
.section-card:nth-child(4) { animation-delay: 0.35s; }

@keyframes cardReveal {
    to { opacity: 1; transform: translateY(0); }
}

.section-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--card-border);
    display: flex;
    align-items: center;
    gap: 14px;
    background: linear-gradient(to right, rgba(232,184,109,0.04), transparent);
}
.section-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: var(--accent-dim);
    border: 1px solid rgba(232,184,109,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent);
    font-size: 16px;
    flex-shrink: 0;
}
.section-header-text { flex: 1; }
.section-title {
    font-family: 'Outfit', sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}
.section-subtitle {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 3px;
}
.section-step-badge {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: var(--accent);
    color: #0B1120;
    font-family: 'Outfit', sans-serif;
    font-size: 11px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.section-card-body {
    padding: 24px;
}

/* ── FIELD GRID ────────────────────────────────────────── */
.field-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px 24px;
}
.field-grid .span-2 { grid-column: span 2; }
.field-grid .span-full { grid-column: 1 / -1; }

/* ── FIELD GROUP ───────────────────────────────────────── */
.field-group {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.field-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--text-secondary);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 7px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.field-label .req {
    color: var(--danger);
    font-size: 14px;
    line-height: 1;
}

.field-input-wrap {
    position: relative;
}
.field-input-icon {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 13px;
    pointer-events: none;
    transition: color 0.2s;
}
.has-icon input,
.has-icon select {
    padding-left: 38px;
}

.field-input,
.field-select,
.field-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid var(--input-border);
    border-radius: var(--radius-sm);
    background: var(--input-bg, #fff);
    color: var(--text-primary);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 13.5px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    line-height: 1.5;
}
.field-input::placeholder,
.field-textarea::placeholder { color: var(--text-muted); opacity: 0.7; }

.field-input:hover,
.field-select:hover { border-color: #B0BCCF; }

.field-input:focus,
.field-select:focus,
.field-textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3.5px var(--accent-dim);
    background: #FFFDF8;
}
.field-input-wrap:focus-within .field-input-icon {
    color: var(--accent);
}

.field-input.is-invalid,
.field-select.is-invalid,
.field-textarea.is-invalid {
    border-color: var(--danger);
    background: #FFF8F8;
}
.field-input.is-invalid:focus,
.field-select.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(239,68,68,0.12);
}

.field-error-msg {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 5px;
    font-size: 11.5px;
    color: var(--danger);
    font-weight: 500;
}
.field-error-msg i { font-size: 11px; }

.field-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath fill='%2394A3B8' d='M5 7L0.669873 2.5L9.33013 2.5L5 7Z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
    cursor: pointer;
}
.field-textarea {
    resize: vertical;
    min-height: 90px;
}

/* ── CAPACITY ROW ──────────────────────────────────────── */
.capacity-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
.capacity-item {
    background: var(--page-bg);
    border: 1.5px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 18px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    text-align: center;
    transition: border-color 0.2s, box-shadow 0.2s;
    cursor: default;
}
.capacity-item:focus-within {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-dim);
    background: #FFFDF8;
}
.capacity-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--accent-dim);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent);
    font-size: 14px;
}
.capacity-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-secondary);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.capacity-item input {
    width: 100%;
    border: none;
    background: transparent;
    text-align: center;
    font-family: 'Outfit', sans-serif;
    font-size: 22px;
    font-weight: 800;
    color: var(--text-primary);
    outline: none;
    padding: 0;
    line-height: 1;
    -moz-appearance: textfield;
}
.capacity-item input::-webkit-outer-spin-button,
.capacity-item input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.capacity-item input::placeholder { color: var(--text-muted); font-weight: 400; font-size: 20px; }

/* ── STICKY ACTIONS ────────────────────────────────────── */
.form-actions-bar {
    position: sticky;
    bottom: 0;
    background: var(--card-bg);
    border-top: 1px solid var(--card-border);
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    z-index: 50;
    border-radius: 0 0 var(--radius) var(--radius);
    margin-top: 20px;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.06);
    backdrop-filter: blur(8px);
}
.actions-hint {
    font-size: 12px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}
.actions-hint i { color: var(--accent); }
.actions-right { display: flex; align-items: center; gap: 10px; }

/* ── DIVIDER ───────────────────────────────────────────── */
.address-divider {
    display: grid;
    grid-template-columns: auto 1fr auto 1fr auto;
    align-items: center;
    gap: 8px;
    margin: 4px 0 4px;
}
.divider-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--text-muted);
    padding: 0 4px;
}
.divider-line {
    height: 1px;
    background: var(--card-border);
}

/* ── RESPONSIVE ────────────────────────────────────────── */
@media (max-width: 768px) {
    .form-progress { display: none; }
    .field-grid { grid-template-columns: 1fr; }
    .field-grid .span-2 { grid-column: span 1; }
    .capacity-grid { grid-template-columns: 1fr; }
    .form-actions-bar { padding: 12px 16px; }
    .actions-hint { display: none; }
}
@media (max-width: 520px) {
    .section-card-body { padding: 16px; }
}
</style>
@endpush

@php
    $visibleFields = collect($formFields ?? [])
        ->filter(fn($f) => !empty($f['visible']))
        ->pluck('name')
        ->all();
    $showAll = empty($visibleFields);

    $show = fn(string $field) => $showAll || in_array($field, $visibleFields);

    $val = fn(string $field, $default = '') => old($field, $building->{$field} ?? $default);
@endphp

{{-- PROGRESS TRACKER --}}
<div class="form-progress">
    <div class="progress-step active">
        <div class="step-bubble"><i class="fa-solid fa-building"></i></div>
        <div class="step-meta">
            <span class="step-label">Property</span>
            <span class="step-sub">Identity & ownership</span>
        </div>
    </div>
    <div class="progress-step">
        <div class="step-bubble"><i class="fa-solid fa-location-dot"></i></div>
        <div class="step-meta">
            <span class="step-label">Address</span>
            <span class="step-sub">Physical location</span>
        </div>
    </div>
    <div class="progress-step">
        <div class="step-bubble"><i class="fa-solid fa-layer-group"></i></div>
        <div class="step-meta">
            <span class="step-label">Capacity</span>
            <span class="step-sub">Blocks, floors & units</span>
        </div>
    </div>
</div>

<form method="POST" action="{{ $action }}" id="buildingForm" novalidate>
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div class="form-section-stack">

        {{-- ── SECTION 1: PROPERTY INFORMATION ──────────────── --}}
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-icon-wrap">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="section-header-text">
                    <div class="section-title">Property Information</div>
                    <div class="section-subtitle">Core identity and ownership details</div>
                </div>
                <div class="section-step-badge">1</div>
            </div>
            <div class="section-card-body">
                <div class="field-grid">

                    @if($show('property_name'))
                    <div class="field-group">
                        <label class="field-label">
                            Property Name <span class="req">*</span>
                        </label>
                        <div class="field-input-wrap has-icon">
                            <i class="fa-solid fa-tag field-input-icon"></i>
                            <input
                                type="text"
                                name="property_name"
                                class="field-input {{ $errors->has('property_name') ? 'is-invalid' : '' }}"
                                value="{{ $val('property_name') }}"
                                placeholder="e.g. Miknas Plaza 2"
                                required
                                maxlength="255">
                        </div>
                        @error('property_name')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('property_code'))
                    <div class="field-group">
                        <label class="field-label">
                            Property Code <span class="req">*</span>
                        </label>
                        <div class="field-input-wrap has-icon">
                            <i class="fa-solid fa-barcode field-input-icon"></i>
                            <input
                                type="text"
                                name="property_code"
                                class="field-input {{ $errors->has('property_code') ? 'is-invalid' : '' }}"
                                value="{{ $val('property_code') }}"
                                placeholder="e.g. MP2"
                                required
                                maxlength="10"
                                style="text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">
                        </div>
                        @error('property_code')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('type_of_ownership'))
                    <div class="field-group">
                        <label class="field-label">Type of Ownership</label>
                        <div class="field-input-wrap">
                            <select name="type_of_ownership" class="field-select {{ $errors->has('type_of_ownership') ? 'is-invalid' : '' }}">
                                <option value="">Select ownership type…</option>
                                @foreach(['Owned','Leased','Joint Venture','Managed'] as $opt)
                                    <option value="{{ $opt }}" {{ $val('type_of_ownership') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('type_of_ownership')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('property_type'))
                    <div class="field-group">
                        <label class="field-label">Property Type</label>
                        <div class="field-input-wrap">
                            <select name="property_type" class="field-select {{ $errors->has('property_type') ? 'is-invalid' : '' }}">
                                <option value="">Select property type…</option>
                                @foreach(['Residential','Commercial','Mixed Use','Industrial','Retail'] as $opt)
                                    <option value="{{ $opt }}" {{ $val('property_type') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('property_type')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('land_lord_name'))
                    <div class="field-group span-2">
                        <label class="field-label">Landlord Name</label>
                        <div class="field-input-wrap has-icon">
                            <i class="fa-solid fa-user-tie field-input-icon"></i>
                            <input
                                type="text"
                                name="land_lord_name"
                                class="field-input {{ $errors->has('land_lord_name') ? 'is-invalid' : '' }}"
                                value="{{ $val('land_lord_name') }}"
                                placeholder="e.g. Akram Miknas"
                                maxlength="255">
                        </div>
                        @error('land_lord_name')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- ── SECTION 2: ADDRESS ─────────────────────────────── --}}
        @php
            $anyAddress = $show('building_no') || $show('road') || $show('block') || $show('area') || $show('city');
        @endphp
        @if($anyAddress)
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-icon-wrap">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="section-header-text">
                    <div class="section-title">Address</div>
                    <div class="section-subtitle">Physical location of the building</div>
                </div>
                <div class="section-step-badge">2</div>
            </div>
            <div class="section-card-body">
                <div class="field-grid">

                    @if($show('building_no'))
                    <div class="field-group">
                        <label class="field-label">Building No.</label>
                        <div class="field-input-wrap has-icon">
                            <i class="fa-solid fa-hashtag field-input-icon"></i>
                            <input
                                type="number"
                                name="building_no"
                                class="field-input {{ $errors->has('building_no') ? 'is-invalid' : '' }}"
                                value="{{ $val('building_no') }}"
                                placeholder="202"
                                min="0">
                        </div>
                        @error('building_no')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('block'))
                    <div class="field-group">
                        <label class="field-label">Block</label>
                        <div class="field-input-wrap has-icon">
                            <i class="fa-solid fa-table-cells field-input-icon"></i>
                            <input
                                type="number"
                                name="block"
                                class="field-input {{ $errors->has('block') ? 'is-invalid' : '' }}"
                                value="{{ $val('block') }}"
                                placeholder="324"
                                min="0">
                        </div>
                        @error('block')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('road'))
                    <div class="field-group span-2">
                        <label class="field-label">Road / Street</label>
                        <div class="field-input-wrap has-icon">
                            <i class="fa-solid fa-road field-input-icon"></i>
                            <input
                                type="text"
                                name="road"
                                class="field-input {{ $errors->has('road') ? 'is-invalid' : '' }}"
                                value="{{ $val('road') }}"
                                placeholder="e.g. Avenue 0022"
                                maxlength="255">
                        </div>
                        @error('road')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('area'))
                    <div class="field-group">
                        <label class="field-label">Area / District</label>
                        <div class="field-input-wrap has-icon">
                            <i class="fa-solid fa-map field-input-icon"></i>
                            <input
                                type="text"
                                name="area"
                                class="field-input {{ $errors->has('area') ? 'is-invalid' : '' }}"
                                value="{{ $val('area') }}"
                                placeholder="e.g. Capital Governorate"
                                maxlength="255">
                        </div>
                        @error('area')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('city'))
                    <div class="field-group">
                        <label class="field-label">City</label>
                        <div class="field-input-wrap has-icon">
                            <i class="fa-solid fa-city field-input-icon"></i>
                            <input
                                type="text"
                                name="city"
                                class="field-input {{ $errors->has('city') ? 'is-invalid' : '' }}"
                                value="{{ $val('city') }}"
                                placeholder="e.g. Manama"
                                maxlength="255">
                        </div>
                        @error('city')
                            <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                </div>
            </div>
        </div>
        @endif

        {{-- ── SECTION 3: CAPACITY ────────────────────────────── --}}
        @php
            $anyCapacity = $show('total_no_of_blocks') || $show('total_no_of_floors') || $show('total_no_of_units');
        @endphp
        @if($anyCapacity)
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-icon-wrap">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="section-header-text">
                    <div class="section-title">Capacity</div>
                    <div class="section-subtitle">Building size — enter 0 if not applicable</div>
                </div>
                <div class="section-step-badge">3</div>
            </div>
            <div class="section-card-body">
                <div class="capacity-grid">

                    @if($show('total_no_of_blocks'))
                    <div class="capacity-item {{ $errors->has('total_no_of_blocks') ? 'is-invalid' : '' }}">
                        <div class="capacity-icon"><i class="fa-solid fa-cubes-stacked"></i></div>
                        <div class="capacity-label">Blocks</div>
                        <input
                            type="number"
                            name="total_no_of_blocks"
                            value="{{ $val('total_no_of_blocks') }}"
                            placeholder="0"
                            min="0">
                        @error('total_no_of_blocks')
                            <div class="field-error-msg" style="font-size:10.5px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('total_no_of_floors'))
                    <div class="capacity-item {{ $errors->has('total_no_of_floors') ? 'is-invalid' : '' }}">
                        <div class="capacity-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div class="capacity-label">Floors</div>
                        <input
                            type="number"
                            name="total_no_of_floors"
                            value="{{ $val('total_no_of_floors') }}"
                            placeholder="0"
                            min="0">
                        @error('total_no_of_floors')
                            <div class="field-error-msg" style="font-size:10.5px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    @if($show('total_no_of_units'))
                    <div class="capacity-item {{ $errors->has('total_no_of_units') ? 'is-invalid' : '' }}">
                        <div class="capacity-icon"><i class="fa-solid fa-door-open"></i></div>
                        <div class="capacity-label">Units</div>
                        <input
                            type="number"
                            name="total_no_of_units"
                            value="{{ $val('total_no_of_units') }}"
                            placeholder="0"
                            min="0">
                        @error('total_no_of_units')
                            <div class="field-error-msg" style="font-size:10.5px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                </div>
            </div>
        </div>
        @endif

        {{-- ── CUSTOM FIELDS ──────────────────────────────────── --}}
        @if(count($customFieldDefs ?? []) > 0)
        <div class="section-card">
            <div class="section-card-header">
                <div class="section-icon-wrap">
                    <i class="fa-solid fa-puzzle-piece"></i>
                </div>
                <div class="section-header-text">
                    <div class="section-title">Custom Fields</div>
                    <div class="section-subtitle">Additional fields configured for this form</div>
                </div>
            </div>
            <div class="section-card-body">
                <div class="field-grid">
                    @foreach($customFieldDefs as $def)
                        @if($showAll || in_array($def->name, $visibleFields))
                        @php $cfVal = old('custom_fields.'.$def->name, ($building->custom_fields[$def->name] ?? '')); @endphp
                        <div class="field-group {{ $def->field_type === 'textarea' ? 'span-full' : '' }}">
                            <label class="field-label">
                                {{ $def->label }}
                                @if($def->is_required) <span class="req">*</span> @endif
                            </label>
                            <div class="field-input-wrap">
                                @if($def->field_type === 'text')
                                    <input type="text" name="custom_fields[{{ $def->name }}]" class="field-input" value="{{ $cfVal }}" {{ $def->is_required ? 'required' : '' }}>
                                @elseif($def->field_type === 'number')
                                    <input type="number" name="custom_fields[{{ $def->name }}]" class="field-input" value="{{ $cfVal }}" {{ $def->is_required ? 'required' : '' }}>
                                @elseif($def->field_type === 'date')
                                    <input type="date" name="custom_fields[{{ $def->name }}]" class="field-input" value="{{ $cfVal }}" {{ $def->is_required ? 'required' : '' }}>
                                @elseif($def->field_type === 'textarea')
                                    <textarea name="custom_fields[{{ $def->name }}]" class="field-textarea" {{ $def->is_required ? 'required' : '' }}>{{ $cfVal }}</textarea>
                                @elseif($def->field_type === 'select')
                                    <select name="custom_fields[{{ $def->name }}]" class="field-select" {{ $def->is_required ? 'required' : '' }}>
                                        <option value="">Select…</option>
                                        @foreach($def->options ?? [] as $opt)
                                            <option value="{{ $opt }}" {{ $cfVal == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            @error('custom_fields.'.$def->name)
                                <div class="field-error-msg"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /form-section-stack --}}

    {{-- STICKY ACTIONS BAR --}}
    <div class="form-actions-bar">
        <div class="actions-hint">
            <i class="fa-solid fa-circle-info"></i>
            Fields marked <span style="color:var(--danger);font-weight:700;margin:0 2px;">*</span> are required
        </div>
        <div class="actions-right">
            <a href="{{ route('buildings.index') }}" class="btn btn-outline">
                <i class="fa-solid fa-xmark"></i> Cancel
            </a>
            <button type="reset" class="btn btn-outline" onclick="return confirm('Reset all fields?')">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ isset($building->id) ? 'Save Changes' : 'Create Building' }}
            </button>
        </div>
    </div>

</form>

@push('scripts')
<script>
(function () {
    // Progress tracker — mark step as done when section fields are filled
    const form = document.getElementById('buildingForm');
    const steps = document.querySelectorAll('.progress-step');

    function updateProgress() {
        // Section 1 inputs
        const s1 = ['property_name','property_code'];
        const s1Done = s1.some(n => (form.elements[n] && form.elements[n].value.trim() !== ''));
        if (steps[0]) steps[0].classList.toggle('done', s1Done);

        // Section 2 inputs
        const s2 = ['building_no','road','block','area','city'];
        const s2Done = s2.some(n => (form.elements[n] && form.elements[n].value.trim() !== ''));
        if (steps[1]) steps[1].classList.toggle('done', s2Done);

        // Section 3 inputs
        const s3 = ['total_no_of_blocks','total_no_of_floors','total_no_of_units'];
        const s3Done = s3.some(n => (form.elements[n] && form.elements[n].value.trim() !== ''));
        if (steps[2]) steps[2].classList.toggle('done', s3Done);
    }

    form.addEventListener('input', updateProgress);
    updateProgress(); // run on load for edit form pre-filled values

    // Property code auto-uppercase
    const codeInput = form.elements['property_code'];
    if (codeInput) {
        codeInput.addEventListener('input', () => {
            const pos = codeInput.selectionStart;
            codeInput.value = codeInput.value.toUpperCase();
            codeInput.setSelectionRange(pos, pos);
        });
    }

    // Submit button loading state
    form.addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
        }
    });
})();
</script>
@endpush
