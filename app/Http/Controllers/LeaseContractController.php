<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaseContractRequest;
use App\Http\Requests\UpdateLeaseContractRequest;
use App\Models\LeaseContract;
use App\Models\Tenant;
use App\Models\PropertyUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaseContractController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaseContract::with('tenant');
        $today = Carbon::today();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lease_agreement_no', 'like', "%{$search}%")
                  ->orWhere('tenant_name', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        if ($code = $request->input('property_code')) {
            $query->where('property_code', $code);
        }

        if ($status = $request->input('status')) {
            match ($status) {
                'active'   => $query->where('lease_end_date', '>=', $today)
                                    ->where('lease_start_date', '<=', $today),
                'expiring' => $query->whereBetween('lease_end_date', [$today, $today->copy()->addDays(30)]),
                'expired'  => $query->where('lease_end_date', '<', $today),
                'upcoming' => $query->where('lease_start_date', '>', $today),
                default    => null,
            };
        }

        if ($sort = $request->input('sort')) {
            $query->orderBy($sort, $request->input('direction', 'asc'));
        } else {
            $query->orderByDesc('lease_start_date');
        }

        $contracts = $query->paginate(15)->withQueryString();

        $stats = [
            'total'    => LeaseContract::count(),
            'active'   => LeaseContract::where('lease_end_date', '>=', $today)->where('lease_start_date', '<=', $today)->count(),
            'expiring' => LeaseContract::whereBetween('lease_end_date', [$today, $today->copy()->addDays(30)])->count(),
            'expired'  => LeaseContract::where('lease_end_date', '<', $today)->count(),
        ];

        $propertyCodes = LeaseContract::select('property_code')
            ->whereNotNull('property_code')
            ->distinct()
            ->orderBy('property_code')
            ->pluck('property_code');

        return view('lease-contracts.index', compact('contracts', 'stats', 'propertyCodes'));
    }

    public function create()
    {
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_type']);
        $units   = PropertyUnit::orderBy('unit_name')->get(['id', 'unit_name', 'property_code', 'floor_code']);

        return view('lease-contracts.create', compact('tenants', 'units'));
    }

    public function store(StoreLeaseContractRequest $request)
    {
        $data = $request->validated();
        $this->syncTenantName($data);

        LeaseContract::create($data);

        return redirect()->route('lease-contracts.index')
            ->with('success', 'Lease contract created successfully.');
    }

    public function show(LeaseContract $leaseContract)
    {
        $leaseContract->load('tenant', 'propertyUnit');

        return view('lease-contracts.show', compact('leaseContract'));
    }

    public function edit(LeaseContract $leaseContract)
    {
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_type']);
        $units   = PropertyUnit::orderBy('unit_name')->get(['id', 'unit_name', 'property_code', 'floor_code']);

        return view('lease-contracts.edit', compact('leaseContract', 'tenants', 'units'));
    }

    public function update(UpdateLeaseContractRequest $request, LeaseContract $leaseContract)
    {
        $data = $request->validated();
        $this->syncTenantName($data);

        $leaseContract->update($data);

        return redirect()->route('lease-contracts.index')
            ->with('success', 'Lease contract updated successfully.');
    }

    public function destroy(LeaseContract $leaseContract)
    {
        $leaseContract->delete();

        return redirect()->route('lease-contracts.index')
            ->with('success', 'Lease contract deleted successfully.');
    }

    private function syncTenantName(array &$data): void
    {
        if (!empty($data['tenant_id'])) {
            $tenant = Tenant::find($data['tenant_id']);
            if ($tenant) {
                $data['tenant_name'] = $tenant->name;
            }
        }
    }
}
