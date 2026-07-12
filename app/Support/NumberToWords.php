<?php

namespace App\Support;

class NumberToWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    /**
     * Spell out a whole non-negative integer in English words.
     */
    public static function convert(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $words = [];

        foreach ([
            1_000_000_000 => 'Billion',
            1_000_000     => 'Million',
            1_000         => 'Thousand',
        ] as $unit => $label) {
            if ($number >= $unit) {
                $words[] = self::convert(intdiv($number, $unit)) . ' ' . $label;
                $number %= $unit;
            }
        }

        if ($number >= 100) {
            $words[] = self::ONES[intdiv($number, 100)] . ' Hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $tens = self::TENS[intdiv($number, 10)];
            $ones = self::ONES[$number % 10];
            $words[] = trim($tens . ' ' . $ones);
        } elseif ($number > 0) {
            $words[] = self::ONES[$number];
        }

        return implode(' ', $words);
    }

    /**
     * Spell out a Bahraini Dinar amount (3 decimal places / fils) for a tax invoice,
     * e.g. 750.000 -> "Bahraini Dinar Seven Hundred Fifty Only."
     *      750.500 -> "Bahraini Dinar Seven Hundred Fifty and Five Hundred Fils Only."
     */
    public static function bahrainiDinars(float $amount): string
    {
        $dinars = (int) floor(round($amount, 3));
        $fils   = (int) round((round($amount, 3) - $dinars) * 1000);

        $words = 'Bahraini Dinar ' . self::convert($dinars);

        if ($fils > 0) {
            $words .= ' and ' . self::convert($fils) . ' Fils';
        }

        return $words . ' Only.';
    }
}
