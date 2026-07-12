<?php

namespace Tests\Unit;

use App\Support\MoneyFormat;
use PHPUnit\Framework\TestCase;

class MoneyFormatTest extends TestCase
{
    public function test_positive_amount_shows_dr(): void
    {
        $this->assertSame('596.850 Dr', MoneyFormat::crDr(596.850));
    }

    public function test_negative_amount_shows_cr(): void
    {
        $this->assertSame('50.000 Cr', MoneyFormat::crDr(-50.000));
    }

    public function test_zero_shows_dash(): void
    {
        $this->assertSame('—', MoneyFormat::crDr(0.0));
    }

    public function test_respects_custom_decimal_places(): void
    {
        $this->assertSame('10.50 Dr', MoneyFormat::crDr(10.5, 2));
    }
}
