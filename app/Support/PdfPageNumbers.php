<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF;
use Dompdf\Canvas;
use Dompdf\FontMetrics;

/**
 * Stamps "PAGE 1 / 5" into the running footer's right-hand slot.
 *
 * WHY THIS IS DRAWN RATHER THAN FLOWED
 * DomPDF has no page counters. `counter(pages)` is not a built-in — the
 * generated-content reflower treats it as an ordinary named counter and
 * resolves it to 0 (FrameReflower\AbstractFrameReflower::resolve_counters),
 * and the `{PAGE_NUM}` substitution that would have handled flowed text is
 * commented out in Renderer\Text. So the number cannot exist as content; the
 * only layer that knows the page total is the canvas, after layout.
 *
 * WHY THERE IS NO LONGER A PILL
 * The footer used to carry a real, empty `<span class="pdf-foot-pill">` and
 * this class stamped the digits "into" it from hand-tuned coordinates. That is
 * two independent layers pretending to be one element, and they drifted: the
 * stamp sat 5.8pt above the pill and 10.8pt left of its centre, so the number
 * printed above its own background. Since the counter can't be flowed, the pill
 * can't be flowed with it — a misaligned pill is worse than no pill, so the
 * pill is gone and the counter is a plain uppercase micro-label instead,
 * matching the footer's other small caps.
 *
 * WHY IT IS DERIVED, NOT NUDGED
 * Every previous fix re-tuned a magic offset (and one of them conflated px with
 * pt). Both axes are now computed: the baseline comes from the canvas's own
 * font metrics, and the right edge from the measured width of the actual string
 * on the actual page — so "PAGE 9 / 9" and "PAGE 10 / 12" both end flush on the
 * margin instead of only the widest case fitting.
 */
class PdfPageNumbers
{
    /** A4 portrait in points. Every export is portrait — no exceptions. */
    private const A4_WIDTH  = 595.28;
    private const A4_HEIGHT = 841.89;

    /** The @page side margin in pdf.css (0.45in), which is where the footer's
     *  content box ends. The old value said 0.6in and put the stamp 10.8pt
     *  adrift; @page is the authority, so keep the two in step. */
    private const MARGIN_SIDE = 32.4;

    /** Distance from the paper's bottom edge to the BASELINE of the footer
     *  row's own text — the company name and the timestamp. Measured off a
     *  rendered export, so the stamp sits on the same line as its neighbours
     *  rather than floating above them. It follows from .pdf-footer's
     *  `bottom: -0.46in`, .pdf-foot-rule's 2px and .pdf-foot-row's 10px
     *  padding; if any of those three change, re-measure this. */
    private const BASELINE_UP = 23.9;

    /** 6.5pt/700, the size and weight .pdf-foot-mid and .pdf-foot-company use.
     *  The label is uppercase, so it takes the 0.06em tracking that pdf.css
     *  gives its other small caps. */
    private const SIZE     = 6.5;
    private const TRACKING = 0.06;   // em

    /** --text-primary #1E2C4F, as the footer's other emphasised text. */
    private const INK = [0.118, 0.173, 0.310];

    public static function stamp(PDF $pdf): PDF
    {
        $dompdf = $pdf->getDomPDF();

        // Every PDF in the app is streamed through this method, and the render
        // below is the moment the memory is needed — so this is the one place
        // the ceiling can be raised without repeating it at ten call sites.
        // See PdfBudget for the measurements: the two year-to-date report PDFs
        // need 379 MB and 547 MB, against php-fpm's 128 MB.
        PdfBudget::reserve();

        // Render FIRST. The page script is not queued for output in DomPDF 3 —
        // the CPDF adapter runs the callback immediately, looping the pages
        // that exist at the moment of the call
        // (Adapter\CPDF::processPageScript). Stamping before the render
        // therefore wrote onto the only page there was, as "PAGE 1 / 1", and
        // every page after it carried no number at all. render() sets
        // barryvdh's rendered flag, so the stream()/output() that follows
        // reuses this render rather than laying the document out a second time.
        //
        // The buffer guard is why this used to be done before the render:
        // DomPDF opens an output buffer of its own when a writable
        // log_output_file is configured, and an exception mid-render would
        // leave it open — which PHPUnit reports as a risky test. Restoring the
        // level here is cheaper than giving up the page count.
        $level = ob_get_level();

        try {
            $pdf->render();
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }

        // After the render, not before: DomPDF replaces the canvas during
        // render() when the document's own @page size differs from the one the
        // instance was built with, and a stamp on the discarded canvas would
        // vanish without a trace.
        $dompdf->getCanvas()->page_script(
            function (int $page, int $total, Canvas $canvas, FontMetrics $metrics): void {
                // Figtree is registered by pdf.css's @font-face and resolves
                // once the stylesheet has been parsed; DejaVu is DomPDF's only
                // built-in, and is the fallback the CSS names too.
                $font = $metrics->getFont('Figtree', 'bold')
                    ?: $metrics->getFont('DejaVu Sans', 'bold');

                $text     = 'PAGE ' . $page . ' / ' . $total;
                $tracking = self::SIZE * self::TRACKING;

                // Tracking is applied after every glyph, the last one included,
                // so the measured run ends with one space of air that is not
                // ink. Discounting it puts the final glyph — not that trailing
                // gap — flush on the margin.
                $width = $metrics->getTextWidth($text, $font, self::SIZE, 0.0, $tracking) - $tracking;

                // Canvas::text() takes the glyph box's top, not the baseline,
                // and subtracts the font's own height to find the baseline
                // (Adapter\CPDF::text). Asking the canvas for that height is
                // what makes the result size-independent: change SIZE and the
                // text stays on the footer's line.
                $canvas->text(
                    self::A4_WIDTH - self::MARGIN_SIDE - $width,
                    self::A4_HEIGHT - self::BASELINE_UP - $canvas->get_font_baseline($font, self::SIZE),
                    $text,
                    $font,
                    self::SIZE,
                    self::INK,
                    0.0,
                    $tracking,
                );
            }
        );

        return $pdf;
    }
}
