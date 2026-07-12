<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\PropertyUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class McpCompanyStatusServerTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/mcp/company-status';

    private const TOKEN = 'test-mcp-token';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mcp.token', self::TOKEN);
    }

    private function callTool(string $tool, array $arguments = [], ?string $token = self::TOKEN): \Illuminate\Testing\TestResponse
    {
        $headers = ['Accept' => 'application/json, text/event-stream'];

        if ($token !== null) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $this->withHeaders($headers)->postJson(self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => 'tools/call',
            'params'  => [
                'name'      => $tool,
                'arguments' => $arguments,
            ],
        ]);
    }

    public function test_request_without_token_is_rejected(): void
    {
        $response = $this->withHeaders(['Accept' => 'application/json, text/event-stream'])
            ->postJson(self::ENDPOINT, [
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'tools/call',
                'params'  => ['name' => 'property-overview', 'arguments' => []],
            ]);

        $response->assertStatus(401);
    }

    public function test_request_with_wrong_token_is_rejected(): void
    {
        $response = $this->callTool('property-overview', [], 'wrong-token');

        $response->assertStatus(401);
    }

    public function test_tools_list_returns_all_registered_tools(): void
    {
        $response = $this->withHeaders([
            'Accept'        => 'application/json, text/event-stream',
            'Authorization' => 'Bearer '.self::TOKEN,
        ])->postJson(self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id'      => 1,
            'method'  => 'tools/list',
        ]);

        $response->assertStatus(200);

        $names = array_column($response->json('result.tools'), 'name');

        $this->assertEqualsCanonicalizing([
            'maintenance-status',
            'leasing-status',
            'accounts-receivable-status',
            'property-overview',
        ], $names);
    }

    public function test_property_overview_tool_returns_portfolio_snapshot(): void
    {
        $building = Building::create([
            'property_name' => 'Test Tower',
            'property_code' => 'TT1',
        ]);

        PropertyUnit::create([
            'building_id'   => $building->id,
            'property_name' => 'Test Tower',
            'property_code' => 'TT1',
            'unit_name'     => 'Unit 1',
        ]);
        PropertyUnit::create([
            'building_id'   => $building->id,
            'property_name' => 'Test Tower',
            'property_code' => 'TT1',
            'unit_name'     => 'Unit 2',
        ]);

        $response = $this->callTool('property-overview');

        $response->assertStatus(200);

        $data = $response->json('result.structuredContent');

        $this->assertSame(1, $data['total_buildings']);
        $this->assertSame(2, $data['total_units']);
        $this->assertSame(0, $data['occupied_units']);
        $this->assertSame(2, $data['vacant_units']);
    }

    public function test_maintenance_status_tool_returns_counts_by_status(): void
    {
        $response = $this->callTool('maintenance-status');

        $response->assertStatus(200);

        $data = $response->json('result.structuredContent');

        $this->assertArrayHasKey('counts_by_status', $data);
        $this->assertArrayHasKey('open_requests', $data);
    }

    public function test_leasing_status_tool_returns_occupancy_summary(): void
    {
        $response = $this->callTool('leasing-status');

        $response->assertStatus(200);

        $data = $response->json('result.structuredContent');

        $this->assertArrayHasKey('total_units', $data);
        $this->assertArrayHasKey('occupancy_rate_percent', $data);
        $this->assertArrayHasKey('expiring_soon', $data);
    }

    public function test_accounts_receivable_status_tool_returns_outstanding_summary(): void
    {
        $response = $this->callTool('accounts-receivable-status');

        $response->assertStatus(200);

        $data = $response->json('result.structuredContent');

        $this->assertArrayHasKey('total_outstanding_bhd', $data);
        $this->assertArrayHasKey('top_tenants_by_balance', $data);
    }
}
