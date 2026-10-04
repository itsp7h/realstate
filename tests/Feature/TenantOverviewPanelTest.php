<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What an empty field says.
 *
 * The tenant overview was seven cards in a grid, five of them reading "Not
 * provided" in italic grey beside a tinted icon tile — a whole screen spent
 * announcing absence, in three accent colours that encoded nothing, on the
 * common case of a tenant with a name and nothing else.
 *
 * "Not provided" tells a reader something they can do nothing with. "Add ›"
 * is the same fact and a way to fix it, and it takes one line instead of a
 * card. That is the whole change: five dead rows became five affordances.
 */
class TenantOverviewPanelTest extends TestCase
{
    use RefreshDatabase;

    private const VIEWS = __DIR__.'/../../resources/views/';
    private const CORE  = __DIR__.'/../../public/css/app-core.css';

    private function tenant(array $fields = []): Tenant
    {
        return Tenant::create(array_merge([
            'name'        => 'Test Tenant',
            'tenant_type' => 'individual',
        ], $fields));
    }

    private function overview(Tenant $tenant): string
    {
        $html = $this->get(route('tenants.show', $tenant))->assertOk()->getContent();
        $at = strpos($html, 'id="panel-overview"');
        $this->assertNotFalse($at, 'The tenant page has no overview panel.');
        $end = strpos($html, 'id="panel-leases"', $at);

        return substr($html, $at, $end - $at);
    }

    public function test_an_empty_field_offers_to_be_filled_instead_of_reporting_itself(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $tenant = $this->tenant();

        $panel = $this->overview($tenant);

        $this->assertStringNotContainsString('Not provided', $panel,
            'An empty field still reports its own emptiness.');

        // One invitation per empty field, each landing on the field it came
        // from — a reader who tapped Add next to Phone has already said which
        // field they mean.
        foreach (['phone', 'email', 'id_cr_number', 'nationality_country'] as $field) {
            $this->assertStringContainsString(
                e(route('tenants.edit', [$tenant, 'focus' => $field])), $panel,
                "The empty {$field} row offers no way to fill it.");
        }
        $this->assertSame(4, substr_count($panel, 'class="kv-add"'));

        // And the row names the field for a reader who cannot see the row.
        $this->assertStringContainsString('<span class="sr-only">phone</span>', $panel);
    }

    public function test_a_filled_field_shows_its_value_and_no_invitation(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $tenant = $this->tenant([
            'phone'               => '17500768',
            'email'               => 'someone@example.com',
            'id_cr_number'        => '00040405',
            'nationality_country' => 'Bahraini',
            'address'             => 'Manama',
        ]);

        $panel = $this->overview($tenant);

        $this->assertStringContainsString('href="tel:17500768"', $panel);
        $this->assertStringContainsString('href="mailto:someone@example.com"', $panel);
        $this->assertStringContainsString('00040405', $panel);
        $this->assertStringContainsString('Bahraini · Manama', $panel);

        $this->assertStringNotContainsString('kv-add', $panel,
            'A complete record still offers to be completed.');
    }

    public function test_a_half_filled_row_shows_what_is_there_and_offers_the_rest(): void
    {
        // Nationality and address share one row, so the row has to carry both
        // halves: a tenant with a nationality and no address must not read as
        // though the row were done.
        $this->actingAs(User::factory()->admin()->create());
        $tenant = $this->tenant(['nationality_country' => 'Bahraini']);

        $panel = $this->overview($tenant);

        $this->assertStringContainsString('Bahraini', $panel);
        $this->assertStringContainsString(
            e(route('tenants.edit', [$tenant, 'focus' => 'address'])), $panel,
            'The row is missing its address and does not say so.');

        // The other way round names the other field.
        $other = $this->tenant(['address' => 'Manama']);
        $this->assertStringContainsString(
            e(route('tenants.edit', [$other, 'focus' => 'nationality_country'])),
            $this->overview($other));
    }

    public function test_the_record_is_two_cards_not_seven(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $panel = $this->overview($this->tenant());

        $this->assertSame(2, substr_count($panel, 'class="kv-card"'));
        $this->assertSame(4, substr_count($panel, 'class="kv-row"'));

        // The grid of icon-tiled cards is gone, and so are the three accent
        // washes it painted on them — an icon is not a status.
        $this->assertStringNotContainsString('detail-grid', $panel);
        $this->assertStringNotContainsString('detail-icon', $panel);
        foreach (['--tone-info-bg', '--tone-success-bg', '--tone-warning-bg'] as $tint) {
            $this->assertStringNotContainsString($tint, $panel,
                'A field still carries a tinted icon tile.');
        }
    }

    public function test_the_provenance_footer_is_dates_not_timestamps(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $tenant = $this->tenant();

        $panel = $this->overview($tenant);

        $this->assertStringContainsString('class="kv-meta"', $panel);
        $this->assertStringContainsString($tenant->created_at->format('d M Y'), $panel);
        // The minute a record was created is not something anyone acts on.
        $this->assertStringNotContainsString($tenant->created_at->format('d M Y, H:i'), $panel);
    }

    public function test_the_edit_form_opens_on_the_field_the_reader_tapped(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $tenant = $this->tenant();

        $html = $this->get(route('tenants.edit', [$tenant, 'focus' => 'email']))
            ->assertOk()->getContent();

        $this->assertStringContainsString("new URLSearchParams(location.search).get('focus')", $html);
        $this->assertStringContainsString('field.focus({preventScroll: true})', $html);

        // A focus value that names nothing on this form is ignored, not thrown.
        $this->get(route('tenants.edit', [$tenant, 'focus' => 'nope']))->assertOk();
    }

    public function test_the_card_is_a_shared_component_with_no_page_level_copy(): void
    {
        // It lives in app-core §4.3c beside the .detail-grid it is an
        // alternative to, so a second detail page is one include away.
        $core = file_get_contents(self::CORE);
        $this->assertStringContainsString('§4.3c  Key/value card', $core);
        $this->assertStringContainsString('.kv-card {', $core);
        $this->assertFileExists(self::VIEWS.'components/kv-card.blade.php');

        // No page redeclares it, and the dead fact-grid CSS the panel used to
        // need is gone rather than left behind for the next reader to puzzle
        // over.
        $profile = file_get_contents(self::VIEWS.'tenants/_profile.blade.php');
        $css = substr($profile, 0, strpos($profile, '</style>'));
        foreach (['.kv-card', '.kv-row', '.detail-grid', '.detail-icon', '.tp-wide'] as $gone) {
            $this->assertStringNotContainsString($gone.' ', $css, "{$gone} is still declared in the page.");
            $this->assertStringNotContainsString($gone.' {', $css);
        }
    }

    public function test_the_two_vocabularies_stay_two(): void
    {
        // §4.3b and §4.3c are alternatives, and the only thing stopping a
        // third from appearing is the comment saying which to reach for. That
        // comment is load-bearing, so it is asserted rather than trusted.
        $core = file_get_contents(self::CORE);

        $this->assertStringContainsString('Use .detail-grid when most fields are filled', $core);
        $this->assertStringContainsString('Use this when the record is sparse', $core);

        // And no detail page invents a third. Scoped to detail pages on
        // purpose: a label-over-figure pair inside a bespoke card is a caption,
        // not a vocabulary — the dashboard has three of those and they are
        // right. What this catches is a *record's fields* being laid out by a
        // page that could have used app-core's.
        //
        // property-units/show is the one that already exists: a .field-label /
        // .field-value copy of .detail-* carrying a 22-field spec sheet. It is
        // listed here rather than hidden, so the count can only go down — a new
        // spelling on any detail page fails, and retiring this one means
        // deleting a line from this array.
        $known = ['property-units/show.blade.php' => 'field'];

        $pages = array_merge(
            glob(self::VIEWS.'*/show.blade.php') ?: [],
            [self::VIEWS.'tenants/_profile.blade.php'],
        );
        $this->assertGreaterThan(4, count($pages), 'The detail pages moved; this test is looking in the wrong place.');

        $offenders = [];
        foreach ($pages as $path) {
            $rel = str_replace(self::VIEWS, '', $path);
            preg_match_all("/@push\\(['\\\"]styles['\\\"]\\)(.*?)@endpush/s", file_get_contents($path), $m);
            $css = implode("\n", $m[1] ?? []);

            // A page declaring both halves of a pair is declaring a component.
            preg_match_all('/\\.([a-z][a-z0-9]*(?:-[a-z0-9]+)*)-label\\s*[,{]/', $css, $labels);
            foreach (array_unique($labels[1] ?? []) as $stem) {
                if (! preg_match('/\\.'.preg_quote($stem, '/').'-value\\s*[,{.:]/', $css)) {
                    continue;   // a lone label is a label, not a vocabulary
                }
                if (($known[$rel] ?? null) === $stem) {
                    continue;
                }
                $offenders[] = "{$rel}: .{$stem}-label / .{$stem}-value";
            }
        }

        $this->assertSame([], $offenders,
            "A detail page is declaring its own label/value component. app-core has two "
            ."and they cover both cases — §4.3b .detail-grid for a record whose fields "
            ."are mostly filled, §4.3c .kv-card for a sparse one:\n  ".implode("\n  ", $offenders));
    }

    public function test_a_dense_spec_sheet_still_uses_the_grid(): void
    {
        // The two are not interchangeable: .detail-grid is for a record whose
        // fields are mostly filled and whose values each deserve a block (a
        // lease's terms, a unit's 22-field spec). .kv-card is for a sparse
        // record, or one where noticing what is missing is the reader's job.
        // Converting a full spec sheet to one-row-per-field would be 22 lines
        // of label-then-value where a grid of values reads at a glance.
        $lease = file_get_contents(self::VIEWS.'lease-contracts/show.blade.php');
        $this->assertStringContainsString('detail-grid', $lease);
        $this->assertStringNotContainsString('Not provided', $lease);

        $unit = file_get_contents(self::VIEWS.'property-units/show.blade.php');
        $this->assertStringNotContainsString('Not provided', $unit);
    }
}
