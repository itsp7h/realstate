<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PromoSeven\Connect\AzureMailer\Transport\AzureTransport;
use Tests\TestCase;

/**
 * The Azure AD app registration doesn't exist yet (see CLAUDE.md), so this
 * only verifies the "azure" mailer is wired up and resolvable — actually
 * sending requires real tenant_id/client_id/client_secret and is exercised
 * manually once those exist, not in the test suite.
 */
class AzureMailerConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_azure_mailer_is_registered_in_config(): void
    {
        $this->assertSame('azure', config('mail.mailers.azure.transport'));
    }

    public function test_azure_mailer_resolves_to_the_custom_transport(): void
    {
        $mailer = Mail::mailer('azure');

        $this->assertInstanceOf(\Illuminate\Mail\Mailer::class, $mailer);
        $this->assertInstanceOf(AzureTransport::class, $mailer->getSymfonyTransport());
    }
}
