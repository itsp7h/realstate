<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEwaBillRequest;
use App\Http\Requests\StoreEwaPaymentRequest;
use App\Models\EwaBill;
use App\Models\EwaPayment;
use App\Services\EwaBillParser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EwaBillController extends Controller
{
    public function index(Request $request): View
    {
        $query = EwaBill::latest('due_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhere('tenant_name', 'like', "%{$search}%")
                  ->orWhere('property_name', 'like', "%{$search}%")
                  ->orWhere('ewa_account_number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($period = $request->input('period')) {
            $query->where('billing_period', 'like', "%{$period}%");
        }

        $bills = $query->paginate(20)->withQueryString();

        $stats = [
            'total'          => EwaBill::count(),
            'issued'         => EwaBill::whereIn('status', ['issued', 'overdue'])->count(),
            'partially_paid' => EwaBill::where('status', 'partially_paid')->count(),
            'paid'           => EwaBill::where('status', 'paid')->count(),
            'overdue'        => EwaBill::where('status', 'overdue')->count(),
        ];

        return view('ewa-bills.index', compact('bills', 'stats'));
    }

    public function create(): View
    {
        return view('ewa-bills.create', ['record' => null]);
    }

    public function store(StoreEwaBillRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['bill_number']       = EwaBill::generateNumber();
        $data['elec_consumption']  = max(0, ($data['elec_curr_reading'] ?? 0) - ($data['elec_prev_reading'] ?? 0));
        $data['water_consumption'] = max(0, ($data['water_curr_reading'] ?? 0) - ($data['water_prev_reading'] ?? 0));
        $data['total_amount']      = EwaBill::computeTotal($data);
        $data['tenant_portion']    = EwaBill::computeTenantPortion($data['total_amount'], $data['ewa_cap'] ?? null);
        $data['status']            = 'issued';

        $bill = EwaBill::create($data);

        return redirect()->route('ewa-bills.show', $bill)
            ->with('success', "EWA Bill {$bill->bill_number} created.");
    }

    public function show(EwaBill $ewaBill): View
    {
        $ewaBill->load('payments');
        return view('ewa-bills.show', ['bill' => $ewaBill]);
    }

    public function edit(EwaBill $ewaBill): View
    {
        return view('ewa-bills.create', ['record' => $ewaBill]);
    }

    public function update(StoreEwaBillRequest $request, EwaBill $ewaBill): RedirectResponse
    {
        $data = $request->validated();
        $data['elec_consumption']  = max(0, ($data['elec_curr_reading'] ?? 0) - ($data['elec_prev_reading'] ?? 0));
        $data['water_consumption'] = max(0, ($data['water_curr_reading'] ?? 0) - ($data['water_prev_reading'] ?? 0));
        $data['total_amount']      = EwaBill::computeTotal($data);
        $data['tenant_portion']    = EwaBill::computeTenantPortion($data['total_amount'], $data['ewa_cap'] ?? null);

        $ewaBill->update($data);

        return redirect()->route('ewa-bills.show', $ewaBill)
            ->with('success', "EWA Bill {$ewaBill->bill_number} updated.");
    }

    public function destroy(EwaBill $ewaBill): RedirectResponse
    {
        $ewaBill->delete();
        return redirect()->route('ewa-bills.index')->with('success', 'EWA Bill deleted.');
    }

    // ── Payments ─────────────────────────────────────────────────

    public function storePayment(StoreEwaPaymentRequest $request, EwaBill $ewaBill): RedirectResponse
    {
        $data = $request->validated();
        $data['payment_number'] = EwaPayment::generateNumber();
        $data['ewa_bill_id']    = $ewaBill->id;

        EwaPayment::create($data);
        $ewaBill->syncStatus();

        return redirect()->route('ewa-bills.show', $ewaBill)
            ->with('success', 'Payment recorded.');
    }

    public function destroyPayment(EwaBill $ewaBill, EwaPayment $ewaPayment): RedirectResponse
    {
        $ewaPayment->delete();
        $ewaBill->syncStatus();

        return redirect()->route('ewa-bills.show', $ewaBill)
            ->with('success', 'Payment removed.');
    }

    public function parseImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $path   = $request->file('file')->store('ewa-imports', 'local');
        $result = (new EwaBillParser())->parse(storage_path('app/' . $path));

        // Clean up temp file
        \Illuminate\Support\Facades\Storage::disk('local')->delete($path);

        // Remove raw text from response
        unset($result['_raw_text']);

        $filled = collect($result)->filter(fn($v) => $v !== null)->count();

        return response()->json([
            'data'   => $result,
            'filled' => $filled,
            'total'  => count($result),
        ]);
    }

    public function pdf(EwaBill $ewaBill): Response
    {
        $ewaBill->load('payments', 'leaseContract.tenant');
        $pdf = Pdf::loadView('ewa-bills.pdf', ['bill' => $ewaBill])
                  ->setPaper('a4', 'portrait');

        return $pdf->download("ewa-bill-{$ewaBill->bill_number}.pdf");
    }

    public function pdfPreview(EwaBill $ewaBill): Response
    {
        $ewaBill->load('payments', 'leaseContract.tenant');
        $pdf = Pdf::loadView('ewa-bills.pdf', ['bill' => $ewaBill])
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("ewa-bill-{$ewaBill->bill_number}.pdf");
    }
}
