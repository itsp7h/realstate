@extends('layouts.admin')

@section('title', 'Invoices')
@section('topbar-title', 'Invoices')
@section('topbar-count', number_format($invoices->total()))

@section('page-title', 'Invoices')
@section('page-subtitle', 'Manage and track invoices issued to tenants')
@section('page-actions')
    <button type="button" class="btn btn-outline" onclick="openGenInvoicesModal()">
        <i class="fa-solid fa-bolt"></i> Generate Invoices
    </button>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> New Invoice
    </a>
@endsection

@push('styles')
<style>


.overdue-row td { background: var(--tone-danger-bg); }

/* GENERATE INVOICES MODAL */
.gen-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 1050;
    background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px);
    align-items: center; justify-content: center; padding: var(--sp-5);
}
.gen-modal-overlay.open { display: flex; }
.gen-modal-header {
    padding: 20px 24px 16px; display: flex; align-items: flex-start; gap: 14px;
    border-bottom: 1px solid var(--card-border);
}
.gen-modal-icon {
    width: 40px; height: 40px; border-radius: var(--radius-sm); flex-shrink: 0;
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center; font-size: var(--fs-md);
}
.gen-modal-title { font-family: 'Outfit', sans-serif; font-size: var(--fs-md); font-weight: 700; color: var(--text-primary); }
.gen-modal-sub { font-size: var(--fs-sm); color: var(--text-muted); margin-top: 3px; line-height: 1.5; }
.gen-modal-body { padding: 20px 24px; }
.gen-modal-body label {
    display: block; font-size: var(--fs-sm); font-weight: 600; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: var(--sp-2);
}
.gen-modal-body input[type="date"] {
    width: 100%; padding: 10px 13px; font-size: var(--fs-base); box-sizing: border-box;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
    transition: border-color 0.18s; font-family: 'Plus Jakarta Sans', sans-serif;
}
.gen-modal-body input[type="date"]:focus { border-color: var(--accent); }
.gen-modal-preview {
    margin-top: 14px; padding: 12px 14px; border-radius: var(--radius-sm);
    background: var(--accent-dim); display: flex; align-items: center; gap: 10px;
}
.gen-modal-preview i { color: var(--accent); font-size: var(--fs-base); }
.gen-modal-preview span { font-size: var(--fs-base); color: var(--text-primary); }
.gen-modal-preview strong { font-family: 'Outfit', sans-serif; font-weight: 700; }
.gen-modal-footer {
    padding: 16px 24px; border-top: 1px solid var(--card-border);
    display: flex; justify-content: flex-end; gap: 10px;
}
</style>
@endpush

@section('content')


{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════
     The status pill is the row's one slot when nothing is filtered by
     status; when a chip is filtering, every pill would say the same word,
     so the amount takes the slot instead. ── --}}
@php
    $invStatusLabels = ['issued' => 'Issued', 'partially_paid' => 'Partially paid', 'paid' => 'Paid', 'overdue' => 'Overdue'];
@endphp
@php
    $invStatus = request('status');
    $invChips  = [[
        'label'  => 'All',
        'href'   => route('invoices.index', array_filter(['search' => request('search')])),
        'active' => ! $invStatus,
    ]];
    foreach ($invStatusLabels as $invVal => $invLabel) {
        $invChips[] = [
            'label'  => $invLabel,
            'href'   => route('invoices.index', array_filter(['search' => request('search'), 'status' => $invVal])),
            'active' => $invStatus === $invVal,
        ];
    }
@endphp
<x-mobile-list
    :actions="['primary' => ['label' => 'New invoice', 'href' => route('invoices.create')]]"
    :stats="[
        ['money' => $collectedThisMonth,               'label' => now()->format('M').' BHD'],
        {{-- "Owed", the same word the dashboard uses for the same figure: one
             name per thing, and it fits the column without wrapping. --}}
        ['money' => $outstanding,                      'label' => 'Owed BHD', 'owed' => true],
        ['value' => number_format($invoices->total()), 'label' => 'Invoices'],
    ]"
    :search="[
        'action'      => route('invoices.index'),
        'placeholder' => 'Search invoice or tenant',
        'aria'        => 'Search invoices',
        'keep'        => ['status'],
    ]"
    :chips="$invChips">

    @forelse($invoices as $inv)
        <a href="{{ route('invoices.show', $inv) }}" class="m-row-card ps-reveal">
            <span class="m-row-thumb"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i></span>
            <span class="m-row-text">
                <span class="m-row-title">{{ $inv->invoice_number }}</span>
                <span class="m-row-sub">{{ $inv->tenant_name }}</span>
                {{-- The status word repeats the active chip, so it only
                     earns a line when nothing is filtering by it. --}}
                <span class="m-row-sub">
                    Due {{ $inv->due_date?->format('d M Y') ?? '—' }}@unless(request('status')) &middot; {{ $inv->status_label }}@endunless
                </span>
            </span>
            <span class="m-row-amount">BHD {{ number_format($inv->amount, 0) }}</span>
            <i class="fa-solid fa-chevron-right m-row-chevron" aria-hidden="true"></i>
        </a>
    @empty
        <div class="m-empty">
            <div class="m-empty-icon"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></div>
            <div class="m-empty-title">No invoices here</div>
            <div class="m-empty-sub">Raise one against a lease, and it shows up here.</div>
            <a href="{{ route('invoices.create') }}" class="m-action-btn primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>New invoice
            </a>
        </div>
    @endforelse
</x-mobile-list>

<div class="stats-grid m-hide-desktop-index">
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon gray"><i class="fa-solid fa-file-invoice-dollar"></i></span>
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

<div class="table-card m-hide-desktop-index">
    <form method="GET" action="{{ route('invoices.index') }}">
        <div class="filter-bar">
            <div class="filter-group is-search">
                <label for="f_search">Search</label>
                <input type="search" id="f_search" name="search" value="{{ request('search') }}" placeholder="Search invoice #, tenant, property…">
            </div>
            <div class="filter-group">
                <label for="f_status">Status</label>
                <select id="f_status" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['draft'=>'Draft','issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid','overdue'=>'Overdue','cancelled'=>'Cancelled'] as $v => $l)
                    <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="f_type">Type</label>
                <select id="f_type" name="type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach(['rent'=>'Rent','utilities'=>'Utilities','other'=>'Other'] as $v => $l)
                    <option value="{{ $v }}" {{ request('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="f_date_from">From</label>
                <input type="date" id="f_date_from" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="filter-group">
                <label for="f_date_to">To</label>
                <input type="date" id="f_date_to" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                @if(request()->hasAny(['search','status','type','date_from','date_to']))
                <a href="{{ route('invoices.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
                @endif
            </div>
        </div>
    </form>
    @if($invoices->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
        <i class="fa-solid fa-file-invoice-dollar" style="font-size:36px;display:block;margin-bottom:12px;opacity:0.3"></i>
        No invoices found
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Invoice Date</th>
                    <th>Tenant</th>
                    <th>Property / Unit</th>
                    <th>Type</th>
                    <th class="num">Amount (BHD)</th>
                    <th class="num">Balance (BHD)</th>
                    <th>Status</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                <tr data-href="{{ route('invoices.show', $inv) }}" style="cursor:pointer"
                    class="{{ $inv->status === 'overdue' ? 'overdue-row' : '' }}">
                    <td class="cell-id">
                        {{ $inv->invoice_number }}
                    </td>
                    <td style="white-space:nowrap;font-size:12px">{{ $inv->invoice_date->format('d M Y') }}</td>
                    <td>{{ $inv->tenant_name }}</td>
                    <td style="font-size:12px">
                        @if($inv->line_count > 1)
                            {{ $inv->property_name }} <span style="color:var(--text-muted)">+ {{ $inv->line_count - 1 }} more</span>
                        @else
                            {{ $inv->property_name }}
                            @if($inv->unit)<span style="color:var(--text-muted)"> / {{ $inv->unit }}</span>@endif
                        @endif
                    </td>
                    <td><span class="status-badge {{ $inv->type }}">{{ $inv->type_label }}</span></td>
                    <td class="num">{{ number_format($inv->amount, 3) }}</td>
                    {{-- An outstanding balance is the column people scan for, so it
                         carries a tone; a settled or cancelled one recedes. --}}
                    <td class="num {{ $inv->balance_due > 0 && $inv->status !== 'cancelled' ? 'val-negative' : 'cell-muted' }}">
                        {{ number_format($inv->balance_due, 3) }}
                    </td>
                    <td>
                        <span class="status-badge {{ $inv->status }}">
                            <i class="fa-solid fa-circle" style="font-size:5px"></i>
                            {{ $inv->status_label }}
                        </span>
                    </td>
                    <td class="col-actions" onclick="event.stopPropagation()">
                        @include('partials.row-actions', ['label' => 'Actions for invoice '.$inv->invoice_number, 'items' => [
                            ['label' => 'View invoice', 'icon' => 'fa-eye', 'url' => route('invoices.show', $inv)],
                            ['label' => 'Preview PDF',  'icon' => 'fa-file-pdf',
                             'onclick' => "openInvPdf('".route('invoices.pdf.preview', $inv)."', '".e($inv->invoice_number)."')"],
                            /* A paid or cancelled invoice is a record, not a draft. */
                            ($inv->status !== 'paid' && $inv->status !== 'cancelled')
                                ? ['label' => 'Edit invoice', 'icon' => 'fa-pen', 'url' => route('invoices.edit', $inv)]
                                : null,
                            ['sep' => true],
                            ['label' => 'Delete invoice', 'icon' => 'fa-trash', 'tone' => 'danger',
                             'action' => route('invoices.destroy', $inv), 'method' => 'DELETE',
                             'confirm' => 'Delete invoice '.$inv->invoice_number.'?'],
                        ]])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $invoices->firstItem() ?? 0 }}–{{ $invoices->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($invoices->total()) }}</strong> invoices
        </div>
        {{ $invoices->links() }}
    </div>
    @endif
</div>

{{-- PDF PREVIEW MODAL --}}
<div class="pdf-viewer-overlay" id="invPdfModal" onclick="closeInvPdf(event)">
    <div class="pdf-viewer" onclick="event.stopPropagation()">
        <div class="pdf-viewer-header">
            <i class="fa-solid fa-file-pdf" style="color:var(--accent);font-size:16px"></i>
            <span id="invPdfTitle">Invoice</span>
            <a id="invPdfDownload" href="#" class="btn btn-outline btn-sm" download>
                <i class="fa-solid fa-download"></i> Download
            </a>
            <button type="button" class="btn btn-outline btn-sm" onclick="closeInvPdfBtn()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <iframe id="invPdfFrame" class="pdf-viewer-frame" src="about:blank"></iframe>
    </div>
</div>

{{-- GENERATE INVOICES MODAL --}}
<div class="gen-modal-overlay" id="genInvoicesModal" onclick="closeGenInvoicesModal(event)">
    <div class="modal-box" style="--modal-w:420px" onclick="event.stopPropagation()">
        <form method="POST" action="{{ route('invoices.generate-monthly') }}">
            @csrf
            <div class="gen-modal-header">
                <div class="gen-modal-icon"><i class="fa-solid fa-bolt"></i></div>
                <div>
                    <div class="gen-modal-title">Generate Rent Invoices</div>
                    <div class="gen-modal-sub">Creates one invoice per tenant for every lease contract active on the date below. Tenants that already have an invoice for that month are skipped.</div>
                </div>
            </div>
            <div class="gen-modal-body">
                <label for="genInvoiceDate">Invoice Date</label>
                <input type="date" id="genInvoiceDate" name="invoice_date"
                       value="{{ now()->format('Y-m-d') }}" required
                       oninput="updateGenInvoicesPreview()">
                <div class="gen-modal-preview">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Invoices will be dated <strong id="genInvoicesMonthLabel">{{ now()->format('d F Y') }}</strong>, covering active leases for <strong id="genInvoicesRangeLabel">{{ now()->format('F Y') }}</strong>.</span>
                </div>
            </div>
            <div class="gen-modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeGenInvoicesModalBtn()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-bolt"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openInvPdf(previewUrl, title) {
    document.getElementById('invPdfTitle').textContent = title;
    document.getElementById('invPdfFrame').src = previewUrl;
    document.getElementById('invPdfDownload').href = previewUrl.replace('/preview', '');
    document.getElementById('invPdfModal').classList.add('open');
}
function closeInvPdf(e) {
    if (e.target === document.getElementById('invPdfModal')) closeInvPdfBtn();
}
function closeInvPdfBtn() {
    document.getElementById('invPdfModal').classList.remove('open');
    document.getElementById('invPdfFrame').src = 'about:blank';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeInvPdfBtn(); closeGenInvoicesModalBtn(); }
});

function openGenInvoicesModal() {
    updateGenInvoicesPreview();
    document.getElementById('genInvoicesModal').classList.add('open');
}
function closeGenInvoicesModal(e) {
    if (e.target === document.getElementById('genInvoicesModal')) closeGenInvoicesModalBtn();
}
function closeGenInvoicesModalBtn() {
    document.getElementById('genInvoicesModal').classList.remove('open');
}
function updateGenInvoicesPreview() {
    const raw = document.getElementById('genInvoiceDate').value;
    if (!raw) return;
    const [year, month, day] = raw.split('-').map(Number);
    const picked = new Date(year, month - 1, day);
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    document.getElementById('genInvoicesMonthLabel').textContent =
        String(picked.getDate()).padStart(2, '0') + ' ' + months[picked.getMonth()] + ' ' + picked.getFullYear();
    document.getElementById('genInvoicesRangeLabel').textContent =
        months[picked.getMonth()] + ' ' + picked.getFullYear();
}
</script>
@endpush
