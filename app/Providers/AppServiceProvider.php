<?php

namespace App\Providers;

use App\Models\AzureMailSetting;
use App\Services\AttentionFeed;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        $this->shareAttentionFeedWithTheShell();
    }

    /**
     * The top bar's bell lives in the layout, so its data has to reach every
     * page without each of the ~40 controllers passing it. A composer bound to
     * the layout is the narrowest way to do that: it runs only when the shell
     * actually renders, and the feed itself is cached for a minute, so this is
     * not a query per page view.
     */
    private function shareAttentionFeedWithTheShell(): void
    {
        View::composer('layouts.admin', function ($view) {
            $feed = app(AttentionFeed::class);
            $user = auth()->user();

            $view->with([
                'attentionItems' => $feed->items($user),
                'attentionCount' => $feed->count($user),
            ]);
        });
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
