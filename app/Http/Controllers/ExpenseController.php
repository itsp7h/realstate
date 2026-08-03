<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Building;
use App\Models\Expense;
use App\Models\PropertyUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Expense::with(['building', 'unit'])->latest('expense_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%");
            });
        }

        if ($buildingId = $request->input('building_id')) {
            $query->where('building_id', $buildingId);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('expense_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('expense_date', '<=', $to);
        }

        $expenses = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => Expense::count(),
            'total_amount' => (float) Expense::sum('amount'),
            'this_month'  => (float) Expense::whereYear('expense_date', now()->year)
                ->whereMonth('expense_date', now()->month)
                ->sum('amount'),
        ];

        $buildings = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);

        return view('expenses.index', [
            'expenses'   => $expenses,
            'stats'      => $stats,
            'buildings'  => $buildings,
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $expense = Expense::create($data);

        return redirect()->route('expenses.index')
            ->with('success', "Expense of {$expense->amount} BHD recorded.");
    }

    public function edit(Expense $expense): View
    {
        return $this->form($expense);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    private function form(?Expense $record): View
    {
        $buildings = Building::orderBy('property_name')->get(['id', 'property_name', 'property_code']);
        $units     = PropertyUnit::orderBy('unit_name')->get(['id', 'unit_name', 'building_id']);

        return view('expenses.create', [
            'record'     => $record,
            'buildings'  => $buildings,
            'units'      => $units,
            'categories' => Expense::CATEGORIES,
        ]);
    }
}
