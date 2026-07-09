<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceNoteRequest;
use App\Models\Invoice;
use App\Models\InvoiceNote;
use Illuminate\Http\RedirectResponse;

class InvoiceNoteController extends Controller
{
    public function store(StoreInvoiceNoteRequest $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validated();
        $data['note_number'] = InvoiceNote::generateNumber($data['type']);
        $data['invoice_id']  = $invoice->id;

        $note = InvoiceNote::create($data);
        $invoice->syncStatus();

        $label = $note->type === 'credit' ? 'Credit note' : 'Debit note';

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "{$label} {$note->note_number} issued successfully.");
    }

    public function destroy(Invoice $invoice, InvoiceNote $invoiceNote): RedirectResponse
    {
        $invoiceNote->delete();
        $invoice->syncStatus();

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Note removed.');
    }
}
