@extends('layouts.admin')

@section('title', 'Import / Export')
@section('topbar-title', 'Import / Export')

@section('page-title', 'Import / Export')
@section('page-subtitle', 'Bulk import data from a spreadsheet or export all records')

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
/* This grid spaces its children with `gap`, so app-core's normal-flow stacking
   margin has to go — it would drop the second card --sp-5 below the first. */
.data-grid > .card + .card { margin-top: 0; }

/* ── PANEL CARD ────────────────────────────────────────── */

/* ── FLOW STEPS ────────────────────────────────────────── */
.flow-steps {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-bottom: var(--sp-6);
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
    font-size: var(--fs-sm); font-weight: 800;
    color: var(--text-muted);
    flex-shrink: 0; z-index: 1; position: relative;
}
.flow-num.gold { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
.flow-body { padding-top: 5px; }
.flow-title { font-size: var(--fs-base); font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
.flow-desc  { font-size: var(--fs-sm); color: var(--text-muted); line-height: 1.5; }

/* ── SECTION BANDS ─────────────────────────────────────── */
.section-bands {
    display: flex; gap: 6px; margin-bottom: var(--sp-5); flex-wrap: wrap;
}
.section-band {
    display: flex; align-items: center; gap: 6px;
    padding: 5px 10px; border-radius: var(--radius-sm);
    font-size: var(--fs-xs); font-weight: 700;
    border: 1px solid;
}
.section-band.blue   { background: var(--tone-info-bg); border-color: var(--tone-info-border); color: var(--tone-info-fg); }
.section-band.green  { background: var(--tone-success-bg); border-color: var(--tone-success-border); color: var(--tone-success-fg); }
.section-band.yellow { background: var(--tone-warning-bg); border-color: var(--tone-warning-border); color: var(--tone-warning-fg); }
.section-band i { font-size: var(--fs-2xs); }

/* ── TEMPLATE DOWNLOAD ─────────────────────────────────── */
.tpl-bar {
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    margin-bottom: var(--sp-5);
}
.tpl-bar-text { font-size: var(--fs-base); color: var(--text-secondary); display: flex; align-items: center; gap: 8px; }
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
    margin-bottom: var(--sp-4);
}
.drop-zone:hover, .drop-zone.drag-over {
    border-color: var(--accent);
    background: var(--accent-dim);
}
.drop-icon {
    font-size: 40px; color: var(--text-muted); margin-bottom: var(--sp-3);
    transition: color 0.2s, transform 0.2s;
}
.drop-zone:hover .drop-icon, .drop-zone.drag-over .drop-icon {
    color: var(--accent); transform: translateY(-4px);
}
.drop-label { font-family: 'Outfit', sans-serif; font-size: var(--fs-md); font-weight: 700; color: var(--text-primary); margin-bottom: var(--sp-1); }
.drop-sub   { font-size: var(--fs-sm); color: var(--text-muted); }
.drop-file  { margin-top: 10px; font-size: var(--fs-base); font-weight: 600; color: var(--accent); min-height: 18px; }

/* ── RESULT BANNER ─────────────────────────────────────── */
.result-banner {
    padding: 14px 18px;
    border-radius: var(--radius-sm);
    border: 1px solid;
    margin-bottom: var(--sp-5);
    animation: bannerIn 0.3s ease;
}
@keyframes bannerIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
.result-banner.success { background: var(--tone-success-bg); border-color: var(--tone-success-border); }
.result-banner.partial  { background: var(--tone-warning-bg); border-color: var(--tone-warning-border); }
.result-banner.error    { background: var(--tone-danger-bg); border-color: var(--tone-danger-border); }
.result-counts { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 6px; }
.result-count-item { font-size: var(--fs-base); font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 5px; }
.result-count-item i.ok   { color: var(--tone-success-fg); }
.result-count-item i.warn { color: var(--tone-warning-fg); }
.result-errors-toggle { font-size: var(--fs-sm); color: var(--text-muted); cursor: pointer; }
.result-errors-list { margin: 8px 0 0 0; padding: 0; font-size: var(--fs-sm); color: var(--text-secondary); line-height: 1.8; list-style: disc; padding-left: 18px; }

/* ── EXPORT CARD ───────────────────────────────────────── */
.export-info {
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: var(--sp-5);
}
.export-sheets { display: flex; flex-direction: column; gap: 8px; }
.export-sheet-row {
    display: flex; align-items: center; gap: 10px;
    font-size: var(--fs-base); color: var(--text-secondary);
}
.export-sheet-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.dot-blue   { background: var(--info); }
.dot-green  { background: var(--success); }
.dot-yellow { background: var(--warning); }
.export-sheet-label { font-weight: 600; color: var(--text-primary); min-width: 80px; }
</style>
@endpush

@section('content')


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
            <div class="result-count-item"><i class="fa-solid fa-circle-xmark" style="color:var(--tone-danger-fg);"></i> Nothing was imported</div>
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
    <i class="fa-solid fa-circle-xmark" style="color:var(--tone-danger-fg);margin-right:8px;"></i>
    {{ session('import_error') }}
</div>
@endif

<div class="data-grid">

    {{-- ── IMPORT PANEL ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon">
                <i class="fa-solid fa-file-import"></i>
            </div>
            <div>
                <div class="card-title">Import Data</div>
                <div class="card-subtitle">Upload a single CSV or XLSX file — rows are auto-routed</div>
            </div>
        </div>
        <div class="card-body">

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
    <div class="card">
        <div class="card-header">
            <div class="card-header-icon green">
                <i class="fa-solid fa-file-export"></i>
            </div>
            <div>
                <div class="card-title">Export Data</div>
                <div class="card-subtitle">Download all records as a multi-sheet XLSX file</div>
            </div>
        </div>
        <div class="card-body">

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
</script>
@endpush
