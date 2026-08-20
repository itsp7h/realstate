@extends('layouts.admin')

@section('title', 'EWA Bills')
@section('topbar-title', 'EWA Bills')

@section('page-title', 'EWA Bills')
@section('page-subtitle', 'Electricity & Water Authority bills linked to lease contracts')
@section('page-actions')
    <a href="{{ route('ewa-bills.create') }}" class="btn btn-outline">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Import from PDF
    </a>
    <a href="{{ route('ewa-bills.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> New EWA Bill
    </a>
@endsection

@push('styles')
<style>
/* ── STATS ─────────────────────────────────────────────────── */

/* ── FILTER ────────────────────────────────────────────────── */

/* ── STATUS BADGES ─────────────────────────────────────────── */

.overdue-row td { background: var(--tone-danger-bg); }
.actions-cell { display: flex; gap: 6px; align-items: center; }

/* ── PDF PREVIEW MODAL ───────────────────────────────────── */

/* ── TABS ──────────────────────────────────────────────────── */

/* EWA header strip */
.ewa-header-strip {
    background: linear-gradient(135deg, var(--tone-info-fg) 0%, var(--tone-info-fg) 100%);
    border-radius: var(--radius); padding: 16px 22px; margin-bottom: var(--sp-5);
    display: flex; align-items: center; gap: 16px; color: var(--ink-on-fill);
}
.ewa-header-strip .ewa-logo-circle {
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
}
.ewa-header-strip h2 { font-family: 'Outfit',sans-serif; font-size: var(--fs-lg); font-weight: 800; margin: 0; }
.ewa-header-strip p  { font-size: var(--fs-sm); opacity: 0.85; margin: 2px 0 0; }
</style>
@endpush

@section('content')


@include('ewa-bills._tabs')

{{-- EWA Brand Strip --}}
<div class="ewa-header-strip">
    <div class="ewa-logo-circle"><i class="fa-solid fa-droplet"></i></div>
    <div>
        <h2>Electricity &amp; Water Authority</h2>
        <p>Kingdom of Bahrain &mdash; Pride in what we do.. Proud to serve</p>
    </div>
</div>

{{-- STATS --}}

{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════
     Same .m-screen / .m-row-card architecture as Payments, Invoices and
     Lease Contracts. Every value carries a visible label so nothing
     renders as a bare figure. ── --}}
<div class="m-screen">
    <div class="m-mini-row">
        <div class="m-mini-stat">
            <div class="v">{{ $stats['total'] }}</div>
            <div class="l">Total Bills</div>
        </div>
        <div class="m-mini-stat">
            <div class="v">{{ $stats['paid'] }}</div>
            <div class="l">Paid</div>
        </div>
        <div class="m-mini-stat">
            <div class="v">{{ $stats['overdue'] }}</div>
            <div class="l">Overdue</div>
        </div>
    </div>

    <div class="m-chip-row no-sb">
        <a href="{{ route('ewa-bills.index') }}" class="m-chip {{ !request('status') ? 'active' : '' }}">All</a>
        @foreach(['issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid','overdue'=>'Overdue','cancelled'=>'Cancelled','draft'=>'Draft'] as $val => $label)
            <a href="{{ route('ewa-bills.index', ['status' => $val]) }}"
               class="m-chip {{ request('status') === $val ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="m-row-list">
        @forelse($bills as $bill)
            @php
                $mIsOverdue = $bill->status === 'overdue';
                $mHasBalance = $bill->balance_due > 0 && $bill->status !== 'cancelled';
            @endphp
            <a href="{{ route('ewa-bills.show', $bill) }}" class="m-row-card">
                <div class="m-row-icon" style="background:{{ $mIsOverdue ? 'var(--ps-danger-bg)' : 'var(--ps-info-bg)' }};color:{{ $mIsOverdue ? 'var(--ps-danger)' : 'var(--ps-info)' }};">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="m-row-title">{{ $bill->bill_number }}</div>
                    <div class="m-row-sub">
                        {{ $bill->tenant_name }} &middot; {{ $bill->property_name }}@if($bill->unit) / {{ $bill->unit }}@endif
                    </div>
                    <div class="m-row-sub">
                        Period {{ $bill->billing_period }} &middot; Due {{ $bill->due_date->format('d M Y') }}
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                    <div style="font-family:'Poppins',system-ui,sans-serif;font-size:1rem;font-weight:700;color:var(--ps-navy);">
                        BHD {{ number_format($bill->total_amount, 3) }}
                    </div>
                    <div style="font-size:.7rem;font-weight:600;color:{{ $mHasBalance ? 'var(--ps-danger)' : 'var(--ps-muted-deep)' }};">
                        Balance BHD {{ number_format($bill->balance_due, 3) }}
                    </div>
                    <span class="status-badge {{ $bill->status }}">{{ $bill->status_label }}</span>
                </div>
            </a>
        @empty
            <div class="m-empty">
                <div class="m-empty-icon"><i class="fa-solid fa-bolt"></i></div>
                <div class="m-empty-title">No EWA bills yet</div>
                <div class="m-empty-sub">Try adjusting your filters.</div>
            </div>
        @endforelse
    </div>
</div>

<div class="stats-grid m-hide-desktop-index">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon teal"><i class="fa-solid fa-droplet"></i></span>
            <span class="stat-lbl">Total</span>
        </div>
        <div class="stat-val">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon blue"><i class="fa-solid fa-paper-plane"></i></span>
            <span class="stat-lbl">Issued</span>
        </div>
        <div class="stat-val">{{ $stats['issued'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon amber"><i class="fa-solid fa-circle-half-stroke"></i></span>
            <span class="stat-lbl">Partial</span>
        </div>
        <div class="stat-val">{{ $stats['partially_paid'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon green"><i class="fa-solid fa-circle-check"></i></span>
            <span class="stat-lbl">Paid</span>
        </div>
        <div class="stat-val">{{ $stats['paid'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <span class="stat-lbl">Overdue</span>
        </div>
        <div class="stat-val">{{ $stats['overdue'] }}</div>
    </div>
</div>

{{-- FILTERS --}}

{{-- TABLE --}}
<div class="table-card m-hide-desktop-index">
    <form method="GET" action="{{ route('ewa-bills.index') }}">
        <div class="filter-bar">
            <div class="filter-group is-search">
                <label for="f_search">Search</label>
                <input type="search" id="f_search" name="search" value="{{ request('search') }}" placeholder="Search bill no., tenant, account…">
            </div>
            <div class="filter-group">
                <label for="f_period">Billing period</label>
                <input type="text" id="f_period" name="period" value="{{ request('period') }}" placeholder="Billing period…">
            </div>
            <div class="filter-group">
                <label for="f_status">Status</label>
                <select id="f_status" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid','overdue'=>'Overdue','cancelled'=>'Cancelled','draft'=>'Draft'] as $v => $l)
                    <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                @if(request()->hasAny(['search','status','period']))
                <a href="{{ route('ewa-bills.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
                @endif
            </div>
        </div>
    </form>
    @if($bills->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
        <i class="fa-solid fa-droplet" style="font-size:36px;display:block;margin-bottom:12px;opacity:0.3"></i>
        No EWA bills found
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Bill #</th>
                    <th>Billing Period</th>
                    <th>Due Date</th>
                    <th>Tenant</th>
                    <th>Property / Unit</th>
                    <th>Account No.</th>
                    <th>Total (BHD)</th>
                    <th>Balance (BHD)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bills as $bill)
                <tr data-href="{{ route('ewa-bills.show', $bill) }}" style="cursor:pointer"
                    class="{{ $bill->status === 'overdue' ? 'overdue-row' : '' }}">
                    <td class="cell-id">
                        {{ $bill->bill_number }}
                    </td>
                    <td style="font-size:12px;white-space:nowrap">{{ $bill->billing_period }}</td>
                    <td style="white-space:nowrap;font-size:12px;{{ $bill->status === 'overdue' ? 'color:var(--tone-danger-fg);font-weight:600' : '' }}">
                        {{ $bill->due_date->format('d M Y') }}
                    </td>
                    <td>{{ $bill->tenant_name }}</td>
                    <td style="font-size:12px">
                        {{ $bill->property_name }}
                        @if($bill->unit)<span style="color:var(--text-muted)"> / {{ $bill->unit }}</span>@endif
                    </td>
                    <td class="cell-muted">{{ $bill->ewa_account_number ?: '—' }}</td>
                    <td class="amount-col">{{ number_format($bill->total_amount, 3) }}</td>
                    <td class="amount-col {{ $bill->balance_due > 0 && $bill->status !== 'cancelled' ? '' : '' }}"
                        style="{{ $bill->balance_due > 0 && $bill->status !== 'cancelled' ? 'color:var(--tone-danger-fg)' : 'color:var(--text-muted)' }}">
                        {{ number_format($bill->balance_due, 3) }}
                    </td>
                    <td>
                        <span class="status-badge {{ $bill->status }}">
                            <i class="fa-solid fa-circle" style="font-size:5px"></i>
                            {{ $bill->status_label }}
                        </span>
                    </td>
                    <td>
                        <div class="actions-cell" onclick="event.stopPropagation()">
                            <a href="{{ route('ewa-bills.show', $bill) }}" class="btn btn-outline btn-sm" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            @if($bill->status !== 'paid' && $bill->status !== 'cancelled')
                            <a href="{{ route('ewa-bills.edit', $bill) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            @endif
                            <button type="button" class="btn btn-outline btn-sm" title="Preview PDF"
                                    onclick="openPdfPreview('{{ route('ewa-bills.pdf.preview', $bill) }}','{{ route('ewa-bills.pdf', $bill) }}','{{ $bill->bill_number }} — {{ $bill->billing_period }}')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <form method="POST" action="{{ route('ewa-bills.destroy', $bill) }}"
                                  onsubmit="return confirm('Delete bill {{ $bill->bill_number }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $bills->firstItem() ?? 0 }}–{{ $bills->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($bills->total()) }}</strong> bills
        </div>
        {{ $bills->links() }}
    </div>
    @endif
</div>

{{-- PDF PREVIEW MODAL --}}
<div class="pdf-viewer-overlay" id="pdfModalOverlay">
    <div class="pdf-viewer">
        <div class="pdf-viewer-header">
            <div class="pdf-viewer-title" id="pdfModalTitle">
                <i class="fa-solid fa-file-invoice" style="color:var(--tone-info-fg);margin-right:6px"></i>
                EWA Bill
            </div>
            <div class="pdf-viewer-actions">
                <a href="#" id="pdfDownloadBtn" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-file-arrow-down"></i> Download
                </a>
                <button type="button" class="btn btn-outline btn-sm" onclick="closePdfPreview()">
                    <i class="fa-solid fa-xmark"></i> Close
                </button>
            </div>
        </div>
        <iframe id="pdfFrame" class="pdf-viewer-frame" src="" title="EWA Bill Preview"></iframe>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openPdfPreview(previewUrl, downloadUrl, title) {
    document.getElementById('pdfFrame').src        = previewUrl;
    document.getElementById('pdfDownloadBtn').href = downloadUrl;
    document.getElementById('pdfModalTitle').innerHTML =
        '<i class="fa-solid fa-file-invoice" style="color:var(--tone-info-fg);margin-right:6px"></i>' + title;
    document.getElementById('pdfModalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePdfPreview() {
    document.getElementById('pdfModalOverlay').classList.remove('open');
    document.getElementById('pdfFrame').src = '';
    document.body.style.overflow = '';
}

document.getElementById('pdfModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePdfPreview();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePdfPreview(); });
</script>
@endpush
