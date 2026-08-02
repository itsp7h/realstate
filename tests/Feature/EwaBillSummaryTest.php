<?php

namespace Tests\Feature;

use App\Models\EwaBill;
use App\Models\LeaseContract;
use App\Models\PropertyUnit;
use App\Services\EwaBillParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EwaBillSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function bindParser(array $parsed): void
    {
        $this->app->bind(EwaBillParser::class, fn () => new class($parsed) extends EwaBillParser {
            public function __construct(private array $parsed) {}
            public function parse(string $filePath): array
            {
                return $this->parsed;
            }
        });
    }

    private function validParsedData(array $overrides = []): array
    {
        return array_merge([
            'ewa_account_number'    => '1042387207',
            'issue_date'            => '2026-07-03',
            'due_date'              => now()->addDays(20)->format('Y-m-d'),
            'billing_period'        => 'June 2026',
            'reading_type'          => 'actual',
            'current_reading_type'  => 'actual',
            'previous_reading_type' => 'actual',
            'current_reading_date'  => '2026-06-30',
            'previous_reading_date' => '2026-05-31',
            'elec_prev_reading'     => '212754',
            'elec_curr_reading'     => '216015',
            'elec_consumption'      => '3261',
            'elec_charges'          => '105.350',
            'water_consumption'     => '17.544',
            'water_charges'         => '14.600',
            'municipality_fee'      => '35.000',
            'sanitary_fee'          => '2.720',
            'arrears'               => '0.000',
            'amount_due'            => '157.670',
            'account_holder_name'   => 'PROMOSEVEN HOLDINGS B.S.C. (CLOSED)',
            'flat_building_raw'     => 'Flat: 0043, Building: 204,',
            'address_raw'           => 'CAPITAL GOVERNORATE, AVENUE 0022,MANAMA / AL FATEH 0324',
        ], $overrides);
    }

    public function test_upload_page_renders_successfully(): void
    {
        $this->get(route('ewa-bills.summary.create'))
            ->assertStatus(200)
            ->assertSee('EWA Summary');
    }

    public function test_store_requires_at_least_one_file(): void
    {
        $this->post(route('ewa-bills.summary.store'), [])
            ->assertSessionHasErrors('files');
    }

    public function test_store_rejects_non_pdf_files(): void
    {
        $this->post(route('ewa-bills.summary.store'), [
            'files' => [UploadedFile::fake()->create('bill.txt', 10, 'text/plain')],
        ])->assertSessionHasErrors('files.0');

        $this->assertSame(0, EwaBill::count());
    }

    public function test_unmatched_account_creates_bill_with_fallback_tenant_and_flags_remark(): void
    {
        $this->bindParser($this->validParsedData());

        $response = $this->post(route('ewa-bills.summary.store'), [
            'files' => [UploadedFile::fake()->create('march-bill.pdf', 50, 'application/pdf')],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, EwaBill::count());

        $bill = EwaBill::first();
        $this->assertSame('PROMOSEVEN HOLDINGS B.S.C. (CLOSED)', $bill->tenant_name);
        $this->assertSame('issued', $bill->status);
        $this->assertEquals(119.950, (float) $bill->total_amount); // 105.350 + 14.600, muni fee excluded
        // With no unit or prior-bill match, property_name stays null rather than
        // being stuffed with the raw PDF address — the address is kept separately.
        $this->assertNull($bill->property_name);
        $this->assertSame('CAPITAL GOVERNORATE, AVENUE 0022,MANAMA / AL FATEH 0324', $bill->address);

        $this->followRedirects($response)
            ->assertStatus(200)
            ->assertSee('Created')
            ->assertSee('New account')
            // 105.350 (elec) + 14.600 (water) + 35.000 (muni) + 2.720 (sanitary), no cap/arrears
            // — matches the EWA invoice's own "Amount Due" figure.
            ->assertSee('157.670');
    }

    public function test_account_number_matching_the_units_electricity_ac_no_resolves_real_property_and_unit(): void
    {
        // The unit roster (property_units.electricity_ac_no) is the authoritative
        // source — it should win even on a brand-new account with no prior bill,
        // giving the real property/unit instead of falling back to raw PDF text.
        $unit = PropertyUnit::create([
            'property_name'     => 'Miknas Plaza 1',
            'property_code'     => 'MP1',
            'unit_name'         => 'MP1 - 11',
            'electricity_ac_no' => '1042387207',
        ]);

        $contract = LeaseContract::create([
            'date'               => '2024-01-01',
            'lease_agreement_no' => 'LA-EWA-UNIT-1',
            'tenant_name'        => 'Florissant Properties',
            'property_name'      => 'Miknas Plaza 1',
            'unit_id'            => $unit->id,
            'lease_start_date'   => now()->subYear()->format('Y-m-d'),
            'lease_end_date'     => now()->addYear()->format('Y-m-d'),
            'ewa_cap'            => 20.000,
        ]);

        $this->bindParser($this->validParsedData());

        $this->post(route('ewa-bills.summary.store'), [
            'files' => [UploadedFile::fake()->create('june-bill.pdf', 50, 'application/pdf')],
        ]);

        $bill = EwaBill::first();
        $this->assertSame('Miknas Plaza 1', $bill->property_name);
        $this->assertSame('Flat 11', $bill->unit);
        $this->assertSame('Florissant Properties', $bill->tenant_name);
        $this->assertSame($contract->id, $bill->lease_contract_id);
        $this->assertEquals(20.000, (float) $bill->ewa_cap);
        // The raw PDF address is preserved separately, never overwriting property_name.
        $this->assertSame('CAPITAL GOVERNORATE, AVENUE 0022,MANAMA / AL FATEH 0324', $bill->address);
    }

    public function test_matching_account_inherits_tenant_property_and_cap_from_existing_bill(): void
    {
        $contract = LeaseContract::create([
            'date'               => '2024-01-01',
            'lease_agreement_no' => 'LA-EWA-1',
            'tenant_name'        => 'Florissant Properties',
            'property_name'      => 'MP1',
            'lease_start_date'   => '2024-01-01',
            'lease_end_date'     => '2025-01-01',
            'ewa_cap'            => 20.000,
        ]);

        EwaBill::create([
            'bill_number'        => 'EWA-OLD-0001',
            'lease_contract_id'  => $contract->id,
            'tenant_name'        => 'Florissant Properties',
            'property_name'      => 'MP1',
            'unit'               => '11',
            'ewa_account_number' => '1042387207',
            'billing_period'     => 'May 2026',
            'reading_type'       => 'actual',
            'total_amount'       => 50.000,
            'due_date'           => now()->subMonth()->format('Y-m-d'),
            'status'             => 'paid',
        ]);

        $this->bindParser($this->validParsedData());

        $this->post(route('ewa-bills.summary.store'), [
            'files' => [UploadedFile::fake()->create('june-bill.pdf', 50, 'application/pdf')],
        ]);

        $this->assertSame(2, EwaBill::count());

        $newBill = EwaBill::where('billing_period', 'June 2026')->first();
        $this->assertNotNull($newBill);
        $this->assertSame('Florissant Properties', $newBill->tenant_name);
        $this->assertSame('MP1', $newBill->property_name);
        $this->assertSame('11', $newBill->unit);
        $this->assertSame($contract->id, $newBill->lease_contract_id);
        $this->assertEquals(20.000, (float) $newBill->ewa_cap);
        $this->assertEquals(99.950, (float) $newBill->tenant_portion); // 119.950 - 20 cap
    }

    public function test_reprocessing_same_account_and_period_updates_instead_of_duplicating(): void
    {
        // Exercised directly against the service (rather than two HTTP hits to
        // the same route) because Laravel's Route object caches its resolved
        // controller instance for the lifetime of the test's application
        // container — a second request to the same route would silently reuse
        // the first request's already-injected (and now stale-bound) parser.
        $this->bindParser($this->validParsedData());
        $service = $this->app->make(\App\Services\EwaBillBatchImportService::class);

        $service->importFile(UploadedFile::fake()->create('june-bill.pdf', 50, 'application/pdf'));
        $this->assertSame(1, EwaBill::count());

        // Re-upload the same bill (e.g. corrected PDF for the same account/period).
        $this->bindParser($this->validParsedData(['elec_charges' => '110.000']));
        $service = $this->app->make(\App\Services\EwaBillBatchImportService::class);

        $result = $service->importFile(UploadedFile::fake()->create('june-bill-corrected.pdf', 50, 'application/pdf'));

        $this->assertSame(1, EwaBill::count());
        $this->assertSame('updated', $result['status']);
        $this->assertEquals(110.000, (float) EwaBill::first()->elec_charges);
    }

    public function test_unparsable_pdf_is_reported_failed_and_not_saved(): void
    {
        $this->bindParser([
            'ewa_account_number' => null,
            'billing_period'     => null,
            'due_date'           => null,
            'reading_type'       => 'actual',
            'elec_charges'       => null,
            'water_charges'      => null,
        ]);

        $response = $this->post(route('ewa-bills.summary.store'), [
            'files' => [UploadedFile::fake()->create('unreadable.pdf', 50, 'application/pdf')],
        ]);

        $response->assertRedirect();
        $this->assertSame(0, EwaBill::count());

        $this->followRedirects($response)
            ->assertStatus(200)
            ->assertSee('Failed');
    }

    public function test_summary_shows_reading_type_for_mixed_actual_and_estimated_readings(): void
    {
        // Real EWA bills can have the current reading Actual while the
        // previous one was Estimated (or vice versa) — the summary must show
        // each independently rather than collapsing to a single status.
        $this->bindParser($this->validParsedData([
            'current_reading_type'  => 'actual',
            'previous_reading_type' => 'estimated',
        ]));

        $response = $this->post(route('ewa-bills.summary.store'), [
            'files' => [UploadedFile::fake()->create('mixed-bill.pdf', 50, 'application/pdf')],
        ]);

        $this->followRedirects($response)
            ->assertStatus(200)
            ->assertSee('Actual')
            ->assertSee('Estimated');
    }

    public function test_batch_results_are_persisted_to_the_database_not_a_ttl_cache(): void
    {
        $this->bindParser($this->validParsedData());

        $storeResponse = $this->post(route('ewa-bills.summary.store'), [
            'files' => [UploadedFile::fake()->create('march-bill.pdf', 50, 'application/pdf')],
        ]);

        $batchId = str($storeResponse->headers->get('Location'))->afterLast('/');

        $this->assertSame(1, \App\Models\EwaBillImportBatch::where('batch_id', $batchId)->count());

        // Fetching the batch works the same whether it's a minute old or a
        // week old — nothing here depends on a cache TTL, unlike the old
        // 30-minute Cache::put()-backed implementation.
        $this->get(route('ewa-bills.summary.show', $batchId))
            ->assertStatus(200)
            ->assertSee('Created');
    }

    public function test_show_404s_for_unknown_batch(): void
    {
        $this->get(route('ewa-bills.summary.show', 'does-not-exist'))
            ->assertStatus(404);
    }

    public function test_export_downloads_excel_for_batch(): void
    {
        $this->bindParser($this->validParsedData());

        $storeResponse = $this->post(route('ewa-bills.summary.store'), [
            'files' => [UploadedFile::fake()->create('march-bill.pdf', 50, 'application/pdf')],
        ]);

        $batchId = str($storeResponse->headers->get('Location'))->afterLast('/');

        $this->get(route('ewa-bills.summary.export', $batchId))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
