<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * The ≤768px header contract, asserted against the Blade and CSS sources.
 *
 * The app had two mobile headers that had drifted apart: the Dashboard's
 * (greeting, big title, three round controls, a dot on the bell) and the one
 * every other screen used (hamburger, small title, four square controls, a
 * counter on the bell). Switching tabs moved the title, changed the control
 * shape and changed what "unread" looked like.
 *
 * There is one system now, in two variants — .pm-header for Home,
 * .topbar for every other tab — and each test below is the tripwire for one
 * rule of it. Plain PHPUnit on purpose, like UiConsistencyTest: it reads
 * files, needs no database, and runs in milliseconds.
 */
class MobileHeaderTest extends TestCase
{
    private const LAYOUT = __DIR__.'/../../resources/views/layouts/admin.blade.php';
    private const MOBILE_CSS = __DIR__.'/../../public/css/app-mobile.css';

    /** The ≤768px header markup only — from <header class="topbar"> to </header>. */
    private function mobileHeader(): string
    {
        $src = file_get_contents(self::LAYOUT);
        $start = strpos($src, '<header class="topbar">');
        $this->assertNotFalse($start, 'The ≤768px header is gone from the layout.');
        $end = strpos($src, '</header>', $start);

        return substr($src, $start, $end - $start);
    }

    public function test_the_header_has_no_hamburger(): void
    {
        // The bottom tab bar is the only navigation. The drawer still exists —
        // More → Full menu opens it — but nothing in a header does.
        $this->assertStringNotContainsString('menuBtn', $this->mobileHeader());
        $this->assertStringNotContainsString('fa-bars', $this->mobileHeader());
    }

    public function test_the_header_has_exactly_two_controls_in_one_order(): void
    {
        $header = $this->mobileHeader();

        $slots = [
            'id="topbarAlertsBtn"', // notifications
            'class="pm-avatar',     // avatar
        ];

        $at = -1;
        foreach ($slots as $slot) {
            $pos = strpos($header, $slot);
            $this->assertNotFalse($pos, "The header is missing its {$slot} slot.");
            $this->assertGreaterThan($at, $pos, "Header slots are out of order at {$slot}.");
            $at = $pos;
        }

        // Two, not three or four: a help "?" used to sit between the bell and
        // the avatar, and a theme toggle used to lead the row. Both live in
        // the More sheet now — the header carries what you reach for on every
        // screen, and nothing else.
        $this->assertSame(2, substr_count($header, 'class="pm-icon-btn')
            + substr_count($header, 'class="pm-avatar'));
        $this->assertStringNotContainsString('circle-question', $header);
        $this->assertStringNotContainsString('theme-toggle-btn', $header);
    }

    public function test_the_theme_toggle_lives_in_the_more_sheet(): void
    {
        // Removed from the header, so it has to be somewhere: the sheet the
        // "More" tab opens.
        $layout = file_get_contents(self::LAYOUT);
        $sheet = substr($layout, strpos($layout, 'id="moreSheet"'));
        $sheet = substr($sheet, 0, strpos($sheet, 'id="helpSheet"'));

        $this->assertStringContainsString('more-sheet-item theme-toggle-btn', $sheet);
    }

    public function test_every_header_control_is_the_same_control(): void
    {
        // One shape, one size, app-wide: .pm-icon-btn. The header must not
        // reach for the desktop shell's 36px square .topbar-icon-btn.
        $this->assertStringNotContainsString('topbar-icon-btn', $this->mobileHeader());
    }

    public function test_help_moved_into_the_more_sheet(): void
    {
        $src = file_get_contents(self::LAYOUT);

        $this->assertStringContainsString('id="moreHelpBtn"', $src);
        $this->assertStringContainsString('aria-controls="helpSheet"', $src);
        $this->assertStringContainsString('id="helpSheet"', $src);
    }

    public function test_the_bell_carries_a_counter_and_never_a_dot(): void
    {
        // One spelling of "unread" across every screen. .pm-dot is still
        // defined in CSS for a count that genuinely isn't known; no view is
        // allowed to reach for it while the count is available.
        $views = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__.'/../../resources/views')
        );

        foreach ($views as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $this->assertStringNotContainsString(
                    'class="pm-dot"',
                    file_get_contents($file->getPathname()),
                    $file->getFilename().' draws a dot on the bell instead of the count.'
                );
            }
        }

        // The span itself now lives in partials/bell-badge.blade.php — one
        // badge, rendered from $attentionCount, included by every bell. The
        // header's job is to include it and to hand it the shared count.
        $header = $this->mobileHeader();
        $this->assertStringContainsString("@include('partials.bell-badge'", $header);
        $this->assertStringContainsString("'count' => \$attentionCount", $header);
        $this->assertStringContainsString(
            'shell-bell-badge',
            file_get_contents(__DIR__.'/../../resources/views/partials/bell-badge.blade.php')
        );
    }

    public function test_no_header_paints_its_own_surface(): void
    {
        // --card-bg and --ps-surface resolve to the same white (and the same
        // dark) today, which is why one header drifting onto the other token
        // was invisible until one of them changed. The mobile layer uses
        // --ps-surface only.
        // Comments stripped first: this asserts about declarations, and the
        // rule it guards explains itself by naming the token it avoids.
        $declarations = preg_replace('#/\*.*?\*/#s', '', file_get_contents(self::MOBILE_CSS));

        $this->assertStringNotContainsString('--card-bg', $declarations);
        $this->assertStringNotContainsString('--card-border', $declarations);
    }

    public function test_the_hairline_is_one_colour_and_only_when_scrolled(): void
    {
        $css = file_get_contents(self::MOBILE_CSS);

        // Both variants start with a transparent bottom border and get
        // --ps-border from one shared .is-scrolled rule — not two hairlines
        // in two colours, which is what they had.
        // Three headers now: Home, the compact one, and the pushed-detail
        // one, which used to paint its hairline unconditionally.
        $this->assertSame(
            3,
            substr_count($css, 'border-bottom: 1px solid transparent'),
            'A mobile header is painting its hairline unconditionally again.'
        );
        $this->assertStringContainsString(
            "    .pm-header.is-scrolled,\n    .topbar.is-scrolled,\n    .pm-push-header.is-scrolled { border-bottom-color: var(--ps-border); }",
            $css
        );

        // One listener drives it, so both variants gain the hairline at the
        // same moment.
        $this->assertStringContainsString(
            "header.classList.toggle('is-scrolled', y > 0);",
            file_get_contents(self::LAYOUT)
        );
    }

    public function test_both_variants_space_their_controls_the_same(): void
    {
        $css = file_get_contents(self::MOBILE_CSS);

        // The gap between the three discs is 8px, set on the controls row of
        // each variant. .pm-header-row's own 12px is the title↔controls
        // gutter and is deliberately a different number.
        $this->assertStringContainsString('.pm-header-actions { display: flex; align-items: center; gap: 8px; }', $css);
        $this->assertStringContainsString('.topbar-actions { gap: 8px; }', $css);

        // The Home variant needs the wrapper to exist for that rule to bite.
        $this->assertStringContainsString(
            'class="pm-header-actions"',
            file_get_contents(__DIR__.'/../../resources/views/dashboard.blade.php')
        );
    }

    public function test_the_badge_can_never_be_clipped(): void
    {
        $css = file_get_contents(self::MOBILE_CSS);
        $btn = substr($css, strpos($css, '.pm-icon-btn {'));
        $btn = substr($btn, 0, strpos($btn, '}'));

        // The badge hangs outside the circle; an overflow:hidden here would
        // cut it in half.
        $this->assertStringContainsString('overflow: visible', $btn);
        $this->assertStringNotContainsString('overflow: hidden', $btn);
    }

    public function test_both_header_variants_share_one_padding(): void
    {
        // What keeps the title's left edge and the controls' right edge from
        // moving when you switch tabs. Three headers declare it: .pm-header
        // (Home), .topbar (every other tab) and the pushed-screen header.
        $css = file_get_contents(self::MOBILE_CSS);

        // One 16px grid: the header's padding is the same number as the
        // screen's side padding and the bottom bar's inset.
        $this->assertSame(
            3,
            substr_count($css, 'calc(16px + env(safe-area-inset-top)) 16px 16px'),
            'A mobile header is using its own padding again.'
        );
    }
}
