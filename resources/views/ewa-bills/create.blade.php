@extends('layouts.admin')

@section('title', $record ? 'Edit EWA Bill' : 'New EWA Bill')
@section('topbar-title', 'EWA Bills')

@push('styles')
<style>
.form-card {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 28px 32px; margin-bottom: 20px;
}
.form-card-title {
    font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700;
    color: var(--text-primary); margin-bottom: 20px; padding-bottom: 14px;
    border-bottom: 1px solid var(--card-border); display: flex; align-items: center; gap: 8px;
}
.form-card-title.ewa { color: #0D9488; }
.form-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 18px; }
.form-grid.cols-3 { grid-template-columns: repeat(3,1fr); }
.form-grid.cols-4 { grid-template-columns: repeat(4,1fr); }
.form-grid.cols-1 { grid-template-columns: 1fr; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.form-label .required { color: #DC2626; margin-left: 2px; }
.form-label .unit-tag { color: var(--accent); font-size: 10px; margin-left: 4px; background: var(--accent-dim); padding: 1px 6px; border-radius: 4px; }
.form-control {
    padding: 9px 13px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
    transition: border-color 0.18s; width: 100%; box-sizing: border-box;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.form-control:focus { border-color: var(--accent); }
.form-control.is-invalid { border-color: #DC2626; }
.invalid-feedback { font-size: 11px; color: #DC2626; margin-top: 3px; display: none; }
.form-control.is-invalid ~ .invalid-feedback { display: block; }
textarea.form-control { resize: vertical; min-height: 80px; }

/* Meter reading row */
.meter-row {
    background: var(--page-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius-sm); padding: 16px 20px; margin-bottom: 14px;
}
.meter-row-header {
    display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
    font-size: 13px; font-weight: 700; color: var(--text-primary);
}
.meter-badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.meter-badge.elec  { background: #FEF9C3; color: #713F12; }
.meter-badge.water { background: #E0F2FE; color: #0369A1; }

/* Consumption auto-computed */
.computed-field {
    background: var(--page-bg); border: 1.5px dashed var(--card-border);
    border-radius: var(--radius-sm); padding: 9px 13px; font-size: 13px;
    color: var(--text-muted); font-family: 'Outfit', sans-serif; font-weight: 700;
}
.computed-field.has-value { color: var(--text-primary); }

/* Amount suffix */
.amount-wrap { position: relative; }
.amount-wrap input { padding-right: 52px; font-family: 'Outfit', sans-serif; font-weight: 700; }
.amount-wrap::after {
    content: 'BHD'; position: absolute; right: 13px; top: 50%;
    transform: translateY(-50%); font-size: 11px; font-weight: 700;
    color: var(--text-muted); pointer-events: none;
}

/* Total summary */
.total-summary {
    background: linear-gradient(135deg, #0D9488 0%, #0369A1 100%);
    border-radius: var(--radius-sm); padding: 18px 24px;
    display: flex; align-items: center; justify-content: space-between; margin-top: 4px;
}
.total-summary .lbl { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.85); }
.total-summary .val { font-family: 'Outfit',sans-serif; font-size: 28px; font-weight: 800; color: #fff; }
.total-summary .cur { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.75); margin-left: 4px; }

/* Cap & split preview */
.cap-section { margin-top: 14px; }
.cap-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.cap-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; flex-shrink: 0; min-width: 80px; }
.cap-source-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700;
    background: #F0FDFA; color: #0D9488; border: 1px solid #99F6E4;
}
.split-preview { display: none; margin-top: 12px; padding: 14px 16px; background: var(--page-bg); border: 1px solid var(--card-border); border-radius: var(--radius-sm); }
.split-preview.show { display: block; }
.split-bar { height: 8px; border-radius: 4px; overflow: hidden; display: flex; margin: 8px 0 12px; }
.split-bar-landlord { background: #059669; transition: width 0.3s ease; }
.split-bar-tenant   { background: #D97706; transition: width 0.3s ease; }
.split-amounts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.split-cell { padding: 10px 14px; border-radius: var(--radius-sm); }
.split-cell.landlord { background: #ECFDF5; border: 1px solid #BBF7D0; }
.split-cell.tenant   { background: #FFFBEB; border: 1px solid #FDE68A; }
.split-cell-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 3px; }
.split-cell.landlord .split-cell-lbl { color: #059669; }
.split-cell.tenant   .split-cell-lbl { color: #D97706; }
.split-cell-val { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; }
.split-cell.landlord .split-cell-val { color: #059669; }
.split-cell.tenant   .split-cell-val { color: #D97706; }

/* Combobox (reused from invoice) */
.contract-combobox { position: relative; }
.cbox-trigger {
    display: flex; align-items: center;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); transition: border-color 0.18s; overflow: hidden; cursor: text;
}
.cbox-trigger:focus-within { border-color: var(--accent); }
.cbox-search {
    flex: 1; padding: 9px 13px; font-size: 13px;
    border: none; background: transparent; color: var(--text-primary);
    outline: none; font-family: 'Plus Jakarta Sans', sans-serif; min-width: 0;
}
.cbox-search::placeholder { color: var(--text-muted); }
.cbox-clear { padding: 0 12px; font-size: 14px; color: var(--text-muted); cursor: pointer; background: none; border: none; line-height: 1; display: none; align-items: center; }
.cbox-clear:hover { color: #DC2626; }
.cbox-clear.visible { display: flex; }
.cbox-spinner { padding: 0 10px; color: var(--text-muted); font-size: 13px; display: none; align-items: center; }
.cbox-spinner.visible { display: flex; }
.cbox-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: var(--card-bg); border: 1.5px solid var(--accent); border-radius: var(--radius-sm);
    box-shadow: 0 8px 24px rgba(0,0,0,0.14); z-index: 999; max-height: 280px; overflow-y: auto; display: none;
}
.cbox-dropdown.open { display: block; }
.cbox-item { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--card-border); transition: background 0.12s; }
.cbox-item:last-child { border-bottom: none; }
.cbox-item:hover, .cbox-item.focused { background: var(--accent-dim); }
.cbox-item-main { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.cbox-item-main mark { background: #FEF08A; color: #713F12; border-radius: 2px; padding: 0 1px; font-weight: 700; }
.cbox-item-sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.cbox-hint { padding: 8px 14px; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--card-border); background: var(--page-bg); }
.cbox-empty { padding: 20px 16px; text-align: center; color: var(--text-muted); font-size: 13px; }

.contract-selected { display: none; margin-top: 10px; border: 1.5px solid #0D9488; border-radius: var(--radius-sm); background: #F0FDFA; overflow: hidden; }
.contract-selected.show { display: block; }
.contract-selected-header { padding: 8px 14px; background: #0D9488; display: flex; align-items: center; justify-content: space-between; }
.contract-selected-header span { font-size: 12px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 6px; }
.contract-selected-change { font-size: 11px; color: rgba(255,255,255,0.85); cursor: pointer; background: none; border: none; font-family: inherit; font-weight: 600; display: flex; align-items: center; gap: 4px; padding: 0; }
.contract-selected-change:hover { color: #fff; }
.contract-selected-body { padding: 10px 14px; display: grid; grid-template-columns: repeat(3,1fr); gap: 8px 14px; }
.cs-item span { font-size: 10px; color: #0D9488; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px; }
.cs-item strong { font-size: 13px; color: var(--text-primary); }

/* ── SMART IMPORT ZONE ───────────────────────────────────── */
.import-zone {
    background: linear-gradient(135deg, #F0FDFA 0%, #E0F2FE 100%);
    border: 2px dashed #0D9488; border-radius: var(--radius);
    padding: 28px 32px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 20px; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.import-zone:hover, .import-zone.drag-over { border-color: #0369A1; background: linear-gradient(135deg, #E0F2FE 0%, #BFDBFE 100%); }
.import-zone-icon {
    width: 56px; height: 56px; border-radius: var(--radius-sm); flex-shrink: 0;
    background: rgba(13,148,136,0.15); display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: #0D9488;
}
.import-zone-text h3 { font-family: 'Outfit',sans-serif; font-size: 16px; font-weight: 700; color: #0D9488; margin-bottom: 3px; }
.import-zone-text p  { font-size: 12px; color: #0369A1; }
.import-zone-btn { margin-left: auto; flex-shrink: 0; }

.import-progress {
    display: none; align-items: center; gap: 12px; padding: 14px 18px;
    background: #F0FDFA; border: 1px solid #99F6E4; border-radius: var(--radius-sm);
    margin-bottom: 8px;
}
.import-progress.show { display: flex; }
.import-progress-bar-wrap { flex: 1; height: 6px; background: #CCFBF1; border-radius: 3px; overflow: hidden; }
.import-progress-bar { height: 100%; background: #0D9488; border-radius: 3px; transition: width 0.3s; width: 0%; }

.import-result {
    display: none; padding: 14px 18px; border-radius: var(--radius-sm);
    margin-bottom: 8px; font-size: 13px;
}
.import-result.show { display: flex; align-items: flex-start; gap: 12px; }
.import-result.success { background: #F0FDFA; border: 1px solid #99F6E4; color: #0D9488; }
.import-result.warning { background: #FFFBEB; border: 1px solid #FDE68A; color: #D97706; }
.import-result.error   { background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626; }
.import-result-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.import-result-body strong { display: block; font-weight: 700; margin-bottom: 4px; }
.import-result-body ul { margin: 6px 0 0 16px; padding: 0; font-size: 12px; line-height: 1.6; }

.form-actions { display: flex; gap: 10px; align-items: center; justify-content: flex-end; padding-top: 6px; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">{{ $record ? 'Edit EWA Bill' : 'New EWA Bill' }}</h1>
        <p class="page-header-sub">Electricity &amp; Water Authority — Kingdom of Bahrain</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('ewa-bills.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <i class="fa-solid fa-circle-exclamation"></i>
    <ul style="margin:6px 0 0 18px;padding:0">
        @foreach($errors->all() as $e)<li style="font-size:13px">{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

@if(!$record)
{{-- SMART IMPORT --}}
<input type="file" id="importFileInput" accept=".pdf" style="display:none">
<div class="import-zone" id="importZone" onclick="document.getElementById('importFileInput').click()">
    <div class="import-zone-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
    <div class="import-zone-text">
        <h3>Smart Import from EWA Bill PDF</h3>
        <p>Upload your EWA bill PDF and fields will be auto-filled &mdash; drag &amp; drop or click to browse</p>
    </div>
    <div class="import-zone-btn">
        <span class="btn btn-outline btn-sm" style="pointer-events:none;border-color:#0D9488;color:#0D9488">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-fill
        </span>
    </div>
</div>

<div class="import-progress" id="importProgress">
    <i class="fa-solid fa-circle-notch fa-spin" style="color:#0D9488;font-size:16px"></i>
    <span style="font-size:13px;color:#0D9488;font-weight:600">Reading EWA bill…</span>
    <div class="import-progress-bar-wrap"><div class="import-progress-bar" id="importBar"></div></div>
</div>

<div class="import-result" id="importResult">
    <div class="import-result-icon" id="importResultIcon"></div>
    <div class="import-result-body" id="importResultBody"></div>
</div>
@endif

<form method="POST"
      action="{{ $record ? route('ewa-bills.update', $record) : route('ewa-bills.store') }}"
      id="ewa-form" novalidate>
    @csrf
    @if($record) @method('PUT') @endif

    {{-- CUSTOMER / PROPERTY --}}
    <div class="form-card">
        <div class="form-card-title ewa">
            <i class="fa-solid fa-droplet"></i> Customer &amp; Property
        </div>

        {{-- Contract combobox --}}
        <div class="form-group" style="margin-bottom:18px">
            <label class="form-label">Link to Lease Contract <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none">(optional)</span></label>
            <input type="hidden" name="lease_contract_id" id="contractId" value="{{ old('lease_contract_id', $record?->lease_contract_id) }}">

            @if($record && $record->lease_contract_id)
                <div class="contract-selected show">
                    <div class="contract-selected-header">
                        <span><i class="fa-solid fa-circle-check"></i> Linked Contract</span>
                    </div>
                    <div class="contract-selected-body">
                        <div class="cs-item"><span>Tenant</span><strong>{{ $record->tenant_name }}</strong></div>
                        <div class="cs-item"><span>Property</span><strong>{{ $record->property_name }}</strong></div>
                        <div class="cs-item"><span>Unit</span><strong>{{ $record->unit ?: '—' }}</strong></div>
                    </div>
                </div>
            @else
                <div class="contract-combobox" id="contractCombobox">
                    <div class="cbox-trigger" id="cboxTrigger">
                        <input type="text" id="cboxSearch" class="cbox-search"
                               placeholder="Search tenant, property… (optional)" autocomplete="off">
                        <span class="cbox-spinner" id="cboxSpinner"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                        <button type="button" class="cbox-clear" id="cboxClear" tabindex="-1"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="cbox-dropdown" id="cboxDropdown">
                        <div class="cbox-hint">Type to search lease contracts</div>
                        <div id="cboxList"></div>
                    </div>
                </div>
                <div class="contract-selected" id="contractSelected">
                    <div class="contract-selected-header">
                        <span><i class="fa-solid fa-circle-check"></i> Contract Linked</span>
                        <button type="button" class="contract-selected-change" id="cboxChangeBtn"><i class="fa-solid fa-pen"></i> Change</button>
                    </div>
                    <div class="contract-selected-body" id="contractPreviewBody"></div>
                </div>
            @endif
        </div>

        <div class="form-grid cols-3">
            <div class="form-group">
                <label class="form-label">Tenant Name <span class="required">*</span></label>
                <input type="text" name="tenant_name" id="tenantNameInput"
                       class="form-control {{ $errors->has('tenant_name') ? 'is-invalid' : '' }}"
                       value="{{ old('tenant_name', $record?->tenant_name) }}" maxlength="255" required>
                <div class="invalid-feedback">{{ $errors->first('tenant_name') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Property</label>
                <input type="text" name="property_name" id="propertyNameInput"
                       class="form-control {{ $errors->has('property_name') ? 'is-invalid' : '' }}"
                       value="{{ old('property_name', $record?->property_name) }}" maxlength="255">
                <div class="invalid-feedback">{{ $errors->first('property_name') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" id="unitInput"
                       class="form-control {{ $errors->has('unit') ? 'is-invalid' : '' }}"
                       value="{{ old('unit', $record?->unit) }}" maxlength="100">
                <div class="invalid-feedback">{{ $errors->first('unit') }}</div>
            </div>
        </div>
    </div>

    {{-- BILL INFO --}}
    <div class="form-card">
        <div class="form-card-title ewa">
            <i class="fa-solid fa-file-invoice"></i> Bill Information
        </div>
        <div class="form-grid cols-4">
            <div class="form-group">
                <label class="form-label">EWA Account No.</label>
                <input type="text" name="ewa_account_number"
                       class="form-control {{ $errors->has('ewa_account_number') ? 'is-invalid' : '' }}"
                       value="{{ old('ewa_account_number', $record?->ewa_account_number) }}" maxlength="50"
                       placeholder="e.g. 12345678">
                <div class="invalid-feedback">{{ $errors->first('ewa_account_number') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Billing Period <span class="required">*</span></label>
                <input type="text" name="billing_period"
                       class="form-control {{ $errors->has('billing_period') ? 'is-invalid' : '' }}"
                       value="{{ old('billing_period', $record?->billing_period) }}" maxlength="30"
                       placeholder="e.g. April 2024" required>
                <div class="invalid-feedback">{{ $errors->first('billing_period') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Reading Date</label>
                <input type="date" name="reading_date"
                       class="form-control {{ $errors->has('reading_date') ? 'is-invalid' : '' }}"
                       value="{{ old('reading_date', $record?->reading_date?->format('Y-m-d')) }}">
                <div class="invalid-feedback">{{ $errors->first('reading_date') }}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Reading Type <span class="required">*</span></label>
                <select name="reading_type" class="form-control {{ $errors->has('reading_type') ? 'is-invalid' : '' }}">
                    <option value="actual"    {{ old('reading_type', $record?->reading_type ?? 'actual') === 'actual'    ? 'selected' : '' }}>Actual (A)</option>
                    <option value="estimated" {{ old('reading_type', $record?->reading_type) === 'estimated' ? 'selected' : '' }}>Estimated (E)</option>
                </select>
                <div class="invalid-feedback">{{ $errors->first('reading_type') }}</div>
            </div>
        </div>
    </div>

    {{-- ELECTRICITY --}}
    <div class="form-card">
        <div class="form-card-title ewa">
            <i class="fa-solid fa-bolt"></i> Electricity
        </div>
        <div class="meter-row">
            <div class="meter-row-header">
                <span class="meter-badge elec">kWh</span>
                Meter Readings
            </div>
            <div class="form-grid cols-4">
                <div class="form-group">
                    <label class="form-label">Previous Reading</label>
                    <input type="number" name="elec_prev_reading" id="elecPrev" step="1" min="0"
                           class="form-control" value="{{ old('elec_prev_reading', $record?->elec_prev_reading) }}"
                           placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Current Reading</label>
                    <input type="number" name="elec_curr_reading" id="elecCurr" step="1" min="0"
                           class="form-control" value="{{ old('elec_curr_reading', $record?->elec_curr_reading) }}"
                           placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Consumption <span class="unit-tag">kWh</span></label>
                    <div class="computed-field" id="elecConsumption">—</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Electricity Charges <span class="unit-tag">BHD</span></label>
                    <div class="amount-wrap">
                        <input type="number" name="elec_charges" id="elecCharges" step="0.001" min="0"
                               class="form-control" value="{{ old('elec_charges', $record?->elec_charges) }}"
                               placeholder="0.000">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- WATER --}}
    <div class="form-card">
        <div class="form-card-title ewa">
            <i class="fa-solid fa-droplet"></i> Water
        </div>
        <div class="meter-row">
            <div class="meter-row-header">
                <span class="meter-badge water">m³</span>
                Meter Readings
            </div>
            <div class="form-grid cols-4">
                <div class="form-group">
                    <label class="form-label">Previous Reading</label>
                    <input type="number" name="water_prev_reading" id="waterPrev" step="0.001" min="0"
                           class="form-control" value="{{ old('water_prev_reading', $record?->water_prev_reading) }}"
                           placeholder="0.000">
                </div>
                <div class="form-group">
                    <label class="form-label">Current Reading</label>
                    <input type="number" name="water_curr_reading" id="waterCurr" step="0.001" min="0"
                           class="form-control" value="{{ old('water_curr_reading', $record?->water_curr_reading) }}"
                           placeholder="0.000">
                </div>
                <div class="form-group">
                    <label class="form-label">Consumption <span class="unit-tag">m³</span></label>
                    <div class="computed-field" id="waterConsumption">—</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Water Charges <span class="unit-tag">BHD</span></label>
                    <div class="amount-wrap">
                        <input type="number" name="water_charges" id="waterCharges" step="0.001" min="0"
                               class="form-control" value="{{ old('water_charges', $record?->water_charges) }}"
                               placeholder="0.000">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TOTAL --}}
    <div class="form-card">
        <div class="form-card-title ewa">
            <i class="fa-solid fa-calculator"></i> Total
        </div>

        <div class="total-summary">
            <div class="lbl">Total Bill (EWA)</div>
            <div><span class="val" id="totalDisplay">0.000</span><span class="cur">BHD</span></div>
        </div>

        {{-- CAP & SPLIT --}}
        <div class="cap-section">
            <div class="cap-row">
                <span class="cap-label">EWA Cap</span>
                <div class="amount-wrap" style="flex:1;max-width:260px">
                    <input type="number" name="ewa_cap" id="ewaCap" step="0.001" min="0"
                           class="form-control {{ $errors->has('ewa_cap') ? 'is-invalid' : '' }}"
                           value="{{ old('ewa_cap', $record?->ewa_cap) }}"
                           placeholder="0.000 — no cap">
                </div>
                <span id="capSourceBadge" class="cap-source-badge" style="display:none">
                    <i class="fa-solid fa-link"></i> from contract
                </span>
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-left:90px;margin-top:-6px">
                Landlord covers up to this amount per bill. Tenant pays the overage.
            </div>

            <div class="split-preview" id="splitPreview">
                <div style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Bill split</div>
                <div class="split-bar">
                    <div class="split-bar-landlord" id="splitBarLandlord" style="width:50%"></div>
                    <div class="split-bar-tenant"   id="splitBarTenant"   style="width:50%"></div>
                </div>
                <div class="split-amounts">
                    <div class="split-cell landlord">
                        <div class="split-cell-lbl"><i class="fa-solid fa-shield-halved"></i> Landlord covers</div>
                        <div class="split-cell-val"><span id="landlordDisplay">0.000</span> <span style="font-size:12px;font-weight:600">BHD</span></div>
                    </div>
                    <div class="split-cell tenant">
                        <div class="split-cell-lbl"><i class="fa-solid fa-user"></i> Tenant owes</div>
                        <div class="split-cell-val"><span id="tenantDisplay">0.000</span> <span style="font-size:12px;font-weight:600">BHD</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-grid" style="margin-top:18px">
            <div class="form-group">
                <label class="form-label">Due Date <span class="required">*</span></label>
                <input type="date" name="due_date"
                       class="form-control {{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                       value="{{ old('due_date', $record?->due_date?->format('Y-m-d')) }}" required>
                <div class="invalid-feedback">{{ $errors->first('due_date') }}</div>
            </div>
            @if($record)
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    @foreach(['draft'=>'Draft','issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid','overdue'=>'Overdue','cancelled'=>'Cancelled'] as $v=>$l)
                    <option value="{{ $v }}" {{ old('status', $record->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        <div class="form-group" style="margin-top:16px">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="Internal notes…">{{ old('notes', $record?->notes) }}</textarea>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('ewa-bills.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid {{ $record ? 'fa-floppy-disk' : 'fa-plus' }}"></i>
            {{ $record ? 'Save Changes' : 'Create EWA Bill' }}
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    // ── Auto-compute consumption ──────────────────────────────
    function computeConsumption(prevId, currId, displayId, decimals) {
        const prev = parseFloat(document.getElementById(prevId)?.value) || 0;
        const curr = parseFloat(document.getElementById(currId)?.value) || 0;
        const el   = document.getElementById(displayId);
        if (!el) return;
        const val  = Math.max(0, curr - prev);
        el.textContent = val > 0 ? val.toFixed(decimals) : '—';
        el.classList.toggle('has-value', val > 0);
    }

    ['elecPrev','elecCurr'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => computeConsumption('elecPrev','elecCurr','elecConsumption',0));
    });
    ['waterPrev','waterCurr'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => computeConsumption('waterPrev','waterCurr','waterConsumption',3));
    });

    // ── Auto-compute total ────────────────────────────────────
    function computeTotal() {
        const elec  = parseFloat(document.getElementById('elecCharges')?.value)    || 0;
        const water = parseFloat(document.getElementById('waterCharges')?.value)   || 0;
        const total = Math.max(0, elec + water);
        document.getElementById('totalDisplay').textContent = total.toFixed(3);
        computeSplit();
    }

    ['elecCharges','waterCharges'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', computeTotal);
    });

    // ── Cap & split preview ───────────────────────────────────
    function computeSplit() {
        const total = parseFloat(document.getElementById('totalDisplay').textContent) || 0;
        const cap   = parseFloat(document.getElementById('ewaCap')?.value) || 0;

        if (cap <= 0) {
            document.getElementById('splitPreview')?.classList.remove('show');
            return;
        }

        const landlord = Math.min(total, cap);
        const tenant   = Math.max(0, total - cap);
        const pct      = total > 0 ? (landlord / total * 100) : 100;

        document.getElementById('landlordDisplay').textContent = landlord.toFixed(3);
        document.getElementById('tenantDisplay').textContent   = tenant.toFixed(3);
        document.getElementById('splitBarLandlord').style.width = pct + '%';
        document.getElementById('splitBarTenant').style.width   = (100 - pct) + '%';
        document.getElementById('splitPreview')?.classList.add('show');
    }

    document.getElementById('ewaCap')?.addEventListener('input', computeSplit);

    // ── Init on edit ──────────────────────────────────────────
    computeConsumption('elecPrev','elecCurr','elecConsumption',0);
    computeConsumption('waterPrev','waterCurr','waterConsumption',3);
    computeTotal();
    computeSplit();

    // ── Contract combobox ─────────────────────────────────────
    const searchInput = document.getElementById('cboxSearch');
    if (!searchInput) return;

    const contractId  = document.getElementById('contractId');
    const dropdown    = document.getElementById('cboxDropdown');
    const list        = document.getElementById('cboxList');
    const spinner     = document.getElementById('cboxSpinner');
    const clearBtn    = document.getElementById('cboxClear');
    const selected    = document.getElementById('contractSelected');
    const previewBody = document.getElementById('contractPreviewBody');
    const changeBtn   = document.getElementById('cboxChangeBtn');
    const trigger     = document.getElementById('cboxTrigger');
    const searchUrl   = '{{ route("lease-contracts.search") }}';

    let debounce = null, lastResults = [], focusedIdx = -1;

    function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function highlight(text, q) {
        if (!q) return esc(text);
        return esc(text).replace(new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')','gi'), '<mark>$1</mark>');
    }

    function doSearch(q) {
        spinner.classList.add('visible');
        fetch(searchUrl + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                spinner.classList.remove('visible');
                lastResults = data; focusedIdx = -1;
                list.innerHTML = data.length === 0
                    ? '<div class="cbox-empty">No contracts found</div>'
                    : data.map((c,i) => `<div class="cbox-item" data-idx="${i}">
                        <div class="cbox-item-main">${highlight(c.tenant_name, q)} <span style="color:var(--text-muted);font-weight:400;font-size:12px">— ${highlight(c.property_name, q)}</span></div>
                        <div class="cbox-item-sub">${c.unit ? '<span>' + esc(c.unit) + '</span>' : ''}${c.agreement_no ? '<span>' + esc(c.agreement_no) + '</span>' : ''}</div>
                      </div>`).join('');
                list.querySelectorAll('.cbox-item').forEach((el, i) => {
                    el.addEventListener('mousedown', e => { e.preventDefault(); selectContract(lastResults[i]); });
                });
                dropdown.classList.add('open');
            })
            .catch(() => spinner.classList.remove('visible'));
    }

    function selectContract(c) {
        contractId.value = c.id;
        document.getElementById('tenantNameInput').value   = c.tenant_name;
        document.getElementById('propertyNameInput').value = c.property_name;
        document.getElementById('unitInput').value         = c.unit || '';

        // Auto-fill EWA cap from contract
        const capInput  = document.getElementById('ewaCap');
        const capBadge  = document.getElementById('capSourceBadge');
        if (capInput) {
            if (c.ewa_cap) {
                capInput.value = c.ewa_cap;
                if (capBadge) capBadge.style.display = 'inline-flex';
            } else {
                capInput.value = '';
                if (capBadge) capBadge.style.display = 'none';
            }
            computeSplit();
        }

        searchInput.value = '';
        clearBtn.classList.remove('visible');
        dropdown.classList.remove('open');
        trigger.style.display = 'none';
        const capHint = c.ewa_cap ? `<div class="cs-item"><span>EWA Cap</span><strong>${esc(c.ewa_cap)} BHD</strong></div>` : '';
        previewBody.innerHTML = `
            <div class="cs-item"><span>Tenant</span><strong>${esc(c.tenant_name)}</strong></div>
            <div class="cs-item"><span>Property</span><strong>${esc(c.property_name)}</strong></div>
            <div class="cs-item"><span>Unit</span><strong>${esc(c.unit || '—')}</strong></div>
            ${capHint}`;
        selected.classList.add('show');
    }

    changeBtn?.addEventListener('click', () => {
        contractId.value = '';
        selected.classList.remove('show');
        trigger.style.display = '';
        searchInput.value = '';
        clearBtn.classList.remove('visible');
        const capBadge = document.getElementById('capSourceBadge');
        if (capBadge) capBadge.style.display = 'none';
        searchInput.focus();
        doSearch('');
    });

    searchInput.addEventListener('input', function () {
        const q = this.value.trim();
        clearBtn.classList.toggle('visible', q.length > 0);
        clearTimeout(debounce);
        debounce = setTimeout(() => doSearch(q), 220);
    });
    searchInput.addEventListener('focus', () => doSearch(searchInput.value.trim()));

    searchInput.addEventListener('keydown', function (e) {
        const items = list.querySelectorAll('.cbox-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); focusedIdx = Math.min(focusedIdx + 1, items.length - 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); focusedIdx = Math.max(focusedIdx - 1, 0); }
        else if (e.key === 'Enter' && focusedIdx >= 0) { e.preventDefault(); selectContract(lastResults[focusedIdx]); return; }
        else if (e.key === 'Escape') { dropdown.classList.remove('open'); return; }
        items.forEach((el,i) => el.classList.toggle('focused', i === focusedIdx));
        if (focusedIdx >= 0) items[focusedIdx].scrollIntoView({ block: 'nearest' });
    });

    clearBtn.addEventListener('click', () => { searchInput.value = ''; clearBtn.classList.remove('visible'); searchInput.focus(); doSearch(''); });
    document.addEventListener('click', e => { if (!document.getElementById('contractCombobox')?.contains(e.target)) dropdown.classList.remove('open'); });

    // ── Smart Import ──────────────────────────────────────────
    const importInput  = document.getElementById('importFileInput');
    const importZone   = document.getElementById('importZone');
    const importProg   = document.getElementById('importProgress');
    const importBar    = document.getElementById('importBar');
    const importResult = document.getElementById('importResult');
    const importIcon   = document.getElementById('importResultIcon');
    const importBody   = document.getElementById('importResultBody');

    if (importInput) {
        importInput.addEventListener('change', function () {
            if (this.files[0]) runImport(this.files[0]);
        });

        // Drag & drop
        importZone.addEventListener('dragover', e => { e.preventDefault(); importZone.classList.add('drag-over'); });
        importZone.addEventListener('dragleave', () => importZone.classList.remove('drag-over'));
        importZone.addEventListener('drop', e => {
            e.preventDefault();
            importZone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file && file.type === 'application/pdf') runImport(file);
        });
    }

    function runImport(file) {
        importProg.classList.add('show');
        importResult.classList.remove('show','success','warning','error');
        importBar.style.width = '0%';

        // Animate bar
        let pct = 0;
        const tick = setInterval(() => {
            pct = Math.min(pct + Math.random() * 15, 85);
            importBar.style.width = pct + '%';
        }, 200);

        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch('{{ route("ewa-bills.parse-import") }}', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                clearInterval(tick);
                importBar.style.width = '100%';
                setTimeout(() => importProg.classList.remove('show'), 500);

                if (res.data) {
                    fillForm(res.data);
                    showResult(res.filled, res.total);
                } else {
                    showError('Could not parse the PDF. Please fill the form manually.');
                }
            })
            .catch(() => {
                clearInterval(tick);
                importProg.classList.remove('show');
                showError('Upload failed. Please try again.');
            });
    }

    function fillForm(data) {
        const map = {
            'ewa_account_number'  : '[name="ewa_account_number"]',
            'billing_period'      : '[name="billing_period"]',
            'reading_date'        : '[name="reading_date"]',
            'reading_type'        : '[name="reading_type"]',
            'elec_prev_reading'   : '#elecPrev',
            'elec_curr_reading'   : '#elecCurr',
            'elec_charges'        : '#elecCharges',
            'water_prev_reading'  : '#waterPrev',
            'water_curr_reading'  : '#waterCurr',
            'water_charges'       : '#waterCharges',
            'due_date'            : '[name="due_date"]',
            'tenant_name'         : '#tenantNameInput',
            'property_name'       : '#propertyNameInput',
        };

        Object.entries(map).forEach(([key, selector]) => {
            if (data[key] == null) return;
            const el = document.querySelector(selector);
            if (!el) return;
            if (el.tagName === 'SELECT') {
                el.value = data[key];
            } else {
                el.value = data[key];
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        // Trigger computations
        computeConsumption('elecPrev','elecCurr','elecConsumption',0);
        computeConsumption('waterPrev','waterCurr','waterConsumption',3);
        computeTotal();
    }

    function showResult(filled, total) {
        importResult.classList.add('show');
        if (filled === 0) {
            importResult.classList.add('warning');
            importIcon.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
            importBody.innerHTML = '<strong>Nothing was extracted</strong>Could not find matching data in this PDF. Please fill the form manually.';
        } else if (filled >= total * 0.5) {
            importResult.classList.add('success');
            importIcon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
            importBody.innerHTML = `<strong>${filled} field${filled > 1 ? 's' : ''} auto-filled</strong>Review the values below and correct anything that looks wrong before saving.`;
        } else {
            importResult.classList.add('warning');
            importIcon.innerHTML = '<i class="fa-solid fa-circle-info"></i>';
            importBody.innerHTML = `<strong>${filled} field${filled > 1 ? 's' : ''} extracted</strong>Some fields could not be read. Please complete the missing ones manually.`;
        }
    }

    function showError(msg) {
        importResult.classList.add('show','error');
        importIcon.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
        importBody.innerHTML = `<strong>Import failed</strong>${msg}`;
    }

    // ── Form validation ───────────────────────────────────────
    document.getElementById('ewa-form').addEventListener('submit', function (e) {
        let ok = true;
        const tenantName = document.querySelector('[name="tenant_name"]');
        if (!tenantName.value.trim()) { tenantName.classList.add('is-invalid'); ok = false; }
        else { tenantName.classList.remove('is-invalid'); }

        const dueDate = document.querySelector('[name="due_date"]');
        if (!dueDate.value) { dueDate.classList.add('is-invalid'); ok = false; }
        else { dueDate.classList.remove('is-invalid'); }

        if (!ok) e.preventDefault();
    });
})();
</script>
@endpush
