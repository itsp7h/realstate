<?php

namespace App\Providers;

use App\Models\AzureMailSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->overrideAzureMailerFromDatabase();
    }

    /**
     * The Azure AD app registration can be entered via the Mail Settings UI
     * (Admin-only) instead of editing .env on the server — env values stay
     * the fallback if no row has been saved yet. Guarded so this is a no-op
     * before the table is migrated (fresh installs, `migrate` itself) or if
     * the DB connection isn't ready (e.g. building `config:cache`).
     */
    private function overrideAzureMailerFromDatabase(): void
    {
        try {
            if (! Schema::hasTable('azure_mail_settings')) {
                return;
            }

            $settings = AzureMailSetting::current();

            if (! $settings->isConfigured()) {
                return;
            }

            config([
                'mail.mailers.azure.tenant_id'     => $settings->tenant_id,
                'mail.mailers.azure.client_id'     => $settings->client_id,
                'mail.mailers.azure.client_secret' => $settings->client_secret,
                'mail.mailers.azure.from_address'  => $settings->from_address,
            ]);
        } catch (Throwable) {
            // No database connection yet (e.g. during `artisan migrate` on a
            // fresh install) — fall back to whatever's in .env.
        }
    }
}
