<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\CustomFieldDefinition;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Services\FormConfigService;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'property_type', 'type_of_ownership']);

        $buildings = Building::withCount(['floors', 'units'])
            ->filter($filters)
            ->orderBy('property_code')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'       => Building::count(),
            'residential' => Building::where('property_type', 'Residential')->count(),
            'commercial'  => Building::where('property_type', 'Commercial')->count(),
            'properties'  => Building::count(),
        ];

        $formFields      = app(FormConfigService::class)->getFormFields('building');
        $customFieldDefs = CustomFieldDefinition::getForForm('building');

        return view('buildings.index', compact('buildings', 'stats', 'formFields', 'customFieldDefs'));
    }

    public function create()
    {
        $building        = new Building();
        $formFields      = app(FormConfigService::class)->getFormFields('building');
        $customFieldDefs = CustomFieldDefinition::getForForm('building');
        return view('buildings.create', compact('building', 'formFields', 'customFieldDefs'));
    }

    public function store(StoreBuildingRequest $request)
    {
        $validated = $request->validated();
        $validated['custom_fields'] = $request->input('custom_fields', []);
        Building::create($validated);
        return redirect()->route('buildings.index')
            ->with('success', 'Building created successfully.');
    }

    public function show(Building $building)
    {
        $floors = $building->floors()->orderBy('floor_name')->get();
        return view('buildings.show', compact('building', 'floors'));
    }

    public function edit(Building $building)
    {
        $formFields      = app(FormConfigService::class)->getFormFields('building');
        $customFieldDefs = CustomFieldDefinition::getForForm('building');
        return view('buildings.edit', compact('building', 'formFields', 'customFieldDefs'));
    }

    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $validated = $request->validated();
        $validated['custom_fields'] = $request->input('custom_fields', []);
        $building->update($validated);
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
