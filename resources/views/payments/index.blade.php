@extends('layouts.admin')

@section('title', 'Payments')
@section('topbar-title', 'Payments')

@push('styles')
<style>

</style>
@endpush

@section('content')

@section('page-title', 'Payments')
@section('page-subtitle', 'All payments received across invoices')


{{-- ═══════════════════════ MOBILE SCREEN ═══════════════════════ --}}
@php
    $payMethodLabels = ['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'online_card' => 'Online / Card'];
@endphp
<div class="m-screen">
    <div class="ps-stat-strip">
        <div class="ps-stat"><div class="ps-stat-label">Collected &middot; {{ now()->format('M') }}</div><div class="ps-stat-value">BHD {{ number_format($stats['this_month'], 0) }}</div></div>
        <div class="ps-stat"><div class="ps-stat-label">All-time</div><div class="ps-stat-value is-gold">BHD {{ number_format($stats['total_collected'], 0) }}</div></div>
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
                <div class="m-row-icon" style="background:var(--ps-success-bg);color:var(--ps-success);"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <div style="flex:1;min-width:0;">
                    <div class="m-row-title">{{ $pmt->payment_number }}</div>
                    <div class="m-row-sub">{{ $mInv?->tenant_name ?? '—' }} &middot; {{ $pmt->payment_date->format('d M Y') }}</div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                    <div style="font-size:1rem;font-weight:700;color:var(--ps-success);">BHD {{ number_format($pmt->amount, 0) }}</div>
                    <span class="m-row-badge" style="background:var(--ps-bg);color:var(--ps-muted-deep);">{{ $pmt->method_label }}</span>
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
        <div class="stat-card-top">
            <span class="stat-icon green"><i class="fa-solid fa-coins"></i></span>
            <span class="stat-lbl">Total Collected (BHD)</span>
        </div>
        <div class="stat-val">{{ number_format($stats['total_collected'], 3) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon teal"><i class="fa-solid fa-money-bill-transfer"></i></span>
            <span class="stat-lbl">Transactions</span>
        </div>
        <div class="stat-val">{{ $stats['count'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-top">
            <span class="stat-icon blue"><i class="fa-solid fa-calendar-check"></i></span>
            <span class="stat-lbl">This Month (BHD)</span>
        </div>
        <div class="stat-val">{{ number_format($stats['this_month'], 3) }}</div>
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
                    <td class="cell-id">
                        {{ $pmt->payment_number }}
                    </td>
                    <td style="white-space:nowrap;font-size:12px">{{ $pmt->payment_date->format('d M Y') }}</td>
                    <td>{{ $inv?->tenant_name ?? '—' }}</td>
                    <td>
                        @if($inv)
                        <a href="{{ route('invoices.show', $inv) }}" onclick="event.stopPropagation()"
                           class="cell-id">
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
                        <span class="badge">
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
                    <td class="cell-muted">{{ $pmt->reference ?: '—' }}</td>
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
    <div class="table-footer">
        <div class="result-count">
            Showing <strong>{{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }}</strong>
            of <strong>{{ number_format($payments->total()) }}</strong> payments
        </div>
        {{ $payments->links() }}
    </div>
    @endif
</div>

@endsection
