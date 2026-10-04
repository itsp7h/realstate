<?php

namespace App\Support;

class MoneyFormat
{
    /**
     * Format a ledger amount with the accounting Dr/Cr suffix used on the
     * reference reports: a positive balance is money owed to us (Dr, a
     * debit against the tenant), a negative balance is a credit in the
     * tenant's favour (Cr). Zero renders as a plain dash.
     */
    public static function crDr(float $amount, int $decimals = 3): string
    {
        if (abs($amount) < 0.0005) {
            return '—';
        }

        $formatted = number_format(abs($amount), $decimals);

        return $amount < 0 ? "{$formatted} Cr" : "{$formatted} Dr";
    }

    /**
     * A figure for the mobile stat strips and list rows.
     *
     * This used to return an em dash for a zero, on the reasoning that
     * "BHD 0" three times across a strip reads as a broken page. It reads
     * as a *missing* one instead: a dash is what a table prints when it
     * has no value to print, and a month that collected nothing has a
     * value — nothing is what it collected. So a zero stays a figure, and
     * the surface mutes it (`.ps-stat-value.is-zero`) rather than hiding
     * it. Pair with isZero() to decide the class.
     *
     * The accounting dash on the reports lives in crDr() and is a
     * different convention — a ledger column genuinely has no entry to
     * print. It is deliberately unchanged.
     */
    public static function figure(?float $amount, int $decimals = 0): string
    {
        return number_format((float) ($amount ?? 0), $decimals);
    }

    /** Whether figure() will render this amount as a zero. */
    public static function isZero(?float $amount): bool
    {
        return $amount === null || abs($amount) < 0.0005;
    }
}
