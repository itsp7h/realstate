<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\CustomFieldDefinition;
use App\Models\PropertyUnit;
use App\Http\Requests\StorePropertyUnitRequest;
use App\Http\Requests\UpdatePropertyUnitRequest;
use App\Services\FormConfigService;
use Illuminate\Http\Request;

class PropertyUnitController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'property_code', 'unit_type', 'unit_condition']);

        $units = PropertyUnit::filter($filters)
            ->orderBy('property_code')
            ->orderBy('unit_name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'      => PropertyUnit::count(),
            'furnished'  => PropertyUnit::where('unit_condition', 'Furnished')->count(),
            'fitted'     => PropertyUnit::where('unit_condition', 'Fitted')->count(),
            'properties' => PropertyUnit::distinct('property_code')->count('property_code'),
        ];

        $formFields      = app(FormConfigService::class)->getFormFields('unit');
        $customFieldDefs = CustomFieldDefinition::getForForm('unit');
        $buildings       = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);

        return view('property-units.index', compact('units', 'stats', 'formFields', 'customFieldDefs', 'buildings'));
    }

    public function create()
    {
        $unit            = new PropertyUnit();
        $formFields      = app(FormConfigService::class)->getFormFields('unit');
        $customFieldDefs = CustomFieldDefinition::getForForm('unit');
        $buildings       = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);
        return view('property-units.create', compact('unit', 'formFields', 'customFieldDefs', 'buildings'));
    }

    public function store(StorePropertyUnitRequest $request)
    {
        $validated = $request->validated();
        $validated['custom_fields'] = $request->input('custom_fields', []);
        PropertyUnit::create($validated);
        return redirect()->route('property-units.index')
            ->with('success', 'Property unit created successfully.');
    }

    public function buildingData(Building $building)
    {
        return response()->json($building->only([
            'property_name', 'property_code', 'type_of_ownership', 'property_type',
            'land_lord_name', 'building_no', 'road', 'block', 'area', 'city',
        ]));
    }

    public function floorsByBuilding(Building $building)
    {
        return response()->json(
            $building->floors()->orderBy('floor_name')->get(['id', 'floor_name', 'floor_code', 'block_name'])
        );
    }

    public function show(PropertyUnit $propertyUnit)
    {
        return view('property-units.show', ['unit' => $propertyUnit]);
    }

    public function edit(PropertyUnit $propertyUnit)
    {
        $formFields      = app(FormConfigService::class)->getFormFields('unit');
        $customFieldDefs = CustomFieldDefinition::getForForm('unit');
        $buildings       = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);
        return view('property-units.edit', [
            'unit'            => $propertyUnit,
            'formFields'      => $formFields,
            'customFieldDefs' => $customFieldDefs,
            'buildings'       => $buildings,
        ]);
    }

    public function update(UpdatePropertyUnitRequest $request, PropertyUnit $propertyUnit)
    {
        $validated = $request->validated();
        $validated['custom_fields'] = $request->input('custom_fields', []);
        $propertyUnit->update($validated);
        return redirect()->route('property-units.index')
            ->with('success', 'Property unit updated successfully.');
    }

    public function destroy(PropertyUnit $propertyUnit)
    {
        $propertyUnit->delete();
        return redirect()->route('property-units.index')
            ->with('success', 'Property unit deleted.');
    }

    public function export(Request $request)
    {
        return redirect()->route('export.units', $request->only(['search', 'property_code', 'unit_type', 'unit_condition']));
    }
}
