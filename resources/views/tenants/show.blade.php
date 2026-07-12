@extends('layouts.admin')

@section('title', $tenant->name . ' — Tenant Profile')
@section('topbar-title', 'Tenant Profile')

@push('styles')
<style>
    .tab-bar {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid var(--card-border);
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .tab-btn {
        padding: 11px 20px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        border: none;
        background: none;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color 0.18s, border-color 0.18s;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .tab-btn:hover { color: var(--text-primary); }
    .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
    .tab-btn .tab-badge {
        background: var(--accent-dim);
        color: var(--accent);
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 20px;
        min-width: 18px;
        text-align: center;
    }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    .table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; overflow-x: auto; }
    .tp-empty { text-align: center; padding: 50px 20px; color: var(--text-muted); }
    .tp-empty i { font-size: 32px; display: block; margin-bottom: 10px; opacity: 0.3; }
    .tp-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 640px; }
    .tp-table th { text-align: left; padding: 10px 14px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); background: var(--page-bg); }
    .tp-table th.right, .tp-table td.right { text-align: right; }
    .tp-table td { padding: 9px 14px; border-bottom: 1px solid var(--card-border); }
    .tp-table tr:last-child td { border-bottom: none; }
    .tp-table tr[data-href] { cursor: pointer; }
    .tp-table tr.total-row td { background: var(--page-bg); font-weight: 700; border-top: 1.5px solid var(--card-border); }
    .tp-money { font-family: 'Outfit', sans-serif; font-weight: 700; }
    .tp-link { color: var(--accent); text-decoration: none; font-weight: 700; }
    .tp-link:hover { text-decoration: underline; }

    .status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
    }
    .status-badge.draft          { background: #F1F5F9; color: #64748B; }
    .status-badge.issued         { background: #EFF6FF; color: #2563EB; }
    .status-badge.partially_paid { background: #FFFBEB; color: #D97706; }
    .status-badge.paid           { background: #ECFDF5; color: #059669; }
    .status-badge.overdue        { background: #FEF2F2; color: #DC2626; }
    .status-badge.cancelled      { background: #F8FAFC; color: #94A3B8; }
    .status-badge.expired        { background: #FEF2F2; color: #DC2626; }
    .status-badge.upcoming       { background: #EFF6FF; color: #2563EB; }
    .status-badge.expiring       { background: #FFFBEB; color: #D97706; }
    .status-badge.active         { background: #ECFDF5; color: #059669; }

    .type-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;
    }
    .type-badge.rent      { background: #EFF6FF; color: #2563EB; }
    .type-badge.utilities { background: #FFF7ED; color: #EA580C; }
    .type-badge.other     { background: #F1F5F9; color: #64748B; }
    .type-badge.credit    { background: #ECFDF5; color: #059669; }
    .type-badge.debit     { background: #FFFBEB; color: #D97706; }

    .rs-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
    .rs-status.paid          { background: #ECFDF5; color: #059669; }
    .rs-status.partial       { background: #FFFBEB; color: #D97706; }
    .rs-status.unpaid        { background: #FEF2F2; color: #DC2626; }
    .rs-status.not_invoiced  { background: #F1F5F9; color: #64748B; }

    /* Credit/Debit note mini-stats + issue form */
    .note-mini-stat { font-size: 11px; color: var(--text-muted); }
    .note-mini-stat strong { font-family: 'Outfit',sans-serif; font-weight: 700; }
    .note-form-card { background: var(--page-bg); border-top: 1px solid var(--card-border); padding: 16px 18px; display: none; }
    .note-form-card.open { display: block; }
    .note-form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; align-items: end; }
    @media (max-width: 820px) { .note-form-grid { grid-template-columns: 1fr; } }
    .note-form-grid .form-group { display: flex; flex-direction: column; gap: 5px; grid-column: span 1; }
    .note-form-grid .reason-group { grid-column: 1 / -1; }
    .note-form-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .note-form-control {
        padding: 8px 12px; font-size: 13px; border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
        background: var(--input-bg); color: var(--text-primary); outline: none; width: 100%; box-sizing: border-box;
    }
    .note-form-control:focus { border-color: var(--accent); }
    .note-form-control.is-invalid { border-color: #DC2626; }
    .note-invalid-feedback { font-size: 11px; color: #DC2626; margin-top: 3px; }

    .profile-hero {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        padding: 28px;
        display: flex;
        align-items: center;
        gap: 22px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .profile-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800;
        flex-shrink: 0;
        border: 3px solid var(--card-border);
    }
    .profile-avatar.individual { background: #ECFDF5; color: var(--success); border-color: #A7F3D0; }
    .profile-avatar.company    { background: #EFF6FF; color: var(--info);    border-color: #BFDBFE; }
    .profile-name { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .profile-meta { display: flex; align-items: center; gap: 10px; margin-top: 6px; flex-wrap: wrap; }
    .profile-actions { margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap; }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }
    .detail-item {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: var(--radius);
        padding: 18px 20px;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }
    .detail-icon {
        width: 38px; height: 38px; border-radius: var(--radius-sm);
        background: var(--accent-dim); color: var(--accent);
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }
    .detail-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 4px; }
    .detail-value { font-size: 14px; font-weight: 600; color: var(--text-primary); word-break: break-all; }
    .detail-value.empty { color: var(--text-muted); font-weight: 400; font-style: italic; }
    .detail-value a { color: var(--info); text-decoration: none; }
    .detail-value a:hover { text-decoration: underline; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb" id="tenantBreadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('tenants.index') }}">Tenants</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Profile</span>
        </div>
        <h1 class="page-header-title">Tenant Profile</h1>
        <p class="page-header-sub">Full details for this tenant record</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('tenants.index') }}" class="btn btn-outline" id="tenantBackBtn">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <button type="button" class="btn btn-outline" id="tenantCloseBtn" style="display:none" onclick="window.parent.closeTenantModalFromChild && window.parent.closeTenantModalFromChild()">
            <i class="fa-solid fa-xmark"></i> Close
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

{{-- PROFILE HERO --}}
<div class="profile-hero">
    <div class="profile-avatar {{ $tenant->tenant_type }}">
        {{ strtoupper(substr($tenant->name, 0, 1)) }}
    </div>
    <div>
        <div class="profile-name">{{ $tenant->name }}</div>
        <div class="profile-meta">
            @if($tenant->tenant_type === 'individual')
                <span class="badge badge-green"><i class="fa-solid fa-user"></i> Individual</span>
            @else
                <span class="badge badge-blue"><i class="fa-solid fa-building-user"></i> Company</span>
            @endif
            @if($tenant->tenant_code)
                <span class="badge badge-gold"><i class="fa-solid fa-hashtag"></i> {{ $tenant->tenant_code }}</span>
            @endif
            @if($tenant->nationality_country)
                <span class="badge badge-gray"><i class="fa-solid fa-earth-americas"></i> {{ $tenant->nationality_country }}</span>
            @endif
            <span style="font-size:12px;color:var(--text-muted);">
                <i class="fa-regular fa-clock"></i> Added {{ $tenant->created_at->format('d M Y') }}
            </span>
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-primary">
            <i class="fa-regular fa-pen-to-square"></i> Edit Profile
        </a>
        <form method="POST" action="{{ route('tenants.destroy', $tenant) }}"
              onsubmit="return confirm('Delete {{ addslashes($tenant->name) }}? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fa-regular fa-trash-can"></i> Delete
            </button>
        </form>
    </div>
</div>

{{-- TABS --}}
<div class="tab-bar">
    <button class="tab-btn" id="tab-overview" onclick="switchTab('overview')">
        <i class="fa-solid fa-address-card"></i> Overview
    </button>
    <button class="tab-btn" id="tab-leases" onclick="switchTab('leases')">
        <i class="fa-solid fa-file-contract"></i> Lease Contracts
        <span class="tab-badge">{{ $tenant->leaseContracts->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-invoices" onclick="switchTab('invoices')">
        <i class="fa-solid fa-file-invoice"></i> Invoices
        <span class="tab-badge">{{ $tenant->invoices->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-payments" onclick="switchTab('payments')">
        <i class="fa-solid fa-money-bill-transfer"></i> Payments &amp; Receipts
        <span class="tab-badge">{{ $tenant->payments->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-ewa" onclick="switchTab('ewa')">
        <i class="fa-solid fa-bolt"></i> EWA Bills
        <span class="tab-badge">{{ $tenant->ewaBills->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-notes" onclick="switchTab('notes')">
        <i class="fa-solid fa-file-invoice-dollar"></i> Credit &amp; Debit Notes
        <span class="tab-badge">{{ $tenant->invoiceNotes->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-ledger" onclick="switchTab('ledger')">
        <i class="fa-solid fa-calendar-check"></i> Rent Ledger
        <span class="tab-badge">{{ $rentSchedule->count() }}</span>
    </button>
</div>

{{-- ===================== OVERVIEW TAB ===================== --}}
<div class="tab-panel" id="panel-overview">
<div class="detail-grid">

    <div class="detail-item">
        <div class="detail-icon"><i class="fa-solid fa-id-card"></i></div>
        <div>
            <div class="detail-label">ID / CR Number</div>
            <div class="detail-value {{ $tenant->id_cr_number ? '' : 'empty' }}">
                {{ $tenant->id_cr_number ?? 'Not provided' }}
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon"><i class="fa-solid fa-phone"></i></div>
        <div>
            <div class="detail-label">Phone</div>
            <div class="detail-value {{ $tenant->phone ? '' : 'empty' }}">
                @if($tenant->phone)
                    <a href="tel:{{ $tenant->phone }}">{{ $tenant->phone }}</a>
                @else
                    Not provided
                @endif
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon" style="background:#EFF6FF;color:var(--info);"><i class="fa-solid fa-envelope"></i></div>
        <div>
            <div class="detail-label">Email Address</div>
            <div class="detail-value {{ $tenant->email ? '' : 'empty' }}">
                @if($tenant->email)
                    <a href="mailto:{{ $tenant->email }}">{{ $tenant->email }}</a>
                @else
                    Not provided
                @endif
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon" style="background:#ECFDF5;color:var(--success);"><i class="fa-solid fa-earth-americas"></i></div>
        <div>
            <div class="detail-label">Nationality / Country</div>
            <div class="detail-value {{ $tenant->nationality_country ? '' : 'empty' }}">
                {{ $tenant->nationality_country ?? 'Not provided' }}
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon" style="background:#FFF7ED;color:#EA580C;"><i class="fa-solid fa-location-dot"></i></div>
        <div>
            <div class="detail-label">Address</div>
            <div class="detail-value {{ $tenant->address ? '' : 'empty' }}">
                {{ $tenant->address ?? 'Not provided' }}
            </div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon"><i class="fa-regular fa-calendar-plus"></i></div>
        <div>
            <div class="detail-label">Created At</div>
            <div class="detail-value">{{ $tenant->created_at->format('d M Y, H:i') }}</div>
        </div>
    </div>

    <div class="detail-item">
        <div class="detail-icon"><i class="fa-regular fa-calendar-check"></i></div>
        <div>
            <div class="detail-label">Last Updated</div>
            <div class="detail-value">{{ $tenant->updated_at->format('d M Y, H:i') }}</div>
        </div>
    </div>

</div>
</div>

{{-- ===================== LEASE CONTRACTS TAB ===================== --}}
<div class="tab-panel" id="panel-leases">
<div class="table-card">
    @if($tenant->leaseContracts->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-file-contract"></i>No lease contracts on file for this tenant.</div>
    @else
    <table class="tp-table">
        <thead>
            <tr>
                <th>Agreement No.</th>
                <th>Property / Unit</th>
                <th>Lease Period</th>
                <th class="right">Rent / Month (BHD)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenant->leaseContracts as $contract)
            <tr data-href="{{ route('lease-contracts.show', $contract) }}" onclick="window.location=this.dataset.href">
                <td><span class="tp-link">{{ $contract->lease_agreement_no }}</span></td>
                <td>{{ $contract->property_name }}{{ $contract->unit ? ' / '.$contract->unit : '' }}</td>
                <td>{{ $contract->lease_start_date->format('d M Y') }} &rarr; {{ $contract->lease_end_date->format('d M Y') }}</td>
                <td class="right tp-money">{{ $contract->rent_per_month !== null ? number_format($contract->rent_per_month, 3) : '—' }}</td>
                <td><span class="status-badge {{ $contract->status }}">{{ ucfirst($contract->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
</div>

{{-- ===================== INVOICES TAB ===================== --}}
<div class="tab-panel" id="panel-invoices">
<div class="table-card">
    @if($tenant->invoices->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-file-invoice"></i>No invoices raised for this tenant yet.</div>
    @else
    <table class="tp-table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Type</th>
                <th>Date</th>
                <th class="right">Total (BHD)</th>
                <th class="right">Paid (BHD)</th>
                <th class="right">Balance (BHD)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenant->invoices as $invoice)
            <tr data-href="{{ route('invoices.show', $invoice) }}" onclick="window.location=this.dataset.href">
                <td><span class="tp-link">{{ $invoice->invoice_number }}</span></td>
                <td><span class="type-badge {{ $invoice->type }}">{{ $invoice->type_label }}</span></td>
                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                <td class="right tp-money">{{ number_format($invoice->total_incl_vat, 3) }}</td>
                <td class="right tp-money">{{ number_format($invoice->total_paid, 3) }}</td>
                <td class="right tp-money" style="color:{{ $invoice->balance_due > 0.001 ? '#DC2626' : '#059669' }}">{{ number_format($invoice->balance_due, 3) }}</td>
                <td><span class="status-badge {{ $invoice->status }}">{{ $invoice->status_label }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
</div>

{{-- ===================== PAYMENTS & RECEIPTS TAB ===================== --}}
<div class="tab-panel" id="panel-payments">
<div class="table-card">
    @if($tenant->payments->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-money-bill-transfer"></i>No payments recorded for this tenant yet.</div>
    @else
    <table class="tp-table">
        <thead>
            <tr>
                <th>Payment #</th>
                <th>Date</th>
                <th>Invoice #</th>
                <th class="right">Amount (BHD)</th>
                <th>Method</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenant->payments as $payment)
            <tr>
                <td style="font-weight:700">{{ $payment->payment_number }}</td>
                <td>{{ $payment->payment_date->format('d M Y') }}</td>
                <td>
                    @if($payment->invoice)
                    <a href="{{ route('invoices.show', $payment->invoice) }}" class="tp-link">{{ $payment->invoice->invoice_number }}</a>
                    @else
                    —
                    @endif
                </td>
                <td class="right tp-money" style="color:#059669">{{ number_format($payment->amount, 3) }}</td>
                <td>{{ $payment->method_label }}</td>
                <td>
                    @if($payment->invoice)
                    <a href="{{ route('invoices.payments.receipt', [$payment->invoice, $payment]) }}" class="btn btn-outline btn-sm" title="Download Receipt" target="_blank">
                        <i class="fa-solid fa-file-arrow-down"></i>
                    </a>
                    @else
                    —
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
</div>

{{-- ===================== EWA BILLS TAB ===================== --}}
<div class="tab-panel" id="panel-ewa">
<div class="table-card">
    @if($tenant->ewaBills->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-bolt"></i>No EWA bills on file for this tenant.</div>
    @else
    <table class="tp-table">
        <thead>
            <tr>
                <th>Bill #</th>
                <th>Billing Period</th>
                <th class="right">Total (BHD)</th>
                <th class="right">Tenant Portion (BHD)</th>
                <th class="right">Paid (BHD)</th>
                <th class="right">Balance (BHD)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenant->ewaBills as $bill)
            <tr data-href="{{ route('ewa-bills.show', $bill) }}" onclick="window.location=this.dataset.href">
                <td><span class="tp-link">{{ $bill->bill_number }}</span></td>
                <td>{{ $bill->billing_period ?: '—' }}</td>
                <td class="right tp-money">{{ number_format($bill->total_amount, 3) }}</td>
                <td class="right tp-money">{{ number_format($bill->effective_tenant_portion, 3) }}</td>
                <td class="right tp-money">{{ number_format($bill->total_paid, 3) }}</td>
                <td class="right tp-money" style="color:{{ $bill->balance_due > 0.001 ? '#DC2626' : '#059669' }}">{{ number_format($bill->balance_due, 3) }}</td>
                <td><span class="status-badge {{ $bill->status }}">{{ $bill->status_label }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
</div>

{{-- ===================== CREDIT & DEBIT NOTES TAB ===================== --}}
<div class="tab-panel" id="panel-notes">
@php
    $totalCredited = $tenant->invoiceNotes->where('type', 'credit')->sum('amount');
    $totalDebited  = $tenant->invoiceNotes->where('type', 'debit')->sum('amount');
@endphp
<div class="table-card">
    <div style="padding:14px 18px;border-bottom:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:16px">
            <div class="note-mini-stat">Total Credited <strong style="color:#059669">{{ number_format($totalCredited, 3) }}</strong></div>
            <div class="note-mini-stat">Total Debited <strong style="color:#D97706">{{ number_format($totalDebited, 3) }}</strong></div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('tenantNoteFormCard').classList.toggle('open')">
            <i class="fa-solid fa-plus"></i> Issue Note
        </button>
    </div>
    @if($tenant->invoiceNotes->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-file-invoice-dollar"></i>No credit or debit notes issued for this tenant.</div>
    @else
    <table class="tp-table">
        <thead>
            <tr>
                <th>Note #</th>
                <th>Type</th>
                <th>Invoice #</th>
                <th>Date</th>
                <th class="right">Amount (BHD)</th>
                <th>Reason</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenant->invoiceNotes as $note)
            <tr>
                <td style="font-weight:700">{{ $note->note_number }}</td>
                <td><span class="type-badge {{ $note->type }}">{{ $note->type_label }}</span></td>
                <td>
                    @if($note->invoice)
                    <a href="{{ route('invoices.show', $note->invoice) }}" class="tp-link">{{ $note->invoice->invoice_number }}</a>
                    @else
                    <span style="color:var(--text-muted)">General adjustment</span>
                    @endif
                </td>
                <td>{{ $note->note_date->format('d M Y') }}</td>
                <td class="right tp-money" style="color:{{ $note->type === 'credit' ? '#059669' : '#D97706' }}">{{ $note->type === 'credit' ? '−' : '+' }}{{ number_format($note->amount, 3) }}</td>
                <td style="color:var(--text-muted)">{{ $note->reason }}</td>
                <td>
                    @if(!$note->invoice)
                    <form method="POST" action="{{ route('tenants.notes.destroy', [$tenant, $note]) }}"
                          onsubmit="return confirm('Remove {{ $note->type_label }} {{ $note->note_number }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="note-form-card {{ $errors->any() ? 'open' : '' }}" id="tenantNoteFormCard">
        <form method="POST" action="{{ route('tenants.notes.store', $tenant) }}" novalidate>
            @csrf
            <div class="note-form-grid">
                <div class="form-group">
                    <label class="note-form-label">Type <span style="color:#DC2626">*</span></label>
                    <select name="type" class="note-form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" required>
                        <option value="">— Select —</option>
                        <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Credit Note (reduces balance)</option>
                        <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit Note (increases balance)</option>
                    </select>
                    <div class="note-invalid-feedback">{{ $errors->first('type') }}</div>
                </div>
                <div class="form-group">
                    <label class="note-form-label">Amount (BHD) <span style="color:#DC2626">*</span></label>
                    <input type="number" name="amount" class="note-form-control {{ $errors->has('amount') ? 'is-invalid' : '' }}"
                           value="{{ old('amount') }}" min="0.001" step="0.001" placeholder="0.000" required>
                    <div class="note-invalid-feedback">{{ $errors->first('amount') }}</div>
                </div>
                <div class="form-group">
                    <label class="note-form-label">Date <span style="color:#DC2626">*</span></label>
                    <input type="date" name="note_date" class="note-form-control {{ $errors->has('note_date') ? 'is-invalid' : '' }}"
                           value="{{ old('note_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                    <div class="note-invalid-feedback">{{ $errors->first('note_date') }}</div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width:100%">
                        <i class="fa-solid fa-circle-check"></i> Issue Note
                    </button>
                </div>
                <div class="form-group reason-group">
                    <label class="note-form-label">Reason <span style="color:#DC2626">*</span></label>
                    <input type="text" name="reason" class="note-form-control {{ $errors->has('reason') ? 'is-invalid' : '' }}"
                           value="{{ old('reason') }}" maxlength="500" placeholder="Why is this being issued?" required>
                    <div class="note-invalid-feedback">{{ $errors->first('reason') }}</div>
                </div>
            </div>
        </form>
    </div>
</div>
</div>

{{-- ===================== RENT LEDGER TAB ===================== --}}
<div class="tab-panel" id="panel-ledger">
<div class="table-card">
    @if($rentSchedule->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-calendar-check"></i>No rent-bearing lease contracts on file for this tenant.</div>
    @else
    <table class="tp-table">
        <thead>
            <tr>
                <th>Month</th>
                <th class="right">Invoiced (BHD)</th>
                <th class="right">Received (BHD)</th>
                <th class="right">Remaining (BHD)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rentSchedule as $row)
            <tr>
                <td style="font-weight:600">{{ $row['month']->format('F Y') }}</td>
                <td class="right tp-money">{{ number_format($row['invoiced'], 3) }}</td>
                <td class="right tp-money">{{ number_format($row['paid'], 3) }}</td>
                <td class="right tp-money" style="color:{{ $row['remaining'] > 0.001 ? '#DC2626' : '#059669' }}">{{ number_format($row['remaining'], 3) }}</td>
                <td>
                    <span class="rs-status {{ $row['status'] }}">
                        {{ match($row['status']) {
                            'paid'         => 'Received',
                            'partial'      => 'Partially Received',
                            'unpaid'       => 'Unpaid',
                            'not_invoiced' => 'Not Invoiced',
                        } }}
                    </span>
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td style="padding:12px 14px">Total</td>
                <td class="right tp-money" style="padding:12px 14px">{{ number_format($rentSchedule->sum('invoiced'), 3) }}</td>
                <td class="right tp-money" style="padding:12px 14px">{{ number_format($rentSchedule->sum('paid'), 3) }}</td>
                <td class="right tp-money" style="padding:12px 14px;color:{{ $rentSchedule->sum('remaining') > 0.001 ? '#DC2626' : '#059669' }}">{{ number_format($rentSchedule->sum('remaining'), 3) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif
    <div style="padding:14px 18px;border-top:1px solid var(--card-border)">
        <a href="{{ route('reports.rent-schedule', ['tenant_id' => $tenant->id]) }}" class="tp-link">
            <i class="fa-solid fa-file-pdf"></i> View full report / download PDF
        </a>
    </div>
</div>
</div>

@endsection

@push('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}

const urlTab = new URLSearchParams(window.location.search).get('tab');
const validTabs = ['overview', 'leases', 'invoices', 'payments', 'ewa', 'notes', 'ledger'];
switchTab(validTabs.includes(urlTab) ? urlTab : 'overview');

// ── Embedded-in-modal detection ───────────────────────────────
// When this page loads inside the tenants-index profile modal (an
// iframe), "Back"/the breadcrumb would navigate the iframe itself, not
// the outer page — swap them for a Close button that closes the modal.
if (window.self !== window.top) {
    document.getElementById('tenantBreadcrumb').style.display = 'none';
    document.getElementById('tenantBackBtn').style.display = 'none';
    document.getElementById('tenantCloseBtn').style.display = '';
}
</script>
@endpush
