<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\MaintenanceRequest;
use App\Models\PropertyUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaintenanceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'date'               => '2026-05-21',
            'property'           => 'Tower A',
            'tenant'             => 'Ahmed Ali',
            'flat'               => '3B',
            'contact_no'         => '+973 3300 0000',
            'available_datetime' => '2026-05-22 10:00:00',
            'apartment_status'   => 'occupied',
        ], $overrides);
    }

    // ── INDEX ────────────────────────────────────────────────────────────────

    public function test_index_renders(): void
    {
        $this->get(route('maintenance.index'))->assertOk()->assertViewIs('maintenance.index');
    }

    public function test_index_shows_requests(): void
    {
        MaintenanceRequest::create($this->baseData(['job_order' => 'JO-ABC123']));
        $this->get(route('maintenance.index'))->assertSee('JO-ABC123');
    }

    public function test_index_filters_by_status(): void
    {
        MaintenanceRequest::create($this->baseData(['job_order' => 'JO-PENDING', 'status' => 'waiting_supervisor']));
        MaintenanceRequest::create($this->baseData(['job_order' => 'JO-DONE', 'status' => 'completed']));

        $this->get(route('maintenance.index', ['status' => 'waiting_supervisor']))
             ->assertSee('JO-PENDING')->assertDontSee('JO-DONE');
    }

    public function test_index_filters_by_search(): void
    {
        MaintenanceRequest::create($this->baseData(['property' => 'UniqueProperty999']));
        MaintenanceRequest::create($this->baseData(['property' => 'OtherBuilding']));

        $this->get(route('maintenance.index', ['search' => 'UniqueProperty999']))
             ->assertSee('UniqueProperty999')->assertDontSee('OtherBuilding');
    }

    // ── CREATE ───────────────────────────────────────────────────────────────

    public function test_create_page_renders(): void
    {
        $this->get(route('maintenance.create'))->assertOk()->assertViewIs('maintenance.create');
    }

    // ── STORE ────────────────────────────────────────────────────────────────

    public function test_store_creates_request(): void
    {
        $response = $this->post(route('maintenance.store'), $this->baseData());
        $response->assertRedirect();
        $this->assertDatabaseHas('maintenance_requests', ['property' => 'Tower A', 'tenant' => 'Ahmed Ali']);
    }

    public function test_store_auto_generates_job_order(): void
    {
        $this->post(route('maintenance.store'), $this->baseData());
        $record = MaintenanceRequest::first();
        $this->assertStringStartsWith('JO-', $record->job_order);
    }

    public function test_store_accepts_custom_job_order(): void
    {
        $this->post(route('maintenance.store'), $this->baseData(['job_order' => 'JO-CUSTOM']));
        $this->assertDatabaseHas('maintenance_requests', ['job_order' => 'JO-CUSTOM']);
    }

    public function test_store_defaults_status_to_waiting_supervisor(): void
    {
        $this->post(route('maintenance.store'), $this->baseData());
        $this->assertEquals('waiting_supervisor', MaintenanceRequest::first()->status);
    }

    public function test_store_saves_job_lines(): void
    {
        $data = $this->baseData([
            'job_lines' => [
                ['location' => 'Kitchen', 'description' => 'Leaking pipe', 'supervisor_comment' => ''],
            ],
        ]);
        $this->post(route('maintenance.store'), $data);
        $record = MaintenanceRequest::first();
        $this->assertEquals('Kitchen', $record->job_lines[0]['location']);
        $this->assertEquals('Leaking pipe', $record->job_lines[0]['description']);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $this->post(route('maintenance.store'), [])
             ->assertSessionHasErrors(['date', 'property', 'tenant', 'flat', 'contact_no', 'available_datetime', 'apartment_status']);
    }

    public function test_store_fails_with_invalid_apartment_status(): void
    {
        $this->post(route('maintenance.store'), $this->baseData(['apartment_status' => 'invalid']))
             ->assertSessionHasErrors('apartment_status');
    }

    public function test_store_fails_with_negative_quotation(): void
    {
        $this->post(route('maintenance.store'), $this->baseData(['quotation_1' => -10]))
             ->assertSessionHasErrors('quotation_1');
    }

    // ── SHOW ─────────────────────────────────────────────────────────────────

    public function test_show_renders(): void
    {
        $record = MaintenanceRequest::create($this->baseData(['job_order' => 'JO-SHOW1']));
        $this->get(route('maintenance.show', $record))->assertOk()->assertSee('JO-SHOW1');
    }

    public function test_show_returns_404_for_missing_record(): void
    {
        $this->get(route('maintenance.show', 9999))->assertNotFound();
    }

    // ── EDIT / UPDATE ────────────────────────────────────────────────────────

    public function test_edit_renders_prefilled(): void
    {
        $record = MaintenanceRequest::create($this->baseData(['tenant' => 'Edit Tenant']));
        $this->get(route('maintenance.edit', $record))->assertOk()->assertSee('Edit Tenant');
    }

    public function test_update_modifies_record(): void
    {
        $record = MaintenanceRequest::create($this->baseData());
        $this->put(route('maintenance.update', $record), $this->baseData([
            'tenant' => 'Updated Tenant',
            'status' => 'in_progress',
        ]));
        $this->assertDatabaseHas('maintenance_requests', ['id' => $record->id, 'tenant' => 'Updated Tenant', 'status' => 'in_progress']);
    }

    public function test_update_fails_with_invalid_status(): void
    {
        $record = MaintenanceRequest::create($this->baseData());
        $this->put(route('maintenance.update', $record), $this->baseData(['status' => 'invalid']))
             ->assertSessionHasErrors('status');
    }

    // ── DESTROY ──────────────────────────────────────────────────────────────

    public function test_destroy_deletes_record(): void
    {
        $record = MaintenanceRequest::create($this->baseData());
        $this->delete(route('maintenance.destroy', $record))->assertRedirect(route('maintenance.index'));
        $this->assertDatabaseMissing('maintenance_requests', ['id' => $record->id]);
    }

    // ── APPROVAL WORKFLOW ────────────────────────────────────────────────────

    public function test_assess_transitions_to_waiting_approval(): void
    {
        $record = MaintenanceRequest::create($this->baseData(['status' => 'waiting_supervisor']));
        $this->post(route('maintenance.assess', $record), [
            'supervisor_name'      => 'Ahmed Supervisor',
            'supervisor_datetime'  => '2026-06-02 10:00:00',
            'quotation_1'          => '150.000',
            'selected_quotation'   => 1,
            'supervisor_signature' => 'data:image/png;base64,abc123',
        ]);
        $record->refresh();
        $this->assertEquals('waiting_approval', $record->status);
        $this->assertEquals('Ahmed Supervisor', $record->supervisor_name);
        $this->assertEquals(1, $record->selected_quotation);
    }

    public function test_assess_requires_supervisor_name_and_datetime(): void
    {
        $record = MaintenanceRequest::create($this->baseData(['status' => 'waiting_supervisor']));
        $this->post(route('maintenance.assess', $record), [])
             ->assertSessionHasErrors(['supervisor_name', 'supervisor_datetime', 'selected_quotation', 'supervisor_signature']);
    }

    public function test_assess_rejects_invalid_quotation_number(): void
    {
        $record = MaintenanceRequest::create($this->baseData(['status' => 'waiting_supervisor']));
        $this->post(route('maintenance.assess', $record), [
            'supervisor_name'      => 'Ahmed Supervisor',
            'supervisor_datetime'  => '2026-06-02 10:00:00',
            'selected_quotation'   => 5,
            'supervisor_signature' => 'data:image/png;base64,abc123',
        ])->assertSessionHasErrors('selected_quotation');
    }

    public function test_approve_transitions_to_approved(): void
    {
        $record = MaintenanceRequest::create($this->baseData(['status' => 'waiting_approval']));
        $this->post(route('maintenance.approve', $record), [
            'approved_dept_head'  => 'Abitsam',
            'dept_head_signature' => 'data:image/png;base64,abc123',
        ]);
        $record->refresh();
        $this->assertEquals('approved', $record->status);
        $this->assertEquals('Abitsam', $record->approved_dept_head);
    }

    public function test_approve_requires_dept_head_signature(): void
    {
        $record = MaintenanceRequest::create($this->baseData(['status' => 'waiting_approval']));
        $this->post(route('maintenance.approve', $record), [])
             ->assertSessionHasErrors('dept_head_signature');
    }

    // ── QUOTATION FILE ATTACHMENTS ───────────────────────────────────────────

    public function test_store_uploads_quotation_file(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('quote1.pdf', 100, 'application/pdf');

        $this->post(route('maintenance.store'), $this->baseData(['quotation_1_file' => $file]));

        $record = MaintenanceRequest::first();
        $this->assertNotNull($record->quotation_1_file);
        Storage::disk('public')->assertExists($record->quotation_1_file);
    }

    public function test_store_rejects_invalid_quotation_file_type(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('malware.exe', 50, 'application/octet-stream');

        $this->post(route('maintenance.store'), $this->baseData(['quotation_1_file' => $file]))
             ->assertSessionHasErrors('quotation_1_file');
    }

    public function test_update_replaces_quotation_file(): void
    {
        Storage::fake('public');
        $old = UploadedFile::fake()->create('old.pdf', 50, 'application/pdf');
        $new = UploadedFile::fake()->create('new.pdf', 60, 'application/pdf');

        $this->post(route('maintenance.store'), $this->baseData(['quotation_1_file' => $old]));
        $record   = MaintenanceRequest::first();
        $oldPath  = $record->quotation_1_file;

        $this->put(route('maintenance.update', $record), $this->baseData(['quotation_1_file' => $new]));
        $record->refresh();

        $this->assertNotEquals($oldPath, $record->quotation_1_file);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($record->quotation_1_file);
    }

    public function test_update_remove_flag_deletes_quotation_file(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('quote.pdf', 50, 'application/pdf');

        $this->post(route('maintenance.store'), $this->baseData(['quotation_1_file' => $file]));
        $record = MaintenanceRequest::first();
        $path   = $record->quotation_1_file;

        $this->put(route('maintenance.update', $record), $this->baseData(['remove_quotation_1_file' => '1']));
        $record->refresh();

        $this->assertNull($record->quotation_1_file);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_destroy_deletes_quotation_files(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('quote.pdf', 50, 'application/pdf');

        $this->post(route('maintenance.store'), $this->baseData(['quotation_1_file' => $file]));
        $record = MaintenanceRequest::first();
        $path   = $record->quotation_1_file;

        $this->delete(route('maintenance.destroy', $record));
        Storage::disk('public')->assertMissing($path);
    }

    // ── BUILDING / UNIT RESOLUTION ─────────────────────────────────────────────

    public function test_store_resolves_building_and_unit_id_from_matching_names(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $unit = PropertyUnit::create([
            'building_id'   => $building->id,
            'property_name' => 'Tower A',
            'property_code' => 'TA1',
            'unit_name'     => '3B',
        ]);

        $this->post(route('maintenance.store'), $this->baseData(['property' => 'tower a', 'flat' => '3b']));

        $record = MaintenanceRequest::first();
        $this->assertEquals($building->id, $record->building_id);
        $this->assertEquals($unit->id, $record->unit_id);
    }

    public function test_store_leaves_building_and_unit_id_null_when_no_match(): void
    {
        $this->post(route('maintenance.store'), $this->baseData(['property' => 'Nonexistent Tower', 'flat' => '9Z']));

        $record = MaintenanceRequest::first();
        $this->assertNull($record->building_id);
        $this->assertNull($record->unit_id);
    }

    public function test_update_re_resolves_building_and_unit_id(): void
    {
        $record = MaintenanceRequest::create($this->baseData(['property' => 'Nonexistent Tower']));
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);
        $unit = PropertyUnit::create([
            'building_id'   => $building->id,
            'property_name' => 'Tower A',
            'property_code' => 'TA1',
            'unit_name'     => '3B',
        ]);

        $this->put(route('maintenance.update', $record), $this->baseData(['property' => 'Tower A', 'flat' => '3B']));

        $record->refresh();
        $this->assertEquals($building->id, $record->building_id);
        $this->assertEquals($unit->id, $record->unit_id);
    }

    // ── AUDIT LOG ────────────────────────────────────────────────────────────

    public function test_creating_request_writes_audit_log(): void
    {
        $this->post(route('maintenance.store'), $this->baseData(['job_order' => 'JO-AUDIT1']));
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'created',
            'entity_type' => 'MaintenanceRequest',
        ]);
    }
}
