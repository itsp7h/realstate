<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #111827; }

.letterhead { display: table; width: 100%; margin-bottom: 4px; }
.letterhead-logo { display: table-cell; width: 56px; vertical-align: middle; }
.logo-badge { width: 44px; height: 44px; }
.letterhead-co { display: table-cell; vertical-align: middle; text-align: center; }
.co-name { font-size: 14px; font-weight: 700; color: #1E3A8A; }
.co-division { font-size: 11px; font-weight: 700; color: #1E3A8A; margin-top: 1px; }
.co-address { font-size: 8.5px; color: #1E3A8A; margin-top: 3px; }
.co-contact { font-size: 8.5px; color: #1E3A8A; margin-top: 1px; }
.co-trn { font-size: 9px; font-weight: 700; margin-top: 6px; }
.letterhead-spacer { display: table-cell; width: 56px; }

/* Tenant name sits above the report title in this template, matching
   the reference "Bill-wise Details" export format exactly. */
.report-tenant { text-align: center; font-size: 13px; font-weight: 700; margin-top: 10px; }
.report-title { text-align: center; font-size: 11px; margin: 4px 0 2px; }
.report-sub { text-align: center; font-size: 9.5px; color: #475569; margin-top: 2px; }

table.ledger { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 9.5px; }
table.ledger th {
    text-align: left; padding: 6px 6px; font-weight: 700; font-size: 9px; text-transform: uppercase; letter-spacing: 0.03em;
    color: #475569; border-bottom: 1.5px solid #111827;
}
table.ledger th.right, table.ledger td.right { text-align: right; }
table.ledger td { padding: 6px 6px; border-bottom: 1px solid #E2E8F0; }
table.ledger tr.total-row td { border-top: 1.5px solid #111827; border-bottom: none; font-weight: 700; padding-top: 8px; }
/* Double rule under the grand total, matching the reference's
   double-underline treatment on Final Balance. */
table.ledger tr.total-row td.right { border-top: 3px double #111827; }
.overdue { color: #DC2626; font-weight: 700; font-style: italic; }
.overdue-zero { font-style: italic; color: #94A3B8; }
.postdated { color: #94A3B8; font-style: italic; }

.footer-note { text-align: center; font-size: 8.5px; font-style: italic; color: #64748B; margin-top: 30px; }
</style>
</head>
<body>

<div class="letterhead">
    <div class="letterhead-logo"><img class="logo-badge" src="{{ public_path('logo/promoseven-logo.png') }}" alt="Promoseven Holdings"></div>
    <div class="letterhead-co">
        <div class="co-name">Promoseven Holdings BSC &copy;</div>
        <div class="co-division">Real Estate Division</div>
        <div class="co-address">Office 27, Building 1130M Road 1531, Muharraq, Kingdom of Bahrain.</div>
        <div class="co-contact">CR # 21534-1, &nbsp; Tel : +973 17500787, &nbsp; Email : realestateaccounts@promoseven.com</div>
        <div class="co-trn">TRN : 200010076400002</div>
    </div>
    <div class="letterhead-spacer"></div>
</div>

<div class="report-tenant">{{ $tenant->name }} @if($tenant->tenant_code)({{ $tenant->tenant_code }})@endif</div>
<div class="report-title">Bill-wise Details</div>
<div class="report-sub">{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}</div>

<table class="ledger">
    <thead>
        <tr>
            <th>Date</th>
            <th>Bill Ref.</th>
            <th>Ref. / LPO No.</th>
            <th class="right">Opening Amount</th>
            <th class="right">Post-Dated Amount</th>
            <th class="right">Final Balance</th>
            <th>Due on</th>
            <th class="right">Overdue by days</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row['date']->format('d-M-Y') }}</td>
            <td>{{ $row['bill_ref'] }}</td>
            <td>{{ $row['description'] }}</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($row['opening_amount']) }}</td>
            <td class="right postdated">&mdash;</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($row['pending_amount']) }}</td>
            <td>{{ $row['due_on']->format('d-M-Y') }}</td>
            <td class="right {{ $row['overdue_days'] > 0 ? 'overdue' : 'overdue-zero' }}">{{ $row['overdue_days'] }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#64748B">No outstanding bills in this date range.</td></tr>
        @endforelse
        @if($rows->isNotEmpty())
        <tr class="total-row">
            <td colspan="5">Total</td>
            <td class="right">{{ \App\Support\MoneyFormat::crDr($total) }}</td>
            <td colspan="2"></td>
        </tr>
        @endif
    </tbody>
</table>

<div class="footer-note">This is a computer-generated statement. Post-dated cheques are not reflected.</div>

</body>
</html>
