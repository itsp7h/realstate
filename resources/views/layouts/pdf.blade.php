<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>@yield('pdf-title', 'Promoseven Holdings')</title>
{{--
    THE PDF LAYOUT — every PDF the app produces extends this.

    Portfolio export, the six list exports, the ten reports, invoices, EWA bills
    and payment receipts all render through here, so the letterhead, the page
    box, the table anatomy and the running footer are defined once. Before this
    existed, fourteen templates each carried their own print CSS and their own
    copy of the company address, and they disagreed on page size, margins, type
    scale and whether a missing value was "—" or "0.000".

    A view supplies:
      @section('pdf-title')     browser/file title
      @section('report-title')  the centred uppercase title      (required)
      @section('report-sub')    the line under it, e.g. a date range
      @section('summary')       an optional KPI strip — use x-pdf.summary
      @section('content')       the body

    Everything else — page box, letterhead, footer, page numbers — is here.
    All styling lives in public/css/pdf.css; a view that needs a one-off rule is
    a sign the shared class is missing, so add it there instead.

    The stylesheet is INLINED rather than linked: DomPDF resolves relative url()
    against the document base, and inlining keeps the font paths working without
    depending on how the PDF is generated or on DomPDF's chroot setting.
--}}
<style>
{!! str_replace("url('fonts/", "url('" . public_path('fonts') . "/", file_get_contents(public_path('css/pdf.css'))) !!}
</style>
</head>
<body>

{{-- Running footer, on every page.

     The page counter is DRAWN by App\Support\PdfPageNumbers after layout, not
     flowed here: DomPDF has no page counters, so `counter(pages)` resolves to 0
     and only the canvas knows the total once the document has been laid out.

     Which is why .pdf-foot-right is empty. It used to hold a styled pill for
     the stamp to land inside, but a flowed pill and a drawn number are two
     layers pretending to be one element, and they drifted far enough apart that
     the number printed above its own background. The cell stays as a spacer so
     the timestamp keeps its centred column. --}}
<div class="pdf-footer">
    <div class="pdf-foot-rule"></div>

    <div class="pdf-foot-row">
        <div class="pdf-foot-left">
            <div style="display:table;">
                @if ($footerLogo = \App\Models\BrandingSetting::letterheadLogoDataUri())
                    <div class="pdf-foot-logo-cell">
                        <img class="pdf-foot-logo" src="{{ $footerLogo }}" alt="">
                    </div>
                @endif
                <div class="pdf-foot-name-cell">
                    <span class="pdf-foot-company">Promoseven Holdings</span>
                    <span class="pdf-foot-report">&middot; {{ $footerName ?? html_entity_decode(trim(strip_tags($__env->yieldContent('report-title'))), ENT_QUOTES) }}</span>
                </div>
            </div>
        </div>
        <div class="pdf-foot-mid">{{ now()->format('d-M-Y H:i') }}</div>
        <div class="pdf-foot-right"></div>
    </div>
</div>

<div class="pdf-page">

    {{-- Letterhead — page 1 only, being in normal flow. "Gold seam": logo,
         a 3px gold bar, then the company block; a title plate underneath.

         Laid out with display:table rather than flex, because DomPDF supports
         no flex or grid — see the .pdf-letterhead notes in pdf.css. The seam is
         the company block's left border, which is the only way it stays as tall
         as that block: a separate element needs a height, and a stated one is
         what left the contact lines outside the seam.

         The logo bytes arrive inline as a data URI; see
         BrandingSetting::letterheadLogoDataUri() for why a path would not. --}}
    <div class="pdf-letterhead">
        @if ($letterheadLogo = \App\Models\BrandingSetting::letterheadLogoDataUri())
            <div class="pdf-logo-cell">
                <img class="pdf-logo" src="{{ $letterheadLogo }}" alt="Promoseven Holdings">
            </div>
        @endif
        {{-- The gold seam is this block's own left border, so it is exactly as
             tall as the block — see .pdf-company-cell.has-seam in pdf.css. --}}
        <div class="pdf-company-cell{{ $letterheadLogo ? ' has-seam' : '' }}">
            <div class="pdf-company">Promoseven Holdings BSC &copy;</div>
            <div class="pdf-division">REAL ESTATE DIVISION</div>
            {{-- Address on its own line, then the contact/registration line.

                 The break is explicit rather than left to the wrap. Once the
                 email joined this block the single line overflowed, and it broke
                 at the worst available point — after "TRN #", orphaning the
                 number onto the next line. Splitting it deliberately also means
                 the address never re-wraps as the email length changes, and it
                 matches the transactional email letterhead, which stacks the
                 same two lines (emails/partials/header.blade.php).

                 Labels are glued to their values with &nbsp; so no future edit
                 can strand a "#" or a country code at a line end. --}}
            <div class="pdf-org">
                Office 27, Building 1130M Road 1531, Muharraq, Kingdom of Bahrain
                <br>
                CR&nbsp;#&nbsp;21534-1 &middot; +973&nbsp;17500787
                {{-- The one editable item here, set in Settings → Branding. The
                     separator sits inside the @if so a blank setting leaves no
                     dangling middot; read via letterheadEmail() rather than
                     current(), which would create the settings row as a side
                     effect of rendering an export. --}}
                @if ($orgEmail = \App\Models\BrandingSetting::letterheadEmail())
                    &middot; {{ $orgEmail }}
                @endif
                {{-- Tax-registration and similar statutory lines a transactional
                     document must carry. Content, not decoration: dropping the
                     TRN off a receipt would break the receipt. --}}
                @hasSection('letterhead-extra')&middot; @yield('letterhead-extra')@endif
            </div>
        </div>
    </div>

    {{-- Title plate: report name left, export date right on the same baseline,
         subtitle beneath the title inside the plate. --}}
    <div class="pdf-plate">
        <div class="pdf-plate-row">
            <div class="pdf-plate-main">
                <div class="pdf-title">@yield('report-title')</div>
                @hasSection('report-sub')
                    <div class="pdf-subtitle">@yield('report-sub')</div>
                @endif
            </div>
            <div class="pdf-plate-date">Exported {{ now()->format('d-M-Y') }}</div>
        </div>
    </div>

    @yield('summary')

    @yield('content')

</div>

</body>
</html>
