<?php

namespace App\Http\Controllers;

use App\Models\PropertyUnit;
use App\Http\Requests\StorePropertyUnitRequest;
use App\Http\Requests\UpdatePropertyUnitRequest;
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
        $unit = new PropertyUnit();
        return view('property-units.create', compact('unit'));
    }

    public function store(StorePropertyUnitRequest $request)
    {
        PropertyUnit::create($request->validated());
        return redirect()->route('property-units.index')
            ->with('success', 'Property unit created successfully.');
    }

    public function show(PropertyUnit $propertyUnit)
    {
        return view('property-units.show', ['unit' => $propertyUnit]);
    }

    public function edit(PropertyUnit $propertyUnit)
    {
        return view('property-units.edit', ['unit' => $propertyUnit]);
    }

    public function update(UpdatePropertyUnitRequest $request, PropertyUnit $propertyUnit)
    {
        $propertyUnit->update($request->validated());
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
