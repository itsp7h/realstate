<?php

namespace App\Mcp\Tools;

use App\Models\Building;
use App\Models\PropertyUnit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('property-overview')]
#[Description('Returns a snapshot of the property portfolio: total buildings and units, how many units are occupied vs vacant, and a per-building breakdown. Use this for a high-level view of the property inventory.')]
class PropertyOverviewTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $buildingCount = Building::count();
        $unitCount     = PropertyUnit::count();
        $occupiedCount = PropertyUnit::has('activeContract')->count();

        $perBuilding = Building::withCount(['units', 'occupiedUnits'])
            ->orderBy('property_name')
            ->get()
            ->map(fn (Building $b) => [
                'property_name'  => $b->property_name,
                'property_code'  => $b->property_code,
                'total_units'    => $b->units_count,
                'occupied_units' => $b->occupied_units_count,
                'vacant_units'   => $b->units_count - $b->occupied_units_count,
            ]);

        return Response::structured([
            'total_buildings'  => $buildingCount,
            'total_units'      => $unitCount,
            'occupied_units'   => $occupiedCount,
            'vacant_units'     => $unitCount - $occupiedCount,
            'occupancy_rate_percent' => $unitCount > 0 ? round(($occupiedCount / $unitCount) * 100, 1) : 0,
            'buildings' => $perBuilding,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
