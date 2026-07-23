<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\CustomFieldDefinition;
use App\Models\LeaseContract;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Http\Requests\UpdateBuildingSettingsRequest;
use App\Services\DashboardAnalyticsService;
use App\Services\FormConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BuildingController extends Controller
{
    public function __construct(private DashboardAnalyticsService $analytics)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'property_type', 'type_of_ownership']);

        $buildings = Building::withCount(['floors', 'units', 'occupiedUnits'])
            ->with('images')
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
        $building->load('images');
        $floors = $building->floors()->orderBy('floor_name')->get();

        $units = $building->units()->with(['floor', 'activeContract'])->orderBy('unit_name')->get();

        $contracts = LeaseContract::where('property_code', $building->property_code)
            ->with('tenant')
            ->orderByDesc('lease_start_date')
            ->get();

        // Unique tenants derived from contracts for this building
        $tenants = $contracts->pluck('tenant')->filter()->unique('id')->values();

        $dashboard = $this->analytics->buildingDashboard($building, $units, $contracts, Carbon::today()->year);

        return view('buildings.show', compact('building', 'floors', 'units', 'contracts', 'tenants', 'dashboard'));
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

    public function updateSettings(UpdateBuildingSettingsRequest $request, Building $building)
    {
        $data = $request->validated();
        $data['vat_rate'] = $data['vat_enabled'] ? ($data['vat_rate'] ?? 0) : 0;

        $building->update($data);

        return redirect()->route('buildings.show', ['building' => $building, 'tab' => 'settings'])
            ->with('success', 'Building settings updated.');
    }
}
