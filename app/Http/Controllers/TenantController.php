<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\RentScheduleService;
use App\Services\TenantLedgerService;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        private RentScheduleService $rentSchedule,
        private TenantLedgerService $ledger,
    ) {}

    public function index(Request $request)
    {
        $query = Tenant::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id_cr_number', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('tenant_type')) {
            $query->where('tenant_type', $type);
        }

        if ($sort = $request->input('sort')) {
            $direction = $request->input('direction', 'asc');
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $tenants = $query->paginate(15)->withQueryString();

        $stats = Tenant::selectRaw('tenant_type, count(*) as total')
            ->groupBy('tenant_type')
            ->pluck('total', 'tenant_type')
            ->toArray();

        return view('tenants.index', compact('tenants', 'stats'));
    }

    public function create()
    {
        return redirect()->route('tenants.index');
    }

    public function store(StoreTenantRequest $request)
    {
        Tenant::create($request->validated());

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    public function show(Request $request, Tenant $tenant)
    {
        $tenant->load([
            'leaseContracts' => fn ($q) => $q->orderByDesc('lease_start_date'),
            'invoices'       => fn ($q) => $q->orderByDesc('invoice_date'),
            'ewaBills'       => fn ($q) => $q->orderByDesc('reading_date'),
            'payments.invoice',
            'invoiceNotes.invoice',
        ]);

        $rentSchedule    = $this->rentSchedule->build($tenant);
        $financialSummary = $this->ledger->buildFinancialSummary($tenant);

        if ($request->boolean('modal')) {
            return view('tenants._profile', compact('tenant', 'rentSchedule', 'financialSummary'));
        }

        return view('tenants.show', compact('tenant', 'rentSchedule', 'financialSummary'));
    }

    public function edit(Tenant $tenant)
    {
        return view('tenants.edit', compact('tenant'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $tenant->update($request->validated());

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        $tenants = Tenant::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('tenant_code', 'like', "%{$q}%")
                        ->orWhere('id_cr_number', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'tenant_code', 'tenant_type', 'address']);

        return response()->json($tenants->map(fn ($t) => [
            'id'          => $t->id,
            'name'        => $t->name,
            'tenant_code' => $t->tenant_code ?? '',
            'tenant_type' => $t->tenant_type,
            'address'     => $t->address ?? '',
        ]));
    }
}
