<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::with('invoice')->latest('payment_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('invoice', fn($iq) => $iq
                      ->where('tenant_name', 'like', "%{$search}%")
                      ->orWhere('invoice_number', 'like', "%{$search}%"));
            });
        }
        if ($method = $request->input('method')) {
            $query->where('method', $method);
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('payment_date', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('payment_date', '<=', $to);
        }

        $payments = $query->paginate(25)->withQueryString();

        $stats = [
            'total_collected' => Payment::sum('amount'),
            'count'           => Payment::count(),
            'this_month'      => Payment::whereYear('payment_date', now()->year)
                                        ->whereMonth('payment_date', now()->month)
                                        ->sum('amount'),
        ];

        return view('payments.index', compact('payments', 'stats'));
    }

    public function store(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validated();
        $data['payment_number'] = Payment::generateNumber();
        $data['invoice_id']     = $invoice->id;

        Payment::create($data);
        $invoice->syncStatus();

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Invoice $invoice, Payment $payment): RedirectResponse
    {
        $payment->delete();
        $invoice->syncStatus();

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Payment removed.');
    }

    public function receipt(Invoice $invoice, Payment $payment): Response
    {
        $payment->load('invoice.tenant', 'ewaBill');
        $pdf = Pdf::loadView('payments.receipt', compact('payment', 'invoice'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download("receipt-{$payment->payment_number}.pdf");
    }

    public function receiptPreview(Invoice $invoice, Payment $payment): Response
    {
        $payment->load('invoice.tenant', 'ewaBill');
        $pdf = Pdf::loadView('payments.receipt', compact('payment', 'invoice'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("receipt-{$payment->payment_number}.pdf");
    }
}
