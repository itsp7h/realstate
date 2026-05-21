<?php

namespace Tests\Feature;

use App\Models\LeaseContract;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LeaseContractImportTest extends TestCase
{
    use RefreshDatabase;

    // ── TEMPLATE DOWNLOAD ────────────────────────────────────────

    public function test_template_csv_downloads_successfully(): void
    {
        $response = $this->get(route('import.template', ['type' => 'contracts', 'format' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Lease Agreement No', $content);
        $this->assertStringContainsString('Tenant Name', $content);
        $this->assertStringContainsString('Lease Start Date', $content);
        $this->assertStringContainsString('Lease End Date', $content);
        $this->assertStringContainsString('Rent per Month', $content);
        $this->assertStringContainsString('Service Amount in BD (Excl. VAT)', $content);
    }

    public function test_template_xlsx_downloads_successfully(): void
    {
        $response = $this->get(route('import.template', ['type' => 'contracts', 'format' => 'xlsx']));

        $response->assertStatus(200);
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename=import-contracts-template.xlsx'
        );
    }

    public function test_template_has_all_26_columns(): void
    {
        $response = $this->get(route('import.template', ['type' => 'contracts', 'format' => 'csv']));
        $content  = $response->streamedContent();
        $lines    = array_filter(explode("\n", trim($content)));
        $headers  = str_getcsv(reset($lines));

        $this->assertCount(26, $headers);
    }

    // ── IMPORT ───────────────────────────────────────────────────

    private function makeCsv(array $headers, array ...$rows): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        $h   = fopen($tmp, 'w');
        fputcsv($h, $headers);
        foreach ($rows as $row) {
            fputcsv($h, $row);
        }
        fclose($h);

        return new UploadedFile($tmp, 'contracts.csv', 'text/csv', null, true);
    }

    private function baseHeaders(): array
    {
        return [
            'Date', 'Lease Agreement No', 'Tenant Name',
            'Property Name', 'Prop Code', 'Block Name', 'Block Code', 'Floor Name', 'Floor Code',
            'Unit', 'Description',
            'Lease Start Date', 'Lease End Date',
            'Rental Income Ledger', 'Invoicing Frequency',
            'Rent Start Date', 'Rent End Date', 'Currency', 'Rent per Month',
            'Service Frequency', 'Service Start Date', 'Service End Date',
            'Service Amount in BD (Excl. VAT)',
            'Security Deposit', 'Lease Break Date', 'Notice Period',
        ];
    }

    private function baseRow(array $overrides = []): array
    {
        return array_values(array_merge([
            'date'                    => '2025-03-01',
            'lease_agreement_no'      => 'LA/0001',
            'tenant_name'             => 'Ahmed Al-Khalifa',
            'property_name'           => 'P7H Muharraq Bldg. 2',
            'property_code'           => 'P7H-1130N',
            'block_name'              => 'Block 1',
            'block_code'              => 'BL1',
            'floor_name'              => 'Floor 1',
            'floor_code'              => 'FL1',
            'unit'                    => '1130N-F1-110',
            'description'             => 'Fitted',
            'lease_start_date'        => '2025-03-01',
            'lease_end_date'          => '2026-02-28',
            'rental_income_ledger'    => '41010011',
            'invoicing_frequency'     => 'Monthly',
            'rent_start_date'         => '2025-03-01',
            'rent_end_date'           => '2026-02-28',
            'currency'                => 'BHD',
            'rent_per_month'          => '450.000',
            'service_frequency'       => 'Monthly',
            'service_start_date'      => '2025-03-01',
            'service_end_date'        => '2026-02-28',
            'service_amount_bd_excl_vat' => '50.000',
            'security_deposit'        => '900.000',
            'lease_break_date'        => '2026-02-28',
            'notice_period'           => '1 Month',
        ], $overrides));
    }

    public function test_import_creates_contract_from_csv(): void
    {
        $file = $this->makeCsv($this->baseHeaders(), $this->baseRow());

        $this->post(route('import.contracts'), ['file' => $file]);

        $contract = LeaseContract::where('lease_agreement_no', 'LA/0001')->first();
        $this->assertNotNull($contract);
        $this->assertEquals('Ahmed Al-Khalifa', $contract->tenant_name);
        $this->assertEquals('P7H-1130N', $contract->property_code);
        $this->assertEquals('2025-03-01', $contract->lease_start_date->format('Y-m-d'));
        $this->assertEquals('2026-02-28', $contract->lease_end_date->format('Y-m-d'));
        $this->assertEquals(450.0, (float) $contract->rent_per_month);
    }

    public function test_import_links_existing_tenant_by_name(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);

        $file = $this->makeCsv($this->baseHeaders(), $this->baseRow());
        $this->post(route('import.contracts'), ['file' => $file]);

        $contract = LeaseContract::where('lease_agreement_no', 'LA/0001')->first();
        $this->assertNotNull($contract);
        $this->assertEquals($tenant->id, $contract->tenant_id);
    }

    public function test_import_stores_contract_without_tenant_match(): void
    {
        // No tenants in DB — contract should still be saved with null tenant_id
        $file = $this->makeCsv($this->baseHeaders(), $this->baseRow());
        $this->post(route('import.contracts'), ['file' => $file]);

        $contract = LeaseContract::where('lease_agreement_no', 'LA/0001')->first();
        $this->assertNotNull($contract);
        $this->assertNull($contract->tenant_id);
        $this->assertEquals('Ahmed Al-Khalifa', $contract->tenant_name);
    }

    public function test_import_skips_duplicate_agreement_number(): void
    {
        LeaseContract::create(array_merge(
            array_combine(
                ['date','lease_agreement_no','tenant_name','lease_start_date','lease_end_date'],
                ['2025-01-01','LA/0001','Existing','2025-01-01','2025-12-31']
            )
        ));

        $file = $this->makeCsv($this->baseHeaders(), $this->baseRow());
        $this->post(route('import.contracts'), ['file' => $file]);

        // Still only 1 record
        $this->assertDatabaseCount('lease_contracts', 1);
    }

    public function test_import_skips_row_missing_required_fields(): void
    {
        $row = $this->baseRow(['lease_agreement_no' => '']);

        $file = $this->makeCsv($this->baseHeaders(), $row);
        $this->post(route('import.contracts'), ['file' => $file]);

        $this->assertDatabaseCount('lease_contracts', 0);
    }

    public function test_import_processes_multiple_rows(): void
    {
        $row1 = $this->baseRow(['lease_agreement_no' => 'LA/0001']);
        $row2 = $this->baseRow(['lease_agreement_no' => 'LA/0002', 'tenant_name' => 'Zahra Investments']);

        $file = $this->makeCsv($this->baseHeaders(), $row1, $row2);
        $this->post(route('import.contracts'), ['file' => $file]);

        $this->assertDatabaseCount('lease_contracts', 2);
        $this->assertDatabaseHas('lease_contracts', ['lease_agreement_no' => 'LA/0001']);
        $this->assertDatabaseHas('lease_contracts', ['lease_agreement_no' => 'LA/0002']);
    }

    public function test_import_rejects_missing_file(): void
    {
        $response = $this->post(route('import.contracts'), []);
        $response->assertSessionHasErrors(['file']);
    }

    public function test_import_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('contracts.pdf', 100, 'application/pdf');
        $response = $this->post(route('import.contracts'), ['file' => $file]);
        $response->assertSessionHasErrors(['file']);
    }

    public function test_import_rejects_file_missing_required_columns(): void
    {
        // Upload a CSV that is missing "Lease Agreement No"
        $headers = ['Tenant Name', 'Lease Start Date', 'Lease End Date'];
        $file    = $this->makeCsv($headers, ['Ahmed', '2025-01-01', '2025-12-31']);

        $this->post(route('import.contracts'), ['file' => $file]);

        $this->assertDatabaseCount('lease_contracts', 0);
    }
}
