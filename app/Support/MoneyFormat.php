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
}
