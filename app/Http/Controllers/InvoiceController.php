<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\Tenant;
use App\Services\TenantMailer;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function __construct(private readonly TenantMailer $tenantMailer)
    {
    }

    public function index(Request $request): View
    {
        $query = Invoice::with('tenant')->latest('invoice_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('tenant_name', 'like', "%{$search}%")
                  ->orWhere('tenant_code', 'like', "%{$search}%")
                  ->orWhere('property_name', 'like', "%{$search}%");
            });
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('invoice_date', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('invoice_date', '<=', $to);
        }

        $invoices = $query->paginate(20)->withQueryString();

        $stats = [
            'total'          => Invoice::count(),
            'issued'         => Invoice::whereIn('status', ['issued', 'overdue'])->count(),
            'partially_paid' => Invoice::where('status', 'partially_paid')->count(),
            'paid'           => Invoice::where('status', 'paid')->count(),
            'overdue'        => Invoice::where('status', 'overdue')->count(),
        ];

        return view('invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Generate one consolidated rent invoice per tenant, covering every lease
     * contract that tenant currently has active, instead of one invoice per lease.
     */
    public function generateMonthly(): RedirectResponse
    {
        $today     = Carbon::today();
        $firstDay  = $today->copy()->startOfMonth();
        $lastDay   = $today->copy()->endOfMonth();
        $monthName = $firstDay->format('F Y');

        $contracts = LeaseContract::whereNotNull('rent_per_month')
            ->where('rent_per_month', '>', 0)
            ->whereDate('lease_start_date', '<=', $today)
            ->whereDate('lease_end_date',   '>=', $today)
            ->whereNotNull('tenant_id')
            ->get()
            ->groupBy('tenant_id');

        $created = 0;
        $skipped = 0;

        foreach ($contracts as $tenantId => $tenantContracts) {
            $exists = Invoice::where('tenant_id', $tenantId)
                ->where('type', 'rent')
                ->whereYear('invoice_date',  $firstDay->year)
                ->whereMonth('invoice_date', $firstDay->month)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $skipped++;
                continue;
            }

            $lines = $tenantContracts->map(fn (LeaseContract $contract) => [
                'lease_contract_id'   => $contract->id,
                'property_name'       => $contract->property_name,
                'unit'                => $contract->unit,
                'lease_agreement_no'  => $contract->lease_agreement_no,
                'rental_period_start' => $firstDay->toDateString(),
                'rental_period_end'   => $lastDay->toDateString(),
                'amount'              => (float) $contract->rent_per_month,
            ])->values()->all();

            $invoice = new Invoice([
                'invoice_number' => Invoice::generateNumber('rent'),
                'tenant_id'      => $tenant->id,
                'tenant_name'    => $tenant->name,
                'tenant_code'    => $tenant->tenant_code,
                'tenant_address' => Invoice::resolveTenantAddress($tenant, $lines),
                'property_name'  => $lines[0]['property_name'],
                'unit'           => count($lines) === 1 ? $lines[0]['unit'] : null,
                'type'           => 'rent',
                'description'    => "Rent for {$monthName}",
                'lines'          => $lines,
                'vat_rate'       => 0,
                'invoice_date'   => $firstDay,
                'status'         => 'issued',
            ]);
            $invoice->recomputeTotals();
            $invoice->save();
            $this->tenantMailer->sendInvoiceIssued($invoice);

            $created++;
        }

        $message = "Generated {$created} invoice(s) for {$monthName}.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} (already exist).";
        }

        return redirect()->route('invoices.index')->with('success', $message);
    }

    public function create(): View
    {
        return view('invoices.create', ['record' => null]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data   = $request->validated();
        $tenant = Tenant::findOrFail($data['tenant_id']);
        $lines  = array_values($data['lines']);

        $data['invoice_number'] = Invoice::generateNumber($data['type']);
        $data['tenant_name']    = $tenant->name;
        $data['tenant_code']    = $tenant->tenant_code;
        $data['tenant_address'] = Invoice::resolveTenantAddress($tenant, $lines);
        $data['property_name']  = $lines[0]['property_name'];
        $data['unit']           = count($lines) === 1 ? ($lines[0]['unit'] ?? null) : null;
        $data['status']         = 'issued';

        $invoice = new Invoice($data);
        $invoice->recomputeTotals();
        $invoice->save();
        $this->tenantMailer->sendInvoiceIssued($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load('payments');
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        return view('invoices.create', ['record' => $invoice]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $data   = $request->validated();
        $tenant = Tenant::findOrFail($data['tenant_id']);
        $lines  = array_values($data['lines']);

        $data['tenant_name']    = $tenant->name;
        $data['tenant_code']    = $tenant->tenant_code;
        $data['tenant_address'] = Invoice::resolveTenantAddress($tenant, $lines);
        $data['property_name']  = $lines[0]['property_name'];
        $data['unit']           = count($lines) === 1 ? ($lines[0]['unit'] ?? null) : null;

        $invoice->fill($data);
        $invoice->recomputeTotals();
        $invoice->save();

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} updated.");
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted.');
    }

    public function pdf(Invoice $invoice): Response
    {
        $invoice->load('payments', 'tenant');
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function pdfPreview(Invoice $invoice): Response
    {
        $invoice->load('payments', 'tenant');
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }
}
