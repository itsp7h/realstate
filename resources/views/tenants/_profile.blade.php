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
    .status-badge.cancelled      { background: var(--page-bg); color: var(--text-muted); }
    .status-badge.expired        { background: var(--tone-danger-bg); color: var(--tone-danger-fg); }
    .status-badge.upcoming       { background: var(--tone-info-bg); color: var(--tone-info-fg); }
    .status-badge.expiring       { background: var(--tone-warning-bg); color: var(--tone-warning-fg); }
    .status-badge.active         { background: var(--tone-success-bg); color: var(--tone-success-fg); }


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
    @media (max-width: 900px) { .note-form-grid { grid-template-columns: 1fr; } }
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
    .profile-avatar.individual { background: var(--tone-success-bg); color:var(--tone-success-fg); border-color: var(--tone-success-border); }
    .profile-avatar.company    { background: var(--tone-info-bg); color:var(--tone-info-fg);    border-color: var(--tone-info-border); }
    .profile-name { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
    .profile-meta { display: flex; align-items: center; gap: 10px; margin-top: 6px; flex-wrap: wrap; }
    .profile-actions { margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap; }

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
            background: var(--ps-placeholder); border-radius: var(--ps-r-card); padding: 20px;
            display: flex; align-items: center; gap: 15px;
        }
        .pm-tenant-avatar {
            flex: none; width: 52px; height: 52px; border-radius: var(--ps-r-pill); background: rgba(216,178,95,.16);
            color: var(--ps-gold); font-family: 'Poppins', system-ui, sans-serif; font-weight: 600; font-size: 1.15rem;
            display: flex; align-items: center; justify-content: center;
        }
        .pm-tenant-name { font-family: 'Poppins', system-ui, sans-serif; font-weight: 700; font-size: 1.35rem; line-height: 1.25; letter-spacing: -.01em; color: var(--ps-ink-inverse); }
        .pm-tenant-meta { font-size: .8rem; color: var(--ps-navy-text); margin-top: 3px; }
        .pm-tenant-actions { display: flex; align-items: center; gap: 12px; }
        /* The one filled button; the icon action beside it is a quiet
           bordered square, not a second button weight. */
        .pm-tenant-actions .pm-btn-gold {
            flex: 1; min-height: var(--ps-h-btn); border: 0; border-radius: var(--ps-r-btn);
            background: var(--ps-btn-grad); color: var(--ps-navy);
            font-size: .95rem; font-weight: 600; letter-spacing: .03em; cursor: pointer;
            text-decoration: none; display: flex; align-items: center; justify-content: center;
            box-shadow: var(--ps-btn-shadow); font-family: 'Poppins', system-ui, sans-serif;
            transition: transform .12s ease, box-shadow .18s ease;
        }
        .pm-tenant-actions .pm-btn-gold:hover { transform: translateY(-1px); }
        .pm-tenant-actions .pm-btn-gold:active { transform: translateY(1px); box-shadow: 0 4px 12px rgba(202,161,79,.28); }
        .pm-tenant-actions .pm-btn-icon {
            flex: none; width: var(--ps-h-btn); min-height: var(--ps-h-btn);
            border: 1px solid var(--ps-border); border-radius: var(--ps-r-btn); background: var(--ps-surface);
            color: var(--ps-navy); font-size: 15px; cursor: pointer; text-decoration: none;
            display: flex; align-items: center; justify-content: center;
        }
    }

    /* The phone-length tab label is the hidden half of the pair until the
       phone breakpoint swaps them. */
    .tp-tab-narrow { display: none; }

    /* ── The mobile app layer (≤768px) ────────────────────────────
         Two fixes, both about the screen's edges.

         Scoped to body.is-pushed-screen — the class the layout puts on
         the tenant/building detail routes — because this same partial is
         also injected into the tenants-index profile modal, which brings
         its own padding and must not get a second helping.

         1. The gutter. body.is-mobile-screen zeroes .page-content's
            padding so each mobile screen can own its own; the .m-screen
            pages set 18px, but this page never did, so its sections ran
            to the viewport edge. app-core's bare .card carries no padding
            of its own either (it expects a .card-header/.card-body
            inside), so the overview cards had their labels hard against
            — and past — that edge. Both are fixed here.

         2. The tab bar. Seven tabs are ~1010px of row; on any phone width
            they wrapped into a four-row block taller than the hero above
            it. It becomes one thumb-scrolled row instead, the way every
            other chip row in the mobile app already behaves — the
            half-visible tab at the edge is the affordance that there is
            more to the right.

         The breakpoint is 768px, not 640px, because that is where the
         mobile layer itself begins: .page-content loses its padding and
         the phone hero appears at 768, so anything narrower than that
         needs these two fixes, not just phone widths. ── */
    @media (max-width: 768px) {
        body.is-pushed-screen .pm-tenant-hero,
        body.is-pushed-screen .tab-panel {
            padding-left: 18px;
            padding-right: 18px;
            box-sizing: border-box;
        }

        /* One row, scrolled by thumb. The rule below it belongs to the
           container, not the row, so it stays put while the tabs move. */
        body.is-pushed-screen .tp-tabs {
            flex-wrap: nowrap;
            gap: 4px;
            padding: 0 18px;
            margin-bottom: 18px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            /* A hairline on a phone, not the desktop bar's 2px. */
            border-bottom-width: 1px;
            border-bottom-color: var(--ps-border-soft);
        }
        body.is-pushed-screen .tp-tabs::-webkit-scrollbar { display: none; }

        body.is-pushed-screen .tp-tabs .tab-btn {
            flex: none;
            white-space: nowrap;
            gap: 7px;
            padding: 12px 14px;
            font-weight: 700;
            color: var(--ps-muted-deep);
            /* The desktop tab pulls itself down over the bar's rule to sit
               flush with it. Here the bar is a scroll container, and
               overflow-x:auto clips vertically too — that -2px took the
               active tab's gold indicator with it. The indicator sits
               directly above the hairline instead. */
            margin-bottom: 0;
        }
        body.is-pushed-screen .tp-tabs .tab-btn.active {
            color: var(--ps-gold-text-deep);
            font-weight: 800;
            border-bottom: 2.5px solid var(--ps-gold-dark);
        }
        /* A count, not a status — the quiet track grey rather than the gold
           tint app-core gives it, so it reads as metadata beside a label
           that is itself gold when active. */
        body.is-pushed-screen .tp-tabs .tab-badge {
            background: var(--ps-track);
            color: var(--ps-muted-deep);
            border-radius: var(--ps-r-pill);
            padding: 1px 8px;
            font-size: 12px;
        }
    }

    /* ── Phone only (≤600px) ──────────────────────────────────────
         Below this width the long tab labels stop being affordable and
         app-core's auto-fill detail grid is down to a single column,
         which makes eight one-line facts an eight-screen scroll.

         600px, not the 640px the brief asked for: app-core §1.0 fixes the
         breakpoint scale at 430/600/768/900/1200/1400 and
         UiConsistencyTest fails any view that invents one in between, so
         a phone rule goes on the phone breakpoint the rest of the app
         already folds at. ── */
    @media (max-width: 600px) {
        body.is-pushed-screen .tp-tabs .tp-tab-wide { display: none; }
        body.is-pushed-screen .tp-tabs .tp-tab-narrow { display: inline; }
    }
</style>

@php
    $mobileLease = $tenant->activeLease ?? $tenant->leaseContracts->first();
    $mobileRentStatus = $tenant->invoices->isEmpty()
        ? null
        : ($tenant->invoices->contains('status', 'overdue') ? 'overdue' : 'paid');
    $mobileStatusMeta = match ($mobileRentStatus) {
        'paid'    => ['label' => 'Paid',    'tint' => 'var(--ps-success-bg)', 'tone' => 'var(--ps-success)'],
        'overdue' => ['label' => 'Overdue', 'tint' => 'var(--ps-danger-bg)',  'tone' => 'var(--ps-danger)'],
        default   => ['label' => 'No invoices yet', 'tint' => 'var(--ps-bg)', 'tone' => 'var(--ps-muted-deep)'],
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
        <span style="padding:5px 11px;border-radius:99px;font-size:.625rem;font-weight:600;letter-spacing:.04em;background:{{ $mobileStatusMeta['tint'] }};color:{{ $mobileStatusMeta['tone'] }};flex-shrink:0;">{{ $mobileStatusMeta['label'] }}</span>
    </div>

    {{-- The shared stat strip, not a two-up of gold-topped KPI cards: this
         screen shows two figures, and figures have one component. --}}
    <div class="ps-stat-strip">
        <div class="ps-stat">
            <div class="ps-stat-value">{{ $mobileLease?->rent_per_month ? number_format($mobileLease->rent_per_month, 0) : '—' }}</div>
            <div class="ps-stat-label">Rent BHD / mo</div>
        </div>
        <div class="ps-stat">
            <div class="ps-stat-value is-word">{{ $mobileLease?->lease_end_date?->format('d M Y') ?? '—' }}</div>
            <div class="ps-stat-label">Lease ends</div>
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
        <div class="pm-section-label">Payment history</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($tenant->payments->sortByDesc('payment_date')->take(6) as $payment)
                <div style="display:flex;align-items:center;gap:13px;background:var(--ps-surface);border:1px solid var(--ps-border);border-radius:16px;padding:13px 15px;box-shadow:var(--ps-card-shadow);">
                    <div style="flex:none;width:30px;height:30px;border-radius:99px;background:var(--ps-success-bg);color:var(--ps-success);display:flex;align-items:center;justify-content:center;font-size:12px;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="pm-action-title">{{ $payment->payment_date->format('F Y') }}</div>
                        <div class="pm-action-sub">Paid &mdash; {{ str_replace('_', ' ', $payment->method) }}</div>
                    </div>
                    <div style="font-family:'Poppins',system-ui,sans-serif;font-weight:700;font-size:1rem;color:var(--ps-navy);flex-shrink:0;">BHD {{ number_format($payment->amount, 0) }}</div>
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
<div class="tab-bar tp-tabs">
    <button class="tab-btn" id="tab-overview" onclick="switchTab('overview')">
        <i class="fa-solid fa-address-card"></i> Overview
    </button>
    <button class="tab-btn" id="tab-leases" onclick="switchTab('leases')">
        <i class="fa-solid fa-file-contract"></i>
        <span class="tp-tab-wide">Lease Contracts</span><span class="tp-tab-narrow">Contracts</span>
        <span class="tab-badge">{{ $tenant->leaseContracts->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-invoices" onclick="switchTab('invoices')">
        <i class="fa-solid fa-file-invoice"></i> Invoices
        <span class="tab-badge">{{ $tenant->invoices->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-payments" onclick="switchTab('payments')">
        <i class="fa-solid fa-money-bill-transfer"></i>
        <span class="tp-tab-wide">Payments &amp; Receipts</span><span class="tp-tab-narrow">Payments</span>
        <span class="tab-badge">{{ $tenant->payments->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-ewa" onclick="switchTab('ewa')">
        <i class="fa-solid fa-bolt"></i> EWA Bills
        <span class="tab-badge">{{ $tenant->ewaBills->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-notes" onclick="switchTab('notes')">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span class="tp-tab-wide">Credit &amp; Debit Notes</span><span class="tp-tab-narrow">Credit Notes</span>
        <span class="tab-badge">{{ $tenant->invoiceNotes->count() }}</span>
    </button>
    <button class="tab-btn" id="tab-ledger" onclick="switchTab('ledger')">
        <i class="fa-solid fa-calendar-check"></i>
        <span class="tp-tab-wide">Rent Ledger</span><span class="tp-tab-narrow">Ledger</span>
        <span class="tab-badge">{{ $rentSchedule->count() }}</span>
    </button>
</div>

{{-- ===================== OVERVIEW TAB =====================
     Was seven .card.detail-item blocks in a grid, five of them reading
     "Not provided" in italic grey beside a tinted icon tile. On a tenant
     with nothing but a name that is a whole screen spent announcing
     absence, in three accent colours that encode nothing.

     Two compact cards now, on the shared .kv-card (app-core §4.3c). What
     is on file reads as a value; what is not offers to be filled. The
     Add links carry ?focus=<field>, which the edit form uses to put the
     cursor in the field the reader tapped rather than at the top of a
     form they have to re-scan. ── --}}
<div class="tab-panel" id="panel-overview">
@php
    $tpEdit  = route('tenants.edit', $tenant);
    /* One link per field, so a tap lands on the field it came from. */
    $tpFill  = fn (string $field) => route('tenants.edit', [$tenant, 'focus' => $field]);
    /* Nationality and address share a row, so it needs both halves: the
       parts that are filled, and whether anything is still missing. */
    $tpWhere = array_values(array_filter([$tenant->nationality_country, $tenant->address]));
@endphp
<div class="kv-stack">

    <x-kv-card
        title="Contact & ID"
        :edit="$tpEdit"
        :rows="[
            ['icon' => 'fa-phone', 'label' => 'Phone',
             'value' => $tenant->phone,
             'href'  => $tenant->phone ? 'tel:'.$tenant->phone : null,
             'add'   => $tenant->phone ? null : $tpFill('phone')],
            ['icon' => 'fa-envelope', 'label' => 'Email',
             'value' => $tenant->email,
             'href'  => $tenant->email ? 'mailto:'.$tenant->email : null,
             'add'   => $tenant->email ? null : $tpFill('email')],
            ['icon' => 'fa-id-card', 'label' => 'ID / CR number',
             'value' => $tenant->id_cr_number,
             'add'   => $tenant->id_cr_number ? null : $tpFill('id_cr_number')],
        ]" />

    <x-kv-card
        :rows="[
            ['icon' => 'fa-location-dot', 'label' => 'Nationality · Address',
             'value' => implode(' · ', $tpWhere),
             'add'   => count($tpWhere) === 2 ? null : $tpFill($tenant->nationality_country ? 'address' : 'nationality_country')],
        ]"
        :meta="[
            ['label' => 'Created', 'value' => $tenant->created_at->format('d M Y')],
            ['label' => 'Updated', 'value' => $tenant->updated_at->format('d M Y')],
        ]" />

</div>
</div>

{{-- ===================== LEASE CONTRACTS TAB ===================== --}}
<div class="tab-panel" id="panel-leases">
<div class="table-card detail-item">
    @if($tenant->leaseContracts->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-file-contract"></i>No lease contracts on file for this tenant.</div>
    @else
    <div class="table-wrap">
        <table>
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
                    <td data-label="Agreement No."><span class="tp-link">{{ $contract->lease_agreement_no }}</span></td>
                    <td data-label="Property / Unit">{{ $contract->property_name }}{{ $contract->unit ? ' / '.$contract->unit : '' }}</td>
                    <td data-label="Lease Period">{{ $contract->lease_start_date->format('d M Y') }} &rarr; {{ $contract->lease_end_date->format('d M Y') }}</td>
                    <td data-label="Rent / Month (BHD)" class="right tp-money">{{ $contract->rent_per_month !== null ? number_format($contract->rent_per_month, 3) : '—' }}</td>
                    <td data-label="Status"><span class="status-badge {{ $contract->status }}">{{ ucfirst($contract->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
</div>

{{-- ===================== INVOICES TAB ===================== --}}
<div class="tab-panel" id="panel-invoices">
<div class="table-card detail-item">
    @if($tenant->invoices->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-file-invoice"></i>No invoices raised for this tenant yet.</div>
    @else
    <div class="table-wrap">
        <table>
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
                    <td data-label="Invoice #"><span class="tp-link">{{ $invoice->invoice_number }}</span></td>
                    <td data-label="Type"><span class="status-badge {{ $invoice->type }}">{{ $invoice->type_label }}</span></td>
                    <td data-label="Date">{{ $invoice->invoice_date->format('d M Y') }}</td>
                    <td data-label="Total (BHD)" class="right tp-money">{{ number_format($invoice->total_incl_vat, 3) }}</td>
                    <td data-label="Paid (BHD)" class="right tp-money">{{ number_format($invoice->total_paid, 3) }}</td>
                    <td data-label="Balance (BHD)" class="right tp-money" style="color:{{ $invoice->balance_due > 0.001 ? 'var(--tone-danger-fg)' : 'var(--tone-success-fg)' }}">{{ number_format($invoice->balance_due, 3) }}</td>
                    <td data-label="Status"><span class="status-badge {{ $invoice->status }}">{{ $invoice->status_label }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
</div>

{{-- ===================== PAYMENTS & RECEIPTS TAB ===================== --}}
<div class="tab-panel" id="panel-payments">
<div class="table-card detail-item">
    @if($tenant->payments->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-money-bill-transfer"></i>No payments recorded for this tenant yet.</div>
    @else
    <div class="table-wrap">
        <table>
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
                    <td data-label="Payment #" style="font-weight:700">{{ $payment->payment_number }}</td>
                    <td data-label="Date">{{ $payment->payment_date->format('d M Y') }}</td>
                    <td data-label="Invoice #">
                        @if($payment->invoice)
                        <a href="{{ route('invoices.show', $payment->invoice) }}" class="tp-link">{{ $payment->invoice->invoice_number }}</a>
                        @else
                        —
                        @endif
                    </td>
                    <td data-label="Amount (BHD)" class="right tp-money" style="color:var(--tone-success-fg)">{{ number_format($payment->amount, 3) }}</td>
                    <td data-label="Method">{{ $payment->method_label }}</td>
                    <td data-label="Receipt">
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
    </div>
    @endif
</div>
</div>

{{-- ===================== EWA BILLS TAB ===================== --}}
<div class="tab-panel" id="panel-ewa">
<div class="table-card detail-item">
    @if($tenant->ewaBills->isEmpty())
    <div class="tp-empty"><i class="fa-solid fa-bolt"></i>No EWA bills on file for this tenant.</div>
    @else
    <div class="table-wrap">
        <table>
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
                    <td data-label="Bill #"><span class="tp-link">{{ $bill->bill_number }}</span></td>
                    <td data-label="Billing Period">{{ $bill->billing_period ?: '—' }}</td>
                    <td data-label="Total (BHD)" class="right tp-money">{{ number_format($bill->total_amount, 3) }}</td>
                    <td data-label="Tenant Portion (BHD)" class="right tp-money">{{ number_format($bill->effective_tenant_portion, 3) }}</td>
                    <td data-label="Paid (BHD)" class="right tp-money">{{ number_format($bill->total_paid, 3) }}</td>
                    <td data-label="Balance (BHD)" class="right tp-money" style="color:{{ $bill->balance_due > 0.001 ? 'var(--tone-danger-fg)' : 'var(--tone-success-fg)' }}">{{ number_format($bill->balance_due, 3) }}</td>
                    <td data-label="Status"><span class="status-badge {{ $bill->status }}">{{ $bill->status_label }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
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
    <div class="table-wrap">
        <table>
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
                    <td data-label="Note #" style="font-weight:700">{{ $note->note_number }}</td>
                    <td data-label="Type"><span class="status-badge {{ $note->type }}">{{ $note->type_label }}</span></td>
                    <td data-label="Invoice #">
                        @if($note->invoice)
                        <a href="{{ route('invoices.show', $note->invoice) }}" class="tp-link">{{ $note->invoice->invoice_number }}</a>
                        @else
                        <span style="color:var(--text-muted)">General adjustment</span>
                        @endif
                    </td>
                    <td data-label="Date">{{ $note->note_date->format('d M Y') }}</td>
                    <td data-label="Amount (BHD)" class="right tp-money" style="color:{{ $note->type === 'credit' ? 'var(--tone-success-fg)' : 'var(--tone-warning-fg)' }}">{{ $note->type === 'credit' ? '−' : '+' }}{{ number_format($note->amount, 3) }}</td>
                    <td data-label="Reason" style="color:var(--text-muted)">{{ $note->reason }}</td>
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
    </div>
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
    <div class="table-wrap">
        <table>
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
                    <td data-label="Month" style="font-weight:600">{{ $row['month']->format('F Y') }}</td>
                    <td data-label="Invoiced (BHD)" class="right tp-money">{{ number_format($row['invoiced'], 3) }}</td>
                    <td data-label="Received (BHD)" class="right tp-money">{{ number_format($row['paid'], 3) }}</td>
                    <td data-label="Remaining (BHD)" class="right tp-money" style="color:{{ $row['remaining'] > 0.001 ? 'var(--tone-danger-fg)' : 'var(--tone-success-fg)' }}">{{ number_format($row['remaining'], 3) }}</td>
                    <td data-label="Status">
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
                    <td data-label="Month" style="padding:12px 14px">Total</td>
                    <td data-label="Invoiced (BHD)" class="right tp-money" style="padding:12px 14px">{{ number_format($rentSchedule->sum('invoiced'), 3) }}</td>
                    <td data-label="Received (BHD)" class="right tp-money" style="padding:12px 14px">{{ number_format($rentSchedule->sum('paid'), 3) }}</td>
                    <td data-label="Remaining (BHD)" class="right tp-money" style="padding:12px 14px;color:{{ $rentSchedule->sum('remaining') > 0.001 ? 'var(--tone-danger-fg)' : 'var(--tone-success-fg)' }}">{{ number_format($rentSchedule->sum('remaining'), 3) }}</td>
                    <td data-label="Status"></td>
                </tr>
            </tbody>
        </table>
    </div>
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

    /* On a phone the seven tabs are one scrolling row, so the active one can
       start off-screen — landing on ?tab=ledger would show a bar that looks
       like it begins at Overview. Centre it in the container by setting
       scrollLeft directly: scrollIntoView() would also scroll the page
       itself, which on load yanks the reader past the hero. */
    const revealTab = function (btn) {
        const bar = btn.closest('.tp-tabs');
        if (!bar || bar.scrollWidth <= bar.clientWidth) return;   // not scrolling (desktop)
        const offset = btn.getBoundingClientRect().left - bar.getBoundingClientRect().left;
        const target = bar.scrollLeft + offset - (bar.clientWidth - btn.offsetWidth) / 2;
        bar.scrollLeft = Math.max(0, Math.min(target, bar.scrollWidth - bar.clientWidth));
    };

    window.switchTab = function (tab) {
        const root = inModal ? document.getElementById('tenantProfileBody') : document;
        root.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        root.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        const btn = root.querySelector('#tab-' + tab);
        btn.classList.add('active');
        root.querySelector('#panel-' + tab).classList.add('active');
        revealTab(btn);
        if (!inModal) {
            history.replaceState(null, '', '?tab=' + tab);
        }
    };

    const validTabs = ['overview', 'leases', 'invoices', 'payments', 'ewa', 'notes', 'ledger'];
    const urlTab = inModal ? null : new URLSearchParams(window.location.search).get('tab');
    switchTab(validTabs.includes(urlTab) ? urlTab : 'overview');

    /* The icon font lands after this script runs, and every tab gets wider when
       it does — so the position worked out above is short by the time the row
       is on screen. Measure it again once the fonts are in. */
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(function () {
            const active = (inModal ? document.getElementById('tenantProfileBody') : document)
                .querySelector('.tp-tabs .tab-btn.active');
            if (active) revealTab(active);
        });
    }
})();
</script>
