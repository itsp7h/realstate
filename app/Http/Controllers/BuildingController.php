<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\CustomFieldDefinition;
use App\Models\LeaseContract;
use App\Models\Tenant;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Http\Requests\UpdateBuildingSettingsRequest;
use App\Services\DashboardAnalyticsService;
use App\Services\FormConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BuildingController extends Controller
{
    public function __construct(private DashboardAnalyticsService $analytics)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'property_type', 'type_of_ownership', 'company_name']);

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

        $companies = Building::whereNotNull('company_name')
            ->distinct()
            ->orderBy('company_name')
            ->pluck('company_name');

        $formFields      = app(FormConfigService::class)->getFormFields('building');
        $customFieldDefs = CustomFieldDefinition::getForForm('building');

        return view('buildings.index', compact('buildings', 'stats', 'formFields', 'customFieldDefs', 'companies'));
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
        $floors = $building->floors()->with('block')->orderBy('floor_name')->get();
        $blocks = $building->blocks()->withCount('floors')->orderBy('block_name')->get();

        $units = $building->units()->with(['floor', 'activeContract'])->orderBy('unit_name')->get();

        $contracts = LeaseContract::where('property_code', $building->property_code)
            ->with('tenant')
            ->orderByDesc('lease_start_date')
            ->get();

        // Unique tenants derived from contracts for this building
        $tenants = $contracts->pluck('tenant')->filter()->unique('id')->values();

        $dashboard = $this->analytics->buildingDashboard($building, $units, $contracts, Carbon::today()->year);

        return view('buildings.show', compact('building', 'floors', 'blocks', 'units', 'contracts', 'tenants', 'dashboard'));
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
        // Floors and blocks cascade-delete at the DB level already
        // (cascadeOnDelete on their building_id FK). property_units.building_id
        // is nullOnDelete though, so units — and anything hanging off them —
        // must be cleaned up explicitly or they're orphaned with a null
        // building_id, still holding their leases and tenants.
        DB::transaction(function () use ($building) {
            $unitIds = $building->units()->pluck('id');

            $tenantIds = LeaseContract::whereIn('unit_id', $unitIds)
                ->whereNotNull('tenant_id')
                ->pluck('tenant_id')
                ->unique();

            LeaseContract::whereIn('unit_id', $unitIds)->delete();

            // Only remove a tenant if this was the only building they had a
            // lease against — a tenant leasing units across multiple
            // buildings must survive deleting just one of them.
            Tenant::whereIn('id', $tenantIds)
                ->whereDoesntHave('leaseContracts')
                ->delete();

            $building->units()->delete();
            $building->delete();
        });

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
