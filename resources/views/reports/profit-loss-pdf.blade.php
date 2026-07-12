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
.report-scope { text-align: center; font-size: 12px; font-weight: 700; margin-top: 6px; }
.report-sub { text-align: center; font-size: 9.5px; color: #475569; margin-top: 2px; }

.stat-row { display: table; width: 100%; margin-top: 16px; }
.stat-cell { display: table-cell; width: 33.33%; padding: 10px; text-align: center; border: 1px solid #E2E8F0; }
.stat-label { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; }
.stat-value { font-size: 16px; font-weight: 700; margin-top: 4px; }
.stat-value.profit { color: #059669; }
.stat-value.loss { color: #DC2626; }
.stat-value.expense { color: #D97706; }

table.ledger { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: 10px; }
table.ledger th {
    text-align: left; padding: 6px 8px; font-weight: 700; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em;
    color: #475569; border-bottom: 1.5px solid #111827;
}
table.ledger th.right, table.ledger td.right { text-align: right; }
table.ledger td { padding: 6px 8px; border-bottom: 1px solid #E2E8F0; }
table.ledger tr.subtotal-row td { border-top: 1px solid #94A3B8; font-weight: 700; }
table.ledger tr.net-row td { border-top: 1.5px solid #111827; border-bottom: none; font-weight: 700; padding-top: 8px; }

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

<div class="report-title">PROFIT &amp; LOSS STATEMENT</div>
<div class="report-scope">
    @if($unit) {{ $unit->unit_name }} &mdash; {{ $unit->building?->property_name ?? $unit->property_name }}
    @elseif($building) {{ $building->property_name }}
    @elseif($tenant) {{ $tenant->name }}
    @else All Buildings @endif
</div>
<div class="report-sub">{{ $from->format('d-M-Y') }} to {{ $to->format('d-M-Y') }}</div>

@php
    $net = $statement['net_profit'];
    $isProfit = $net >= 0;
    $fmt = fn ($v) => number_format($v, 3);
@endphp

<div class="stat-row">
    <div class="stat-cell"><div class="stat-label">Total Revenue</div><div class="stat-value">{{ $fmt($statement['total_revenue']) }}</div></div>
    <div class="stat-cell"><div class="stat-label">Total Expense</div><div class="stat-value expense">{{ $fmt($statement['total_expense']) }}</div></div>
    <div class="stat-cell"><div class="stat-label">Net {{ $isProfit ? 'Profit' : 'Loss' }}</div><div class="stat-value {{ $isProfit ? 'profit' : 'loss' }}">{{ $fmt(abs($net)) }}</div></div>
</div>

<table class="ledger">
    <thead>
        <tr><th>Line Item</th><th class="right">Amount (BHD)</th></tr>
    </thead>
    <tbody>
        <tr><td>Rent collected</td><td class="right">{{ $fmt($statement['revenue']['rent_collected']) }}</td></tr>
        <tr><td>Utilities collected</td><td class="right">{{ $fmt($statement['revenue']['utilities_collected']) }}</td></tr>
        <tr><td>Other invoices collected</td><td class="right">{{ $fmt($statement['revenue']['other_collected']) }}</td></tr>
        <tr><td>EWA collected from tenants</td><td class="right">{{ $fmt($statement['revenue']['ewa_collected']) }}</td></tr>
        <tr class="subtotal-row"><td>Total Revenue</td><td class="right">{{ $fmt($statement['total_revenue']) }}</td></tr>

        <tr><td>EWA charges not recovered from tenant</td><td class="right">{{ $fmt($statement['expenses']['ewa_landlord_expense']) }}</td></tr>
        <tr><td>Approved maintenance cost</td><td class="right">{{ $fmt($statement['expenses']['maintenance_expense']) }}</td></tr>
        <tr class="subtotal-row"><td>Total Expense</td><td class="right">{{ $fmt($statement['total_expense']) }}</td></tr>

        <tr class="net-row"><td>Net {{ $isProfit ? 'Profit' : 'Loss' }}</td><td class="right">{{ $fmt(abs($net)) }}</td></tr>
    </tbody>
</table>

@if($breakdown->isNotEmpty())
<table class="ledger">
    <thead>
        <tr><th>Building</th><th class="right">Revenue (BHD)</th><th class="right">Expense (BHD)</th><th class="right">Net (BHD)</th></tr>
    </thead>
    <tbody>
        @foreach($breakdown as $row)
        <tr>
            <td>{{ $row['building']->property_name }}</td>
            <td class="right">{{ $fmt($row['total_revenue']) }}</td>
            <td class="right">{{ $fmt($row['total_expense']) }}</td>
            <td class="right">{{ $fmt($row['net_profit']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer-note">This is a computer-generated statement. Revenue is recognised on a cash basis; expenses are recognised when incurred.</div>

</body>
</html>
