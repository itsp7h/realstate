@extends('layouts.admin')

@section('title', 'Payments')
@section('topbar-title', 'Payments')

@push('styles')
<style>
.pay-stats {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px; margin-bottom: 24px;
}
.pay-stat {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
}
.pay-stat-icon {
    width: 40px; height: 40px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;
}
.pay-stat-icon.green { background: #ECFDF5; color: #059669; }
.pay-stat-icon.teal  { background: #F0FDFA; color: #0D9488; }
.pay-stat-icon.blue  { background: #EFF6FF; color: #2563EB; }
.pay-stat-val { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.pay-stat-lbl { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

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

.method-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 600;
    background: var(--page-bg); color: var(--text-secondary); border: 1px solid var(--card-border);
}

.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius); overflow: hidden; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Payments</h1>
        <p class="page-header-sub">All payments received across invoices</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif

<div class="pay-stats">
    <div class="pay-stat">
        <div class="pay-stat-icon green"><i class="fa-solid fa-coins"></i></div>
        <div>
            <div class="pay-stat-val">{{ number_format($stats['total_collected'], 3) }}</div>
            <div class="pay-stat-lbl">Total Collected (BHD)</div>
        </div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-icon teal"><i class="fa-solid fa-money-bill-transfer"></i></div>
        <div>
            <div class="pay-stat-val">{{ $stats['count'] }}</div>
            <div class="pay-stat-lbl">Transactions</div>
        </div>
    </div>
    <div class="pay-stat">
        <div class="pay-stat-icon blue"><i class="fa-solid fa-calendar-check"></i></div>
        <div>
            <div class="pay-stat-val">{{ number_format($stats['this_month'], 3) }}</div>
            <div class="pay-stat-lbl">This Month (BHD)</div>
        </div>
    </div>
</div>

<form method="GET" action="{{ route('payments.index') }}" class="filter-bar">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search payment #, tenant, invoice #, reference…">
    <select name="method" onchange="this.form.submit()">
        <option value="">All Methods</option>
        @foreach(['cash'=>'Cash','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','online_card'=>'Online / Card'] as $v => $l)
        <option value="{{ $v }}" {{ request('method') === $v ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ request('date_from') }}" title="Date from">
    <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Date to">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    @if(request()->hasAny(['search','method','date_from','date_to']))
    <a href="{{ route('payments.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
    @endif
</form>

<div class="table-card">
    @if($payments->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
        <i class="fa-solid fa-money-bill-transfer" style="font-size:36px;display:block;margin-bottom:12px;opacity:0.3"></i>
        No payments recorded yet
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Payment #</th>
                    <th>Date</th>
                    <th>Tenant</th>
                    <th>Invoice #</th>
                    <th>Amount (BHD)</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $pmt)
                @php $inv = $pmt->invoice; @endphp
                <tr data-href="{{ $inv ? route('invoices.show', $inv) : '#' }}" style="cursor:pointer">
                    <td style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--accent)">
                        {{ $pmt->payment_number }}
                    </td>
                    <td style="white-space:nowrap;font-size:12px">{{ $pmt->payment_date->format('d M Y') }}</td>
                    <td>{{ $inv?->tenant_name ?? '—' }}</td>
                    <td>
                        @if($inv)
                        <a href="{{ route('invoices.show', $inv) }}" onclick="event.stopPropagation()"
                           style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--accent);text-decoration:none;font-size:13px">
                            {{ $inv->invoice_number }}
                        </a>
                        @else
                        <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td style="font-family:'Outfit',sans-serif;font-weight:700;color:#059669">
                        {{ number_format($pmt->amount, 3) }}
                    </td>
                    <td>
                        <span class="method-badge">
                            <i class="fa-solid {{ match($pmt->method) {
                                'cash'          => 'fa-money-bill',
                                'bank_transfer' => 'fa-building-columns',
                                'cheque'        => 'fa-money-check',
                                'online_card'   => 'fa-credit-card',
                                default         => 'fa-circle-dollar-to-slot'
                            } }}" style="font-size:10px"></i>
                            {{ $pmt->method_label }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $pmt->reference ?: '—' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center" onclick="event.stopPropagation()">
                            @if($inv)
                            <a href="{{ route('invoices.payments.receipt', [$inv, $pmt]) }}"
                               class="btn btn-outline btn-sm" title="Download Receipt" target="_blank">
                                <i class="fa-solid fa-file-arrow-down"></i>
                            </a>
                            <form method="POST" action="{{ route('invoices.payments.destroy', [$inv, $pmt]) }}"
                                  onsubmit="return confirm('Remove payment {{ $pmt->payment_number }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:14px 18px;border-top:1px solid var(--card-border);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--text-muted)">
        <div>Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}</div>
        <div>{{ $payments->links() }}</div>
    </div>
    @endif
</div>

@endsection
