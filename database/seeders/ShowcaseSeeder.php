<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\BuildingImage;
use App\Models\CustomFieldDefinition;
use App\Models\EwaBill;
use App\Models\EwaPayment;
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
use Database\Seeders\Support\ShowcasePhotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Builds one fully-populated property — Miknas Plaza 3 — so every feature in
 * the app has something to show: occupancy and vacancy, leases at every stage
 * of their term, invoices in every status, part-payments, credit and debit
 * notes, EWA bills both over and under their cap, manual expenses in all eight
 * categories, manual revenue in all five, maintenance jobs at every step of the
 * approval flow, and photos.
 *
 * Scope is deliberately one building. Everything it writes is identifiable —
 * building `MP3`, tenant codes `MP3-T-*`, invoices/bills whose property_name is
 * "Miknas Plaza 3" — and purge() removes exactly that set, so the seeder is
 * re-runnable and never touches another property's records.
 *
 * Two things worth knowing before reading further:
 *
 *   • Numbers are cash-basis realistic for Bahrain: BHD to three decimals, EWA
 *     at 29 fils/kWh and 200 fils/m³, residential rent VAT-exempt and
 *     commercial rent standard-rated at 10%. ProfitLossService joins invoices
 *     to buildings by `property_name` and to units by `unit` — strings, not
 *     ids — so those columns are written to match exactly, or the P&L reads
 *     zero.
 *   • Model events are suppressed for the bulk write. The Auditable trait logs
 *     every create, and ~1,500 rows stamped with today's date would bury the
 *     real audit trail. Anything a model observer would normally fill in
 *     (Tenant's tenant_code) is therefore set explicitly here.
 */
class ShowcaseSeeder extends Seeder
{
    private const NAME = 'Miknas Plaza 3';
    private const CODE = 'MP3';

    /** Marks every tenant this seeder owns, so purge() can find them again. */
    private const TENANT_PREFIX = 'MP3-T-';

    /** Same figure in fils that the EWA tariff sheet quotes per unit. */
    private const ELEC_RATE  = 0.029;
    private const WATER_RATE = 0.200;

    /** Reproducible output: the same dataset every run, for screenshots. */
    private const SEED = 20260908;

    private Carbon $today;

    /** Per-prefix running sequence, for the app's own document numbering. */
    private array $sequences = [];

    /** Named counters backing the status/method patterns. See cursor(). */
    private array $cursors = [];

    public function run(): void
    {
        $this->today = Carbon::today();

        mt_srand(self::SEED);

        Model::withoutEvents(function () {
            $this->purge();

            $building = $this->seedBuilding();
            $floors   = $this->seedFloors($building);
            $units    = $this->seedUnits($building, $floors);

            $this->seedPhotos($building);
            $this->seedCustomFields($building, $units);

            $tenants = $this->seedTenants();
            $leases  = $this->seedLeases($building, $units, $tenants);

            $this->seedInvoicing($leases);
            $this->seedEwa($leases);
            $this->seedExpenses($building, $units);
            $this->seedRevenues($building, $units);
            $this->seedMaintenance($building, $units, $leases);
        });

        $this->report();
    }

    // ── purge ────────────────────────────────────────────────────────────────

    /**
     * Removes a previous run. Order matters: children before parents, because
     * these tables mix `cascade` and `set null` foreign keys and a `set null`
     * would otherwise leave an orphan invoice or unit behind rather than
     * deleting it.
     */
    private function purge(): void
    {
        $building = Building::where('property_code', self::CODE)->first();

        $unitIds  = $building ? PropertyUnit::where('building_id', $building->id)->pluck('id')->all() : [];
        $leaseIds = LeaseContract::where('property_code', self::CODE)
            ->orWhereIn('unit_id', $unitIds ?: [0])
            ->pluck('id')->all();

        $invoiceIds = Invoice::where('property_name', self::NAME)->pluck('id')->all();
        $billIds    = EwaBill::where('property_name', self::NAME)
            ->orWhereIn('lease_contract_id', $leaseIds ?: [0])
            ->pluck('id')->all();

        if ($invoiceIds) {
            Payment::whereIn('invoice_id', $invoiceIds)->delete();
            InvoiceNote::whereIn('invoice_id', $invoiceIds)->delete();
            Invoice::whereIn('id', $invoiceIds)->delete();
        }

        if ($billIds) {
            // Only the EWA-side receipts. A rent Payment can also carry an
            // ewa_bill_id — it means "the tenant settled both in one
            // transfer" — and deleting by that column would take another
            // property's receipt with it. The foreign key is `set null`, so
            // dropping the bill clears the reference on its own.
            EwaPayment::whereIn('ewa_bill_id', $billIds)->delete();
            EwaBill::whereIn('id', $billIds)->delete();
        }

        if ($leaseIds) {
            LeaseContract::whereIn('id', $leaseIds)->delete();
        }

        if ($building) {
            Expense::where('building_id', $building->id)->delete();
            Revenue::where('building_id', $building->id)->delete();
            MaintenanceRequest::where('building_id', $building->id)->delete();
            BuildingImage::where('building_id', $building->id)->delete();
            PropertyUnit::where('building_id', $building->id)->delete();
            Floor::where('building_id', $building->id)->delete();

            // While the id is still known: the photo directory is keyed by it,
            // and after the row is gone there is nothing left to resolve it
            // from. Scoped to this building, so no other property's uploads
            // are ever in reach.
            Storage::disk('public')->deleteDirectory("buildings/{$building->id}");

            $building->delete();
        }

        MaintenanceRequest::where('property', self::NAME)->delete();
        Tenant::where('tenant_code', 'like', self::TENANT_PREFIX . '%')->delete();
        CustomFieldDefinition::whereIn('name', ['title_deed_no', 'has_balcony'])->delete();
    }

    // ── the property ─────────────────────────────────────────────────────────

    private function seedBuilding(): Building
    {
        return Building::create([
            'property_name'      => self::NAME,
            'property_code'      => self::CODE,
            'type_of_ownership'  => 'Owned',
            'property_type'      => 'Mixed Use',
            'land_lord_name'     => 'Akram Miknas',
            'building_no'        => 1290,
            'road'               => 'Road 3801',
            'block'              => 338,
            'area'               => 'Seef District',
            'city'               => 'Manama',
            'total_no_of_blocks' => 1,
            'total_no_of_floors' => 12,
            'total_no_of_units'  => 46,
            // Mixed use: the commercial ground floor is standard-rated, so the
            // building carries a VAT rate even though its flats are exempt.
            'vat_enabled'        => true,
            'vat_rate'           => 10.00,
            'custom_fields'      => ['title_deed_no' => 'TD/338/1290/2019'],
        ]);
    }

    /**
     * Ground floor plus eleven upper floors, named the way the existing
     * properties name theirs (Floor 1 / FL1, Block 1 / BL1) so the two read as
     * one portfolio.
     *
     * @return array<int, Floor> keyed by storey number, 0 = ground
     */
    private function seedFloors(Building $building): array
    {
        $floors = [];

        $floors[0] = Floor::create([
            'building_id'       => $building->id,
            'floor_name'        => 'Ground Floor',
            'floor_code'        => 'GF',
            'block_name'        => 'Block 1',
            'block_code'        => 'BL1',
            'total_no_of_units' => 4,
        ]);

        for ($f = 1; $f <= 11; $f++) {
            $floors[$f] = Floor::create([
                'building_id'       => $building->id,
                'floor_name'        => "Floor {$f}",
                'floor_code'        => "FL{$f}",
                'block_name'        => 'Block 1',
                'block_code'        => 'BL1',
                'total_no_of_units' => $f === 11 ? 2 : 4,
            ]);
        }

        return $floors;
    }

    /**
     * 46 units: four commercial at street level, four flats on each of floors
     * 1–10, and two penthouses on 11. Rent climbs 8 BHD a storey, which is
     * what makes the unit list worth sorting in a demo.
     *
     * @param  array<int, Floor>  $floors
     * @return array<string, PropertyUnit> keyed by unit name
     */
    private function seedUnits(Building $building, array $floors): array
    {
        $ground = [
            ['G1', 'Commercial', 'Fitted',         145, 20.0, 1200, 3, 'Retail showroom facing the avenue'],
            ['G2', 'Commercial', 'Shell & Core',   120, null,  950, 2, 'Retail shell, tenant fit-out pending'],
            ['G3', 'Office',     'Fitted',          95, null,  700, 2, 'Ground-floor clinic / office suite'],
            ['G4', 'Office',     'Semi-Furnished',  88, null,  650, 2, 'Ground-floor office suite'],
        ];

        $types      = ['2BHK', '3BHK', '1BHK', 'Studio'];
        $areas      = [112, 148, 78, 55];
        $baseRents  = [430, 560, 310, 230];
        $parkings   = [1, 2, 1, 0];
        $conditions = ['Furnished', 'Semi-Furnished', 'Unfurnished', 'Fitted'];

        $units = [];
        $idx   = 0;

        foreach ($ground as [$suffix, $type, $condition, $area, $terrace, $rent, $parking, $description]) {
            $units[self::CODE . " - {$suffix}"] = $this->makeUnit(
                $building, $floors[0], $idx++, self::CODE . " - {$suffix}",
                $type, $condition, 'Street View', $area, $terrace, (float) $rent, $parking, $description,
            );
        }

        for ($f = 1; $f <= 10; $f++) {
            $view = $f <= 4 ? 'Street View' : ($f <= 8 ? 'City View' : 'Sea View');

            for ($b = 0; $b < 4; $b++) {
                $name = self::CODE . " - {$f}" . ($b + 1);

                $units[$name] = $this->makeUnit(
                    $building, $floors[$f], $idx++, $name,
                    $types[$b], $conditions[($f + $b) % 4], $view,
                    $areas[$b], null, (float) ($baseRents[$b] + ($f - 1) * 8), $parkings[$b],
                    "{$types[$b]} flat, {$view}",
                );
            }
        }

        foreach ([1, 2] as $n) {
            $name = self::CODE . ' - 11' . $n;

            $units[$name] = $this->makeUnit(
                $building, $floors[11], $idx++, $name,
                'Penthouse', 'Furnished', 'Sea View', 265, 45.0, 1150.0, 2,
                'Full-floor penthouse with private terrace',
            );
        }

        return $units;
    }

    private function makeUnit(
        Building $building,
        Floor $floor,
        int $idx,
        string $name,
        string $type,
        string $condition,
        string $view,
        float $area,
        ?float $terrace,
        float $rent,
        int $parking,
        string $description,
    ): PropertyUnit {
        return PropertyUnit::create([
            'building_id'                   => $building->id,
            'floor_id'                      => $floor->id,
            // The unit table denormalises its property and address columns —
            // the import/export templates and the unit PDF read them from here
            // rather than joining, so they have to be filled.
            'property_name'                 => $building->property_name,
            'property_code'                 => $building->property_code,
            'type_of_ownership'             => $building->type_of_ownership,
            'property_type'                 => $building->property_type,
            'land_lord_name'                => $building->land_lord_name,
            'building_no'                   => $building->building_no,
            'road'                          => $building->road,
            'block'                         => $building->block,
            'area'                          => $building->area,
            'city'                          => $building->city,
            'unit_name'                     => $name,
            'description'                   => $description,
            'unit_type'                     => $type,
            'creation_date'                 => '2019-06-30',
            'unit_condition'                => $condition,
            'view'                          => $view,
            'no_of_parkings_foc'            => $parking,
            'area_unit'                     => 'Sq. Mt.',
            'area_inside'                   => $area,
            'area_terrace'                  => $terrace,
            'rate_per_area_unit'            => round($rent / $area, 3),
            'rent_per_month'                => $rent,
            'security_deposit_amount'       => $rent,
            'municipality_nos'              => 'MUN/338/' . str_pad((string) (1200 + $idx), 5, '0', STR_PAD_LEFT),
            'electricity_installation_date' => '2019-04-22',
            'electricity_meter_no'          => 'KS' . str_pad((string) (3000 + $idx * 7), 6, '0', STR_PAD_LEFT),
            'water_installation_date'        => '2019-05-06',
            'water_meter_no'                => '23H' . str_pad((string) (163000000 + $idx * 431), 9, '0', STR_PAD_LEFT),
            'electricity_ac_no'             => 'AC-MP3-' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT),
            'custom_fields'                 => ['has_balcony' => $type === 'Studio' ? 'No' : 'Yes'],
        ]);
    }

    private function seedPhotos(Building $building): void
    {
        $disk = Storage::disk('public');
        $dir  = "buildings/{$building->id}";

        $disk->makeDirectory($dir);

        foreach (ShowcasePhotos::looks() as $i => $look) {
            $relative = "{$dir}/facade-{$look}.jpg";

            // GD writes to a real path, so resolve the disk's own root rather
            // than assuming storage/app/public.
            ShowcasePhotos::facade($look, $i, $disk->path($relative));

            BuildingImage::create([
                'building_id' => $building->id,
                'path'        => $relative,
                'sort_order'  => $i + 1,
            ]);
        }
    }

    /**
     * Two custom fields, one per form type, so the Custom Fields feature has
     * something to display. Both optional — they appear on every building and
     * unit form, including the other properties', and a required field would
     * block editing those.
     *
     * @param  array<string, PropertyUnit>  $units
     */
    private function seedCustomFields(Building $building, array $units): void
    {
        CustomFieldDefinition::create([
            'form_type'   => 'building',
            'name'        => 'title_deed_no',
            'label'       => 'Title Deed No.',
            'field_type'  => 'text',
            'is_required' => false,
            'sort_order'  => 1,
            'is_active'   => true,
        ]);

        CustomFieldDefinition::create([
            'form_type'   => 'unit',
            'name'        => 'has_balcony',
            'label'       => 'Balcony',
            'field_type'  => 'select',
            'options'     => ['Yes', 'No'],
            'is_required' => false,
            'sort_order'  => 1,
            'is_active'   => true,
        ]);
    }

    // ── tenants ──────────────────────────────────────────────────────────────

    /**
     * 22 individuals and 10 companies. Every address is invented, every email
     * is on example.com (the reserved domain), and no company name belongs to
     * a real business — this is demo data that will sit in a database holding
     * live records, so none of it should be mistakable for a real party.
     *
     * @return list<Tenant>
     */
    private function seedTenants(): array
    {
        $individuals = [
            ['Hussain Ali Al Mannai',        '870412345', 'Bahraini'],
            ['Fatima Abdulrahman Al Dosari', '910233871', 'Bahraini'],
            ['Rajesh Kumar Nair',            'P4471903',  'Indian'],
            ['Maria Consuelo Santos',        'P8830412',  'Filipino'],
            ['Ahmed Sayed Kamal',            'A2290117',  'Egyptian'],
            ['Noora Yousif Al Binali',       '880511204', 'Bahraini'],
            ['Imran Tariq Sheikh',           'PK5540218', 'Pakistani'],
            ['James Whitfield',              'GB9921476', 'British'],
            ['Layla Hassan Al Qassab',       '790322145', 'Bahraini'],
            ['Suresh Menon',                 'P6612390',  'Indian'],
            ['Omar Khalid Al Zayani',        '850144023', 'Bahraini'],
            ['Grace Nyambura Mwangi',        'KE3320981', 'Kenyan'],
            ['Yousef Nabil Haddad',          'JO7741220', 'Jordanian'],
            ['Aisha Mohammed Al Sabah',      'KW4410332', 'Kuwaiti'],
            ['Vikram Singh Chauhan',         'P7719044',  'Indian'],
            ['Sara Ibrahim Al Ansari',        '930277510', 'Bahraini'],
            ['Daniel Osei Boateng',          'GH2214077', 'Ghanaian'],
            ['Reem Adel Al Shaikh',          '900166432', 'Bahraini'],
            ['Chen Wei Lim',                 'MY8830155', 'Malaysian'],
            ['Khalifa Saeed Al Rumaihi',     '760455901', 'Bahraini'],
            ['Priya Lakshmi Raman',          'P5503388',  'Indian'],
            ['Mohammed Faisal Al Aali',      '890322670', 'Bahraini'],
        ];

        $companies = [
            ['Gulf Horizon Trading W.L.L.',      '98341-1'],
            ['Seef Dental Care S.P.C.',          '107442-1'],
            ['Delmon Digital Networks B.S.C.(c)', '64120-3'],
            ['Al Waha Café & Roastery W.L.L.',   '112905-1'],
            ['Northern Gulf Logistics W.L.L.',   '88217-2'],
            ['Manama Legal Consultancy S.P.C.',  '121663-1'],
            ['Pearl Coast Interiors W.L.L.',     '99074-1'],
            ['Bright Path Tutoring Centre W.L.L.', '130558-1'],
            ['Arabian Wellness Pharmacy W.L.L.', '104881-2'],
            ['Ittihad Engineering Services W.L.L.', '77390-1'],
        ];

        $tenants = [];
        $n       = 0;

        // Companies first: the commercial units are leased in unit order, and
        // seedLeases() pairs the two lists positionally.
        foreach ($companies as [$name, $cr]) {
            $tenants[] = Tenant::create([
                'tenant_code'         => $this->tenantCode(++$n),
                'name'                => $name,
                'tenant_type'         => 'company',
                'company_name'        => $name,
                'id_cr_number'        => $cr,
                'phone'               => '17' . str_pad((string) (500000 + $n * 137), 6, '0', STR_PAD_LEFT),
                'email'               => 'accounts+' . $n . '@example.com',
                'nationality_country' => 'Bahrain',
                'address'             => "Office {$n}0, Building 1290, Road 3801, Block 338, Seef District, Manama",
            ]);
        }

        foreach ($individuals as [$name, $id, $nationality]) {
            $slug = strtolower(str_replace([' ', '.'], ['.', ''], $name));

            $tenants[] = Tenant::create([
                'tenant_code'         => $this->tenantCode(++$n),
                'name'                => $name,
                'tenant_type'         => 'individual',
                'id_cr_number'        => $id,
                'phone'               => '3' . str_pad((string) (3000000 + $n * 20411), 7, '0', STR_PAD_LEFT),
                'email'               => "{$slug}@example.com",
                'nationality_country' => $nationality,
                'address'             => 'Flat ' . (10 + $n) . ', Building 1290, Road 3801, Block 338, Seef District, Manama',
            ]);
        }

        return $tenants;
    }

    private function tenantCode(int $n): string
    {
        return self::TENANT_PREFIX . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    // ── leases ───────────────────────────────────────────────────────────────

    /**
     * 38 live leases across 46 units — 82.6% occupancy, so the occupancy meter
     * has something to show — plus three historic leases on vacant units, so a
     * vacant unit can still have tenancy history behind it.
     *
     * Terms are staggered over the last 14 months. Anything that started 12 or
     * more months ago runs 24 months (a renewal), which keeps it live; the rest
     * run 12, so leases that began 10–11 months ago are now inside the 60-day
     * expiry window the notification feed watches.
     *
     * @param  array<string, PropertyUnit>  $units
     * @param  list<Tenant>  $tenants
     * @return list<array{lease: LeaseContract, unit: PropertyUnit, tenant: Tenant}>
     */
    private function seedLeases(Building $building, array $units, array $tenants): array
    {
        $vacant = [
            self::CODE . ' - G4',  self::CODE . ' - 34',  self::CODE . ' - 43',
            self::CODE . ' - 61',  self::CODE . ' - 74',  self::CODE . ' - 92',
            self::CODE . ' - 103', self::CODE . ' - 112',
        ];

        $leasable = array_values(array_filter(
            $units,
            fn (PropertyUnit $u) => ! in_array($u->unit_name, $vacant, true),
        ));

        $leases = [];

        foreach ($leasable as $i => $unit) {
            // 32 tenants, 38 leases: the first six companies take a second
            // unit, which is what a real portfolio looks like and gives the
            // tenant ledger a tenant with two units on it.
            $tenant = $tenants[$i < count($tenants) ? $i : $i - count($tenants)];

            $startMonthsAgo = 14 - ($i % 14);
            $termMonths     = $startMonthsAgo >= 12 ? 24 : 12;

            $start = $this->today->copy()->startOfMonth()->subMonths($startMonthsAgo);
            $end   = $start->copy()->addMonths($termMonths)->subDay();

            $leases[] = [
                'lease'  => $this->makeLease($building, $unit, $tenant, $i + 1, $start, $end),
                'unit'   => $unit,
                'tenant' => $tenant,
            ];
        }

        // Vacant units with history: a lease that ran its term and ended.
        foreach ([[1, 7], [3, 4], [5, 2]] as $n => [$vacantIdx, $endedMonthsAgo]) {
            $unit   = $units[$vacant[$vacantIdx]];
            $tenant = $tenants[26 + $n];

            $end   = $this->today->copy()->startOfMonth()->subMonths($endedMonthsAgo)->endOfMonth();
            $start = $end->copy()->addDay()->subMonths(12);

            $leases[] = [
                'lease'  => $this->makeLease($building, $unit, $tenant, 100 + $n, $start, $end),
                'unit'   => $unit,
                'tenant' => $tenant,
            ];
        }

        return $leases;
    }

    private function makeLease(
        Building $building,
        PropertyUnit $unit,
        Tenant $tenant,
        int $n,
        Carbon $start,
        Carbon $end,
    ): LeaseContract {
        $commercial = in_array($unit->unit_type, ['Commercial', 'Office'], true);
        $rent       = (float) $unit->rent_per_month;

        // Residential rent is VAT-exempt in Bahrain; commercial rent is
        // standard-rated. Getting this wrong would make the VAT return wrong,
        // and the return is one of the things being demonstrated.
        $vatEnabled = $commercial;

        $frequency = match (true) {
            $n % 12 === 0 => 'Annually',
            $n % 7 === 3  => 'Quarterly',
            default       => 'Monthly',
        };

        $floor = $unit->floor_id ? Floor::find($unit->floor_id) : null;

        return LeaseContract::create([
            'date'                       => $start->copy()->subDays(14)->toDateString(),
            'lease_agreement_no'         => 'LA/MP3/' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'tenant_id'                  => $tenant->id,
            'tenant_name'                => $tenant->name,
            'property_name'              => $building->property_name,
            'property_code'              => $building->property_code,
            'block_name'                 => $floor?->block_name,
            'block_code'                 => $floor?->block_code,
            'floor_name'                 => $floor?->floor_name,
            'floor_code'                 => $floor?->floor_code,
            'unit_id'                    => $unit->id,
            'unit'                       => $unit->unit_name,
            'description'                => $unit->unit_condition === 'Shell & Core' ? 'Shell & Core' : 'Fitted',
            'lease_start_date'           => $start->toDateString(),
            'lease_end_date'             => $end->toDateString(),
            // A break clause on every fifth lease, at the term's midpoint.
            'lease_break_date'           => $n % 5 === 0
                ? $start->copy()->addMonths((int) ((int) $start->diffInMonths($end) / 2))->toDateString()
                : null,
            'notice_period'              => $commercial ? '3 Months' : '1 Month',
            'rental_income_ledger'       => $commercial ? '4110-COMM' : '4100-RESI',
            'currency'                   => 'BHD',
            'security_deposit'           => $rent,
            // A cap on every other lease: the landlord absorbs EWA up to this
            // figure and the tenant pays the excess, which is what makes the
            // landlord-portion column on the P&L non-zero.
            'ewa_cap'                    => $n % 2 === 0 ? ($commercial ? 60.000 : 25.000) : null,
            'vat_enabled'                => $vatEnabled,
            'vat_rate'                   => $vatEnabled ? 10.00 : 0.00,
            'invoicing_frequency'        => $frequency,
            'rent_start_date'            => $start->toDateString(),
            'rent_end_date'              => $end->toDateString(),
            'rent_per_month'             => $rent,
            'service_frequency'          => $commercial ? 'Quarterly' : ($unit->unit_type === 'Penthouse' ? 'Annually' : null),
            'service_start_date'         => $commercial || $unit->unit_type === 'Penthouse' ? $start->toDateString() : null,
            'service_end_date'           => $commercial || $unit->unit_type === 'Penthouse' ? $end->toDateString() : null,
            'service_amount_bd_excl_vat' => $commercial ? 180.000 : ($unit->unit_type === 'Penthouse' ? 600.000 : null),
        ]);
    }

    // ── invoicing ────────────────────────────────────────────────────────────

    /**
     * Rent invoices for the last twelve months on every lease, at whatever
     * frequency that lease bills at, plus service-charge and sundry invoices so
     * all three invoice types are populated.
     *
     * Status follows age, which is what a real ledger looks like: everything
     * older than three months is settled, last month is mostly settled with
     * some slippage, and the current month is still open.
     *
     * @param  list<array{lease: LeaseContract, unit: PropertyUnit, tenant: Tenant}>  $leases
     */
    private function seedInvoicing(array $leases): void
    {
        $windowStart = $this->today->copy()->startOfMonth()->subMonths(11);
        $created     = [];

        foreach ($leases as $row) {
            $lease  = $row['lease'];
            $tenant = $row['tenant'];

            $step = match ($lease->invoicing_frequency) {
                'Annually'  => 12,
                'Quarterly' => 3,
                default     => 1,
            };

            $leaseStart = Carbon::parse($lease->rent_start_date)->startOfMonth();
            $leaseEnd   = Carbon::parse($lease->rent_end_date);
            $period     = $leaseStart->copy();

            // Advance to the reporting window without losing the lease's own
            // billing phase — a quarterly lease bills on its own months, not
            // on calendar quarters.
            while ($period->lt($windowStart)) {
                $period->addMonths($step);
            }

            while ($period->lte($this->today) && $period->lte($leaseEnd)) {
                $periodEnd = $period->copy()->addMonths($step)->subDay();
                $amount    = round((float) $lease->rent_per_month * $step, 3);

                $created[] = $this->makeInvoice(
                    $lease, $tenant, 'rent',
                    'Rent — ' . $period->format('M Y') . ($step > 1 ? ' to ' . $periodEnd->format('M Y') : ''),
                    $amount, $period, $periodEnd,
                );

                $period->addMonths($step);
            }

            // Service charge, where the lease carries one.
            if ($lease->service_amount_bd_excl_vat && $lease->service_frequency) {
                $serviceStep = $lease->service_frequency === 'Annually' ? 12 : 3;
                $sPeriod     = Carbon::parse($lease->service_start_date)->startOfMonth();

                while ($sPeriod->lt($windowStart)) {
                    $sPeriod->addMonths($serviceStep);
                }

                while ($sPeriod->lte($this->today) && $sPeriod->lte($leaseEnd)) {
                    $created[] = $this->makeInvoice(
                        $lease, $tenant, 'utilities',
                        'Service charge — ' . $sPeriod->format('M Y'),
                        (float) $lease->service_amount_bd_excl_vat,
                        $sPeriod, $sPeriod->copy()->addMonths($serviceStep)->subDay(),
                    );

                    $sPeriod->addMonths($serviceStep);
                }
            }
        }

        $this->seedSundryInvoices($leases);
        $this->seedInvoiceNotes(array_filter($created));
    }

    /**
     * One-off charges, so the 'other' invoice type is not empty: parking, key
     * replacement, a fit-out deposit, an early-termination charge.
     *
     * @param  list<array{lease: LeaseContract, unit: PropertyUnit, tenant: Tenant}>  $leases
     */
    private function seedSundryInvoices(array $leases): void
    {
        $sundries = [
            ['Additional parking bay — annual',      240.000, 1],
            ['Replacement access cards (2)',           12.000, 2],
            ['Fit-out supervision charge',            350.000, 3],
            ['Chiller top-up — recharged to tenant',   68.500, 4],
            ['Early termination administration fee',  150.000, 5],
            ['Additional storage cage — annual',       96.000, 6],
            ['Balcony glazing repair — tenant damage', 185.000, 7],
            ['Move-in / move-out lift booking',        30.000, 8],
        ];

        foreach ($sundries as $i => [$description, $amount, $monthsAgo]) {
            $row   = $leases[($i * 5) % count($leases)];
            $date  = $this->today->copy()->subMonths($monthsAgo)->startOfMonth()->addDays(6 + $i);

            $this->makeInvoice($row['lease'], $row['tenant'], 'other', $description, $amount, $date, $date);
        }
    }

    /**
     * Creates one invoice, its payments, and the status the two agree on.
     */
    private function makeInvoice(
        LeaseContract $lease,
        Tenant $tenant,
        string $type,
        string $description,
        float $amount,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): Invoice {
        $invoiceDate = $periodStart->copy();
        if ($invoiceDate->gt($this->today)) {
            $invoiceDate = $this->today->copy();
        }

        $vatRate   = (float) $lease->vat_rate;
        $vatAmount = round($amount * $vatRate / 100, 3);

        // Carbon 3 returns a signed float here, so this has to be cast before
        // the status table below compares it with ===.
        $ageMonths = (int) $invoiceDate->copy()->startOfMonth()
            ->diffInMonths($this->today->copy()->startOfMonth());

        $status = $this->invoiceStatus($ageMonths);

        $invoice = Invoice::create([
            'invoice_number' => $this->nextNumber('INV-' . $this->typeCode($type) . '-' . $invoiceDate->format('my'), Invoice::class, 'invoice_number'),
            'tenant_id'      => $tenant->id,
            'tenant_name'    => $tenant->name,
            'tenant_code'    => $tenant->tenant_code,
            'tenant_address' => $tenant->address,
            // ProfitLossService and VatReturnService both filter on these two
            // strings rather than on ids. They must match the building's
            // property_name and the unit's unit_name exactly.
            'property_name'  => $lease->property_name,
            'unit'           => $lease->unit,
            'type'           => $type,
            'description'    => $description,
            'lines'          => [[
                'lease_contract_id'   => (string) $lease->id,
                'property_name'       => $lease->property_name,
                'unit'                => $lease->unit,
                'lease_agreement_no'  => $lease->lease_agreement_no,
                'rental_period_start' => $periodStart->toDateString(),
                'rental_period_end'   => $periodEnd->toDateString(),
                'amount'              => number_format($amount, 3, '.', ''),
            ]],
            'amount'         => $amount,
            'vat_rate'       => $vatRate,
            'vat_amount'     => $vatAmount,
            'invoice_date'   => $invoiceDate->toDateString(),
            'status'         => $status,
            'remarks'        => $status === 'cancelled' ? 'Cancelled — superseded by a corrected invoice' : null,
        ]);

        $this->settleInvoice($invoice, $status, $amount + $vatAmount, $invoiceDate);

        return $invoice;
    }

    /**
     * The ageing curve: everything old is settled, last month has some
     * slippage, the current month is still open.
     *
     * Dealt from a fixed pattern per age bucket rather than rolled at random.
     * A demo has to show every status — an empty "Draft" or "Overdue" filter
     * looks like a broken screen, not like a quiet month — and a weighted roll
     * can legitimately produce none of a 10% outcome. Cycling a pattern makes
     * the mix exact and repeatable, and each bucket has enough invoices in it
     * to get all the way round.
     */
    private function invoiceStatus(int $ageMonths): string
    {
        $pattern = match (true) {
            // Long settled — bar the occasional cancellation.
            $ageMonths >= 4  => array_merge(array_fill(0, 19, 'paid'), ['cancelled']),
            $ageMonths === 3 => array_merge(array_fill(0, 9, 'paid'), ['partially_paid']),
            $ageMonths === 2 => array_merge(array_fill(0, 8, 'paid'), ['partially_paid', 'overdue']),
            // Last month: mostly in, a little outstanding.
            $ageMonths === 1 => [
                'paid', 'paid', 'paid', 'paid', 'paid', 'paid',
                'partially_paid', 'partially_paid', 'overdue', 'overdue',
            ],
            // The open month: issued dominates, with early payers, part
            // payments and a couple still in draft.
            default => [
                'issued', 'paid', 'issued', 'partially_paid', 'issued',
                'paid', 'issued', 'partially_paid', 'issued', 'draft',
                'issued', 'paid', 'issued', 'partially_paid', 'issued',
                'paid', 'issued', 'issued', 'paid', 'draft',
            ],
        };

        return $pattern[$this->cursor("status-{$ageMonths}") % count($pattern)];
    }

    /**
     * Writes the payments that justify the status. `paid` settles in full —
     * every fifth in two instalments, so the receipt list is not uniform;
     * `partially_paid` lands 45–75% of the way there. Everything else has no
     * cash against it.
     */
    private function settleInvoice(Invoice $invoice, string $status, float $total, Carbon $invoiceDate): void
    {
        if (! in_array($status, ['paid', 'partially_paid'], true)) {
            return;
        }

        $portion = $status === 'paid' ? 1.0 : mt_rand(45, 75) / 100;
        $due     = round($total * $portion, 3);

        $instalments = $status === 'paid' && $invoice->id % 5 === 0 ? 2 : 1;

        for ($i = 0; $i < $instalments; $i++) {
            $amount = $instalments === 1
                ? $due
                : ($i === 0 ? round($due / 2, 3) : round($due - round($due / 2, 3), 3));

            $paidOn = $invoiceDate->copy()->addDays(3 + mt_rand(0, 17) + $i * 14);
            if ($paidOn->gt($this->today)) {
                $paidOn = $this->today->copy();
            }

            // Cycled, not rolled, so all four methods are represented — the
            // cheque fields and the card reference each only render on their
            // own method.
            $methods = [
                'bank_transfer', 'bank_transfer', 'cheque', 'cash', 'bank_transfer',
                'online_card', 'bank_transfer', 'cheque', 'cash', 'bank_transfer',
            ];
            $method = $methods[$this->cursor('pay-method') % count($methods)];

            Payment::create([
                'payment_number' => $this->nextNumber('PAY-' . $paidOn->format('Ymd'), Payment::class, 'payment_number'),
                'invoice_id'     => $invoice->id,
                'amount'         => $amount,
                'payment_date'   => $paidOn->toDateString(),
                'method'         => $method,
                'reference'      => match ($method) {
                    'bank_transfer' => 'BBK/TRF/' . $paidOn->format('ymd') . '/' . str_pad((string) ($invoice->id % 1000), 3, '0', STR_PAD_LEFT),
                    'online_card'   => 'CRD-' . strtoupper(substr(md5((string) $invoice->id), 0, 8)),
                    'cash'          => 'Cash receipt at office',
                    default         => null,
                },
                'cheque_number'  => $method === 'cheque' ? (string) (400000 + $invoice->id) : null,
                'cheque_date'    => $method === 'cheque' ? $paidOn->toDateString() : null,
                'notes'          => $instalments === 2 ? 'Instalment ' . ($i + 1) . ' of 2' : null,
            ]);
        }
    }

    /**
     * Credit and debit notes against settled invoices — the adjustments that
     * make a tenant ledger worth reading.
     *
     * @param  list<Invoice>  $invoices
     */
    private function seedInvoiceNotes(array $invoices): void
    {
        $invoices = array_values(array_filter(
            $invoices,
            fn (Invoice $i) => in_array($i->status, ['paid', 'partially_paid'], true),
        ));

        if (! $invoices) {
            return;
        }

        $notes = [
            ['credit', 45.000,  'Rent waiver — three days without air conditioning'],
            ['credit', 120.000, 'Billing correction — rent invoiced at the pre-renewal rate'],
            ['credit', 30.000,  'Goodwill adjustment — lift outage'],
            ['credit', 18.500,  'Service charge over-recovery, prior quarter'],
            ['credit', 250.000, 'Fit-out contribution agreed at renewal'],
            ['credit', 62.000,  'Duplicate charge reversed'],
            ['debit',  25.000,  'Late payment charge'],
            ['debit',  85.000,  'Damage recovery — internal door'],
            ['debit',  40.000,  'Returned cheque handling fee'],
            ['debit',  15.000,  'Additional refuse collection'],
        ];

        foreach ($notes as $i => [$type, $amount, $reason]) {
            $invoice = $invoices[($i * 17) % count($invoices)];
            $date    = Carbon::parse($invoice->invoice_date)->addDays(20 + $i);

            if ($date->gt($this->today)) {
                $date = $this->today->copy();
            }

            InvoiceNote::create([
                'note_number' => $this->nextNumber(($type === 'credit' ? 'CN-' : 'DN-') . $date->format('Ymd'), InvoiceNote::class, 'note_number'),
                'invoice_id'  => $invoice->id,
                'tenant_id'   => $invoice->tenant_id,
                'type'        => $type,
                'amount'      => $amount,
                'note_date'   => $date->toDateString(),
                'reason'      => $reason,
            ]);
        }
    }

    // ── EWA ──────────────────────────────────────────────────────────────────

    /**
     * Six months of electricity and water bills for every lease.
     *
     * Every lease, not every second one: caps are set on alternating lease
     * numbers, and billing alternate leases lined the two parities up so that
     * almost every bill belonged to an uncapped lease — the cap feature was
     * seeded but invisible, and the P&L's landlord-portion column read 215 BHD
     * where it should read thousands.
     *
     * The cap is the point of this block. Where a lease carries one, the
     * landlord absorbs consumption up to the cap and the tenant pays the
     * excess — so some months the tenant owes nothing and the landlord carries
     * the whole bill, and that landlord portion is what shows up as an expense
     * on the P&L. Where a lease carries no cap, the tenant owes the lot.
     *
     * @param  list<array{lease: LeaseContract, unit: PropertyUnit, tenant: Tenant}>  $leases
     */
    private function seedEwa(array $leases): void
    {
        foreach ($leases as $i => $row) {
            $lease  = $row['lease'];
            $unit   = $row['unit'];
            $tenant = $row['tenant'];

            // Bigger units consume more; a studio and a penthouse should not
            // read the same on the meter.
            $scale = match ($unit->unit_type) {
                'Studio'                => 0.55,
                '1BHK'                  => 0.75,
                '2BHK'                  => 1.00,
                '3BHK'                  => 1.30,
                'Penthouse'             => 2.10,
                'Commercial', 'Office'  => 1.85,
                default                 => 1.00,
            };

            $elecReading  = 10000 + $i * 137;
            $waterReading = 900 + $i * 7;

            for ($m = 6; $m >= 1; $m--) {
                $period      = $this->today->copy()->startOfMonth()->subMonths($m);
                $readingDate = $period->copy()->addMonth()->day(5);

                if ($readingDate->gt($this->today)) {
                    continue;
                }

                // Summer peaks: Bahrain's July/August cooling load is roughly
                // double the winter baseline, which is what makes the
                // consumption chart worth looking at.
                $summer = in_array((int) $period->format('n'), [6, 7, 8, 9], true) ? 1.9 : 1.0;

                $elecUse  = (int) round(mt_rand(320, 520) * $scale * $summer);
                $waterUse = round(mt_rand(120, 340) / 10 * $scale, 3);

                $elecPrev  = $elecReading;
                $waterPrev = $waterReading;
                $elecReading  += $elecUse;
                $waterReading = round($waterReading + $waterUse, 3);

                $elecCharge  = round($elecUse * self::ELEC_RATE, 3);
                $waterCharge = round($waterUse * self::WATER_RATE, 3);
                $total       = round($elecCharge + $waterCharge, 3);

                $cap           = $lease->ewa_cap !== null ? (float) $lease->ewa_cap : null;
                $tenantPortion = EwaBill::computeTenantPortion($total, $cap);

                // Cast: Carbon 3's diff returns a float, and the table below
                // compares with ===. Signed, so period-before-today is
                // positive after the swap.
                $age = (int) $period->copy()->startOfMonth()
                    ->diffInMonths($this->today->copy()->startOfMonth());

                // Same reasoning as invoiceStatus(): dealt from a pattern so
                // every status on the EWA list has rows behind it.
                $recent = ['paid', 'paid', 'partially_paid', 'issued', 'paid', 'overdue', 'paid', 'issued'];

                $status = match (true) {
                    // Nothing to collect: consumption stayed inside the cap.
                    $tenantPortion <= 0 => 'paid',
                    $age >= 3           => 'paid',
                    $age === 2          => $this->cursor('ewa-2') % 8 === 0 ? 'partially_paid' : 'paid',
                    default             => $recent[$this->cursor('ewa-recent') % count($recent)],
                };

                $bill = EwaBill::create([
                    'bill_number'        => $this->nextNumber('EWA-' . $readingDate->format('Ymd'), EwaBill::class, 'bill_number'),
                    'lease_contract_id'  => $lease->id,
                    'tenant_name'        => $tenant->name,
                    'property_name'      => $lease->property_name,
                    'address'            => 'Building 1290, Road 3801, Block 338, Seef District, Manama',
                    'unit'               => $lease->unit,
                    'ewa_account_number' => '1' . str_pad((string) (2000000 + $i * 311), 8, '0', STR_PAD_LEFT),
                    'billing_period'     => $period->format('F Y'),
                    'reading_date'       => $readingDate->toDateString(),
                    'reading_type'       => $m === 1 && $i % 6 === 0 ? 'estimated' : 'actual',
                    'elec_prev_reading'  => $elecPrev,
                    'elec_curr_reading'  => $elecReading,
                    'elec_consumption'   => $elecUse,
                    'elec_charges'       => $elecCharge,
                    'water_prev_reading' => $waterPrev,
                    'water_curr_reading' => $waterReading,
                    'water_consumption'  => $waterUse,
                    'water_charges'      => $waterCharge,
                    'ewa_cap'            => $cap,
                    'tenant_portion'     => $cap !== null ? $tenantPortion : null,
                    'total_amount'       => $total,
                    'due_date'           => $readingDate->copy()->addDays(25)->toDateString(),
                    'status'             => $status,
                    'remarks'            => $cap !== null && $tenantPortion <= 0
                        ? 'Within the lease EWA cap — no tenant recharge this period'
                        : null,
                ]);

                $this->settleEwaBill($bill, $status, $tenantPortion, $readingDate);
            }
        }
    }

    private function settleEwaBill(EwaBill $bill, string $status, float $tenantPortion, Carbon $readingDate): void
    {
        if ($tenantPortion <= 0 || ! in_array($status, ['paid', 'partially_paid'], true)) {
            return;
        }

        $amount = $status === 'paid' ? $tenantPortion : round($tenantPortion * mt_rand(40, 70) / 100, 3);
        $paidOn = $readingDate->copy()->addDays(mt_rand(6, 24));

        if ($paidOn->gt($this->today)) {
            $paidOn = $this->today->copy();
        }

        $method = match (mt_rand(1, 4)) {
            1, 2    => 'bank_transfer',
            3       => 'cash',
            default => 'online_card',
        };

        EwaPayment::create([
            'payment_number' => $this->nextNumber('EWAPAY-' . $paidOn->format('Ymd'), EwaPayment::class, 'payment_number'),
            'ewa_bill_id'    => $bill->id,
            'amount'         => $amount,
            'payment_date'   => $paidOn->toDateString(),
            'method'         => $method,
            'reference'      => $method === 'bank_transfer'
                ? 'BBK/EWA/' . $paidOn->format('ymd') . '/' . str_pad((string) ($bill->id % 1000), 3, '0', STR_PAD_LEFT)
                : null,
            'notes'          => $status === 'partially_paid' ? 'Part payment received' : null,
        ]);
    }

    // ── manual expenses & revenue ────────────────────────────────────────────

    /**
     * Twelve months of operating cost in all eight categories: four monthly
     * service contracts, a quarterly municipality charge, an annual insurance
     * premium, and ad-hoc repairs — some charged to a specific unit, which is
     * what makes the unit-scoped P&L filter worth using.
     *
     * @param  array<string, PropertyUnit>  $units
     */
    private function seedExpenses(Building $building, array $units): void
    {
        $unitList = array_values($units);
        $author   = $this->authorId();

        $monthly = [
            ['management_fees',     850.000, 'Al Hilal Facilities Management W.L.L.', 'Monthly property management retainer'],
            ['security',            620.000, 'Gulf Guard Security Services W.L.L.',   '24-hour lobby and car park security'],
            ['cleaning',            480.000, 'Bahrain Sparkle Cleaning Co. W.L.L.',   'Common-area cleaning, daily'],
        ];

        for ($m = 11; $m >= 0; $m--) {
            $monthStart = $this->today->copy()->startOfMonth()->subMonths($m);

            foreach ($monthly as $i => [$category, $amount, $vendor, $description]) {
                $date = $this->cap($monthStart->copy()->addDays(2 + $i * 3));

                Expense::create([
                    'building_id'  => $building->id,
                    'category'     => $category,
                    'description'  => $description . ' — ' . $monthStart->format('M Y'),
                    'amount'       => $amount,
                    'expense_date' => $date->toDateString(),
                    'vendor_name'  => $vendor,
                    'created_by'   => $author,
                ]);
            }

            // Common-area electricity and water: the landlord's own EWA
            // account, distinct from the tenant bills recharged above, and
            // seasonal for the same reason.
            $summer = in_array((int) $monthStart->format('n'), [6, 7, 8, 9], true);

            Expense::create([
                'building_id'  => $building->id,
                'category'     => 'utilities',
                'description'  => 'Common-area electricity & water — ' . $monthStart->format('M Y'),
                'amount'       => round(($summer ? mt_rand(620, 780) : mt_rand(340, 470)), 3),
                'expense_date' => $this->cap($monthStart->copy()->addDays(12))->toDateString(),
                'vendor_name'  => 'Electricity & Water Authority',
                'created_by'   => $author,
            ]);

            if ($m % 3 === 0) {
                Expense::create([
                    'building_id'  => $building->id,
                    'category'     => 'municipality_fees',
                    'description'  => 'Quarterly municipality charge — ' . $monthStart->format('M Y'),
                    'amount'       => 1150.000,
                    'expense_date' => $this->cap($monthStart->copy()->addDays(18))->toDateString(),
                    'vendor_name'  => 'Manama Municipality',
                    'created_by'   => $author,
                ]);
            }

            if ($m === 10) {
                Expense::create([
                    'building_id'  => $building->id,
                    'category'     => 'insurance',
                    'description'  => 'Annual property and public liability cover',
                    'amount'       => 2400.000,
                    'expense_date' => $this->cap($monthStart->copy()->addDays(8))->toDateString(),
                    'vendor_name'  => 'Solidarity General Takaful B.S.C.',
                    'created_by'   => $author,
                ]);
            }
        }

        $repairs = [
            ['Lift 2 door sensor replacement',            420.000, 'Delmon Elevators & Escalators W.L.L.', 1,  null],
            ['Water pump overhaul, basement plant room',  865.000, 'AquaFlow Plumbing W.L.L.',             3,  null],
            ['Split AC compressor replacement',           310.000, 'Seef Electricals & AC W.L.L.',         2,  6],
            ['Fire alarm panel annual certification',     275.000, 'SafeGuard Fire Systems W.L.L.',        5,  null],
            ['Kitchen sink and trap replacement',          58.500, 'AquaFlow Plumbing W.L.L.',             4,  14],
            ['Corridor lighting — LED retrofit, floors 1–5', 640.000, 'Seef Electricals & AC W.L.L.',      7,  null],
            ['Bathroom leak repair and re-grout',         145.000, 'AquaFlow Plumbing W.L.L.',             6,  22],
            ['Car park barrier motor repair',             198.000, 'Delmon Elevators & Escalators W.L.L.', 8,  null],
            ['Roof waterproofing patch',                  530.000, 'Pearl Coast Interiors W.L.L.',         9,  null],
            ['Entrance door closer replacement',           45.000, 'Seef Electricals & AC W.L.L.',        10,  31],
            ['Balcony railing re-anchoring',              225.000, 'Pearl Coast Interiors W.L.L.',         2,  38],
            ['Water tank cleaning and chlorination',      180.000, 'AquaFlow Plumbing W.L.L.',             4,  null],
        ];

        foreach ($repairs as $i => [$description, $amount, $vendor, $monthsAgo, $unitIdx]) {
            Expense::create([
                'building_id'  => $building->id,
                'unit_id'      => $unitIdx !== null ? $unitList[$unitIdx]->id : null,
                'category'     => 'repairs_maintenance',
                'description'  => $description,
                'amount'       => $amount,
                'expense_date' => $this->cap(
                    $this->today->copy()->subMonths($monthsAgo)->startOfMonth()->addDays(4 + $i)
                )->toDateString(),
                'vendor_name'  => $vendor,
                'created_by'   => $author,
            ]);
        }

        $other = [
            ['Lobby signage refresh',                  380.000, 'Pearl Coast Interiors W.L.L.',    5],
            ['Landscaping — entrance planters',        165.000, 'Green Oasis Landscaping W.L.L.',   3],
            ['Pest control, whole building',           240.000, 'Gulf Pest Control W.L.L.',         7],
            ['Legal fee — lease template review',      450.000, 'Manama Legal Consultancy S.P.C.',  9],
        ];

        foreach ($other as $i => [$description, $amount, $vendor, $monthsAgo]) {
            Expense::create([
                'building_id'  => $building->id,
                'category'     => 'other',
                'description'  => $description,
                'amount'       => $amount,
                'expense_date' => $this->cap(
                    $this->today->copy()->subMonths($monthsAgo)->startOfMonth()->addDays(9 + $i)
                )->toDateString(),
                'vendor_name'  => $vendor,
                'created_by'   => $author,
            ]);
        }
    }

    /**
     * Income that arrives outside the invoice ledger, in all five categories —
     * the rooftop lease and visitor parking recur, the rest are one-offs.
     *
     * @param  array<string, PropertyUnit>  $units
     */
    private function seedRevenues(Building $building, array $units): void
    {
        $unitList = array_values($units);
        $author   = $this->authorId();

        for ($m = 11; $m >= 0; $m--) {
            $monthStart = $this->today->copy()->startOfMonth()->subMonths($m);

            Revenue::create([
                'building_id'  => $building->id,
                'category'     => 'parking_fee',
                'description'  => 'Visitor parking collections — ' . $monthStart->format('M Y'),
                'amount'       => round(mt_rand(120, 210), 3),
                'revenue_date' => $this->cap($monthStart->copy()->addDays(26))->toDateString(),
                'source_name'  => 'Visitor parking — cash collections',
                'created_by'   => $author,
            ]);

            Revenue::create([
                'building_id'  => $building->id,
                'category'     => 'miscellaneous_income',
                'description'  => 'Rooftop antenna site lease — ' . $monthStart->format('M Y'),
                'amount'       => 250.000,
                'revenue_date' => $this->cap($monthStart->copy()->addDays(4))->toDateString(),
                'source_name'  => 'Delmon Digital Networks B.S.C.(c)',
                'created_by'   => $author,
            ]);
        }

        $oneOffs = [
            ['late_fee',           25.000, 'Late payment charge — August rent',        1,  3],
            ['late_fee',           40.000, 'Late payment charge — returned cheque',    2, 11],
            ['late_fee',           25.000, 'Late payment charge — July rent',          3, 17],
            ['late_fee',           15.000, 'Late payment charge — service charge',     4, 24],
            ['late_fee',           45.000, 'Late payment charge — two months in arrears', 6, 29],
            ['deposit_forfeiture', 430.000, 'Deposit forfeited — vacated without notice', 5, 20],
            ['deposit_forfeiture', 310.000, 'Deposit part-forfeited — cleaning and repairs', 8, 35],
            ['other',              120.000, 'Vending machine commission, half-year',   2, null],
            ['other',              600.000, 'Ground-floor event space hire, two days', 4, null],
            ['other',               75.000, 'Scrap metal disposal proceeds',           7, null],
        ];

        foreach ($oneOffs as $i => [$category, $amount, $description, $monthsAgo, $unitIdx]) {
            Revenue::create([
                'building_id'  => $building->id,
                'unit_id'      => $unitIdx !== null ? $unitList[$unitIdx]->id : null,
                'category'     => $category,
                'description'  => $description,
                'amount'       => $amount,
                'revenue_date' => $this->cap(
                    $this->today->copy()->subMonths($monthsAgo)->startOfMonth()->addDays(7 + $i)
                )->toDateString(),
                'source_name'  => $unitIdx !== null ? $unitList[$unitIdx]->unit_name : 'Building — sundry income',
                'created_by'   => $author,
            ]);
        }
    }

    // ── maintenance ──────────────────────────────────────────────────────────

    /**
     * Eighteen job orders, at least one at every step of the workflow, so the
     * board view has a card in each column.
     *
     * Only a request that is department-head approved *and* has a quotation
     * selected reaches the P&L as a maintenance cost — ProfitLossService reads
     * `quotation_{selected_quotation}` on approved requests — so the approved,
     * in-progress and completed ones carry both.
     *
     * @param  array<string, PropertyUnit>  $units
     * @param  list<array{lease: LeaseContract, unit: PropertyUnit, tenant: Tenant}>  $leases
     */
    private function seedMaintenance(Building $building, array $units, array $leases): void
    {
        $jobs = [
            ['waiting_supervisor', 'Kitchen mixer tap dripping continuously',        'Kitchen',      null],
            ['waiting_supervisor', 'Bedroom AC not cooling, thermostat unresponsive', 'Bedroom 2',    null],
            ['waiting_supervisor', 'Bathroom extract fan noisy',                     'Bathroom',     null],
            ['waiting_approval',   'Water heater leaking from the base',             'Utility room', [95.000, 130.000, 118.000]],
            ['waiting_approval',   'Entrance door lock jammed',                      'Entrance',     [55.000, 48.000, 70.000]],
            ['waiting_approval',   'Balcony tile lifting, trip hazard',              'Balcony',      [280.000, 245.000, 310.000]],
            ['approved',           'Living room AC compressor replacement',          'Living room',  [310.000, 365.000, 340.000]],
            ['approved',           'Bathroom silicone and grout renewal',            'Bathroom',     [140.000, 125.000, 160.000]],
            ['approved',           'Kitchen cabinet hinge and door repair',          'Kitchen',      [85.000, 95.000, 78.000]],
            ['in_progress',        'Bedroom window seal replacement',                'Bedroom 1',    [210.000, 190.000, 235.000]],
            ['in_progress',        'Full flat repaint after tenancy',                'Whole unit',   [520.000, 480.000, 610.000]],
            ['in_progress',        'Wardrobe sliding track replacement',             'Bedroom 2',    [120.000, 145.000, 132.000]],
            ['completed',          'Blocked kitchen drain cleared',                  'Kitchen',      [65.000, 58.000, 80.000]],
            ['completed',          'Ceiling water stain traced and sealed',          'Living room',  [175.000, 210.000, 190.000]],
            ['completed',          'Bathroom WC cistern replacement',                'Bathroom',     [110.000, 98.000, 125.000]],
            ['completed',          'Electrical socket replacement, three points',    'Whole unit',   [72.000, 85.000, 68.000]],
            ['completed',          'Main door repaint and furniture replacement',    'Entrance',     [155.000, 140.000, 168.000]],
            ['cancelled',          'Tenant reported AC fault, resolved by filter clean', 'Living room', null],
        ];

        $supervisors = ['Hussain Al Mannai', 'Jaffar Al Sayed', 'Ravi Chandran'];
        $deptHeads   = ['Ghassan Yusuf', 'Akram Miknas'];

        foreach ($jobs as $i => [$status, $description, $location, $quotations]) {
            $row    = $leases[($i * 3) % count($leases)];
            $lease  = $row['lease'];
            $unit   = $row['unit'];
            $tenant = $row['tenant'];

            $raised = $this->cap(
                $this->today->copy()->subMonths(($i % 9) + 1)->startOfMonth()->addDays(3 + $i)
            );

            $assessed  = in_array($status, ['waiting_approval', 'approved', 'in_progress', 'completed'], true);
            $approved  = in_array($status, ['approved', 'in_progress', 'completed'], true);
            $supervisor = $supervisors[$i % count($supervisors)];
            $deptHead   = $deptHeads[$i % count($deptHeads)];

            // Pick the cheapest quote, which is the decision an approver
            // usually has to justify not making.
            $selected = $quotations && $approved
                ? array_search(min($quotations), $quotations, true) + 1
                : null;

            MaintenanceRequest::create([
                'date'                 => $raised->toDateString(),
                'request_date'         => $raised->toDateString(),
                'job_order'            => 'JO-MP3-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'building_id'          => $building->id,
                'unit_id'              => $unit->id,
                'property'             => $building->property_name,
                'tenant'               => $tenant->name,
                'flat'                 => $unit->unit_name,
                'contact_no'           => $tenant->phone,
                'available_datetime'   => $raised->copy()->addDays(2)->setTime(10, 0)->toDateTimeString(),
                'apartment_status'     => $unit->unit_condition === 'Furnished' ? 'furnished' : 'occupied',
                'status'               => $status,
                'job_lines'            => [[
                    'location'           => $location,
                    'description'        => $description,
                    'supervisor_comment' => $assessed ? 'Inspected on site; scope confirmed with the tenant.' : null,
                ]],
                'supervisor_name'      => $assessed ? $supervisor : null,
                'supervisor_datetime'  => $assessed ? $raised->copy()->addDays(2)->setTime(11, 30)->toDateTimeString() : null,
                'supervisor_signature' => $assessed ? ShowcasePhotos::signatureDataUri($supervisor) : null,
                'job_assessment'       => $assessed
                    ? 'Attended and inspected. ' . $description . '. Parts and labour quoted below; work can proceed once approved.'
                    : null,
                'quotation_1'          => $quotations[0] ?? null,
                'quotation_2'          => $quotations[1] ?? null,
                'quotation_3'          => $quotations[2] ?? null,
                'selected_quotation'   => $selected,
                'approved_supervisor'  => $approved ? $supervisor : null,
                'approved_dept_head'   => $approved ? $deptHead : null,
                'dept_head_signature'  => $approved ? ShowcasePhotos::signatureDataUri($deptHead) : null,
                'maintenance_remarks'  => match ($status) {
                    'completed'   => 'Work completed and signed off by the tenant.',
                    'in_progress' => 'Contractor on site; completion expected within the week.',
                    'cancelled'   => 'No fault found on re-inspection; request closed without cost.',
                    default       => null,
                },
            ]);
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /**
     * The app's document numbers are `PREFIX-0001`, sequential within the
     * prefix and unique-indexed. The real generators derive the prefix from
     * today's date, which would stamp a whole year of back-dated invoices with
     * one month's code; this keeps the same shape but numbers each document in
     * its own period.
     *
     * The first call for a prefix reads the highest number already issued
     * under it and carries on from there. Counting from 1 within the run is
     * not enough: this seeder runs against a database that already holds real
     * documents, and a back-dated invoice can land in a month that was
     * genuinely invoiced — which is exactly how INV-R-0726-0001 collided the
     * first time this was run for real.
     *
     * @param  class-string<Model>  $model
     */
    private function nextNumber(string $prefix, string $model, string $column): string
    {
        if (! array_key_exists($prefix, $this->sequences)) {
            $last = $model::where($column, 'like', $prefix . '-%')
                ->orderByDesc($column)
                ->value($column);

            $this->sequences[$prefix] = $last ? (int) substr((string) $last, -4) : 0;
        }

        $this->sequences[$prefix]++;

        return $prefix . '-' . str_pad((string) $this->sequences[$prefix], 4, '0', STR_PAD_LEFT);
    }

    /**
     * A named counter, for dealing from a fixed pattern instead of rolling a
     * weighted die. Returns 0 on first call, then 1, 2, … per name.
     */
    private function cursor(string $name): int
    {
        return $this->cursors[$name] = ($this->cursors[$name] ?? -1) + 1;
    }

    private function typeCode(string $type): string
    {
        return match ($type) {
            'rent'      => 'R',
            'utilities' => 'U',
            'other'     => 'O',
            default     => 'X',
        };
    }

    /** Never let a seeded date run past today: several forms refuse one. */
    private function cap(Carbon $date): Carbon
    {
        return $date->gt($this->today) ? $this->today->copy() : $date;
    }

    /**
     * Whoever the expense and revenue rows are attributed to. Prefers the
     * accounts user, since that is who books them, and falls back to any user
     * so the seeder still runs on an empty database.
     */
    private function authorId(): ?int
    {
        return \App\Models\User::where('role', 'accountant')->value('id')
            ?? \App\Models\User::where('email', 'realstateaccounts@promoseven.com')->value('id')
            ?? \App\Models\User::orderBy('id')->value('id');
    }

    private function report(): void
    {
        $building = Building::where('property_code', self::CODE)->first();

        if (! $building || ! $this->command) {
            return;
        }

        $units    = PropertyUnit::where('building_id', $building->id)->count();
        $occupied = $building->occupiedUnits()->count();

        $this->command->getOutput()->writeln([
            '',
            "  <info>{$building->property_name}</info> seeded — building #{$building->id}",
            '  ' . str_repeat('─', 58),
            sprintf('  %-26s %s', 'Floors / units', Floor::where('building_id', $building->id)->count() . " / {$units}"),
            sprintf('  %-26s %s', 'Occupied', "{$occupied} of {$units} (" . \App\Support\Occupancy::percent($occupied, $units) . '%)'),
            sprintf('  %-26s %s', 'Tenants', Tenant::where('tenant_code', 'like', self::TENANT_PREFIX . '%')->count()),
            sprintf('  %-26s %s', 'Leases', LeaseContract::where('property_code', self::CODE)->count()),
            sprintf('  %-26s %s', 'Invoices', Invoice::where('property_name', self::NAME)->count()),
            sprintf('  %-26s %s', 'Receipts', Payment::whereIn('invoice_id', Invoice::where('property_name', self::NAME)->select('id'))->count()),
            sprintf('  %-26s %s', 'EWA bills', EwaBill::where('property_name', self::NAME)->count()),
            sprintf('  %-26s %s', 'Expenses', Expense::where('building_id', $building->id)->count()),
            sprintf('  %-26s %s', 'Revenue entries', Revenue::where('building_id', $building->id)->count()),
            sprintf('  %-26s %s', 'Maintenance jobs', MaintenanceRequest::where('building_id', $building->id)->count()),
            sprintf('  %-26s %s', 'Photos', BuildingImage::where('building_id', $building->id)->count()),
            '',
        ]);
    }
}
