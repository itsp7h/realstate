@extends('layouts.admin')

@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@push('styles')
<style>
/* ── STATS ─────────────────────────────────────────────── */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
@media (max-width: 1100px) { .dash-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 640px)  { .dash-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.dash-stat {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 18px 20px;
    display: flex; align-items: center; gap: 14px;
    min-width: 0;
    transition: box-shadow 0.2s, transform 0.2s;
}
.dash-stat:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
a.dash-stat { text-decoration: none; cursor: pointer; }
.dash-table tr[data-href] { cursor: pointer; }
.dash-stat-icon {
    width: 44px; height: 44px; border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.dash-stat-icon.gold   { background: var(--accent-dim); color: var(--accent); }
.dash-stat-icon.blue   { background: #EFF6FF; color: #3B82F6; }
.dash-stat-icon.green  { background: #ECFDF5; color: #10B981; }
.dash-stat-icon.purple { background: #F5F3FF; color: #7C3AED; }
.dash-stat-icon.rose   { background: #FFF1F2; color: #F43F5E; }
.dash-stat-val { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.dash-stat-lbl { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

/* ── RECENT TABLES ──────────────────────────────────────── */
.dash-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr; } }
.dash-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    overflow: hidden;
}
.dash-card-head {
    padding: 14px 20px;
    border-bottom: 1px solid var(--card-border);
    display: flex; align-items: center; justify-content: space-between;
}
.dash-card-title {
    font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700;
    color: var(--text-primary); display: flex; align-items: center; gap: 8px;
}
.dash-card-title i { color: var(--accent); font-size: 13px; }
.dash-table { width: 100%; border-collapse: collapse; }
.dash-table th {
    padding: 8px 16px; font-size: 11px; font-weight: 700;
    color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;
    border-bottom: 1px solid var(--card-border); text-align: left;
    background: var(--page-bg);
}
.dash-table td {
    padding: 10px 16px; font-size: 13px; color: var(--text-secondary);
    border-bottom: 1px solid var(--card-border);
}
.dash-table tr:last-child td { border-bottom: none; }
.dash-table tr:hover td { background: var(--page-bg); }
.dash-code {
    font-family: 'Outfit', sans-serif; font-weight: 700;
    color: var(--text-primary); font-size: 13px;
}
.empty-dash { text-align: center; padding: 30px; color: var(--text-muted); font-size: 13px; }

/* ── FINANCIAL OVERVIEW CHART ───────────────────────────── */
.finance-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    padding: 24px;
    margin-bottom: 28px;
}
.finance-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}
.finance-head-left { display: flex; align-items: center; gap: 14px; }
.finance-icon {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: var(--accent); color: #0B1120;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    box-shadow: 0 4px 14px var(--accent-glow);
}
.finance-title { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); }
.finance-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.finance-year-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 20px;
    background: var(--page-bg); border: 1px solid var(--card-border);
    font-size: 12px; font-weight: 600; color: var(--text-secondary);
}
.finance-legend { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
.legend-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 6px 13px; border-radius: 20px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 12px; font-weight: 600;
    background: var(--page-bg); border: 1px solid var(--card-border);
    color: var(--text-secondary); cursor: pointer;
    transition: all 0.15s ease;
}
.legend-btn:hover { border-color: var(--card-border); background: #EEF2F7; }
.legend-btn.is-off { opacity: 0.45; text-decoration: line-through; }
.legend-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.finance-canvas-wrap { position: relative; width: 100%; height: 320px; }
.finance-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    height: 320px; color: var(--text-muted); font-size: 13px; gap: 10px;
}
.finance-empty i { font-size: 28px; opacity: 0.5; }

/* ── PROPERTY PERFORMANCE CARDS ──────────────────────────── */
.property-section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
}
.property-section-title {
    font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px;
    color: var(--text-primary); display: flex; align-items: center; gap: 9px;
}
.property-section-title i { color: var(--accent); }
.property-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 20px;
    margin-bottom: 28px;
    overflow-x: auto;
    scroll-snap-type: x proximity;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--card-border) transparent;
    padding: 4px 2px 10px;
}
.property-grid::-webkit-scrollbar { height: 8px; }
.property-grid::-webkit-scrollbar-track { background: transparent; }
.property-grid::-webkit-scrollbar-thumb { background: var(--card-border); border-radius: 4px; }
.property-grid::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
.property-card {
    width: 420px;
    flex: 0 0 420px;
    scroll-snap-align: start;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    display: flex; flex-direction: column;
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.2s;
}
@media (max-width: 460px) { .property-card { width: 85vw; flex-basis: 85vw; } }
.property-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.property-carousel { position: relative; height: 190px; background: var(--page-bg); flex-shrink: 0; }
.property-carousel-track { display: flex; height: 100%; transition: transform 0.35s cubic-bezier(0.22,1,0.36,1); }
.property-carousel-track img { width: 100%; height: 100%; object-fit: cover; flex-shrink: 0; }
.property-carousel-empty {
    height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: var(--text-muted); gap: 6px;
}
.property-carousel-empty i { font-size: 26px; opacity: 0.45; }
.property-carousel-empty span { font-size: 11.5px; }
.property-carousel-btn {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 30px; height: 30px; border-radius: 50%; border: none;
    background: rgba(11,17,32,0.45); color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; transition: background 0.15s;
    backdrop-filter: blur(2px);
}
.property-carousel-btn:hover { background: rgba(11,17,32,0.7); }
.property-carousel-btn.prev { left: 10px; }
.property-carousel-btn.next { right: 10px; }
.property-carousel-dots {
    position: absolute; bottom: 10px; left: 0; right: 0;
    display: flex; justify-content: center; gap: 5px;
}
.property-carousel-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: rgba(255,255,255,0.55); border: none; padding: 0; cursor: pointer;
}
.property-carousel-dot.active { background: #fff; width: 16px; border-radius: 3px; }
.property-body { padding: 20px 22px 22px; display: flex; flex-direction: column; flex: 1; }
.property-title-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 3px; }
.property-name { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 17px; color: var(--text-primary); }
.property-address { font-size: 12.5px; color: var(--text-muted); margin-bottom: 18px; }
.property-address i { margin-right: 4px; }
.property-period-label {
    font-size: 11px; font-weight: 700; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;
}
.property-stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 18px; }
.property-stat-box { border-radius: var(--radius-sm); padding: 10px 8px; text-align: center; border: 1px solid; }
.property-stat-box.income { background: #ECFDF5; border-color: #A7F3D0; }
.property-stat-box.net    { background: #EFF6FF; border-color: #BFDBFE; }
.property-stat-box.occ    { background: var(--page-bg); border-color: var(--card-border); }
.property-stat-label { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
.property-stat-box.income .property-stat-label { color: #065F46; }
.property-stat-box.net .property-stat-label    { color: #1E40AF; }
.property-stat-box.occ .property-stat-label    { color: var(--text-secondary); }
.property-stat-value { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 15px; margin-top: 2px; }
.property-stat-box.income .property-stat-value { color: #047857; }
.property-stat-box.net .property-stat-value    { color: #1D4ED8; }
.property-stat-box.occ .property-stat-value    { color: var(--text-primary); }
.property-stat-sub { font-size: 9.5px; color: var(--text-muted); margin-top: 1px; }
.property-expense-title {
    font-size: 10.5px; font-weight: 700; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;
}
.property-expense-list {
    background: var(--page-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius-sm); padding: 12px 14px; margin-top: auto;
    box-sizing: border-box; min-height: 100px;
    display: flex; flex-direction: column; justify-content: center;
}
.property-expense-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 4px 0; font-size: 12.5px;
}
.property-expense-row + .property-expense-row { border-top: 1px dashed var(--card-border); }
.property-expense-row .label { color: var(--text-primary); font-weight: 600; display: flex; align-items: center; gap: 8px; }
.property-expense-row .label i { width: 14px; text-align: center; }
.property-expense-row .value { font-weight: 700; color: var(--text-primary); }
.property-expense-empty { text-align: center; font-size: 12px; color: var(--text-muted); padding: 6px 0; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
/* ── CLICKABLE PROPERTY CARDS ──────────────────────────────── */
document.querySelectorAll('.property-card[data-href]').forEach(function (card) {
    card.addEventListener('click', function () {
        window.location = card.dataset.href;
    });
});

/* ── PROPERTY PHOTO CAROUSELS ─────────────────────────────── */
document.querySelectorAll('.property-carousel').forEach(function (carousel) {
    const track = carousel.querySelector('.property-carousel-track');
    const slides = carousel.querySelectorAll('.property-carousel-track img');
    const dots = carousel.querySelectorAll('.property-carousel-dot');
    if (!track || slides.length <= 1) return;
    let index = 0;

    function render() {
        track.style.transform = 'translateX(-' + (index * 100) + '%)';
        dots.forEach((d, i) => d.classList.toggle('active', i === index));
    }
    carousel.querySelector('.prev')?.addEventListener('click', () => {
        index = (index - 1 + slides.length) % slides.length;
        render();
    });
    carousel.querySelector('.next')?.addEventListener('click', () => {
        index = (index + 1) % slides.length;
        render();
    });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { index = i; render(); }));
});

/* ── PORTFOLIO FINANCIAL CHART ────────────────────────────── */
(function () {
    const canvas = document.getElementById('portfolioChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const chartData = @json($chartData);
    const ctx = canvas.getContext('2d');

    function gradient(hex) {
        const g = ctx.createLinearGradient(0, 0, 0, 320);
        g.addColorStop(0, hex + 'E6');
        g.addColorStop(1, hex + '1A');
        return g;
    }

    const portfolioChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Income',   data: chartData.income,   backgroundColor: gradient('#10b981'), hoverBackgroundColor: '#10b981', borderRadius: 6, borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 },
                { label: 'Expenses', data: chartData.expenses, backgroundColor: gradient('#ef4444'), hoverBackgroundColor: '#ef4444', borderRadius: 6, borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 },
                { label: 'Credits',  data: chartData.credits,  backgroundColor: gradient('#0ea5e9'), hoverBackgroundColor: '#0ea5e9', borderRadius: 6, borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 },
                { label: 'Debits',   data: chartData.debits,   backgroundColor: gradient('#f59e0b'), hoverBackgroundColor: '#f59e0b', borderRadius: 6, borderSkipped: false, barPercentage: 0.65, categoryPercentage: 0.85 },
                { label: 'Profit', data: chartData.profit, type: 'line', borderColor: '#8b5cf6', borderWidth: 3, tension: 0.4, fill: false, pointRadius: 4, pointHoverRadius: 7, pointBackgroundColor: '#fff', pointBorderColor: '#8b5cf6', pointBorderWidth: 2 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0B1120', titleFont: { family: 'Outfit', size: 13, weight: '700' },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 12.5, weight: '500' },
                    padding: 12, cornerRadius: 10, boxPadding: 6, usePointStyle: true,
                    borderColor: '#1A2540', borderWidth: 1,
                    callbacks: {
                        label: function (c) {
                            let l = c.dataset.label || '';
                            if (l) l += ': BHD ';
                            if (c.parsed.y !== null) l += new Intl.NumberFormat('en-US').format(c.parsed.y);
                            return l;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: 'rgba(148,163,184,0.15)', tickLength: 0, borderDash: [4, 4] },
                    ticks: {
                        padding: 10, color: '#64748b', font: { family: 'Plus Jakarta Sans', size: 11.5, weight: '500' },
                        callback: (v) => (Math.abs(v) >= 1000 ? 'BHD ' + (v / 1000) + 'k' : 'BHD ' + v),
                    },
                },
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', size: 11.5, weight: '600' } } },
            },
        },
    });

    document.querySelectorAll('.legend-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const i = Number(btn.dataset.dataset);
            const meta = portfolioChart.getDatasetMeta(i);
            meta.hidden = meta.hidden === null ? !portfolioChart.data.datasets[i].hidden : !meta.hidden;
            btn.classList.toggle('is-off', meta.hidden === true);
            portfolioChart.update();
        });
    });
})();
</script>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">Dashboard</h1>
        <p class="page-header-sub">Overview of your real estate portfolio</p>
    </div>
</div>

{{-- STATS --}}
<div class="dash-stats">
    <a href="{{ route('buildings.index') }}" class="dash-stat">
        <div class="dash-stat-icon gold"><i class="fa-solid fa-building"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['buildings'] }}</div>
            <div class="dash-stat-lbl">Buildings</div>
        </div>
    </a>
    <a href="{{ route('floors.global') }}" class="dash-stat">
        <div class="dash-stat-icon blue"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['floors'] }}</div>
            <div class="dash-stat-lbl">Floors</div>
        </div>
    </a>
    <a href="{{ route('property-units.index') }}" class="dash-stat">
        <div class="dash-stat-icon green"><i class="fa-solid fa-door-open"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['units'] }}</div>
            <div class="dash-stat-lbl">Total Units</div>
        </div>
    </a>
    <a href="{{ route('property-units.index', ['unit_condition' => 'Furnished']) }}" class="dash-stat">
        <div class="dash-stat-icon purple"><i class="fa-solid fa-couch"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['furnished'] }}</div>
            <div class="dash-stat-lbl">Furnished</div>
        </div>
    </a>
    <a href="{{ route('property-units.index', ['unit_condition' => 'Fitted']) }}" class="dash-stat">
        <div class="dash-stat-icon rose"><i class="fa-solid fa-hammer"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['fitted'] }}</div>
            <div class="dash-stat-lbl">Fitted</div>
        </div>
    </a>
</div>

{{-- PORTFOLIO FINANCIAL OVERVIEW --}}
<div class="finance-card">
    <div class="finance-card-head">
        <div class="finance-head-left">
            <div class="finance-icon"><i class="fa-solid fa-chart-line"></i></div>
            <div>
                <div class="finance-title">Portfolio Financial Overview</div>
                <div class="finance-sub">{{ $chartYear }} &middot; Combined income, expenses, profit, credits &amp; debits</div>
            </div>
        </div>
        <span class="finance-year-badge"><i class="fa-regular fa-calendar"></i> {{ $chartYear }}</span>
    </div>

    @if(collect($chartData['income'])->filter(fn($v) => $v !== null)->isEmpty())
        <div class="finance-empty">
            <i class="fa-solid fa-chart-line"></i>
            No financial activity recorded for {{ $chartYear }} yet
        </div>
    @else
        <div class="finance-legend">
            <button type="button" class="legend-btn" data-dataset="0"><span class="legend-dot" style="background:#10b981;"></span> Income</button>
            <button type="button" class="legend-btn" data-dataset="1"><span class="legend-dot" style="background:#ef4444;"></span> Expenses</button>
            <button type="button" class="legend-btn" data-dataset="2"><span class="legend-dot" style="background:#0ea5e9;"></span> Credits</button>
            <button type="button" class="legend-btn" data-dataset="3"><span class="legend-dot" style="background:#f59e0b;"></span> Debits</button>
            <button type="button" class="legend-btn" data-dataset="4"><span class="legend-dot" style="background:#8b5cf6;"></span> Profit</button>
        </div>
        <div class="finance-canvas-wrap">
            <canvas id="portfolioChart"></canvas>
        </div>
    @endif
</div>

{{-- INDIVIDUAL PROPERTY PERFORMANCE --}}
@if($buildingPerformance->isNotEmpty())
<div class="property-section-head">
    <h2 class="property-section-title"><i class="fa-solid fa-building"></i> Individual Property Performance</h2>
</div>
<div class="property-grid">
    @foreach($buildingPerformance as $perf)
    @php $building = $perf['building']; @endphp
    <div class="property-card" data-href="{{ route('buildings.show', $building) }}">
        <div class="property-carousel">
            @if($building->images->isNotEmpty())
                <div class="property-carousel-track">
                    @foreach($building->images as $image)
                        <img src="{{ $image->url }}" alt="{{ $building->property_name }}">
                    @endforeach
                </div>
                @if($building->images->count() > 1)
                <button type="button" class="property-carousel-btn prev" onclick="event.stopPropagation()"><i class="fa-solid fa-chevron-left"></i></button>
                <button type="button" class="property-carousel-btn next" onclick="event.stopPropagation()"><i class="fa-solid fa-chevron-right"></i></button>
                <div class="property-carousel-dots" onclick="event.stopPropagation()">
                    @foreach($building->images as $i => $image)
                        <button type="button" class="property-carousel-dot {{ $i === 0 ? 'active' : '' }}"></button>
                    @endforeach
                </div>
                @endif
            @else
                <div class="property-carousel-empty">
                    <i class="fa-solid fa-building"></i>
                    <span>No photos yet</span>
                </div>
            @endif
        </div>

        <div class="property-body">
            <div class="property-title-row">
                <span class="property-name">{{ $building->property_name }}</span>
                <span class="badge badge-gold">{{ $building->property_type ?? 'Active' }}</span>
            </div>
            <div class="property-address">
                <i class="fa-solid fa-location-dot"></i>{{ $building->full_address ?? $building->property_code }}
            </div>

            <div class="property-period-label">Current Month ({{ now()->format('F Y') }}) Overview</div>

            <div class="property-stats-row">
                <div class="property-stat-box income">
                    <div class="property-stat-label">Total Income</div>
                    <div class="property-stat-value">{{ number_format($perf['total_income'], 0) }}</div>
                </div>
                <div class="property-stat-box net">
                    <div class="property-stat-label">Net Income</div>
                    <div class="property-stat-value">{{ number_format($perf['net_income'], 0) }}</div>
                </div>
                <div class="property-stat-box occ">
                    <div class="property-stat-label">Occupancy</div>
                    <div class="property-stat-value">{{ $perf['occupancy_percent'] }}%</div>
                    <div class="property-stat-sub">{{ $perf['tenant_count'] }} Tenants</div>
                </div>
            </div>

            <div class="property-expense-title">Expense Breakdown</div>
            <div class="property-expense-list">
                @if(array_sum($perf['expenses']) <= 0)
                    <div class="property-expense-empty">No expenses recorded this month</div>
                @else
                    <div class="property-expense-row">
                        <span class="label"><i class="fa-solid fa-bolt" style="color:var(--warning);"></i>Electricity</span>
                        <span class="value">BHD {{ number_format($perf['expenses']['electricity'], 0) }}</span>
                    </div>
                    <div class="property-expense-row">
                        <span class="label"><i class="fa-solid fa-droplet" style="color:var(--info);"></i>Water</span>
                        <span class="value">BHD {{ number_format($perf['expenses']['water'], 0) }}</span>
                    </div>
                    <div class="property-expense-row">
                        <span class="label"><i class="fa-solid fa-wrench" style="color:var(--danger);"></i>Maintenance</span>
                        <span class="value">BHD {{ number_format($perf['expenses']['maintenance'], 0) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- RECENT RECORDS --}}
<div class="dash-grid">

    <div class="dash-card">
        <div class="dash-card-head">
            <div class="dash-card-title"><i class="fa-solid fa-building"></i> Recent Buildings</div>
            <a href="{{ route('buildings.index') }}" class="btn btn-outline btn-sm">View all</a>
        </div>
        @if($recentBuildings->isEmpty())
            <div class="empty-dash"><i class="fa-solid fa-building" style="font-size:24px;display:block;margin-bottom:8px;"></i> No buildings yet</div>
        @else
        <table class="dash-table">
            <thead><tr><th>Code</th><th>Name</th><th>Type</th></tr></thead>
            <tbody>
                @foreach($recentBuildings as $b)
                <tr data-href="{{ route('buildings.show', $b) }}">
                    <td><span class="dash-code">{{ $b->property_code }}</span></td>
                    <td>{{ $b->property_name }}</td>
                    <td>{{ $b->property_type ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div class="dash-card">
        <div class="dash-card-head">
            <div class="dash-card-title"><i class="fa-solid fa-door-open"></i> Recent Units</div>
            <a href="{{ route('property-units.index') }}" class="btn btn-outline btn-sm">View all</a>
        </div>
        @if($recentUnits->isEmpty())
            <div class="empty-dash"><i class="fa-solid fa-door-open" style="font-size:24px;display:block;margin-bottom:8px;"></i> No units yet</div>
        @else
        <table class="dash-table">
            <thead><tr><th>Unit</th><th>Building</th><th>Condition</th></tr></thead>
            <tbody>
                @foreach($recentUnits as $u)
                <tr data-href="{{ route('property-units.show', $u) }}">
                    <td><span class="dash-code">{{ $u->unit_name }}</span></td>
                    <td>{{ optional($u->building)->property_code ?? '—' }}</td>
                    <td>{{ $u->unit_condition ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>

@endsection
