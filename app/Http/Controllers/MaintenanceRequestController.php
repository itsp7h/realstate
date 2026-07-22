<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssessMaintenanceRequest;
use App\Http\Requests\ApproveMaintenanceRequest;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\Building;
use App\Models\MaintenanceRequest;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaintenanceRequest::latest('date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('job_order', 'like', "%{$search}%")
                  ->orWhere('property', 'like', "%{$search}%")
                  ->orWhere('tenant', 'like', "%{$search}%")
                  ->orWhere('flat', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($buildingId = $request->input('building_id')) {
            $query->where('building_id', $buildingId);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('date', '<=', $to);
        }

        $requests = $query->paginate(20)->withQueryString();

        $stats = [
            'total'              => MaintenanceRequest::count(),
            'waiting_supervisor' => MaintenanceRequest::where('status', 'waiting_supervisor')->count(),
            'waiting_approval'   => MaintenanceRequest::where('status', 'waiting_approval')->count(),
            'approved'           => MaintenanceRequest::where('status', 'approved')->count(),
            'in_progress'        => MaintenanceRequest::where('status', 'in_progress')->count(),
            'completed'          => MaintenanceRequest::where('status', 'completed')->count(),
        ];

        $properties = Building::orderBy('property_name')
            ->get(['property_name', 'property_code']);

        $units = PropertyUnit::orderBy('unit_name')
            ->get(['unit_name', 'property_code', 'property_name']);

        $tenants = Tenant::orderBy('name')
            ->get(['id', 'name']);

        return view('maintenance.index', compact('requests', 'stats', 'properties', 'units', 'tenants'));
    }

    public function create(): View
    {
        return view('maintenance.create', ['record' => null]);
    }

    public function store(StoreMaintenanceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['job_order'])) {
            $data['job_order'] = 'JO-' . strtoupper(substr(uniqid(), -6));
        }

        $data['status']       = 'waiting_supervisor';
        $data['request_date'] = $data['request_date'] ?? now()->toDateString();
        $data                 = array_merge($data, $this->resolveBuildingAndUnit($data['property'] ?? null, $data['flat'] ?? null));

        foreach (['quotation_1_file', 'quotation_2_file', 'quotation_3_file'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('maintenance/quotations', 'public');
            }
        }

        $record = MaintenanceRequest::create($data);

        return redirect()->route('maintenance.index')
            ->with('success', "Maintenance request {$record->job_order} submitted successfully.");
    }

    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        return view('maintenance.show', ['record' => $maintenanceRequest]);
    }

    public function edit(MaintenanceRequest $maintenanceRequest): View
    {
        return view('maintenance.create', ['record' => $maintenanceRequest]);
    }

    public function update(UpdateMaintenanceRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $data = $request->validated();
        $data = array_merge($data, $this->resolveBuildingAndUnit($data['property'] ?? null, $data['flat'] ?? null));

        foreach (['quotation_1_file', 'quotation_2_file', 'quotation_3_file'] as $field) {
            if ($request->hasFile($field)) {
                if ($maintenanceRequest->$field) {
                    Storage::disk('public')->delete($maintenanceRequest->$field);
                }
                $data[$field] = $request->file($field)->store('maintenance/quotations', 'public');
            } elseif ($request->boolean("remove_{$field}")) {
                if ($maintenanceRequest->$field) {
                    Storage::disk('public')->delete($maintenanceRequest->$field);
                }
                $data[$field] = null;
            } else {
                unset($data[$field]);
            }
        }

        $maintenanceRequest->update($data);

        return redirect()->route('maintenance.show', $maintenanceRequest)
            ->with('success', "Maintenance request {$maintenanceRequest->job_order} updated.");
    }

    public function assess(AssessMaintenanceRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $data = array_merge($request->validated(), ['status' => 'waiting_approval']);
        $maintenanceRequest->update($data);

        return redirect()->route('maintenance.index')
            ->with('success', "Assessment submitted for {$maintenanceRequest->job_order} — awaiting department approval.");
    }

    public function approve(ApproveMaintenanceRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $maintenanceRequest->update(array_merge($request->validated(), ['status' => 'approved']));

        return redirect()->route('maintenance.index')
            ->with('success', "Request {$maintenanceRequest->job_order} has been approved.");
    }

    public function destroy(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        foreach (['quotation_1_file', 'quotation_2_file', 'quotation_3_file'] as $field) {
            if ($maintenanceRequest->$field) {
                Storage::disk('public')->delete($maintenanceRequest->$field);
            }
        }

        $maintenanceRequest->delete();

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance request deleted.');
    }

    /**
     * Resolve building_id/unit_id from the free-text property/flat fields by
     * case-insensitive name match, same approach as ImportController's lease
     * import. Returns nulls when no matching Building/PropertyUnit is found.
     */
    private function resolveBuildingAndUnit(?string $property, ?string $flat): array
    {
        $buildingId = Building::whereRaw('LOWER(TRIM(property_name)) = ?', [strtolower(trim($property ?? ''))])
            ->value('id');

        $unitId = $buildingId
            ? PropertyUnit::where('building_id', $buildingId)
                ->whereRaw('LOWER(TRIM(unit_name)) = ?', [strtolower(trim($flat ?? ''))])
                ->value('id')
            : null;

        return ['building_id' => $buildingId, 'unit_id' => $unitId];
    }
}
