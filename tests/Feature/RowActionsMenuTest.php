<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Row actions live behind one menu per row (app-core §4.1b).
 *
 * Every list used to carry three or four icon buttons per row — the widest
 * column on the page, repeated twenty times, competing with the data beside it.
 *
 * The risk in moving them is silent: a destructive action that loses its form
 * becomes a GET link, and a menu whose panel is left inside the table's scroll
 * container gets clipped at the cell. Both are asserted.
 */
class RowActionsMenuTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string,string> route name => the page's URL */
    private function lists(): array
    {
        return [
            'buildings'       => route('buildings.index'),
            'floors'          => route('floors.global'),
            'units'           => route('property-units.index'),
            'tenants'         => route('tenants.index'),
            'leases'          => route('lease-contracts.index'),
            'invoices'        => route('invoices.index'),
            'users'           => route('users.index'),
        ];
    }

    private function seedRecords(): void
    {
        $building = Building::create([
            'property_name' => 'Marina Bay Tower', 'property_code' => 'MBT', 'property_type' => 'Residential',
        ]);
        $floor = Floor::create(['building_id' => $building->id, 'floor_name' => 'Floor 1']);
        $unit = PropertyUnit::create([
            'property_name' => $building->property_name, 'property_code' => $building->property_code,
            'unit_name' => 'Flat 101', 'building_id' => $building->id, 'floor_id' => $floor->id,
            'rent_per_month' => 450,
        ]);
        $tenant = Tenant::create(['name' => 'Yousif Kanoo', 'tenant_type' => 'individual']);
        LeaseContract::create([
            'date' => Carbon::today()->toDateString(), 'lease_agreement_no' => 'LA-ROW-1',
            'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name,
            'property_name' => $building->property_name, 'unit_id' => $unit->id, 'unit' => $unit->unit_name,
            'lease_start_date' => Carbon::today()->subMonth()->toDateString(),
            'lease_end_date' => Carbon::today()->addYear()->toDateString(), 'rent_per_month' => 450,
        ]);
        $invoice = new Invoice([
            'invoice_number' => 'INV-ROW-1', 'tenant_id' => $tenant->id, 'tenant_name' => $tenant->name,
            'property_name' => $building->property_name, 'unit' => $unit->unit_name, 'type' => 'rent',
            'lines' => [['property_name' => $building->property_name, 'unit' => $unit->unit_name, 'amount' => 450.000]],
            'vat_rate' => 0, 'invoice_date' => Carbon::today()->toDateString(), 'status' => 'issued',
        ]);
        $invoice->recomputeTotals();
        $invoice->save();
    }

    public function test_every_list_puts_its_row_actions_behind_one_trigger(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seedRecords();

        foreach ($this->lists() as $name => $url) {
            $content = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('data-rowmenu-toggle', $content,
                "[{$name}] has no row action trigger.");
            $this->assertStringContainsString('row-menu-panel', $content,
                "[{$name}] renders a trigger with no panel.");
        }
    }

    public function test_no_list_still_draws_a_strip_of_icon_buttons_in_its_rows(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seedRecords();

        foreach ($this->lists() as $name => $url) {
            $content = $this->get($url)->getContent();
            $body = Str::after($content, '<tbody>');

            // The pattern this replaced. A page reintroducing it would look
            // right on its own and wrong beside every other list.
            $this->assertStringNotContainsString('class="action-btns"', $body,
                "[{$name}] is back to a row of icon buttons.");
        }
    }

    public function test_a_menu_trigger_says_what_it_opens(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seedRecords();

        $content = $this->get(route('floors.global'))->getContent();

        $this->assertStringContainsString('aria-haspopup="true"', $content);
        $this->assertStringContainsString('aria-expanded="false"', $content);
        // An icon-only trigger needs a name, and "Actions" repeated twenty
        // times names nothing — each carries the row it belongs to.
        $this->assertStringContainsString('aria-label="Actions for Floor 1"', $content);
    }

    public function test_the_panel_starts_closed(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seedRecords();

        $content = $this->get(route('floors.global'))->getContent();

        $this->assertMatchesRegularExpression('/<div class="row-menu-panel" id="rowmenu-\w+" hidden>/', $content,
            'A panel that renders open covers the table on load.');
    }

    /**
     * The one that matters. Moving a delete into a menu must not turn a
     * CSRF-protected POST into a link anyone can follow.
     */
    public function test_destructive_actions_keep_their_form_csrf_and_method(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seedRecords();

        foreach ($this->lists() as $name => $url) {
            $content = $this->get($url)->getContent();
            $panels = Str::between($content, 'row-menu-panel', '</tbody>');

            $this->assertStringContainsString('<form method="POST"', $panels, "[{$name}] delete is not a form.");
            $this->assertStringContainsString('name="_token"', $panels, "[{$name}] delete has no CSRF token.");
            $this->assertStringContainsString('value="DELETE"', $panels, "[{$name}] delete is not method-spoofed.");
            $this->assertStringContainsString('onsubmit="return confirm(', $panels, "[{$name}] delete asks nothing.");
        }
    }

    public function test_a_destructive_item_is_toned_and_set_apart(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seedRecords();

        $content = $this->get(route('property-units.index'))->getContent();

        $this->assertStringContainsString('row-menu-item is-danger', $content);
        // A divider above it, so it is never adjacent to a benign item.
        $this->assertStringContainsString('row-menu-sep', $content);
    }

    public function test_you_cannot_delete_the_account_you_are_signed_in_as(): void
    {
        $me = User::factory()->admin()->create(['name' => 'Signed In Admin']);
        $other = User::factory()->user()->create(['name' => 'Someone Else']);
        $this->actingAs($me);

        $content = $this->get(route('users.index'))->getContent();

        // Matched on the form action, not the bare URL: route('users.destroy',
        // $me) is a prefix of route('users.edit', $me), so the loose form of
        // this assertion passes for the wrong reason.
        $this->assertStringContainsString('action="'.route('users.destroy', $other).'"', $content);
        $this->assertStringNotContainsString('action="'.route('users.destroy', $me).'"', $content);
    }

    public function test_a_paid_invoice_offers_no_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seedRecords();
        Invoice::query()->update(['status' => 'paid']);

        $content = $this->get(route('invoices.index'))->getContent();
        $invoice = Invoice::first();

        // A settled invoice is a record, not a draft.
        $this->assertStringNotContainsString(route('invoices.edit', $invoice), $content);
        $this->assertStringContainsString(route('invoices.show', $invoice), $content);
    }

    public function test_the_actions_cell_still_stops_the_row_click(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->seedRecords();

        foreach ([route('floors.global'), route('property-units.index'), route('invoices.index')] as $url) {
            $this->assertStringContainsString('onclick="event.stopPropagation()"', $this->get($url)->getContent(),
                "Rows navigate on click, so [{$url}]'s actions cell must swallow its own clicks.");
        }
    }

    public function test_the_panel_is_positioned_outside_the_tables_scroll_container(): void
    {
        // .table-wrap has overflow-x:auto, so an absolutely positioned panel is
        // clipped at the cell. The component uses fixed and the shell's handler
        // places it — assert the contract rather than the rendering.
        $css = file_get_contents(base_path('public/css/app-core.css'));
        $panel = Str::between($css, '.row-menu-panel {', '}');

        $this->assertStringContainsString('position: fixed', $panel);
        $this->assertStringContainsString('data-rowmenu-toggle',
            file_get_contents(resource_path('views/layouts/admin.blade.php')));
    }
}
