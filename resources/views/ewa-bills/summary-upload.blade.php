@extends('layouts.admin')

@section('title', 'EWA Summary')
@section('topbar-title', 'EWA Bills')

@push('styles')
<style>
.ewa-header-strip {
    background: linear-gradient(135deg, #0D9488 0%, #0369A1 100%);
    border-radius: var(--radius); padding: 16px 22px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 16px; color: #fff;
}
.ewa-header-strip .ewa-logo-circle {
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
}
.ewa-header-strip h2 { font-family: 'Outfit',sans-serif; font-size: 18px; font-weight: 800; margin: 0; }
.ewa-header-strip p  { font-size: 12px; opacity: 0.85; margin: 2px 0 0; }

.summary-card {
    background: var(--card-bg); border: 1px solid var(--card-border);
    border-radius: var(--radius); padding: 28px 32px; margin-bottom: 20px;
}

.batch-drop-zone {
    border: 2px dashed var(--card-border); border-radius: var(--radius);
    background: var(--page-bg); padding: 44px 24px; text-align: center;
    cursor: pointer; transition: border-color 0.2s, background 0.2s;
}
.batch-drop-zone:hover, .batch-drop-zone.drag-over {
    border-color: #0D9488; background: rgba(13,148,136,0.06);
}
.batch-drop-icon {
    font-size: 38px; color: var(--text-muted); margin-bottom: 10px;
    transition: color 0.2s, transform 0.2s;
}
.batch-drop-zone:hover .batch-drop-icon, .batch-drop-zone.drag-over .batch-drop-icon {
    color: #0D9488; transform: translateY(-3px);
}
.batch-drop-label { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 5px; }
.batch-drop-sub { font-size: 12px; color: var(--text-muted); }

/* File list — styled like a stack of utility-bill stubs */
.stub-list { margin-top: 20px; display: flex; flex-direction: column; gap: 8px; }
.stub-row {
    display: flex; align-items: center; gap: 12px;
    background: var(--page-bg); border: 1px solid var(--card-border);
    border-left: 3px solid #0D9488;
    border-radius: var(--radius-sm); padding: 10px 14px;
    animation: stubIn 0.2s ease both;
}
@keyframes stubIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
.stub-icon { color: #0D9488; font-size: 15px; flex-shrink: 0; }
.stub-name { font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 600; color: var(--text-primary); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.stub-size { font-size: 11px; color: var(--text-muted); font-variant-numeric: tabular-nums; flex-shrink: 0; }
.stub-remove {
    background: none; border: none; cursor: pointer; color: var(--text-muted);
    font-size: 12px; flex-shrink: 0; padding: 4px 6px; border-radius: 4px; transition: color 0.15s;
}
.stub-remove:hover { color: #DC2626; }

.batch-count-bar {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--card-border);
}
.batch-count-label { font-size: 13px; color: var(--text-muted); }
.batch-count-label strong { color: var(--text-primary); font-family: 'Outfit', sans-serif; }

/* ── TABS ──────────────────────────────────────────────────── */
.tab-bar { display: flex; gap: 4px; border-bottom: 2px solid var(--card-border); margin-bottom: 20px; }
.tab-btn {
    padding: 11px 22px; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; font-weight: 600;
    color: var(--text-muted); border: none; background: none; cursor: pointer;
    border-bottom: 2px solid transparent; margin-bottom: -2px; transition: color 0.18s, border-color 0.18s;
    display: flex; align-items: center; gap: 8px; text-decoration: none;
}
.tab-btn:hover { color: var(--text-primary); }
.tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-header-title">EWA Summary</h1>
        <p class="page-header-sub">Upload government EWA bill PDFs in bulk — each one is parsed, saved, and summarized into an Excel sheet</p>
    </div>
</div>

@include('ewa-bills._tabs')

@if($errors->any())
<div class="alert alert-danger">
    <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
</div>
@endif

<div class="ewa-header-strip">
    <div class="ewa-logo-circle"><i class="fa-solid fa-file-invoice"></i></div>
    <div>
        <h2>Bulk Bill Extraction</h2>
        <p>Drop in as many EWA bill PDFs as you have for this period — we'll read the readings and charges automatically</p>
    </div>
</div>

<div class="summary-card">
    <form id="batchForm" method="POST" action="{{ route('ewa-bills.summary.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="batch-drop-zone" id="batchDropZone" onclick="document.getElementById('batchFileInput').click()">
            <div class="batch-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <div class="batch-drop-label">Drag &amp; drop EWA bill PDFs here</div>
            <div class="batch-drop-sub">or click to browse — PDF only, max 10 MB per file</div>
            <input type="file" id="batchFileInput" name="files[]" accept="application/pdf,.pdf" multiple style="display:none;">
        </div>

        <div class="stub-list" id="stubList"></div>

        <div class="batch-count-bar" id="batchCountBar" style="display:none;">
            <div class="batch-count-label"><strong id="batchCount">0</strong> file(s) ready to process</div>
            <button type="submit" class="btn btn-primary" id="batchSubmitBtn" disabled>
                <i class="fa-solid fa-gears"></i> Process Bills
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const dropZone   = document.getElementById('batchDropZone');
    const fileInput  = document.getElementById('batchFileInput');
    const stubList   = document.getElementById('stubList');
    const countBar   = document.getElementById('batchCountBar');
    const countLabel = document.getElementById('batchCount');
    const submitBtn  = document.getElementById('batchSubmitBtn');

    let files = [];

    function render() {
        stubList.innerHTML = '';
        files.forEach((file, i) => {
            const row = document.createElement('div');
            row.className = 'stub-row';
            row.innerHTML = `
                <i class="fa-solid fa-file-pdf stub-icon"></i>
                <span class="stub-name">${file.name}</span>
                <span class="stub-size">${(file.size / 1024).toFixed(1)} KB</span>
                <button type="button" class="stub-remove" data-idx="${i}" title="Remove"><i class="fa-solid fa-xmark"></i></button>
            `;
            stubList.appendChild(row);
        });

        countBar.style.display = files.length ? 'flex' : 'none';
        countLabel.textContent = files.length;
        submitBtn.disabled = files.length === 0;

        stubList.querySelectorAll('.stub-remove').forEach(btn => {
            btn.addEventListener('click', () => {
                files.splice(Number(btn.dataset.idx), 1);
                syncInput();
                render();
            });
        });
    }

    function syncInput() {
        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
    }

    function addFiles(fileListLike) {
        Array.from(fileListLike).forEach(f => {
            if (f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf')) {
                files.push(f);
            }
        });
        syncInput();
        render();
    }

    fileInput.addEventListener('change', () => addFiles(fileInput.files));

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        addFiles(e.dataTransfer.files);
    });
})();
</script>
@endpush
