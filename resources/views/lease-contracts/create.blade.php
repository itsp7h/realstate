@extends('layouts.admin')

@section('title', 'New Lease Contract')
@section('topbar-title', 'New Lease Contract')

@push('styles')
<style>
.section-stack { display:flex;flex-direction:column;gap:16px; }
.section-badge {
    display:inline-flex;align-items:center;gap:6px;
    padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
    text-transform:uppercase;letter-spacing:.06em;
}
.badge-contract  { background:#FFF7ED;color:#C2410C; }
.badge-location  { background:#F0FDF4;color:#15803D; }
.badge-lease     { background:#EFF6FF;color:#1D4ED8; }
.badge-rent      { background:#FFF1F2;color:#BE123C; }
.badge-service   { background:#F5F3FF;color:#6D28D9; }
.badge-financial { background:var(--accent-dim);color:#92400E; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('lease-contracts.index') }}">Lease Contracts</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>New</span>
        </div>
        <h1 class="page-header-title">New Lease Contract</h1>
        <p class="page-header-sub">Fill in all sections below to create a new lease agreement</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('lease-contracts.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="{{ route('lease-contracts.store') }}" novalidate>
@csrf

<div class="section-stack">

    {{-- ── 1. CONTRACT INFO ──────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon" style="background:#FFF7ED;color:#C2410C;"><i class="fa-solid fa-file-contract"></i></div>
            <div>
                <h3>Contract Info <span class="section-badge badge-contract">Required</span></h3>
                <p>Core agreement details and tenant identification</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">

                <div class="form-group">
                    <label>Date <span class="required">*</span></label>
                    <input type="date" name="date"
                        class="{{ $errors->has('date') ? 'error' : '' }}"
                        value="{{ old('date', date('Y-m-d')) }}" required>
                    @error('date') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Lease Agreement No.</label>
                    <input type="text" name="lease_agreement_no"
                        class="{{ $errors->has('lease_agreement_no') ? 'error' : '' }}"
                        value="{{ old('lease_agreement_no') }}"
                        placeholder="Leave blank to auto-generate (e.g. LA-2026-001)" maxlength="100">
                    @error('lease_agreement_no') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Tenant <span class="required">*</span></label>
                    <select name="tenant_id" id="tenant_id_select"
                        class="{{ $errors->has('tenant_id') ? 'error' : '' }}" required>
                        <option value="">— Select Tenant —</option>
                        @foreach($tenants as $t)
                            <option value="{{ $t->id }}" {{ old('tenant_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('tenant_id') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group col-span-2">
                    <label>Unit Description</label>
                    <select name="description" class="{{ $errors->has('description') ? 'error' : '' }}">
                        <option value="">— Select —</option>
                        @foreach(['Fitted','Shell & Core','Semi-Fitted'] as $opt)
                            <option value="{{ $opt }}" {{ old('description') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('description') <span class="field-error">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ── 2. PROPERTY LOCATION ──────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon" style="background:#F0FDF4;color:#15803D;"><i class="fa-solid fa-location-dot"></i></div>
            <div>
                <h3>Property Location <span class="section-badge badge-location">Location</span></h3>
                <p>Property, block, floor, and unit details</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">

                <div class="form-group">
                    <label>Property Name</label>
                    <input type="text" name="property_name"
                        class="{{ $errors->has('property_name') ? 'error' : '' }}"
                        value="{{ old('property_name') }}"
                        placeholder="e.g. Al Reef Tower" maxlength="255">
                    @error('property_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Property Code</label>
                    <input type="text" name="property_code"
                        class="{{ $errors->has('property_code') ? 'error' : '' }}"
                        value="{{ old('property_code') }}"
                        placeholder="e.g. P001" maxlength="50">
                    @error('property_code') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Block Name</label>
                    <input type="text" name="block_name"
                        class="{{ $errors->has('block_name') ? 'error' : '' }}"
                        value="{{ old('block_name') }}"
                        placeholder="e.g. Block A" maxlength="100">
                    @error('block_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Block Code</label>
                    <input type="text" name="block_code"
                        class="{{ $errors->has('block_code') ? 'error' : '' }}"
                        value="{{ old('block_code') }}"
                        placeholder="e.g. BLK-A" maxlength="50">
                    @error('block_code') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Floor Name</label>
                    <input type="text" name="floor_name"
                        class="{{ $errors->has('floor_name') ? 'error' : '' }}"
                        value="{{ old('floor_name') }}"
                        placeholder="e.g. Ground Floor" maxlength="100">
                    @error('floor_name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Floor Code</label>
                    <input type="text" name="floor_code"
                        class="{{ $errors->has('floor_code') ? 'error' : '' }}"
                        value="{{ old('floor_code') }}"
                        placeholder="e.g. GF" maxlength="50">
                    @error('floor_code') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Unit</label>
                    <select name="unit_id" id="unit_id_select"
                        class="{{ $errors->has('unit_id') ? 'error' : '' }}">
                        <option value="">— Select Unit —</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->unit_name }} {{ $u->unit_code ? "({$u->unit_code})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" id="unit_fallback_group">
                    <label>Unit (if not in system)</label>
                    <input type="text" name="unit"
                        class="{{ $errors->has('unit') ? 'error' : '' }}"
                        value="{{ old('unit') }}"
                        placeholder="Free-text unit identifier" maxlength="100">
                    @error('unit') <span class="field-error">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ── 3. LEASE TERM ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon" style="background:#EFF6FF;color:#1D4ED8;"><i class="fa-solid fa-calendar-days"></i></div>
            <div>
                <h3>Lease Term <span class="section-badge badge-lease">Dates</span></h3>
                <p>Lease period, break clause, and notice period</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">

                <div class="form-group">
                    <label>Lease Start Date <span class="required">*</span></label>
                    <input type="date" name="lease_start_date"
                        class="{{ $errors->has('lease_start_date') ? 'error' : '' }}"
                        value="{{ old('lease_start_date') }}" required>
                    @error('lease_start_date') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Lease End Date <span class="required">*</span></label>
                    <input type="date" name="lease_end_date"
                        class="{{ $errors->has('lease_end_date') ? 'error' : '' }}"
                        value="{{ old('lease_end_date') }}" required>
                    @error('lease_end_date') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Break Date</label>
                    <input type="date" name="lease_break_date"
                        class="{{ $errors->has('lease_break_date') ? 'error' : '' }}"
                        value="{{ old('lease_break_date') }}">
                    @error('lease_break_date') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Notice Period</label>
                    <input type="text" name="notice_period"
                        class="{{ $errors->has('notice_period') ? 'error' : '' }}"
                        value="{{ old('notice_period') }}"
                        placeholder="e.g. 3 months" maxlength="50">
                    @error('notice_period') <span class="field-error">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ── 4. RENT COMPONENT ─────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon" style="background:#FFF1F2;color:#BE123C;"><i class="fa-solid fa-coins"></i></div>
            <div>
                <h3>Rent Component <span class="section-badge badge-rent">Financials</span></h3>
                <p>Rental frequency, period, and monthly amount</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">

                <div class="form-group">
                    <label>Invoicing Frequency</label>
                    <select name="invoicing_frequency" class="{{ $errors->has('invoicing_frequency') ? 'error' : '' }}">
                        <option value="">— Select —</option>
                        @foreach(['Monthly','Quarterly','Semi-Annually','Annually'] as $opt)
                            <option value="{{ $opt }}" {{ old('invoicing_frequency') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('invoicing_frequency') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency" class="{{ $errors->has('currency') ? 'error' : '' }}">
                        <option value="">— Select —</option>
                        @foreach(['BHD','USD','EUR','GBP','SAR','AED'] as $cur)
                            <option value="{{ $cur }}" {{ old('currency') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                        @endforeach
                    </select>
                    @error('currency') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Rent Start Date</label>
                    <input type="date" name="rent_start_date"
                        class="{{ $errors->has('rent_start_date') ? 'error' : '' }}"
                        value="{{ old('rent_start_date') }}">
                    @error('rent_start_date') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Rent End Date</label>
                    <input type="date" name="rent_end_date"
                        class="{{ $errors->has('rent_end_date') ? 'error' : '' }}"
                        value="{{ old('rent_end_date') }}">
                    @error('rent_end_date') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group col-span-2">
                    <label>Rent per Month</label>
                    <div style="position:relative;">
                        <input type="number" name="rent_per_month"
                            class="{{ $errors->has('rent_per_month') ? 'error' : '' }}"
                            value="{{ old('rent_per_month') }}"
                            placeholder="0.000" min="0" step="0.001"
                            style="padding-right:60px;">
                        <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:700;color:var(--text-muted);pointer-events:none;">/ mo</span>
                    </div>
                    @error('rent_per_month') <span class="field-error">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ── 5. SERVICE CHARGE ─────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon" style="background:#F5F3FF;color:#6D28D9;"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <div>
                <h3>Service Charge <span class="section-badge badge-service">Optional</span></h3>
                <p>Service charge component details (BD excl. VAT)</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">

                <div class="form-group">
                    <label>Service Frequency</label>
                    <select name="service_frequency" class="{{ $errors->has('service_frequency') ? 'error' : '' }}">
                        <option value="">— Select —</option>
                        @foreach(['Monthly','Quarterly','Semi-Annually','Annually'] as $opt)
                            <option value="{{ $opt }}" {{ old('service_frequency') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('service_frequency') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Service Amount (BD excl. VAT)</label>
                    <input type="number" name="service_amount_bd_excl_vat"
                        class="{{ $errors->has('service_amount_bd_excl_vat') ? 'error' : '' }}"
                        value="{{ old('service_amount_bd_excl_vat') }}"
                        placeholder="0.000" min="0" step="0.001">
                    @error('service_amount_bd_excl_vat') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Service Start Date</label>
                    <input type="date" name="service_start_date"
                        class="{{ $errors->has('service_start_date') ? 'error' : '' }}"
                        value="{{ old('service_start_date') }}">
                    @error('service_start_date') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Service End Date</label>
                    <input type="date" name="service_end_date"
                        class="{{ $errors->has('service_end_date') ? 'error' : '' }}"
                        value="{{ old('service_end_date') }}">
                    @error('service_end_date') <span class="field-error">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ── 6. FINANCIAL ──────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon" style="background:var(--accent-dim);color:#92400E;"><i class="fa-solid fa-landmark"></i></div>
            <div>
                <h3>Financial <span class="section-badge badge-financial">Optional</span></h3>
                <p>Ledger reference, security deposit, and EWA cap</p>
            </div>
        </div>
        <div class="card-body">
            <div class="form-grid">

                <div class="form-group">
                    <label>Rental Income Ledger</label>
                    <input type="text" name="rental_income_ledger"
                        class="{{ $errors->has('rental_income_ledger') ? 'error' : '' }}"
                        value="{{ old('rental_income_ledger') }}"
                        placeholder="e.g. 4100-RENTAL" maxlength="50">
                    @error('rental_income_ledger') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label>Security Deposit</label>
                    <input type="number" name="security_deposit"
                        class="{{ $errors->has('security_deposit') ? 'error' : '' }}"
                        value="{{ old('security_deposit') }}"
                        placeholder="0.000" min="0" step="0.001">
                    @error('security_deposit') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group col-span-full">
                    <label>EWA Cap <span style="font-size:11px;color:var(--text-muted);font-weight:400;text-transform:none">(BHD/bill — landlord covers up to this amount per EWA bill)</span></label>
                    <div style="position:relative;max-width:320px;">
                        <input type="number" name="ewa_cap"
                            class="{{ $errors->has('ewa_cap') ? 'error' : '' }}"
                            value="{{ old('ewa_cap') }}"
                            placeholder="0.000 — leave blank if tenant pays full bill" min="0" step="0.001"
                            style="padding-right:52px;">
                        <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:700;color:var(--text-muted);pointer-events:none;">BHD</span>
                    </div>
                    @error('ewa_cap') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group col-span-full">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" id="vatEnabledInput" name="vat_enabled" value="1"
                            {{ old('vat_enabled') ? 'checked' : '' }} style="width:16px;height:16px;">
                        Charge VAT on this contract
                    </label>
                    <input type="hidden" name="vat_enabled" value="0" id="vatEnabledFallback"
                        {{ old('vat_enabled') ? 'disabled' : '' }}>
                    <div id="vatRateWrap" style="max-width:200px;margin-top:10px; {{ old('vat_enabled') ? '' : 'display:none;' }}">
                        <input type="number" name="vat_rate" id="vatRateInput"
                            class="{{ $errors->has('vat_rate') ? 'error' : '' }}"
                            value="{{ old('vat_rate', 0) }}"
                            placeholder="0.00" min="0" max="100" step="0.01">
                        <span style="font-size:11px;color:var(--text-muted);">% VAT rate for this tenant's invoices</span>
                    </div>
                    @error('vat_rate') <span class="field-error">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- SUBMIT --}}
    <div style="display:flex;gap:10px;justify-content:flex-end;padding-bottom:8px;">
        <a href="{{ route('lease-contracts.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Create Contract
        </button>
    </div>

</div>
</form>

@endsection

@push('scripts')
<script>
// Auto-fill unit fallback
const unitSelect = document.getElementById('unit_id_select');
const unitFallback = document.getElementById('unit_fallback_group').querySelector('input');
unitSelect.addEventListener('change', function() {
    if (this.value) {
        const text = this.options[this.selectedIndex].text;
        unitFallback.value = text;
    }
});

// VAT toggle
const vatEnabledInput = document.getElementById('vatEnabledInput');
const vatEnabledFallback = document.getElementById('vatEnabledFallback');
const vatRateWrap = document.getElementById('vatRateWrap');
vatEnabledInput.addEventListener('change', function() {
    vatRateWrap.style.display = this.checked ? '' : 'none';
    vatEnabledFallback.disabled = this.checked;
});
</script>
@endpush
