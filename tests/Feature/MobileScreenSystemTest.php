<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One mobile system, on every screen.
 *
 * The ≤768px app had grown a variant of each shared component per page: three
 * list rows (.m-row-card, .bldm-row, .dashm-prop), four ways to show figures
 * (the navy .ps-stat-strip, its .is-quiet twin, .m-mini-stat, .dashm-money),
 * two search fields, two chip classes, five copies of the FAB and four
 * hand-written bottom sheets. This pins the consolidation: the components live
 * in app-mobile.css and the layout, and no screen re-declares one.
 */
class MobileScreenSystemTest extends TestCase
{
    use RefreshDatabase;

    private const CSS    = __DIR__.'/../../public/css/app-mobile.css';
    private const LAYOUT = __DIR__.'/../../resources/views/layouts/admin.blade.php';
    private const VIEWS  = __DIR__.'/../../resources/views';

    /** The routes that draw a mobile app screen — $mobileRedesignedRoutes. */
    private const SCREENS = [
        'buildings.index', 'floors.global', 'property-units.index', 'tenants.index',
        'maintenance.index', 'invoices.index', 'reports.index',
        'lease-contracts.index', 'payments.index',
        'ewa-bills.index', 'expenses.index', 'revenues.index',
    ];

    /** Every Blade file that draws part of the mobile app. */
    private function mobileViews(): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::VIEWS));
        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $c = file_get_contents($file->getPathname());
            if (preg_match('/class="(m-screen|m-dash)|ps-stat-strip|m-row-card|pm-header/', $c)) {
                $out[str_replace(self::VIEWS.'/', '', $file->getPathname())] = $c;
            }
        }

        return $out;
    }

    public function test_every_mobile_screen_renders_on_the_shared_components(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (self::SCREENS as $name) {
            $html = $this->get(route($name))->assertOk()->getContent();

            $this->assertStringContainsString('class="m-screen"', $html,
                "{$name} does not draw a mobile screen.");
            $this->assertStringContainsString('class="ps-bottom-bar"', $html,
                "{$name} is missing the layout's bottom bar.");
        }
    }

    public function test_no_screen_declares_its_own_copy_of_a_shared_component(): void
    {
        // Left column: the per-page class that was deleted. Right column: what
        // it became.
        $retired = [
            'm-mini-stat'      => '.ps-stat-strip',
            'm-mini-row'       => '.ps-stat-strip',
            'dashm-money'      => '.ps-stat-strip',
            'pm-kpi-card'      => '.ps-stat-strip',
            'ps-stat-strip is-quiet' => '.ps-stat-strip',
            'bldm-row'         => '.m-row-card',
            'dashm-prop'       => '.m-row-card',
            'pm-occ-row'       => '.m-row-card',
            'm-row-icon'       => '.m-row-thumb',
            'pm-search-field'  => '.m-search',
            'm-search-input'   => '.m-search',
            'pm-chip'          => '.m-chip',
            'bldm-primary'     => '.m-action-btn.primary',
            'bldm-more'        => '.m-action-btn.more',
            'pm-ripple'        => 'the shared 0.98 press scale',
            'pm-bottom-space'  => "the layout's own bottom clearance",
        ];

        $offenders = [];
        foreach ($this->mobileViews() as $path => $c) {
            // Comments may name a retired class to explain what replaced it.
            $c = preg_replace('/\{\{--.*?--\}\}/s', '', $c);
            foreach ($retired as $class => $replacement) {
                if (str_contains($c, $class)) {
                    $offenders[] = "{$path}: {$class} — use {$replacement}";
                }
            }
        }

        $this->assertSame([], $offenders,
            "A screen is drawing its own variant of a shared component:\n  ".implode("\n  ", $offenders));
    }

    public function test_a_screen_with_a_primary_action_has_no_fab(): void
    {
        // Two "+" buttons on one screen is the duplicate-add-button the
        // consolidation removed: a list screen puts its create verb in the
        // actions row, and only a screen without one gets the floating button.
        $offenders = [];
        foreach ($this->mobileViews() as $path => $c) {
            $hasPrimary = str_contains($c, "'primary' =>") || str_contains($c, 'm-action-btn primary');
            $hasFab = str_contains($c, "@section('mobile-fab')");
            if ($hasPrimary && $hasFab) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders,
            "These screens offer both a primary create button and a FAB:\n  ".implode("\n  ", $offenders));
    }

    public function test_only_the_layout_draws_the_bottom_bar(): void
    {
        $layout = file_get_contents(self::LAYOUT);

        $this->assertStringContainsString('<div class="ps-bottom-bar">', $layout);
        $this->assertStringContainsString('@hasSection(\'mobile-fab\')', $layout);

        foreach ($this->mobileViews() as $path => $c) {
            $this->assertStringNotContainsString('bottom-tabbar', $c,
                "{$path} ships its own tab bar.");
            // A page may define the FAB as a section for the layout to place;
            // it may not position it itself.
            $this->assertStringNotContainsString('class="pm-fab" style=', $c,
                "{$path} is positioning the FAB itself.");
        }
    }

    public function test_the_bottom_bar_is_one_row_of_pill_then_fab(): void
    {
        $css = file_get_contents(self::CSS);

        // The FAB is a static child of the fixed row, so it cannot overlap a
        // tab and the pill needs no :has() trick to make room for one.
        $this->assertStringNotContainsString('body:has(.pm-fab)', $css);
        $this->assertStringContainsString('.ps-bottom-bar {', $css);

        $fab = substr($css, strpos($css, '.pm-fab {'));
        $fab = substr($fab, 0, strpos($fab, '}'));
        $this->assertStringNotContainsString('position: fixed', $fab);

        // One bottom clearance, in the layer, not per page.
        $this->assertStringContainsString('padding-bottom: calc(96px + env(safe-area-inset-bottom))', $css);
    }

    public function test_one_figure_component_and_it_carries_one_ink(): void
    {
        $css = file_get_contents(self::CSS);

        // White card, equal columns divided by a rule, figure over label.
        $this->assertStringContainsString('.ps-stat + .ps-stat { border-left: 1px solid var(--ps-divider); }', $css);

        // No tone modifiers on a figure, and no gold top border anywhere: a
        // figure is a figure, and the strip is not a status board.
        $this->assertStringNotContainsString('.ps-stat-value.is-gold', $css);
        $this->assertStringNotContainsString('.ps-stat-strip.is-quiet', $css);
        $this->assertStringNotContainsString('border-top: 3px solid var(--ps-gold)', $css);
    }

    public function test_a_row_carries_exactly_one_right_hand_slot(): void
    {
        // The slot took an occupancy bar, a badge and an amount stacked on
        // four screens. Each row may open at most one of the three.
        $offenders = [];
        foreach ($this->mobileViews() as $path => $c) {
            foreach (explode('m-row-card', $c) as $i => $chunk) {
                if ($i === 0) {
                    continue;
                }
                $row = substr($chunk, 0, strpos($chunk.'</a>', '</a>'));
                $slots = (int) str_contains($row, 'm-row-occ')
                       + (int) str_contains($row, 'm-row-amount')
                       + (int) (str_contains($row, 'status-badge') || str_contains($row, 'm-row-badge'));
                if ($slots > 1) {
                    $offenders[] = $path;
                }
            }
        }

        $this->assertSame([], array_unique($offenders),
            "These rows stack more than one figure in the slot:\n  ".implode("\n  ", array_unique($offenders)));
    }

    public function test_the_mobile_app_has_one_accent(): void
    {
        // Navy, gold, alert red and greys. A green, blue or purple wash on a
        // sheet row or a row thumbnail made a list of destinations read as a
        // status board. Semantic tones still reach status *badges*, which is
        // what they are for.
        $offenders = [];
        foreach ($this->mobileViews() + ['layouts/admin.blade.php' => file_get_contents(self::LAYOUT)] as $path => $c) {
            if (preg_match_all('/(more-sheet-icon|m-row-thumb|m-action-btn)[^>]*style="[^"]*(--m-green|--m-blue|--m-purple|--tone-success|--tone-info)/', $c, $m)) {
                $offenders[] = $path.': '.implode(', ', array_unique($m[2]));
            }
        }

        $this->assertSame([], $offenders,
            "Mobile chrome is painting itself a fourth hue:\n  ".implode("\n  ", $offenders));
    }

    public function test_no_emoji_anywhere_in_the_mobile_app(): void
    {
        $offenders = [];
        foreach ($this->mobileViews() as $path => $c) {
            if (preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $c)) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders,
            "Emoji are not part of the type system:\n  ".implode("\n  ", $offenders));
    }

    public function test_motion_is_transform_and_opacity_only(): void
    {
        $css = file_get_contents(self::CSS);

        // The reveal and the bar draw are the two animations every screen
        // runs; a width keyframe would relayout every row it touches.
        $reveal = substr($css, strpos($css, '@keyframes ps-in'));
        $this->assertStringNotContainsString('width', substr($reveal, 0, 160));

        $bar = substr($css, strpos($css, '@keyframes ps-bar'));
        $bar = substr($bar, 0, strpos($bar, '}') + 40);
        $this->assertStringContainsString('scaleX', $bar);
        $this->assertStringNotContainsString('width: 0', $bar);

        // And all of it goes away when the setting says so.
        $this->assertStringContainsString('.ps-reveal, .m-row-bar-fill { animation: none; }', $css);
    }

    public function test_one_sheet_component_and_one_handler(): void
    {
        $layout = file_get_contents(self::LAYOUT);

        // Open, aria-expanded, scroll lock, focus the first row, hand focus
        // back — once, in the layout, for every sheet.
        $this->assertStringContainsString("[data-sheet-open]", $layout);
        $this->assertStringContainsString('[data-sheet-close]', $layout);

        $offenders = [];
        foreach ($this->mobileViews() as $path => $c) {
            if (str_contains($c, 'data-bldm-close') || preg_match('/const sheet = document\.getElementById/', $c)) {
                $offenders[] = $path;
            }
        }

        $this->assertSame([], $offenders,
            "These screens carry their own sheet plumbing:\n  ".implode("\n  ", $offenders));
    }

    public function test_occupancy_is_computed_in_one_helper(): void
    {
        $offenders = [];
        $base = dirname(__DIR__, 2);
        foreach (['app', 'resources/views'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base.'/'.$dir));
            foreach ($it as $file) {
                if (! $file->isFile() || ! preg_match('/\.php$/', $file->getFilename())) {
                    continue;
                }
                if (str_ends_with($file->getPathname(), 'app/Support/Occupancy.php')) {
                    continue;
                }
                $c = file_get_contents($file->getPathname());
                // The shape the five call sites all had: divide the occupied
                // count by the total and multiply by a hundred.
                if (preg_match('/occupied\w*\s*\/\s*\$?\w*(units|Units|total|Total)\w*\s*\*\s*100/', $c)) {
                    $offenders[] = str_replace($base.'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $offenders,
            "These files work out occupancy themselves instead of using App\\Support\\Occupancy:\n  "
            .implode("\n  ", $offenders));
    }
}
