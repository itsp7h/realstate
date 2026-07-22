@extends('layouts.admin')

@section('title', 'Import / Export')
@section('topbar-title', 'Import / Export')

@push('styles')
<style>
/* ── PAGE LAYOUT ───────────────────────────────────────── */
.data-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: start;
}
@media (max-width: 900px) { .data-grid { grid-template-columns: 1fr; } }

/* ── PANEL CARD ────────────────────────────────────────── */
.data-panel {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius);
    overflow: hidden;
}
.data-panel-head {
    padding: 20px 24px 16px;
    border-bottom: 1px solid var(--card-border);
    display: flex;
    align-items: center;
    gap: 14px;
}
.data-panel-icon {
    width: 44px; height: 44px;
    border-radius: var(--radius-sm);
    background: var(--accent-dim);
    border: 1px solid rgba(232,184,109,0.25);
    display: flex; align-items: center; justify-content: center;
    color: var(--accent); font-size: 18px; flex-shrink: 0;
}
.data-panel-icon.green { background: #ECFDF5; border-color: #A7F3D0; color: #059669; }
.data-panel-title {
    font-family: 'Outfit', sans-serif;
    font-size: 16px; font-weight: 800;
    color: var(--text-primary); line-height: 1;
}
.data-panel-sub { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
.data-panel-body { padding: 22px 24px; }

/* ── FLOW STEPS ────────────────────────────────────────── */
.flow-steps {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-bottom: 24px;
}
.flow-step {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding-bottom: 18px;
    position: relative;
}
.flow-step:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 15px; top: 32px;
    width: 2px; height: calc(100% - 14px);
    background: var(--card-border);
}
.flow-num {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--page-bg);
    border: 2px solid var(--card-border);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Outfit', sans-serif;
    font-size: 12px; font-weight: 800;
    color: var(--text-muted);
    flex-shrink: 0; z-index: 1; position: relative;
}
.flow-num.gold { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
.flow-body { padding-top: 5px; }
.flow-title { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
.flow-desc  { font-size: 12px; color: var(--text-muted); line-height: 1.5; }

/* ── SECTION BANDS ─────────────────────────────────────── */
.section-bands {
    display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap;
}
.section-band {
    display: flex; align-items: center; gap: 6px;
    padding: 5px 10px; border-radius: var(--radius-sm);
    font-size: 11px; font-weight: 700;
    border: 1px solid;
}
.section-band.blue   { background: #EFF6FF; border-color: #BFDBFE; color: #1D4ED8; }
.section-band.green  { background: #ECFDF5; border-color: #A7F3D0; color: #059669; }
.section-band.yellow { background: #FFFBEB; border-color: #FDE68A; color: #D97706; }
.section-band i { font-size: 10px; }

/* ── TEMPLATE DOWNLOAD ─────────────────────────────────── */
.tpl-bar {
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    margin-bottom: 20px;
}
.tpl-bar-text { font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; }
.tpl-bar-btns { display: flex; gap: 6px; flex-shrink: 0; }

/* ── DROP ZONE ─────────────────────────────────────────── */
.drop-zone {
    border: 2px dashed var(--card-border);
    border-radius: var(--radius);
    background: var(--page-bg);
    padding: 38px 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    margin-bottom: 16px;
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--accent);
    background: var(--accent-dim);
}
.drop-icon {
    font-size: 40px; color: var(--text-muted); margin-bottom: 12px;
    transition: color 0.2s, transform 0.2s;
}
.drop-zone:hover .drop-icon, .drop-zone.drag-over .drop-icon {
    color: var(--accent); transform: translateY(-4px);
}
.drop-label { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
.drop-sub   { font-size: 12px; color: var(--text-muted); }
.drop-file  { margin-top: 10px; font-size: 13px; font-weight: 600; color: var(--accent); min-height: 18px; }

/* ── RESULT BANNER ─────────────────────────────────────── */
.result-banner {
    padding: 14px 18px;
    border-radius: var(--radius-sm);
    border: 1px solid;
    margin-bottom: 20px;
    animation: bannerIn 0.3s ease;
}
@keyframes bannerIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
.result-banner.success { background: #ECFDF5; border-color: #6EE7B7; }
.result-banner.partial  { background: #FFFBEB; border-color: #FCD34D; }
.result-banner.error    { background: #FEF2F2; border-color: #FCA5A5; }
.result-counts { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 6px; }
.result-count-item { font-size: 13px; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 5px; }
.result-count-item i.ok   { color: #059669; }
.result-count-item i.warn { color: #D97706; }
.result-errors-toggle { font-size: 12px; color: var(--text-muted); cursor: pointer; }
.result-errors-list { margin: 8px 0 0 0; padding: 0; font-size: 12px; color: var(--text-secondary); line-height: 1.8; list-style: disc; padding-left: 18px; }

/* ── EXPORT CARD ───────────────────────────────────────── */
.export-info {
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 20px;
}
.export-sheets { display: flex; flex-direction: column; gap: 8px; }
.export-sheet-row {
    display: flex; align-items: center; gap: 10px;
    font-size: 13px; color: var(--text-secondary);
}
.export-sheet-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.dot-blue   { background: #3B82F6; }
.dot-green  { background: #10B981; }
.dot-yellow { background: #F59E0B; }
.export-sheet-label { font-weight: 600; color: var(--text-primary); min-width: 80px; }

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

/* ── IMPORT DROP ZONE (modal) ───────────────────────────── */
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
    border: 1px solid; animation: bannerIn 0.3s ease both;
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
    animation: bannerIn 0.3s ease both;
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
.smart-results-grid { display: flex; flex-wrap: wrap; gap: 10px; }
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

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
        <div class="breadcrumb">
            <a href="{{ url('/dashboard') }}">Home</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Settings</span>
            <i class="fa-solid fa-chevron-right"></i>
            <span>Import / Export</span>
        </div>
        <h1 class="page-header-title">Import / Export</h1>
        <p class="page-header-sub">Bulk import data from a spreadsheet or export all records</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openSmartImport()">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Smart Import
    </button>
</div>

{{-- RESULT BANNER --}}
@if(session()->has('import_counts'))
@php
    $counts = session('import_counts');
    $errs   = session('import_errors', []);
    $total  = array_sum($counts);
    $type   = $total > 0 && count($errs) > 0 ? 'partial' : ($total > 0 ? 'success' : 'error');
@endphp
<div class="result-banner {{ $type }}">
    <div class="result-counts">
        @if($counts['buildings'] > 0)
            <div class="result-count-item"><i class="fa-solid fa-circle-check ok"></i> {{ $counts['buildings'] }} building(s) imported</div>
        @endif
        @if($counts['floors'] > 0)
            <div class="result-count-item"><i class="fa-solid fa-circle-check ok"></i> {{ $counts['floors'] }} floor(s) imported</div>
        @endif
        @if($counts['units'] > 0)
            <div class="result-count-item"><i class="fa-solid fa-circle-check ok"></i> {{ $counts['units'] }} unit(s) imported</div>
        @endif
        @if($total === 0)
            <div class="result-count-item"><i class="fa-solid fa-circle-xmark" style="color:#DC2626;"></i> Nothing was imported</div>
        @endif
        @if(count($errs) > 0)
            <div class="result-count-item"><i class="fa-solid fa-triangle-exclamation warn"></i> {{ count($errs) }} row(s) skipped</div>
        @endif
    </div>
    @if(count($errs) > 0)
    <details>
        <summary class="result-errors-toggle">View {{ count($errs) }} error(s)</summary>
        <ul class="result-errors-list">
            @foreach($errs as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </details>
    @endif
</div>
@endif

@if(session('import_error'))
<div class="result-banner error" style="margin-bottom:20px;">
    <i class="fa-solid fa-circle-xmark" style="color:#DC2626;margin-right:8px;"></i>
    {{ session('import_error') }}
</div>
@endif

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
            Smart import complete &mdash; {{ $totalImported }} record(s) saved
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

<div class="data-grid">

    {{-- ── IMPORT PANEL ─────────────────────────────────────── --}}
    <div class="data-panel">
        <div class="data-panel-head">
            <div class="data-panel-icon">
                <i class="fa-solid fa-file-import"></i>
            </div>
            <div>
                <div class="data-panel-title">Import Data</div>
                <div class="data-panel-sub">Upload a single CSV or XLSX file — rows are auto-routed</div>
            </div>
        </div>
        <div class="data-panel-body">

            {{-- How it works --}}
            <div class="flow-steps">
                <div class="flow-step">
                    <div class="flow-num gold">1</div>
                    <div class="flow-body">
                        <div class="flow-title">Download the template</div>
                        <div class="flow-desc">One file with all columns — Building, Floor, and Unit fields side by side.</div>
                    </div>
                </div>
                <div class="flow-step">
                    <div class="flow-num gold">2</div>
                    <div class="flow-body">
                        <div class="flow-title">Fill in your data</div>
                        <div class="flow-desc">Each row can hold a building, a floor, a unit, or all three. Fill only the columns you need — empty columns are skipped.</div>
                    </div>
                </div>
                <div class="flow-step">
                    <div class="flow-num gold">3</div>
                    <div class="flow-body">
                        <div class="flow-title">Upload & import</div>
                        <div class="flow-desc">The system detects what each row contains and saves it to the right table automatically.</div>
                    </div>
                </div>
            </div>

            {{-- Section bands --}}
            <div class="section-bands">
                <div class="section-band blue"><i class="fa-solid fa-building"></i> Needs: Property Name + Property Code</div>
                <div class="section-band green"><i class="fa-solid fa-layer-group"></i> Needs: Floor Name + Property Code</div>
                <div class="section-band yellow"><i class="fa-solid fa-door-open"></i> Needs: Unit Name + Property Code</div>
            </div>

            {{-- Template download --}}
            <div class="tpl-bar">
                <div class="tpl-bar-text">
                    <i class="fa-solid fa-file-spreadsheet" style="color:var(--accent);"></i>
                    Download the unified template to get started
                </div>
                <div class="tpl-bar-btns">
                    <a href="{{ route('data.template', 'xlsx') }}" class="btn btn-outline btn-sm" download>
                        <i class="fa-solid fa-file-excel"></i> XLSX
                    </a>
                    <a href="{{ route('data.template', 'csv') }}" class="btn btn-outline btn-sm" download>
                        <i class="fa-solid fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>

            {{-- Upload --}}
            <form method="POST" action="{{ route('data.import') }}" enctype="multipart/form-data" id="importForm">
                @csrf
                <div class="drop-zone" id="dropZone"
                     onclick="document.getElementById('fileInput').click()"
                     ondragover="dzOver(event)" ondragleave="dzLeave()" ondrop="dzDrop(event)">
                    <div class="drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="drop-label">Drag & drop your file here</div>
                    <div class="drop-sub">or click to browse — CSV or XLSX, max 10 MB</div>
                    <div class="drop-file" id="dropFileName"></div>
                    <input type="file" id="fileInput" name="file"
                           accept=".csv,.xlsx,.xls,text/csv"
                           style="display:none" onchange="fileChosen(this)">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;" id="importBtn" disabled
                        onclick="this.disabled=true;this.innerHTML='<i class=\'fa-solid fa-spinner fa-spin\'></i> Importing…';this.closest(\'form\').submit();">
                    <i class="fa-solid fa-file-import"></i> Import
                </button>
            </form>

        </div>
    </div>

    {{-- ── EXPORT PANEL ─────────────────────────────────────── --}}
    <div class="data-panel">
        <div class="data-panel-head">
            <div class="data-panel-icon green">
                <i class="fa-solid fa-file-export"></i>
            </div>
            <div>
                <div class="data-panel-title">Export Data</div>
                <div class="data-panel-sub">Download all records as a multi-sheet XLSX file</div>
            </div>
        </div>
        <div class="data-panel-body">

            <div class="export-info">
                <div class="export-sheets">
                    <div class="export-sheet-row">
                        <div class="export-sheet-dot dot-blue"></div>
                        <div class="export-sheet-label">Sheet 1</div>
                        <div>Buildings — all property records</div>
                    </div>
                    <div class="export-sheet-row">
                        <div class="export-sheet-dot dot-green"></div>
                        <div class="export-sheet-label">Sheet 2</div>
                        <div>Floors — all floor records with building reference</div>
                    </div>
                    <div class="export-sheet-row">
                        <div class="export-sheet-dot dot-yellow"></div>
                        <div class="export-sheet-label">Sheet 3</div>
                        <div>Units — all property unit records</div>
                    </div>
                </div>
            </div>

            <div class="flow-steps">
                <div class="flow-step">
                    <div class="flow-num" style="background:var(--accent-dim);border-color:var(--accent);color:var(--accent);">
                        <i class="fa-solid fa-database" style="font-size:11px;"></i>
                    </div>
                    <div class="flow-body">
                        <div class="flow-title">All records exported</div>
                        <div class="flow-desc">Every building, floor, and unit in the system is included with all fields.</div>
                    </div>
                </div>
                <div class="flow-step">
                    <div class="flow-num" style="background:var(--accent-dim);border-color:var(--accent);color:var(--accent);">
                        <i class="fa-solid fa-file-excel" style="font-size:11px;"></i>
                    </div>
                    <div class="flow-body">
                        <div class="flow-title">Import-ready format</div>
                        <div class="flow-desc">The exported file uses the same column headers as the import template — you can re-import it directly after editing.</div>
                    </div>
                </div>
            </div>

            <a href="{{ route('data.export') }}" class="btn btn-success" style="width:100%;justify-content:center;margin-top:8px;">
                <i class="fa-solid fa-file-excel"></i> Export All Data
            </a>

        </div>
    </div>

</div>

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

@endsection

@push('scripts')
<script>
function dzOver(e)  { e.preventDefault(); document.getElementById('dropZone').classList.add('drag-over'); }
function dzLeave()  { document.getElementById('dropZone').classList.remove('drag-over'); }
function dzDrop(e)  {
    e.preventDefault();
    dzLeave();
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    const inp = document.getElementById('fileInput');
    inp.files = dt.files;
    fileChosen(inp);
}
function fileChosen(inp) {
    const file = inp.files[0];
    if (!file) return;
    document.getElementById('dropFileName').textContent = '📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('importBtn').disabled = false;
}

/* ── SMART IMPORT MODAL ────────────────────────────────────── */
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
