<?php

namespace Tests\Unit;

use App\Support\NaturalOrder;
use PHPUnit\Framework\TestCase;

/**
 * Human ordering for names that carry numbers.
 *
 * The bug this exists for: "Floor 1, Floor 10, Floor 2 … Floor 9" — correct by
 * byte, wrong to anyone reading the document.
 */
class NaturalOrderTest extends TestCase
{
    private function rows(array $names, string $key = 'name'): \Illuminate\Support\Collection
    {
        return collect($names)->map(fn ($n) => [$key => $n]);
    }

    public function test_numbers_in_names_sort_numerically(): void
    {
        $sorted = NaturalOrder::sort(
            $this->rows(['Floor 10', 'Floor 2', 'Floor 1', 'Floor 9', 'Floor 11']),
            'name'
        );

        $this->assertSame(
            ['Floor 1', 'Floor 2', 'Floor 9', 'Floor 10', 'Floor 11'],
            $sorted->pluck('name')->all()
        );
    }

    /** The unit names in this app carry the property code and a room number. */
    public function test_unit_codes_sort_by_their_number(): void
    {
        $sorted = NaturalOrder::sort(
            $this->rows(['MP2 - 102', 'MP2 - 94', 'MP2 - 11', 'MP2 - R/T', 'MP2 - 9']),
            'name'
        );

        $this->assertSame(
            ['MP2 - 9', 'MP2 - 11', 'MP2 - 94', 'MP2 - 102', 'MP2 - R/T'],
            $sorted->pluck('name')->all()
        );
    }

    public function test_case_does_not_decide_the_order(): void
    {
        $sorted = NaturalOrder::sort($this->rows(['beta 2', 'Alpha 10', 'alpha 2']), 'name');

        $this->assertSame(['alpha 2', 'Alpha 10', 'beta 2'], $sorted->pluck('name')->all());
    }

    /** Later keys only decide ties on the earlier ones. */
    public function test_it_sorts_by_several_keys_in_order(): void
    {
        $rows = collect([
            ['code' => 'MP2', 'name' => 'Floor 2'],
            ['code' => 'MP1', 'name' => 'Floor 10'],
            ['code' => 'MP2', 'name' => 'Floor 1'],
            ['code' => 'MP1', 'name' => 'Floor 2'],
        ]);

        $sorted = NaturalOrder::sort($rows, 'code', 'name');

        $this->assertSame(
            ['MP1 Floor 2', 'MP1 Floor 10', 'MP2 Floor 1', 'MP2 Floor 2'],
            $sorted->map(fn ($r) => $r['code'] . ' ' . $r['name'])->all()
        );
    }

    /**
     * An unnamed record is an incomplete one; leading a document with a run of
     * dashes buries the rows someone opened it to read.
     */
    public function test_blank_values_sort_last(): void
    {
        $sorted = NaturalOrder::sort($this->rows(['Floor 2', '', 'Floor 1', '   ']), 'name');

        $this->assertSame(['Floor 1', 'Floor 2', '', '   '], $sorted->pluck('name')->all());
    }

    public function test_it_reindexes_so_the_result_can_be_addressed_positionally(): void
    {
        $sorted = NaturalOrder::sort($this->rows(['Floor 2', 'Floor 1']), 'name');

        $this->assertSame([0, 1], $sorted->keys()->all());
    }
}
