<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111827; }

.letterhead { display: table; width: 100%; margin-bottom: 4px; }
.letterhead-logo { display: table-cell; width: 60px; vertical-align: middle; }
.logo-badge { width: 48px; height: 48px; }
.letterhead-co { display: table-cell; vertical-align: middle; text-align: center; }
.co-name { font-size: 13px; font-weight: 700; }
.co-line { font-size: 10px; margin-top: 2px; }
.letterhead-spacer { display: table-cell; width: 60px; }

.doc-title { text-align: center; font-size: 13px; font-weight: 700; letter-spacing: 0.05em; margin: 22px 0 18px; }

.dated-row { display: table; width: 100%; margin-bottom: 6px; }
.dated-row .fill { display: table-cell; }
.dated-row .lbl { display: table-cell; text-align: right; white-space: nowrap; padding-right: 6px; }
.dated-row .val { display: table-cell; text-align: right; white-space: nowrap; font-weight: 700; width: 110px; }

table.rv { width: 100%; border-collapse: collapse; margin-top: 6px; }
table.rv th {
    text-align: left; padding: 6px 4px 8px; font-weight: 400; font-size: 11px;
    border-bottom: 1px solid #111827;
}
table.rv th.amt-head { text-align: right; }
table.rv td { padding: 3px 4px; vertical-align: top; }
table.rv td.amt { text-align: right; white-space: nowrap; }
table.rv .particulars-col { width: 77%; }
/* A solid-fill spacer column instead of a per-row border-left — DomPDF's
   border-collapse support leaves visible white seams between stacked
   <td> borders, but a background-painted column has no seams. */
table.rv td.divider, table.rv th.divider { width: 1px; padding: 0; background: #111827; }

.p-account { font-weight: 700; }
.p-tenant { padding-left: 18px; }
.p-category { padding-left: 18px; font-weight: 700; }
.p-unit-line { padding-left: 36px; }
.p-unit-line .cr { display: inline-block; width: 30px; text-align: right; }
.p-unit-line .amt-inline { display: inline-block; width: 90px; text-align: right; }

.rv-spacer td { padding: 0; height: 90px; }

.rv-meta-row td { padding-top: 14px; }
.rv-meta-lbl { font-weight: 700; }
.rv-meta-val { padding-left: 18px; }

.rv-total-row td { border-top: 1px solid #111827; padding-top: 6px; }
.rv-total-row td.amt { font-weight: 700; }

.sign-block { display: table; width: 100%; margin-top: 60px; }
.sign-cell { display: table-cell; width: 50%; vertical-align: bottom; }
.sign-cell.right { text-align: right; }
.sign-line { border-top: 1px solid #111827; width: 200px; margin-left: auto; padding-top: 4px; font-size: 10px; }
.prepared-by { margin-top: 40px; font-size: 11px; }
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

<div class="doc-title">RECEIPT</div>

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
            <th class="amt-head">Amount</th>
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
