<?php

namespace App\Support;

/**
 * How full something is, in one place.
 *
 * The same three lines — guard the divide-by-zero, divide, round — were
 * written out at five call sites (the dashboard controller, the analytics
 * service, two MCP tools and a Blade fallback), and they had already drifted:
 * some rounded to a whole number, one to a decimal, and the Blade copy could
 * disagree with the service that was meant to feed it.
 *
 * percent() is the figure the UI shows: a whole number, 0–100.
 * rate() is the figure an API/tool answer carries, where a decimal is useful.
 */
class Occupancy
{
    /** Whole-number occupancy for display: 0 when there is nothing to divide. */
    public static function percent(?int $occupied, ?int $total): int
    {
        if (! $total || $total < 1) {
            return 0;
        }

        return (int) max(0, min(100, round(($occupied ?? 0) / $total * 100)));
    }

    /** Occupancy for data consumers, kept to one decimal by default. */
    public static function rate(?int $occupied, ?int $total, int $decimals = 1): float
    {
        if (! $total || $total < 1) {
            return 0.0;
        }

        return round(max(0, min(100, ($occupied ?? 0) / $total * 100)), $decimals);
    }
}
