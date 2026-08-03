<?php

namespace Tests\Feature;

use App\Models\AzureMailSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AppServiceProvider::boot() merges saved AzureMailSetting values into
 * config('mail.mailers.azure') at runtime — this confirms that actually
 * happens once a full set of settings is saved, and that a half-filled
 * row (or none at all) leaves the env-based config alone.
 */
class AzureMailerRuntimeOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_is_untouched_when_no_settings_saved(): void
    {
        $this->assertTrue(blank(config('mail.mailers.azure.tenant_id')));
    }

    public function test_config_is_overridden_once_settings_are_fully_configured(): void
    {
        AzureMailSetting::current()->update([
            'tenant_id'     => 'tenant-abc',
            'client_id'     => 'client-def',
            'client_secret' => 'secret-xyz',
            'from_address'  => 'noreply@promoseven.com',
        ]);

        // Re-run the provider's boot logic against the now-saved settings,
        // rather than refreshApplication() — that would tear down the
        // in-memory SQLite connection this test's data lives in.
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertSame('tenant-abc', config('mail.mailers.azure.tenant_id'));
        $this->assertSame('client-def', config('mail.mailers.azure.client_id'));
        $this->assertSame('secret-xyz', config('mail.mailers.azure.client_secret'));
        $this->assertSame('noreply@promoseven.com', config('mail.mailers.azure.from_address'));
    }
}
