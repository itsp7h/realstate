{{--
    Shared form partial for create & edit.
    $unit is injected by both views (new PropertyUnit or existing).
    $action and $method are set by the parent view.
--}}

@push('styles')
<style>
    .form-section-stack { display: flex; flex-direction: column; gap: 20px; }
    .section-number {
        width: 24px; height: 24px;
        background: var(--accent);
        color: #0B1120;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Outfit', sans-serif;
        font-size: 11px;
        font-weight: 800;
        flex-shrink: 0;
    }
    .form-actions-bar {
        position: sticky;
        bottom: 0;
        background: var(--card-bg);
        border-top: 1px solid var(--card-border);
        padding: 16px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        z-index: 50;
        border-radius: 0 0 var(--radius) var(--radius);
    }
    .divider-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        margin: 4px 0 12px;
    }
    .divider-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--card-border);
    }
</style>
@endpush

<form method="POST" action="{{ $action }}" id="unitForm" novalidate>
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div class="form-section-stack">

        {{-- 1. PROPERTY LEVEL --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-building"></i></div>
                <div>
                    <h3>Property Information</h3>
                    <p>Basic property-level details</p>
                </div>
                <span class="section-number" style="margin-left:auto;">1</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Property Name <span class="required">*</span></label>
                        <input type="text" name="property_name"
                            value="{{ old('property_name', $unit->property_name ?? '') }}"
                            placeholder="e.g. Miknas Plaza 2"
                            class="{{ $errors->has('property_name') ? 'error' : '' }}" required>
                        @error('property_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Property Code <span class="required">*</span></label>
                        <select name="property_code" class="{{ $errors->has('property_code') ? 'error' : '' }}" required>
                            <option value="">Select code…</option>
                            @foreach(['AAL','MP1','MP2','MP3','MP4','MP5'] as $code)
                                <option value="{{ $code }}" {{ old('property_code', $unit->property_code ?? '') == $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                        @error('property_code') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Type of Ownership</label>
                        <select name="type_of_ownership">
                            <option value="">Select…</option>
                            @foreach(['Owned','Leased','Joint Venture','Managed'] as $opt)
                                <option value="{{ $opt }}" {{ old('type_of_ownership', $unit->type_of_ownership ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('type_of_ownership') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Property Type</label>
                        <select name="property_type">
                            <option value="">Select…</option>
                            @foreach(['Residential','Commercial','Mixed Use','Industrial','Retail'] as $opt)
                                <option value="{{ $opt }}" {{ old('property_type', $unit->property_type ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('property_type') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Land Lord Name</label>
                        <input type="text" name="land_lord_name"
                            value="{{ old('land_lord_name', $unit->land_lord_name ?? '') }}"
                            placeholder="e.g. Akram Miknas">
                        @error('land_lord_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. ADDRESS --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                    <h3>Address</h3>
                    <p>Physical location of the property</p>
                </div>
                <span class="section-number" style="margin-left:auto;">2</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Building No.</label>
                        <input type="number" name="building_no"
                            value="{{ old('building_no', $unit->building_no ?? '') }}"
                            placeholder="e.g. 202" min="1">
                        @error('building_no') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group col-span-2">
                        <label>Road</label>
                        <input type="text" name="road"
                            value="{{ old('road', $unit->road ?? '') }}"
                            placeholder="e.g. Avenue 0022">
                        @error('road') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Block</label>
                        <input type="number" name="block"
                            value="{{ old('block', $unit->block ?? '') }}"
                            placeholder="e.g. 324" min="1">
                        @error('block') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area"
                            value="{{ old('area', $unit->area ?? '') }}"
                            placeholder="e.g. Capital Governorate">
                        @error('area') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city"
                            value="{{ old('city', $unit->city ?? '') }}"
                            placeholder="e.g. Manama">
                        @error('city') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. BLOCK LEVEL --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-cubes"></i></div>
                <div>
                    <h3>Block Details</h3>
                    <p>Block-level configuration</p>
                </div>
                <span class="section-number" style="margin-left:auto;">3</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Total No. of Blocks</label>
                        <input type="number" name="total_no_of_blocks"
                            value="{{ old('total_no_of_blocks', $unit->total_no_of_blocks ?? '') }}"
                            placeholder="e.g. 3" min="1">
                        @error('total_no_of_blocks') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Block Name</label>
                        <input type="text" name="block_name"
                            value="{{ old('block_name', $unit->block_name ?? '') }}"
                            placeholder="e.g. Block 1">
                        @error('block_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Block Code</label>
                        <input type="text" name="block_code"
                            value="{{ old('block_code', $unit->block_code ?? '') }}"
                            placeholder="e.g. BL1">
                        @error('block_code') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Building No. (Block Ref)</label>
                        <input type="number" name="building_no_2"
                            value="{{ old('building_no_2', $unit->building_no_2 ?? '') }}"
                            placeholder="e.g. 202" min="1">
                        @error('building_no_2') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. FLOOR LEVEL --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div>
                    <h3>Floor Details</h3>
                    <p>Floor-level configuration</p>
                </div>
                <span class="section-number" style="margin-left:auto;">4</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Total No. of Floors</label>
                        <input type="number" name="total_no_of_floors"
                            value="{{ old('total_no_of_floors', $unit->total_no_of_floors ?? '') }}"
                            placeholder="e.g. 10" min="1">
                        @error('total_no_of_floors') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Floor Name</label>
                        <input type="text" name="floor_name"
                            value="{{ old('floor_name', $unit->floor_name ?? '') }}"
                            placeholder="e.g. Floor 1">
                        @error('floor_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Floor Code</label>
                        <input type="text" name="floor_code"
                            value="{{ old('floor_code', $unit->floor_code ?? '') }}"
                            placeholder="e.g. FL1">
                        @error('floor_code') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. UNIT LEVEL --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-door-open"></i></div>
                <div>
                    <h3>Unit Details</h3>
                    <p>Individual unit configuration and attributes</p>
                </div>
                <span class="section-number" style="margin-left:auto;">5</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Total No. of Units</label>
                        <input type="number" name="total_no_of_units"
                            value="{{ old('total_no_of_units', $unit->total_no_of_units ?? '') }}"
                            placeholder="e.g. 20" min="1">
                        @error('total_no_of_units') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Unit Name <span class="required">*</span></label>
                        <input type="text" name="unit_name"
                            value="{{ old('unit_name', $unit->unit_name ?? '') }}"
                            placeholder="e.g. MP2 - 11"
                            class="{{ $errors->has('unit_name') ? 'error' : '' }}" required>
                        @error('unit_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group col-span-2">
                        <label>Description</label>
                        <input type="text" name="description"
                            value="{{ old('description', $unit->description ?? '') }}"
                            placeholder="e.g. Miknas Plaza 2 - Flat 11">
                        @error('description') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Unit Type</label>
                        <select name="unit_type">
                            <option value="">Select type…</option>
                            @foreach(['Studio','1BHK','2BHK','3BHK','4BHK','Penthouse','Commercial','Office'] as $opt)
                                <option value="{{ $opt }}" {{ old('unit_type', $unit->unit_type ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('unit_type') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Unit Condition</label>
                        <select name="unit_condition">
                            <option value="">Select condition…</option>
                            @foreach(['Furnished','Fitted','Semi-Furnished','Unfurnished','Shell & Core'] as $opt)
                                <option value="{{ $opt }}" {{ old('unit_condition', $unit->unit_condition ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('unit_condition') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>View</label>
                        <input type="text" name="view"
                            value="{{ old('view', $unit->view ?? '') }}"
                            placeholder="e.g. City View, Sea View">
                        @error('view') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Creation Date</label>
                        <input type="date" name="creation_date"
                            value="{{ old('creation_date', isset($unit->creation_date) ? \Carbon\Carbon::parse($unit->creation_date)->format('Y-m-d') : '') }}">
                        @error('creation_date') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>No. of Parkings (FOC)</label>
                        <input type="number" name="no_of_parkings_foc"
                            value="{{ old('no_of_parkings_foc', $unit->no_of_parkings_foc ?? '') }}"
                            placeholder="e.g. 1" min="0">
                        @error('no_of_parkings_foc') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. AREA & PRICING --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-coins"></i></div>
                <div>
                    <h3>Area &amp; Pricing</h3>
                    <p>Size measurements and financial details</p>
                </div>
                <span class="section-number" style="margin-left:auto;">6</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Area Unit (Sq. Mt. / Sq. Ft.)</label>
                        <select name="area_unit">
                            <option value="">Select unit…</option>
                            @foreach(['Sq. Mt.','Sq. Ft.'] as $opt)
                                <option value="{{ $opt }}" {{ old('area_unit', $unit->area_unit ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('area_unit') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Area (Inside)</label>
                        <input type="number" name="area_inside" step="0.01" min="0"
                            value="{{ old('area_inside', $unit->area_inside ?? '') }}"
                            placeholder="0.00">
                        @error('area_inside') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Area (Terrace)</label>
                        <input type="number" name="area_terrace" step="0.01" min="0"
                            value="{{ old('area_terrace', $unit->area_terrace ?? '') }}"
                            placeholder="0.00">
                        @error('area_terrace') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Rate (per Sq. Mt. / Sq. Ft.)</label>
                        <input type="number" name="rate_per_area_unit" step="0.01" min="0"
                            value="{{ old('rate_per_area_unit', $unit->rate_per_area_unit ?? '') }}"
                            placeholder="0.00">
                        @error('rate_per_area_unit') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Rent (Amount per month)</label>
                        <input type="number" name="rent_per_month" step="0.01" min="0"
                            value="{{ old('rent_per_month', $unit->rent_per_month ?? '') }}"
                            placeholder="0.00">
                        @error('rent_per_month') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Security Deposit Amount</label>
                        <input type="number" name="security_deposit_amount" step="0.01" min="0"
                            value="{{ old('security_deposit_amount', $unit->security_deposit_amount ?? '') }}"
                            placeholder="0.00">
                        @error('security_deposit_amount') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 7. LEGAL --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                <div>
                    <h3>Legal</h3>
                    <p>Municipality and legal reference numbers</p>
                </div>
                <span class="section-number" style="margin-left:auto;">7</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label>Municipality Nos.</label>
                        <input type="text" name="municipality_nos"
                            value="{{ old('municipality_nos', $unit->municipality_nos ?? '') }}"
                            placeholder="e.g. MUN-2024-001">
                        @error('municipality_nos') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- 8. UTILITIES --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-bolt"></i></div>
                <div>
                    <h3>Utilities</h3>
                    <p>Electricity and water meter information</p>
                </div>
                <span class="section-number" style="margin-left:auto;">8</span>
            </div>
            <div class="card-body">
                <div class="divider-label"><i class="fa-solid fa-bolt" style="font-size:10px;"></i> Electricity</div>
                <div class="form-grid" style="margin-bottom:20px;">
                    <div class="form-group">
                        <label>Installation Date</label>
                        <input type="date" name="electricity_installation_date"
                            value="{{ old('electricity_installation_date', isset($unit->electricity_installation_date) ? \Carbon\Carbon::parse($unit->electricity_installation_date)->format('Y-m-d') : '') }}">
                        @error('electricity_installation_date') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Meter No.</label>
                        <input type="text" name="electricity_meter_no"
                            value="{{ old('electricity_meter_no', $unit->electricity_meter_no ?? '') }}"
                            placeholder="e.g. KS003472">
                        @error('electricity_meter_no') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Electricity A/c No</label>
                        <input type="text" name="electricity_ac_no"
                            value="{{ old('electricity_ac_no', $unit->electricity_ac_no ?? '') }}"
                            placeholder="Account number">
                        @error('electricity_ac_no') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="divider-label"><i class="fa-solid fa-droplet" style="font-size:10px;"></i> Water</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Installation Date</label>
                        <input type="date" name="water_installation_date"
                            value="{{ old('water_installation_date', isset($unit->water_installation_date) ? \Carbon\Carbon::parse($unit->water_installation_date)->format('Y-m-d') : '') }}">
                        @error('water_installation_date') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Meter No.</label>
                        <input type="text" name="water_meter_no"
                            value="{{ old('water_meter_no', $unit->water_meter_no ?? '') }}"
                            placeholder="e.g. 23H163009453">
                        @error('water_meter_no') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /form-section-stack --}}

    {{-- STICKY ACTIONS BAR --}}
    <div class="form-actions-bar" style="margin-top:20px;">
        <a href="{{ route('property-units.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Cancel
        </a>
        <div style="display:flex;gap:10px;">
            <button type="reset" class="btn btn-outline">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ isset($unit->id) ? 'Save Changes' : 'Create Unit' }}
            </button>
        </div>
    </div>

</form>
