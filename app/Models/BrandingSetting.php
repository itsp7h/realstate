<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Single-row table holding app-wide branding — site name, company name,
 * tagline, logo/favicon, and accent colors — shown across the shell, login
 * screens, and PDF exports so an Admin can rebrand without editing views.
 */
class BrandingSetting extends Model
{
    protected $fillable = [
        'site_name', 'company_name', 'tagline', 'logo_path', 'favicon_path',
        'primary_color', 'secondary_color', 'company_email',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    /**
     * The letterhead logo as a base64 data URI, for the PDF templates.
     *
     * The bytes go inline rather than as a URL or a filesystem path because
     * DomPDF resolves an <img src> against its chroot, which defaults to
     * public/. The uploaded logo lives under storage/app/public and is only
     * reachable through the public/storage symlink, and realpath() takes that
     * straight back out of the chroot again — so a path is refused and an http
     * URL needs remote fetching enabled. A data URI needs neither.
     *
     * Falls back to the bundled mark, then to null so the letterhead can drop
     * the image rather than print a broken one. Read-only by design, unlike
     * current(): rendering an export must never write the settings row.
     */
    /**
     * The contact email for the letterhead's organisation line, or null.
     *
     * Read-only by design, exactly as letterheadLogoDataUri() below: this runs
     * inside every PDF render, and current() would create the settings row as a
     * side effect of exporting a document. A blank value returns null rather
     * than an empty string so the template can drop its separator instead of
     * printing a dangling middot.
     */
    public static function letterheadEmail(): ?string
    {
        $email = static::query()->value('company_email');

        return is_string($email) && trim($email) !== '' ? trim($email) : null;
    }

    public static function letterheadLogoDataUri(): ?string
    {
        $stored = static::query()->value('logo_path');

        $candidates = array_filter([
            $stored ? Storage::disk('public')->path($stored) : null,
            public_path('logo/promoseven-logo.png'),
        ]);

        foreach ($candidates as $file) {
            if (! is_file($file) || ! is_readable($file)) {
                continue;
            }

            $bytes = file_get_contents($file);

            if ($bytes === false || $bytes === '') {
                continue;
            }

            return 'data:' . static::mimeOf($file) . ';base64,' . base64_encode($bytes);
        }

        return null;
    }

    /**
     * Extension first, sniffing second: mime_content_type() reports SVG as
     * text/xml or text/plain depending on the file's first bytes, which DomPDF
     * then refuses to draw.
     */
    protected static function mimeOf(string $file): string
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => mime_content_type($file) ?: 'application/octet-stream',
        };
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
