<?php

namespace Tests\Unit;

use App\Support\NumberToWords;
use PHPUnit\Framework\TestCase;

class NumberToWordsTest extends TestCase
{
    public function test_converts_zero(): void
    {
        $this->assertSame('Zero', NumberToWords::convert(0));
    }

    public function test_converts_teen_number(): void
    {
        $this->assertSame('Fifteen', NumberToWords::convert(15));
    }

    public function test_converts_tens_and_ones(): void
    {
        $this->assertSame('Fifty', NumberToWords::convert(50));
        $this->assertSame('Seventy Five', NumberToWords::convert(75));
    }

    public function test_converts_hundreds(): void
    {
        $this->assertSame('Seven Hundred Fifty', NumberToWords::convert(750));
    }

    public function test_converts_thousands(): void
    {
        $this->assertSame('One Thousand Two Hundred Thirty Four', NumberToWords::convert(1234));
    }

    public function test_bahraini_dinars_whole_amount(): void
    {
        $this->assertSame('Bahraini Dinar Seven Hundred Fifty Only.', NumberToWords::bahrainiDinars(750.000));
    }

    public function test_bahraini_dinars_with_fils(): void
    {
        $this->assertSame(
            'Bahraini Dinar Seven Hundred Fifty and Five Hundred Fils Only.',
            NumberToWords::bahrainiDinars(750.500)
        );
    }

    public function test_bahraini_dinars_zero(): void
    {
        $this->assertSame('Bahraini Dinar Zero Only.', NumberToWords::bahrainiDinars(0));
    }
}
