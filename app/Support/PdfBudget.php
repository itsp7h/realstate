<?php

namespace App\Support;

/**
 * Raises the resource ceiling for the one thing in this app that needs it: a
 * DomPDF render of a long report.
 *
 * WHY THIS EXISTS
 * DomPDF holds a Frame and a computed Style object for every element it lays
 * out, so a report's cost scales with its row count and nothing releases until
 * the document is finished. Measured on this application's own reports:
 *
 *     rows    peak      seconds
 *       50     48 MB      1.7
 *      400    128 MB      5.1
 *      800    264 MB     12.8
 *     1048    379 MB     18.9      Collection Report, year to date
 *     1242    547 MB     26.9      VAT Return, year to date
 *
 * Roughly 0.35 MB and 18 ms per row on top of a ~30 MB floor, near enough
 * linear. php-fpm allows 128 MB, so both of those reports died with "Allowed
 * memory size exhausted" in Css/Style.php and returned a 500 — while the same
 * routes passed a CLI smoke test, because the CLI php.ini sets memory_limit to
 * -1. Any check of a PDF route has to run under the fpm limit to mean anything.
 *
 * WHY A CEILING RATHER THAN A FIX
 * This is the symptom, not the cause. The cause is the engine, and the cost is
 * a constant factor rather than anything pathological — there is no
 * accidentally-quadratic step to remove. Two things were measured and
 * rejected:
 *
 *   • Leaner markup. The row template is already bare text in bare cells; the
 *     only removable elements are the em-dashes standing in for absent values.
 *     Single-digit percentage.
 *   • Splitting the one long table into a table per N rows, which halves both
 *     figures (362 MB → 182 MB, 16.8 s → 9.1 s, and N barely matters between
 *     25 and 100 — the win is in giving the table layout engine independent
 *     units). Worth having, but each table prints its own header, so keeping
 *     one header per page means sizing chunks to the page — and the first page
 *     holds fewer rows than the rest because of the title block. That is a
 *     hand-tuned row count that silently degrades into half-empty pages or
 *     mid-page headers the moment a row wraps. Left for a change that can be
 *     designed and reviewed as a layout change, not smuggled in as a fix.
 *
 * WHY THESE NUMBERS
 * MEMORY is set from the worst measured render (547 MB) plus room for about
 * 2,000 rows, and is affordable here: this box has 8 GB and php-fpm is capped
 * at 5 workers (pm.max_children), so even five simultaneous report renders sit
 * inside physical memory. Re-check both if either changes.
 *
 * SECONDS stays under nginx's own fastcgi_read_timeout, which is not set in
 * realstate.conf and therefore defaults to 60. Going above that would buy
 * nothing: nginx would return 504 while PHP was still working, so PHP should be
 * the one to give up first and leave an error in the log that says so.
 */
final class PdfBudget
{
    /** Per-request ceiling for a PDF render. See the note above before raising. */
    public const MEMORY = '768M';

    /** Below nginx's 60s default, so PHP fails first and leaves a log line. */
    public const SECONDS = 55;

    /**
     * Grants a PDF render the headroom it needs. Safe to call more than once,
     * and safe on the CLI, where the limit is already unlimited.
     */
    public static function reserve(): void
    {
        $target = self::targetMemoryLimit((string) ini_get('memory_limit'));

        if ($target !== null) {
            ini_set('memory_limit', $target);
        }

        // 0 means no limit — already more generous than anything set here.
        $current = (int) ini_get('max_execution_time');

        if ($current !== 0 && $current < self::SECONDS) {
            set_time_limit(self::SECONDS);
        }
    }

    /**
     * What memory_limit should become, or null to leave it alone.
     *
     * Only ever raises. A deployment that has deliberately given PHP more than
     * this — or all of it, with -1 — keeps what it was given; this is a floor
     * for the render, not a policy for the process.
     */
    public static function targetMemoryLimit(string $current): ?string
    {
        $bytes = self::toBytes($current);

        // -1 is unlimited, and an unparseable value is not something to
        // second-guess by overwriting it.
        if ($bytes === null || $bytes < 0) {
            return null;
        }

        return $bytes < self::toBytes(self::MEMORY) ? self::MEMORY : null;
    }

    /**
     * Parses a php.ini byte value ("128M", "1G", "-1", "134217728").
     * Returns null if it is not a shape ini uses.
     */
    public static function toBytes(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^(-?\d+)\s*([KMG])?$/i', $value, $m)) {
            return null;
        }

        $number = (int) $m[1];

        return match (strtoupper($m[2] ?? '')) {
            'K'     => $number * 1024,
            'M'     => $number * 1024 * 1024,
            'G'     => $number * 1024 * 1024 * 1024,
            default => $number,
        };
    }
}
