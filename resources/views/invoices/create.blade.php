@extends('layouts.admin')

@section('title', $record ? 'Edit Invoice' : 'New Invoice')
@section('topbar-title', 'Invoices')

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
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
.form-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
.form-grid.cols-1 { grid-template-columns: 1fr; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.form-label .required { color: #DC2626; margin-left: 2px; }
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
.form-control.is-invalid ~ .invalid-feedback,
.is-invalid + .invalid-feedback { display: block; }
textarea.form-control { resize: vertical; min-height: 80px; }

/* ── TENANT COMBOBOX ─────────────────────────────────────── */
.contract-combobox { position: relative; }

.cbox-trigger {
    display: flex; align-items: center; gap: 0;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); transition: border-color 0.18s; overflow: hidden;
    cursor: text;
}
.cbox-trigger:focus-within { border-color: var(--accent); }
.cbox-trigger.is-invalid { border-color: #DC2626; }

.cbox-search {
    flex: 1; padding: 9px 13px; font-size: 13px;
    border: none; background: transparent; color: var(--text-primary);
    outline: none; font-family: 'Plus Jakarta Sans', sans-serif; min-width: 0;
}
.cbox-search::placeholder { color: var(--text-muted); }

.cbox-clear {
    padding: 0 12px; font-size: 14px; color: var(--text-muted);
    cursor: pointer; background: none; border: none; line-height: 1;
    display: none; align-items: center; transition: color 0.15s;
}
.cbox-clear:hover { color: #DC2626; }
.cbox-clear.visible { display: flex; }

.cbox-spinner {
    padding: 0 10px; color: var(--text-muted); font-size: 13px;
    display: none; align-items: center;
}
.cbox-spinner.visible { display: flex; }

.cbox-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: var(--card-bg); border: 1.5px solid var(--accent);
    border-radius: var(--radius-sm); box-shadow: 0 8px 24px rgba(0,0,0,0.14);
    z-index: 999; max-height: 320px; overflow-y: auto;
    display: none;
}
.cbox-dropdown.open { display: block; }

.cbox-item {
    padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--card-border);
    transition: background 0.12s;
}
.cbox-item:last-child { border-bottom: none; }
.cbox-item:hover, .cbox-item.focused { background: var(--accent-dim); }

.cbox-item-main {
    font-size: 13px; font-weight: 600; color: var(--text-primary);
    display: flex; align-items: center; gap: 6px;
}
.cbox-item-main mark { background: #FEF08A; color: #713F12; border-radius: 2px; padding: 0 1px; font-weight: 700; }
.cbox-item-sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; display: flex; gap: 12px; flex-wrap: wrap; }
.cbox-item-sub span { display: flex; align-items: center; gap: 4px; }

.cbox-empty { padding: 20px 16px; text-align: center; color: var(--text-muted); font-size: 13px; }
.cbox-hint {
    padding: 8px 14px; font-size: 11px; color: var(--text-muted);
    border-bottom: 1px solid var(--card-border); background: var(--page-bg);
    display: flex; align-items: center; justify-content: space-between;
}

.contract-selected {
    display: none; margin-top: 10px;
    border: 1.5px solid var(--accent); border-radius: var(--radius-sm);
    background: var(--accent-dim); overflow: hidden;
}
.contract-selected.show { display: block; }
.contract-selected-header { padding: 8px 14px; background: var(--accent); display: flex; align-items: center; justify-content: space-between; }
.contract-selected-header span { font-size: 12px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 6px; }
.contract-selected-change {
    font-size: 11px; color: rgba(255,255,255,0.85); cursor: pointer;
    background: none; border: none; font-family: inherit; font-weight: 600;
    display: flex; align-items: center; gap: 4px; transition: color 0.15s; padding: 0;
}
.contract-selected-change:hover { color: #fff; }
.contract-selected-body { padding: 12px 16px; display: grid; grid-template-columns: repeat(3,1fr); gap: 10px 16px; }
.cs-item span { font-size: 10px; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px; }
.cs-item strong { font-size: 13px; color: var(--text-primary); }

/* Type pills */
.type-pills { display: flex; gap: 8px; flex-wrap: wrap; }
.type-pill {
    display: flex; align-items: center; gap: 6px; padding: 7px 14px;
    border: 1.5px solid var(--input-border); border-radius: 20px;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s;
    background: var(--input-bg); color: var(--text-muted);
}
.type-pill input { display: none; }
.type-pill:hover { border-color: var(--accent); color: var(--accent); }
.type-pill.selected-rent      { border-color: #2563EB; background: #EFF6FF; color: #2563EB; }
.type-pill.selected-utilities { border-color: #EA580C; background: #FFF7ED; color: #EA580C; }
.type-pill.selected-other     { border-color: #64748B; background: #F1F5F9; color: #64748B; }

/* ── RENTAL LINES TABLE ──────────────────────────────────── */
.lines-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.lines-table th {
    background: var(--page-bg); padding: 8px 8px; text-align: left;
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted);
    border-bottom: 1.5px solid var(--card-border);
}
.lines-table td { padding: 6px 6px; border-bottom: 1px solid var(--card-border); vertical-align: top; }
.lines-table input {
    width: 100%; box-sizing: border-box; padding: 6px 8px; font-size: 12.5px;
    border: 1.5px solid var(--input-border); border-radius: 6px; background: var(--input-bg); color: var(--text-primary);
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.lines-table input:focus { border-color: var(--accent); outline: none; }
.lines-table input:disabled { background: var(--page-bg); color: var(--text-muted); cursor: not-allowed; }
.lines-table td.amount-col input { font-family: 'Outfit', sans-serif; font-weight: 700; text-align: right; }

.line-combo-wrap { position: relative; }
.line-combo-dropdown {
    position: absolute; top: calc(100% + 3px); left: 0; min-width: 320px;
    background: var(--card-bg); border: 1.5px solid var(--accent);
    border-radius: 6px; box-shadow: 0 8px 24px rgba(0,0,0,0.16);
    z-index: 999; max-height: 260px; overflow-y: auto; display: none;
}
.line-combo-dropdown.open { display: block; }
.line-combo-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid var(--card-border); }
.line-combo-item:last-child { border-bottom: none; }
.line-combo-item:hover, .line-combo-item.focused { background: var(--accent-dim); }
.line-combo-item-main { font-size: 12.5px; font-weight: 600; color: var(--text-primary); }
.line-combo-item-sub { font-size: 10.5px; color: var(--text-muted); margin-top: 2px; display: flex; gap: 10px; flex-wrap: wrap; }
.line-combo-empty { padding: 14px 12px; text-align: center; color: var(--text-muted); font-size: 12px; }
.line-remove-btn {
    border: none; background: none; color: var(--text-muted); cursor: pointer; padding: 6px; font-size: 13px;
}
.line-remove-btn:hover { color: #DC2626; }
.lines-actions { display: flex; gap: 8px; margin-top: 10px; }
.lines-total-row { background: var(--page-bg); }
.lines-total-row td { font-weight: 700; color: var(--text-primary); }

.form-actions { display: flex; gap: 10px; align-items: center; justify-content: flex-end; padding-top: 6px; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">{{ $record ? 'Edit Invoice' : 'New Invoice' }}</h1>
        <p class="page-header-sub">{{ $record ? 'Update invoice details' : 'Create a new tax invoice for a tenant, covering one or more rental lines' }}</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline">
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

@php
    $existingLines = old('lines', $record?->lines ?? [['property_name'=>'','unit'=>'','lease_agreement_no'=>'','lease_contract_id'=>'','rental_period_start'=>'','rental_period_end'=>'','amount'=>'']]);
@endphp

<form method="POST"
      action="{{ $record ? route('invoices.update', $record) : route('invoices.store') }}"
      id="invoice-form" novalidate>
    @csrf
    @if($record) @method('PUT') @endif

    {{-- TENANT --}}
    <div class="form-card">
        <div class="form-card-title">
            <i class="fa-solid fa-user" style="color:var(--accent)"></i> Tenant
        </div>

        <div class="form-group">
            <label class="form-label">Tenant <span class="required">*</span></label>

            <input type="hidden" name="tenant_id" id="tenantId"
                   value="{{ old('tenant_id', $record?->tenant_id) }}">

            @if($record)
                <div class="contract-selected show" id="tenantSelected">
                    <div class="contract-selected-header">
                        <span><i class="fa-solid fa-circle-check"></i> Tenant Selected</span>
                    </div>
                    <div class="contract-selected-body">
                        <div class="cs-item"><span>Name</span><strong>{{ $record->tenant_name }}</strong></div>
                        <div class="cs-item"><span>Code</span><strong>{{ $record->tenant_code ?: '—' }}</strong></div>
                        <div class="cs-item"><span>Address</span><strong>{{ $record->tenant_address ?: '—' }}</strong></div>
                    </div>
                </div>
            @else
                <div class="contract-combobox" id="tenantCombobox">
                    <div class="cbox-trigger" id="cboxTrigger">
                        <input type="text" id="cboxSearch" class="cbox-search"
                               placeholder="Type tenant name or code…"
                               autocomplete="off" spellcheck="false">
                        <span class="cbox-spinner" id="cboxSpinner"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                        <button type="button" class="cbox-clear" id="cboxClear" tabindex="-1">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="cbox-dropdown" id="cboxDropdown">
                        <div class="cbox-hint">
                            <span><i class="fa-solid fa-magnifying-glass" style="margin-right:4px"></i>Type to search tenants</span>
                            <span id="cboxCount"></span>
                        </div>
                        <div id="cboxList"></div>
                    </div>
                </div>

                <div class="contract-selected" id="tenantSelected">
                    <div class="contract-selected-header">
                        <span><i class="fa-solid fa-circle-check"></i> Tenant Selected</span>
                        <button type="button" class="contract-selected-change" id="cboxChangeBtn">
                            <i class="fa-solid fa-pen"></i> Change
                        </button>
                    </div>
                    <div class="contract-selected-body" id="tenantPreviewBody"></div>
                </div>

                @error('tenant_id')
                <div class="invalid-feedback" style="display:block">{{ $message }}</div>
                @enderror
            @endif
        </div>
    </div>

    {{-- INVOICE DETAILS --}}
    <div class="form-card">
        <div class="form-card-title">
            <i class="fa-solid fa-file-invoice-dollar" style="color:var(--accent)"></i> Invoice Details
        </div>

        <div class="form-grid" style="margin-bottom:18px">
            <div class="form-group" style="grid-column:span 2">
                <label class="form-label">Type <span class="required">*</span></label>
                <div class="type-pills" id="typePills">
                    @foreach(['rent' => ['Rent','fa-house','selected-rent'], 'utilities' => ['Utilities','fa-bolt','selected-utilities'], 'other' => ['Other','fa-tag','selected-other']] as $val => [$lbl,$icon,$cls])
                    <label class="type-pill {{ old('type', $record?->type ?? 'rent') === $val ? $cls : '' }}" data-type="{{ $val }}">
                        <input type="radio" name="type" value="{{ $val }}" {{ old('type', $record?->type ?? 'rent') === $val ? 'checked' : '' }} required>
                        <i class="fa-solid {{ $icon }}"></i> {{ $lbl }}
                    </label>
                    @endforeach
                </div>
                @error('type')<div style="font-size:11px;color:#DC2626;margin-top:4px">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-grid" style="margin-bottom:18px">
            <div class="form-group">
                <label class="form-label">VAT Rate (%)</label>
                <input type="number" name="vat_rate" id="vatRateInput"
                       class="form-control {{ $errors->has('vat_rate') ? 'is-invalid' : '' }}"
                       value="{{ old('vat_rate', $record?->vat_rate ?? 0) }}"
                       min="0" max="100" step="0.01" placeholder="0.00">
                <div class="invalid-feedback">{{ $errors->first('vat_rate') }}</div>
            </div>

            <div class="form-group">
                <label class="form-label">Invoice Date <span class="required">*</span></label>
                <input type="date" name="invoice_date"
                       class="form-control {{ $errors->has('invoice_date') ? 'is-invalid' : '' }}"
                       value="{{ old('invoice_date', $record?->invoice_date?->format('Y-m-d')) }}"
                       id="invoiceDate" required>
                <div class="invalid-feedback">{{ $errors->first('invoice_date') }}</div>
            </div>
        </div>

        <div class="form-grid cols-1">
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
                          placeholder="What this invoice is for…" rows="2">{{ old('description', $record?->description) }}</textarea>
                <div class="invalid-feedback">{{ $errors->first('description') }}</div>
            </div>

            <div class="form-group" style="margin-top:2px">
                <label class="form-label">Internal Notes</label>
                <textarea name="notes" class="form-control {{ $errors->has('notes') ? 'is-invalid' : '' }}"
                          placeholder="Private notes (not shown on invoice)…" rows="2">{{ old('notes', $record?->notes) }}</textarea>
                <div class="invalid-feedback">{{ $errors->first('notes') }}</div>
            </div>
        </div>

        @if($record)
        <div class="form-grid" style="margin-top:18px">
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}">
                    @foreach(['draft'=>'Draft','issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid','overdue'=>'Overdue','cancelled'=>'Cancelled'] as $v => $l)
                    <option value="{{ $v }}" {{ old('status', $record->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">{{ $errors->first('status') }}</div>
            </div>
        </div>
        @endif
    </div>

    {{-- RENTAL LINES --}}
    <div class="form-card">
        <div class="form-card-title">
            <i class="fa-solid fa-table-list" style="color:var(--accent)"></i> Rental Lines
        </div>
        @error('lines')<div style="font-size:12px;color:#DC2626;margin-bottom:10px">{{ $message }}</div>@enderror

        <table class="lines-table">
            <thead>
                <tr>
                    <th style="width:22%">Property</th>
                    <th style="width:12%">Unit No.</th>
                    <th style="width:14%">Lease No.</th>
                    <th style="width:14%">Period Start</th>
                    <th style="width:14%">Period End</th>
                    <th style="width:14%">Rent (BHD)</th>
                    <th style="width:28px"></th>
                </tr>
            </thead>
            <tbody id="linesBody">
                @foreach($existingLines as $i => $line)
                <tr class="line-row">
                    <td>
                        <input type="hidden" name="lines[{{ $i }}][lease_contract_id]" class="line-lease-id" value="{{ $line['lease_contract_id'] ?? '' }}">
                        <div class="line-combo-wrap">
                            <input type="text" name="lines[{{ $i }}][property_name]" class="line-property-input"
                                   value="{{ $line['property_name'] ?? '' }}" placeholder="Select a tenant first" autocomplete="off" required>
                            <div class="line-combo-dropdown"></div>
                        </div>
                    </td>
                    <td><input type="text" name="lines[{{ $i }}][unit]" class="line-unit-input" value="{{ $line['unit'] ?? '' }}" placeholder="Flat 22"></td>
                    <td><input type="text" name="lines[{{ $i }}][lease_agreement_no]" class="line-lease-no-input" value="{{ $line['lease_agreement_no'] ?? '' }}" placeholder="LA/001"></td>
                    <td><input type="date" name="lines[{{ $i }}][rental_period_start]" class="line-period-start" value="{{ $line['rental_period_start'] ?? '' }}"></td>
                    <td><input type="date" name="lines[{{ $i }}][rental_period_end]" class="line-period-end" value="{{ $line['rental_period_end'] ?? '' }}"></td>
                    <td class="amount-col"><input type="number" name="lines[{{ $i }}][amount]" class="line-amount" value="{{ $line['amount'] ?? '' }}" step="0.001" min="0.001" placeholder="0.000" required></td>
                    <td><button type="button" class="line-remove-btn" title="Remove line"><i class="fa-solid fa-trash-can"></i></button></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="lines-total-row">
                    <td colspan="5" style="text-align:right">Subtotal (Excl. VAT)</td>
                    <td id="subtotalDisplay" style="font-family:'Outfit',sans-serif">0.000</td>
                    <td></td>
                </tr>
                <tr class="lines-total-row">
                    <td colspan="5" style="text-align:right">VAT</td>
                    <td id="vatDisplay" style="font-family:'Outfit',sans-serif">0.000</td>
                    <td></td>
                </tr>
                <tr class="lines-total-row">
                    <td colspan="5" style="text-align:right">Total (Incl. VAT)</td>
                    <td id="totalDisplay" style="font-family:'Outfit',sans-serif;color:var(--accent)">0.000</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="lines-actions">
            <button type="button" class="btn btn-outline btn-sm" id="addLineBtn">
                <i class="fa-solid fa-plus"></i> Add Line
            </button>
            <button type="button" class="btn btn-outline btn-sm" id="loadActiveLeasesBtn" style="display:none">
                <i class="fa-solid fa-file-import"></i> Load Tenant's Active Leases
            </button>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid {{ $record ? 'fa-floppy-disk' : 'fa-plus' }}"></i>
            {{ $record ? 'Save Changes' : 'Create Invoice' }}
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    // ── Type pills ────────────────────────────────────────────
    document.querySelectorAll('.type-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.type-pill').forEach(p => p.className = 'type-pill');
            pill.classList.add('selected-' + pill.dataset.type);
            pill.querySelector('input').checked = true;
        });
    });

    const invoiceDateEl = document.getElementById('invoiceDate');
    const tenantId      = document.getElementById('tenantId');

    // ── Rental lines: totals, add/remove ───────────────────────
    const linesBody     = document.getElementById('linesBody');
    const vatRateInput   = document.getElementById('vatRateInput');
    let lineIndex = {{ count($existingLines) }};

    // ── Per-line property/unit picker, scoped to the selected tenant ──
    function setRowComboboxEnabled(row, enabled) {
        const input = row.querySelector('.line-property-input');
        if (!input) return;
        input.disabled = !enabled;
        input.placeholder = enabled ? 'Search property or unit…' : 'Select a tenant first';
    }

    function initLineCombobox(row) {
        const input           = row.querySelector('.line-property-input');
        const dropdown        = row.querySelector('.line-combo-dropdown');
        const leaseIdInput    = row.querySelector('.line-lease-id');
        const unitInput       = row.querySelector('.line-unit-input');
        const leaseNoInput    = row.querySelector('.line-lease-no-input');
        const periodStartInput = row.querySelector('.line-period-start');
        const periodEndInput   = row.querySelector('.line-period-end');
        const amountInput     = row.querySelector('.line-amount');
        if (!input) return;

        let debounce    = null;
        let results     = [];
        let focusedIdx  = -1;

        const escHtml = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const open  = () => dropdown.classList.add('open');
        const close = () => { dropdown.classList.remove('open'); focusedIdx = -1; };

        function render(items) {
            results    = items;
            focusedIdx = -1;

            if (!items.length) {
                dropdown.innerHTML = '<div class="line-combo-empty">No matching leases for this tenant</div>';
                return;
            }

            dropdown.innerHTML = '';
            items.forEach((c) => {
                const div = document.createElement('div');
                div.className = 'line-combo-item';
                const sub = [
                    c.unit ? c.unit : '',
                    c.lease_agreement_no ? c.lease_agreement_no : '',
                    c.amount ? c.amount + ' BHD/mo' : '',
                ].filter(Boolean).join(' &middot; ');
                div.innerHTML = `<div class="line-combo-item-main">${escHtml(c.property_name)}</div>${sub ? '<div class="line-combo-item-sub">' + sub + '</div>' : ''}`;
                div.addEventListener('mousedown', (e) => { e.preventDefault(); select(c); });
                dropdown.appendChild(div);
            });
        }

        function select(c) {
            input.value = c.property_name || '';
            leaseIdInput.value = c.id || '';
            if (unitInput) unitInput.value = c.unit || '';
            if (leaseNoInput) leaseNoInput.value = c.lease_agreement_no || '';
            if (amountInput && c.amount) amountInput.value = c.amount;

            const now = new Date();
            if (periodStartInput && !periodStartInput.value) {
                periodStartInput.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            }
            if (periodEndInput && !periodEndInput.value) {
                periodEndInput.value = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
            }

            // VAT follows the building on the invoice's first line.
            if (row === linesBody.querySelector('.line-row') && c.vat_rate !== undefined) {
                vatRateInput.value = c.vat_rate;
            }

            close();
            recalcTotals();
        }

        function search(q) {
            if (!tenantId.value) return;
            fetch('/lease-contracts/tenant/' + tenantId.value + '/search?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => { render(data); open(); })
                .catch(() => {});
        }

        input.addEventListener('input', function () {
            leaseIdInput.value = ''; // typing manually detaches this line from the previously selected lease
            clearTimeout(debounce);
            const q = this.value.trim();
            debounce = setTimeout(() => search(q), 200);
        });

        input.addEventListener('focus', function () {
            if (!input.disabled) search(this.value.trim());
        });

        input.addEventListener('keydown', function (e) {
            const items = dropdown.querySelectorAll('.line-combo-item');
            if (!items.length) return;

            if (e.key === 'ArrowDown') { e.preventDefault(); focusedIdx = Math.min(focusedIdx + 1, items.length - 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); focusedIdx = Math.max(focusedIdx - 1, 0); }
            else if (e.key === 'Enter' && focusedIdx >= 0) { e.preventDefault(); select(results[focusedIdx]); return; }
            else if (e.key === 'Escape') { close(); return; }

            items.forEach((el, i) => el.classList.toggle('focused', i === focusedIdx));
            if (focusedIdx >= 0) items[focusedIdx].scrollIntoView({ block: 'nearest' });
        });

        document.addEventListener('click', function (e) {
            if (!row.contains(e.target)) close();
        });
    }

    function recalcTotals() {
        let subtotal = 0;
        linesBody.querySelectorAll('.line-amount').forEach(input => {
            subtotal += parseFloat(input.value) || 0;
        });
        const vatRate = parseFloat(vatRateInput.value) || 0;
        const vat     = subtotal * (vatRate / 100);
        const total   = subtotal + vat;

        document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(3);
        document.getElementById('vatDisplay').textContent      = vat.toFixed(3);
        document.getElementById('totalDisplay').textContent    = total.toFixed(3);
    }

    function addLineRow(data) {
        data = data || {};
        const row = document.createElement('tr');
        row.className = 'line-row';
        row.innerHTML = `
            <td>
                <input type="hidden" name="lines[${lineIndex}][lease_contract_id]" class="line-lease-id" value="${data.lease_contract_id || ''}">
                <div class="line-combo-wrap">
                    <input type="text" name="lines[${lineIndex}][property_name]" class="line-property-input"
                           value="${data.property_name || ''}" placeholder="Select a tenant first" autocomplete="off" required>
                    <div class="line-combo-dropdown"></div>
                </div>
            </td>
            <td><input type="text" name="lines[${lineIndex}][unit]" class="line-unit-input" value="${data.unit || ''}" placeholder="Flat 22"></td>
            <td><input type="text" name="lines[${lineIndex}][lease_agreement_no]" class="line-lease-no-input" value="${data.lease_agreement_no || ''}" placeholder="LA/001"></td>
            <td><input type="date" name="lines[${lineIndex}][rental_period_start]" class="line-period-start" value="${data.rental_period_start || ''}"></td>
            <td><input type="date" name="lines[${lineIndex}][rental_period_end]" class="line-period-end" value="${data.rental_period_end || ''}"></td>
            <td class="amount-col"><input type="number" name="lines[${lineIndex}][amount]" class="line-amount" value="${data.amount || ''}" step="0.001" min="0.001" placeholder="0.000" required></td>
            <td><button type="button" class="line-remove-btn" title="Remove line"><i class="fa-solid fa-trash-can"></i></button></td>
        `;
        linesBody.appendChild(row);
        initLineCombobox(row);
        setRowComboboxEnabled(row, !!tenantId.value);
        lineIndex++;
        recalcTotals();
    }

    document.getElementById('addLineBtn').addEventListener('click', () => addLineRow());

    linesBody.addEventListener('click', function (e) {
        const btn = e.target.closest('.line-remove-btn');
        if (!btn) return;
        if (linesBody.querySelectorAll('.line-row').length <= 1) return; // keep at least one line
        btn.closest('.line-row').remove();
        recalcTotals();
    });

    linesBody.addEventListener('input', function (e) {
        if (e.target.classList.contains('line-amount')) recalcTotals();
    });

    vatRateInput.addEventListener('input', recalcTotals);
    recalcTotals();

    // Wire up comboboxes for the rows already rendered by the server.
    linesBody.querySelectorAll('.line-row').forEach(row => {
        initLineCombobox(row);
        setRowComboboxEnabled(row, !!tenantId.value);
    });

    // ── Tenant combobox (create mode only) ──────────────────
    const searchInput = document.getElementById('cboxSearch');
    const loadLeasesBtn = document.getElementById('loadActiveLeasesBtn');
    if (tenantId.value) loadLeasesBtn.style.display = '';

    if (!searchInput) return; // edit mode — combobox not rendered

    const dropdown    = document.getElementById('cboxDropdown');
    const list        = document.getElementById('cboxList');
    const countEl     = document.getElementById('cboxCount');
    const spinner     = document.getElementById('cboxSpinner');
    const clearBtn    = document.getElementById('cboxClear');
    const selected    = document.getElementById('tenantSelected');
    const previewBody = document.getElementById('tenantPreviewBody');
    const changeBtn   = document.getElementById('cboxChangeBtn');
    const trigger     = document.getElementById('cboxTrigger');
    const searchUrl   = '{{ route("tenants.search") }}';

    let debounceTimer = null;
    let focusedIdx    = -1;
    let lastResults   = [];

    function showDropdown() { dropdown.classList.add('open'); }
    function hideDropdown() { dropdown.classList.remove('open'); focusedIdx = -1; }

    function highlight(text, query) {
        if (!query) return escHtml(text);
        const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escHtml(text).replace(re, '<mark>$1</mark>');
    }
    function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    function renderResults(results, q) {
        list.innerHTML = '';
        lastResults    = results;
        focusedIdx     = -1;

        if (results.length === 0) {
            list.innerHTML = '<div class="cbox-empty"><i class="fa-solid fa-face-sad-tear" style="display:block;font-size:24px;margin-bottom:8px;opacity:0.35"></i>No tenants found</div>';
            countEl.textContent = '';
            return;
        }

        countEl.textContent = results.length + (results.length === 15 ? '+ results' : ' results');

        results.forEach((t, i) => {
            const div = document.createElement('div');
            div.className = 'cbox-item';
            div.dataset.idx = i;

            const sub = [
                t.tenant_code ? '<span><i class="fa-solid fa-hashtag" style="font-size:9px"></i>' + escHtml(t.tenant_code) + '</span>' : '',
                t.address     ? '<span><i class="fa-solid fa-location-dot" style="font-size:9px"></i>' + escHtml(t.address) + '</span>' : '',
            ].filter(Boolean).join('');

            div.innerHTML = `
                <div class="cbox-item-main">${highlight(t.name, q)}</div>
                ${sub ? '<div class="cbox-item-sub">' + sub + '</div>' : ''}
            `;

            div.addEventListener('mousedown', (e) => { e.preventDefault(); selectTenant(t); });
            list.appendChild(div);
        });
    }

    function selectTenant(t) {
        tenantId.value = t.id;
        searchInput.value = '';
        clearBtn.classList.remove('visible');
        hideDropdown();
        trigger.style.display = 'none';

        previewBody.innerHTML = `
            <div class="cs-item"><span>Name</span><strong>${escHtml(t.name)}</strong></div>
            <div class="cs-item"><span>Code</span><strong>${escHtml(t.tenant_code || '—')}</strong></div>
            <div class="cs-item"><span>Address</span><strong>${escHtml(t.address || '—')}</strong></div>
        `;
        selected.classList.add('show');
        trigger.classList.remove('is-invalid');
        loadLeasesBtn.style.display = '';

        linesBody.querySelectorAll('.line-row').forEach(row => setRowComboboxEnabled(row, true));
    }

    function clearSelection() {
        tenantId.value = '';
        selected.classList.remove('show');
        trigger.style.display = '';
        searchInput.value = '';
        searchInput.focus();
        clearBtn.classList.remove('visible');
        list.innerHTML = '';
        countEl.textContent = '';
        loadLeasesBtn.style.display = 'none';
        showDropdown();

        linesBody.querySelectorAll('.line-row').forEach(row => setRowComboboxEnabled(row, false));
    }

    changeBtn?.addEventListener('click', clearSelection);

    function doSearch(q) {
        spinner.classList.add('visible');
        fetch(searchUrl + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => { spinner.classList.remove('visible'); renderResults(data, q); showDropdown(); })
            .catch(() => { spinner.classList.remove('visible'); });
    }

    searchInput.addEventListener('input', function () {
        const q = this.value.trim();
        clearBtn.classList.toggle('visible', q.length > 0);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => doSearch(q), 220);
    });

    searchInput.addEventListener('focus', function () {
        if (!tenantId.value) doSearch(this.value.trim());
    });

    searchInput.addEventListener('keydown', function (e) {
        const items = list.querySelectorAll('.cbox-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') { e.preventDefault(); focusedIdx = Math.min(focusedIdx + 1, items.length - 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); focusedIdx = Math.max(focusedIdx - 1, 0); }
        else if (e.key === 'Enter' && focusedIdx >= 0) { e.preventDefault(); selectTenant(lastResults[focusedIdx]); return; }
        else if (e.key === 'Escape') { hideDropdown(); return; }

        items.forEach((el, i) => el.classList.toggle('focused', i === focusedIdx));
        if (focusedIdx >= 0) items[focusedIdx].scrollIntoView({ block: 'nearest' });
    });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        this.classList.remove('visible');
        searchInput.focus();
        doSearch('');
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('tenantCombobox')?.contains(e.target)) hideDropdown();
    });

    // ── Load tenant's active leases into the lines table ───────
    loadLeasesBtn.addEventListener('click', function () {
        if (!tenantId.value) return;
        loadLeasesBtn.disabled = true;
        fetch('/lease-contracts/tenant/' + tenantId.value + '/active')
            .then(r => r.json())
            .then(leases => {
                if (!leases.length) { alert('This tenant has no active lease contracts.'); return; }
                // Clear existing empty rows, then add one row per active lease.
                linesBody.innerHTML = '';
                lineIndex = 0;
                leases.forEach(l => addLineRow(l));
                // VAT follows the building on the first lease loaded.
                if (leases[0].vat_rate !== undefined) {
                    vatRateInput.value = leases[0].vat_rate;
                    recalcTotals();
                }
            })
            .finally(() => { loadLeasesBtn.disabled = false; });
    });

    // ── Form validation ───────────────────────────────────────
    document.getElementById('invoice-form').addEventListener('submit', function (e) {
        let ok = true;

        if (!tenantId.value) { trigger?.classList.add('is-invalid'); ok = false; }
        if (!document.querySelector('input[name="type"]:checked')) {
            document.querySelector('.type-pills').style.cssText = 'outline:1.5px solid #DC2626;border-radius:8px;padding:4px';
            ok = false;
        }
        if (!invoiceDateEl?.value) { invoiceDateEl?.classList.add('is-invalid'); ok = false; } else { invoiceDateEl?.classList.remove('is-invalid'); }

        const amounts = linesBody.querySelectorAll('.line-amount');
        let hasValidLine = false;
        amounts.forEach(input => { if (parseFloat(input.value) > 0) hasValidLine = true; });
        if (!hasValidLine) { alert('Add at least one rental line with an amount.'); ok = false; }

        if (!ok) e.preventDefault();
    });
})();
</script>
@endpush
