<?php

namespace Tests\Feature;

use App\Models\AzureMailSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AzureMailSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('settings.azure-mail.edit'))->assertOk();
    }

    public function test_user_role_cannot_access_mail_settings(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $this->get(route('settings.azure-mail.edit'))->assertForbidden();
    }

    public function test_maintenance_cannot_access_mail_settings(): void
    {
        $this->actingAs(User::factory()->maintenance()->create());

        $this->get(route('settings.azure-mail.edit'))->assertForbidden();
    }

    public function test_admin_can_save_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.azure-mail.update'), [
            'tenant_id'     => 'tenant-123',
            'client_id'     => 'client-456',
            'client_secret' => 'super-secret',
            'from_address'  => 'noreply@promoseven.com',
        ])->assertRedirect(route('settings.azure-mail.edit'));

        $setting = AzureMailSetting::current();
        $this->assertSame('tenant-123', $setting->tenant_id);
        $this->assertSame('client-456', $setting->client_id);
        $this->assertSame('super-secret', $setting->client_secret);
        $this->assertSame('noreply@promoseven.com', $setting->from_address);
    }

    public function test_leaving_client_secret_blank_keeps_the_existing_one(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        AzureMailSetting::current()->update([
            'tenant_id'     => 'tenant-123',
            'client_id'     => 'client-456',
            'client_secret' => 'original-secret',
            'from_address'  => 'noreply@promoseven.com',
        ]);

        $this->put(route('settings.azure-mail.update'), [
            'tenant_id'     => 'tenant-123',
            'client_id'     => 'client-456',
            'client_secret' => '',
            'from_address'  => 'noreply@promoseven.com',
        ])->assertRedirect();

        $this->assertSame('original-secret', AzureMailSetting::current()->client_secret);
    }

    public function test_validates_required_fields(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.azure-mail.update'), [])
            ->assertSessionHasErrors(['tenant_id', 'client_id', 'from_address']);
    }

    public function test_validates_from_address_is_an_email(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('settings.azure-mail.update'), [
            'tenant_id'    => 'tenant-123',
            'client_id'    => 'client-456',
            'from_address' => 'not-an-email',
        ])->assertSessionHasErrors(['from_address']);
    }

    public function test_send_test_blocks_when_not_fully_configured(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('settings.azure-mail.test'))
            ->assertRedirect(route('settings.azure-mail.edit'))
            ->assertSessionHas('error');
    }

    public function test_send_test_sends_when_configured(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
        $this->actingAs($admin);

        AzureMailSetting::current()->update([
            'tenant_id'     => 'tenant-123',
            'client_id'     => 'client-456',
            'client_secret' => 'secret',
            'from_address'  => 'noreply@promoseven.com',
        ]);

        $this->post(route('settings.azure-mail.test'))
            ->assertRedirect(route('settings.azure-mail.edit'))
            ->assertSessionHas('success');
    }
}
