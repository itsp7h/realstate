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

    <a href="{{ route('reports.profit-loss') }}" class="report-card">
        <div class="report-card-icon"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="report-card-title">Profit &amp; Loss</div>
        <div class="report-card-desc">Rent, utilities and EWA cash collected against maintenance costs and unrecovered EWA charges — per building, per tenant, or across everything.</div>
    </a>
</div>

<div style="margin-top:20px;font-size:12px;color:var(--text-muted);max-width:640px">
    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
    Draft reports — "On Account" credit balances and post-dated cheques aren't tracked in the system yet, so those columns aren't included.
</div>

@endsection
