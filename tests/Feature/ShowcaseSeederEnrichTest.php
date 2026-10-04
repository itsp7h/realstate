<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingImage;
use App\Models\EwaBill;
use App\Models\Expense;
use App\Models\Floor;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\PropertyUnit;
use App\Models\Revenue;
use App\Models\Tenant;
use App\Services\ProfitLossService;
use App\Support\Occupancy;
use Database\Seeders\ShowcaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The ENRICH half of the seeder, which is the dangerous half.
 *
 * Miknas Plaza 1 and 2 are real records: 63 units between them with no rent,
 * no area and no meters on any of them, a handful of genuine leases and
 * invoices, and image rows pointing at 240×160 placeholders. The seeder has to
 * fill the gaps and let the empty units without touching any of that.
 *
 * So this fixture reproduces the shape of the live data — including its
 * defects, like the "3 BHK" unit type that the unit form will not accept — and
 * every test here is really one question: did the seeder add what was missing
 * without damaging what was already there?
 */
class ShowcaseSeederEnrichTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;

    /** The pre-existing records that must survive, whatever else happens. */
    private Tenant $sittingTenant;
    private LeaseContract $sittingLease;
    private Invoice $existingInvoice;
    private PropertyUnit $occupiedUnit;
    private BuildingImage $realPhoto;
    private BuildingImage $placeholderPhoto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->buildLiveShapedFixture();

        $this->seed(ShowcaseSeeder::class);
    }

    /**
     * Reproduces Miknas Plaza 1 as it actually stands: address partly filled,
     * no unit totals, 25 units carrying only a type, a condition, a view and a
     * deposit, three of them not attached to any floor, and one rooftop unit
     * already let.
     */
    private function buildLiveShapedFixture(): void
    {
        $this->building = Building::create([
            'property_name'     => 'Miknas Plaza 1',
            'property_code'     => 'MP1',
            'type_of_ownership' => 'Owned',
            'property_type'     => 'Residential',
            'land_lord_name'    => 'Akram Miknas',
            'building_no'       => 457,
            'road'              => 'Sh. Abdulla Bin Khalid Al Khalifa Avenue',
            'city'              => 'Manama',
            // block, area and the three totals are null on the live record.
        ]);

        $floors = [];
        for ($f = 1; $f <= 7; $f++) {
            $floors[$f] = Floor::create([
                'building_id' => $this->building->id,
                'floor_name'  => "Floor {$f}",
                'floor_code'  => "FL{$f}",
                'block_name'  => 'Block 1',
                'block_code'  => 'BL1',
            ]);
        }

        $names = [
            '11', '12', '21', '22', '23', '24', '31', '32', '33', '34',
            '41', '42', '43', '44', '51', '52', '53', '54', '61', '62', '63', '64',
        ];

        foreach ($names as $i => $suffix) {
            PropertyUnit::create([
                'property_name'           => 'Miknas Plaza 1',
                'property_code'           => 'MP1',
                'building_id'             => $this->building->id,
                'floor_id'                => $floors[(int) $suffix[0]]->id,
                'unit_name'               => "MP1 - {$suffix}",
                // Exactly as stored on the live record: not a value the unit
                // form's `in:` rule accepts.
                'unit_type'               => '3 BHK',
                'unit_condition'          => 'Furnished',
                'view'                    => 'City View',
                'security_deposit_amount' => 500,
                'creation_date'           => '2016-01-01',
                // rent, areas, rate, meters, municipality no, address: all null.
            ]);
        }

        // The three units with no floor_id, as on the live record.
        foreach (['S701', 'S702', 'S703'] as $suffix) {
            PropertyUnit::create([
                'property_name'  => 'Miknas Plaza 1',
                'property_code'  => 'MP1',
                'building_id'    => $this->building->id,
                'unit_name'      => "MP1 - {$suffix}",
                'unit_type'      => '3 BHK',
                'unit_condition' => 'Furnished',
                'view'           => 'City View',
            ]);
        }

        // A sitting tenant on a real lease, with a real invoice against it.
        $this->occupiedUnit = PropertyUnit::where('unit_name', 'MP1 - 11')->firstOrFail();

        $this->sittingTenant = Tenant::create([
            'tenant_code'  => 'Tenant-00063',
            'name'         => 'Yousif Dhneem',
            'tenant_type'  => 'individual',
            'id_cr_number' => '00040405',
        ]);

        $this->sittingLease = LeaseContract::create([
            'date'               => Carbon::today()->subMonths(6)->toDateString(),
            'lease_agreement_no' => 'LA/0006',
            'tenant_id'          => $this->sittingTenant->id,
            'tenant_name'        => $this->sittingTenant->name,
            'property_name'      => 'Miknas Plaza 1',
            'property_code'      => 'MP1',
            'unit_id'            => $this->occupiedUnit->id,
            'unit'               => $this->occupiedUnit->unit_name,
            'lease_start_date'   => Carbon::today()->subMonths(6)->toDateString(),
            'lease_end_date'     => Carbon::today()->addMonths(6)->toDateString(),
            'rent_per_month'     => 535.000,
            'vat_enabled'        => false,
        ]);

        $this->existingInvoice = Invoice::create([
            'invoice_number' => 'INV-R-' . Carbon::today()->subMonths(2)->format('my') . '-0001',
            'tenant_id'      => $this->sittingTenant->id,
            'tenant_name'    => $this->sittingTenant->name,
            'property_name'  => 'Miknas Plaza 1',
            'unit'           => $this->occupiedUnit->unit_name,
            'type'           => 'rent',
            'lines'          => [['property_name' => 'Miknas Plaza 1', 'amount' => '535.000']],
            'amount'         => 535.000,
            'invoice_date'   => Carbon::today()->subMonths(2)->toDateString(),
            'status'         => 'issued',
        ]);

        // Two image rows: one a 240×160 placeholder of the kind actually on
        // disk, one a genuine upload that has to survive.
        Storage::disk('public')->put("buildings/{$this->building->id}/img1.jpg", $this->png(240, 160));
        Storage::disk('public')->put("buildings/{$this->building->id}/frontage.jpg", $this->png(1200, 800));

        $this->placeholderPhoto = BuildingImage::create([
            'building_id' => $this->building->id,
            'path'        => "buildings/{$this->building->id}/img1.jpg",
            'sort_order'  => 1,
        ]);

        $this->realPhoto = BuildingImage::create([
            'building_id' => $this->building->id,
            'path'        => "buildings/{$this->building->id}/frontage.jpg",
            'sort_order'  => 2,
        ]);
    }

    private function png(int $w, int $h): string
    {
        $img = imagecreatetruecolor($w, $h);
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    // ── it fills what was missing ───────────────────────────────────────────

    public function test_it_prices_every_unit_that_had_no_pricing(): void
    {
        foreach (PropertyUnit::where('building_id', $this->building->id)->get() as $unit) {
            $this->assertNotNull($unit->rent_per_month, "{$unit->unit_name} still has no rent");
            $this->assertGreaterThan(0, (float) $unit->rent_per_month);
            $this->assertNotNull($unit->area_inside, "{$unit->unit_name} still has no area");
            $this->assertNotNull($unit->rate_per_area_unit, "{$unit->unit_name} still has no rate");
            $this->assertNotNull($unit->security_deposit_amount);
            $this->assertNotNull($unit->electricity_meter_no, "{$unit->unit_name} still has no meter");
            $this->assertNotNull($unit->water_meter_no);
            $this->assertNotNull($unit->municipality_nos);
            $this->assertSame('Sq. Mt.', $unit->area_unit);
        }
    }

    public function test_it_corrects_the_unit_type_the_form_could_not_accept(): void
    {
        $this->assertSame(
            0,
            PropertyUnit::where('building_id', $this->building->id)->where('unit_type', '3 BHK')->count(),
            '"3 BHK" is not in the unit form\'s allowed list, so those records cannot be saved',
        );

        $this->assertSame(
            25,
            PropertyUnit::where('building_id', $this->building->id)->where('unit_type', '3BHK')->count(),
        );
    }

    public function test_it_attaches_units_that_belonged_to_no_floor(): void
    {
        $this->assertSame(
            0,
            PropertyUnit::where('building_id', $this->building->id)->whereNull('floor_id')->count(),
        );

        // Derived from the unit's own name: S701 is on the seventh floor.
        $this->assertSame(
            'Floor 7',
            PropertyUnit::where('unit_name', 'MP1 - S701')->firstOrFail()->floor_id
                ? Floor::find(PropertyUnit::where('unit_name', 'MP1 - S701')->firstOrFail()->floor_id)->floor_name
                : null,
        );
    }

    public function test_it_completes_the_buildings_own_blank_fields(): void
    {
        $this->building->refresh();

        $this->assertSame(25, $this->building->total_no_of_units);
        $this->assertSame(7, $this->building->total_no_of_floors);
        $this->assertNotNull($this->building->area);
        $this->assertNotNull($this->building->block);
    }

    public function test_it_keeps_the_address_already_on_the_record(): void
    {
        $this->building->refresh();

        // Filled columns are never rewritten: this address is real.
        $this->assertSame(457, $this->building->building_no);
        $this->assertSame('Sh. Abdulla Bin Khalid Al Khalifa Avenue', $this->building->road);
        $this->assertSame('Residential', $this->building->property_type);
    }

    // ── it does not damage what was there ───────────────────────────────────

    public function test_the_sitting_tenants_lease_and_invoice_are_untouched(): void
    {
        $this->assertDatabaseHas('tenants', [
            'id'          => $this->sittingTenant->id,
            'tenant_code' => 'Tenant-00063',
            'name'        => 'Yousif Dhneem',
        ]);

        $this->assertDatabaseHas('lease_contracts', [
            'id'                 => $this->sittingLease->id,
            'lease_agreement_no' => 'LA/0006',
            'rent_per_month'     => 535.000,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id'             => $this->existingInvoice->id,
            'invoice_number' => $this->existingInvoice->invoice_number,
            'amount'         => 535.000,
            'status'         => 'issued',
        ]);
    }

    public function test_it_does_not_put_a_second_tenant_in_an_occupied_unit(): void
    {
        $live = LeaseContract::where('unit_id', $this->occupiedUnit->id)
            ->whereDate('lease_start_date', '<=', Carbon::today())
            ->whereDate('lease_end_date', '>=', Carbon::today())
            ->get();

        $this->assertCount(1, $live, 'the occupied unit was let twice over');
        $this->assertSame($this->sittingLease->id, $live->first()->id);
    }

    public function test_an_occupied_units_asking_rent_matches_its_own_lease(): void
    {
        // Otherwise the unit page and the lease page state different rents for
        // the same flat, in front of whoever is being shown the system.
        // Compared numerically: the unit column and the lease column do not
        // carry the same decimal cast, so the strings differ where the money
        // does not.
        $this->assertEqualsWithDelta(
            535.0,
            (float) $this->occupiedUnit->fresh()->rent_per_month,
            0.001,
        );
    }

    public function test_it_replaces_the_placeholder_photo_but_keeps_a_real_upload(): void
    {
        $this->assertDatabaseMissing('building_images', ['id' => $this->placeholderPhoto->id]);
        Storage::disk('public')->assertMissing($this->placeholderPhoto->path);

        $this->assertDatabaseHas('building_images', [
            'id'   => $this->realPhoto->id,
            'path' => $this->realPhoto->path,
        ]);
        Storage::disk('public')->assertExists($this->realPhoto->path);

        // Plus the five it generated.
        $this->assertSame(
            5,
            BuildingImage::where('building_id', $this->building->id)
                ->where('path', 'like', '%facade-%')->count(),
        );
    }

    // ── it produces a demonstrable property ─────────────────────────────────

    public function test_it_lets_the_vacant_units_and_leaves_some_empty(): void
    {
        $total    = PropertyUnit::where('building_id', $this->building->id)->count();
        $occupied = $this->building->occupiedUnits()->count();

        $this->assertGreaterThan(0, $total - $occupied, 'no vacancy left to demonstrate');
        $this->assertGreaterThanOrEqual(70, Occupancy::percent($occupied, $total));

        $this->assertGreaterThan(
            15,
            LeaseContract::where('lease_agreement_no', 'like', 'LA/MP1/%')->count(),
        );
    }

    public function test_it_bills_and_costs_the_property(): void
    {
        $ownTenants = Tenant::where('tenant_code', 'like', 'MP1-T-%')->pluck('id');

        $this->assertGreaterThan(100, Invoice::whereIn('tenant_id', $ownTenants)->count());
        $this->assertGreaterThan(
            50,
            EwaBill::whereIn(
                'lease_contract_id',
                LeaseContract::where('lease_agreement_no', 'like', 'LA/MP1/%')->select('id'),
            )->count(),
        );
        $this->assertGreaterThan(0, Expense::where('building_id', $this->building->id)->count());
        $this->assertGreaterThan(0, Revenue::where('building_id', $this->building->id)->count());
        $this->assertGreaterThan(0, MaintenanceRequest::where('job_order', 'like', 'JO-MP1-%')->count());

        foreach (array_keys(Expense::CATEGORIES) as $category) {
            $this->assertGreaterThan(
                0,
                Expense::where('building_id', $this->building->id)->where('category', $category)->count(),
                "no '{$category}' expenses on this property",
            );
        }
    }

    public function test_its_profit_and_loss_reads_as_a_going_concern(): void
    {
        $statement = app(ProfitLossService::class)->build(
            Carbon::today()->startOfMonth()->subMonths(12),
            Carbon::today(),
            $this->building->id,
        );

        $this->assertGreaterThan(0, $statement['total_revenue']);
        $this->assertGreaterThan(0, $statement['total_expense']);
        $this->assertGreaterThan(0, $statement['net_profit']);
    }

    // ── and it can be run again ─────────────────────────────────────────────

    public function test_a_second_run_neither_duplicates_nor_destroys(): void
    {
        $before = [
            'units'    => PropertyUnit::where('building_id', $this->building->id)->count(),
            'leases'   => LeaseContract::where('lease_agreement_no', 'like', 'LA/MP1/%')->count(),
            'tenants'  => Tenant::where('tenant_code', 'like', 'MP1-T-%')->count(),
            'expenses' => Expense::where('building_id', $this->building->id)->count(),
            'photos'   => BuildingImage::where('building_id', $this->building->id)->count(),
        ];

        // A cost the user booked by hand, which no re-run may sweep away.
        $manual = Expense::create([
            'building_id'  => $this->building->id,
            'category'     => 'other',
            'description'  => 'Booked by hand during the demo',
            'amount'       => 12.500,
            'expense_date' => Carbon::today()->toDateString(),
            'vendor_name'  => 'A Vendor The Seeder Never Heard Of',
        ]);

        $this->seed(ShowcaseSeeder::class);

        $this->assertSame($before['units'], PropertyUnit::where('building_id', $this->building->id)->count());
        $this->assertSame($before['leases'], LeaseContract::where('lease_agreement_no', 'like', 'LA/MP1/%')->count());
        $this->assertSame($before['tenants'], Tenant::where('tenant_code', 'like', 'MP1-T-%')->count());
        $this->assertSame($before['photos'], BuildingImage::where('building_id', $this->building->id)->count());

        // The seeder's own expenses were replaced, not stacked; the hand-booked
        // one is still there.
        $this->assertSame($before['expenses'] + 1, Expense::where('building_id', $this->building->id)->count());
        $this->assertDatabaseHas('expenses', ['id' => $manual->id, 'amount' => 12.500]);

        // And the real records are still real.
        $this->assertDatabaseHas('invoices', ['id' => $this->existingInvoice->id]);
        $this->assertDatabaseHas('lease_contracts', ['id' => $this->sittingLease->id]);
        $this->assertDatabaseHas('tenants', ['id' => $this->sittingTenant->id]);
    }
}
