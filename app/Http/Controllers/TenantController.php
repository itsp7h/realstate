<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
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

    public function show(Tenant $tenant)
    {
        return view('tenants.show', compact('tenant'));
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
}
