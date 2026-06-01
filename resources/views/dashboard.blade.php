@extends('layouts.admin')

@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@push('styles')
<style>
/* ── STATS ─────────────────────────────────────────────── */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.dash-stat {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 18px 20px;
    display: flex; align-items: center; gap: 14px;
    transition: box-shadow 0.2s, transform 0.2s;
}
.dash-stat:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
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

/* ── HERO IMPORT/EXPORT ─────────────────────────────────── */
.data-hero {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 28px 32px;
    display: flex;
    align-items: center;
    gap: 28px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.data-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--accent-dim) 0%, transparent 60%);
    pointer-events: none;
}
.data-hero-icon {
    width: 64px; height: 64px; border-radius: 16px;
    background: var(--accent); border: 1px solid rgba(232,184,109,0.4);
    display: flex; align-items: center; justify-content: center;
    color: #0B1120; font-size: 26px; flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(232,184,109,0.3);
    position: relative; z-index: 1;
}
.data-hero-text { flex: 1; position: relative; z-index: 1; }
.data-hero-title {
    font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800;
    color: var(--text-primary); margin-bottom: 5px;
}
.data-hero-sub { font-size: 13px; color: var(--text-secondary); line-height: 1.5; }
.data-hero-actions { display: flex; gap: 10px; flex-shrink: 0; position: relative; z-index: 1; }

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

/* ── MODAL BASE ─────────────────────────────────────────── */
.modal-overlay {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(11,17,32,0.55);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
    opacity: 0; pointer-events: none;
    transition: opacity 0.25s ease;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal-box {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.18), 0 8px 24px rgba(0,0,0,0.10);
    width: 100%; max-width: 680px; max-height: 90vh;
    display: flex; flex-direction: column;
    transform: translateY(20px) scale(0.98);
    transition: transform 0.3s cubic-bezier(0.22,1,0.36,1);
    overflow: hidden;
}
.modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
.modal-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid var(--card-border);
    flex-shrink: 0;
}
.modal-header-top { display: flex; align-items: flex-start; gap: 14px; }
.modal-header-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.modal-header-text { flex: 1; }
.modal-header-title { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 800; color: var(--text-primary); }
.modal-header-sub { font-size: 13px; color: var(--text-muted); margin-top: 3px; }
.modal-close-btn {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 16px;
    padding: 4px 6px; border-radius: 6px; transition: color 0.15s;
}
.modal-close-btn:hover { color: var(--text-primary); }
.modal-body { overflow-y: auto; flex: 1; }
.modal-footer { flex-shrink: 0; }

/* ── IMPORT DROP ZONE (shared) ──────────────────────────── */
.import-drop-zone {
    border: 2px dashed var(--card-border); border-radius: var(--radius);
    background: var(--page-bg); padding: 36px 24px;
    text-align: center; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.import-drop-zone:hover, .import-drop-zone.drag-over {
    border-color: var(--accent); background: var(--accent-dim);
}
.import-drop-icon { font-size: 36px; color: var(--text-muted); margin-bottom: 10px; transition: color 0.2s, transform 0.2s; }
.import-drop-zone:hover .import-drop-icon,
.import-drop-zone.drag-over .import-drop-icon { color: var(--accent); transform: translateY(-3px); }
.import-drop-label { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 5px; }
.import-drop-sub { font-size: 12px; color: var(--text-muted); }
.import-file-name { margin-top: 12px; font-size: 13px; font-weight: 600; color: var(--accent); min-height: 18px; }

/* ── IMPORT BANNER (error) ──────────────────────────────── */
.import-banner {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 14px 18px; border-radius: var(--radius);
    border: 1px solid; animation: bannerSlide 0.3s ease both;
}
.import-banner.error { background: #FEF2F2; border-color: #FCA5A5; }
.import-banner-icon { font-size: 16px; flex-shrink: 0; padding-top: 2px; }
.import-banner.error .import-banner-icon { color: #DC2626; }
.import-banner-body { flex: 1; }
.import-banner-title { font-size: 13.5px; font-weight: 600; color: var(--text-primary); }
.import-banner-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 13px; flex-shrink: 0;
    padding: 2px 4px; border-radius: 4px; transition: color 0.15s;
}
.import-banner-close:hover { color: var(--text-primary); }

/* ── SMART IMPORT RESULTS ───────────────────────────────── */
.smart-results-wrap {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    padding: 18px 22px;
    margin-bottom: 24px;
    animation: bannerSlide 0.3s ease both;
}
@keyframes bannerSlide {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.smart-results-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
}
.smart-results-heading {
    font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700;
    color: var(--text-primary); display: flex; align-items: center; gap: 8px;
}
.smart-results-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 14px; padding: 2px 6px;
    border-radius: 4px; transition: color 0.15s;
}
.smart-results-close:hover { color: var(--text-primary); }
.smart-results-grid {
    display: flex; flex-wrap: wrap; gap: 10px;
}
.smart-result-card {
    display: flex; align-items: flex-start; gap: 12px;
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    min-width: 160px; flex: 1;
}
.smart-result-card.has-errors { border-color: rgba(234,179,8,0.4); background: #FFFBEB; }
.smart-result-entity-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.smart-result-entity { font-size: 12px; font-weight: 700; color: var(--text-primary); text-transform: capitalize; }
.smart-result-count  { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
.smart-result-errors { margin-top: 6px; }
.smart-result-errors summary { font-size: 11px; color: #D97706; cursor: pointer; list-style: revert; }
.smart-result-errors ul { margin: 6px 0 0 14px; padding: 0; font-size: 11px; color: var(--text-secondary); line-height: 1.8; }

/* ── SMART IMPORT MODAL ─────────────────────────────────── */
.smart-import-box { max-width: 560px !important; }
.smart-detect-info {
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 16px;
}
.smart-detect-label {
    font-size: 12px; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.05em;
    margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
}
.smart-detect-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.smart-detect-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 600;
    padding: 4px 10px; border-radius: 20px;
    background: var(--accent-dim); color: var(--accent);
    border: 1px solid rgba(232,184,109,0.35);
}
.smart-detect-note {
    font-size: 12px; color: var(--text-muted); margin: 0; line-height: 1.6;
}
</style>
@endpush

@push('scripts')
<script>
function importDragOver(e, dropId) {
    e.preventDefault();
    document.getElementById(dropId).classList.add('drag-over');
}
function importDragLeave(dropId) {
    document.getElementById(dropId).classList.remove('drag-over');
}
function importDrop(e, dropId, inputId) {
    e.preventDefault();
    document.getElementById(dropId).classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    const input = document.getElementById(inputId);
    input.files = dt.files;
    input.dispatchEvent(new Event('change'));
}
function openSmartImport() {
    document.getElementById('smartImportModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeSmartImport() {
    document.getElementById('smartImportModal').classList.remove('open');
    document.body.style.overflow = '';
}
function smartImportFileChosen(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('smartImportFileName').textContent = '📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('smartImportDropLabel').textContent = 'File selected — ready to import';
    document.getElementById('smartImportSubmit').disabled = false;
}
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
    <div class="dash-stat">
        <div class="dash-stat-icon gold"><i class="fa-solid fa-building"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['buildings'] }}</div>
            <div class="dash-stat-lbl">Buildings</div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-icon blue"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['floors'] }}</div>
            <div class="dash-stat-lbl">Floors</div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-icon green"><i class="fa-solid fa-door-open"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['units'] }}</div>
            <div class="dash-stat-lbl">Total Units</div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-icon purple"><i class="fa-solid fa-couch"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['furnished'] }}</div>
            <div class="dash-stat-lbl">Furnished</div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="dash-stat-icon rose"><i class="fa-solid fa-hammer"></i></div>
        <div>
            <div class="dash-stat-val">{{ $stats['fitted'] }}</div>
            <div class="dash-stat-lbl">Fitted</div>
        </div>
    </div>
</div>

{{-- IMPORT / EXPORT HERO --}}
<div class="data-hero">
    <div class="data-hero-icon">
        <i class="fa-solid fa-arrows-rotate"></i>
    </div>
    <div class="data-hero-text">
        <div class="data-hero-title">Import &amp; Export Data</div>
        <div class="data-hero-sub">
            Bulk import buildings, floors, and units from a single spreadsheet — or export all records to Excel.
        </div>
    </div>
    <div class="data-hero-actions">
        <a href="{{ route('data.export') }}" class="btn btn-success">
            <i class="fa-solid fa-file-excel"></i> Export All
        </a>
        <button type="button" class="btn btn-primary" onclick="openSmartImport()">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Smart Import
        </button>
    </div>
</div>

{{-- SMART IMPORT RESULTS --}}
@if(session('smart_import_results'))
<div class="smart-results-wrap" id="smartResultsWrap">
    <div class="smart-results-top">
        <div class="smart-results-heading">
            @php
                $totalImported = collect(session('smart_import_results'))->sum('imported');
                $totalErrors   = collect(session('smart_import_results'))->sum(fn($r) => count($r['errors']));
            @endphp
            <i class="fa-solid {{ $totalErrors > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' }}" style="color:{{ $totalErrors > 0 ? 'var(--accent)' : '#10B981' }}"></i>
            Import complete &mdash; {{ $totalImported }} record(s) saved
            @if($totalErrors > 0), {{ $totalErrors }} skipped @endif
        </div>
        <button class="smart-results-close" onclick="document.getElementById('smartResultsWrap').remove()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="smart-results-grid">
        @foreach(session('smart_import_results') as $entity => $result)
        <div class="smart-result-card {{ count($result['errors']) > 0 ? 'has-errors' : '' }}">
            <div class="smart-result-entity-icon">
                @php
                    $icons = ['tenants'=>'fa-user','contracts'=>'fa-file-contract','buildings'=>'fa-building','floors'=>'fa-layer-group','units'=>'fa-door-open'];
                @endphp
                <i class="fa-solid {{ $icons[$entity] ?? 'fa-database' }}"></i>
            </div>
            <div class="smart-result-body">
                <div class="smart-result-entity">{{ ucfirst($entity) }}</div>
                <div class="smart-result-count">{{ $result['imported'] }} imported</div>
                @if(count($result['errors']) > 0)
                <details class="smart-result-errors">
                    <summary>{{ count($result['errors']) }} skipped</summary>
                    <ul>
                        @foreach($result['errors'] as $err)
                        <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </details>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@elseif(session('smart_import_error'))
<div class="import-banner error" style="margin-bottom:20px;">
    <div class="import-banner-icon"><i class="fa-solid fa-circle-xmark"></i></div>
    <div class="import-banner-body">
        <div class="import-banner-title">{{ session('smart_import_error') }}</div>
    </div>
    <button class="import-banner-close" onclick="this.closest('.import-banner').remove()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
@endif

{{-- SMART IMPORT MODAL --}}
<div class="modal-overlay" id="smartImportModal" onclick="if(event.target===this)closeSmartImport()">
    <div class="modal-box smart-import-box">

        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div class="modal-header-text">
                    <div class="modal-header-title">Smart Import</div>
                    <div class="modal-header-sub">Upload any file — auto-detected &amp; routed to the right tables</div>
                </div>
                <button class="modal-close-btn" type="button" onclick="closeSmartImport()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="modal-body" style="padding:20px 24px;">

            {{-- Detection info --}}
            <div class="smart-detect-info">
                <div class="smart-detect-label"><i class="fa-solid fa-microchip"></i> Auto-detects any of these types</div>
                <div class="smart-detect-badges">
                    <span class="smart-detect-badge"><i class="fa-solid fa-building"></i> Buildings</span>
                    <span class="smart-detect-badge"><i class="fa-solid fa-layer-group"></i> Floors</span>
                    <span class="smart-detect-badge"><i class="fa-solid fa-door-open"></i> Units</span>
                    <span class="smart-detect-badge"><i class="fa-solid fa-user"></i> Tenants</span>
                    <span class="smart-detect-badge"><i class="fa-solid fa-file-contract"></i> Contracts</span>
                </div>
                <p class="smart-detect-note">
                    A lease contracts file automatically imports both <strong>Tenants</strong> and <strong>Contracts</strong> in one pass.
                    Duplicate records are skipped, not overwritten.
                </p>
            </div>

            {{-- Upload form --}}
            <form id="smartImportForm" method="POST" action="{{ route('import.smart') }}" enctype="multipart/form-data">
                @csrf
                <div class="import-drop-zone" id="smartImportDrop"
                     onclick="document.getElementById('smartImportFile').click()"
                     ondragover="importDragOver(event,'smartImportDrop')"
                     ondragleave="importDragLeave('smartImportDrop')"
                     ondrop="importDrop(event,'smartImportDrop','smartImportFile')">
                    <div class="import-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="import-drop-label" id="smartImportDropLabel">Drag &amp; drop your file here</div>
                    <div class="import-drop-sub">CSV or XLSX &mdash; max 10 MB</div>
                    <div class="import-file-name" id="smartImportFileName"></div>
                    <input type="file" id="smartImportFile" name="file"
                           accept=".csv,.xlsx,.xls,text/csv"
                           style="display:none;"
                           onchange="smartImportFileChosen(this)">
                </div>
            </form>

        </div>

        <div class="modal-footer" style="padding:14px 24px;border-top:1px solid var(--card-border);display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" class="btn btn-outline" onclick="closeSmartImport()">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="smartImportSubmit"
                    onclick="document.getElementById('smartImportForm').submit()" disabled>
                <i class="fa-solid fa-wand-magic-sparkles"></i> Import
            </button>
        </div>

    </div>
</div>

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
                <tr>
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
                <tr>
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
