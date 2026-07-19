<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111827; background: #fff; }

.page { border: 1.5px solid #111827; padding: 26px 30px; }

/* ── LETTERHEAD ──────────────────────────────────── */
.letterhead { display: table; width: 100%; margin-bottom: 6px; }
.letterhead-logo { display: table-cell; width: 70px; vertical-align: middle; }
.logo-badge { width: 56px; height: 56px; }
.letterhead-co { display: table-cell; vertical-align: middle; text-align: center; }
.co-name { font-size: 15px; font-weight: 700; color: #1E3A8A; }
.co-division { font-size: 12px; font-weight: 700; color: #1E3A8A; margin-top: 1px; }
.co-address { font-size: 9px; color: #1E3A8A; margin-top: 4px; }
.co-contact { font-size: 9px; color: #1E3A8A; margin-top: 2px; }
.letterhead-spacer { display: table-cell; width: 70px; }

.trn-line { text-align: center; font-size: 10px; font-weight: 700; margin: 10px 0 4px; }
.doc-title { text-align: center; font-size: 20px; font-weight: 700; letter-spacing: 1px; margin: 6px 0 16px; }

/* ── INVOICE META ────────────────────────────────── */
.meta-row { display: table; width: 100%; margin-bottom: 14px; }
.meta-left  { display: table-cell; font-size: 11px; font-weight: 700; }
.meta-right { display: table-cell; text-align: right; font-size: 11px; font-weight: 700; }

/* ── TENANT INFO ─────────────────────────────────── */
.tenant-title { font-size: 12px; font-weight: 700; text-decoration: underline; margin-bottom: 8px; }
.tenant-line { font-size: 11px; margin-bottom: 3px; }
.tenant-line b { font-weight: 700; }

/* ── RENTAL DETAILS TABLE ────────────────────────── */
.rental-wrap { margin-top: 16px; border: 1.5px solid #111827; }
.rental-title { font-size: 11px; font-weight: 700; padding: 5px 8px; border-bottom: 1.5px solid #111827; }
table.rental { width: 100%; border-collapse: collapse; font-size: 10.5px; }
table.rental th {
    background: #D9D2B0; padding: 6px 6px; text-align: left; font-weight: 700;
    border-bottom: 1.5px solid #111827; border-right: 1px solid #111827;
}
table.rental th:last-child, table.rental td:last-child { border-right: none; }
table.rental th.right, table.rental td.right { text-align: right; }
table.rental td { padding: 5px 6px; border-bottom: 1px solid #94A3B8; border-right: 1px solid #94A3B8; height: 18px; }
table.rental tr.empty-row td { height: 18px; }
table.rental tr.total-row td { background: #D9D2B0; font-weight: 700; border-top: 1.5px solid #111827; border-bottom: none; }

/* ── TOTALS BOX ──────────────────────────────────── */
.totals-box { width: 46%; margin: 0 0 0 auto; border: 1.5px solid #111827; border-top: none; }
.totals-box table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
.totals-box td { padding: 5px 10px; border-bottom: 1px solid #94A3B8; }
.totals-box tr:last-child td { border-bottom: none; font-weight: 700; }
.totals-box td.right { text-align: right; font-weight: 700; }

/* ── AMOUNT IN WORDS ─────────────────────────────── */
.words-label { color: #991B1B; font-weight: 700; text-decoration: underline; font-size: 10.5px; margin: 16px 0 4px; }
.words-value { font-size: 11px; font-weight: 700; }

/* ── REMARKS ──────────────────────────────────────── */
.remarks-label { font-weight: 700; text-decoration: underline; font-size: 10.5px; margin: 14px 0 4px; }
.remarks-value { font-size: 10.5px; }

/* ── BANK DETAILS ────────────────────────────────── */
.bank-label { color: #991B1B; font-weight: 700; text-decoration: underline; font-size: 10.5px; margin: 14px 0 4px; }
.bank-line { font-size: 10.5px; font-weight: 700; margin-bottom: 2px; }

/* ── SIGNATURE / FOOTER ──────────────────────────── */
.sign-block { text-align: right; font-size: 10.5px; font-weight: 700; margin-top: 16px; line-height: 1.5; }
.footer-note { text-align: center; font-size: 9.5px; font-style: italic; margin-top: 26px; }

.spacer { height: 10px; }
</style>
</head>
<body>

<div class="page">

    {{-- LETTERHEAD --}}
    <div class="letterhead">
        <div class="letterhead-logo"><img class="logo-badge" src="{{ public_path('logo/promoseven-logo.png') }}" alt="Promoseven Holdings"></div>
        <div class="letterhead-co">
            <div class="co-name">Promoseven Holdings BSC &copy;</div>
            <div class="co-division">Real Estate Division</div>
            <div class="co-address">Office 27, Building 1130M Road 1531, Muharraq, Kingdom of Bahrain.</div>
            <div class="co-contact">CR # 21534-1, &nbsp; Tel : +973 17500787, &nbsp; Email : realestateaccounts@promoseven.com</div>
        </div>
        <div class="letterhead-spacer"></div>
    </div>

    <div class="trn-line">TRN # 200010076400002</div>
    <div class="doc-title">TAX INVOICE</div>

    {{-- INVOICE NUMBER / DATE --}}
    <div class="meta-row">
        <div class="meta-left">Invoice No.: {{ $invoice->invoice_number }}</div>
        <div class="meta-right">Invoice Date: {{ $invoice->invoice_date->format('d/m/Y') }}</div>
    </div>

    {{-- TENANT INFO --}}
    <div class="tenant-title">Tenant Information</div>
    <div class="tenant-line"><b>Name:</b> &nbsp;{{ $invoice->tenant_name }}</div>
    <div class="tenant-line"><b>Code:</b> &nbsp;{{ $invoice->tenant_code ?: '—' }}</div>
    <div class="tenant-line"><b>Address:</b> &nbsp;{{ $invoice->display_address ?: '—' }}</div>

    {{-- RENTAL DETAILS --}}
    <div class="rental-wrap">
        <div class="rental-title">Rental Details</div>
        <table class="rental">
            <thead>
                <tr>
                    <th style="width:6%">S.No.</th>
                    <th style="width:26%">Property</th>
                    <th style="width:14%">Unit No.</th>
                    <th style="width:14%">Lease No.</th>
                    <th style="width:24%">Rental Period</th>
                    <th class="right" style="width:16%">Rent (BD)</th>
                </tr>
            </thead>
            <tbody>
                @php $lines = $invoice->lines ?? []; @endphp
                @foreach($lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><b>{{ $line['property_name'] ?? '' }}</b></td>
                    <td>{{ $line['unit'] ?? '' }}</td>
                    <td>{{ $line['lease_agreement_no'] ?? '' }}</td>
                    <td>
                        @if(!empty($line['rental_period_start']))
                            From {{ \Illuminate\Support\Carbon::parse($line['rental_period_start'])->format('d-M-y') }} to {{ !empty($line['rental_period_end']) ? \Illuminate\Support\Carbon::parse($line['rental_period_end'])->format('d-m-Y') : '' }}
                        @endif
                    </td>
                    <td class="right">{{ number_format($line['amount'] ?? 0, 3) }}</td>
                </tr>
                @endforeach
                {{-- pad with empty rows so the table reads like the paper template --}}
                @for($i = count($lines); $i < max(10, count($lines) + 1); $i++)
                <tr class="empty-row"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
                @endfor
                <tr class="total-row">
                    <td></td><td></td><td></td><td></td>
                    <td class="right">Total</td>
                    <td class="right">{{ number_format($invoice->amount, 3) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- TOTALS --}}
    <div class="totals-box">
        <table>
            <tr><td>Total Excl. VAT (BD)</td><td class="right">{{ number_format($invoice->amount, 3) }}</td></tr>
            <tr><td>VAT ({{ number_format($invoice->vat_rate, 0) }}%)</td><td class="right">{{ $invoice->vat_amount > 0 ? number_format($invoice->vat_amount, 3) : '-' }}</td></tr>
            <tr><td>Total incl. VAT (BD)</td><td class="right">{{ number_format($invoice->total_incl_vat, 3) }}</td></tr>
        </table>
    </div>

    <div class="spacer"></div>

    {{-- AMOUNT IN WORDS --}}
    <div class="words-label">Amount In Words</div>
    <div class="words-value">{{ $invoice->amount_in_words }}</div>

    @if($invoice->remarks)
    {{-- REMARKS --}}
    <div class="remarks-label">Remarks</div>
    <div class="remarks-value">{{ $invoice->remarks }}</div>
    @endif

    {{-- BANK DETAILS --}}
    <div class="bank-label">Please remit your payment to our Bankers</div>
    <div class="bank-line">Al Salam Bank Bahrain</div>
    <div class="bank-line">In the name of: Promoseven Holdings BSC &copy;</div>
    <div class="bank-line">IBAN: BH26ALSA00280465160030</div>
    <div class="bank-line">Account No. 280465160030</div>
    <div class="bank-line">Swift Code: ALSABHBM</div>

    {{-- SIGNATURE --}}
    <div class="sign-block">
        For and on Behalf of<br>
        Promoseven Holdings BSC &copy;<br>
        Real Estate Division
    </div>

    <div class="footer-note">Note: This is a computer-generated Tax Invoice and does not require a physical signature or company stamp.</div>

</div>

</body>
</html>
