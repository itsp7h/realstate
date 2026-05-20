<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class FloorController extends Controller
{
    public function globalIndex(Request $request)
    {
        $buildingId = $request->input('building_id');

        $floors = Floor::with('building')
            ->when($buildingId, fn(Builder $q) => $q->where('building_id', $buildingId))
            ->orderBy('building_id')
            ->orderBy('floor_name')
            ->paginate(25)
            ->withQueryString();

        $buildings = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);

        $stats = [
            'total'    => Floor::count(),
            'filtered' => $floors->total(),
        ];

        return view('floors.global-index', compact('floors', 'buildings', 'stats', 'buildingId'));
    }

    public function index(Building $building)
    {
        $floors = $building->floors()
            ->orderBy('floor_name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_floors' => $building->floors()->count(),
            'total_units'  => $building->floors()->sum('total_no_of_units'),
        ];

        return view('floors.index', compact('building', 'floors', 'stats'));
    }

    public function create(Building $building)
    {
        $floor = new Floor();
        return view('floors.create', compact('building', 'floor'));
    }

    public function store(Building $building, Request $request)
    {
        $validated = $request->validate([
            'floor_name'        => ['required', 'string', 'max:100'],
            'floor_code'        => ['nullable', 'string', 'max:50'],
            'block_name'        => ['nullable', 'string', 'max:100'],
            'block_code'        => ['nullable', 'string', 'max:50'],
            'total_no_of_units' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['building_id'] = $building->id;

        Floor::create($validated);

        return redirect(route('buildings.show', $building) . '?tab=floors')
            ->with('success', 'Floor added successfully.');
    }

    public function edit(Floor $floor)
    {
        $building = $floor->building;
        return view('floors.edit', compact('building', 'floor'));
    }

    public function update(Request $request, Floor $floor)
    {
        $validated = $request->validate([
            'floor_name'        => ['required', 'string', 'max:100'],
            'floor_code'        => ['nullable', 'string', 'max:50'],
            'block_name'        => ['nullable', 'string', 'max:100'],
            'block_code'        => ['nullable', 'string', 'max:50'],
            'total_no_of_units' => ['nullable', 'integer', 'min:1'],
        ]);

        $floor->update($validated);

        return redirect(route('buildings.show', $floor->building) . '?tab=floors')
            ->with('success', 'Floor updated successfully.');
    }

    public function destroy(Floor $floor)
    {
        if ($floor->units()->exists()) {
            return back()->with('error', 'Cannot delete floor — it still has units linked to it.');
        }

        $building = $floor->building;
        $floor->delete();

        return redirect(route('buildings.show', $building) . '?tab=floors')
            ->with('success', 'Floor deleted successfully.');
    }
}
