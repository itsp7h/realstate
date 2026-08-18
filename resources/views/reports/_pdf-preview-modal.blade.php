{{-- Shared PDF preview modal for report pages. Include once per page, then
     call openReportPdf(url, title) from a "Preview" button. --}}
<div class="pdf-viewer-overlay" id="reportPdfModal" onclick="closeReportPdf(event)">
    <div class="pdf-viewer" onclick="event.stopPropagation()">
        <div class="pdf-viewer-header">
            <i class="fa-solid fa-file-pdf" style="color:var(--accent);font-size:16px"></i>
            <span id="reportPdfTitle"></span>
            <a id="reportPdfDownloadLink" href="#" class="btn btn-outline btn-sm" download>
                <i class="fa-solid fa-download"></i> Download
            </a>
            <button type="button" class="btn btn-outline btn-sm" onclick="closeReportPdfBtn()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <iframe id="reportPdfFrame" class="pdf-viewer-frame" src="about:blank"></iframe>
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
