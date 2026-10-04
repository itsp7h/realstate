@extends('layouts.admin')

@section('title', $invoice->invoice_number)
@section('topbar-title', 'Invoices')

@section('page-title')
    {{ $invoice->invoice_number }}
@endsection
@section('page-subtitle')
    {{ $invoice->tenant_name }} &mdash; {{ $invoice->property_name }}{{ $invoice->unit ? ' / '.$invoice->unit : '' }}
@endsection
@section('page-back')
    <a href="{{ route('invoices.index') }}" class="btn btn-outline" aria-label="Back to invoices">
        <i class="fa-solid fa-arrow-left"></i><span class="pagehead-back-label"> Back</span>
    </a>
@endsection

{{-- One set of controls for both sizes. Desktop is the row it always was:
     .inv-act-primary is display:contents, so Preview and Download sit in the
     header flex row exactly as before. Under 600px the shared app bar (Back ·
     title · ⋯) takes over and this row becomes the two primary buttons
     beneath it, with Edit and Delete moved into #invActionSheet. --}}
@section('page-actions')
    <div class="inv-act-primary">
        <button type="button" class="btn btn-outline inv-act-preview"
                onclick="openInvPdf('{{ route('invoices.pdf.preview', $invoice) }}', '{{ $invoice->invoice_number }}', '{{ route('invoices.pdf', $invoice) }}')">
            <i class="fa-solid fa-file-pdf"></i> Preview PDF
        </button>
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline inv-act-download" download>
            <i class="fa-solid fa-download"></i> Download
        </a>
    </div>
    @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline inv-act-sheeted">
        <i class="fa-solid fa-pen"></i> Edit
    </a>
    @endif
    {{-- Stays a real form on every width; on the phone it is hidden and the
         sheet's Delete row submits it by id, so the CSRF token, the DELETE
         method and the confirm all keep working untouched. --}}
    <form method="POST" id="invDeleteForm" class="inv-act-sheeted" action="{{ route('invoices.destroy', $invoice) }}"
          onsubmit="return confirm('Delete invoice {{ $invoice->invoice_number }}? This cannot be undone.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">
            <i class="fa-solid fa-trash"></i> Delete
        </button>
    </form>
@endsection

@section('page-overflow')
    <button type="button" class="btn btn-outline btn-icon" id="invMoreBtn"
            aria-label="More invoice actions" aria-haspopup="dialog" aria-expanded="false" aria-controls="invActionSheet">
        <i class="fa-solid fa-ellipsis"></i>
    </button>
@endsection

@push('styles')
<style>
.inv-number {
    font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800;
    color: var(--accent); letter-spacing: -0.5px;
}
.inv-meta { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px 24px; margin-top: var(--sp-5); }
.inv-meta-item span { font-size: var(--fs-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 3px; }
.inv-meta-item strong { font-size: var(--fs-base); color: var(--text-primary); }

.amount-cell.balance strong { color: {{ $invoice->balance_due > 0 && $invoice->status !== 'cancelled' ? 'var(--tone-danger-fg)' : 'var(--tone-success-fg)' }}; }


.notes-block {
    padding: 14px 18px; background: var(--page-bg);
    border-radius: var(--radius-sm); font-size: var(--fs-base); color: var(--text-primary);
    white-space: pre-wrap; word-break: break-word; line-height: 1.6;
}

/* Credit / Debit notes */
.note-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--card-border); }
.note-row:last-child { border-bottom: none; }
.note-icon { width: 34px; height: 34px; border-radius: var(--radius-sm); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: var(--fs-base); }
.note-icon.credit { background: var(--tone-success-bg); color: var(--tone-success-fg); }
.note-icon.debit  { background: var(--tone-warning-bg); color: var(--tone-warning-fg); }
.note-info { flex: 1; min-width: 0; }
.note-num { font-size: var(--fs-base); font-weight: 700; color: var(--text-primary); font-family: 'Outfit',sans-serif; }
.note-sub { font-size: var(--fs-xs); color: var(--text-muted); margin-top: 1px; }
.note-amt { font-family: 'Outfit',sans-serif; font-size: var(--fs-md); font-weight: 800; white-space: nowrap; }
.note-amt.credit { color: var(--tone-success-fg); }
.note-amt.debit  { color: var(--tone-warning-fg); }
.note-mini-stat { font-size: var(--fs-xs); color: var(--text-muted); }
.note-mini-stat strong { font-family: 'Outfit',sans-serif; font-weight: 700; }

.note-form-card { margin-top: var(--sp-1); display: none; }
.note-form-card.open { display: block; }
.note-form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; align-items: end; }
@media (max-width: 900px) { .note-form-grid { grid-template-columns: 1fr; } }
.note-form-grid .form-group { display: flex; flex-direction: column; gap: 5px; grid-column: span 1; }
.note-form-grid .reason-group { grid-column: 1 / -1; }
.note-form-label { font-size: var(--fs-xs); font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
.note-form-control {
    padding: 8px 12px; font-size: var(--fs-base); border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none; width: 100%; box-sizing: border-box;
}
.note-form-control:focus { border-color: var(--accent); }
.note-form-control.is-invalid { border-color: var(--tone-danger-border); }
.note-invalid-feedback { font-size: var(--fs-xs); color: var(--tone-danger-fg); margin-top: 3px; }

/* Payments */
.payment-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--card-border); }
.payment-row:last-child { border-bottom: none; }
.payment-icon { width: 34px; height: 34px; border-radius: var(--radius-sm); flex-shrink: 0; background: var(--tone-success-bg); color: var(--tone-success-fg); display: flex; align-items: center; justify-content: center; font-size: var(--fs-base); }
.payment-info { flex: 1; min-width: 0; }
.payment-num { font-size: var(--fs-base); font-weight: 700; color: var(--text-primary); font-family: 'Outfit',sans-serif; }
.payment-sub { font-size: var(--fs-xs); color: var(--text-muted); margin-top: 1px; }
.payment-amt { font-family: 'Outfit',sans-serif; font-size: var(--fs-md); font-weight: 800; color: var(--tone-success-fg); white-space: nowrap; }
.pay-amount-wrap { position: relative; }
.pay-amount-wrap input { padding-right: 46px; }
.pay-amount-wrap::after { content: 'BHD'; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: var(--fs-xs); font-weight: 700; color: var(--text-muted); pointer-events: none; }

/* The wrapper around Preview/Download adds no box on desktop, so the header
   row is the flex row it always was. */
.inv-act-primary { display: contents; }
.inv-datestamp { margin-left: auto; text-align: right; font-size: var(--fs-sm); color: var(--text-muted); }
.inv-datestamp strong { color: var(--text-primary); }
.inv-notes-head { gap: var(--sp-4); }
.payment-actions, .note-actions { display: flex; gap: 6px; }

/* ── Phone ───────────────────────────────────────────────────────────────
     Everything on this page that reads as a desktop row — three meta
     columns, a record and its three buttons on one line, a right-aligned
     date stamp — gets a line of its own instead of a squeezed share. */
@media (max-width: 768px) {
    .inv-number { font-size: 24px; }
    .inv-datestamp { margin-left: 0; text-align: left; width: 100%; }

    /* Label / value list rather than three columns 90px wide. */
    .inv-meta { grid-template-columns: 1fr; gap: 0; margin-top: var(--sp-4); }
    .inv-meta-item {
        display: flex; align-items: baseline; justify-content: space-between;
        gap: var(--sp-4); padding: 10px 0; border-bottom: 1px solid var(--row-border);
    }
    .inv-meta-item:last-child { border-bottom: none; }
    .inv-meta-item span { margin-bottom: 0; }
    .inv-meta-item strong { text-align: right; }

    /* The record on the first line, its actions on a full-width second one at
       touch size — three 32px buttons beside a wrapping payment number is the
       worst of both. */
    .payment-row, .note-row { flex-wrap: wrap; row-gap: 10px; padding: 14px 0; }
    /* The cluster carries the touch height and its buttons stretch into it, so
       the row's own layout never restyles .btn itself. */
    .payment-actions, .note-actions {
        flex: 1 0 100%;
        gap: var(--sp-2);
        align-items: stretch;
        min-height: var(--h-control-lg);
    }
    .payment-actions form, .note-actions form { display: contents; }
    .payment-actions .btn, .note-actions .btn { flex: 1; }

    /* Both totals share a line; the action takes its own. */
    .inv-notes-head { flex-wrap: wrap; gap: var(--sp-3) var(--sp-4); }
    .inv-notes-head > .btn { flex: 1 0 100%; }
}

/* ── Phone action row (≤600px) ───────────────────────────────────────────
     app-core's §7.3b app bar supplies Back · title · ⋯; what belongs to this
     page is the pair beneath it — Preview and Download, half the row each —
     and moving Edit / Delete into the sheet behind the ⋯. */
@media (max-width: 600px) {
    .inv-act-primary {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--sp-2);
        width: 100%;
    }
    /* Scoped through the header so it outweighs app-core's own
       `.shell-pagehead-actions .btn` sizing, which is the more specific of
       the two and would otherwise hold these at the 44px minimum. */
    .shell-pagehead-actions .inv-act-preview,
    .shell-pagehead-actions .inv-act-download {
        min-height: 48px;
        padding: 0 var(--sp-2);
        border-radius: var(--radius-lg);
        font-weight: 700;
        gap: var(--sp-2);
    }
    /* Filled against the outlined Download so the pair has an order to it.
       Navy, not the app's gold: gold means create/confirm everywhere else and
       this only opens a viewer. */
    .shell-pagehead-actions .inv-act-preview,
    .shell-pagehead-actions .inv-act-preview:hover,
    .shell-pagehead-actions .inv-act-preview:active {
        background: var(--text-primary);
        border-color: var(--text-primary);
        color: var(--ink-on-fill);
    }
    /* Dark mode inverts --text-primary to near-white, which would put white
       text on a white fill; the sidebar's active navy is the equivalent lift
       against the dark page. */
    [data-theme="dark"] .shell-pagehead-actions .inv-act-preview,
    [data-theme="dark"] .shell-pagehead-actions .inv-act-preview:hover,
    [data-theme="dark"] .shell-pagehead-actions .inv-act-preview:active {
        background: var(--sidebar-active);
        border-color: var(--sidebar-border);
        color: var(--ink-on-fill);
    }

    /* Edit and Delete are in #invActionSheet at this width. Scoped through
       the header so it outweighs app-core's `> form { display: contents }`,
       which is the more specific of the two on the delete form. */
    .shell-pagehead-actions > .inv-act-sheeted { display: none; }
    /* With those two gone the row holds only the pair, so it stops being a
       wrap grid and lets .inv-act-primary own the width. */
    .shell-pagehead-actions { display: block; }
}
</style>
@endpush

@section('content')


<div class="card">
    <div class="card-header">
        <div>
            <div class="inv-number">{{ $invoice->invoice_number }}</div>
            <div style="margin-top:4px;display:flex;gap:8px;align-items:center">
                <span class="status-badge {{ $invoice->status }}">
                    <i class="fa-solid fa-circle" style="font-size:5px"></i>
                    {{ $invoice->status_label }}
                </span>
                <span class="status-badge {{ $invoice->type }}">{{ $invoice->type_label }}</span>
            </div>
        </div>
        <div class="inv-datestamp">
            <div>Invoice Date <strong>{{ $invoice->invoice_date->format('d M Y') }}</strong></div>
            @if($invoice->status === 'overdue')
            <div style="margin-top:4px"><span style="font-size:11px;color:var(--tone-danger-fg);font-weight:600">Overdue</span></div>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="inv-meta">
            <div class="inv-meta-item">
                <span>Tenant</span>
                <strong>
                    {{-- The payer's name links through to the tenant record only
                         for a role that can open one. An Accountant reads this
                         invoice but is refused the portfolio, so it gets the
                         name as plain text rather than a link into a 403. --}}
                    @if($invoice->tenant && auth()->user()?->canAccessPortfolio())
                        <a href="{{ route('tenants.show', $invoice->tenant) }}" style="color:var(--text-primary);text-decoration:none">{{ $invoice->tenant_name }}</a>
                    @else
                        {{ $invoice->tenant_name }}
                    @endif
                </strong>
            </div>
            <div class="inv-meta-item"><span>Tenant Code</span><strong>{{ $invoice->tenant_code ?: '—' }}</strong></div>
            <div class="inv-meta-item"><span>Rental Lines</span><strong>{{ $invoice->line_count }}</strong></div>
        </div>

        <div class="figure-split" style="--figure-cols:4">
            <div class="figure-split-cell">
                <span>Subtotal (Excl. VAT)</span>
                <strong>{{ number_format($invoice->amount, 3) }}</strong>
            </div>
            <div class="figure-split-cell">
                <span>VAT ({{ number_format($invoice->vat_rate, 2) }}%)</span>
                <strong>{{ number_format($invoice->vat_amount, 3) }}</strong>
            </div>
            <div class="figure-split-cell">
                <span>Total (Incl. VAT)</span>
                <strong>{{ number_format($invoice->total_incl_vat, 3) }}</strong>
            </div>
            <div class="figure-split-cell {{ $invoice->balance_due > 0 && $invoice->status !== 'cancelled' ? 'is-danger' : 'is-success' }}">
                <span>Balance Due</span>
                <strong>{{ number_format($invoice->balance_due, 3) }}</strong>
            </div>
        </div>

        @if(!empty($invoice->lines))
        <div style="margin-top:20px">
            <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px">Rental Lines</div>
            <div class="table-wrap">
                <table class="is-compact">
                    <thead>
                        <tr style="background:var(--page-bg)">
                            <th style="text-align:left;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Property</th>
                            <th style="text-align:left;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Unit</th>
                            <th style="text-align:left;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Lease No.</th>
                            <th style="text-align:left;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Period</th>
                            <th style="text-align:right;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Rent (BHD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->lines as $line)
                        <tr style="border-bottom:1px solid var(--card-border)">
                            <td data-label="Property" style="padding:8px 10px">{{ $line['property_name'] ?? '—' }}</td>
                            <td data-label="Unit" style="padding:8px 10px">{{ !empty($line['unit'] ?? null) ? $line['unit'] : '—' }}</td>
                            <td data-label="Lease No." style="padding:8px 10px">{{ !empty($line['lease_agreement_no'] ?? null) ? $line['lease_agreement_no'] : '—' }}</td>
                            <td data-label="Period" style="padding:8px 10px">
                                @if(!empty($line['rental_period_start']))
                                    {{ \Illuminate\Support\Carbon::parse($line['rental_period_start'])->format('d M Y') }} &rarr; {{ !empty($line['rental_period_end']) ? \Illuminate\Support\Carbon::parse($line['rental_period_end'])->format('d M Y') : '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Rent (BHD)" style="padding:8px 10px;text-align:right;font-family:'Outfit',sans-serif;font-weight:700">{{ number_format($line['amount'] ?? 0, 3) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($invoice->description)
        <div style="margin-top:18px">
            <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px">Description</div>
            <div class="notes-block">{{ $invoice->description }}</div>
        </div>
        @endif

        @if($invoice->notes)
        <div style="margin-top:14px">
            <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px">Internal Notes</div>
            <div class="notes-block" style="border-left:3px solid var(--accent-dim);padding-left:14px">{{ $invoice->notes }}</div>
        </div>
        @endif

        @if($invoice->remarks)
        <div style="margin-top:14px">
            <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px">Remarks <span style="text-transform:none;font-weight:400">(printed on invoice)</span></div>
            <div class="notes-block">{{ $invoice->remarks }}</div>
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-money-bill-transfer" style="color:var(--accent)"></i>
            Payments
            <span style="font-size:12px;font-weight:600;color:var(--text-muted);background:var(--page-bg);padding:2px 8px;border-radius:20px">{{ $invoice->payments->count() }}</span>
        </div>
        @if($invoice->balance_due > 0.001 && $invoice->status !== 'cancelled')
        <div class="card-header-actions">
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('payFormCard').classList.toggle('open')">
                <i class="fa-solid fa-plus"></i> Record Payment
            </button>
        </div>
        @endif
    </div>
    <div class="card-body" style="padding-top:6px;padding-bottom:6px">
        @forelse($invoice->payments as $pmt)
        <div class="payment-row">
            <div class="payment-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="payment-info">
                <div class="payment-num">{{ $pmt->payment_number }}</div>
                <div class="payment-sub">{{ $pmt->payment_date->format('d M Y') }} &bull; {{ $pmt->method_label }}@if($pmt->reference) &bull; {{ $pmt->reference }}@endif @if($pmt->ewaBill) &bull; also covers {{ $pmt->ewaBill->bill_number }}@endif</div>
            </div>
            <div class="payment-amt">{{ number_format($pmt->amount, 3) }}</div>
            <div class="payment-actions" onclick="event.stopPropagation()">
                <button type="button" class="btn btn-outline btn-sm" title="Preview Receipt"
                        onclick="openInvPdf('{{ route('invoices.payments.receipt.preview', [$invoice, $pmt]) }}', '{{ $pmt->payment_number }}', '{{ route('invoices.payments.receipt', [$invoice, $pmt]) }}')">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <a href="{{ route('invoices.payments.receipt', [$invoice, $pmt]) }}" class="btn btn-outline btn-sm" title="Download Receipt" target="_blank">
                    <i class="fa-solid fa-file-arrow-down"></i>
                </a>
                @if($invoice->status !== 'cancelled')
                <form method="POST" action="{{ route('invoices.payments.destroy', [$invoice, $pmt]) }}"
                      onsubmit="return confirm('Remove payment {{ $pmt->payment_number }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:28px 20px;color:var(--text-muted);font-size:13px">
            <i class="fa-solid fa-receipt" style="font-size:26px;display:block;margin-bottom:8px;opacity:0.3"></i>
            No payments recorded for this invoice
        </div>
        @endforelse

        @if($invoice->balance_due > 0.001 && $invoice->status !== 'cancelled')
        <div class="card is-nested is-compact note-form-card {{ $errors->any() ? 'open' : '' }}" id="payFormCard">
            <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" novalidate>
                @csrf
                <div class="note-form-grid">
                    <div class="form-group">
                        <label class="note-form-label">Amount (BHD) <span style="color:var(--tone-danger-fg)">*</span></label>
                        <div class="pay-amount-wrap">
                            <input type="number" name="amount" class="note-form-control {{ $errors->has('amount') ? 'is-invalid' : '' }}"
                                   value="{{ old('amount', number_format($invoice->balance_due, 3)) }}" min="0.001" step="0.001" placeholder="0.000" required>
                        </div>
                        <div class="note-invalid-feedback">{{ $errors->first('amount') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="note-form-label">Payment Date <span style="color:var(--tone-danger-fg)">*</span></label>
                        <input type="date" name="payment_date" class="note-form-control {{ $errors->has('payment_date') ? 'is-invalid' : '' }}"
                               value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        <div class="note-invalid-feedback">{{ $errors->first('payment_date') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="note-form-label">Method <span style="color:var(--tone-danger-fg)">*</span></label>
                        <select name="method" class="note-form-control {{ $errors->has('method') ? 'is-invalid' : '' }}" required>
                            <option value="">— Select —</option>
                            @foreach(['cash'=>'Cash','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','online_card'=>'Online / Card'] as $v => $l)
                            <option value="{{ $v }}" {{ old('method') === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                        <div class="note-invalid-feedback">{{ $errors->first('method') }}</div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width:100%">
                            <i class="fa-solid fa-circle-check"></i> Record Payment
                        </button>
                    </div>
                    <div class="form-group reason-group">
                        <label class="note-form-label">Reference</label>
                        <input type="text" name="reference" class="note-form-control" value="{{ old('reference') }}" maxlength="255" placeholder="Transaction ID…">
                    </div>
                    <div class="form-group reason-group pay-cheque-field" style="display:{{ old('method') === 'cheque' ? 'block' : 'none' }}">
                        <label class="note-form-label">Cheque No <span style="color:var(--tone-danger-fg)">*</span></label>
                        <input type="text" name="cheque_number" class="note-form-control {{ $errors->has('cheque_number') ? 'is-invalid' : '' }}"
                               value="{{ old('cheque_number') }}" maxlength="50" placeholder="Cheque number">
                        <div class="note-invalid-feedback">{{ $errors->first('cheque_number') }}</div>
                    </div>
                    <div class="form-group reason-group pay-cheque-field" style="display:{{ old('method') === 'cheque' ? 'block' : 'none' }}">
                        <label class="note-form-label">Cheque Date <span style="color:var(--tone-danger-fg)">*</span></label>
                        <input type="date" name="cheque_date" class="note-form-control {{ $errors->has('cheque_date') ? 'is-invalid' : '' }}"
                               value="{{ old('cheque_date') }}">
                        <div class="note-invalid-feedback">{{ $errors->first('cheque_date') }}</div>
                    </div>
                    @php $tenantEwaBills = $invoice->tenant?->ewaBills()->orderByDesc('reading_date')->get() ?? collect(); @endphp
                    @if($tenantEwaBills->isNotEmpty())
                    <div class="form-group reason-group">
                        <label class="note-form-label">Also covers EWA bill (optional)</label>
                        <select name="ewa_bill_id" class="note-form-control {{ $errors->has('ewa_bill_id') ? 'is-invalid' : '' }}">
                            <option value="">— None —</option>
                            @foreach($tenantEwaBills as $bill)
                            <option value="{{ $bill->id }}" {{ old('ewa_bill_id') == $bill->id ? 'selected' : '' }}>
                                {{ $bill->bill_number }} — {{ $bill->billing_period ?: $bill->reading_date?->format('M Y') }}
                            </option>
                            @endforeach
                        </select>
                        <div class="note-invalid-feedback">{{ $errors->first('ewa_bill_id') }}</div>
                    </div>
                    @endif
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-file-invoice-dollar" style="color:var(--accent)"></i>
            Credit &amp; Debit Notes
            <span style="font-size:12px;font-weight:600;color:var(--text-muted);background:var(--page-bg);padding:2px 8px;border-radius:20px">{{ $invoice->invoiceNotes->count() }}</span>
        </div>
        <div class="card-header-actions inv-notes-head">
            <div class="note-mini-stat">Total Credited <strong style="color:var(--tone-success-fg)">{{ number_format($invoice->total_credit_notes, 3) }}</strong></div>
            <div class="note-mini-stat">Total Debited <strong style="color:var(--tone-warning-fg)">{{ number_format($invoice->total_debit_notes, 3) }}</strong></div>
            @if($invoice->status !== 'cancelled')
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('noteFormCard').classList.toggle('open')">
                <i class="fa-solid fa-plus"></i> Issue Note
            </button>
            @endif
        </div>
    </div>
    <div class="card-body" style="padding-top:6px;padding-bottom:6px">
        @forelse($invoice->invoiceNotes as $note)
        <div class="note-row">
            <div class="note-icon {{ $note->type }}"><i class="fa-solid {{ $note->type === 'credit' ? 'fa-minus' : 'fa-plus' }}"></i></div>
            <div class="note-info">
                <div class="note-num">{{ $note->note_number }} &mdash; {{ $note->type_label }}</div>
                <div class="note-sub">{{ $note->note_date->format('d M Y') }} &bull; {{ $note->reason }}</div>
            </div>
            <div class="note-amt {{ $note->type }}">{{ $note->type === 'credit' ? '−' : '+' }}{{ number_format($note->amount, 3) }}</div>
            @if($invoice->status !== 'cancelled')
            <div class="note-actions" onclick="event.stopPropagation()">
                <form method="POST" action="{{ route('invoices.notes.destroy', [$invoice, $note]) }}"
                      onsubmit="return confirm('Remove {{ $note->type_label }} {{ $note->note_number }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
            @endif
        </div>
        @empty
        <div style="text-align:center;padding:28px 20px;color:var(--text-muted);font-size:13px">
            <i class="fa-solid fa-file-invoice-dollar" style="font-size:26px;display:block;margin-bottom:8px;opacity:0.3"></i>
            No credit or debit notes issued for this invoice
        </div>
        @endforelse

        @if($invoice->status !== 'cancelled')
        <div class="card is-nested is-compact note-form-card {{ $errors->any() ? 'open' : '' }}" id="noteFormCard">
            <form method="POST" action="{{ route('invoices.notes.store', $invoice) }}" novalidate>
                @csrf
                <div class="note-form-grid">
                    <div class="form-group">
                        <label class="note-form-label">Type <span style="color:var(--tone-danger-fg)">*</span></label>
                        <select name="type" class="note-form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" required>
                            <option value="">— Select —</option>
                            <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Credit Note (reduces balance)</option>
                            <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit Note (increases balance)</option>
                        </select>
                        <div class="note-invalid-feedback">{{ $errors->first('type') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="note-form-label">Amount (BHD) <span style="color:var(--tone-danger-fg)">*</span></label>
                        <input type="number" name="amount" class="note-form-control {{ $errors->has('amount') ? 'is-invalid' : '' }}"
                               value="{{ old('amount') }}" min="0.001" step="0.001" placeholder="0.000" required>
                        <div class="note-invalid-feedback">{{ $errors->first('amount') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="note-form-label">Date <span style="color:var(--tone-danger-fg)">*</span></label>
                        <input type="date" name="note_date" class="note-form-control {{ $errors->has('note_date') ? 'is-invalid' : '' }}"
                               value="{{ old('note_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                        <div class="note-invalid-feedback">{{ $errors->first('note_date') }}</div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width:100%">
                            <i class="fa-solid fa-circle-check"></i> Issue Note
                        </button>
                    </div>
                    <div class="form-group reason-group">
                        <label class="note-form-label">Reason <span style="color:var(--tone-danger-fg)">*</span></label>
                        <input type="text" name="reason" class="note-form-control {{ $errors->has('reason') ? 'is-invalid' : '' }}"
                               value="{{ old('reason') }}" maxlength="500" placeholder="Why is this being issued?" required>
                        <div class="note-invalid-feedback">{{ $errors->first('reason') }}</div>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

{{-- PHONE ACTION SHEET — Edit / Delete, behind the header's ⋯ button.
     Built from the shared .modal-overlay + .more-sheet-item system, which
     app-mobile.css already turns into a bottom sheet with a drag handle, a
     scrim, and safe-area padding — the same object as the More sheet in the
     tab bar, so this is not a second sheet implementation. --}}
<div class="modal-overlay inv-action-sheet" id="invActionSheet" role="dialog" aria-modal="true"
     aria-label="Invoice actions">
    <div class="modal-box">
        @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
        <a href="{{ route('invoices.edit', $invoice) }}" class="more-sheet-item">
            <div class="more-sheet-icon" style="background:var(--tone-accent-bg)">
                <i class="fa-solid fa-pen" style="color:var(--tone-accent-fg)"></i>
            </div>
            <div>
                <div class="more-sheet-label">Edit invoice</div>
                <div class="more-sheet-desc">Change lines, dates or amounts</div>
            </div>
        </a>
        @endif
        <button type="submit" form="invDeleteForm" class="more-sheet-item danger">
            <div class="more-sheet-icon" style="background:var(--tone-danger-bg)">
                <i class="fa-solid fa-trash" style="color:var(--tone-danger-fg)"></i>
            </div>
            <div>
                <div class="more-sheet-label">Delete invoice</div>
                <div class="more-sheet-desc">{{ $invoice->invoice_number }} — this cannot be undone</div>
            </div>
        </button>
    </div>
</div>

{{-- PDF PREVIEW MODAL --}}
<div class="pdf-viewer-overlay" id="invPdfModal" onclick="closeInvPdf(event)">
    <div class="pdf-viewer" onclick="event.stopPropagation()">
        <div class="pdf-viewer-header">
            <i class="fa-solid fa-file-pdf" style="color:var(--accent);font-size:16px"></i>
            <span id="invPdfTitle">{{ $invoice->invoice_number }}</span>
            <a id="invPdfDownloadLink" href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline btn-sm" download>
                <i class="fa-solid fa-download"></i> Download
            </a>
            <button type="button" class="btn btn-outline btn-sm" onclick="closeInvPdfBtn()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <iframe id="invPdfFrame" class="pdf-viewer-frame" src="about:blank"></iframe>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openInvPdf(previewUrl, title, downloadUrl) {
    document.getElementById('invPdfTitle').textContent = title;
    document.getElementById('invPdfFrame').src = previewUrl;
    document.getElementById('invPdfDownloadLink').href = downloadUrl || previewUrl;
    document.getElementById('invPdfModal').classList.add('open');
}
function closeInvPdf(e) {
    if (e.target === document.getElementById('invPdfModal')) closeInvPdfBtn();
}
function closeInvPdfBtn() {
    document.getElementById('invPdfModal').classList.remove('open');
    document.getElementById('invPdfFrame').src = 'about:blank';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeInvPdfBtn();
});

/* Phone action sheet. Open/close only — the drag handle, the scrim and the
   slide-up are the shared sheet layer's, attached by the layout. */
(function () {
    var sheet = document.getElementById('invActionSheet');
    var trigger = document.getElementById('invMoreBtn');
    if (!sheet || !trigger) return;

    function open() {
        sheet.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        sheet.querySelector('.more-sheet-item')?.focus();
    }
    function close() {
        if (!sheet.classList.contains('open')) return;
        sheet.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
        // The drawer and the other sheets share this lock, so only release it
        // if nothing else is still holding the page open.
        document.body.style.overflow =
            document.querySelector('.sidebar.open, .modal-overlay.open') ? 'hidden' : '';
        trigger.focus();
    }

    trigger.addEventListener('click', open);
    sheet.addEventListener('click', function (e) { if (e.target === sheet) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();

(function () {
    var form = document.getElementById('payFormCard')?.querySelector('form');
    if (!form) return;
    var method = form.querySelector('[name="method"]');
    var chequeFields = form.querySelectorAll('.pay-cheque-field');
    var chequeNumber = form.querySelector('[name="cheque_number"]');
    var chequeDate = form.querySelector('[name="cheque_date"]');

    function syncChequeFields() {
        var isCheque = method.value === 'cheque';
        chequeFields.forEach(function (el) { el.style.display = isCheque ? 'block' : 'none'; });
        if (chequeNumber) chequeNumber.required = isCheque;
        if (chequeDate) chequeDate.required = isCheque;
    }

    method?.addEventListener('change', syncChequeFields);
    syncChequeFields();
})();
</script>
@endpush
