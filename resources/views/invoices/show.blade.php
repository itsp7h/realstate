@extends('layouts.admin')

@section('title', $invoice->invoice_number)
@section('topbar-title', 'Invoices')

@push('styles')
<style>
.detail-card {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); overflow: hidden; margin-bottom: 18px;
}
.detail-card-header {
    padding: 20px 24px; border-bottom: 1px solid var(--card-border);
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.detail-card-title {
    font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary);
    display: flex; align-items: center; gap: 8px;
}
.detail-card-body { padding: 22px 24px; }

.inv-number {
    font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800;
    color: var(--accent); letter-spacing: -0.5px;
}
.inv-meta { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px 24px; margin-top: 20px; }
.inv-meta-item span { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 3px; }
.inv-meta-item strong { font-size: 14px; color: var(--text-primary); }

.amount-summary {
    display: grid; grid-template-columns: repeat(2,1fr); gap: 1px;
    background: var(--card-border); border: 1px solid var(--card-border);
    border-radius: var(--radius-sm); overflow: hidden; margin-top: 20px;
}
.amount-cell {
    background: var(--card-bg); padding: 14px 18px; text-align: center;
}
.amount-cell span { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 4px; }
.amount-cell strong { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: var(--text-primary); }
.amount-cell.balance strong { color: {{ $invoice->balance_due > 0 && $invoice->status !== 'cancelled' ? '#DC2626' : '#059669' }}; }

.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
}
.status-badge.draft          { background: #F1F5F9; color: #64748B; }
.status-badge.issued         { background: #EFF6FF; color: #2563EB; }
.status-badge.partially_paid { background: #FFFBEB; color: #D97706; }
.status-badge.paid           { background: #ECFDF5; color: #059669; }
.status-badge.overdue        { background: #FEF2F2; color: #DC2626; }
.status-badge.cancelled      { background: #F8FAFC; color: #94A3B8; }

.type-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
}
.type-badge.rent      { background: #EFF6FF; color: #2563EB; }
.type-badge.utilities { background: #FFF7ED; color: #EA580C; }
.type-badge.other     { background: #F1F5F9; color: #64748B; }

.notes-block {
    padding: 14px 18px; background: var(--page-bg);
    border-radius: var(--radius-sm); font-size: 13px; color: var(--text-primary);
    white-space: pre-wrap; word-break: break-word; line-height: 1.6;
}

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
        <h1 class="page-header-title">{{ $invoice->invoice_number }}</h1>
        <p class="page-header-sub">
            {{ $invoice->tenant_name }} &mdash; {{ $invoice->property_name }}{{ $invoice->unit ? ' / '.$invoice->unit : '' }}
        </p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <button type="button" class="btn btn-outline"
                onclick="openInvPdf('{{ route('invoices.pdf.preview', $invoice) }}', '{{ $invoice->invoice_number }}')">
            <i class="fa-solid fa-file-pdf"></i> Preview PDF
        </button>
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline" download>
            <i class="fa-solid fa-download"></i> Download
        </a>
        @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline">
            <i class="fa-solid fa-pen"></i> Edit
        </a>
        @endif
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
              onsubmit="return confirm('Delete invoice {{ $invoice->invoice_number }}? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
@endif

<div class="detail-card">
    <div class="detail-card-header">
        <div>
            <div class="inv-number">{{ $invoice->invoice_number }}</div>
            <div style="margin-top:4px;display:flex;gap:8px;align-items:center">
                <span class="status-badge {{ $invoice->status }}">
                    <i class="fa-solid fa-circle" style="font-size:5px"></i>
                    {{ $invoice->status_label }}
                </span>
                <span class="type-badge {{ $invoice->type }}">{{ $invoice->type_label }}</span>
            </div>
        </div>
        <div style="text-align:right;font-size:12px;color:var(--text-muted)">
            <div>Invoice Date <strong style="color:var(--text-primary)">{{ $invoice->invoice_date->format('d M Y') }}</strong></div>
            @if($invoice->status === 'overdue')
            <div style="margin-top:4px"><span style="font-size:11px;color:#DC2626;font-weight:600">Overdue</span></div>
            @endif
        </div>
    </div>
    <div class="detail-card-body">
        <div class="inv-meta">
            <div class="inv-meta-item">
                <span>Tenant</span>
                <strong>
                    @if($invoice->tenant)
                        <a href="{{ route('tenants.show', $invoice->tenant) }}" style="color:var(--text-primary);text-decoration:none">{{ $invoice->tenant_name }}</a>
                    @else
                        {{ $invoice->tenant_name }}
                    @endif
                </strong>
            </div>
            <div class="inv-meta-item"><span>Tenant Code</span><strong>{{ $invoice->tenant_code ?: '—' }}</strong></div>
            <div class="inv-meta-item"><span>Rental Lines</span><strong>{{ $invoice->line_count }}</strong></div>
        </div>

        <div class="amount-summary" style="grid-template-columns:repeat(4,1fr)">
            <div class="amount-cell">
                <span>Subtotal (Excl. VAT)</span>
                <strong>{{ number_format($invoice->amount, 3) }}</strong>
            </div>
            <div class="amount-cell">
                <span>VAT ({{ number_format($invoice->vat_rate, 2) }}%)</span>
                <strong>{{ number_format($invoice->vat_amount, 3) }}</strong>
            </div>
            <div class="amount-cell">
                <span>Total (Incl. VAT)</span>
                <strong>{{ number_format($invoice->total_incl_vat, 3) }}</strong>
            </div>
            <div class="amount-cell balance">
                <span>Balance Due</span>
                <strong>{{ number_format($invoice->balance_due, 3) }}</strong>
            </div>
        </div>

        @if(!empty($invoice->lines))
        <div style="margin-top:20px">
            <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px">Rental Lines</div>
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:var(--page-bg)">
                        <th style="text-align:left;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Property</th>
                        <th style="text-align:left;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Unit</th>
                        <th style="text-align:left;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Lease No.</th>
                        <th style="text-align:left;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Period</th>
                        <th style="text-align:right;padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted)">Rent (BHD)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->lines as $line)
                    <tr style="border-bottom:1px solid var(--card-border)">
                        <td style="padding:8px 10px">{{ $line['property_name'] ?? '—' }}</td>
                        <td style="padding:8px 10px">{{ !empty($line['unit'] ?? null) ? $line['unit'] : '—' }}</td>
                        <td style="padding:8px 10px">{{ !empty($line['lease_agreement_no'] ?? null) ? $line['lease_agreement_no'] : '—' }}</td>
                        <td style="padding:8px 10px">
                            @if(!empty($line['rental_period_start']))
                                {{ \Illuminate\Support\Carbon::parse($line['rental_period_start'])->format('d M Y') }} &rarr; {{ !empty($line['rental_period_end']) ? \Illuminate\Support\Carbon::parse($line['rental_period_end'])->format('d M Y') : '—' }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="padding:8px 10px;text-align:right;font-family:'Outfit',sans-serif;font-weight:700">{{ number_format($line['amount'] ?? 0, 3) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($invoice->description)
        <div style="margin-top:18px">
            <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px">Description</div>
            <div class="notes-block">{{ $invoice->description }}</div>
        </div>
        @endif

        @if($invoice->notes)
        <div style="margin-top:14px">
            <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px">Internal Notes</div>
            <div class="notes-block" style="border-left:3px solid var(--accent-dim);padding-left:14px">{{ $invoice->notes }}</div>
        </div>
        @endif
    </div>
</div>

{{-- PDF PREVIEW MODAL --}}
<div class="pdf-modal-overlay" id="invPdfModal" onclick="closeInvPdf(event)">
    <div class="pdf-modal-box" onclick="event.stopPropagation()">
        <div class="pdf-modal-header">
            <i class="fa-solid fa-file-pdf" style="color:var(--accent);font-size:16px"></i>
            <span id="invPdfTitle">{{ $invoice->invoice_number }}</span>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline btn-sm" download>
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
