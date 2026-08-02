<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRevenueRequest;
use App\Http\Requests\UpdateRevenueRequest;
use App\Models\Building;
use App\Models\PropertyUnit;
use App\Models\Revenue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(Request $request): View
    {
        $query = Revenue::with(['building', 'unit'])->latest('revenue_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('source_name', 'like', "%{$search}%");
            });
        }

        if ($buildingId = $request->input('building_id')) {
            $query->where('building_id', $buildingId);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('revenue_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('revenue_date', '<=', $to);
        }

        $revenues = $query->paginate(20)->withQueryString();

        $stats = [
            'total'        => Revenue::count(),
            'total_amount' => (float) Revenue::sum('amount'),
            'this_month'   => (float) Revenue::whereYear('revenue_date', now()->year)
                ->whereMonth('revenue_date', now()->month)
                ->sum('amount'),
        ];

        $buildings = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);

        return view('revenues.index', [
            'revenues'   => $revenues,
            'stats'      => $stats,
            'buildings'  => $buildings,
            'categories' => Revenue::CATEGORIES,
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(StoreRevenueRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $revenue = Revenue::create($data);

        return redirect()->route('revenues.index')
            ->with('success', "Revenue of {$revenue->amount} BHD recorded.");
    }

    public function edit(Revenue $revenue): View
    {
        return $this->form($revenue);
    }

    public function update(UpdateRevenueRequest $request, Revenue $revenue): RedirectResponse
    {
        $revenue->update($request->validated());

        return redirect()->route('revenues.index')
            ->with('success', 'Revenue updated.');
    }

    public function destroy(Revenue $revenue): RedirectResponse
    {
        $revenue->delete();

        return redirect()->route('revenues.index')->with('success', 'Revenue deleted.');
    }

    private function form(?Revenue $record): View
    {
        $buildings = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);
        $units     = PropertyUnit::orderBy('unit_name')->get(['id', 'unit_name', 'building_id']);

        return view('revenues.create', [
            'record'     => $record,
            'buildings'  => $buildings,
            'units'      => $units,
            'categories' => Revenue::CATEGORIES,
        ]);
    }
}
