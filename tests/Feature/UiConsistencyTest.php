<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * The design system's invariants, asserted against the Blade sources.
 *
 * Deliberately a plain PHPUnit test: it reads files, needs no database and no
 * booted application, so it runs in milliseconds and can never be the slow
 * part of the suite.
 *
 * These are the rules the app drifted from before the standardization pass:
 * two page headers, two KPI cards, nine table implementations, twelve
 * pagination footers, and per-page CSS redefining shared components. Each
 * test below is the tripwire for one of them, so the next page to be added
 * cannot quietly reintroduce it.
 */
class UiConsistencyTest extends TestCase
{
    /** Views that are deliberately outside the admin design system. */
    private const EXEMPT = [
        'emails/',            // transactional email — inline CSS by necessity
        // auth/login is on app-core like every other page now, so it is held to
        // the same invariants. The two /login-preview/* variants are unrouted
        // design mockups kept for comparison and are deliberately outside it.
        'auth/login-1a.blade.php',
        'auth/login-1b.blade.php',
        'welcome.blade.php',  // Laravel's default landing page, not routed
        'vendor/',            // published framework views
        'layouts/',           // the shell itself defines the system
        'partials/',
    ];

    /**
     * Known, documented exceptions with a reason. Empty: buildings/show was
     * the last one — it ran on the Bootstrap CDN and is now on the design
     * system like everything else.
     */
    private const LEGACY = [];

    /** @return array<string, string> path => contents */
    private function views(bool $includeLegacy = false): array
    {
        $out = [];
        $base = dirname(__DIR__, 2).'/resources/views';

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $rel = str_replace($base.'/', '', $file->getPathname());

            if (str_contains($rel, 'pdf') || str_contains($rel, 'receipt')) {
                continue;
            }
            foreach (self::EXEMPT as $skip) {
                if (str_contains($rel, $skip)) {
                    continue 2;
                }
            }
            if (! $includeLegacy && in_array($rel, self::LEGACY, true)) {
                continue;
            }

            $out[$rel] = file_get_contents($file->getPathname());
        }

        ksort($out);

        return $out;
    }

    /** The CSS a view pushes into @stack('styles'). */
    private function pageCss(string $contents): string
    {
        preg_match_all("/@push\(['\"]styles['\"]\)(.*?)@endpush/s", $contents, $m);

        return implode("\n", $m[1] ?? []);
    }

    public function test_no_page_builds_its_own_page_header(): void
    {
        $offenders = [];

        foreach ($this->views(true) as $path => $c) {
            if (str_contains($c, 'class="page-header')) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders,
            "These pages draw their own header instead of @section('page-title'):\n  ".implode("\n  ", $offenders));
    }

    public function test_every_back_button_uses_the_shared_header_slot(): void
    {
        // The way up is one object across the app: @section('page-back'), which
        // the layout renders into .shell-pagehead-back and app-core turns into
        // a 44px icon button beside the title under 600px. A page that opens
        // its own action row with a Back instead gets the full-width button
        // below the title that this replaced — on that page only.
        $offenders = [];

        foreach ($this->views(true) as $path => $c) {
            if (preg_match_all("/@section\(['\"]page-actions['\"]\)(.*?)@endsection/s", $c, $m)) {
                foreach ($m[1] as $block) {
                    if (str_contains($block, 'fa-arrow-left')) {
                        $offenders[] = "{$path}: Back lives in page-actions, not page-back";
                    }
                }
            }

            if (! preg_match("/@section\(['\"]page-back['\"]\)(.*?)@endsection/s", $c, $b)) {
                continue;
            }
            // Icon-only on a phone, so the control needs a name of its own and
            // the label needs the hook that hides it.
            if (! str_contains($b[1], 'aria-label')) {
                $offenders[] = "{$path}: page-back has no aria-label";
            }
            if (! str_contains($b[1], 'pagehead-back-label')) {
                $offenders[] = "{$path}: page-back label is not in a .pagehead-back-label";
            }
        }

        $this->assertSame([], $offenders,
            "Back belongs to the shared header slot:\n  ".implode("\n  ", $offenders));
    }

    public function test_kpi_cards_all_use_the_shared_anatomy(): void
    {
        $offenders = [];

        foreach ($this->views() as $path => $c) {
            $cards = preg_match_all('/class="stat-card(?![\w-])/', $c);
            if ($cards === 0) {
                continue;
            }
            // Every card leads with .stat-card-top (icon + label above the figure).
            $tops = substr_count($c, 'class="stat-card-top"');
            if ($tops < $cards) {
                $offenders[] = "{$path}: {$cards} cards, {$tops} with .stat-card-top";
            }
        }

        $this->assertSame([], $offenders, implode("\n  ", $offenders));
    }

    public function test_every_table_can_scroll_inside_its_container(): void
    {
        $offenders = [];

        foreach ($this->views(true) as $path => $c) {
            $offset = 0;
            while (($pos = strpos($c, '<table', $offset)) !== false) {
                $before = substr($c, max(0, $pos - 400), min(400, $pos));
                if (! str_contains($before, 'table-wrap')
                    && ! str_contains($before, 'table-responsive')
                    && ! str_contains($before, 'overflow-x')) {
                    $offenders[] = $path;
                    break;
                }
                $offset = $pos + 6;
            }
        }

        $this->assertSame([], $offenders,
            "Tables outside a scroll container overflow the page on mobile:\n  ".implode("\n  ", $offenders));
    }

    public function test_no_page_css_redefines_a_shared_component(): void
    {
        $shared = [
            'stats-grid', 'stat-card', 'stat-icon', 'stat-val', 'stat-lbl',
            'page-header', 'filter-bar', 'filter-group', 'table-card', 'table-wrap',
            'table-footer', 'result-count', 'pagination', 'page-btn',
            'card-header', 'card-body', 'card-title', 'card-footer',
            'btn', 'badge', 'status-badge', 'alert', 'empty-state', 'empty-icon',
            'form-actions', 'form-grid', 'tab-bar', 'tab-btn',
        ];

        $offenders = [];

        foreach ($this->views() as $path => $c) {
            $css = $this->pageCss($c);
            if ($css === '') {
                continue;
            }
            // Appearance properties. A page may not set these on a shared
            // component; layout-only tweaks (flex, grid-column, width) inside
            // the page's own container are fine and stay out of the system's way.
            $appearance = '/\b(background|color|border(?!-radius)?|border-radius|box-shadow|font|font-size|'
                .'font-weight|font-family|padding|height|min-height|text-transform|letter-spacing)\s*:/';

            preg_match_all('/([^{}]+)\{([^}]*)\}/', $css, $m, PREG_SET_ORDER);
            foreach ($m as [$_, $selector, $body]) {
                $lines = explode("\n", trim($selector));
                $sel = trim(end($lines));
                if ($sel === '' || str_starts_with($sel, '@') || str_starts_with($sel, '/*')) {
                    continue;
                }
                if (! preg_match($appearance, $body)) {
                    continue;
                }
                foreach ($shared as $cls) {
                    if (preg_match('/(^|[\s,>+~])\.'.preg_quote($cls, '/').'(?![\w-])/', $sel)) {
                        $offenders[] = "{$path}: {$sel}";
                    }
                }
            }
        }

        $this->assertSame([], $offenders,
            "Page CSS must not restyle a shared component:\n  ".implode("\n  ", $offenders));
    }

    public function test_page_css_declares_no_raw_colour(): void
    {
        $offenders = [];

        foreach ($this->views() as $path => $c) {
            $css = $this->pageCss($c);
            if (preg_match_all('/#[0-9a-fA-F]{3,6}\b/', $css, $m)) {
                $offenders[] = "{$path}: ".implode(', ', array_unique($m[0]));
            }
        }

        $this->assertSame([], $offenders,
            "Colour comes from a token, so both themes stay legible:\n  ".implode("\n  ", $offenders));
    }

    public function test_page_css_sets_no_section_margin(): void
    {
        // Section rhythm belongs to .shell-content's gap (app-core §7.5). A page
        // that sets its own leaves the KPI row at a different height per page.
        $sections = ['stats-grid', 'table-card', 'filter-card', 'page-header', 'card-grid', 'table-footer'];
        $offenders = [];

        foreach ($this->views() as $path => $c) {
            $css = $this->pageCss($c);
            preg_match_all('/([^{}]+)\{([^}]*)\}/', $css, $m, PREG_SET_ORDER);
            foreach ($m as [$_, $selector, $body]) {
                $lines = explode("\n", trim($selector));
                $sel = trim(end($lines));
                foreach ($sections as $cls) {
                    if (str_contains($sel, ".{$cls}") && preg_match('/margin(-top|-bottom)?\s*:/', $body)) {
                        $offenders[] = "{$path}: {$sel}";
                    }
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n  ", $offenders));
    }

    public function test_every_filter_bar_sits_inside_a_card(): void
    {
        // app-core §4.5: the filter bar belongs to the thing it narrows —
        // inside the .table-card above the header row, or in its own
        // .filter-card when the result below is not a table. A bar floating
        // on the page background is not a third option.
        $offenders = [];

        foreach ($this->views(true) as $path => $c) {
            $bars = substr_count($c, 'class="filter-bar');
            if ($bars === 0) {
                continue;
            }
            $inside = preg_match_all(
                // card open → optional Blade comments / directives / a form → the bar
                '/class="(?:table-card|filter-card)[^"]*"[^>]*>'
                .'(?:\s|\{\{--.*?--\}\}|@[a-z]+(?:\([^)]*\))?|<form[^>]*>)*'
                .'<div class="filter-bar/s',
                $c
            );
            if ($inside < $bars) {
                $offenders[] = "{$path}: {$bars} bars, {$inside} inside a card";
            }
        }

        $this->assertSame([], $offenders, implode("\n  ", $offenders));
    }

    /**
     * Class names of elements that hold two or more direct .card children —
     * i.e. a row or grid of cards, rather than a card standing on its own.
     *
     * @return list<string>
     */
    private function cardRowContainers(string $markup): array
    {
        $void = ['br', 'img', 'input', 'hr', 'meta', 'link', 'source', 'path', 'circle', 'use', 'col', 'area', 'embed'];
        $stack = [];
        $found = [];

        preg_match_all('/<(\/?)([a-z][a-z0-9]*)([^>]*)>/i', $markup, $tags, PREG_SET_ORDER);

        foreach ($tags as [$_, $closing, $tag, $attrs]) {
            $tag = strtolower($tag);

            if ($closing === '/') {
                for ($i = count($stack) - 1; $i >= 0; $i--) {
                    if ($stack[$i]['tag'] === $tag) {
                        $stack = array_slice($stack, 0, $i);
                        break;
                    }
                }

                continue;
            }
            if (in_array($tag, $void, true) || str_ends_with(rtrim($attrs), '/')) {
                continue;
            }

            $class = preg_match('/class="([^"]*)"/', $attrs, $m) ? $m[1] : '';
            $classes = preg_split('/\s+/', trim(preg_replace('/\{\{.*?\}\}/s', '', $class)), -1, PREG_SPLIT_NO_EMPTY);

            if (in_array('card', $classes, true) && $stack !== []) {
                $parent = &$stack[count($stack) - 1];
                $parent['cards']++;
                if ($parent['cards'] === 2 && $parent['classes'] !== []) {
                    $found[] = $parent['classes'][0];
                }
                unset($parent);
            }

            $stack[] = ['tag' => $tag, 'classes' => $classes, 'cards' => 0];
        }

        return array_values(array_unique($found));
    }

    public function test_a_row_of_cards_resets_the_stacking_margin(): void
    {
        // app-core §4.3 gives stacked cards their rhythm with `.card + .card`,
        // which is right in normal flow and wrong everywhere else: a container
        // that spaces its children with `gap` has to switch the margin off, or
        // its second card starts --sp-5 lower than its first — and in a stretch
        // grid ends --sp-5 shorter. That misalignment is invisible in the CSS
        // and obvious on the page, so it is asserted here.
        $core = file_get_contents(dirname(__DIR__, 2).'/public/css/app-core.css');
        $offenders = [];

        foreach ($this->views(true) as $path => $c) {
            $css = $this->pageCss($c)."\n".$core;

            foreach ($this->cardRowContainers($c) as $cls) {
                $q = preg_quote($cls, '/');
                // Only containers that do their own spacing.
                if (! preg_match('/\.'.$q.'\b[^{}]*\{[^}]*\bgap\s*:/s', $css)) {
                    continue;
                }
                if (preg_match('/\.'.$q.'\s*>\s*\.card\s*\+\s*\.card[^{}]*\{[^}]*margin-top\s*:\s*0/s', $css)) {
                    continue;
                }
                $offenders[] = "{$path}: .{$cls} spaces its cards with gap but never resets .card + .card";
            }
        }

        $this->assertSame([], $offenders, implode("\n  ", $offenders));
    }

    public function test_pages_only_use_the_documented_breakpoints(): void
    {
        // app-core.css §1.0. A page that invents its own breakpoint puts a
        // fold in the layout that exists there and nowhere else.
        $allowed = ['430px', '600px', '768px', '769px', '900px', '1200px', '1400px'];
        $offenders = [];

        foreach ($this->views(true) as $path => $c) {
            preg_match_all('/@media[^{]*\(\s*(?:min|max)-width:\s*([0-9.]+px)/', $c, $m);
            foreach (array_unique($m[1] ?? []) as $px) {
                if (! in_array($px, $allowed, true)) {
                    $offenders[] = "{$path}: {$px}";
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n  ", $offenders));
    }

    public function test_no_view_loads_a_third_party_ui_framework(): void
    {
        // The app has one visual language. A CDN stylesheet in a view is how
        // buildings/show ended up looking like a different product.
        $offenders = [];

        foreach ($this->views(true) as $path => $c) {
            if (preg_match('~<link[^>]+(bootstrap|tailwind|bulma|foundation|semantic)~i', $c)
                || preg_match('~<script[^>]+(bootstrap|jquery)~i', $c)) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders, implode("\n  ", $offenders));
    }
}
