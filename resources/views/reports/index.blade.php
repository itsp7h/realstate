@extends('layouts.admin')

@section('title', 'Reports')
@section('topbar-title', 'Reports')

@push('styles')
<style>
    /* Library card. The CTA is a visual affordance inside the card's own link,
       not a second control — the whole card is the <a>, so there is one tab
       stop and one accessible name per report. margin-top:auto against
       .card-body.is-stack pins it to the card's foot, so the row of buttons
       lands on one line however long a description runs. Layout only; the
       chrome is .btn.btn-outline.btn-sm from §4.1. */
    .rl-cta { margin-top: auto; align-self: flex-start; }
</style>
@endpush

@section('content')

@section('page-title', 'Reports')
@section('page-subtitle', 'Tenant statements, accounts-receivable ageing, and profit & loss')

@php
    $reports = [
        ['route' => 'reports.tenant-statement',    'icon' => 'fa-file-invoice',        'bg' => 'var(--m-gold-tint)',  'fg' => 'var(--m-gold-text)', 'name' => 'Tenant Statement',          'desc' => 'Bill-wise statement of outstanding rent invoices & EWA bills for one tenant.'],
        ['route' => 'reports.bill-wise-statement', 'icon' => 'fa-list-check',          'bg' => 'var(--m-blue-tint)',  'fg' => 'var(--m-blue)',      'name' => 'Bill-wise Statement',       'desc' => 'One row per outstanding bill — opening amount, balance, due date, days overdue.'],
        ['route' => 'reports.tenant-ledger',       'icon' => 'fa-book',                'bg' => 'var(--m-purple-tint)','fg' => 'var(--m-purple)',    'name' => 'Tenant Ledger',             'desc' => 'Complete transaction history for one tenant with a running balance.'],
        ['route' => 'reports.tenant-ageing',       'icon' => 'fa-hourglass-half',      'bg' => 'var(--m-green-tint)', 'fg' => 'var(--m-green)',     'name' => 'Tenant Ageing',             'desc' => 'Outstanding bills split into under 60 / 60–120 / over 120 day buckets.'],
        ['route' => 'reports.group-ageing',        'icon' => 'fa-table-list',          'bg' => 'var(--m-blue-tint)',  'fg' => 'var(--m-blue)',      'name' => 'Group Outstanding (Ageing)','desc' => 'One row per tenant with an outstanding balance, plus a grand total.'],
        ['route' => 'reports.financial-summary',   'icon' => 'fa-chart-pie',           'bg' => 'var(--m-purple-tint)','fg' => 'var(--m-purple)',    'name' => 'Tenant Financial Summary',  'desc' => 'Opening balance, billed and received amounts, and net balance per tenant.'],
        ['route' => 'reports.profit-loss',         'icon' => 'fa-scale-balanced',      'bg' => 'var(--m-gold-tint)',  'fg' => 'var(--m-gold-text)', 'name' => 'Profit & Loss',             'desc' => 'Rent, utilities & EWA collected against maintenance costs and unrecovered EWA.'],
        ['route' => 'reports.rent-schedule',       'icon' => 'fa-calendar-check',      'bg' => 'var(--m-green-tint)', 'fg' => 'var(--m-green)',     'name' => 'Rent Payment Schedule',     'desc' => 'Month-by-month paid / partly paid / never invoiced status for one tenant.'],
        ['route' => 'reports.collection',          'icon' => 'fa-receipt',             'bg' => 'var(--m-blue-tint)',  'fg' => 'var(--m-blue)',      'name' => 'Collection Report',         'desc' => 'Every rent & EWA payment received in a date range, with receipt details.'],
        ['route' => 'reports.vat-return',          'icon' => 'fa-file-invoice-dollar', 'bg' => 'var(--m-gold-tint)',  'fg' => 'var(--m-gold-text)', 'name' => 'VAT Return',                'desc' => 'Rent invoices & EWA bills in the exact format the quarterly VAT filing needs.'],
    ];
@endphp

{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════ --}}
<div class="m-screen">
    <div class="m-row-list">
        @foreach($reports as $r)
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
    <div style="font-size:.8rem;color:var(--ps-muted-deep);line-height:1.65;padding:0 4px;">
        <i class="fa-solid fa-circle-info"></i>
        Draft reports — "On Account" credit balances and post-dated cheques aren't tracked yet, so those columns aren't included.
    </div>
</div>

{{-- ═══════════════════════ DESKTOP — archetype E · library ═══════════════════════ --}}
<div class="card-grid is-4 m-hide-desktop-index">
    @foreach($reports as $r)
        <a href="{{ route($r['route']) }}" class="card is-interactive is-accent">
            <div class="card-body is-stack">
                <div class="card-header-icon"><i class="fa-solid {{ $r['icon'] }}"></i></div>
                <div>
                    <div class="card-title">{{ $r['name'] }}</div>
                    <div class="card-subtitle">{{ $r['desc'] }}</div>
                </div>
                <span class="btn btn-outline btn-sm rl-cta">Open report <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>
    @endforeach
</div>

<div class="alert alert-info m-hide-desktop-index">
    <i class="fa-solid fa-circle-info"></i>
    <span>Draft reports — &ldquo;On Account&rdquo; credit balances and post-dated cheques aren&rsquo;t tracked in the system yet, so those columns aren&rsquo;t included.</span>
</div>

@endsection
