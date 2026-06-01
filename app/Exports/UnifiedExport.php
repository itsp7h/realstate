<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UnifiedExport implements WithMultipleSheets
{
    public function __construct(
        private array $buildingFilters = [],
        private array $unitFilters     = [],
        private ?int  $buildingId      = null,
    ) {}

    public function sheets(): array
    {
        return [
            new BuildingsExport($this->buildingFilters),
            new FloorsExport($this->buildingId),
            new UnitsExport($this->unitFilters),
        ];
    }
}
