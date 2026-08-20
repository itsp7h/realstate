<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row table holding app-wide branding — site name, company name,
 * tagline, logo/favicon, and accent colors — shown across the shell, login
 * screens, and PDF exports so an Admin can rebrand without editing views.
 */
class BrandingSetting extends Model
{
    protected $fillable = [
        'site_name', 'company_name', 'tagline', 'logo_path', 'favicon_path',
        'primary_color', 'secondary_color',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon_path ? asset('storage/' . $this->favicon_path) : null;
    }

    public function displaySiteName(): string
    {
        return $this->site_name ?: config('app.name');
    }

    public function initials(): string
    {
        if (! $this->site_name) {
            return 'P7';
        }

        $words = preg_split('/\s+/', trim($this->site_name));

        return mb_strtoupper(implode('', array_map(
            fn ($word) => mb_substr($word, 0, 1),
            array_slice($words, 0, 2)
        )));
    }
}
