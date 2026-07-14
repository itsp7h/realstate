<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * One tab per property — each group in $groupedRows (keyed by building
 * name, from VatReturnService::groupByBuilding()) becomes its own
 * VatReturnSheetExport tab, named after that property.
 */
class VatReturnExport implements WithMultipleSheets
{
    public function __construct(private Collection $groupedRows) {}

    public function sheets(): array
    {
        $usedTitles = [];

        return $this->groupedRows
            ->map(function (Collection $rows, string $buildingName) use (&$usedTitles) {
                $title = $this->uniqueSheetTitle($buildingName, $usedTitles);
                $usedTitles[] = $title;

                return new VatReturnSheetExport($rows, $title);
            })
            ->values()
            ->all();
    }

    /**
     * Excel sheet titles must be <=31 chars, unique within the workbook,
     * and can't contain \ / ? * [ ].
     */
    private function uniqueSheetTitle(string $name, array $usedTitles): string
    {
        $clean = trim(preg_replace('/[\\\\\/\?\*\[\]]/', '', $name));
        $clean = $clean === '' ? 'Unassigned' : $clean;
        $base  = substr($clean, 0, 31);

        $title = $base;
        $suffix = 2;
        while (in_array($title, $usedTitles, true)) {
            $title = substr($base, 0, 31 - strlen(" ({$suffix})")) . " ({$suffix})";
            $suffix++;
        }

        return $title;
    }
}
