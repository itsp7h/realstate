{{--
    Shared form partial for create & edit.
    $building is injected by both views (new Building or existing).
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
</style>
@endpush

@php
    // Extract visible field names. Empty array means show ALL (no config saved yet).
    $visibleFields = collect($formFields ?? [])
        ->filter(fn($f) => !empty($f['visible']))
        ->pluck('name')
        ->all();
    $showAll = empty($visibleFields);
@endphp

<form method="POST" action="{{ $action }}" id="buildingForm" novalidate>
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div class="form-section-stack">

        {{-- 1. PROPERTY INFO --}}
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
                    @if($showAll || in_array('property_name', $visibleFields))
                    <div class="form-group">
                        <label>Property Name <span class="required">*</span></label>
                        <input type="text" name="property_name"
                            value="{{ old('property_name', $building->property_name ?? '') }}"
                            placeholder="e.g. Miknas Plaza 2"
                            class="{{ $errors->has('property_name') ? 'error' : '' }}" required>
                        @error('property_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('property_code', $visibleFields))
                    <div class="form-group">
                        <label>Property Code <span class="required">*</span></label>
                        <input type="text" name="property_code"
                            value="{{ old('property_code', $building->property_code ?? '') }}"
                            placeholder="e.g. MP2" maxlength="10"
                            class="{{ $errors->has('property_code') ? 'error' : '' }}" required>
                        @error('property_code') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('type_of_ownership', $visibleFields))
                    <div class="form-group">
                        <label>Type of Ownership</label>
                        <select name="type_of_ownership">
                            <option value="">Select…</option>
                            @foreach(['Owned','Leased','Joint Venture','Managed'] as $opt)
                                <option value="{{ $opt }}" {{ old('type_of_ownership', $building->type_of_ownership ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('type_of_ownership') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('property_type', $visibleFields))
                    <div class="form-group">
                        <label>Property Type</label>
                        <select name="property_type">
                            <option value="">Select…</option>
                            @foreach(['Residential','Commercial','Mixed Use','Industrial','Retail'] as $opt)
                                <option value="{{ $opt }}" {{ old('property_type', $building->property_type ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('property_type') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('land_lord_name', $visibleFields))
                    <div class="form-group">
                        <label>Land Lord Name</label>
                        <input type="text" name="land_lord_name"
                            value="{{ old('land_lord_name', $building->land_lord_name ?? '') }}"
                            placeholder="e.g. Akram Miknas">
                        @error('land_lord_name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. ADDRESS --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                    <h3>Address</h3>
                    <p>Physical location of the building</p>
                </div>
                <span class="section-number" style="margin-left:auto;">2</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    @if($showAll || in_array('building_no', $visibleFields))
                    <div class="form-group">
                        <label>Building No.</label>
                        <input type="number" name="building_no"
                            value="{{ old('building_no', $building->building_no ?? '') }}"
                            placeholder="e.g. 202" min="0">
                        @error('building_no') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('road', $visibleFields))
                    <div class="form-group col-span-2">
                        <label>Road</label>
                        <input type="text" name="road"
                            value="{{ old('road', $building->road ?? '') }}"
                            placeholder="e.g. Avenue 0022">
                        @error('road') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('block', $visibleFields))
                    <div class="form-group">
                        <label>Block</label>
                        <input type="number" name="block"
                            value="{{ old('block', $building->block ?? '') }}"
                            placeholder="e.g. 324" min="0">
                        @error('block') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('area', $visibleFields))
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area"
                            value="{{ old('area', $building->area ?? '') }}"
                            placeholder="e.g. Capital Governorate">
                        @error('area') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('city', $visibleFields))
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city"
                            value="{{ old('city', $building->city ?? '') }}"
                            placeholder="e.g. Manama">
                        @error('city') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 3. CAPACITY --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div>
                    <h3>Capacity</h3>
                    <p>Building size and capacity details</p>
                </div>
                <span class="section-number" style="margin-left:auto;">3</span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    @if($showAll || in_array('total_no_of_blocks', $visibleFields))
                    <div class="form-group">
                        <label>Total No. of Blocks</label>
                        <input type="number" name="total_no_of_blocks"
                            value="{{ old('total_no_of_blocks', $building->total_no_of_blocks ?? '') }}"
                            placeholder="e.g. 3" min="0">
                        @error('total_no_of_blocks') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('total_no_of_floors', $visibleFields))
                    <div class="form-group">
                        <label>Total No. of Floors</label>
                        <input type="number" name="total_no_of_floors"
                            value="{{ old('total_no_of_floors', $building->total_no_of_floors ?? '') }}"
                            placeholder="e.g. 10" min="0">
                        @error('total_no_of_floors') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @if($showAll || in_array('total_no_of_units', $visibleFields))
                    <div class="form-group">
                        <label>Total No. of Units</label>
                        <input type="number" name="total_no_of_units"
                            value="{{ old('total_no_of_units', $building->total_no_of_units ?? '') }}"
                            placeholder="e.g. 20" min="0">
                        @error('total_no_of_units') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- CUSTOM FIELDS --}}
        @if(count($customFieldDefs ?? []) > 0)
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon"><i class="fa-solid fa-puzzle-piece"></i></div>
                <div>
                    <h3>Custom Fields</h3>
                    <p>Additional fields configured for this form</p>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    @foreach($customFieldDefs as $def)
                        @if($showAll || in_array($def->name, $visibleFields))
                        <div class="form-group {{ $def->field_type === 'textarea' ? 'col-span-full' : '' }}">
                            <label>{{ $def->label }}{!! $def->is_required ? ' <span class="required">*</span>' : '' !!}</label>
                            @php $val = old('custom_fields.'.$def->name, ($building->custom_fields[$def->name] ?? '')); @endphp
                            @if($def->field_type === 'text')
                                <input type="text" name="custom_fields[{{ $def->name }}]" value="{{ $val }}" {{ $def->is_required ? 'required' : '' }}>
                            @elseif($def->field_type === 'number')
                                <input type="number" name="custom_fields[{{ $def->name }}]" value="{{ $val }}" {{ $def->is_required ? 'required' : '' }}>
                            @elseif($def->field_type === 'date')
                                <input type="date" name="custom_fields[{{ $def->name }}]" value="{{ $val }}" {{ $def->is_required ? 'required' : '' }}>
                            @elseif($def->field_type === 'textarea')
                                <textarea name="custom_fields[{{ $def->name }}]" {{ $def->is_required ? 'required' : '' }}>{{ $val }}</textarea>
                            @elseif($def->field_type === 'select')
                                <select name="custom_fields[{{ $def->name }}]" {{ $def->is_required ? 'required' : '' }}>
                                    <option value="">Select…</option>
                                    @foreach($def->options ?? [] as $opt)
                                        <option value="{{ $opt }}" {{ $val == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('custom_fields.'.$def->name) <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /form-section-stack --}}

    {{-- STICKY ACTIONS BAR --}}
    <div class="form-actions-bar" style="margin-top:20px;">
        <a href="{{ route('buildings.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Cancel
        </a>
        <div style="display:flex;gap:10px;">
            <button type="reset" class="btn btn-outline">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ isset($building->id) ? 'Save Changes' : 'Create Building' }}
            </button>
        </div>
    </div>

</form>
