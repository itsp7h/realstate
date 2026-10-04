<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One order, one rhythm, on every mobile list screen.
 *
 * Eleven screens each wrote the same five sections out by hand, and one had
 * drifted: Buildings led with the search pill, so its Add button sat below the
 * search where every other screen put it above — and its two filters (type,
 * ownership) had no mobile path at all, because the chips row was simply
 * missing. The order is components/mobile-list's job now, the 12px between the
 * sections is app-mobile.css's, and neither is a page's.
 */
class MobileListOrderTest extends TestCase
{
    use RefreshDatabase;

    private const CSS       = __DIR__.'/../../public/css/app-mobile.css';
    private const COMPONENT = __DIR__.'/../../resources/views/components/mobile-list.blade.php';
    private const VIEWS     = __DIR__.'/../../resources/views/';

    /** header → actions → stats → search → chips → rows. */
    private const ORDER = [
        'actions' => 'class="m-action-row',
        'stats'   => 'class="ps-stat-strip',
        'search'  => 'class="m-search ',
        'chips'   => 'class="m-chip-row',
    ];

    /**
     * Every list screen, and the sections it is expected to draw. A screen may
     * omit one — Payments has no create verb, Maintenance has no figures to
     * strip, Floors has no search term to type — but what it does draw comes
     * in the order above, and the omission closes up rather than leaving a
     * hole.
     */
    private const SCREENS = [
        'buildings.index'       => ['actions', 'stats', 'search', 'chips'],
        'property-units.index'  => ['actions', 'stats', 'search', 'chips'],
        'tenants.index'         => ['actions', 'search', 'chips'],
        'lease-contracts.index' => ['actions', 'stats', 'search', 'chips'],
        'invoices.index'        => ['actions', 'stats', 'search', 'chips'],
        'payments.index'        => ['stats', 'search', 'chips'],
        'expenses.index'        => ['actions', 'stats', 'search', 'chips'],
        'maintenance.index'     => ['actions', 'search', 'chips'],
        'ewa-bills.index'       => ['actions', 'stats', 'search', 'chips'],
        'revenues.index'        => ['actions', 'stats', 'search', 'chips'],
        'floors.global'         => ['actions', 'stats', 'chips'],
    ];

    /** The list views that must be built from the component, not by hand. */
    private const LIST_VIEWS = [
        'buildings/index.blade.php', 'property-units/index.blade.php',
        'tenants/index.blade.php', 'lease-contracts/index.blade.php',
        'invoices/index.blade.php', 'payments/index.blade.php',
        'expenses/index.blade.php', 'maintenance/index.blade.php',
        'ewa-bills/index.blade.php', 'revenues/index.blade.php',
        'floors/global-index.blade.php',
    ];

    /** The chrome above the rows, as rendered. */
    private function chrome(string $route): string
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        $screen = substr($html, strpos($html, 'class="m-screen"'));
        $rows = strpos($screen, 'class="m-row-list"');
        $this->assertNotFalse($rows, "{$route} draws no row list.");

        return substr($screen, 0, $rows);
    }

    public function test_every_list_screen_draws_its_sections_in_one_order(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (self::SCREENS as $route => $expected) {
            $chrome = $this->chrome($route);

            $found = [];
            foreach (self::ORDER as $section => $marker) {
                $at = strpos($chrome, $marker);
                if ($at !== false) {
                    $found[$section] = $at;
                }
            }

            // Present: a screen that has filters draws the chips row. This is
            // the half Buildings failed — its two selects lived on the desktop
            // filter bar and nothing replaced them on the phone.
            $this->assertSame($expected, array_keys($found),
                "{$route} is missing a section. Expected ".implode(', ', $expected)
                .'; drew '.implode(', ', array_keys($found)).'.');

            // In order: sorting by where each one actually appears must not
            // move anything. This is the half Buildings failed the other way —
            // the search pill rendered above the actions row.
            $document = $found;
            asort($document);
            $this->assertSame($expected, array_keys($document),
                "{$route} draws its sections out of order: ".implode(' → ', array_keys($document)).'.');
        }
    }

    public function test_the_reveal_queue_closes_up_when_a_section_is_absent(): void
    {
        // The steps are the animation's queue: each section takes the next one
        // and the rows take the one after. A screen with no stat strip used to
        // hard-code the numbers around the hole it left, which is how a page
        // ended up animating its rows 40ms late.
        $this->actingAs(User::factory()->admin()->create());

        foreach (self::SCREENS as $route => $expected) {
            $chrome = $this->chrome($route);

            $step = 0;
            foreach ($expected as $section) {
                $this->assertStringContainsString('--ps-step:'.$step, $chrome,
                    "{$route}: {$section} is not step {$step} in the reveal queue.");
                $step++;
            }

            $html = $this->get(route($route))->getContent();
            $this->assertStringContainsString('--ps-row-step:'.$step, $html,
                "{$route}: the rows do not pick up the queue at step {$step}.");
        }
    }

    public function test_no_list_screen_writes_the_sections_itself(): void
    {
        $offenders = [];
        foreach (self::LIST_VIEWS as $view) {
            $c = file_get_contents(self::VIEWS.$view);
            $c = preg_replace('/\{\{--.*?--\}\}/s', '', $c);

            if (! str_contains($c, '<x-mobile-list')) {
                $offenders[] = "{$view}: does not use <x-mobile-list>";
            }
            foreach (['class="m-screen"', 'class="m-search', 'class="m-chip-row',
                'class="ps-stat-strip', "partials.mobile-actions", 'class="m-row-list'] as $hand) {
                if (str_contains($c, $hand)) {
                    $offenders[] = "{$view}: writes {$hand} itself";
                }
            }
        }

        $this->assertSame([], $offenders,
            "The section order is the component's job, not a page's:\n  ".implode("\n  ", $offenders));
    }

    public function test_one_gap_between_the_sections_everywhere(): void
    {
        $css = file_get_contents(self::CSS);

        $screen = substr($css, strpos($css, '.m-screen {'));
        $screen = substr($screen, 0, strpos($screen, '}'));

        $this->assertStringContainsString('gap: 12px', $screen,
            'The sections of a mobile screen sit 12px apart.');

        // And no page re-declares it, which is how two screens end up 2px
        // out of step with each other.
        foreach (self::LIST_VIEWS as $view) {
            $c = file_get_contents(self::VIEWS.$view);
            $this->assertDoesNotMatchRegularExpression('/\.m-screen\s*\{/', $c,
                "{$view} re-declares .m-screen.");
        }
    }

    public function test_the_buildings_chips_filter_by_type_and_by_ownership(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $chrome = $this->chrome('buildings.index');

        foreach (['Residential', 'Commercial', 'Mixed Use', 'Industrial', 'Retail'] as $type) {
            $this->assertStringContainsString(e(route('buildings.index', ['property_type' => $type])), $chrome,
                "Buildings has no chip for the {$type} type.");
            $this->assertStringContainsString(">\n                        {$type}\n", $chrome,
                "The {$type} chip carries no label.");
        }
        foreach (['Owned', 'Leased', 'Joint Venture', 'Managed'] as $own) {
            $this->assertStringContainsString(e(route('buildings.index', ['type_of_ownership' => $own])), $chrome,
                "Buildings has no chip for {$own} ownership.");
        }
        $this->assertStringContainsString('aria-current="true"', $chrome,
            'With nothing filtered, the All chip is not marked as the current one.');

        // The two facets are separate questions, and the row says so.
        $this->assertStringContainsString('class="m-chip-sep"', $chrome);
    }

    public function test_a_buildings_chip_keeps_the_other_facet_and_clears_itself(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $html = $this->get(route('buildings.index', [
            'search' => 'tower', 'property_type' => 'Residential', 'type_of_ownership' => 'Leased',
        ]))->assertOk()->getContent();

        // Commercial swaps the type and leaves the ownership and the search be.
        $this->assertStringContainsString(
            e(route('buildings.index', ['search' => 'tower', 'property_type' => 'Commercial', 'type_of_ownership' => 'Leased'])),
            $html, 'A type chip drops the ownership filter or the search.');

        // Residential is the active one, so tapping it again clears the type.
        $this->assertStringContainsString(
            e(route('buildings.index', ['search' => 'tower', 'type_of_ownership' => 'Leased'])),
            $html, 'The active type chip does not clear itself.');

        // All drops both facets but keeps what was typed.
        $this->assertStringContainsString(
            e(route('buildings.index', ['search' => 'tower'])).'"',
            $html, 'The All chip drops the search along with the filters.');

        // And typing again does not throw the chips away.
        $this->assertStringContainsString('<input type="hidden" name="property_type" value="Residential">', $html);
        $this->assertStringContainsString('<input type="hidden" name="type_of_ownership" value="Leased">', $html);
    }

    public function test_the_component_owns_the_order_in_one_place(): void
    {
        $c = file_get_contents(self::COMPONENT);

        $at = [];
        foreach (['partials.mobile-actions', 'ps-stat-strip', 'class="m-search', 'class="m-chip-row', 'class="m-row-list'] as $marker) {
            $at[$marker] = strpos($c, $marker);
            $this->assertNotFalse($at[$marker], "The component does not draw {$marker}.");
        }

        $sorted = $at;
        asort($sorted);
        $this->assertSame(array_keys($at), array_keys($sorted),
            'The component itself draws the sections out of order.');
    }
}
