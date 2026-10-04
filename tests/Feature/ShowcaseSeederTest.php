<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\EwaBill;
use App\Models\Expense;
use App\Models\Floor;
use App\Models\Invoice;
use App\Models\InvoiceNote;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\PropertyUnit;
use App\Models\Revenue;
use App\Models\Tenant;
use App\Services\ProfitLossService;
use App\Services\VatReturnService;
use App\Support\Occupancy;
use Database\Seeders\ShowcaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The showcase dataset exists to be demonstrated, so what these tests pin is
 * coverage rather than exact figures: every status filter has rows behind it,
 * every category is represented, and each leg of the profit & loss statement
 * is non-zero. An empty filter on a demo screen reads as a broken feature.
 *
 * The seeder writes photos, so the public disk is faked — otherwise the suite
 * would leave JPEGs in storage/app/public.
 */
class ShowcaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private const NAME = 'Miknas Plaza 3';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(ShowcaseSeeder::class);
    }

    public function test_it_seeds_the_building_with_its_floors_and_units(): void
    {
        $building = Building::where('property_code', 'MP3')->first();

        $this->assertNotNull($building);
        $this->assertSame('Mixed Use', $building->property_type);
        $this->assertTrue($building->vat_enabled);
        $this->assertSame(12, Floor::where('building_id', $building->id)->count());
        $this->assertSame(46, PropertyUnit::where('building_id', $building->id)->count());
    }

    public function test_occupancy_leaves_room_to_show_both_occupied_and_vacant_units(): void
    {
        $building = Building::where('property_code', 'MP3')->firstOrFail();

        $total    = PropertyUnit::where('building_id', $building->id)->count();
        $occupied = $building->occupiedUnits()->count();
        $percent  = Occupancy::percent($occupied, $total);

        $this->assertGreaterThan(0, $total - $occupied, 'every unit is let, so no vacancy can be demonstrated');
        $this->assertGreaterThanOrEqual(70, $percent);
        $this->assertLessThanOrEqual(95, $percent);
    }

    public function test_every_unit_carries_the_pricing_and_utility_detail_the_forms_expect(): void
    {
        $units = PropertyUnit::where('property_code', 'MP3')->get();

        foreach ($units as $unit) {
            $this->assertNotNull($unit->rent_per_month, "{$unit->unit_name} has no rent");
            $this->assertNotNull($unit->area_inside, "{$unit->unit_name} has no area");
            $this->assertNotNull($unit->rate_per_area_unit, "{$unit->unit_name} has no rate");
            $this->assertNotNull($unit->electricity_meter_no, "{$unit->unit_name} has no electricity meter");
            $this->assertNotNull($unit->water_meter_no, "{$unit->unit_name} has no water meter");
            $this->assertNotNull($unit->floor_id, "{$unit->unit_name} is not on a floor");
        }
    }

    public function test_leases_cover_active_expiring_and_expired_terms(): void
    {
        $today = Carbon::today();

        $active = LeaseContract::where('property_code', 'MP3')
            ->whereDate('lease_start_date', '<=', $today)
            ->whereDate('lease_end_date', '>=', $today)
            ->count();

        $expiring = LeaseContract::where('property_code', 'MP3')
            ->whereDate('lease_end_date', '>=', $today)
            ->whereDate('lease_end_date', '<=', $today->copy()->addDays(60))
            ->count();

        $expired = LeaseContract::where('property_code', 'MP3')
            ->whereDate('lease_end_date', '<', $today)
            ->count();

        $this->assertGreaterThan(20, $active);
        $this->assertGreaterThan(0, $expiring, 'nothing in the 60-day expiry window the alert feed watches');
        $this->assertGreaterThan(0, $expired);
    }

    public function test_leases_bill_at_every_frequency_and_price_vat_by_use(): void
    {
        $leases = LeaseContract::where('property_code', 'MP3')->get();

        foreach (['Monthly', 'Quarterly', 'Annually'] as $frequency) {
            $this->assertGreaterThan(
                0,
                $leases->where('invoicing_frequency', $frequency)->count(),
                "no lease bills {$frequency}",
            );
        }

        // Commercial rent is standard-rated in Bahrain, residential is exempt.
        $this->assertGreaterThan(0, $leases->where('vat_enabled', true)->count());
        $this->assertGreaterThan(0, $leases->where('vat_enabled', false)->count());
        $this->assertGreaterThan(0, $leases->whereNotNull('ewa_cap')->count());
        $this->assertGreaterThan(0, $leases->whereNotNull('lease_break_date')->count());
    }

    public function test_invoices_exist_in_every_status_and_of_every_type(): void
    {
        $invoices = Invoice::where('property_name', self::NAME)->get();

        foreach (['draft', 'issued', 'partially_paid', 'paid', 'overdue', 'cancelled'] as $status) {
            $this->assertGreaterThan(
                0,
                $invoices->where('status', $status)->count(),
                "the invoice list has nothing to show under '{$status}'",
            );
        }

        foreach (['rent', 'utilities', 'other'] as $type) {
            $this->assertGreaterThan(0, $invoices->where('type', $type)->count(), "no '{$type}' invoices");
        }

        // VAT is charged on the commercial invoices and on nothing else.
        $this->assertGreaterThan(0, $invoices->where('vat_amount', '>', 0)->count());
    }

    public function test_paid_invoices_are_actually_settled_and_part_paid_ones_are_not(): void
    {
        $invoices = Invoice::where('property_name', self::NAME)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->get();

        foreach ($invoices as $invoice) {
            if ($invoice->status === 'paid') {
                $this->assertEqualsWithDelta(
                    0,
                    max(0, $invoice->total_incl_vat - $invoice->total_paid),
                    0.01,
                    "{$invoice->invoice_number} is marked paid but has a balance",
                );
                continue;
            }

            $this->assertGreaterThan(0, $invoice->total_paid, "{$invoice->invoice_number} is part-paid with no payment");
            $this->assertLessThan(
                $invoice->total_incl_vat,
                $invoice->total_paid,
                "{$invoice->invoice_number} is part-paid but fully covered",
            );
        }
    }

    public function test_receipts_use_every_payment_method_and_cheques_carry_their_details(): void
    {
        $payments = Payment::whereIn(
            'invoice_id',
            Invoice::where('property_name', self::NAME)->select('id'),
        )->get();

        foreach (['cash', 'bank_transfer', 'cheque', 'online_card'] as $method) {
            $this->assertGreaterThan(0, $payments->where('method', $method)->count(), "no {$method} receipts");
        }

        foreach ($payments->where('method', 'cheque') as $cheque) {
            $this->assertNotNull($cheque->cheque_number, "{$cheque->payment_number} is a cheque with no number");
            $this->assertNotNull($cheque->cheque_date, "{$cheque->payment_number} is a cheque with no date");
        }
    }

    public function test_credit_and_debit_notes_are_both_present(): void
    {
        $notes = InvoiceNote::whereIn(
            'invoice_id',
            Invoice::where('property_name', self::NAME)->select('id'),
        )->get();

        $this->assertGreaterThan(0, $notes->where('type', 'credit')->count());
        $this->assertGreaterThan(0, $notes->where('type', 'debit')->count());
    }

    public function test_ewa_bills_show_the_cap_working_in_both_directions(): void
    {
        $bills = EwaBill::where('property_name', self::NAME)->get();

        $this->assertGreaterThan(0, $bills->count());

        // Thresholds, not "at least one". These assertions passed on a
        // two-bill sample while the cap was effectively unseeded — every bill
        // but a couple belonged to an uncapped lease — so each case has to
        // carry enough rows to be visible in a list and to move the P&L.
        $capped = $bills->filter(fn (EwaBill $b) => $b->hasCap());

        $this->assertGreaterThanOrEqual(
            30,
            $capped->count(),
            'too few capped bills for the cap to be demonstrable',
        );

        // A capped bill under the cap: the landlord carries all of it and the
        // tenant is recharged nothing.
        $this->assertGreaterThanOrEqual(
            10,
            $capped->filter(fn (EwaBill $b) => $b->effective_tenant_portion <= 0)->count(),
            'too few bills fall inside their cap, so the absorbed case is not visible',
        );

        // And ones over it, where the excess is recharged.
        $this->assertGreaterThanOrEqual(
            10,
            $capped->filter(fn (EwaBill $b) => $b->effective_tenant_portion > 0)->count(),
            'too few capped bills exceed their cap, so the recharge case is not visible',
        );

        // Uncapped bills too — the tenant owes the whole reading.
        $this->assertGreaterThanOrEqual(30, $bills->filter(fn (EwaBill $b) => ! $b->hasCap())->count());

        foreach (['paid', 'partially_paid', 'issued', 'overdue'] as $status) {
            $this->assertGreaterThan(0, $bills->where('status', $status)->count(), "no '{$status}' EWA bills");
        }

        foreach (['actual', 'estimated'] as $type) {
            $this->assertGreaterThan(0, $bills->where('reading_type', $type)->count(), "no {$type} readings");
        }
    }

    /**
     * Meters are read on the 5th of the following month, so on the 1st–4th
     * the newest period has no bill yet. Every EWA status and reading type
     * must still be on show when the seeder runs in that window.
     */
    public function test_ewa_bills_cover_every_status_before_the_monthly_reading(): void
    {
        $this->travelTo(Carbon::parse('2026-10-02 09:00'));
        $this->seed(ShowcaseSeeder::class);

        $bills = EwaBill::all();

        foreach (['paid', 'partially_paid', 'issued', 'overdue'] as $status) {
            $this->assertGreaterThan(0, $bills->where('status', $status)->count(), "no '{$status}' EWA bills");
        }

        foreach (['actual', 'estimated'] as $type) {
            $this->assertGreaterThan(0, $bills->where('reading_type', $type)->count(), "no {$type} readings");
        }
    }

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';
        $tenants = Tenant::count();

        try {
            // Called directly: db:seed would stop at its own production prompt
            // first, and --force skips that prompt, which is what this guards.
            $this->app->make(ShowcaseSeeder::class)->run();
            $this->fail('the seeder ran in production');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('must not run in production', $e->getMessage());
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertSame($tenants, Tenant::count());
    }

    public function test_ewa_charges_follow_the_readings(): void
    {
        foreach (EwaBill::where('property_name', self::NAME)->get() as $bill) {
            $this->assertSame(
                (int) $bill->elec_consumption,
                (int) $bill->elec_curr_reading - (int) $bill->elec_prev_reading,
                "{$bill->bill_number}: electricity consumption does not match its readings",
            );

            $this->assertEqualsWithDelta(
                (float) $bill->total_amount,
                (float) $bill->elec_charges + (float) $bill->water_charges,
                0.001,
                "{$bill->bill_number}: total does not match its charge lines",
            );
        }
    }

    public function test_manual_expenses_and_revenue_cover_every_category(): void
    {
        $building = Building::where('property_code', 'MP3')->firstOrFail();

        $expenses = Expense::where('building_id', $building->id)->get();
        foreach (array_keys(Expense::CATEGORIES) as $category) {
            $this->assertGreaterThan(0, $expenses->where('category', $category)->count(), "no '{$category}' expenses");
        }

        $revenues = Revenue::where('building_id', $building->id)->get();
        foreach (array_keys(Revenue::CATEGORIES) as $category) {
            $this->assertGreaterThan(0, $revenues->where('category', $category)->count(), "no '{$category}' revenue");
        }

        // Some of each booked against a specific unit, for the unit-scoped filter.
        $this->assertGreaterThan(0, $expenses->whereNotNull('unit_id')->count());
        $this->assertGreaterThan(0, $revenues->whereNotNull('unit_id')->count());
    }

    public function test_maintenance_covers_the_whole_approval_flow(): void
    {
        $requests = MaintenanceRequest::where('property', self::NAME)->get();

        foreach (['waiting_supervisor', 'waiting_approval', 'approved', 'in_progress', 'completed', 'cancelled'] as $status) {
            $this->assertGreaterThan(0, $requests->where('status', $status)->count(), "no '{$status}' jobs");
        }

        $approved = $requests->whereNotNull('approved_dept_head');

        $this->assertGreaterThan(0, $approved->count());

        foreach ($approved as $request) {
            $this->assertNotNull(
                $request->selected_quotation,
                "{$request->job_order} is approved but has no quotation selected, so it costs the P&L nothing",
            );
            $this->assertNotNull($request->dept_head_signature);
            $this->assertStringStartsWith('data:image/png;base64,', $request->dept_head_signature);
        }
    }

    public function test_the_profit_and_loss_statement_has_every_leg_populated(): void
    {
        $building = Building::where('property_code', 'MP3')->firstOrFail();

        $from = Carbon::today()->startOfMonth()->subMonths(12);
        $to   = Carbon::today();

        $statement = app(ProfitLossService::class)->build($from, $to, $building->id);

        foreach (['rent_collected', 'utilities_collected', 'other_collected', 'ewa_collected', 'manual_revenue'] as $leg) {
            $this->assertGreaterThan(0, $statement['revenue'][$leg], "the P&L shows no {$leg}");
        }

        foreach (['ewa_landlord_expense', 'maintenance_expense', 'manual_expense'] as $leg) {
            $this->assertGreaterThan(0, $statement['expenses'][$leg], "the P&L shows no {$leg}");
        }

        // The landlord's absorbed EWA is the one leg that can be technically
        // non-zero and still meaningless: it comes only from bills on a capped
        // lease, and a handful of stray bills once made it read 215 BHD
        // against 43,000 of total cost. A floor keeps it demonstrable.
        $this->assertGreaterThan(
            1000,
            $statement['expenses']['ewa_landlord_expense'],
            'the absorbed EWA figure is too small to read as a real cost line',
        );

        // A demo property should read as a going concern, not a loss-maker.
        $this->assertGreaterThan(0, $statement['net_profit']);
    }

    public function test_the_vat_return_shows_both_standard_rated_and_exempt_supplies(): void
    {
        $building = Building::where('property_code', 'MP3')->firstOrFail();

        $rows = app(VatReturnService::class)->build(
            Carbon::today()->startOfMonth()->subMonths(12),
            Carbon::today(),
            $building->id,
        );

        $this->assertGreaterThan(0, $rows->where('tax_code', 'S')->count(), 'no standard-rated supplies');
        $this->assertGreaterThan(0, $rows->where('tax_code', 'EXM-S')->count(), 'no exempt supplies');
    }

    public function test_tenants_are_of_both_kinds_and_stay_identifiable(): void
    {
        $tenants = Tenant::where('tenant_code', 'like', 'MP3-T-%')->get();

        $this->assertGreaterThan(0, $tenants->where('tenant_type', 'individual')->count());
        $this->assertGreaterThan(0, $tenants->where('tenant_type', 'company')->count());

        // Demo data lands in a database that holds live records, so none of it
        // may look like a real contactable party.
        foreach ($tenants as $tenant) {
            $this->assertStringEndsWith('@example.com', (string) $tenant->email);
        }
    }

    public function test_it_writes_building_photos_to_the_public_disk(): void
    {
        $building = Building::where('property_code', 'MP3')->firstOrFail();
        $images   = $building->images;

        $this->assertCount(5, $images);

        foreach ($images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertGreaterThan(1000, Storage::disk('public')->size($image->path));
        }
    }

    /**
     * Regression: the seeder back-dates a year of invoices, so it issues
     * numbers in months another property was genuinely invoiced in. Counting
     * from 1 within the run collided with a real INV-R-0726-0001 the first
     * time this ran against staging's database.
     */
    public function test_it_issues_document_numbers_around_documents_that_already_exist(): void
    {
        // A tenant outside every prefix the seeder owns.
        $outsider = Tenant::create([
            'tenant_code' => 'Tenant-09999',
            'name'        => 'Some Other Landlord Tenant',
            'tenant_type' => 'company',
        ]);

        // Hand one seeded invoice over to that tenant and another property. Its
        // number stays issued, but the seeder now has no claim on it by tenant
        // or by property name — which is exactly the situation on a live
        // database: a number in use by a record this seeder does not own.
        $planted = Invoice::where('property_name', self::NAME)
            ->where('type', 'rent')
            ->orderBy('id')
            ->skip(5)
            ->firstOrFail();

        $planted->updateQuietly([
            'tenant_id'     => $outsider->id,
            'tenant_name'   => $outsider->name,
            'property_name' => 'Some Other Tower',
            'unit'          => 'OT - 11',
        ]);

        $this->seed(ShowcaseSeeder::class);

        $numbers = Invoice::pluck('invoice_number');

        $this->assertSame(
            $numbers->count(),
            $numbers->unique()->count(),
            'the seeder issued an invoice number that was already in use',
        );

        // The other property's document is untouched, number included.
        $this->assertDatabaseHas('invoices', [
            'id'             => $planted->id,
            'invoice_number' => $planted->invoice_number,
            'property_name'  => 'Some Other Tower',
        ]);

        // And the seeder did not simply skip the month it collided in.
        $this->assertGreaterThan(
            0,
            Invoice::where('property_name', self::NAME)
                ->where('invoice_number', 'like', substr($planted->invoice_number, 0, 12) . '%')
                ->count(),
            'the seeder issued nothing in the month whose numbering was contested',
        );
    }

    public function test_running_it_twice_replaces_the_dataset_rather_than_duplicating_it(): void
    {
        $before = [
            'buildings' => Building::where('property_code', 'MP3')->count(),
            'units'     => PropertyUnit::where('property_code', 'MP3')->count(),
            'tenants'   => Tenant::where('tenant_code', 'like', 'MP3-T-%')->count(),
            'leases'    => LeaseContract::where('property_code', 'MP3')->count(),
            'invoices'  => Invoice::where('property_name', self::NAME)->count(),
        ];

        $this->seed(ShowcaseSeeder::class);

        $this->assertSame($before['buildings'], Building::where('property_code', 'MP3')->count());
        $this->assertSame($before['units'], PropertyUnit::where('property_code', 'MP3')->count());
        $this->assertSame($before['tenants'], Tenant::where('tenant_code', 'like', 'MP3-T-%')->count());
        $this->assertSame($before['leases'], LeaseContract::where('property_code', 'MP3')->count());
        $this->assertSame($before['invoices'], Invoice::where('property_name', self::NAME)->count());

        // And nothing is orphaned behind it.
        $this->assertSame(0, Invoice::whereNull('tenant_id')->where('property_name', self::NAME)->count());
    }
}
