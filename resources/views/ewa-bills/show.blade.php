@extends('layouts.admin')

@section('title', $bill->bill_number)
@section('topbar-title', 'EWA Bills')

@push('styles')
<style>
.inv-layout { display: grid; grid-template-columns: 1fr 360px; gap: 20px; align-items: start; }
@media (max-width: 1100px) { .inv-layout { grid-template-columns: 1fr; } }

.detail-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; margin-bottom: 18px; }
.detail-card-header { padding: 18px 22px; border-bottom: 1px solid var(--card-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.detail-card-title { font-family: 'Outfit',sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
.detail-card-body  { padding: 20px 22px; }

/* EWA header */
.ewa-bill-header {
    background: linear-gradient(135deg, #0D9488 0%, #0369A1 100%);
    padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.ewa-bill-number { font-family: 'Outfit',sans-serif; font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
.ewa-bill-period { font-size: 13px; color: rgba(255,255,255,0.85); margin-top: 3px; }
.ewa-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.ewa-badge.issued         { background: rgba(255,255,255,0.2); color: #fff; }
.ewa-badge.partially_paid { background: #FFFBEB; color: #D97706; }
.ewa-badge.paid           { background: #ECFDF5; color: #059669; }
.ewa-badge.overdue        { background: #FEF2F2; color: #DC2626; }
.ewa-badge.cancelled      { background: #F8FAFC; color: #94A3B8; }
.ewa-badge.draft          { background: #F1F5F9; color: #64748B; }

/* Meta grid */
.meta-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px 18px; margin-bottom: 18px; }
.meta-item span { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px; }
.meta-item strong { font-size: 13px; color: var(--text-primary); }

/* Readings table */
.readings-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.readings-table th { background: var(--page-bg); padding: 8px 14px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; text-align: left; border-bottom: 1px solid var(--card-border); }
.readings-table td { padding: 10px 14px; font-size: 13px; border-bottom: 1px solid var(--card-border); }
.readings-table tr:last-child td { border-bottom: none; }
.readings-table .type-icon { width: 30px; height: 30px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 13px; }
.readings-table .type-icon.elec  { background: #FEF9C3; color: #713F12; }
.readings-table .type-icon.water { background: #E0F2FE; color: #0369A1; }
.num-cell { font-family: 'Outfit',sans-serif; font-weight: 700; font-size: 13px; }

/* Charges breakdown */
.charges-table { width: 100%; border-collapse: collapse; }
.charges-table td { padding: 9px 16px; font-size: 13px; border-bottom: 1px solid var(--card-border); }
.charges-table tr:last-child td { border-bottom: none; }
.charges-table .lbl { color: var(--text-secondary); }
.charges-table .amt { text-align: right; font-family: 'Outfit',sans-serif; font-weight: 700; }
.charges-table .subsidy-row .amt { color: #059669; }
.charges-table .total-row td { background: var(--page-bg); font-weight: 700; font-size: 14px; }
.charges-table .total-row .amt { font-family: 'Outfit',sans-serif; font-size: 20px; font-weight: 800; color: #0D9488; }
.charges-table .cap-divider td { padding: 0; border-bottom: 2px dashed var(--card-border); background: transparent; }
.charges-table .cap-row td { background: transparent; }
.charges-table .landlord-row td { background: #F0FDF4; }
.charges-table .landlord-row .amt { color: #059669; }
.charges-table .tenant-row td { background: #FFFBEB; font-weight: 700; }
.charges-table .tenant-row .amt { font-family: 'Outfit',sans-serif; font-size: 16px; font-weight: 800; color: #D97706; }

/* Split bar on show page */
.show-split-bar { height: 6px; border-radius: 3px; overflow: hidden; display: flex; margin: 10px 16px 4px; }
.show-split-bar-landlord { background: #059669; }
.show-split-bar-tenant   { background: #D97706; }

/* Balance summary */
.balance-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 1px; background: var(--card-border); border: 1px solid var(--card-border); border-radius: var(--radius-sm); overflow: hidden; margin-top: 16px; }
.balance-cell { background: var(--card-bg); padding: 14px 18px; text-align: center; }
.balance-cell span { font-size: 10px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 4px; }
.balance-cell strong { font-family: 'Outfit',sans-serif; font-size: 20px; font-weight: 800; color: var(--text-primary); }

/* Payment form card */
.pay-form-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; position: sticky; top: 20px; }
.pay-form-header { padding: 14px 18px; background: #ECFDF5; border-bottom: 1px solid #BBF7D0; display: flex; align-items: center; gap: 8px; }
.pay-form-header span { font-family: 'Outfit',sans-serif; font-size: 14px; font-weight: 700; color: #059669; }
.pay-form-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 12px; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.form-label .required { color: #DC2626; }
.form-control { padding: 8px 12px; font-size: 13px; border: 1.5px solid var(--input-border); border-radius: var(--radius-sm); background: var(--input-bg); color: var(--text-primary); outline: none; transition: border-color 0.18s; width: 100%; box-sizing: border-box; font-family: 'Plus Jakarta Sans',sans-serif; }
.form-control:focus { border-color: var(--accent); }
.form-control.is-invalid { border-color: #DC2626; }
.invalid-feedback { font-size: 11px; color: #DC2626; display: none; }
.form-control.is-invalid ~ .invalid-feedback { display: block; }
.amount-wrap { position: relative; }
.amount-wrap input { padding-right: 50px; font-family: 'Outfit',sans-serif; font-size: 15px; font-weight: 700; }
.amount-wrap::after { content: 'BHD'; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: var(--text-muted); pointer-events: none; }
.balance-hint { background: var(--page-bg); border-radius: var(--radius-sm); padding: 10px 14px; font-size: 12px; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; }
.balance-hint strong { font-family: 'Outfit',sans-serif; color: #DC2626; font-size: 14px; }

/* Payments list */
.payment-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--card-border); }
.payment-row:last-child { border-bottom: none; }
.payment-icon { width: 34px; height: 34px; border-radius: var(--radius-sm); flex-shrink: 0; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.payment-info { flex: 1; min-width: 0; }
.payment-num { font-size: 13px; font-weight: 700; color: var(--text-primary); font-family: 'Outfit',sans-serif; }
.payment-sub { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
.payment-amt { font-family: 'Outfit',sans-serif; font-size: 16px; font-weight: 800; color: #059669; white-space: nowrap; }
textarea.form-control { resize: none; min-height: 56px; }

/* ── PDF PREVIEW MODAL ───────────────────────────────────── */
.pdf-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 2000;
    background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
}
.pdf-modal-overlay.open { display: flex; }
.pdf-modal {
    width: 90vw; max-width: 900px; height: 90vh;
    background: #1E293B; border-radius: var(--radius);
    display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,0.5);
}
.pdf-modal-header {
    padding: 14px 20px; background: #0F172A;
    display: flex; align-items: center; gap: 12px; flex-shrink: 0;
}
.pdf-modal-title { font-family: 'Outfit',sans-serif; font-size: 14px; font-weight: 700; color: #fff; flex: 1; }
.pdf-modal-actions { display: flex; gap: 8px; }
.pdf-modal-btn {
    padding: 6px 14px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600;
    cursor: pointer; border: none; display: flex; align-items: center; gap: 6px; font-family: inherit;
    transition: opacity 0.15s;
}
.pdf-modal-btn:hover { opacity: 0.85; }
.pdf-modal-btn.download { background: #0D9488; color: #fff; }
.pdf-modal-btn.close    { background: #334155; color: #94A3B8; }
.pdf-modal-frame { flex: 1; width: 100%; border: none; background: #fff; }
.pdf-modal-loading {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    background: #1E293B; color: #64748B; flex-direction: column; gap: 12px; font-size: 13px;
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">{{ $bill->bill_number }}</h1>
        <p class="page-header-sub">{{ $bill->tenant_name }}{{ $bill->property_name ? ' — '.$bill->property_name : '' }}{{ $bill->unit ? ' / '.$bill->unit : '' }}</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('ewa-bills.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <button type="button" class="btn btn-outline" onclick="openPdfPreview()">
            <i class="fa-solid fa-eye"></i> Preview PDF
        </button>
        <a href="{{ route('ewa-bills.pdf', $bill) }}" class="btn btn-outline">
            <i class="fa-solid fa-file-arrow-down"></i> Download
        </a>
        @if($bill->status !== 'paid' && $bill->status !== 'cancelled')
        <a href="{{ route('ewa-bills.edit', $bill) }}" class="btn btn-outline"><i class="fa-solid fa-pen"></i> Edit</a>
        @endif
        <form method="POST" action="{{ route('ewa-bills.destroy', $bill) }}"
              onsubmit="return confirm('Delete {{ $bill->bill_number }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
@endif

<div class="inv-layout">
<div>

{{-- EWA Bill Header --}}
<div class="detail-card">
    <div class="ewa-bill-header">
        <div>
            <div style="font-size:11px;color:rgba(255,255,255,0.7);font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px">Electricity &amp; Water Authority — Kingdom of Bahrain</div>
            <div class="ewa-bill-number">{{ $bill->bill_number }}</div>
            <div class="ewa-bill-period">{{ $bill->billing_period }}{{ $bill->ewa_account_number ? ' &bull; Account: '.$bill->ewa_account_number : '' }}</div>
        </div>
        <div style="text-align:right">
            <div><span class="ewa-badge {{ $bill->status }}"><i class="fa-solid fa-circle" style="font-size:5px"></i> {{ $bill->status_label }}</span></div>
            <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:8px">
                Due <strong style="color:#fff">{{ $bill->due_date->format('d M Y') }}</strong>
                @if($bill->status === 'overdue')<span style="color:#FCA5A5;font-size:11px;font-weight:600"> (Overdue)</span>@endif
            </div>
            @if($bill->reading_date)
            <div style="font-size:11px;color:rgba(255,255,255,0.6);margin-top:3px">Reading: {{ $bill->reading_date->format('d M Y') }} &bull; {{ $bill->reading_type_label }}</div>
            @endif
        </div>
    </div>

    <div class="detail-card-body">
        {{-- Customer info --}}
        <div class="meta-grid" style="margin-bottom:18px">
            <div class="meta-item"><span>Tenant</span><strong>{{ $bill->tenant_name }}</strong></div>
            <div class="meta-item"><span>Property</span><strong>{{ $bill->property_name ?: '—' }}</strong></div>
            <div class="meta-item"><span>Unit</span><strong>{{ $bill->unit ?: '—' }}</strong></div>
        </div>

        {{-- Meter readings table --}}
        @if($bill->elec_prev_reading !== null || $bill->water_prev_reading !== null)
        <div style="margin-bottom:18px">
            <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px">Meter Readings</div>
            <table class="readings-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Previous</th>
                        <th>Current</th>
                        <th>Consumption</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @if($bill->elec_prev_reading !== null)
                    <tr>
                        <td><div style="display:flex;align-items:center;gap:8px"><div class="type-icon elec"><i class="fa-solid fa-bolt"></i></div>Electricity</div></td>
                        <td class="num-cell">{{ number_format($bill->elec_prev_reading, 0) }}</td>
                        <td class="num-cell">{{ number_format($bill->elec_curr_reading, 0) }}</td>
                        <td class="num-cell" style="color:#0D9488">{{ number_format($bill->elec_consumption, 0) }}</td>
                        <td style="color:var(--text-muted);font-size:12px">kWh</td>
                    </tr>
                    @endif
                    @if($bill->water_prev_reading !== null)
                    <tr>
                        <td><div style="display:flex;align-items:center;gap:8px"><div class="type-icon water"><i class="fa-solid fa-droplet"></i></div>Water</div></td>
                        <td class="num-cell">{{ number_format($bill->water_prev_reading, 3) }}</td>
                        <td class="num-cell">{{ number_format($bill->water_curr_reading, 3) }}</td>
                        <td class="num-cell" style="color:#0369A1">{{ number_format($bill->water_consumption, 3) }}</td>
                        <td style="color:var(--text-muted);font-size:12px">m³</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @endif

        {{-- Charges breakdown --}}
        <div style="margin-bottom:4px">
            <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px">Charges</div>
            <table class="charges-table">
                @if($bill->elec_charges)
                <tr><td class="lbl"><i class="fa-solid fa-bolt" style="color:#B45309;margin-right:6px"></i>Electricity Charges</td><td class="amt">{{ number_format($bill->elec_charges, 3) }} BHD</td></tr>
                @endif
                @if($bill->water_charges)
                <tr><td class="lbl"><i class="fa-solid fa-droplet" style="color:#0369A1;margin-right:6px"></i>Water Charges</td><td class="amt">{{ number_format($bill->water_charges, 3) }} BHD</td></tr>
                @endif
                <tr class="total-row"><td class="lbl">Total Bill (EWA)</td><td class="amt">{{ number_format($bill->total_amount, 3) }} BHD</td></tr>
                @if($bill->hasCap())
                <tr class="cap-divider"><td colspan="2"></td></tr>
                @php
                    $capAmt      = (float) $bill->ewa_cap;
                    $landlordAmt = $bill->landlord_portion;
                    $tenantAmt   = $bill->effective_tenant_portion;
                    $pct         = $bill->total_amount > 0 ? ($landlordAmt / (float)$bill->total_amount * 100) : 100;
                @endphp
                <tr class="cap-row">
                    <td class="lbl"><i class="fa-solid fa-shield-halved" style="color:#0D9488;margin-right:6px"></i>EWA Cap (landlord limit)</td>
                    <td class="amt" style="color:var(--text-muted)">{{ number_format($capAmt, 3) }} BHD</td>
                </tr>
                <tr><td colspan="2" style="padding:0 16px 4px">
                    <div class="show-split-bar">
                        <div class="show-split-bar-landlord" style="width:{{ number_format($pct, 1) }}%"></div>
                        <div class="show-split-bar-tenant"   style="width:{{ number_format(100 - $pct, 1) }}%"></div>
                    </div>
                </td></tr>
                <tr class="landlord-row">
                    <td class="lbl"><i class="fa-solid fa-shield-halved" style="color:#059669;margin-right:6px"></i>Landlord covers</td>
                    <td class="amt">{{ number_format($landlordAmt, 3) }} BHD</td>
                </tr>
                <tr class="tenant-row">
                    <td class="lbl"><i class="fa-solid fa-user" style="color:#D97706;margin-right:6px"></i>Tenant owes</td>
                    <td class="amt">{{ number_format($tenantAmt, 3) }} BHD</td>
                </tr>
                @endif
            </table>
        </div>

        {{-- Balance summary --}}
        @if($bill->hasCap())
        <div class="balance-row">
            <div class="balance-cell"><span>Tenant Portion</span><strong style="color:#D97706">{{ number_format($bill->effective_tenant_portion, 3) }}</strong></div>
            <div class="balance-cell"><span>Tenant Paid</span><strong style="color:#059669">{{ number_format($bill->total_paid, 3) }}</strong></div>
            <div class="balance-cell"><span>Remaining</span><strong style="{{ $bill->balance_due > 0 && $bill->status !== 'cancelled' ? 'color:#DC2626' : 'color:#059669' }}">{{ number_format($bill->balance_due, 3) }}</strong></div>
        </div>
        @else
        <div class="balance-row">
            <div class="balance-cell"><span>Bill Total</span><strong>{{ number_format($bill->total_amount, 3) }}</strong></div>
            <div class="balance-cell"><span>Total Paid</span><strong style="color:#059669">{{ number_format($bill->total_paid, 3) }}</strong></div>
            <div class="balance-cell"><span>Balance Due</span><strong style="{{ $bill->balance_due > 0 && $bill->status !== 'cancelled' ? 'color:#DC2626' : 'color:#059669' }}">{{ number_format($bill->balance_due, 3) }}</strong></div>
        </div>
        @endif

        @if($bill->notes)
        <div style="margin-top:16px;padding:12px 16px;background:var(--page-bg);border-radius:var(--radius-sm);font-size:13px;color:var(--text-secondary)">
            <div style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Notes</div>
            {{ $bill->notes }}
        </div>
        @endif

        @if($bill->remarks)
        <div style="margin-top:16px;padding:12px 16px;background:var(--page-bg);border-radius:var(--radius-sm);font-size:13px;color:var(--text-secondary)">
            <div style="font-size:10px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Remarks (printed on invoice)</div>
            {{ $bill->remarks }}
        </div>
        @endif
    </div>
</div>

{{-- Payments --}}
<div class="detail-card">
    <div class="detail-card-header">
        <div class="detail-card-title">
            <i class="fa-solid fa-money-bill-transfer" style="color:var(--accent)"></i>
            Payments
            <span style="font-size:12px;font-weight:600;color:var(--text-muted);background:var(--page-bg);padding:2px 8px;border-radius:20px">{{ $bill->payments->count() }}</span>
        </div>
    </div>
    <div class="detail-card-body" style="padding-top:6px;padding-bottom:6px">
        @forelse($bill->payments as $pmt)
        <div class="payment-row">
            <div class="payment-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="payment-info">
                <div class="payment-num">{{ $pmt->payment_number }}</div>
                <div class="payment-sub">{{ $pmt->payment_date->format('d M Y') }} &bull; {{ $pmt->method_label }}@if($pmt->reference) &bull; {{ $pmt->reference }}@endif</div>
            </div>
            <div class="payment-amt">{{ number_format($pmt->amount, 3) }}</div>
            <div style="display:flex;gap:6px" onclick="event.stopPropagation()">
                @if($bill->status !== 'cancelled')
                <form method="POST" action="{{ route('ewa-bills.payments.destroy', [$bill, $pmt]) }}"
                      onsubmit="return confirm('Remove payment?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:28px 20px;color:var(--text-muted);font-size:13px">
            <i class="fa-solid fa-receipt" style="font-size:26px;display:block;margin-bottom:8px;opacity:0.3"></i>
            No payments recorded
        </div>
        @endforelse
    </div>
</div>

</div>

{{-- RIGHT: Record Payment --}}
<div>
    @if($bill->status !== 'paid' && $bill->status !== 'cancelled')
    <div class="pay-form-card">
        <div class="pay-form-header">
            <i class="fa-solid fa-circle-plus" style="color:#059669;font-size:16px"></i>
            <span>Record Payment</span>
        </div>
        <div class="pay-form-body">
            @if($errors->any())
            <div class="alert alert-danger" style="font-size:12px;padding:10px 14px">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
            @endif

            <div class="balance-hint">
                <span>{{ $bill->hasCap() ? 'Tenant balance due' : 'Balance due' }}</span>
                <strong>{{ number_format($bill->balance_due, 3) }} BHD</strong>
            </div>

            <form method="POST" action="{{ route('ewa-bills.payments.store', $bill) }}" id="pay-form" novalidate>
                @csrf
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Amount (BHD) <span class="required">*</span></label>
                    <div class="amount-wrap">
                        <input type="number" name="amount" id="payAmount"
                               class="form-control {{ $errors->has('amount') ? 'is-invalid' : '' }}"
                               value="{{ old('amount', number_format($bill->balance_due, 3)) }}"
                               min="0.001" step="0.001" placeholder="0.000" required>
                    </div>
                    <div class="invalid-feedback">{{ $errors->first('amount') }}</div>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Payment Date <span class="required">*</span></label>
                    <input type="date" name="payment_date"
                           class="form-control {{ $errors->has('payment_date') ? 'is-invalid' : '' }}"
                           value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                    <div class="invalid-feedback">{{ $errors->first('payment_date') }}</div>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Method <span class="required">*</span></label>
                    <select name="method" class="form-control {{ $errors->has('method') ? 'is-invalid' : '' }}" required>
                        <option value="">— Select —</option>
                        @foreach(['cash'=>'Cash','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','online_card'=>'Online / Card'] as $v=>$l)
                        <option value="{{ $v }}" {{ old('method') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">{{ $errors->first('method') }}</div>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" maxlength="255" placeholder="Transaction ID…">
                </div>
                <div class="form-group pay-cheque-field" style="margin-bottom:12px;display:{{ old('method') === 'cheque' ? 'block' : 'none' }}">
                    <label class="form-label">Cheque No <span class="required">*</span></label>
                    <input type="text" name="cheque_number" class="form-control {{ $errors->has('cheque_number') ? 'is-invalid' : '' }}"
                           value="{{ old('cheque_number') }}" maxlength="50" placeholder="Cheque number">
                    <div class="invalid-feedback">{{ $errors->first('cheque_number') }}</div>
                </div>
                <div class="form-group pay-cheque-field" style="margin-bottom:12px;display:{{ old('method') === 'cheque' ? 'block' : 'none' }}">
                    <label class="form-label">Cheque Date <span class="required">*</span></label>
                    <input type="date" name="cheque_date" class="form-control {{ $errors->has('cheque_date') ? 'is-invalid' : '' }}"
                           value="{{ old('cheque_date') }}">
                    <div class="invalid-feedback">{{ $errors->first('cheque_date') }}</div>
                </div>
                <div class="form-group" style="margin-bottom:16px">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" placeholder="Optional…">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">
                    <i class="fa-solid fa-circle-check"></i> Record Payment
                </button>
            </form>
        </div>
    </div>
    @else
    <div class="detail-card" style="padding:24px;text-align:center;color:var(--text-muted)">
        @if($bill->status === 'paid')
        <i class="fa-solid fa-circle-check" style="font-size:32px;color:#059669;display:block;margin-bottom:10px"></i>
        <div style="font-weight:700;color:#059669;font-size:14px">Fully Paid</div>
        @else
        <i class="fa-solid fa-ban" style="font-size:32px;color:#94A3B8;display:block;margin-bottom:10px"></i>
        <div style="font-weight:700;font-size:14px">Cancelled</div>
        @endif
    </div>
    @endif
</div>
</div>

@endsection

{{-- PDF PREVIEW MODAL --}}
<div class="pdf-modal-overlay" id="pdfModalOverlay">
    <div class="pdf-modal">
        <div class="pdf-modal-header">
            <div class="pdf-modal-title">
                <i class="fa-solid fa-file-invoice" style="color:#0D9488;margin-right:6px"></i>
                {{ $bill->bill_number }} &mdash; {{ $bill->billing_period }}
            </div>
            <div class="pdf-modal-actions">
                <a href="{{ route('ewa-bills.pdf', $bill) }}" class="pdf-modal-btn download">
                    <i class="fa-solid fa-file-arrow-down"></i> Download
                </a>
                <button type="button" class="pdf-modal-btn close" onclick="closePdfPreview()">
                    <i class="fa-solid fa-xmark"></i> Close
                </button>
            </div>
        </div>
        <iframe id="pdfFrame" class="pdf-modal-frame" src="" title="EWA Bill Preview"></iframe>
    </div>
</div>

@push('scripts')
<script>
const previewUrl = '{{ route("ewa-bills.pdf.preview", $bill) }}';

function openPdfPreview() {
    const frame   = document.getElementById('pdfFrame');
    const overlay = document.getElementById('pdfModalOverlay');
    if (!frame.src || frame.src === window.location.href) {
        frame.src = previewUrl;
    }
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePdfPreview() {
    document.getElementById('pdfModalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('pdfModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePdfPreview();
});

// Close on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePdfPreview(); });

document.getElementById('payAmount')?.addEventListener('blur', function() {
    if (this.value) this.value = parseFloat(this.value).toFixed(3);
});
(function () {
    const form = document.getElementById('pay-form');
    if (!form) return;
    const method = form.querySelector('[name="method"]');
    const chequeFields = form.querySelectorAll('.pay-cheque-field');
    const chequeNumber = form.querySelector('[name="cheque_number"]');
    const chequeDate = form.querySelector('[name="cheque_date"]');

    function syncChequeFields() {
        const isCheque = method.value === 'cheque';
        chequeFields.forEach(el => { el.style.display = isCheque ? 'block' : 'none'; });
        if (chequeNumber) chequeNumber.required = isCheque;
        if (chequeDate) chequeDate.required = isCheque;
    }
    method?.addEventListener('change', syncChequeFields);
    syncChequeFields();

    form.addEventListener('submit', function(e) {
        let ok = true;
        const amount = document.getElementById('payAmount');
        if (!amount?.value || parseFloat(amount.value) < 0.001) { amount?.classList.add('is-invalid'); ok = false; }
        else { amount?.classList.remove('is-invalid'); }
        if (!method?.value) { method?.classList.add('is-invalid'); ok = false; }
        else { method?.classList.remove('is-invalid'); }
        if (method.value === 'cheque') {
            if (!chequeNumber?.value) { chequeNumber?.classList.add('is-invalid'); ok = false; }
            else { chequeNumber?.classList.remove('is-invalid'); }
            if (!chequeDate?.value) { chequeDate?.classList.add('is-invalid'); ok = false; }
            else { chequeDate?.classList.remove('is-invalid'); }
        }
        if (!ok) e.preventDefault();
    });
})();
</script>
@endpush
