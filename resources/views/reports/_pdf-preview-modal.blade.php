{{-- Shared PDF preview modal for report pages. Include once per page, then
     call openReportPdf(url, title) from a "Preview" button. --}}
@push('styles')
<style>
.pdf-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 1050;
    background: rgba(0,0,0,0.85); align-items: center; justify-content: center;
}
.pdf-modal-overlay.open { display: flex; }
.pdf-modal-box {
    width: 90vw; height: 90vh; background: #1E2433; border-radius: var(--radius);
    display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.5);
}
.pdf-modal-header {
    padding: 12px 18px; background: #151929; border-bottom: 1px solid #2D3650;
    display: flex; align-items: center; gap: 12px;
}
.pdf-modal-header span { flex: 1; font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: #E2E8F0; }
.pdf-modal-iframe { flex: 1; border: none; width: 100%; background: #fff; }
</style>
@endpush

<div class="pdf-modal-overlay" id="reportPdfModal" onclick="closeReportPdf(event)">
    <div class="pdf-modal-box" onclick="event.stopPropagation()">
        <div class="pdf-modal-header">
            <i class="fa-solid fa-file-pdf" style="color:var(--accent);font-size:16px"></i>
            <span id="reportPdfTitle"></span>
            <a id="reportPdfDownloadLink" href="#" class="btn btn-outline btn-sm" download>
                <i class="fa-solid fa-download"></i> Download
            </a>
            <button type="button" class="btn btn-outline btn-sm" onclick="closeReportPdfBtn()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <iframe id="reportPdfFrame" class="pdf-modal-iframe" src="about:blank"></iframe>
    </div>
</div>

@push('scripts')
<script>
window.openReportPdf = function (url, title) {
    document.getElementById('reportPdfTitle').textContent = title || 'Preview';
    document.getElementById('reportPdfFrame').src = url;
    document.getElementById('reportPdfDownloadLink').href = url;
    document.getElementById('reportPdfModal').classList.add('open');
};
window.closeReportPdf = function (e) {
    if (e.target === document.getElementById('reportPdfModal')) closeReportPdfBtn();
};
window.closeReportPdfBtn = function () {
    document.getElementById('reportPdfModal').classList.remove('open');
    document.getElementById('reportPdfFrame').src = 'about:blank';
};
document.addEventListener('keydown', function (e) {
    var modal = document.getElementById('reportPdfModal');
    if (e.key === 'Escape' && modal && modal.classList.contains('open')) {
        closeReportPdfBtn();
    }
});
</script>
@endpush
