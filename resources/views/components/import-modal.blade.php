{{--
    Import Modal Component
    Props:
      $type         — 'buildings' | 'floors' | 'units'
      $label        — display label e.g. "Buildings"
      $icon         — FA icon class e.g. "fa-building"
      $routeName    — named route for POST action e.g. 'import.buildings'
      $templateRoute — named route for template download e.g. 'import.template' with param $type
--}}
@php
    $modalId  = 'importModal_' . $type;
    $formId   = 'importForm_' . $type;
    $inputId  = 'importFile_' . $type;
    $dropId   = 'importDrop_' . $type;
    $openFn   = 'openImport_' . $type;
    $closeFn  = 'closeImport_' . $type;

    $hasImportSuccess = session('import_type') === $type && session()->has('import_count');
    $hasImportErrors  = session('import_type') === $type && session()->has('import_errors') && count(session('import_errors', [])) > 0;
@endphp

{{-- ── IMPORT RESULT BANNER ──────────────────────────────────── --}}
@if($hasImportSuccess)
<div class="import-banner {{ $hasImportErrors ? 'partial' : 'success' }}" id="importBanner_{{ $type }}">
    <div class="import-banner-icon">
        <i class="fa-solid {{ $hasImportErrors ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
    </div>
    <div class="import-banner-body">
        <div class="import-banner-title">
            {{ session('import_count') }} {{ Str::plural(strtolower($label), session('import_count')) }} imported successfully
            @if($hasImportErrors)
                — {{ count(session('import_errors')) }} row(s) skipped
            @endif
        </div>
        @if($hasImportErrors)
        <details class="import-errors-details">
            <summary>View {{ count(session('import_errors')) }} error(s)</summary>
            <ul class="import-errors-list">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </details>
        @endif
    </div>
    <button class="import-banner-close" onclick="this.closest('.import-banner').remove()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
@elseif(session('import_type') === $type && session('import_count') === 0 && $hasImportErrors)
<div class="import-banner error" id="importBanner_{{ $type }}">
    <div class="import-banner-icon"><i class="fa-solid fa-circle-xmark"></i></div>
    <div class="import-banner-body">
        <div class="import-banner-title">Import failed — no rows were saved</div>
        <details class="import-errors-details">
            <summary>View {{ count(session('import_errors')) }} error(s)</summary>
            <ul class="import-errors-list">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </details>
    </div>
    <button class="import-banner-close" onclick="this.closest('.import-banner').remove()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
@endif

{{-- ── IMPORT MODAL ─────────────────────────────────────────── --}}
<div class="modal-overlay" id="{{ $modalId }}" onclick="if(event.target===this){{ $closeFn }}()">
    <div class="modal-box import-modal-box">

        {{-- Header --}}
        <div class="modal-header">
            <div class="modal-header-top">
                <div class="modal-header-icon">
                    <i class="fa-solid {{ $icon }}"></i>
                </div>
                <div class="modal-header-text">
                    <div class="modal-header-title">Import {{ $label }}</div>
                    <div class="modal-header-sub">Upload a CSV or XLSX file to bulk-import {{ strtolower($label) }}</div>
                </div>
                <button class="modal-close-btn" type="button" onclick="{{ $closeFn }}()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="modal-body" style="padding: 20px 24px;">

            {{-- Template download --}}
            <div class="import-template-bar">
                <div class="import-template-text">
                    <i class="fa-solid fa-file-spreadsheet" style="color:var(--accent);"></i>
                    <span>Download a sample template to get started.</span>
                </div>
                <div style="display:flex;gap:6px;">
                    <a href="{{ route('import.template', [$type, 'xlsx']) }}" class="btn btn-outline btn-sm" download>
                        <i class="fa-solid fa-file-excel"></i> XLSX
                    </a>
                    <a href="{{ route('import.template', [$type, 'csv']) }}" class="btn btn-outline btn-sm" download>
                        <i class="fa-solid fa-file-csv"></i> CSV
                    </a>
                </div>
            </div>

            {{-- Column guide --}}
            <div class="import-col-guide">
                @if($type === 'buildings')
                    <span class="col-badge required-col">Property Name *</span>
                    <span class="col-badge required-col">Property Code *</span>
                    <span class="col-badge">Type of Ownership</span>
                    <span class="col-badge">Property Type</span>
                    <span class="col-badge">Land Lord</span>
                    <span class="col-badge">Building No.</span>
                    <span class="col-badge">Road</span>
                    <span class="col-badge">Block</span>
                    <span class="col-badge">Area</span>
                    <span class="col-badge">City</span>
                    <span class="col-badge">Total Blocks</span>
                    <span class="col-badge">Total Floors</span>
                    <span class="col-badge">Total Units</span>
                @elseif($type === 'floors')
                    <span class="col-badge required-col">Property Code *</span>
                    <span class="col-badge required-col">Floor Name *</span>
                    <span class="col-badge">Floor Code</span>
                    <span class="col-badge">Block Name</span>
                    <span class="col-badge">Block Code</span>
                    <span class="col-badge">Units</span>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:6px;width:100%;">
                        <i class="fa-solid fa-circle-info"></i>
                        <strong>Property Code</strong> must match an existing building.
                    </div>
                @elseif($type === 'tenants')
                    <span class="col-badge required-col">Name *</span>
                    <span class="col-badge">Tenant Type</span>
                    <span class="col-badge">ID / CR Number</span>
                    <span class="col-badge">Phone</span>
                    <span class="col-badge">Email</span>
                    <span class="col-badge">Nationality / Country</span>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:6px;width:100%;">
                        <i class="fa-solid fa-circle-info"></i>
                        <strong>Tenant Type</strong> must be <code>individual</code> or <code>company</code> (defaults to individual).
                        Rows with a duplicate name are skipped.
                    </div>
                @elseif($type === 'contracts')
                    <span class="col-badge required-col">Lease Agreement No *</span>
                    <span class="col-badge required-col">Tenant Name *</span>
                    <span class="col-badge required-col">Lease Start Date *</span>
                    <span class="col-badge required-col">Lease End Date *</span>
                    <span class="col-badge">Date</span>
                    <span class="col-badge">Prop Code</span>
                    <span class="col-badge">Floor Name / Code</span>
                    <span class="col-badge">Unit</span>
                    <span class="col-badge">Description</span>
                    <span class="col-badge">Rent per Month</span>
                    <span class="col-badge">Currency</span>
                    <span class="col-badge">Invoicing Frequency</span>
                    <span class="col-badge">Service Amount in BD (Excl. VAT)</span>
                    <span class="col-badge">Security Deposit</span>
                    <span class="col-badge">Lease Break Date</span>
                    <span class="col-badge">Notice Period</span>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:6px;width:100%;">
                        <i class="fa-solid fa-circle-info"></i>
                        <strong>Tenant Name</strong> will auto-link to an existing tenant if the name matches exactly.
                        Dates must be in <strong>YYYY-MM-DD</strong> format or standard Excel date format.
                    </div>
                @else
                    <span class="col-badge required-col">Property Code *</span>
                    <span class="col-badge required-col">Unit Name *</span>
                    <span class="col-badge">Floor Code</span>
                    <span class="col-badge">Unit Type</span>
                    <span class="col-badge">Condition</span>
                    <span class="col-badge">Area Inside</span>
                    <span class="col-badge">Rent/Month</span>
                    <span class="col-badge">+ more…</span>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:6px;width:100%;">
                        <i class="fa-solid fa-circle-info"></i>
                        <strong>Property Code</strong> must match an existing building. <strong>Floor Code</strong> is optional.
                    </div>
                @endif
            </div>

            {{-- Upload form --}}
            <form id="{{ $formId }}" method="POST" action="{{ isset($routeName) ? route($routeName) : route('data.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="import-drop-zone" id="{{ $dropId }}"
                     onclick="document.getElementById('{{ $inputId }}').click()"
                     ondragover="importDragOver(event,'{{ $dropId }}')"
                     ondragleave="importDragLeave('{{ $dropId }}')"
                     ondrop="importDrop(event,'{{ $dropId }}','{{ $inputId }}')">
                    <div class="import-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="import-drop-label">Drag & drop your CSV here</div>
                    <div class="import-drop-sub">or click to browse — max 10 MB, .csv or .xlsx</div>
                    <div class="import-file-name" id="fileName_{{ $type }}"></div>
                    <input type="file" id="{{ $inputId }}" name="file"
                           accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           style="display:none;" onchange="importFileChosen('{{ $type }}', this)">
                </div>
            </form>
        </div>

        {{-- Footer --}}
        <div class="modal-footer" style="padding:14px 24px;border-top:1px solid var(--card-border);display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" class="btn btn-outline" onclick="{{ $closeFn }}()">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="importSubmitBtn_{{ $type }}"
                    onclick="document.getElementById('{{ $formId }}').submit()" disabled>
                <i class="fa-solid fa-file-import"></i> Import
            </button>
        </div>

    </div>
</div>

@push('styles')
<style>
/* ── MODAL OVERLAY (self-contained) ─────────────────────── */
.modal-overlay {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(11,17,32,0.55); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center; padding: 20px;
    opacity: 0; pointer-events: none;
    transition: opacity 0.25s ease;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal-box {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.18), 0 8px 24px rgba(0,0,0,0.10);
    width: 100%; max-width: 680px; max-height: 90vh;
    display: flex; flex-direction: column; overflow: hidden;
    transform: translateY(20px) scale(0.98);
    transition: transform 0.3s cubic-bezier(0.22,1,0.36,1);
}
.modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
.modal-header { padding: 20px 24px 0; flex-shrink: 0; }
.modal-header-top { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
.modal-header-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: var(--accent-dim); border: 1px solid rgba(232,184,109,0.25);
    display: flex; align-items: center; justify-content: center;
    color: var(--accent); font-size: 16px; flex-shrink: 0;
}
.modal-header-text { flex: 1; }
.modal-header-title { font-family: 'Outfit',sans-serif; font-size: 17px; font-weight: 800; color: var(--text-primary); line-height: 1; }
.modal-header-sub { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
.modal-close-btn {
    width: 32px; height: 32px; border-radius: var(--radius-sm);
    border: 1.5px solid var(--card-border); background: transparent;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    color: var(--text-muted); font-size: 13px; transition: all 0.15s; flex-shrink: 0;
}
.modal-close-btn:hover { background: var(--page-bg); color: var(--text-primary); }
.modal-footer {
    padding: 16px 24px; border-top: 1px solid var(--card-border);
    display: flex; align-items: center; justify-content: flex-end; gap: 10px;
    flex-shrink: 0; background: var(--card-bg);
}

/* ── IMPORT BANNER ───────────────────────────────────────── */
.import-banner {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    border-radius: var(--radius);
    margin-bottom: 18px;
    border: 1px solid;
    animation: bannerSlide 0.3s ease both;
}
@keyframes bannerSlide {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.import-banner.success { background: #ECFDF5; border-color: #6EE7B7; }
.import-banner.partial  { background: #FFFBEB; border-color: #FCD34D; }
.import-banner.error    { background: #FEF2F2; border-color: #FCA5A5; }
.import-banner-icon     { font-size: 16px; flex-shrink: 0; padding-top: 2px; }
.import-banner.success .import-banner-icon { color: #059669; }
.import-banner.partial  .import-banner-icon { color: #D97706; }
.import-banner.error    .import-banner-icon { color: #DC2626; }
.import-banner-body     { flex: 1; }
.import-banner-title    { font-size: 13.5px; font-weight: 600; color: var(--text-primary); }
.import-errors-details  { margin-top: 8px; }
.import-errors-details summary {
    font-size: 12px; color: var(--text-muted); cursor: pointer;
    list-style: revert; padding-left: 4px;
}
.import-errors-list     { margin: 8px 0 0 16px; padding: 0; font-size: 12px; color: var(--text-secondary); line-height: 1.8; }
.import-banner-close {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); font-size: 13px; flex-shrink: 0;
    padding: 2px 4px; border-radius: 4px;
    transition: color 0.15s;
}
.import-banner-close:hover { color: var(--text-primary); }

/* ── IMPORT MODAL BOX ────────────────────────────────────── */
.import-modal-box {
    max-width: 560px !important;
}

/* ── TEMPLATE BAR ────────────────────────────────────────── */
.import-template-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-sm);
    padding: 11px 14px;
    margin-bottom: 14px;
}
.import-template-text {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--text-secondary);
}

/* ── COLUMN GUIDE ────────────────────────────────────────── */
.import-col-guide {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 18px;
}
.col-badge {
    font-size: 11px;
    font-family: 'Plus Jakarta Sans', monospace;
    padding: 3px 8px;
    border-radius: 4px;
    background: var(--page-bg);
    border: 1px solid var(--card-border);
    color: var(--text-muted);
}
.col-badge.required-col {
    background: var(--accent-dim);
    border-color: rgba(232,184,109,0.4);
    color: var(--accent);
    font-weight: 700;
}

/* ── DROP ZONE ───────────────────────────────────────────── */
.import-drop-zone {
    border: 2px dashed var(--card-border);
    border-radius: var(--radius);
    background: var(--page-bg);
    padding: 36px 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.import-drop-zone:hover,
.import-drop-zone.drag-over {
    border-color: var(--accent);
    background: var(--accent-dim);
}
.import-drop-icon {
    font-size: 36px;
    color: var(--text-muted);
    margin-bottom: 10px;
    transition: color 0.2s, transform 0.2s;
}
.import-drop-zone:hover .import-drop-icon,
.import-drop-zone.drag-over .import-drop-icon {
    color: var(--accent);
    transform: translateY(-3px);
}
.import-drop-label {
    font-family: 'Outfit', sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 5px;
}
.import-drop-sub {
    font-size: 12px;
    color: var(--text-muted);
}
.import-file-name {
    margin-top: 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--accent);
    min-height: 18px;
}
</style>
@endpush

@push('scripts')
<script>
function {{ $openFn }}() {
    document.getElementById('{{ $modalId }}').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function {{ $closeFn }}() {
    document.getElementById('{{ $modalId }}').classList.remove('open');
    document.body.style.overflow = '';
}
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
function importFileChosen(type, input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('fileName_' + type).textContent = '📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('importSubmitBtn_' + type).disabled = false;
}
</script>
@endpush
