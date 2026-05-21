<?php

namespace Tests\Feature;

use App\Models\LeaseContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseContractExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeContract(array $overrides = []): LeaseContract
    {
        return LeaseContract::create(array_merge([
            'date'               => '2025-01-01',
            'lease_agreement_no' => 'LA/TEST-' . uniqid(),
            'tenant_name'        => 'Test Tenant',
            'property_code'      => 'PROP001',
            'lease_start_date'   => '2025-01-01',
            'lease_end_date'     => '2026-01-01',
        ], $overrides));
    }

    public function test_export_contracts_returns_xlsx(): void
    {
        $this->makeContract();

        $response = $this->get(route('export.contracts'));

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'lease-contracts-',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_export_contracts_respects_property_code_filter(): void
    {
        $this->makeContract(['property_code' => 'PROP001']);
        $this->makeContract(['property_code' => 'PROP002']);

        $response = $this->get(route('export.contracts', ['property_code' => 'PROP001']));

        $response->assertStatus(200);
    }

    public function test_export_contracts_with_search_filter(): void
    {
        $this->makeContract(['tenant_name' => 'Ahmed Al-Khalifa']);
        $this->makeContract(['tenant_name' => 'Fatima Investments']);

        $response = $this->get(route('export.contracts', ['search' => 'Ahmed']));

        $response->assertStatus(200);
    }

    public function test_export_empty_contracts_returns_xlsx(): void
    {
        $response = $this->get(route('export.contracts'));

        $response->assertStatus(200);
    }
}
