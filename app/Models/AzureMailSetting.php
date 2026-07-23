<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row table holding the Azure AD app registration used by the
 * "azure" mailer (config/mail.php) — lets an Admin enter/rotate these
 * credentials from the UI instead of editing .env on the server, since
 * .env changes require server access this app's Admin users don't have.
 */
class AzureMailSetting extends Model
{
    protected $fillable = [
        'tenant_id', 'client_id', 'client_secret', 'from_address',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function isConfigured(): bool
    {
        return filled($this->tenant_id) && filled($this->client_id)
            && filled($this->client_secret) && filled($this->from_address);
    }
}
