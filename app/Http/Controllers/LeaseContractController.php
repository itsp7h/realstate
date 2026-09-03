<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaseContractRequest;
use App\Http\Requests\UpdateLeaseContractRequest;
use App\Models\Building;
use App\Models\Floor;
use App\Models\LeaseContract;
use App\Models\Tenant;
use App\Models\PropertyUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaseContractController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaseContract::with('tenant');
        $today = Carbon::today();
        $asOf  = $request->input('as_of') ? Carbon::parse($request->input('as_of')) : $today;

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
                'active'      => $query->whereNull('terminated_at')->whereNull('renewed_at')
                                    ->where('lease_end_date', '>=', $asOf)
                                    ->where('lease_start_date', '<=', $asOf),
                'expiring'    => $query->whereNull('terminated_at')->whereNull('renewed_at')
                                    ->whereBetween('lease_end_date', [$asOf, $asOf->copy()->addDays(30)]),
                'for_renewal' => $query->whereNull('terminated_at')->whereNull('renewed_at')->where('lease_end_date', '<', $asOf),
                'upcoming'    => $query->whereNull('terminated_at')->whereNull('renewed_at')->where('lease_start_date', '>', $asOf),
                'renewed'     => $query->whereNotNull('renewed_at'),
                'terminated'  => $query->whereNotNull('terminated_at'),
                default       => null,
            };
        }

        if ($sort = $request->input('sort')) {
            $query->orderBy($sort, $request->input('direction', 'asc'));
        } else {
            $query->orderByDesc('lease_start_date');
        }

        $contracts = $query->paginate(15)->withQueryString();

        $stats = [
            'total'       => LeaseContract::count(),
            'active'      => LeaseContract::whereNull('terminated_at')->whereNull('renewed_at')->where('lease_end_date', '>=', $asOf)->where('lease_start_date', '<=', $asOf)->count(),
            'expiring'    => LeaseContract::whereNull('terminated_at')->whereNull('renewed_at')->whereBetween('lease_end_date', [$asOf, $asOf->copy()->addDays(30)])->count(),
            'for_renewal' => LeaseContract::whereNull('terminated_at')->whereNull('renewed_at')->where('lease_end_date', '<', $asOf)->count(),
            'renewed'     => LeaseContract::whereNotNull('renewed_at')->count(),
            'terminated'  => LeaseContract::whereNotNull('terminated_at')->count(),
        ];

        $propertyCodes = LeaseContract::select('property_code')
            ->whereNotNull('property_code')
            ->distinct()
            ->orderBy('property_code')
            ->pluck('property_code');

        $tenants  = Tenant::orderBy('name')->get(['id', 'name', 'tenant_type']);
        $units    = PropertyUnit::orderBy('unit_name')->get(['id', 'unit_name', 'building_id', 'floor_id']);
        $buildings = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);
        $floors    = Floor::orderBy('floor_name')->get(['id', 'building_id', 'floor_name', 'floor_code', 'block_name', 'block_code']);

        $asOfValue = $request->input('as_of', '');

        return view('lease-contracts.index', compact('contracts', 'stats', 'propertyCodes', 'tenants', 'units', 'buildings', 'floors', 'asOfValue'));
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

        if (empty($data['lease_agreement_no'])) {
            $data['lease_agreement_no'] = LeaseContract::generateNumber();
        }

        LeaseContract::create($data);

        return redirect()->route('lease-contracts.index')
            ->with('success', 'Lease contract created successfully.');
    }

    public function show(LeaseContract $leaseContract)
    {
        $leaseContract->load('tenant', 'propertyUnit', 'renewedFrom', 'renewedInto');

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

    /**
     * The only action that actually frees up a unit — an expired end date
     * alone leaves the lease (and the unit's occupancy) untouched, showing
     * as "for renewal" instead of vacant.
     */
    public function terminate(LeaseContract $leaseContract)
    {
        if (! $leaseContract->terminated_at) {
            $leaseContract->update(['terminated_at' => now()]);
        }

        return back()->with('success', 'Lease terminated — the unit is now marked vacant.');
    }

    /**
     * One-click renewal: closes out the current contract (marks it
     * "renewed", distinct from "terminated" since the unit never vacates)
     * and opens a new one-year contract picking up the day after the old
     * lease's end date, carrying over the same tenant/unit/rent terms.
     */
    public function renew(LeaseContract $leaseContract)
    {
        if ($leaseContract->terminated_at || $leaseContract->renewed_at) {
            return back()->with('error', 'This contract has already been closed out and cannot be renewed.');
        }

        if (! $leaseContract->lease_end_date) {
            return back()->with('error', 'This contract has no end date to renew from.');
        }

        $newStart = $leaseContract->lease_end_date->copy()->addDay();
        $newEnd   = $newStart->copy()->addYear()->subDay();

        $newContract = DB::transaction(function () use ($leaseContract, $newStart, $newEnd) {
            $contract = LeaseContract::create([
                ...$leaseContract->only([
                    'tenant_id', 'tenant_name', 'property_name', 'property_code',
                    'block_name', 'block_code', 'floor_name', 'floor_code',
                    'unit_id', 'unit', 'description', 'notice_period',
                    'rental_income_ledger', 'currency', 'security_deposit', 'ewa_cap',
                    'invoicing_frequency', 'rent_per_month', 'service_frequency',
                    'service_amount_bd_excl_vat', 'vat_enabled', 'vat_rate',
                ]),
                'date'                => now(),
                'lease_agreement_no'  => LeaseContract::generateNumber(),
                'lease_start_date'    => $newStart,
                'lease_end_date'      => $newEnd,
                'rent_start_date'     => $newStart,
                'rent_end_date'       => $newEnd,
                'service_start_date'  => $newStart,
                'service_end_date'    => $newEnd,
                'renewed_from_id'     => $leaseContract->id,
            ]);

            $leaseContract->update(['renewed_at' => now()]);

            return $contract;
        });

        return redirect()->route('lease-contracts.show', $newContract)
            ->with('success', "Lease renewed for one year — new agreement {$newContract->lease_agreement_no} runs {$newStart->format('d M Y')} to {$newEnd->format('d M Y')}.");
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        $contracts = LeaseContract::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('tenant_name', 'like', "%{$q}%")
                        ->orWhere('property_name', 'like', "%{$q}%")
                        ->orWhere('lease_agreement_no', 'like', "%{$q}%")
                        ->orWhere('unit', 'like', "%{$q}%");
                });
            })
            ->orderBy('tenant_name')
            ->limit(15)
            ->get(['id', 'lease_agreement_no', 'tenant_name', 'property_name', 'unit',
                   'lease_start_date', 'lease_end_date', 'rent_per_month', 'ewa_cap']);

        return response()->json($contracts->map(fn($c) => [
            'id'             => $c->id,
            'label'          => $c->tenant_name . ' — ' . $c->property_name . ($c->unit ? ' / ' . $c->unit : ''),
            'tenant_name'    => $c->tenant_name,
            'property_name'  => $c->property_name,
            'unit'           => $c->unit ?? '',
            'agreement_no'   => $c->lease_agreement_no ?? '',
            'start'          => $c->lease_start_date ? \Carbon\Carbon::parse($c->lease_start_date)->format('d M Y') : '',
            'end'            => $c->lease_end_date   ? \Carbon\Carbon::parse($c->lease_end_date)->format('d M Y')   : '',
            'rent'           => $c->rent_per_month   ? number_format((float)$c->rent_per_month, 3) : '',
            'ewa_cap'        => $c->ewa_cap           ? number_format((float)$c->ewa_cap, 3) : '',
        ]));
    }

    /**
     * Active lease contracts for a given tenant, for auto-filling invoice lines.
     */
    public function activeForTenant(Tenant $tenant): JsonResponse
    {
        // A tenant whose lease is past its end date but not terminated is
        // still occupying and still invoiceable — "for renewal" isn't "gone".
        $contracts = LeaseContract::where('tenant_id', $tenant->id)
            ->whereNull('terminated_at')
            ->orderBy('property_name')
            ->get(['id', 'lease_agreement_no', 'property_name', 'property_code', 'unit', 'rent_per_month', 'vat_enabled', 'vat_rate']);

        return response()->json($contracts->map(fn ($c) => [
            'id'                 => $c->id,
            'property_name'      => $c->property_name,
            'unit'               => $c->unit ?? '',
            'lease_agreement_no' => $c->lease_agreement_no ?? '',
            'amount'             => $c->rent_per_month ? number_format((float) $c->rent_per_month, 3, '.', '') : '',
            'vat_rate'           => $c->effective_vat_rate,
        ]));
    }

    /**
     * A tenant's lease contracts, searchable by property/unit/agreement no,
     * for the per-line picker on the invoice rental-lines table.
     */
    public function searchForTenant(Tenant $tenant, Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        $contracts = LeaseContract::where('tenant_id', $tenant->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('property_name', 'like', "%{$q}%")
                        ->orWhere('unit', 'like', "%{$q}%")
                        ->orWhere('lease_agreement_no', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('lease_start_date')
            ->limit(15)
            ->get(['id', 'lease_agreement_no', 'property_name', 'property_code', 'unit', 'rent_per_month', 'lease_start_date', 'lease_end_date', 'vat_enabled', 'vat_rate']);

        return response()->json($contracts->map(fn ($c) => [
            'id'                 => $c->id,
            'property_name'      => $c->property_name,
            'unit'               => $c->unit ?? '',
            'lease_agreement_no' => $c->lease_agreement_no ?? '',
            'amount'             => $c->rent_per_month ? number_format((float) $c->rent_per_month, 3, '.', '') : '',
            'start'              => $c->lease_start_date ? Carbon::parse($c->lease_start_date)->format('d M Y') : '',
            'end'                => $c->lease_end_date   ? Carbon::parse($c->lease_end_date)->format('d M Y')   : '',
            'vat_rate'           => $c->effective_vat_rate,
        ]));
    }

    private function syncTenantName(array &$data): void
    {
        $data['tenant_name'] = Tenant::findOrFail($data['tenant_id'])->name;
    }
}
