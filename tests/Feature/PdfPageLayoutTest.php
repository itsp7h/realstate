<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BrandingSetting;
use App\Models\PropertyUnit;
use App\Models\User;
use App\Support\PdfPageNumbers;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Blade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shared PDF layer.
 *
 * Every PDF the app produces extends layouts/pdf.blade.php and is styled only
 * by public/css/pdf.css. Before that, fourteen templates each carried their own
 * print CSS and their own copy of the company address, disagreeing on page
 * size, margins, type scale and whether a missing value read "—" or "0.000".
 *
 * These guard the traps that only surface when you rasterise the output.
 */
class PdfPageLayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * How far down the page to look for the gold seam. Anything short of the
     * running footer will do — its rule is the same gold, and counting it would
     * make a too-short seam look full height.
     */
    private const GOLD_SCAN_BAND = 300.0;

    /** The report title renderLetterhead() uses, marking where the plate starts. */
    private const PLATE_SENTINEL = 'PLATESTART';

    /** Every PDF view in the app. Add one here when you add one. */
    private const VIEWS = [
        'data/export-pdf',
        'exports/listing-pdf',
        'invoices/pdf',
        'ewa-bills/pdf',
        'payments/receipt',
        'reports/bill-wise-statement-pdf',
        'reports/collection-pdf',
        'reports/financial-summary-pdf',
        'reports/group-ageing-pdf',
        'reports/profit-loss-pdf',
        'reports/rent-schedule-pdf',
        'reports/tenant-ageing-pdf',
        'reports/tenant-ledger-pdf',
        'reports/tenant-statement-pdf',
        'reports/vat-return-pdf',
    ];

    private function css(): string
    {
        return file_get_contents(public_path('css/pdf.css'));
    }

    private function source(string $name): string
    {
        return file_get_contents(resource_path("views/{$name}.blade.php"));
    }

    public function test_every_pdf_view_extends_the_shared_layout(): void
    {
        foreach (self::VIEWS as $view) {
            $this->assertStringContainsString(
                "@extends('layouts.pdf')",
                $this->source($view),
                "{$view} does not extend layouts.pdf."
            );
        }
    }

    /** One stylesheet. A view with its own <style> is drift by definition. */
    public function test_no_pdf_view_carries_its_own_print_css(): void
    {
        foreach (self::VIEWS as $view) {
            $source = $this->source($view);

            $this->assertStringNotContainsString('<style>', $source, "{$view} still has a <style> block.");
            $this->assertStringNotContainsString('<!DOCTYPE', $source, "{$view} still declares a document.");
            $this->assertStringNotContainsString('@page', $source, "{$view} still declares a page box.");
        }
    }

    /** The company address belongs to the layout, not to fifteen copies. */
    public function test_no_pdf_view_repeats_the_letterhead(): void
    {
        foreach (self::VIEWS as $view) {
            $this->assertStringNotContainsString(
                'Office 27, Building 1130M',
                $this->source($view),
                "{$view} carries its own copy of the company address."
            );
        }
    }

    /**
     * The universal selector matches the page box in DomPDF, so `* { margin: 0 }`
     * silently zeroes the @page margins — which printed documents flush to the
     * paper edge with the last column clipped. It has been reintroduced twice.
     */
    public function test_the_stylesheet_does_not_reset_margins_with_the_universal_selector(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/^\s*\*[\s,{][^}]*margin\s*:/m',
            $this->css(),
            'A `*` rule sets margin, which zeroes the @page margins in DomPDF.'
        );
    }

    public function test_the_page_box_is_a4_portrait_with_a_printable_inset(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression('/@page\s*\{[^}]*size:\s*A4\s+portrait/', $css);

        // The inset lives on @page, NOT on a wrapper's padding. DomPDF applies a
        // block's vertical padding once — at the start and end of the block, not
        // on every page it spans — so `@page { margin: 0 }` plus wrapper padding
        // insets page 1's top and the last page's bottom and lets every page
        // between run edge to edge, printing over the running footer.
        $this->assertMatchesRegularExpression('/@page\s*\{[^}]*margin:\s*0\.45in/', $css);
    }

    /** Portrait everywhere: a wide table pays in type size, never orientation. */
    public function test_nothing_turns_the_paper(): void
    {
        $this->assertStringNotContainsString('A4 landscape', $this->css());

        foreach (glob(app_path('Http/Controllers/*.php')) as $controller) {
            $this->assertStringNotContainsString(
                "'landscape'",
                file_get_contents($controller),
                basename($controller) . ' still asks for landscape.'
            );
        }
    }

    /** The 8pt floor for dense tables, per spec. */
    public function test_the_dense_table_does_not_go_below_eight_point(): void
    {
        preg_match('/\.pdf-table\.is-dense td \{[^}]*font-size:\s*([\d.]+)pt/', $this->css(), $m);

        $this->assertNotEmpty($m, 'No .is-dense body type size declared.');
        $this->assertGreaterThanOrEqual(8.0, (float) $m[1]);
    }

    /**
     * A float inside the fixed footer broke DomPDF's normal flow for everything
     * after it — the summary strip and every section vanished, leaving ten
     * mostly blank pages.
     */
    public function test_the_running_footer_uses_no_floats(): void
    {
        preg_match('/\.pdf-footer\s*\{.*?(?=\n\n)/s', $this->css(), $m);

        $this->assertNotEmpty($m, 'No .pdf-footer rule.');
        $this->assertStringNotContainsString('float:', $m[0]);
    }

    /** counter(pages) is unresolved in DomPDF; PdfPageNumbers stamps instead. */
    public function test_nothing_relies_on_the_pages_counter(): void
    {
        $this->assertDoesNotMatchRegularExpression('/content:[^;]*counter\(pages\)/', $this->css());
    }

    public function test_tables_are_fixed_layout_and_rows_do_not_split(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression('/\.pdf-table\s*\{[^}]*table-layout:\s*fixed/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-table tr \{[^}]*page-break-inside:\s*avoid/', $css);
    }

    /** Section headers must not be stranded from the table they introduce. */
    public function test_a_section_header_stays_with_its_table(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression('/\.pdf-section-head\s*\{[^}]*page-break-inside:\s*avoid/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-section-head\s*\{[^}]*page-break-after:\s*avoid/', $css);
    }

    /** Natural flow — a one-unit property must not own a whole page. */
    public function test_no_section_forces_a_page_break(): void
    {
        $this->assertStringNotContainsString('page-break-before: always', $this->css());
    }

    public function test_figtree_is_declared_and_the_files_exist(): void
    {
        $css = $this->css();

        foreach ([400, 700, 800] as $weight) {
            $this->assertMatchesRegularExpression(
                '/@font-face\s*\{[^}]*font-weight:\s*' . $weight . ';[^}]*Figtree-' . $weight . '\.ttf/',
                $css
            );
            $this->assertFileExists(public_path("fonts/Figtree-{$weight}.ttf"));
        }

        $this->assertStringContainsString("font-family: 'Figtree'", $css);
    }

    /** A sum over nothing but NULLs is unknown, not 0.000. */
    public function test_a_portfolio_with_no_rent_figures_totals_to_a_dash(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        PropertyUnit::create([
            'building_id'    => $building->id,
            'property_name'  => 'Tower A',
            'property_code'  => 'TA1',
            'unit_name'      => 'Flat 1',
            'rent_per_month' => null,
        ]);

        $html = view('data.export-pdf', [
            'buildings'   => Building::with(['floors', 'units'])->get(),
            'orphanUnits' => collect(),
            'unlisted'    => ['floors' => 0],
            'totals'      => ['buildings' => 1, 'floors' => 0, 'units' => 1, 'rent' => null],
        ])->render();

        $this->assertStringNotContainsString('0.000', $html);
        $this->assertStringContainsString('—', $html);
    }

    public function test_a_genuine_zero_still_prints_as_a_figure(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        PropertyUnit::create([
            'building_id'    => $building->id,
            'property_name'  => 'Tower A',
            'property_code'  => 'TA1',
            'unit_name'      => 'Flat 1',
            'rent_per_month' => 0,
        ]);

        $html = view('data.export-pdf', [
            'buildings'   => Building::with(['floors', 'units'])->get(),
            'orphanUnits' => collect(),
            'unlisted'    => ['floors' => 0],
            'totals'      => ['buildings' => 1, 'floors' => 0, 'units' => 1, 'rent' => 0.0],
        ])->render();

        $this->assertStringContainsString('0.000', $html);
    }

    public function test_the_document_still_renders_end_to_end(): void
    {
        Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('data.export', 'pdf'));

        $response->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * The approved "Gold seam" letterhead. Fixed values, so a later change that
     * quietly re-centres the mark or drops the seam gets caught.
     */
    public function test_the_letterhead_is_the_gold_seam_design(): void
    {
        $css = $this->css();

        // Logo left at 52px, a 3px gold seam, then the company block.
        $this->assertMatchesRegularExpression('/\.pdf-logo \{[^}]*height: 52px/', $css);
        // The seam is the company block's left border, not an element of its
        // own — the only way it stays as tall as the block it marks. A separate
        // div needs a height, and DomPDF gives you the bug either way: a stated
        // 52px stopped at the division line, height:100% collapsed to zero.
        $this->assertMatchesRegularExpression(
            '/\.pdf-company-cell\.has-seam \{[^}]*border-left: 3px solid #D8B25F/',
            $css
        );
        $this->assertStringNotContainsString('.pdf-seam', $css);
        $this->assertStringNotContainsString('pdf-seam', $this->letterheadMarkup());

        // Nothing may reintroduce a height on it.
        $this->assertDoesNotMatchRegularExpression('/\.pdf-company-cell\.has-seam \{[^}]*height:/', $css);

        $this->assertMatchesRegularExpression('/\.pdf-company \{[^}]*font-size: 12pt/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-division \{[^}]*letter-spacing: 0\.18em/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-division \{[^}]*color: #84681E/', $css);

        // Title plate.
        $this->assertMatchesRegularExpression('/\.pdf-plate \{[^}]*background: #F7F9FC/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-plate \{[^}]*border-radius: 10px/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-title \{[^}]*text-transform: uppercase/', $css);

        // No flex or grid: DomPDF honours neither, and a rule that assumes them
        // renders as a stack of blocks.
        $this->assertStringNotContainsString('display: flex', $css);
        $this->assertStringNotContainsString('display: grid', $css);
    }

    /** The approved "Gold accent" footer, on every page. */
    public function test_the_footer_is_the_gold_accent_design(): void
    {
        $css = $this->css();

        // 56px of gold then hairline grey — a left border, not a gradient,
        // which DomPDF does not render.
        $this->assertMatchesRegularExpression('/\.pdf-foot-rule \{[^}]*border-left: 56px solid #D8B25F/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-foot-rule \{[^}]*background: #E3E7EF/', $css);
        // A declaration, not a mention: pdf.css documents why the gradient the
        // brief suggests is not used, and a bare substring check flags its own
        // explanation.
        $this->assertDoesNotMatchRegularExpression('/background[^;{}]*linear-gradient/', $css);

        $this->assertMatchesRegularExpression('/\.pdf-foot-logo \{[^}]*height: 12px/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-foot-company \{[^}]*color: #1E2C4F/', $css);
        $this->assertMatchesRegularExpression('/\.pdf-foot-report \{[^}]*color: #8A93A8/', $css);

        // No page-number pill. It cannot exist: the counter has to be drawn on
        // the canvas (DomPDF has no page counters), so a flowed pill would be a
        // second layer for the number to drift away from — which is exactly
        // what it did, printing above its own capsule. The label stands alone.
        $this->assertStringNotContainsString('.pdf-foot-pill', $css);
        $this->assertStringNotContainsString('.pdf-foot-pill', $this->footerMarkup());

        // It repeats: fixed position, so it is not part of the flow.
        $this->assertMatchesRegularExpression('/\.pdf-footer \{[^}]*position: fixed/', $css);
    }

    // ── Letterhead organisation line ─────────────────────────────────────────

    /**
     * The contact email is the one editable item on the letterhead, so it has
     * to survive being unset: a blank setting must take its separator with it
     * rather than print "+973 17500787 &middot;" and stop.
     */
    public function test_the_letterhead_prints_the_branding_contact_email(): void
    {
        BrandingSetting::current()->update(['company_email' => 'accounts@example.com']);

        $html = $this->renderLetterhead();

        $this->assertStringContainsString('accounts@example.com', $html);
        $this->assertStringContainsString('CR&nbsp;#&nbsp;21534-1', $html);
    }

    public function test_the_letterhead_drops_the_separator_when_no_email_is_set(): void
    {
        BrandingSetting::current()->update(['company_email' => null]);

        $org = $this->organisationLine($this->renderLetterhead());

        $this->assertStringContainsString('17500787', $org);
        $this->assertStringNotContainsString('@', $org);

        // The bug this guards: a middot with nothing after it. Normalised first,
        // because the Blade @if leaves the line's own whitespace behind.
        $this->assertDoesNotMatchRegularExpression(
            '/&middot;\s*$/',
            preg_replace('/\s+/', ' ', trim(strip_tags($org, '<br>'))) ?: ''
        );
        $this->assertStringNotContainsString('·  ·', preg_replace('/\s+/', ' ', strip_tags($org)));
    }

    /** A blank string is not an email, and must behave as an unset one. */
    public function test_a_whitespace_only_email_is_treated_as_unset(): void
    {
        BrandingSetting::current()->update(['company_email' => '   ']);

        $this->assertNull(BrandingSetting::letterheadEmail());
    }

    /**
     * Rendering an export must not write to the database. current() would
     * create the settings row as a side effect, which is why the letterhead
     * reads through letterheadEmail().
     */
    public function test_reading_the_letterhead_email_does_not_create_a_settings_row(): void
    {
        $this->assertSame(0, BrandingSetting::count());

        $this->assertNull(BrandingSetting::letterheadEmail());

        $this->assertSame(0, BrandingSetting::count(), 'Rendering the letterhead created a settings row.');
    }

    /**
     * The address and the contact line are split on purpose. Left to wrap, the
     * line broke after "TRN #" and orphaned the number onto the next line, so
     * the break is explicit and the labels are glued to their values.
     */
    public function test_the_organisation_line_breaks_after_the_address(): void
    {
        BrandingSetting::current()->update(['company_email' => 'realestateaccounts@promoseven.com']);

        $org = preg_replace('/\s+/', ' ', $this->organisationLine($this->renderLetterhead()));

        $this->assertMatchesRegularExpression('/Kingdom of Bahrain\s*<br>\s*CR/', $org);

        // Nothing on the contact line may separate a label from its value.
        $this->assertStringNotContainsString('CR # 21534', strip_tags($org));
    }

    /**
     * The TRN is supplied by the transactional views, not the layout, so the
     * gluing has to hold there too — it lands at the end of the contact line,
     * which is where a wrap would strand it.
     */
    public function test_every_view_that_supplies_a_trn_glues_it_to_its_number(): void
    {
        $found = 0;

        foreach (self::VIEWS as $view) {
            $blade = file_get_contents(resource_path('views/' . $view . '.blade.php'));

            if (!str_contains($blade, 'letterhead-extra')) {
                continue;
            }

            $found++;
            $this->assertStringNotContainsString('TRN # ', $blade, "{$view} leaves its TRN breakable.");
            $this->assertStringContainsString('TRN&nbsp;#&nbsp;', $blade, "{$view} does not glue its TRN.");
        }

        $this->assertGreaterThan(0, $found, 'No view supplies letterhead-extra any more.');
    }

    // ── The gold seam ────────────────────────────────────────────────────────

    /**
     * The seam must be as tall as the company block, contact lines included.
     *
     * It used to be a separate div with height: 52px — the logo's height — so it
     * stopped at the division line and left the address and contact lines
     * outside it. Making that div height: 100% instead collapsed it to nothing
     * at all in DomPDF, which is why the seam is now the company block's own
     * left border: a border cannot be shorter than the box it belongs to.
     *
     * Measured off the rasterised page, because the seam is drawn, not text —
     * a CSS assertion cannot tell you how tall it came out.
     */
    public function test_the_gold_seam_spans_the_whole_company_block(): void
    {
        if (!$this->hasPoppler()) {
            $this->markTestSkipped('poppler-utils (pdftoppm/pdftotext) is not installed.');
        }

        BrandingSetting::current()->update(['company_email' => 'accounts@example.com']);

        [$seamTop, $seamBottom] = $this->seamExtent($bytes = $this->letterheadPdf());
        [$textTop, $textBottom] = $this->letterheadTextExtent($bytes);

        // The seam encloses the block: it starts at or above the first line's
        // ink and ends at or below the last line's ink.
        $this->assertLessThanOrEqual($textTop, $seamTop, 'The seam starts below the company name.');
        $this->assertGreaterThanOrEqual(
            $textBottom,
            $seamBottom,
            sprintf('The seam ends at %.2fpt but the block runs to %.2fpt.', $seamBottom, $textBottom)
        );
    }

    /**
     * And it has to follow the block when the block grows — the property a
     * fixed height cannot have. A statutory line long enough to wrap adds a
     * line to the block, and the seam has to cover it.
     *
     * Wrapped with spaces on purpose: DomPDF will not break inside a single
     * unbroken token, so a very long email overflows the line instead of
     * adding one, and would not test anything.
     */
    public function test_the_gold_seam_grows_with_a_taller_company_block(): void
    {
        if (!$this->hasPoppler()) {
            $this->markTestSkipped('poppler-utils (pdftoppm/pdftotext) is not installed.');
        }

        BrandingSetting::current()->update(['company_email' => 'accounts@example.com']);

        [, $shortSeam] = $this->seamExtent($this->letterheadPdf());

        $long = implode(' ', array_fill(0, 40, 'REGISTERED'));
        [, $tallSeam]   = $this->seamExtent($bytes = $this->letterheadPdf($long));
        [, $textBottom] = $this->letterheadTextExtent($bytes);

        $this->assertGreaterThan($shortSeam, $tallSeam, 'The seam did not follow the taller block.');
        $this->assertGreaterThanOrEqual($textBottom, $tallSeam, 'The seam is short of the wrapped line.');
    }

    private function hasPoppler(): bool
    {
        exec('command -v pdftoppm && command -v pdftotext', $out, $status);

        return $status === 0;
    }

    private function letterheadPdf(?string $extra = null): string
    {
        $pdf = Pdf::loadHTML($this->renderLetterhead($extra))->setPaper('a4', 'portrait');

        return PdfPageNumbers::stamp($pdf)->output();
    }

    /** Top and bottom of the gold column, in points from the page top. */
    private function seamExtent(string $bytes): array
    {
        $dir = $this->scratch($bytes);
        exec(sprintf('pdftoppm -r %d -f 1 -l 1 -singlefile %s %s', 150, escapeshellarg("$dir/doc.pdf"), escapeshellarg("$dir/page")));

        $ppm = file_get_contents("$dir/page.ppm");
        $this->cleanUp($dir);

        // P6: magic, width, height, maxval, then three bytes per pixel.
        preg_match('/^P6\s+(\d+)\s+(\d+)\s+(\d+)\s/', $ppm, $m);
        $this->assertNotEmpty($m, 'pdftoppm did not produce a P6 bitmap.');

        [$w, $h] = [(int) $m[1], (int) $m[2]];
        $data    = substr($ppm, strlen($m[0]));
        $scale   = 72 / 150;

        $top = null;
        $bottom = null;

        // Top third of the page only. The running footer's rule is the same
        // gold (.pdf-foot-rule's 56px left border), so scanning the whole page
        // would report the seam as reaching the bottom of the paper however
        // short it actually is — which is the bug being tested for.
        $band = min($h, (int) round(self::GOLD_SCAN_BAND / $scale));

        // #D8B25F, with tolerance for the rasteriser's antialiasing.
        for ($y = 0; $y < $band; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $i = ($y * $w + $x) * 3;
                if (abs(ord($data[$i]) - 216) < 42 && abs(ord($data[$i + 1]) - 178) < 42 && abs(ord($data[$i + 2]) - 95) < 42) {
                    $top ??= $y;
                    $bottom = $y;
                    break;
                }
            }
        }

        $this->assertNotNull($top, 'No gold seam on the page at all.');

        return [$top * $scale, ($bottom + 1) * $scale];
    }

    /** Ink top and bottom of the letterhead's own lines, in points. */
    private function letterheadTextExtent(string $bytes): array
    {
        $dir = $this->scratch($bytes);
        exec(sprintf('pdftotext -bbox -f 1 -l 1 %s %s', escapeshellarg("$dir/doc.pdf"), escapeshellarg("$dir/doc.xml")));
        $xml = file_get_contents("$dir/doc.xml");
        $this->cleanUp($dir);

        preg_match_all(
            '/<word xMin="[\d.]+" yMin="([\d.]+)" xMax="[\d.]+" yMax="([\d.]+)">([^<]*)<\/word>/',
            $xml,
            $ws,
            PREG_SET_ORDER
        );

        // The letterhead is every word before the title plate. Delimited by the
        // plate's own first word rather than by a y cutoff, because a wrapped
        // statutory line pushes the plate down — a fixed cutoff would either
        // clip the letterhead or count the plate as part of it.
        $letterhead = [];

        foreach ($ws as $w) {
            if ($w[3] === self::PLATE_SENTINEL) {
                break;
            }
            $letterhead[] = $w;
        }

        $this->assertNotEmpty($letterhead, 'No letterhead text before the title plate.');
        $this->assertNotSame(count($ws), count($letterhead), 'Never found the title plate.');

        return [
            min(array_map(fn ($w) => (float) $w[1], $letterhead)),
            max(array_map(fn ($w) => (float) $w[2], $letterhead)),
        ];
    }

    private function scratch(string $bytes): string
    {
        $dir = sys_get_temp_dir() . '/pdf-seam-' . getmypid() . '-' . substr(md5($bytes), 0, 8);
        @mkdir($dir, 0777, true);
        file_put_contents("$dir/doc.pdf", $bytes);

        return $dir;
    }

    private function cleanUp(string $dir): void
    {
        array_map('unlink', glob("$dir/*") ?: []);
        @rmdir($dir);
    }

    private function renderLetterhead(?string $extra = null): string
    {
        return Blade::render(<<<'BLADE'
            @extends('layouts.pdf')
            @section('report-title', 'PLATESTART')
            @section('letterhead-extra', $extra)
            @section('content')<p>Body.</p>@endsection
            BLADE, ['extra' => $extra ?? 'TRN&nbsp;#&nbsp;200010076400002']);
    }

    /** Just the .pdf-org block, so a match cannot come from the footer. */
    private function organisationLine(string $html): string
    {
        preg_match('/<div class="pdf-org">(.*?)<\/div>/s', $html, $m);

        $this->assertNotEmpty($m, 'No .pdf-org block in the letterhead.');

        return $m[1];
    }

    private function footerMarkup(): string
    {
        return file_get_contents(resource_path('views/layouts/pdf.blade.php'));
    }

    private function letterheadMarkup(): string
    {
        return file_get_contents(resource_path('views/layouts/pdf.blade.php'));
    }

    /**
     * The regression this file exists for.
     *
     * The counter used to be stamped at hand-tuned coordinates that had drifted
     * from the CSS — 5.8pt above the footer's baseline and 10.8pt off the pill
     * it was supposed to sit inside, so "1 / 5" printed above its own
     * background. Both axes are now derived, so this asserts the two things
     * that were wrong: the label sits on the same baseline as the footer's own
     * text, and it ends flush on the @page margin.
     *
     * Measured off the rendered document rather than off the constants, which
     * would only restate them.
     */
    public function test_the_page_counter_sits_on_the_footer_baseline_on_every_page(): void
    {
        if (!$this->hasPdfToText()) {
            $this->markTestSkipped('poppler-utils (pdftotext) is not installed.');
        }

        $words = $this->footerWordsPerPage($this->multiPagePdf());

        // Enough pages that the page number goes from one digit to two: the old
        // implementation substituted a placeholder into a string whose width was
        // measured once, so page 10 onwards could not stay flush.
        $this->assertGreaterThanOrEqual(10, count($words), 'Need a 10+ page document.');

        $contentEdge = 595.28 - 32.4;   // A4 width less the @page side margin

        foreach ($words as $page => $line) {
            $label = array_values(array_filter($line, fn ($w) => $w['x1'] > 500));
            $body  = array_values(array_filter($line, fn ($w) => $w['text'] === 'Promoseven'));

            $this->assertNotEmpty($body, "Page {$page} has no footer company name.");
            $this->assertSame(
                ['PAGE', (string) $page, '/', (string) count($words)],
                array_column($label, 'text'),
                "Page {$page} does not read \"PAGE {$page} / \" . count(words)."
            );

            // The bug: the number floated above the footer line.
            $this->assertEqualsWithDelta(
                $body[0]['y0'],
                $label[0]['y0'],
                0.05,
                "Page {$page}'s counter is off the footer's baseline."
            );

            // And sat left of where it belonged.
            $this->assertEqualsWithDelta(
                $contentEdge,
                end($label)['x1'],
                0.05,
                "Page {$page}'s counter is not flush on the margin."
            );
        }
    }

    private function hasPdfToText(): bool
    {
        exec('command -v pdftotext', $out, $status);

        return $status === 0;
    }

    /** A real export, long enough to paginate past nine pages. */
    private function multiPagePdf(): string
    {
        $rows = '';
        for ($i = 1; $i <= 340; $i++) {
            $rows .= '<tr><td>Row ' . $i . '</td><td class="pdf-num">' . ($i * 137.5) . '</td></tr>';
        }

        $html = Blade::render(<<<'BLADE'
            @extends('layouts.pdf')
            @section('report-title', 'PAGINATION CHECK')
            @section('content')
            <table class="pdf-table">
                <thead><tr><th>Ref</th><th class="pdf-num">Amount</th></tr></thead>
                <tbody>{!! $rows !!}</tbody>
            </table>
            @endsection
            BLADE, ['rows' => $rows]);

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return PdfPageNumbers::stamp($pdf)->output();
    }

    /**
     * Every word in the footer band, per page, as [text, x1, y0] in points.
     * Keyed by page number so failures name the page.
     */
    private function footerWordsPerPage(string $bytes): array
    {
        $dir = sys_get_temp_dir() . '/pdf-footer-' . getmypid();
        @mkdir($dir);
        file_put_contents("$dir/doc.pdf", $bytes);
        exec(sprintf('pdftotext -bbox %s %s', escapeshellarg("$dir/doc.pdf"), escapeshellarg("$dir/doc.xml")));
        $xml = file_get_contents("$dir/doc.xml");
        array_map('unlink', glob("$dir/*"));
        @rmdir($dir);

        preg_match_all('/<page width="[\d.]+" height="([\d.]+)">(.*?)<\/page>/s', $xml, $pages, PREG_SET_ORDER);

        $out = [];

        foreach ($pages as $i => $page) {
            $band = (float) $page[1] - 45.0;   // the footer band only
            preg_match_all(
                '/<word xMin="([\d.]+)" yMin="([\d.]+)" xMax="([\d.]+)" yMax="[\d.]+">([^<]*)<\/word>/',
                $page[2],
                $words,
                PREG_SET_ORDER
            );

            $out[$i + 1] = array_values(array_filter(array_map(fn ($w) => [
                'text' => $w[4],
                'x0'   => (float) $w[1],
                'x1'   => (float) $w[3],
                'y0'   => (float) $w[2],
            ], $words), fn ($w) => $w['y0'] > $band));
        }

        return $out;
    }
}
