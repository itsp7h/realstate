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
    .status-badge.draft          { background: var(--tone-neutral-bg); color: var(--tone-neutral-fg); }
    .status-badge.issued         { background: var(--tone-info-bg); color: var(--tone-info-fg); }
    .status-badge.partially_paid { background: var(--tone-warning-bg); color: var(--tone-warning-fg); }
    .status-badge.paid           { background: var(--tone-success-bg); color: var(--tone-success-fg); }
    .status-badge.overdue        { background: var(--tone-danger-bg); color: var(--tone-danger-fg); }
    .status-badge.cancelled      { background: #F8FAFC; color: #94A3B8; }
    .status-badge.expired        { background: var(--tone-danger-bg); color: var(--tone-danger-fg); }
    .status-badge.upcoming       { background: var(--tone-info-bg); color: var(--tone-info-fg); }
    .status-badge.expiring       { background: var(--tone-warning-bg); color: var(--tone-warning-fg); }
    .status-badge.active         { background: var(--tone-success-bg); color: var(--tone-success-fg); }

    .type-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600;
    }
    .type-badge.rent      { background: var(--tone-info-bg); color: var(--tone-info-fg); }
    .type-badge.utilities { background: var(--tone-warning-bg); color: var(--tone-warning-fg); }
    .type-badge.other     { background: var(--tone-neutral-bg); color: var(--tone-neutral-fg); }
    .type-badge.credit    { background: var(--tone-success-bg); color: var(--tone-success-fg); }
    .type-badge.debit     { background: var(--tone-warning-bg); color: var(--tone-warning-fg); }

    .rs-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 11px; border-radius: 20px; font-size: 11.5px; font-weight: 700; }
    .rs-status.paid          { background: var(--tone-success-bg); color: var(--tone-success-fg); }
    .rs-status.partial       { background: var(--tone-warning-bg); color: var(--tone-warning-fg); }
    .rs-status.unpaid        { background: var(--tone-danger-bg); color: var(--tone-danger-fg); }
    .rs-status.not_invoiced  { background: var(--tone-neutral-bg); color: var(--tone-neutral-fg); }

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
    .note-form-control.is-invalid { border-color: var(--tone-danger-border); }
    .note-invalid-feedback { font-size: 11px; color: var(--tone-danger-fg); margin-top: 3px; }

    .profile-hero { display: flex; align-items: center; gap: 22px; margin-bottom: 20px; flex-wrap: wrap; }
    .profile-avatar {
        width: 72px; height: 72px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800;
        flex-shrink: 0;
        border: 3px solid var(--card-border);
    }
    .profile-avatar.individual { background: var(--tone-success-bg); color: var(--success); border-color: var(--tone-success-border); }
    .profile-avatar.company    { background: var(--tone-info-bg); color: var(--info);    border-color: var(--tone-info-border); }
    .profile-name { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .profile-meta { display: flex; align-items: center; gap: 10px; margin-top: 6px; flex-wrap: wrap; }
    .profile-actions { margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap; }

    .detail-icon {
        width: 38px; height: 38px; border-radius: var(--radius-sm);
        background: var(--accent-dim); color: var(--accent);
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }

    /* ── Mobile tenant hero (Miknas Property Manager design) ──────
         Shown only on mobile, above the same tabs/content desktop uses
         below — adds the design's rent/lease summary + quick actions
         without removing any of the existing tab functionality. ── */
    .pm-tenant-hero { display: none; }
    @media (max-width: 768px) {
        .profile-hero { display: none; }
        .pm-tenant-hero {
            display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px;
        }
        .pm-tenant-card {
            background: var(--pm-navy); border-radius: 12px; padding: 18px;
            display: flex; align-items: center; gap: 14px;
        }
        .pm-tenant-avatar {
            flex: none; width: 52px; height: 52px; border-radius: 9999px; background: var(--pm-gold-tint);
            color: var(--pm-gold); font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 18px;
            display: flex; align-items: center; justify-content: center;
        }
        .pm-tenant-name { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 19px; color: #fff; }
        .pm-tenant-meta { font-size: 12px; color: var(--pm-navy-text); margin-top: 1px; }
        .pm-tenant-actions { display: flex; gap: 9px; }
        .pm-tenant-actions .pm-btn-gold {
            flex: 1; padding: 13px 0; border: 0; border-radius: 8px; background: var(--pm-gold); color: var(--pm-navy);
            font-size: 13.5px; font-weight: 700; cursor: pointer; text-decoration: none; text-align: center;
            box-shadow: 0 4px 16px var(--pm-gold-glow); font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .pm-tenant-actions .pm-btn-icon {
            flex: none; width: 48px; border: 1px solid var(--pm-border); border-radius: 8px; background: var(--pm-surface);
            color: var(--pm-text-2); font-size: 15px; cursor: pointer; text-decoration: none;
            display: flex; align-items: center; justify-content: center;
        }
    }
</style>

@php
    $mobileLease = $tenant->activeLease ?? $tenant->leaseContracts->first();
    $mobileRentStatus = $tenant->invoices->isEmpty()
        ? null
        : ($tenant->invoices->contains('status', 'overdue') ? 'overdue' : 'paid');
    $mobileStatusMeta = match ($mobileRentStatus) {
        'paid'    => ['label' => 'Paid',    'tint' => 'var(--pm-green-tint)', 'tone' => 'var(--pm-green-text)'],
        'overdue' => ['label' => 'Overdue', 'tint' => 'var(--pm-red-tint)',   'tone' => 'var(--pm-red)'],
        default   => ['label' => 'No invoices yet', 'tint' => 'var(--pm-page)', 'tone' => 'var(--pm-text-3)'],
    };
    $mobileOpenInvoice = $tenant->invoices->first(fn ($i) => in_array($i->status, ['issued', 'partially_paid', 'overdue'], true));
@endphp

{{-- MOBILE TENANT HERO --}}
<div class="pm-tenant-hero">
    <div class="pm-tenant-card detail-item">
        <div class="pm-tenant-avatar">{{ strtoupper(substr($tenant->name, 0, 2)) }}</div>
        <div style="flex:1;min-width:0;">
            <div class="pm-tenant-name">{{ $tenant->name }}</div>
            <div class="pm-tenant-meta">{{ $mobileLease?->property_code ?? 'No active lease' }}{{ $mobileLease?->unit ? ' · Unit '.$mobileLease->unit : '' }}</div>
        </div>
        <span style="padding:5px 10px;border-radius:9999px;font-size:10px;font-weight:700;background:{{ $mobileStatusMeta['tint'] }};color:{{ $mobileStatusMeta['tone'] }};flex-shrink:0;">{{ $mobileStatusMeta['label'] }}</span>
    </div>

    <div class="pm-kpi-grid">
        <div class="pm-kpi-card">
            <div class="pm-kpi-label">MONTHLY RENT</div>
            <div class="pm-kpi-value">{{ $mobileLease?->rent_per_month ? 'BHD '.number_format($mobileLease->rent_per_month, 0) : '—' }}</div>
        </div>
        <div class="pm-kpi-card">
            <div class="pm-kpi-label">LEASE ENDS</div>
            <div class="pm-kpi-value">{{ $mobileLease?->lease_end_date?->format('d M Y') ?? '—' }}</div>
        </div>
    </div>

    <div class="pm-tenant-actions">
        @if($mobileOpenInvoice)
            <a href="{{ route('invoices.show', $mobileOpenInvoice) }}" class="pm-btn-gold">Record rent payment</a>
        @elseif($mobileRentStatus === 'paid')
            <span class="pm-btn-gold" style="cursor:default;">Rent received</span>
        @else
            <span class="pm-btn-gold" style="opacity:.6;cursor:default;">No payment due</span>
        @endif
        @if($tenant->email)
            <a href="mailto:{{ $tenant->email }}" class="pm-btn-icon"><i class="fa-regular fa-comment"></i></a>
        @endif
        @if($tenant->phone)
            <a href="tel:{{ $tenant->phone }}" class="pm-btn-icon"><i class="fa-solid fa-phone"></i></a>
        @endif
    </div>

    @if($tenant->payments->isNotEmpty())
    <div>
        <div class="pm-section-label">PAYMENT HISTORY</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($tenant->payments->sortByDesc('payment_date')->take(6) as $payment)
                <div style="display:flex;align-items:center;gap:12px;background:var(--pm-surface);border:1px solid var(--pm-border);border-radius:12px;padding:12px 14px;">
                    <div style="flex:none;width:28px;height:28px;border-radius:9999px;background:var(--pm-green);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="pm-action-title">{{ $payment->payment_date->format('F Y') }}</div>
                        <div class="pm-action-sub">Paid &mdash; {{ str_replace('_', ' ', $payment->method) }}</div>
                    </div>
                    <div style="font-family:'Outfit',sans-serif;font-weight:700;font-size:14px;color:var(--pm-text);flex-shrink:0;">BHD {{ number_format($payment->amount, 0) }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- PROFILE HERO --}}
<div class="card is-hero profile-hero">
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
        <i class="fa-solid fa-address-card detail-item"></i> Overview
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

    <div class="card detail-item">
        <div class="detail-icon"><i class="fa-solid fa-id-card detail-item"></i></div>
        <div>
            <div class="detail-label">ID / CR Number</div>
            <div class="detail-value {{ $tenant->id_cr_number ? '' : 'is-empty' }}">
                {{ $tenant->id_cr_number ?? 'Not provided' }}
            </div>
        </div>
    </div>

    <div class="card detail-item">
        <div class="detail-icon"><i class="fa-solid fa-phone"></i></div>
        <div>
            <div class="detail-label">Phone</div>
            <div class="detail-value {{ $tenant->phone ? '' : 'is-empty' }}">
                @if($tenant->phone)
                    <a href="tel:{{ $tenant->phone }}">{{ $tenant->phone }}</a>
                @else
                    Not provided
                @endif
            </div>
        </div>
    </div>

    <div class="card detail-item">
        <div class="detail-icon" style="background:var(--tone-info-bg);color:var(--info);"><i class="fa-solid fa-envelope"></i></div>
        <div>
            <div class="detail-label">Email Address</div>
            <div class="detail-value {{ $tenant->email ? '' : 'is-empty' }}">
                @if($tenant->email)
                    <a href="mailto:{{ $tenant->email }}">{{ $tenant->email }}</a>
                @else
                    Not provided
                @endif
            </div>
        </div>
    </div>

    <div class="card detail-item">
        <div class="detail-icon" style="background:var(--tone-success-bg);color:var(--success);"><i class="fa-solid fa-earth-americas"></i></div>
        <div>
            <div class="detail-label">Nationality / Country</div>
            <div class="detail-value {{ $tenant->nationality_country ? '' : 'is-empty' }}">
                {{ $tenant->nationality_country ?? 'Not provided' }}
            </div>
        </div>
    </div>

    <div class="card detail-item">
        <div class="detail-icon" style="background:var(--tone-warning-bg);color:var(--tone-warning-fg);"><i class="fa-solid fa-location-dot"></i></div>
        <div>
            <div class="detail-label">Address</div>
            <div class="detail-value {{ $tenant->address ? '' : 'is-empty' }}">
                {{ $tenant->address ?? 'Not provided' }}
            </div>
        </div>
    </div>

    <div class="card detail-item">
        <div class="detail-icon"><i class="fa-regular fa-calendar-plus"></i></div>
        <div>
            <div class="detail-label">Created At</div>
            <div class="detail-value">{{ $tenant->created_at->format('d M Y, H:i') }}</div>
        </div>
    </div>

    <div class="card detail-item">
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
<div class="table-card detail-item">
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
<div class="table-card detail-item">
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
<div class="table-card detail-item">
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
                <td class="right tp-money" style="color:var(--tone-success-fg)">{{ number_format($payment->amount, 3) }}</td>
                <td>{{ $payment->method_label }}</td>
                <td>
                    @if($payment->invoice)
                    <div style="display:flex;gap:6px">
                        <button type="button" class="btn btn-outline btn-sm" title="Preview Receipt"
                                onclick="openReceiptPdf('{{ route('invoices.payments.receipt.preview', [$payment->invoice, $payment]) }}', '{{ $payment->payment_number }}', '{{ route('invoices.payments.receipt', [$payment->invoice, $payment]) }}')">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <a href="{{ route('invoices.payments.receipt', [$payment->invoice, $payment]) }}" class="btn btn-outline btn-sm" title="Download Receipt" target="_blank">
                            <i class="fa-solid fa-file-arrow-down"></i>
                        </a>
                    </div>
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
<div class="table-card detail-item">
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
<div class="table-card detail-item">
    <div style="padding:14px 18px;border-bottom:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:16px">
            <div class="note-mini-stat">Total Credited <strong style="color:var(--tone-success-fg)">{{ number_format($totalCredited, 3) }}</strong></div>
            <div class="note-mini-stat">Total Debited <strong style="color:var(--tone-warning-fg)">{{ number_format($totalDebited, 3) }}</strong></div>
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

    <div class="note-form-card detail-item {{ $errors->any() ? 'open' : '' }}" id="tenantNoteFormCard">
        <form method="POST" action="{{ route('tenants.notes.store', $tenant) }}" novalidate>
            @csrf
            <div class="note-form-grid">
                <div class="form-group">
                    <label class="note-form-label">Type <span style="color:var(--tone-danger-fg)">*</span></label>
                    <select name="type" class="note-form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" required>
                        <option value="">— Select —</option>
                        <option value="credit" {{ old('type') === 'credit' ? 'selected' : '' }}>Credit Note (reduces balance)</option>
                        <option value="debit" {{ old('type') === 'debit' ? 'selected' : '' }}>Debit Note (increases balance)</option>
                    </select>
                    <div class="note-invalid-feedback">{{ $errors->first('type') }}</div>
                </div>
                <div class="form-group">
                    <label class="note-form-label">Amount (BHD) <span style="color:var(--tone-danger-fg)">*</span></label>
                    <input type="number" name="amount" class="note-form-control {{ $errors->has('amount') ? 'is-invalid' : '' }}"
                           value="{{ old('amount') }}" min="0.001" step="0.001" placeholder="0.000" required>
                    <div class="note-invalid-feedback">{{ $errors->first('amount') }}</div>
                </div>
                <div class="form-group">
                    <label class="note-form-label">Date <span style="color:var(--tone-danger-fg)">*</span></label>
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
                    <label class="note-form-label">Reason <span style="color:var(--tone-danger-fg)">*</span></label>
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
<div class="table-card detail-item">
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

{{-- RECEIPT PREVIEW MODAL --}}
<div class="pdf-viewer-overlay" id="receiptPdfModal" onclick="closeReceiptPdf(event)">
    <div class="pdf-viewer" onclick="event.stopPropagation()">
        <div class="pdf-viewer-header">
            <i class="fa-solid fa-file-pdf" style="color:var(--accent);font-size:16px"></i>
            <span id="receiptPdfTitle"></span>
            <a id="receiptPdfDownloadLink" href="#" class="btn btn-outline btn-sm" download>
                <i class="fa-solid fa-download"></i> Download
            </a>
            <button type="button" class="btn btn-outline btn-sm" onclick="closeReceiptPdfBtn()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <iframe id="receiptPdfFrame" class="pdf-viewer-frame" src="about:blank"></iframe>
    </div>
</div>

<script>
window.openReceiptPdf = function (previewUrl, title, downloadUrl) {
    document.getElementById('receiptPdfTitle').textContent = title;
    document.getElementById('receiptPdfFrame').src = previewUrl;
    document.getElementById('receiptPdfDownloadLink').href = downloadUrl || previewUrl;
    document.getElementById('receiptPdfModal').classList.add('open');
};
window.closeReceiptPdf = function (e) {
    if (e.target === document.getElementById('receiptPdfModal')) closeReceiptPdfBtn();
};
window.closeReceiptPdfBtn = function () {
    document.getElementById('receiptPdfModal').classList.remove('open');
    document.getElementById('receiptPdfFrame').src = 'about:blank';
};
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('receiptPdfModal').classList.contains('open')) {
        closeReceiptPdfBtn();
    }
});
</script>

<script>
(function () {
    // Runs whether this fragment is loaded as a full page or injected into
    // the tenants-index popup modal — `tenantProfileModal` only exists on
    // the index page, so its presence tells us which context we're in.
    const inModal = !!document.getElementById('tenantProfileModal');

    window.switchTab = function (tab) {
        const root = inModal ? document.getElementById('tenantProfileBody') : document;
        root.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        root.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        root.querySelector('#tab-' + tab).classList.add('active');
        root.querySelector('#panel-' + tab).classList.add('active');
        if (!inModal) {
            history.replaceState(null, '', '?tab=' + tab);
        }
    };

    const validTabs = ['overview', 'leases', 'invoices', 'payments', 'ewa', 'notes', 'ledger'];
    const urlTab = inModal ? null : new URLSearchParams(window.location.search).get('tab');
    switchTab(validTabs.includes(urlTab) ? urlTab : 'overview');
})();
</script>
