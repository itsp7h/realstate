<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    // ── INDEX ────────────────────────────────────────────────────

    public function test_index_renders_successfully(): void
    {
        $response = $this->get(route('tenants.index'));
        $response->assertStatus(200);
        $response->assertViewIs('tenants.index');
    }

    public function test_index_shows_tenants(): void
    {
        Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->get(route('tenants.index'))
            ->assertSee('Ahmed Al-Khalifa');
    }

    public function test_index_filters_by_search(): void
    {
        Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        Tenant::create(['name' => 'Zahra Investments',  'tenant_type' => 'company']);

        $response = $this->get(route('tenants.index', ['search' => 'Zahra']));
        $tenants  = $response->viewData('tenants');

        $this->assertCount(1, $tenants);
        $this->assertEquals('Zahra Investments', $tenants->first()->name);
    }

    public function test_index_filters_by_tenant_type(): void
    {
        Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        Tenant::create(['name' => 'Zahra Investments',  'tenant_type' => 'company']);

        $response = $this->get(route('tenants.index', ['tenant_type' => 'company']));
        $tenants  = $response->viewData('tenants');

        $this->assertCount(1, $tenants);
        $this->assertEquals('Zahra Investments', $tenants->first()->name);
    }

    // ── STORE ────────────────────────────────────────────────────

    public function test_store_creates_tenant_with_minimal_fields(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);
    }

    public function test_store_creates_tenant_with_all_fields(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'                => 'Zahra Investments W.L.L.',
            'tenant_type'         => 'company',
            'id_cr_number'        => 'CR-12345',
            'phone'               => '+973 1700 0000',
            'email'               => 'info@zahra.bh',
            'nationality_country' => 'Bahrain',
        ]);

        $response->assertRedirect(route('tenants.index'));
        $this->assertDatabaseHas('tenants', [
            'name'                => 'Zahra Investments W.L.L.',
            'tenant_type'         => 'company',
            'id_cr_number'        => 'CR-12345',
            'email'               => 'info@zahra.bh',
        ]);
    }

    public function test_store_fails_without_name(): void
    {
        $response = $this->post(route('tenants.store'), [
            'tenant_type' => 'individual',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_store_fails_without_tenant_type(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name' => 'Ahmed Al-Khalifa',
        ]);

        $response->assertSessionHasErrors(['tenant_type']);
    }

    public function test_store_fails_with_invalid_tenant_type(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'partnership',
        ]);

        $response->assertSessionHasErrors(['tenant_type']);
    }

    public function test_store_fails_with_invalid_email(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
            'email'       => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_fails_with_name_exceeding_max_length(): void
    {
        $response = $this->post(route('tenants.store'), [
            'name'        => str_repeat('A', 256),
            'tenant_type' => 'individual',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    // ── SHOW ─────────────────────────────────────────────────────

    public function test_show_displays_tenant_profile(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
            'email'       => 'ahmed@example.com',
        ]);

        $this->get(route('tenants.show', $tenant))
            ->assertStatus(200)
            ->assertViewIs('tenants.show')
            ->assertSee('Ahmed Al-Khalifa')
            ->assertSee('ahmed@example.com');
    }

    public function test_show_returns_404_for_missing_tenant(): void
    {
        $this->get(route('tenants.show', 999))
            ->assertStatus(404);
    }

    // ── EDIT / UPDATE ────────────────────────────────────────────

    public function test_edit_renders_form_with_existing_values(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->get(route('tenants.edit', $tenant))
            ->assertStatus(200)
            ->assertViewIs('tenants.edit')
            ->assertSee('Ahmed Al-Khalifa');
    }

    public function test_update_saves_changes(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->put(route('tenants.update', $tenant), [
            'name'        => 'Ahmed Al-Khalifa Jr.',
            'tenant_type' => 'individual',
            'phone'       => '+973 3300 1234',
        ])->assertRedirect(route('tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'id'    => $tenant->id,
            'name'  => 'Ahmed Al-Khalifa Jr.',
            'phone' => '+973 3300 1234',
        ]);
    }

    public function test_update_fails_without_name(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->put(route('tenants.update', $tenant), [
            'tenant_type' => 'company',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_update_fails_with_invalid_email(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->put(route('tenants.update', $tenant), [
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
            'email'       => 'bad-email',
        ])->assertSessionHasErrors(['email']);
    }

    // ── DESTROY ──────────────────────────────────────────────────

    public function test_destroy_deletes_tenant(): void
    {
        $tenant = Tenant::create([
            'name'        => 'Ahmed Al-Khalifa',
            'tenant_type' => 'individual',
        ]);

        $this->delete(route('tenants.destroy', $tenant))
            ->assertRedirect(route('tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_destroy_returns_404_for_missing_tenant(): void
    {
        $this->delete(route('tenants.destroy', 999))
            ->assertStatus(404);
    }
}
