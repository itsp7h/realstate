<?php

namespace Tests\Unit;

use App\Http\Controllers\ImportController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImportControllerHeaderAliasTest extends TestCase
{
    private function fuzzyUnitIdentifierAlias(string $header): ?string
    {
        $controller = new ImportController();
        $method = new \ReflectionMethod($controller, 'fuzzyUnitIdentifierAlias');

        return $method->invoke($controller, $header);
    }

    #[DataProvider('realWorldUnitHeaderProvider')]
    public function test_recognizes_real_world_unit_identifier_headers(string $header): void
    {
        $this->assertSame('unit', $this->fuzzyUnitIdentifierAlias($header));
    }

    public static function realWorldUnitHeaderProvider(): array
    {
        return [
            'Flat No'         => ['Flat No'],
            'Flat No.'        => ['Flat No.'],
            'Flat Number'     => ['Flat Number'],
            'Flat #'          => ['Flat #'],
            'flat no (lowercase)' => ['flat no'],
            'Apt No'          => ['Apt No'],
            'Apt #'           => ['Apt #'],
            'Apartment No'    => ['Apartment No'],
            'Apartment Number'=> ['Apartment Number'],
            'Suite No'        => ['Suite No'],
            'Suite Number'    => ['Suite Number'],
            'Unit No'         => ['Unit No'],
            'Unit Number'     => ['Unit Number'],
            'bare Flat'       => ['Flat'],
            'bare Apartment'  => ['Apartment'],
            'bare Suite'      => ['Suite'],
        ];
    }

    #[DataProvider('falsePositiveHeaderProvider')]
    public function test_does_not_misfire_on_similar_but_unrelated_headers(string $header): void
    {
        $this->assertNull($this->fuzzyUnitIdentifierAlias($header));
    }

    public static function falsePositiveHeaderProvider(): array
    {
        return [
            // Contains "unit" and "no" as substrings/words but means something else entirely.
            'Total No. of Units'   => ['Total No. of Units'],
            'No of Units'          => ['No of Units'],
            'Total Units'          => ['Total Units'],
            'Unit Count'           => ['Unit Count'],
            // Contains "unit" but is an already-exact-aliased field, not the identifier.
            'Unit Type'            => ['Unit Type'],
            'Unit Condition'       => ['Unit Condition'],
            // Contains "no" but nothing to do with units/flats.
            'Building No'          => ['Building No'],
            'Building No.'         => ['Building No.'],
            'Floor No'             => ['Floor No'],
            'Municipality Nos'     => ['Municipality Nos'],
            'Phone Number'         => ['Phone Number'],
            'Account Number'       => ['Account Number'],
            'CR Number'            => ['CR Number'],
            'ID Number'            => ['ID Number'],
            'Parking (FOC)'        => ['Parking (FOC)'],
            'No of Parkings'       => ['No of Parkings'],
            'unrelated word'       => ['Description'],
            'empty string'         => [''],
        ];
    }
}
