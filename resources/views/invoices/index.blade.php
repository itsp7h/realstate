@extends('layouts.admin')

@section('title', 'Invoices')
@section('topbar-title', 'Invoices')

@push('styles')
<style>
.inv-stats {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px; margin-bottom: 24px;
}
.inv-stat {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
}
.inv-stat-icon {
    width: 40px; height: 40px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
}
.inv-stat-icon.gray  { background: #F1F5F9; color: #64748B; }
.inv-stat-icon.blue  { background: #EFF6FF; color: #2563EB; }
.inv-stat-icon.amber { background: #FFFBEB; color: #D97706; }
.inv-stat-icon.green { background: #ECFDF5; color: #059669; }
.inv-stat-icon.red   { background: #FEF2F2; color: #DC2626; }
.inv-stat-val { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.inv-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

.filter-bar {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 14px 18px;
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px;
}
.filter-bar input, .filter-bar select {
    padding: 8px 12px; font-size: 13px;
    border: 1.5px solid var(--input-border); border-radius: var(--radius-sm);
    background: var(--input-bg); color: var(--text-primary); outline: none;
    transition: border-color 0.18s;
}
.filter-bar input:focus, .filter-bar select:focus { border-color: var(--accent); }
.filter-bar input[type="search"] { flex: 1; min-width: 180px; }
.filter-bar input[type="date"]   { min-width: 140px; }

.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.status-badge.draft          { background: #F1F5F9; color: #64748B; }
.status-badge.issued         { background: #EFF6FF; color: #2563EB; }
.status-badge.partially_paid { background: #FFFBEB; color: #D97706; }
.status-badge.paid           { background: #ECFDF5; color: #059669; }
.status-badge.overdue        { background: #FEF2F2; color: #DC2626; }
.status-badge.cancelled      { background: #F8FAFC; color: #94A3B8; }

.type-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600;
}
.type-badge.rent      { background: #EFF6FF; color: #2563EB; }
.type-badge.utilities { background: #FFF7ED; color: #EA580C; }
.type-badge.other     { background: #F1F5F9; color: #64748B; }

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; }
.overdue-row td { background: #FFF8F8; }

.pdf-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 1050;
    background: rgba(0,0,0,0.85); align-items: center; justify-content: center;
}
.pdf-modal-overlay.open { display: flex; }
.pdf-modal-box {
    width: 90vw; height: 90vh; background: #1E2433; border-radius: var(--radius);
    display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.5);
}
.pdf-modal-header {
    padding: 12px 18px; background: #151929; border-bottom: 1px solid #2D3650;
    display: flex; align-items: center; gap: 12px;
}
.pdf-modal-header span { flex: 1; font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: #E2E8F0; }
.pdf-modal-iframe { flex: 1; border: none; width: 100%; background: #fff; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Invoices</h1>
        <p class="page-header-sub">Manage and track invoices issued to tenants</p>
    </div>
    <div class="page-header-actions">
        <form method="POST" action="{{ route('invoices.generate-monthly') }}"
              onsubmit="return confirm('Generate rent invoices for all active contracts this month?\nDuplicates will be skipped.')">
            @csrf
            <button type="submit" class="btn btn-outline">
                <i class="fa-solid fa-bolt"></i> Generate {{ now()->format('F') }} Invoices
            </button>
        </form>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Invoice
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

<div class="inv-stats">
    <div class="inv-stat">
        <div class="inv-stat-icon gray"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div><div class="inv-stat-val">{{ $stats['total'] }}</div><div class="inv-stat-lbl">Total</div></div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat-icon blue"><i class="fa-solid fa-paper-plane"></i></div>
        <div><div class="inv-stat-val">{{ $stats['issued'] }}</div><div class="inv-stat-lbl">Issued</div></div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat-icon amber"><i class="fa-solid fa-circle-half-stroke"></i></div>
        <div><div class="inv-stat-val">{{ $stats['partially_paid'] }}</div><div class="inv-stat-lbl">Partial</div></div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="inv-stat-val">{{ $stats['paid'] }}</div><div class="inv-stat-lbl">Paid</div></div>
    </div>
    <div class="inv-stat">
        <div class="inv-stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div><div class="inv-stat-val">{{ $stats['overdue'] }}</div><div class="inv-stat-lbl">Overdue</div></div>
    </div>
</div>

<form method="GET" action="{{ route('invoices.index') }}" class="filter-bar">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search invoice #, tenant, property…">
    <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        @foreach(['draft'=>'Draft','issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid','overdue'=>'Overdue','cancelled'=>'Cancelled'] as $v => $l)
        <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
    </select>
    <select name="type" onchange="this.form.submit()">
        <option value="">All Types</option>
        @foreach(['rent'=>'Rent','utilities'=>'Utilities','other'=>'Other'] as $v => $l)
        <option value="{{ $v }}" {{ request('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ request('date_from') }}" title="Invoice date from">
    <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Invoice date to">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    @if(request()->hasAny(['search','status','type','date_from','date_to']))
    <a href="{{ route('invoices.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
    @endif
</form>

<div class="table-card">
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
                    <th>Amount (BHD)</th>
                    <th>Balance (BHD)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                <tr data-href="{{ route('invoices.show', $inv) }}" style="cursor:pointer"
                    class="{{ $inv->status === 'overdue' ? 'overdue-row' : '' }}">
                    <td style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--accent)">
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
                    <td><span class="type-badge {{ $inv->type }}">{{ $inv->type_label }}</span></td>
                    <td style="font-family:'Outfit',sans-serif;font-weight:700">{{ number_format($inv->amount, 3) }}</td>
                    <td style="font-family:'Outfit',sans-serif;font-weight:700;{{ $inv->balance_due > 0 && $inv->status !== 'cancelled' ? 'color:#DC2626' : 'color:var(--text-muted)' }}">
                        {{ number_format($inv->balance_due, 3) }}
                    </td>
                    <td>
                        <span class="status-badge {{ $inv->status }}">
                            <i class="fa-solid fa-circle" style="font-size:5px"></i>
                            {{ $inv->status_label }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center" onclick="event.stopPropagation()">
                            <button type="button" class="btn btn-outline btn-sm" title="Preview PDF"
                                    onclick="openInvPdf('{{ route('invoices.pdf.preview', $inv) }}', '{{ $inv->invoice_number }}')">
                                <i class="fa-solid fa-file-pdf"></i>
                            </button>
                            <a href="{{ route('invoices.show', $inv) }}" class="btn btn-outline btn-sm" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            @if($inv->status !== 'paid' && $inv->status !== 'cancelled')
                            <a href="{{ route('invoices.edit', $inv) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            @endif
                            <form method="POST" action="{{ route('invoices.destroy', $inv) }}"
                                  onsubmit="return confirm('Delete invoice {{ $inv->invoice_number }}?')">
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
        <div>Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }}</div>
        <div>{{ $invoices->links() }}</div>
    </div>
    @endif
</div>

{{-- PDF PREVIEW MODAL --}}
<div class="pdf-modal-overlay" id="invPdfModal" onclick="closeInvPdf(event)">
    <div class="pdf-modal-box" onclick="event.stopPropagation()">
        <div class="pdf-modal-header">
            <i class="fa-solid fa-file-pdf" style="color:var(--accent);font-size:16px"></i>
            <span id="invPdfTitle">Invoice</span>
            <a id="invPdfDownload" href="#" class="btn btn-outline btn-sm" download>
                <i class="fa-solid fa-download"></i> Download
            </a>
            <button type="button" class="btn btn-outline btn-sm" onclick="closeInvPdfBtn()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <iframe id="invPdfFrame" class="pdf-modal-iframe" src="about:blank"></iframe>
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
    if (e.key === 'Escape') closeInvPdfBtn();
});
</script>
@endpush
