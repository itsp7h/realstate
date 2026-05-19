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
        $filters = $request->only(['search', 'property_code', 'unit_type', 'unit_condition', 'floor_name']);

        $units = PropertyUnit::filter($filters)
            ->orderBy('property_code')
            ->orderBy('floor_name')
            ->orderBy('unit_name')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'      => PropertyUnit::count(),
            'furnished'  => PropertyUnit::where('unit_condition', 'Furnished')->count(),
            'fitted'     => PropertyUnit::where('unit_condition', 'Fitted')->count(),
            'properties' => PropertyUnit::distinct('property_code')->count('property_code'),
        ];

        return view('property-units.index', compact('units', 'stats'));
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
            'total_no_of_blocks', 'total_no_of_floors',
        ]));
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
        // Export logic will be implemented when the maatwebsite/excel package is added.
        // Filters from $request->only([...]) will be applied before export.
        abort(501, 'Export not yet implemented.');
    }
}
