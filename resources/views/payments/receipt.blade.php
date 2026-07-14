<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #111827; }

/* ── LETTERHEAD ──────────────────────────────────────────── */
.letterhead { display: table; width: 100%; }
.letterhead-logo { display: table-cell; width: 64px; vertical-align: middle; }
.logo-badge { width: 52px; height: 52px; }
.letterhead-co { display: table-cell; vertical-align: middle; text-align: center; }
.co-name { font-size: 14px; font-weight: 700; letter-spacing: 0.3px; color: #1E3A8A; }
.co-line { font-size: 9px; color: #4B5563; margin-top: 3px; line-height: 1.5; }
.letterhead-spacer { display: table-cell; width: 64px; }

/* ── TITLE ───────────────────────────────────────────────── */
.doc-title {
    text-align: center; font-size: 16px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: #111827; margin: 28px 0 8px;
}
.title-rule { width: 64px; height: 2px; background: #1E3A8A; margin: 0 auto 24px; }

/* ── DATED ROW ───────────────────────────────────────────── */
.dated-row { display: table; width: 100%; margin-bottom: 16px; }
.dated-row .fill { display: table-cell; }
.dated-row .lbl { display: table-cell; text-align: right; white-space: nowrap; padding-right: 8px; font-size: 10px; color: #4B5563; }
.dated-row .val { display: table-cell; text-align: right; white-space: nowrap; font-weight: 700; font-size: 10px; width: 110px; }

/* ── PARTICULARS / AMOUNT TABLE ──────────────────────────── */
table.rv { width: 100%; border-collapse: collapse; }
table.rv th {
    text-align: left; padding: 8px 6px; font-weight: 700; font-size: 9px; text-transform: uppercase;
    letter-spacing: 0.06em; color: #6B7280; background: #F9FAFB; border-bottom: 1.5px solid #111827;
}
table.rv th.amt-head { text-align: right; width: 110px; }
table.rv td { padding: 8px 6px; vertical-align: top; }
table.rv td.amt { text-align: right; white-space: nowrap; width: 110px; font-weight: 600; }
table.rv .particulars-col { width: auto; }
/* A solid-fill spacer column instead of a per-row border-left — DomPDF's
   border-collapse support leaves visible white seams between stacked
   <td> borders, but a background-painted column has no seams. */
table.rv td.divider, table.rv th.divider { width: 1px; padding: 0; background: #111827; }

.p-account { font-weight: 700; padding-bottom: 4px; }
.p-tenant { padding-left: 16px; padding-bottom: 4px; }
.p-category { padding-left: 16px; font-weight: 700; padding-bottom: 4px; }
.p-unit-line { padding-left: 32px; color: #374151; }
.p-unit-line .cr { display: inline-block; width: 28px; text-align: right; font-weight: 600; }
.p-unit-line .amt-inline { display: inline-block; width: 90px; text-align: right; font-weight: 600; }

.rv-spacer td { padding: 0; height: 32px; }

.rv-meta-row td { padding-top: 16px; padding-bottom: 4px; }
.rv-meta-lbl { font-weight: 700; margin-bottom: 4px; }
.rv-meta-val { padding-left: 16px; color: #374151; }

.rv-total-row td { border-top: 1.5px solid #111827; padding-top: 10px; padding-bottom: 2px; }
.rv-total-row td.amt { font-weight: 700; font-size: 12px; }

/* ── SIGNATURE / FOOTER ──────────────────────────────────── */
.sign-block { display: table; width: 100%; margin-top: 64px; }
.sign-cell { display: table-cell; width: 50%; vertical-align: bottom; }
.sign-cell.right { text-align: right; }
.sign-line { border-top: 1px solid #111827; width: 190px; margin-left: auto; padding-top: 5px; font-size: 9.5px; color: #4B5563; }
.prepared-by { margin-top: 32px; font-size: 10.5px; color: #4B5563; }
</style>
</head>
<body>

<div class="letterhead">
    <div class="letterhead-logo"><img class="logo-badge" src="{{ public_path('logo/promoseven-logo.png') }}" alt="Promoseven Holdings"></div>
    <div class="letterhead-co">
        <div class="co-name">PROMOSEVEN HOLDINGS B.S.C.</div>
        <div class="co-line">Road/Street: 1531, Town: MUHARRAQ, Block: 215,</div>
        <div class="co-line">Flat/Shop#: 27, Building: 1130, Bahrain</div>
        <div class="co-line">E-Mail : realestateaccounts@promoseven.com</div>
        <div class="co-line">TRN : 200010076400002</div>
    </div>
    <div class="letterhead-spacer"></div>
</div>

<div class="doc-title">Receipt</div>
<div class="title-rule"></div>

<div class="dated-row">
    <div class="fill"></div>
    <div class="lbl">Dated</div>
    <div class="val">: {{ $payment->payment_date->format('d-M-Y') }}</div>
</div>

<table class="rv">
    <thead>
        <tr>
            <th class="particulars-col">Particulars</th>
            <th class="divider"></th>
            <th class="amt-head">Amount (BHD)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="p-account">Account :</td>
            <td class="divider"></td>
            <td class="amt"></td>
        </tr>
        <tr>
            <td class="p-tenant">{{ $invoice->tenant_name }}</td>
            <td class="divider"></td>
            <td class="amt">{{ number_format($payment->amount, 3) }}</td>
        </tr>
        <tr>
            <td class="p-category">Primary Cost Category</td>
            <td class="divider"></td>
            <td class="amt"></td>
        </tr>
        <tr>
            <td class="p-unit-line">
                {{ $invoice->unit ?: $invoice->property_name }}
                <span class="amt-inline">{{ number_format($payment->amount, 3) }}</span>
                <span class="cr">Cr</span>
            </td>
            <td class="divider"></td>
            <td class="amt"></td>
        </tr>
        <tr class="rv-spacer"><td></td><td class="divider"></td><td></td></tr>
        <tr class="rv-meta-row">
            <td>
                <div class="rv-meta-lbl">Through :</div>
                <div class="rv-meta-val">{{ $payment->reference ?: $payment->method_label }}</div>
            </td>
            <td class="divider"></td>
            <td class="amt"></td>
        </tr>
        <tr>
            <td>
                <div class="rv-meta-lbl">On Account of :</div>
                <div class="rv-meta-val">{{ $payment->notes ?: 'Being the payment against ' . $invoice->type_label . ' (Inv ' . $invoice->invoice_number . ')' }}</div>
            </td>
            <td class="divider"></td>
            <td class="amt"></td>
        </tr>
        <tr>
            <td>
                <div class="rv-meta-lbl">Amount (in words) :</div>
                <div class="rv-meta-val">{{ \App\Support\NumberToWords::bahrainiDinars($payment->amount) }}</div>
            </td>
            <td class="divider"></td>
            <td class="amt"></td>
        </tr>
        <tr class="rv-total-row">
            <td></td>
            <td class="divider"></td>
            <td class="amt">{{ number_format($payment->amount, 3) }}</td>
        </tr>
    </tbody>
</table>

<div class="sign-block">
    <div class="sign-cell"></div>
    <div class="sign-cell right">
        <div class="sign-line">Authorised Signatory</div>
    </div>
</div>

<div class="prepared-by">Prepared by</div>

</body>
</html>
