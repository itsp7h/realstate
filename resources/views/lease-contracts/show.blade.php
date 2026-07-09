@extends('layouts.admin')

@section('title', $leaseContract->lease_agreement_no . ' — Lease Contract')
@section('topbar-title', 'Lease Contract Detail')

@push('styles')
<style>
.contract-hero {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    padding: 28px;
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.contract-icon-wrap {
    width: 64px; height: 64px; border-radius: var(--radius);
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; flex-shrink: 0;
    border: 2px solid var(--accent);
}
.contract-agr-no {
    font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800;
    color: var(--text-primary); line-height: 1.2;
}
.contract-meta { display: flex; align-items: center; gap: 10px; margin-top: 6px; flex-wrap: wrap; }
.hero-actions { margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap; }

.section-panel {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    margin-bottom: 16px;
}
.section-panel-header {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--card-border);
}
.section-panel-icon {
    width: 36px; height: 36px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.section-panel-header h3 {
    font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary);
    margin: 0;
}
.section-panel-body { padding: 20px; }

.detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.detail-item { display: flex; flex-direction: column; gap: 4px; }
.detail-label {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.06em; color: var(--text-muted);
}
.detail-value {
    font-size: 14px; font-weight: 600; color: var(--text-primary);
}
.detail-value.empty { color: var(--text-muted); font-weight: 400; font-style: italic; }
.detail-value a { color: var(--info); text-decoration: none; }
.detail-value a:hover { text-decoration: underline; }

.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 700;
}
.status-active   { background:#ECFDF5;color:var(--success);border:1px solid #A7F3D0; }
.status-expiring { background:#FFFBEB;color:var(--warning);border:1px solid #FDE68A; }
.status-expired  { background:#F1F5F9;color:var(--text-muted);border:1px solid var(--card-border); }
.status-upcoming { background:#EFF6FF;color:var(--info);border:1px solid #BFDBFE; }

.lease-progress-wrap { margin-top: 12px; }
.lease-progress-bar { height: 8px; border-radius: 8px; background: var(--card-border); position: relative; overflow: hidden; }
.lease-progress-fill { height: 100%; border-radius: 8px; background: var(--accent); transition: width 1s ease; }
.lease-progress-labels { display: flex; justify-content: space-between; margin-top: 6px; }
.lease-progress-labels span { font-size: 11px; color: var(--text-muted); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <a href="{{ route('lease-contracts.index') }}">Lease Contracts</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>{{ $leaseContract->lease_agreement_no }}</span>
        </div>
        <h1 class="page-header-title">Contract Detail</h1>
        <p class="page-header-sub">Full breakdown of lease agreement {{ $leaseContract->lease_agreement_no }}</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('lease-contracts.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- HERO --}}
<div class="contract-hero">
    <div class="contract-icon-wrap">
        <i class="fa-solid fa-file-contract"></i>
    </div>
    <div style="flex:1;min-width:0;">
        <div class="contract-agr-no">{{ $leaseContract->lease_agreement_no }}</div>
        <div class="contract-meta">
            @php
                $statusMap = [
                    'active'   => ['class'=>'status-active',   'icon'=>'fa-circle-check',   'label'=>'Active'],
                    'expiring' => ['class'=>'status-expiring', 'icon'=>'fa-circle-exclamation','label'=>'Expiring Soon'],
                    'expired'  => ['class'=>'status-expired',  'icon'=>'fa-circle-xmark',   'label'=>'Expired'],
                    'upcoming' => ['class'=>'status-upcoming', 'icon'=>'fa-circle-arrow-right','label'=>'Upcoming'],
                ];
                $s = $statusMap[$leaseContract->status] ?? $statusMap['expired'];
            @endphp
            <span class="status-badge {{ $s['class'] }}">
                <i class="fa-solid {{ $s['icon'] }}"></i> {{ $s['label'] }}
            </span>
            @if($leaseContract->property_name)
                <span style="font-size:13px;color:var(--text-muted);">
                    <i class="fa-solid fa-building" style="margin-right:4px;"></i>{{ $leaseContract->property_name }}
                </span>
            @endif
            <span style="font-size:13px;color:var(--text-muted);">
                <i class="fa-regular fa-calendar" style="margin-right:4px;"></i>
                {{ $leaseContract->date?->format('d M Y') ?? '—' }}
            </span>
        </div>
        {{-- Lease progress bar --}}
        @if($leaseContract->lease_start_date && $leaseContract->lease_end_date)
        @php
            $start = $leaseContract->lease_start_date->timestamp;
            $end   = $leaseContract->lease_end_date->timestamp;
            $now   = now()->timestamp;
            $total = max($end - $start, 1);
            $elapsed = min(max($now - $start, 0), $total);
            $pct = round(($elapsed / $total) * 100);
        @endphp
        <div class="lease-progress-wrap">
            <div class="lease-progress-bar">
                <div class="lease-progress-fill" style="width:{{ $pct }}%;"></div>
            </div>
            <div class="lease-progress-labels">
                <span>{{ $leaseContract->lease_start_date->format('d M Y') }}</span>
                <span>{{ $pct }}% elapsed</span>
                <span>{{ $leaseContract->lease_end_date->format('d M Y') }}</span>
            </div>
        </div>
        @endif
    </div>
    <div class="hero-actions">
        <a href="{{ route('lease-contracts.edit', $leaseContract) }}" class="btn btn-primary">
            <i class="fa-solid fa-pen"></i> Edit
        </a>
        <form method="POST" action="{{ route('lease-contracts.destroy', $leaseContract) }}"
              onsubmit="return confirm('Delete this contract permanently?')" style="display:inline;">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fa-solid fa-trash"></i> Delete
            </button>
        </form>
    </div>
</div>

{{-- ── TENANT & CONTRACT ─────────────────────────────────────── --}}
<div class="section-panel">
    <div class="section-panel-header">
        <div class="section-panel-icon" style="background:#FFF7ED;color:#C2410C;"><i class="fa-solid fa-file-contract"></i></div>
        <h3>Contract &amp; Tenant</h3>
    </div>
    <div class="section-panel-body">
        <div class="detail-grid">

            <div class="detail-item">
                <div class="detail-label">Agreement No.</div>
                <div class="detail-value">{{ $leaseContract->lease_agreement_no }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Date</div>
                <div class="detail-value">{{ $leaseContract->date?->format('d M Y') ?? '—' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Tenant Name</div>
                <div class="detail-value {{ !$leaseContract->tenant_name ? 'empty' : '' }}">
                    @if($leaseContract->tenant)
                        <a href="{{ route('tenants.show', $leaseContract->tenant) }}">{{ $leaseContract->tenant_name }}</a>
                    @else
                        {{ $leaseContract->tenant_name ?: '—' }}
                    @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Description</div>
                <div class="detail-value {{ !$leaseContract->description ? 'empty' : '' }}">
                    {{ $leaseContract->description ?: 'Not specified' }}
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ── PROPERTY LOCATION ─────────────────────────────────────── --}}
<div class="section-panel">
    <div class="section-panel-header">
        <div class="section-panel-icon" style="background:#F0FDF4;color:#15803D;"><i class="fa-solid fa-location-dot"></i></div>
        <h3>Property Location</h3>
    </div>
    <div class="section-panel-body">
        <div class="detail-grid">

            <div class="detail-item">
                <div class="detail-label">Property Name</div>
                <div class="detail-value {{ !$leaseContract->property_name ? 'empty' : '' }}">{{ $leaseContract->property_name ?: '—' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Property Code</div>
                <div class="detail-value {{ !$leaseContract->property_code ? 'empty' : '' }}">{{ $leaseContract->property_code ?: '—' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Block</div>
                <div class="detail-value {{ !$leaseContract->block_name ? 'empty' : '' }}">
                    {{ $leaseContract->block_name ?: '—' }}
                    @if($leaseContract->block_code) <span style="font-size:11px;color:var(--text-muted);margin-left:4px;">({{ $leaseContract->block_code }})</span> @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Floor</div>
                <div class="detail-value {{ !$leaseContract->floor_name ? 'empty' : '' }}">
                    {{ $leaseContract->floor_name ?: '—' }}
                    @if($leaseContract->floor_code) <span style="font-size:11px;color:var(--text-muted);margin-left:4px;">({{ $leaseContract->floor_code }})</span> @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Unit</div>
                <div class="detail-value {{ !$leaseContract->unit ? 'empty' : '' }}">
                    @if($leaseContract->propertyUnit)
                        <a href="{{ route('property-units.show', $leaseContract->propertyUnit) }}">{{ $leaseContract->unit ?: $leaseContract->propertyUnit->unit_name }}</a>
                    @else
                        {{ $leaseContract->unit ?: '—' }}
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ── LEASE TERM ────────────────────────────────────────────── --}}
<div class="section-panel">
    <div class="section-panel-header">
        <div class="section-panel-icon" style="background:#EFF6FF;color:#1D4ED8;"><i class="fa-solid fa-calendar-days"></i></div>
        <h3>Lease Term</h3>
    </div>
    <div class="section-panel-body">
        <div class="detail-grid">

            <div class="detail-item">
                <div class="detail-label">Start Date</div>
                <div class="detail-value">{{ $leaseContract->lease_start_date?->format('d M Y') ?? '—' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">End Date</div>
                <div class="detail-value">{{ $leaseContract->lease_end_date?->format('d M Y') ?? '—' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Break Date</div>
                <div class="detail-value {{ !$leaseContract->lease_break_date ? 'empty' : '' }}">{{ $leaseContract->lease_break_date?->format('d M Y') ?? 'None' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Notice Period</div>
                <div class="detail-value {{ !$leaseContract->notice_period ? 'empty' : '' }}">{{ $leaseContract->notice_period ?: 'Not specified' }}</div>
            </div>

            @if($leaseContract->lease_start_date && $leaseContract->lease_end_date)
            <div class="detail-item">
                <div class="detail-label">Duration</div>
                <div class="detail-value">{{ $leaseContract->lease_start_date->diffInMonths($leaseContract->lease_end_date) }} months</div>
            </div>
            @endif

        </div>
    </div>
</div>

{{-- ── RENT COMPONENT ────────────────────────────────────────── --}}
<div class="section-panel">
    <div class="section-panel-header">
        <div class="section-panel-icon" style="background:#FFF1F2;color:#BE123C;"><i class="fa-solid fa-coins"></i></div>
        <h3>Rent Component</h3>
    </div>
    <div class="section-panel-body">
        <div class="detail-grid">

            <div class="detail-item">
                <div class="detail-label">Rent / Month</div>
                <div class="detail-value" style="font-family:'Outfit',sans-serif;font-size:18px;font-weight:800;color:var(--text-primary);">
                    @if($leaseContract->rent_per_month !== null)
                        {{ number_format($leaseContract->rent_per_month, 3) }}
                        @if($leaseContract->currency)
                            <span style="font-size:13px;font-weight:600;color:var(--text-muted);margin-left:4px;">{{ $leaseContract->currency }}</span>
                        @endif
                    @else
                        <span class="empty" style="font-size:14px;">Not set</span>
                    @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Invoicing Frequency</div>
                <div class="detail-value {{ !$leaseContract->invoicing_frequency ? 'empty' : '' }}">{{ $leaseContract->invoicing_frequency ?: 'Not specified' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Rent Start</div>
                <div class="detail-value {{ !$leaseContract->rent_start_date ? 'empty' : '' }}">{{ $leaseContract->rent_start_date?->format('d M Y') ?? '—' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Rent End</div>
                <div class="detail-value {{ !$leaseContract->rent_end_date ? 'empty' : '' }}">{{ $leaseContract->rent_end_date?->format('d M Y') ?? '—' }}</div>
            </div>

        </div>
    </div>
</div>

{{-- ── SERVICE CHARGE ────────────────────────────────────────── --}}
<div class="section-panel">
    <div class="section-panel-header">
        <div class="section-panel-icon" style="background:#F5F3FF;color:#6D28D9;"><i class="fa-solid fa-screwdriver-wrench"></i></div>
        <h3>Service Charge</h3>
    </div>
    <div class="section-panel-body">
        <div class="detail-grid">

            <div class="detail-item">
                <div class="detail-label">Amount (BD excl. VAT)</div>
                <div class="detail-value" style="font-family:'Outfit',sans-serif;font-size:18px;font-weight:800;">
                    @if($leaseContract->service_amount_bd_excl_vat !== null)
                        {{ number_format($leaseContract->service_amount_bd_excl_vat, 3) }}
                        <span style="font-size:13px;font-weight:600;color:var(--text-muted);margin-left:4px;">BHD</span>
                    @else
                        <span class="empty" style="font-size:14px;">Not set</span>
                    @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Service Frequency</div>
                <div class="detail-value {{ !$leaseContract->service_frequency ? 'empty' : '' }}">{{ $leaseContract->service_frequency ?: 'Not specified' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Service Start</div>
                <div class="detail-value {{ !$leaseContract->service_start_date ? 'empty' : '' }}">{{ $leaseContract->service_start_date?->format('d M Y') ?? '—' }}</div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Service End</div>
                <div class="detail-value {{ !$leaseContract->service_end_date ? 'empty' : '' }}">{{ $leaseContract->service_end_date?->format('d M Y') ?? '—' }}</div>
            </div>

        </div>
    </div>
</div>

{{-- ── FINANCIAL ─────────────────────────────────────────────── --}}
<div class="section-panel">
    <div class="section-panel-header">
        <div class="section-panel-icon" style="background:var(--accent-dim);color:#92400E;"><i class="fa-solid fa-landmark"></i></div>
        <h3>Financial</h3>
    </div>
    <div class="section-panel-body">
        <div class="detail-grid">

            <div class="detail-item">
                <div class="detail-label">Security Deposit</div>
                <div class="detail-value" style="font-family:'Outfit',sans-serif;font-size:18px;font-weight:800;">
                    @if($leaseContract->security_deposit !== null)
                        {{ number_format($leaseContract->security_deposit, 3) }}
                        @if($leaseContract->currency)
                            <span style="font-size:13px;font-weight:600;color:var(--text-muted);margin-left:4px;">{{ $leaseContract->currency }}</span>
                        @endif
                    @else
                        <span class="empty" style="font-size:14px;">Not set</span>
                    @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Rental Income Ledger</div>
                <div class="detail-value {{ !$leaseContract->rental_income_ledger ? 'empty' : '' }}">
                    {{ $leaseContract->rental_income_ledger ?: 'Not assigned' }}
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">EWA Cap</div>
                <div class="detail-value" style="font-family:'Outfit',sans-serif;font-size:18px;font-weight:800;">
                    @if($leaseContract->ewa_cap)
                        {{ number_format($leaseContract->ewa_cap, 3) }}
                        <span style="font-size:13px;font-weight:600;color:var(--text-muted);margin-left:4px;">BHD / bill</span>
                    @else
                        <span class="empty" style="font-size:14px;">Not set</span>
                    @endif
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">VAT</div>
                <div class="detail-value" style="font-family:'Outfit',sans-serif;font-size:18px;font-weight:800;">
                    @if($leaseContract->vat_enabled)
                        {{ number_format($leaseContract->vat_rate, 2) }}<span style="font-size:13px;font-weight:600;color:var(--text-muted);margin-left:4px;">%</span>
                    @else
                        <span class="empty" style="font-size:14px;">Not charged</span>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
