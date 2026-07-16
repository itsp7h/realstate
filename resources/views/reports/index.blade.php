@extends('layouts.admin')

@section('title', 'Reports')
@section('topbar-title', 'Reports')

@push('styles')
<style>
.report-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
@media (max-width: 900px) { .report-grid { grid-template-columns: 1fr; } }
.report-card {
    background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius);
    padding: 24px; display: flex; flex-direction: column; gap: 12px; text-decoration: none;
    transition: border-color .18s, transform .18s;
}
.report-card:hover { border-color: var(--accent); transform: translateY(-2px); }
.report-card-icon {
    width: 44px; height: 44px; border-radius: var(--radius-sm); background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.report-card-title { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); }
.report-card-desc { font-size: 12.5px; color: var(--text-muted); line-height: 1.55; }
.report-card-badge {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
    color: var(--accent); background: var(--accent-dim); padding: 3px 9px; border-radius: 20px; align-self: flex-start;
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Reports</h1>
        <p class="page-header-sub">Tenant statements, accounts-receivable ageing, and profit &amp; loss</p>
    </div>
</div>

<div class="report-grid">
    <a href="{{ route('reports.tenant-statement') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-file-invoice"></i></div>
        <div class="report-card-title">Tenant Statement</div>
        <div class="report-card-desc">A running bill-wise statement for one tenant — every outstanding rent invoice and EWA bill, in date order, with a balance due.</div>
    </a>

    <a href="{{ route('reports.bill-wise-statement') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-list-check"></i></div>
        <div class="report-card-title">Bill-wise Statement</div>
        <div class="report-card-desc">One row per outstanding bill for a tenant — opening amount, final balance, due date, and days overdue, matching the accountant's expected format.</div>
    </a>

    <a href="{{ route('reports.tenant-ledger') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-book"></i></div>
        <div class="report-card-title">Tenant Ledger</div>
        <div class="report-card-desc">Complete transaction history for one tenant — every bill, payment, and note in date order, with a running balance after each one.</div>
    </a>

    <a href="{{ route('reports.tenant-ageing') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="report-card-title">Tenant Ageing</div>
        <div class="report-card-desc">The same outstanding bills for one tenant, split into how overdue each one is: under 60 days, 60–120 days, and over 120 days.</div>
    </a>

    <a href="{{ route('reports.group-ageing') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-table-list"></i></div>
        <div class="report-card-title">Group Outstanding (Ageing)</div>
        <div class="report-card-desc">One row per tenant with an outstanding balance, in the same ageing buckets, with a grand total across everyone.</div>
    </a>

    <a href="{{ route('reports.financial-summary') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-chart-pie"></i></div>
        <div class="report-card-title">Tenant Financial Summary</div>
        <div class="report-card-desc">One row per tenant for a date range &mdash; opening balance carried in, amount billed and received in the period, and the resulting net balance.</div>
    </a>

    <a href="{{ route('reports.profit-loss') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="report-card-title">Profit &amp; Loss</div>
        <div class="report-card-desc">Rent, utilities and EWA cash collected against maintenance costs and unrecovered EWA charges — per building, per tenant, or across everything.</div>
    </a>

    <a href="{{ route('reports.rent-schedule') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="report-card-title">Rent Payment Schedule</div>
        <div class="report-card-desc">Month-by-month for one tenant — which months were paid in full, which were only partly paid, and which were never invoiced at all.</div>
    </a>

    <a href="{{ route('reports.collection') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-receipt"></i></div>
        <div class="report-card-title">Collection Report</div>
        <div class="report-card-desc">Every rent and EWA payment received in a date range &mdash; receipt no, cheque details, tenant, and amount.</div>
    </a>

    <a href="{{ route('reports.vat-return') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="report-card-title">VAT Return</div>
        <div class="report-card-desc">Every rent invoice and EWA bill for a property and date range, in the exact column format the quarterly VAT filing needs — export straight to XLSX.</div>
    </a>
</div>

<div style="margin-top:20px;font-size:12px;color:var(--text-muted);max-width:640px">
    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
    Draft reports — "On Account" credit balances and post-dated cheques aren't tracked in the system yet, so those columns aren't included.
</div>

@endsection
