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
.letterhead-spacer { display: table-cell; width: 56px; }

.report-title { text-align: center; font-size: 13px; font-weight: 700; margin: 14px 0 2px; }
.report-tenant { text-align: center; font-size: 12px; font-weight: 700; margin-top: 6px; }
.report-sub { text-align: center; font-size: 9.5px; color: #475569; margin-top: 2px; }

table.ledger { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 10px; }
table.ledger th {
    text-align: left; padding: 6px 8px; font-weight: 700; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em;
    color: #475569; border-bottom: 1.5px solid #111827;
}
table.ledger th.right, table.ledger td.right { text-align: right; }
table.ledger td { padding: 6px 8px; border-bottom: 1px solid #E2E8F0; }
.status-paid         { color: #059669; font-weight: 700; }
.status-partial       { color: #D97706; font-weight: 700; }
.status-unpaid        { color: #DC2626; font-weight: 700; }
.status-not_invoiced  { color: #64748B; font-weight: 700; }

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
    </div>
    <div class="letterhead-spacer"></div>
</div>

<div class="report-title">RENT PAYMENT SCHEDULE</div>
<div class="report-tenant">{{ $tenant->name }} @if($tenant->tenant_code)({{ $tenant->tenant_code }})@endif</div>
@if($rows->isNotEmpty())
<div class="report-sub">{{ $rows->first()['month']->format('M Y') }} to {{ $rows->last()['month']->format('M Y') }}</div>
@endif

<table class="ledger">
    <thead>
        <tr>
            <th>Month</th>
            <th class="right">Expected (BHD)</th>
            <th class="right">Invoiced (BHD)</th>
            <th class="right">Paid (BHD)</th>
            <th class="right">Remaining (BHD)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row['month']->format('F Y') }}</td>
            <td class="right">{{ number_format($row['expected'], 3) }}</td>
            <td class="right">{{ number_format($row['invoiced'], 3) }}</td>
            <td class="right">{{ number_format($row['paid'], 3) }}</td>
            <td class="right">{{ number_format($row['remaining'], 3) }}</td>
            <td class="status-{{ $row['status'] }}">
                {{ match($row['status']) {
                    'paid'         => 'Paid',
                    'partial'      => 'Partially Paid',
                    'unpaid'       => 'Unpaid',
                    'not_invoiced' => 'Not Invoiced',
                } }}
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:20px;color:#64748B">No rent-bearing lease contracts on file.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer-note">This is a computer-generated statement. "Not Invoiced" means no rent invoice was raised for that month.</div>

</body>
</html>
