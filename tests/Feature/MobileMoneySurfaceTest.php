<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MoneyFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Depth, and what earns it, on the mobile dashboard.
 *
 * The Portfolio tab is a page of white cards, and the three money figures are
 * its subject — the ring above them is state, the rows below are destinations.
 * On a white page the only way to say "these three" is a change of surface, so
 * the money strip is the one navy plate on the screen and nothing else may be.
 * Gold marks the one figure that asks for something; red marks the row that
 * does the asking; and a zero is a zero, not a dash.
 */
class MobileMoneySurfaceTest extends TestCase
{
    use RefreshDatabase;

    private const CSS   = __DIR__.'/../../public/css/app-mobile.css';
    private const VIEWS = __DIR__.'/../../resources/views/';

    private function overdueInvoice(float $amount): void
    {
        $tenant = Tenant::create(['name' => 'Owing Tenant', 'tenant_type' => 'individual']);
        $invoice = new Invoice([
            'invoice_number' => 'INV-OWED-1',
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'property_name'  => 'Test Property',
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Test Property', 'amount' => $amount]],
            'vat_rate'       => 0,
            'invoice_date'   => now()->subDays(40)->format('Y-m-d'),
            'due_date'       => now()->subDays(10)->format('Y-m-d'),
            'status'         => 'overdue',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();
    }

    public function test_a_zero_is_a_figure_not_a_dash(): void
    {
        // The dash was the old behaviour and it was the wrong reading: a dash
        // is what a column prints when it has nothing to print, and a month
        // that collected nothing has a value.
        $this->assertSame('0', MoneyFormat::figure(0.0));
        $this->assertSame('0', MoneyFormat::figure(null));
        $this->assertSame('1,250', MoneyFormat::figure(1250.0));

        $this->assertTrue(MoneyFormat::isZero(0.0));
        $this->assertTrue(MoneyFormat::isZero(null));
        $this->assertFalse(MoneyFormat::isZero(1250.0));

        // The accounting dash on the reports is a different convention and
        // stays: a ledger column genuinely has no entry to print.
        $this->assertSame('—', MoneyFormat::crDr(0.0));
    }

    public function test_no_mobile_figure_renders_a_zero_as_a_dash(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['dashboard', 'invoices.index', 'payments.index', 'expenses.index',
            'revenues.index', 'ewa-bills.index'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            preg_match_all('/<div class="ps-stat-value[^"]*">\s*([^<]*)</', $html, $m);
            $this->assertNotEmpty($m[1], "{$route} draws no figures.");
            foreach ($m[1] as $figure) {
                $this->assertNotSame('—', trim($figure),
                    "{$route} still renders a zero amount as a dash.");
            }
        }
    }

    public function test_a_zero_figure_takes_the_muted_ink_of_its_surface(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        // Nothing billed and nothing collected on an empty portfolio, so the
        // first two columns are the zero case.
        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('class="ps-stat-value is-zero">0<', $html);

        $css = file_get_contents(self::CSS);
        $this->assertStringContainsString('.ps-stat-value.is-zero { color: var(--ps-muted);', $css,
            'A zero on a white strip takes --ps-muted.');
        $this->assertStringContainsString(
            '.ps-stat-strip.is-navy .ps-stat-value.is-zero { color: var(--ps-on-navy-quiet); }', $css,
            'A zero on the navy plate takes the quiet ink of the plate.');

        // --ps-faint is #97a1b4, which this file documents as decorative-only
        // because it fails AA at text sizes. A figure is not a decoration.
        $strip = substr($css, strpos($css, '.ps-stat-value.is-zero'));
        $this->assertStringNotContainsString('--ps-faint', substr($strip, 0, 400));
    }

    public function test_the_money_strip_is_the_one_navy_plate_on_the_screen(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="ps-stat-strip is-navy"'),
            'The Portfolio tab draws exactly one navy plate.');

        // The occupancy ring and the property rows stay white — two dark
        // surfaces on one screen and neither of them is emphasis any more.
        $this->assertStringContainsString('class="dashm-occ"', $html);
        $this->assertStringNotContainsString('dashm-occ is-navy', $html);

        $css = file_get_contents(self::CSS);
        $plate = substr($css, strpos($css, '.ps-stat-strip.is-navy {'));
        $plate = substr($plate, 0, strpos($plate, '}'));
        $this->assertStringContainsString('var(--ps-navy-card)', $plate);
        $this->assertStringContainsString('radial-gradient', $plate);
        $this->assertStringContainsString('var(--ps-navy-sheen)', $plate);

        // The plate reads through tokens, so the dark theme follows without a
        // second rule — and it must not be a raw hex here.
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}/', $plate,
            'The plate declares a raw colour instead of a token.');
    }

    public function test_gold_marks_what_is_owed_and_only_when_something_is(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        // Nothing owed: the figure is a quiet zero, not a gold one, and there
        // is no call to action — a zero owed is good news.
        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('ps-stat-value is-owed is-zero', $html);
        $this->assertStringNotContainsString('pm-action-row is-alert', $html);

        // Something owed: the figure goes gold and the alert row appears.
        $this->overdueInvoice(3160.0);
        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/ps-stat-value is-owed\s*">/', $html,
            'A non-zero owed figure is not marked as the one to read.');
        $this->assertStringContainsString('pm-action-row is-alert', $html);
        $this->assertStringContainsString("Chase what's owed", $html);

        $css = file_get_contents(self::CSS);
        $this->assertStringContainsString(
            '.ps-stat-strip.is-navy .ps-stat-value.is-owed { color: var(--ps-gold-on-navy); }', $css);

        // .is-zero is declared after .is-owed, so a zero takes it back at
        // equal specificity. Gold on a zero flags an alert on good news.
        $this->assertLessThan(
            strpos($css, '.ps-stat-strip.is-navy .ps-stat-value.is-zero'),
            strpos($css, '.ps-stat-strip.is-navy .ps-stat-value.is-owed'),
            'A zero owed would still render gold.');
    }

    public function test_the_alert_row_is_red_and_carries_no_gold(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->overdueInvoice(3160.0);
        $this->get(route('dashboard'))->assertOk();

        $css = file_get_contents(self::CSS);
        $alert = substr($css, strpos($css, '.pm-action-row.is-alert {'));
        $alert = substr($alert, 0, strpos($alert, '.pm-action-title'));

        $this->assertStringContainsString('var(--ps-danger-line)', $alert);
        $this->assertStringContainsString('var(--ps-danger-bg)', $alert);
        $this->assertStringContainsString('var(--ps-danger)', $alert);
        $this->assertStringNotContainsString('gold', $alert,
            'Gold is the brand accent and red is needs-action; a row may not wear both.');

        // Both tones flip with the theme because they are the same tokens the
        // status badges read, so there is no second rule for dark.
        $dark = substr($css, strpos($css, ':root[data-theme="dark"] {'));
        $dark = substr($dark, 0, strpos($dark, "\n    }"));
        $this->assertStringContainsString('--ps-danger-line:', $dark,
            'The dark theme does not redeclare the alert border.');
    }

    public function test_the_action_rows_anatomy_does_not_depend_on_the_element(): void
    {
        // Four of the five call sites write the title and sub as <div>; the
        // dashboard's chase row wrote them as <span>, so its title and its
        // amount ran together into one wrapping paragraph.
        $css = file_get_contents(self::CSS);

        $this->assertStringContainsString('.pm-action-title { display: block;', $css);
        $this->assertStringContainsString('.pm-action-sub { display: block;', $css);
    }

    public function test_the_money_columns_are_formatted_in_one_place(): void
    {
        // A page hands the strip a raw amount; the strip formats it and decides
        // whether it is a quiet zero. Neither decision is repeated per page.
        $component = file_get_contents(self::VIEWS.'components/mobile-list.blade.php');
        $this->assertStringContainsString("MoneyFormat::isZero", $component);
        $this->assertStringContainsString("MoneyFormat::figure", $component);

        foreach (['invoices/index', 'payments/index', 'expenses/index', 'revenues/index'] as $view) {
            $c = file_get_contents(self::VIEWS.$view.'.blade.php');
            $this->assertStringNotContainsString('MoneyFormat::figure', $c,
                "{$view} formats its own money instead of passing 'money' => \$amount.");
            $this->assertStringContainsString("'money' =>", $c);
        }
    }
}
