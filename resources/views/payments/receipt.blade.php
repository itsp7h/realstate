<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 12px;
    color: #1E293B;
    background: #fff;
    padding: 40px 48px;
}

/* ── HEADER ──────────────────────────────────── */
.header {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding-bottom: 24px; border-bottom: 2px solid #1E293B; margin-bottom: 28px;
}
.company-name {
    font-size: 22px; font-weight: 700; color: #1E293B; letter-spacing: -0.3px;
}
.company-sub { font-size: 10px; color: #64748B; margin-top: 3px; }

.receipt-label {
    text-align: right;
}
.receipt-label .word {
    font-size: 28px; font-weight: 700; color: #1E293B; letter-spacing: 2px; text-transform: uppercase;
}
.receipt-label .num {
    font-size: 13px; font-weight: 600; color: #2563EB; margin-top: 2px;
}

/* ── META GRID ────────────────────────────────── */
.meta-grid {
    display: table; width: 100%; margin-bottom: 24px;
}
.meta-col { display: table-cell; width: 50%; vertical-align: top; }
.meta-col:last-child { text-align: right; }

.meta-section-title {
    font-size: 9px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: #94A3B8; margin-bottom: 6px;
}
.meta-line { font-size: 12px; color: #1E293B; margin-bottom: 3px; }
.meta-line.bold { font-weight: 700; font-size: 13px; }
.meta-line.muted { color: #64748B; font-size: 11px; }

/* ── PAYMENT DETAILS TABLE ────────────────────── */
.section-title {
    font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: #64748B; margin-bottom: 10px; padding-bottom: 6px;
    border-bottom: 1px solid #E2E8F0;
}

.detail-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
.detail-table tr td { padding: 8px 12px; vertical-align: top; }
.detail-table tr:nth-child(odd) td { background: #F8FAFC; }
.detail-table tr:first-child td:first-child { border-radius: 4px 0 0 0; }
.detail-table tr:first-child td:last-child  { border-radius: 0 4px 0 0; }
.detail-table .label-col { color: #64748B; font-size: 11px; width: 40%; }
.detail-table .value-col { color: #1E293B; font-weight: 600; font-size: 12px; }

/* ── AMOUNT BOX ───────────────────────────────── */
.amount-box {
    border: 2px solid #1E293B; border-radius: 6px; margin-bottom: 28px;
    overflow: hidden;
}
.amount-box-header {
    background: #1E293B; color: #fff; padding: 8px 16px;
    font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
}
.amount-box-body { display: table; width: 100%; }
.amount-cell { display: table-cell; padding: 14px 20px; text-align: center; vertical-align: middle; }
.amount-cell + .amount-cell { border-left: 1px solid #E2E8F0; }
.amount-cell .lbl { font-size: 9px; color: #94A3B8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
.amount-cell .val { font-size: 20px; font-weight: 700; color: #1E293B; }
.amount-cell .val.green { color: #059669; }
.amount-cell .val.red   { color: #DC2626; }
.amount-cell .currency  { font-size: 10px; color: #94A3B8; margin-left: 2px; }

/* ── INVOICE REFERENCE ────────────────────────── */
.inv-ref-box {
    background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px;
    padding: 14px 18px; margin-bottom: 28px;
}
.inv-ref-row { display: table; width: 100%; }
.inv-ref-cell { display: table-cell; vertical-align: top; width: 50%; }
.inv-ref-lbl { font-size: 9px; color: #94A3B8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px; }
.inv-ref-val { font-size: 12px; font-weight: 600; color: #1E293B; }

/* ── NOTES ─────────────────────────────────────── */
.notes-box {
    background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 6px; padding: 12px 16px; margin-bottom: 28px;
}
.notes-box .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #D97706; margin-bottom: 4px; }
.notes-box .body { font-size: 11px; color: #92400E; line-height: 1.5; }

/* ── FOOTER ─────────────────────────────────────── */
.footer {
    border-top: 1px solid #E2E8F0; padding-top: 16px; margin-top: 20px;
    display: table; width: 100%;
}
.footer-left  { display: table-cell; vertical-align: middle; font-size: 10px; color: #94A3B8; }
.footer-right { display: table-cell; vertical-align: middle; text-align: right; font-size: 10px; color: #94A3B8; }
.footer-stamp {
    display: inline-block; border: 2px solid #059669; border-radius: 50%;
    padding: 6px 10px; color: #059669; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em;
}

/* ── WATERMARK ────────────────────────────────── */
.watermark {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font-size: 80px; font-weight: 900; color: rgba(5, 150, 105, 0.05);
    letter-spacing: 8px; text-transform: uppercase; pointer-events: none;
    white-space: nowrap;
}
</style>
</head>
<body>

<div class="watermark">PAID</div>

{{-- HEADER --}}
<div class="header">
    <div>
        <div class="company-name">P7H Real Estate</div>
        <div class="company-sub">Kingdom of Bahrain &bull; Property Management</div>
    </div>
    <div class="receipt-label">
        <div class="word">Receipt</div>
        <div class="num">{{ $payment->payment_number }}</div>
    </div>
</div>

{{-- META --}}
<div class="meta-grid">
    <div class="meta-col">
        <div class="meta-section-title">Received From</div>
        <div class="meta-line bold">{{ $invoice->tenant_name }}</div>
        <div class="meta-line muted">{{ $invoice->property_name }}{{ $invoice->unit ? ' / '.$invoice->unit : '' }}</div>
    </div>
    <div class="meta-col">
        <div class="meta-section-title">Payment Date</div>
        <div class="meta-line bold">{{ $payment->payment_date->format('d F Y') }}</div>
        <div class="meta-line muted">Issued: {{ now()->format('d F Y') }}</div>
    </div>
</div>

{{-- PAYMENT DETAILS --}}
<div class="section-title">Payment Details</div>
<table class="detail-table">
    <tr>
        <td class="label-col">Payment Number</td>
        <td class="value-col">{{ $payment->payment_number }}</td>
    </tr>
    <tr>
        <td class="label-col">Payment Method</td>
        <td class="value-col">{{ $payment->method_label }}</td>
    </tr>
    @if($payment->reference)
    <tr>
        <td class="label-col">Reference / Cheque No.</td>
        <td class="value-col">{{ $payment->reference }}</td>
    </tr>
    @endif
    <tr>
        <td class="label-col">Payment Date</td>
        <td class="value-col">{{ $payment->payment_date->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label-col">Invoice Type</td>
        <td class="value-col">{{ $invoice->type_label }}</td>
    </tr>
    @if($invoice->description)
    <tr>
        <td class="label-col">Description</td>
        <td class="value-col">{{ $invoice->description }}</td>
    </tr>
    @endif
</table>

{{-- AMOUNTS --}}
<div class="amount-box">
    <div class="amount-box-header">Amount Summary (BHD)</div>
    <div class="amount-box-body">
        <div class="amount-cell">
            <div class="lbl">Invoice Total</div>
            <div class="val">{{ number_format($invoice->amount, 3) }}<span class="currency">BHD</span></div>
        </div>
        <div class="amount-cell">
            <div class="lbl">This Payment</div>
            <div class="val green">{{ number_format($payment->amount, 3) }}<span class="currency">BHD</span></div>
        </div>
        <div class="amount-cell">
            <div class="lbl">Remaining Balance</div>
            <div class="val {{ $invoice->balance_due > 0 ? 'red' : 'green' }}">
                {{ number_format($invoice->balance_due, 3) }}<span class="currency">BHD</span>
            </div>
        </div>
    </div>
</div>

{{-- INVOICE REFERENCE --}}
<div class="section-title">Invoice Reference</div>
<div class="inv-ref-box">
    <div class="inv-ref-row">
        <div class="inv-ref-cell">
            <div class="inv-ref-lbl">Invoice Number</div>
            <div class="inv-ref-val">{{ $invoice->invoice_number }}</div>
        </div>
        <div class="inv-ref-cell">
            <div class="inv-ref-lbl">Invoice Date</div>
            <div class="inv-ref-val">{{ $invoice->invoice_date->format('d M Y') }}</div>
        </div>
    </div>
</div>

{{-- NOTES --}}
@if($payment->notes)
<div class="notes-box">
    <div class="lbl">Notes</div>
    <div class="body">{{ $payment->notes }}</div>
</div>
@endif

{{-- FOOTER --}}
<div class="footer">
    <div class="footer-left">
        This receipt is computer-generated and valid without signature.<br>
        P7H Real Estate &bull; Kingdom of Bahrain
    </div>
    <div class="footer-right">
        @if($invoice->balance_due <= 0)
        <div class="footer-stamp">&#10003; Settled</div>
        @endif
    </div>
</div>

</body>
</html>
