<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\MaintenanceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        if ($from = $request->input('date_from')) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('date', '<=', $to);
        }

        $requests = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => MaintenanceRequest::count(),
            'open'        => MaintenanceRequest::where('status', 'open')->count(),
            'in_progress' => MaintenanceRequest::where('status', 'in_progress')->count(),
            'completed'   => MaintenanceRequest::where('status', 'completed')->count(),
        ];

        return view('maintenance.index', compact('requests', 'stats'));
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

        $data['status']       = $data['status'] ?? 'open';
        $data['request_date'] = $data['request_date'] ?? now()->toDateString();

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
        $maintenanceRequest->update($request->validated());

        return redirect()->route('maintenance.show', $maintenanceRequest)
            ->with('success', "Maintenance request {$maintenanceRequest->job_order} updated.");
    }

    public function destroy(MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $maintenanceRequest->delete();

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance request deleted.');
    }
}
