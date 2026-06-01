<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogsTest extends TestCase
{
    use RefreshDatabase;

    // ── AUDIT LOG PAGE ───────────────────────────────────────────────────────

    public function test_audit_log_page_renders(): void
    {
        $response = $this->get(route('admin.audit-log'));
        $response->assertOk()->assertViewIs('admin.audit-log');
    }

    public function test_audit_log_shows_entries(): void
    {
        AuditLog::create([
            'action'      => 'created',
            'entity_type' => 'Building',
            'entity_id'   => 1,
            'entity_name' => 'Tower A',
            'ip_address'  => '127.0.0.1',
        ]);

        $response = $this->get(route('admin.audit-log'));
        $response->assertOk()->assertSee('Tower A');
    }

    public function test_audit_log_filters_by_action(): void
    {
        AuditLog::create(['action' => 'created', 'entity_type' => 'Building', 'entity_name' => 'Alpha']);
        AuditLog::create(['action' => 'deleted', 'entity_type' => 'Tenant',   'entity_name' => 'Beta']);

        $response = $this->get(route('admin.audit-log', ['action' => 'created']));
        $response->assertSee('Alpha')->assertDontSee('Beta');
    }

    public function test_audit_log_filters_by_entity_type(): void
    {
        AuditLog::create(['action' => 'created', 'entity_type' => 'Building', 'entity_name' => 'TowerX']);
        AuditLog::create(['action' => 'created', 'entity_type' => 'Tenant',   'entity_name' => 'PersonY']);

        $response = $this->get(route('admin.audit-log', ['entity_type' => 'Building']));
        $response->assertSee('TowerX')->assertDontSee('PersonY');
    }

    public function test_audit_log_filters_by_search(): void
    {
        AuditLog::create(['action' => 'created', 'entity_type' => 'Building', 'entity_name' => 'UniqueSearch123']);
        AuditLog::create(['action' => 'created', 'entity_type' => 'Tenant',   'entity_name' => 'OtherName']);

        $response = $this->get(route('admin.audit-log', ['search' => 'UniqueSearch123']));
        $response->assertSee('UniqueSearch123')->assertDontSee('OtherName');
    }

    public function test_audit_log_clear_truncates_table(): void
    {
        AuditLog::create(['action' => 'created', 'entity_type' => 'Building', 'entity_name' => 'X']);
        $this->assertDatabaseCount('audit_logs', 1);

        $response = $this->delete(route('admin.audit-log.clear'));
        $response->assertRedirect(route('admin.audit-log'));
        $this->assertDatabaseCount('audit_logs', 0);
    }

    // ── ERROR LOG PAGE ───────────────────────────────────────────────────────

    public function test_error_log_page_renders(): void
    {
        $response = $this->get(route('admin.error-log'));
        $response->assertOk()->assertViewIs('admin.error-log');
    }

    public function test_error_log_clear_empties_file(): void
    {
        $logFile = storage_path('logs/laravel.log');
        file_put_contents($logFile, '[2026-05-21 10:00:00] local.ERROR: test error {} []');

        $response = $this->delete(route('admin.error-log.clear'));
        $response->assertRedirect(route('admin.error-log'));
        $this->assertEmpty(file_get_contents($logFile));
    }

    // ── AUDITABLE TRAIT ──────────────────────────────────────────────────────

    public function test_creating_a_building_writes_audit_log(): void
    {
        Building::create(['property_name' => 'Test Tower', 'property_code' => 'TT001']);
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'created',
            'entity_type' => 'Building',
            'entity_name' => 'Test Tower',
        ]);
    }

    public function test_updating_a_building_writes_audit_log(): void
    {
        $building = Building::create(['property_name' => 'Old Name', 'property_code' => 'ON001']);
        $building->update(['property_name' => 'New Name']);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'updated',
            'entity_type' => 'Building',
            'entity_name' => 'New Name',
        ]);
    }

    public function test_deleting_a_building_writes_audit_log(): void
    {
        $building = Building::create(['property_name' => 'Deleted Tower', 'property_code' => 'DT001']);
        $building->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'deleted',
            'entity_type' => 'Building',
            'entity_name' => 'Deleted Tower',
        ]);
    }

    public function test_creating_a_tenant_writes_audit_log(): void
    {
        Tenant::create(['name' => 'John Doe', 'tenant_type' => 'individual']);
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'created',
            'entity_type' => 'Tenant',
            'entity_name' => 'John Doe',
        ]);
    }

    // ── AUDIT LOG RECORD HELPER ──────────────────────────────────────────────

    public function test_audit_log_record_creates_entry(): void
    {
        AuditLog::record('imported', 'LeaseContract', null, '5 row(s)');
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'imported',
            'entity_type' => 'LeaseContract',
            'entity_name' => '5 row(s)',
        ]);
    }

    public function test_audit_log_record_stores_changes(): void
    {
        AuditLog::record('updated', 'Building', 1, 'Tower A', [
            'property_name' => ['from' => 'Old', 'to' => 'New'],
        ]);

        $log = AuditLog::first();
        $this->assertEquals(['property_name' => ['from' => 'Old', 'to' => 'New']], $log->changes);
    }
}
