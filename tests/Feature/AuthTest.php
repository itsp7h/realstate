<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_for_guest(): void
    {
        auth()->logout();

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_user_can_login_with_email(): void
    {
        auth()->logout();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username(): void
    {
        auth()->logout();

        $user = User::factory()->create([
            'name' => 'Mariam',
            'email' => 'mariam@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'Mariam',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username_regardless_of_case(): void
    {
        auth()->logout();

        $user = User::factory()->create([
            'name' => 'Developer',
            'email' => 'developer@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'developer',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_email_regardless_of_case(): void
    {
        auth()->logout();

        $user = User::factory()->create([
            'email' => 'Test@Example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_incorrect_password(): void
    {
        auth()->logout();

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_requires_login_and_password(): void
    {
        auth()->logout();

        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['login', 'password']);
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_protected_route(): void
    {
        auth()->logout();

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_cannot_view_login_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/login');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_role_cannot_delete_records(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        $this->actingAs(User::factory()->user()->create());

        $response = $this->delete("/tenants/{$tenant->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_admin_can_delete_records(): void
    {
        $tenant = Tenant::create(['name' => 'Ahmed Al-Khalifa', 'tenant_type' => 'individual']);
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->delete("/tenants/{$tenant->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }

    public function test_user_role_cannot_view_reports(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $response = $this->get('/reports');

        $response->assertForbidden();
    }

    public function test_admin_can_view_reports(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get('/reports');

        $response->assertOk();
    }

    public function test_user_role_cannot_access_audit_log(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $response = $this->get('/admin/audit-log');

        $response->assertForbidden();
    }

    public function test_admin_can_access_audit_log(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get('/admin/audit-log');

        $response->assertOk();
    }

    public function test_user_role_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $response = $this->get('/users');

        $response->assertForbidden();
    }

    public function test_maintenance_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->maintenance()->create());

        $response = $this->get('/users');

        $response->assertForbidden();
    }

    public function test_creating_user_with_duplicate_name_fails_validation(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        User::factory()->create(['name' => 'Mariam']);

        $response = $this->post('/users', [
            'name'     => 'Mariam',
            'email'    => 'another@example.com',
            'role'     => 'user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_access_user_management(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get('/users');

        $response->assertOk();
    }

    public function test_maintenance_can_access_maintenance_module(): void
    {
        $this->actingAs(User::factory()->maintenance()->create());

        $response = $this->get('/maintenance');

        $response->assertOk();
    }

    public function test_maintenance_can_access_dashboard(): void
    {
        $this->actingAs(User::factory()->maintenance()->create());

        $response = $this->get('/dashboard');

        $response->assertOk();
    }

    public function test_maintenance_cannot_access_anything_outside_its_module(): void
    {
        $this->actingAs(User::factory()->maintenance()->create());

        $response = $this->get('/tenants');

        $response->assertForbidden();
    }

    public function test_maintenance_cannot_delete_even_within_its_own_module(): void
    {
        $this->actingAs(User::factory()->maintenance()->create());

        $record = MaintenanceRequest::create([
            'date'               => '2026-05-21',
            'property'           => 'Tower A',
            'tenant'             => 'Ahmed Ali',
            'flat'               => '3B',
            'contact_no'         => '+973 3300 0000',
            'available_datetime' => '2026-05-22 10:00:00',
            'apartment_status'   => 'occupied',
        ]);

        $response = $this->delete("/maintenance/{$record->id}");

        $response->assertForbidden();
    }
}
