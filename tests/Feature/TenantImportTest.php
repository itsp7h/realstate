<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TenantImportTest extends TestCase
{
    use RefreshDatabase;

    // ── TEMPLATE DOWNLOAD ────────────────────────────────────────

    public function test_tenant_template_csv_downloads_successfully(): void
    {
        $response = $this->get(route('import.template', ['type' => 'tenants', 'format' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Name', $content);
        $this->assertStringContainsString('Tenant Type', $content);
        $this->assertStringContainsString('Email', $content);
    }

    public function test_tenant_template_xlsx_downloads_successfully(): void
    {
        $response = $this->get(route('import.template', ['type' => 'tenants', 'format' => 'xlsx']));

        $response->assertStatus(200);
        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename=import-tenants-template.xlsx'
        );
    }

    public function test_tenant_template_has_6_columns(): void
    {
        $response = $this->get(route('import.template', ['type' => 'tenants', 'format' => 'csv']));
        $content  = $response->streamedContent();
        $lines    = array_filter(explode("\n", trim($content)));
        $headers  = str_getcsv(reset($lines));

        $this->assertCount(6, $headers);
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

        return new UploadedFile($tmp, 'tenants.csv', 'text/csv', null, true);
    }

    private function baseHeaders(): array
    {
        return ['Name', 'Tenant Type', 'ID / CR Number', 'Phone', 'Email', 'Nationality / Country'];
    }

    private function baseRow(array $overrides = []): array
    {
        return array_values(array_merge([
            'name'                 => 'Ahmed Al-Khalifa',
            'tenant_type'          => 'individual',
            'id_cr_number'         => '840912345',
            'phone'                => '+973 3300 0000',
            'email'                => 'ahmed@email.com',
            'nationality_country'  => 'Bahraini',
        ], $overrides));
    }

    public function test_import_creates_tenant_from_csv(): void
    {
        $file = $this->makeCsv($this->baseHeaders(), $this->baseRow());

        $response = $this->post(route('import.tenants'), ['file' => $file]);

        $response->assertRedirect(route('tenants.index'));
        $response->assertSessionHas('import_count', 1);

        $tenant = Tenant::where('name', 'Ahmed Al-Khalifa')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('individual', $tenant->tenant_type);
        $this->assertEquals('840912345', $tenant->id_cr_number);
        $this->assertEquals('+973 3300 0000', $tenant->phone);
        $this->assertEquals('ahmed@email.com', $tenant->email);
        $this->assertEquals('Bahraini', $tenant->nationality_country);
    }

    public function test_import_defaults_tenant_type_to_individual(): void
    {
        $row  = $this->baseRow(['tenant_type' => '']);
        $file = $this->makeCsv($this->baseHeaders(), $row);

        $this->post(route('import.tenants'), ['file' => $file]);

        $this->assertEquals('individual', Tenant::where('name', 'Ahmed Al-Khalifa')->value('tenant_type'));
    }

    public function test_import_accepts_company_type(): void
    {
        $row  = $this->baseRow(['name' => 'Acme Corp', 'tenant_type' => 'company']);
        $file = $this->makeCsv($this->baseHeaders(), $row);

        $this->post(route('import.tenants'), ['file' => $file]);

        $this->assertEquals('company', Tenant::where('name', 'Acme Corp')->value('tenant_type'));
    }

    public function test_import_normalises_invalid_tenant_type_to_individual(): void
    {
        $row  = $this->baseRow(['tenant_type' => 'CORPORATE']);
        $file = $this->makeCsv($this->baseHeaders(), $row);

        $this->post(route('import.tenants'), ['file' => $file]);

        $this->assertEquals('individual', Tenant::where('name', 'Ahmed Al-Khalifa')->value('tenant_type'));
    }

    public function test_import_skips_duplicate_name(): void
    {
        Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);

        $file = $this->makeCsv($this->baseHeaders(), $this->baseRow());

        $response = $this->post(route('import.tenants'), ['file' => $file]);

        $response->assertSessionHas('import_count', 0);
        $this->assertDatabaseCount('tenants', 1);
    }

    public function test_import_skips_row_missing_name(): void
    {
        $row  = $this->baseRow(['name' => '']);
        $file = $this->makeCsv($this->baseHeaders(), $row);

        $this->post(route('import.tenants'), ['file' => $file]);

        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_import_processes_multiple_rows(): void
    {
        $row1 = $this->baseRow(['name' => 'Tenant A']);
        $row2 = $this->baseRow(['name' => 'Tenant B', 'tenant_type' => 'company']);

        $file = $this->makeCsv($this->baseHeaders(), $row1, $row2);

        $response = $this->post(route('import.tenants'), ['file' => $file]);

        $response->assertSessionHas('import_count', 2);
        $this->assertDatabaseCount('tenants', 2);
        $this->assertDatabaseHas('tenants', ['name' => 'Tenant A']);
        $this->assertDatabaseHas('tenants', ['name' => 'Tenant B', 'tenant_type' => 'company']);
    }

    public function test_import_rejects_missing_file(): void
    {
        $response = $this->post(route('import.tenants'), []);
        $response->assertSessionHasErrors(['file']);
    }

    public function test_import_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('tenants.pdf', 100, 'application/pdf');
        $response = $this->post(route('import.tenants'), ['file' => $file]);
        $response->assertSessionHasErrors(['file']);
    }

    public function test_import_rejects_file_missing_required_columns(): void
    {
        $headers = ['Email', 'Phone'];
        $file    = $this->makeCsv($headers, ['a@b.com', '123']);

        $this->post(route('import.tenants'), ['file' => $file]);

        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_import_handles_bom_prefixed_csv(): void
    {
        // Excel saves CSV files with a UTF-8 BOM (\xEF\xBB\xBF) at the start
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, "\xEF\xBB\xBF" . "Name,Tenant Type,ID / CR Number,Phone,Email,Nationality / Country\r\nBOM Tenant,individual,,,, \r\n");
        $file = new UploadedFile($tmp, 'tenants.csv', 'text/csv', null, true);

        $response = $this->post(route('import.tenants'), ['file' => $file]);

        $response->assertSessionHas('import_count', 1);
        $this->assertDatabaseHas('tenants', ['name' => 'BOM Tenant']);
    }

    // ── EXPORT ───────────────────────────────────────────────────

    public function test_export_tenants_returns_xlsx(): void
    {
        Tenant::create(['name' => 'Export Tenant', 'tenant_type' => 'individual']);

        $response = $this->get(route('export.tenants'));

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'tenants-',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_export_tenants_respects_search_filter(): void
    {
        Tenant::create(['name' => 'Alpha Corp', 'tenant_type' => 'company']);
        Tenant::create(['name' => 'Beta LLC',   'tenant_type' => 'company']);

        $response = $this->get(route('export.tenants', ['search' => 'Alpha']));

        $response->assertStatus(200);
    }
}
