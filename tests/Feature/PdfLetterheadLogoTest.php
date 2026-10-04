<?php

namespace Tests\Feature;

use App\Models\BrandingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The letterhead mark in the shared PDF layout.
 *
 * Two things here only fail in the finished file, not in the HTML, so they get
 * guards: the logo must come from Settings → Branding rather than a bundled
 * literal, and it must travel as inline bytes. DomPDF resolves an <img src>
 * against its chroot (public/ by default) and the upload lives under
 * storage/app/public behind the public/storage symlink, which realpath() takes
 * straight back out again — so a path renders as a blank gap, silently.
 */
class PdfLetterheadLogoTest extends TestCase
{
    use RefreshDatabase;

    /** A 1x1 transparent PNG — distinct from the bundled mark, so the two sources can't be confused. */
    private const PIXEL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /** Any view extending layouts.pdf renders the shared letterhead; this is the cheapest one. */
    private function letterhead(): string
    {
        return view('exports.listing-pdf', [
            'title'   => 'Buildings',
            'noun'    => 'property',
            'count'   => 0,
            'columns' => [['label' => 'Property Name', 'align' => 'left']],
            'rows'    => collect(),
            'applied' => [],
            'trimmed' => false,
            'totalColumns' => 1,
        ])->render();
    }

    private function pdfLogoRule(): string
    {
        $css = file_get_contents(public_path('css/pdf.css'));

        $this->assertMatchesRegularExpression('/\.pdf-logo\s*\{[^}]*\}/', $css, 'pdf.css has no .pdf-logo rule.');
        preg_match('/\.pdf-logo\s*\{([^}]*)\}/', $css, $m);

        return $m[1];
    }

    public function test_the_letterhead_prints_the_logo_uploaded_in_branding_settings(): void
    {
        Storage::fake('public');
        $bytes = base64_decode(self::PIXEL);
        Storage::disk('public')->put('branding/mark.png', $bytes);
        BrandingSetting::create(['site_name' => 'Promoseven', 'logo_path' => 'branding/mark.png']);

        $html = $this->letterhead();

        $this->assertStringContainsString('class="pdf-logo"', $html);
        $this->assertStringContainsString('src="data:image/png;base64,' . base64_encode($bytes) . '"', $html);
    }

    /** No upload yet is the common case on a fresh install — it must not print a hole. */
    public function test_it_falls_back_to_the_bundled_mark_when_no_logo_has_been_uploaded(): void
    {
        BrandingSetting::create(['site_name' => 'Promoseven']);

        $expected = base64_encode(file_get_contents(public_path('logo/promoseven-logo.png')));

        $this->assertStringContainsString(
            'src="data:image/png;base64,' . $expected . '"',
            $this->letterhead()
        );
    }

    /** The chroot trap: the mark must be embedded, never fetched. */
    public function test_the_mark_is_embedded_rather_than_referenced_by_path_or_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/mark.png', base64_decode(self::PIXEL));
        BrandingSetting::create(['logo_path' => 'branding/mark.png']);

        preg_match('/<img class="pdf-logo"[^>]*>/', $this->letterhead(), $m);

        $this->assertNotEmpty($m, 'The letterhead prints no logo at all.');
        $this->assertStringContainsString('src="data:', $m[0]);
        foreach (['http', '/storage/', storage_path(), public_path()] as $reference) {
            $this->assertStringNotContainsString($reference, $m[0], "The logo is referenced by {$reference}.");
        }
    }

    /** Rendering an export is a read; it must not write the settings row into existence. */
    public function test_resolving_the_logo_never_creates_the_settings_row(): void
    {
        $this->assertNotNull(BrandingSetting::letterheadLogoDataUri());

        $this->assertDatabaseCount('branding_settings', 0);
    }

    /**
     * The approved "Gold seam" letterhead puts the mark on the LEFT of a row at
     * 52px, with a 3px gold seam between it and the company block — it is no
     * longer centred above the name. Both dimensions are stated because DomPDF
     * will not infer one from the other.
     */
    public function test_the_mark_sits_left_at_52px(): void
    {
        $rule = $this->pdfLogoRule();

        $this->assertMatchesRegularExpression('/display:\s*block/', $rule);
        $this->assertMatchesRegularExpression('/width:\s*52px/', $rule);
        $this->assertMatchesRegularExpression('/height:\s*52px/', $rule);
        $this->assertStringNotContainsString('auto', $rule, 'The mark is no longer centred.');
    }

    /**
     * The logo is a shaped, transparent mark, so a radius or a plate behind it
     * would draw a square edge around a round mark.
     */
    public function test_the_mark_carries_no_tile_behind_it(): void
    {
        $rule = $this->pdfLogoRule();

        $this->assertStringNotContainsString('border-radius', $rule);
        $this->assertStringNotContainsString('background', $rule);
        $this->assertStringNotContainsString('border:', $rule);
    }
}
