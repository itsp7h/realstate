<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'property_type', 'type_of_ownership']);

        $buildings = Building::filter($filters)
            ->orderBy('property_code')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'       => Building::count(),
            'residential' => Building::where('property_type', 'Residential')->count(),
            'commercial'  => Building::where('property_type', 'Commercial')->count(),
            'properties'  => Building::count(),
        ];

        return view('buildings.index', compact('buildings', 'stats'));
    }

    public function create()
    {
        $building = new Building();
        return view('buildings.create', compact('building'));
    }

    public function store(StoreBuildingRequest $request)
    {
        Building::create($request->validated());
        return redirect()->route('buildings.index')
            ->with('success', 'Building created successfully.');
    }

    public function show(Building $building)
    {
        return view('buildings.show', compact('building'));
    }

    public function edit(Building $building)
    {
        return view('buildings.edit', compact('building'));
    }

    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $building->update($request->validated());
        return redirect()->route('buildings.index')
            ->with('success', 'Building updated successfully.');
    }

    public function destroy(Building $building)
    {
        $building->delete();
        return redirect()->route('buildings.index')
            ->with('success', 'Building deleted.');
    }
}
