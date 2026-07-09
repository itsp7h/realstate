@extends('layouts.admin')

@section('title', 'EWA Bills')
@section('topbar-title', 'EWA Bills')

@push('styles')
<style>
/* ── STATS ─────────────────────────────────────────────────── */
.ewa-stats {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(160px,1fr));
    gap: 14px; margin-bottom: 24px;
}
.ewa-stat {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
}
.ewa-stat-icon {
    width: 40px; height: 40px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
}
.ewa-stat-icon.teal  { background: #F0FDFA; color: #0D9488; }
.ewa-stat-icon.blue  { background: #EFF6FF; color: #2563EB; }
.ewa-stat-icon.amber { background: #FFFBEB; color: #D97706; }
.ewa-stat-icon.green { background: #ECFDF5; color: #059669; }
.ewa-stat-icon.red   { background: #FEF2F2; color: #DC2626; }
.ewa-stat-val { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.ewa-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

/* ── FILTER ────────────────────────────────────────────────── */
.filter-bar {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 14px 18px;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px;
}
.filter-bar input, .filter-bar select {
    padding: 8px 12px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
    transition: border-color 0.18s; font-family: 'Plus Jakarta Sans', sans-serif;
}
.filter-bar input:focus, .filter-bar select:focus { border-color: var(--accent); }
.filter-bar input[type="search"] { flex: 1; min-width: 200px; }

/* ── STATUS BADGES ─────────────────────────────────────────── */
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.status-badge.issued         { background: #EFF6FF; color: #2563EB; }
.status-badge.partially_paid { background: #FFFBEB; color: #D97706; }
.status-badge.paid           { background: #ECFDF5; color: #059669; }
.status-badge.overdue        { background: #FEF2F2; color: #DC2626; }
.status-badge.cancelled      { background: #F8FAFC; color: #94A3B8; }
.status-badge.draft          { background: #F1F5F9; color: #64748B; }

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; }
.amount-col { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 13px; }
.overdue-row td { background: #FFF8F8; }
.actions-cell { display: flex; gap: 6px; align-items: center; }

/* ── PDF PREVIEW MODAL ───────────────────────────────────── */
.pdf-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 2000;
    background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
}
.pdf-modal-overlay.open { display: flex; }
.pdf-modal {
    width: 90vw; max-width: 900px; height: 90vh;
    background: #1E293B; border-radius: var(--radius);
    display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,0.5);
}
.pdf-modal-header {
    padding: 14px 20px; background: #0F172A;
    display: flex; align-items: center; gap: 12px; flex-shrink: 0;
}
.pdf-modal-title { font-family: 'Outfit',sans-serif; font-size: 14px; font-weight: 700; color: #fff; flex: 1; }
.pdf-modal-actions { display: flex; gap: 8px; }
.pdf-modal-btn {
    padding: 6px 14px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600;
    cursor: pointer; border: none; display: flex; align-items: center; gap: 6px;
    font-family: inherit; text-decoration: none; transition: opacity 0.15s;
}
.pdf-modal-btn:hover { opacity: 0.85; }
.pdf-modal-btn.download { background: #0D9488; color: #fff; }
.pdf-modal-btn.close    { background: #334155; color: #94A3B8; }
.pdf-modal-frame { flex: 1; width: 100%; border: none; background: #fff; }

/* EWA header strip */
.ewa-header-strip {
    background: linear-gradient(135deg, #0D9488 0%, #0369A1 100%);
    border-radius: var(--radius); padding: 16px 22px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 16px; color: #fff;
}
.ewa-header-strip .ewa-logo-circle {
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
}
.ewa-header-strip h2 { font-family: 'Outfit',sans-serif; font-size: 18px; font-weight: 800; margin: 0; }
.ewa-header-strip p  { font-size: 12px; opacity: 0.85; margin: 2px 0 0; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">EWA Bills</h1>
        <p class="page-header-sub">Electricity &amp; Water Authority bills linked to lease contracts</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('ewa-bills.create') }}" class="btn btn-outline">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Import from PDF
        </a>
        <a href="{{ route('ewa-bills.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New EWA Bill
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

{{-- EWA Brand Strip --}}
<div class="ewa-header-strip">
    <div class="ewa-logo-circle"><i class="fa-solid fa-droplet"></i></div>
    <div>
        <h2>Electricity &amp; Water Authority</h2>
        <p>Kingdom of Bahrain &mdash; Pride in what we do.. Proud to serve</p>
    </div>
</div>

{{-- STATS --}}
<div class="ewa-stats">
    <div class="ewa-stat">
        <div class="ewa-stat-icon teal"><i class="fa-solid fa-droplet"></i></div>
        <div><div class="ewa-stat-val">{{ $stats['total'] }}</div><div class="ewa-stat-lbl">Total</div></div>
    </div>
    <div class="ewa-stat">
        <div class="ewa-stat-icon blue"><i class="fa-solid fa-paper-plane"></i></div>
        <div><div class="ewa-stat-val">{{ $stats['issued'] }}</div><div class="ewa-stat-lbl">Issued</div></div>
    </div>
    <div class="ewa-stat">
        <div class="ewa-stat-icon amber"><i class="fa-solid fa-circle-half-stroke"></i></div>
        <div><div class="ewa-stat-val">{{ $stats['partially_paid'] }}</div><div class="ewa-stat-lbl">Partial</div></div>
    </div>
    <div class="ewa-stat">
        <div class="ewa-stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="ewa-stat-val">{{ $stats['paid'] }}</div><div class="ewa-stat-lbl">Paid</div></div>
    </div>
    <div class="ewa-stat">
        <div class="ewa-stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div><div class="ewa-stat-val">{{ $stats['overdue'] }}</div><div class="ewa-stat-lbl">Overdue</div></div>
    </div>
</div>

{{-- FILTERS --}}
<form method="GET" action="{{ route('ewa-bills.index') }}" class="filter-bar">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search bill no., tenant, account…">
    <input type="text"   name="period" value="{{ request('period') }}" placeholder="Billing period…" style="min-width:140px">
    <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        @foreach(['issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid','overdue'=>'Overdue','cancelled'=>'Cancelled','draft'=>'Draft'] as $v => $l)
        <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    @if(request()->hasAny(['search','status','period']))
    <a href="{{ route('ewa-bills.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
    @endif
</form>

{{-- TABLE --}}
<div class="table-card">
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
                    <td style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--accent)">
                        {{ $bill->bill_number }}
                    </td>
                    <td style="font-size:12px;white-space:nowrap">{{ $bill->billing_period }}</td>
                    <td style="white-space:nowrap;font-size:12px;{{ $bill->status === 'overdue' ? 'color:#DC2626;font-weight:600' : '' }}">
                        {{ $bill->due_date->format('d M Y') }}
                    </td>
                    <td>{{ $bill->tenant_name }}</td>
                    <td style="font-size:12px">
                        {{ $bill->property_name }}
                        @if($bill->unit)<span style="color:var(--text-muted)"> / {{ $bill->unit }}</span>@endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $bill->ewa_account_number ?: '—' }}</td>
                    <td class="amount-col">{{ number_format($bill->total_amount, 3) }}</td>
                    <td class="amount-col {{ $bill->balance_due > 0 && $bill->status !== 'cancelled' ? '' : '' }}"
                        style="{{ $bill->balance_due > 0 && $bill->status !== 'cancelled' ? 'color:#DC2626' : 'color:var(--text-muted)' }}">
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
    <div style="padding:14px 18px;border-top:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--text-muted)">
        <div>Showing {{ $bills->firstItem() }}–{{ $bills->lastItem() }} of {{ $bills->total() }}</div>
        <div>{{ $bills->links() }}</div>
    </div>
    @endif
</div>

{{-- PDF PREVIEW MODAL --}}
<div class="pdf-modal-overlay" id="pdfModalOverlay">
    <div class="pdf-modal">
        <div class="pdf-modal-header">
            <div class="pdf-modal-title" id="pdfModalTitle">
                <i class="fa-solid fa-file-invoice" style="color:#0D9488;margin-right:6px"></i>
                EWA Bill
            </div>
            <div class="pdf-modal-actions">
                <a href="#" id="pdfDownloadBtn" class="pdf-modal-btn download">
                    <i class="fa-solid fa-file-arrow-down"></i> Download
                </a>
                <button type="button" class="pdf-modal-btn close" onclick="closePdfPreview()">
                    <i class="fa-solid fa-xmark"></i> Close
                </button>
            </div>
        </div>
        <iframe id="pdfFrame" class="pdf-modal-frame" src="" title="EWA Bill Preview"></iframe>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openPdfPreview(previewUrl, downloadUrl, title) {
    document.getElementById('pdfFrame').src        = previewUrl;
    document.getElementById('pdfDownloadBtn').href = downloadUrl;
    document.getElementById('pdfModalTitle').innerHTML =
        '<i class="fa-solid fa-file-invoice" style="color:#0D9488;margin-right:6px"></i>' + title;
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
