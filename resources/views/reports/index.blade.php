@extends('layouts.admin')

@section('title', 'Reports')
@section('topbar-title', 'Reports')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Reports</h1>
        <p class="page-header-sub">Tenant statements, accounts-receivable ageing, and profit &amp; loss</p>
    </div>
</div>

{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════ --}}
@php
    $mobileReports = [
        ['route' => 'reports.tenant-statement',       'icon' => 'fa-file-invoice',         'bg' => 'var(--m-gold-tint)', 'fg' => 'var(--m-gold-text)', 'name' => 'Tenant Statement',            'desc' => 'Bill-wise statement of outstanding rent invoices & EWA bills for one tenant.'],
        ['route' => 'reports.bill-wise-statement',    'icon' => 'fa-list-check',           'bg' => 'var(--m-blue-tint)', 'fg' => 'var(--m-blue)',      'name' => 'Bill-wise Statement',          'desc' => 'One row per outstanding bill — opening amount, balance, due date, days overdue.'],
        ['route' => 'reports.tenant-ledger',          'icon' => 'fa-book',                 'bg' => 'var(--m-purple-tint)','fg' => 'var(--m-purple)',   'name' => 'Tenant Ledger',                'desc' => 'Complete transaction history for one tenant with a running balance.'],
        ['route' => 'reports.tenant-ageing',          'icon' => 'fa-hourglass-half',       'bg' => 'var(--m-green-tint)', 'fg' => 'var(--m-green)',    'name' => 'Tenant Ageing',                'desc' => 'Outstanding bills split into under 60 / 60–120 / over 120 day buckets.'],
        ['route' => 'reports.group-ageing',           'icon' => 'fa-table-list',           'bg' => 'var(--m-blue-tint)', 'fg' => 'var(--m-blue)',      'name' => 'Group Outstanding (Ageing)',   'desc' => 'One row per tenant with an outstanding balance, plus a grand total.'],
        ['route' => 'reports.financial-summary',      'icon' => 'fa-chart-pie',            'bg' => 'var(--m-purple-tint)','fg' => 'var(--m-purple)',   'name' => 'Tenant Financial Summary',     'desc' => 'Opening balance, billed and received amounts, and net balance per tenant.'],
        ['route' => 'reports.profit-loss',            'icon' => 'fa-scale-balanced',       'bg' => 'var(--m-gold-tint)', 'fg' => 'var(--m-gold-text)', 'name' => 'Profit & Loss',                'desc' => 'Rent, utilities & EWA collected against maintenance costs and unrecovered EWA.'],
        ['route' => 'reports.rent-schedule',          'icon' => 'fa-calendar-check',       'bg' => 'var(--m-green-tint)', 'fg' => 'var(--m-green)',    'name' => 'Rent Payment Schedule',        'desc' => 'Month-by-month paid / partly paid / never invoiced status for one tenant.'],
        ['route' => 'reports.collection',             'icon' => 'fa-receipt',              'bg' => 'var(--m-blue-tint)', 'fg' => 'var(--m-blue)',      'name' => 'Collection Report',            'desc' => 'Every rent & EWA payment received in a date range, with receipt details.'],
        ['route' => 'reports.vat-return',             'icon' => 'fa-file-invoice-dollar',  'bg' => 'var(--m-gold-tint)', 'fg' => 'var(--m-gold-text)', 'name' => 'VAT Return',                   'desc' => 'Rent invoices & EWA bills in the exact format the quarterly VAT filing needs.'],
    ];
@endphp
<div class="m-screen">
    <div class="m-row-list">
        @foreach($mobileReports as $r)
            <a href="{{ route($r['route']) }}" class="m-row-card">
                <div class="m-row-icon" style="background:{{ $r['bg'] }};color:{{ $r['fg'] }};"><i class="fa-solid {{ $r['icon'] }}"></i></div>
                <div style="flex:1;min-width:0;">
                    <div class="m-row-title">{{ $r['name'] }}</div>
                    <div class="m-row-sub" style="line-height:1.4;">{{ $r['desc'] }}</div>
                </div>
                <i class="fa-solid fa-chevron-right m-row-chevron"></i>
            </a>
        @endforeach
    </div>
    <div style="font-size:11px;color:var(--m-muted);line-height:1.5;padding:0 4px;">
        <i class="fa-solid fa-circle-info"></i>
        Draft reports — "On Account" credit balances and post-dated cheques aren't tracked yet, so those columns aren't included.
    </div>
</div>

<div class="card-grid is-3 m-hide-desktop-index">
    <a href="{{ route('reports.tenant-statement') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-file-invoice"></i></div>
        <div class="card-title">Tenant Statement</div>
        <div class="card-subtitle">A running bill-wise statement for one tenant — every outstanding rent invoice and EWA bill, in date order, with a balance due.</div>
    </a>

    <a href="{{ route('reports.bill-wise-statement') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-list-check"></i></div>
        <div class="card-title">Bill-wise Statement</div>
        <div class="card-subtitle">One row per outstanding bill for a tenant — opening amount, final balance, due date, and days overdue, matching the accountant's expected format.</div>
    </a>

    <a href="{{ route('reports.tenant-ledger') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-book"></i></div>
        <div class="card-title">Tenant Ledger</div>
        <div class="card-subtitle">Complete transaction history for one tenant — every bill, payment, and note in date order, with a running balance after each one.</div>
    </a>

    <a href="{{ route('reports.tenant-ageing') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="card-title">Tenant Ageing</div>
        <div class="card-subtitle">The same outstanding bills for one tenant, split into how overdue each one is: under 60 days, 60–120 days, and over 120 days.</div>
    </a>

    <a href="{{ route('reports.group-ageing') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-table-list"></i></div>
        <div class="card-title">Group Outstanding (Ageing)</div>
        <div class="card-subtitle">One row per tenant with an outstanding balance, in the same ageing buckets, with a grand total across everyone.</div>
    </a>

    <a href="{{ route('reports.financial-summary') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-chart-pie"></i></div>
        <div class="card-title">Tenant Financial Summary</div>
        <div class="card-subtitle">One row per tenant for a date range &mdash; opening balance carried in, amount billed and received in the period, and the resulting net balance.</div>
    </a>

    <a href="{{ route('reports.profit-loss') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-scale-balanced"></i></div>
        <div class="card-title">Profit &amp; Loss</div>
        <div class="card-subtitle">Rent, utilities and EWA cash collected against maintenance costs and unrecovered EWA charges — per building, per tenant, or across everything.</div>
    </a>

    <a href="{{ route('reports.rent-schedule') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="card-title">Rent Payment Schedule</div>
        <div class="card-subtitle">Month-by-month for one tenant — which months were paid in full, which were only partly paid, and which were never invoiced at all.</div>
    </a>

    <a href="{{ route('reports.collection') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-receipt"></i></div>
        <div class="card-title">Collection Report</div>
        <div class="card-subtitle">Every rent and EWA payment received in a date range &mdash; receipt no, cheque details, tenant, and amount.</div>
    </a>

    <a href="{{ route('reports.vat-return') }}" class="card is-interactive is-accent is-roomy">
        <div class="card-header-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="card-title">VAT Return</div>
        <div class="card-subtitle">Every rent invoice and EWA bill for a property and date range, in the exact column format the quarterly VAT filing needs — export straight to XLSX.</div>
    </a>
</div>

<div class="m-hide-desktop-index" style="margin-top:20px;font-size:12px;color:var(--text-muted);max-width:640px">
    <i class="fa-solid fa-circle-info" style="margin-right:5px"></i>
    Draft reports — "On Account" credit balances and post-dated cheques aren't tracked in the system yet, so those columns aren't included.
</div>

@endsection
