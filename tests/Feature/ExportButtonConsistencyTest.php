<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * The Export button is one object, app-wide.
 *
 * It was three: soft green (.btn-success) on Buildings, Units and Floors,
 * .btn-outline on Tenants, Leases and the EWA summary, and gold .btn-primary on
 * all ten reports — the same word doing the same job at three different weights
 * on three tabs of the same app. The navy secondary won, and it now lives in
 * exactly two files: .btn-export in app-core.css and
 * components/export-button.blade.php, which is the only thing allowed to render
 * it.
 *
 * Like UiConsistencyTest, this reads the sources: no database, no booted app,
 * milliseconds to run.
 */
class ExportButtonConsistencyTest extends TestCase
{
    private function views(): array
    {
        $out = [];
        $base = dirname(__DIR__, 2).'/resources/views';

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $out[str_replace($base.'/', '', $file->getPathname())] = file_get_contents($file->getPathname());
        }
        ksort($out);

        return $out;
    }

    private function css(string $file): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/public/css/'.$file);
    }

    private function stripComments(string $css): string
    {
        return preg_replace('#/\*.*?\*/#s', '', $css);
    }

    public function test_the_export_button_is_defined_once_in_app_core(): void
    {
        $css = $this->css('app-core.css');

        $this->assertSame(1, preg_match_all('/^\.btn-export\s*\{/m', $css),
            '.btn-export must be declared exactly once, in app-core §4.1.');

        // Navy ink on the card surface, and every value a token.
        preg_match('/^\.btn-export\s*\{(.*?)\}/ms', $css, $m);
        $this->assertStringContainsString('var(--card-bg)', $m[1]);
        $this->assertStringContainsString('var(--card-border)', $m[1]);
        $this->assertStringContainsString('var(--text-primary)', $m[1]);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}\b/', $m[1],
            'The export button takes its colour from tokens, so it flips with the theme.');

        // Hover is the only extra state it defines; focus-visible and disabled
        // come from .btn, which is the point of it being a .btn variant.
        $this->assertStringContainsString('.btn-export:hover', $css);
        $this->assertMatchesRegularExpression('/\.btn:focus-visible/', $css);
        $this->assertMatchesRegularExpression('/\.btn:disabled/', $css);
    }

    public function test_the_green_variant_is_gone(): void
    {
        // Selectors, not the words: app-core's comment on .btn-export names the
        // variant it replaced, and that note is the reason this rule exists.
        $this->assertDoesNotMatchRegularExpression('/(^|[\s,>+~])\.btn-success(?![\w-])/m',
            $this->stripComments($this->css('app-core.css')),
            'The green Export variant was deleted so it cannot come back.');
        $this->assertDoesNotMatchRegularExpression('/(^|[\s,>+~])\.green-outline(?![\w-])/m',
            $this->stripComments($this->css('app-mobile.css')),
            'The mobile layer kept a green-named export class; it is .outline now.');

        $offenders = [];
        foreach ($this->views() as $path => $c) {
            if (str_contains($c, 'btn-success') || str_contains($c, 'green-outline')) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders,
            "These views still ask for the green button:\n  ".implode("\n  ", $offenders));
    }

    public function test_every_export_trigger_goes_through_the_component(): void
    {
        $allowed = [
            'components/export-button.blade.php',   // the component itself
            'partials/export-menu.blade.php',       // wraps it in the format popover
        ];

        $offenders = [];

        foreach ($this->views() as $path => $c) {
            if (in_array($path, $allowed, true)) {
                continue;
            }
            // A button or link whose label starts with "Export" and that is not
            // the component. The mobile .m-* layer labels its two links "XLSX"
            // and "PDF" and is a separate visual system, so it is not caught.
            if (preg_match_all('/<(a|button)\b[^>]*>\s*(<i[^>]*>\s*<\/i>\s*)?\s*Export\b/i', $c, $m)) {
                $offenders[] = "{$path}: ".count($m[0]).' hand-written Export button(s)';
            }
        }

        $this->assertSame([], $offenders,
            "Export renders through <x-export-button>, never by hand:\n  ".implode("\n  ", $offenders));
    }

    public function test_no_page_restyles_the_export_button(): void
    {
        $offenders = [];

        foreach ($this->views() as $path => $c) {
            preg_match_all("/@push\(['\"]styles['\"]\)(.*?)@endpush/s", $c, $m);
            $pageCss = implode("\n", $m[1] ?? []);

            if (preg_match('/\.btn-export(?![\w-])/', $pageCss)) {
                $offenders[] = $path;
            }
            // A page may not hand the component a competing class either.
            if (preg_match('/<x-export-button[^>]*\bclass=/', $c)) {
                $offenders[] = "{$path} (passes its own class)";
            }
        }

        $this->assertSame([], $offenders,
            "The export button has one style, in app-core:\n  ".implode("\n  ", $offenders));
    }

    public function test_the_export_menu_partial_takes_no_style_parameter(): void
    {
        $partial = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/partials/export-menu.blade.php'
        );

        $this->assertStringContainsString('<x-export-button', $partial);
        $this->assertStringNotContainsString('$class', $partial,
            'The per-page class parameter is what let Export drift; it stays gone.');

        $offenders = [];
        foreach ($this->views() as $path => $c) {
            if (! str_contains($c, "partials.export-menu")) {
                continue;
            }
            if (preg_match("/partials\.export-menu.*?\]\)/s", $c, $m) && str_contains($m[0], "'class'")) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders,
            "These pages still pass a button class to the export menu:\n  ".implode("\n  ", $offenders));
    }
}
