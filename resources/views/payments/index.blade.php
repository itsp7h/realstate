@extends('layouts.admin')

@section('title', 'Payments')
@section('topbar-title', 'Payments')

@push('styles')
<style>

.method-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 600;
    background: var(--page-bg); color: var(--text-secondary); border: 1px solid var(--card-border);
}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Payments</h1>
        <p class="page-header-sub">All payments received across invoices</p>
    </div>
</div>

{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════ --}}
@php
    $payMethodLabels = ['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'online_card' => 'Online / Card'];
@endphp
<div class="m-screen">
    <div style="background:linear-gradient(135deg,#10141F,#232B42);border-radius:18px;padding:20px;display:flex;gap:24px;">
        <div style="flex:1;"><div style="font-size:10px;letter-spacing:1px;font-weight:600;color:#9FB0CE;">COLLECTED &middot; {{ now()->format('M') }}</div><div style="font-size:21px;font-weight:800;color:#7ED8AC;">BHD {{ number_format($stats['this_month'], 0) }}</div></div>
        <div style="flex:1;"><div style="font-size:10px;letter-spacing:1px;font-weight:600;color:#9FB0CE;">ALL-TIME</div><div style="font-size:21px;font-weight:800;color:#E7B266;">BHD {{ number_format($stats['total_collected'], 0) }}</div></div>
    </div>
    <div class="m-chip-row no-sb">
        <a href="{{ route('payments.index') }}" class="m-chip {{ !request('method') ? 'active' : '' }}">All</a>
        @foreach($payMethodLabels as $val => $label)
            <a href="{{ route('payments.index', ['method' => $val]) }}" class="m-chip {{ request('method') === $val ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="m-row-list">
        @forelse($payments as $pmt)
            @php $mInv = $pmt->invoice; @endphp
            <a href="{{ $mInv ? route('invoices.show', $mInv) : '#' }}" class="m-row-card">
                <div class="m-row-icon" style="background:#E6F6EE;color:#17A96C;"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <div style="flex:1;min-width:0;">
                    <div class="m-row-title">{{ $pmt->payment_number }}</div>
                    <div class="m-row-sub">{{ $mInv?->tenant_name ?? '—' }} &middot; {{ $pmt->payment_date->format('d M Y') }}</div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                    <div style="font-size:13.5px;font-weight:800;color:#17A96C;">BHD {{ number_format($pmt->amount, 0) }}</div>
                    <span class="m-row-badge" style="background:var(--m-line);color:#6B7688;">{{ $pmt->method_label }}</span>
                </div>
            </a>
        @empty
            <div class="m-empty">
                <div class="m-empty-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <div class="m-empty-title">No payments recorded yet</div>
                <div class="m-empty-sub">Try adjusting your filters.</div>
            </div>
        @endforelse
    </div>
</div>

<div class="stats-grid m-hide-desktop-index">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-coins"></i></div>
        <div>
            <div class="stat-val">{{ number_format($stats['total_collected'], 3) }}</div>
            <div class="stat-lbl">Total Collected (BHD)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-money-bill-transfer"></i></div>
        <div>
            <div class="stat-val">{{ $stats['count'] }}</div>
            <div class="stat-lbl">Transactions</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-calendar-check"></i></div>
        <div>
            <div class="stat-val">{{ number_format($stats['this_month'], 3) }}</div>
            <div class="stat-lbl">This Month (BHD)</div>
        </div>
    </div>
</div>

<div class="table-card m-hide-desktop-index">
    <form method="GET" action="{{ route('payments.index') }}">
        <div class="filter-bar">
            <div class="filter-group is-search">
                <label for="f_search">Search</label>
                <input type="search" id="f_search" name="search" value="{{ request('search') }}" placeholder="Search payment #, tenant, invoice #, reference…">
            </div>
            <div class="filter-group">
                <label for="f_method">Method</label>
                <select id="f_method" name="method" onchange="this.form.submit()">
                    <option value="">All Methods</option>
                    @foreach(['cash'=>'Cash','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','online_card'=>'Online / Card'] as $v => $l)
                    <option value="{{ $v }}" {{ request('method') === $v ? 'selected' : '' }}>{{ $l }}</option>
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
                @if(request()->hasAny(['search','method','date_from','date_to']))
                <a href="{{ route('payments.index') }}" class="btn btn-outline btn-sm"><i class="fa-solid fa-xmark"></i> Reset</a>
                @endif
            </div>
        </div>
    </form>
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
                    <td style="font-family:'Outfit',sans-serif;font-weight:700;color:var(--tone-success-fg)">
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
