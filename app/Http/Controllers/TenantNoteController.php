<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceNoteRequest;
use App\Models\InvoiceNote;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

class TenantNoteController extends Controller
{
    public function store(StoreInvoiceNoteRequest $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validated();
        $data['note_number'] = InvoiceNote::generateNumber($data['type']);
        $data['tenant_id']   = $tenant->id;
        $data['invoice_id']  = null;

        $note = InvoiceNote::create($data);

        $label = $note->type === 'credit' ? 'Credit note' : 'Debit note';

        return redirect()->route('tenants.show', ['tenant' => $tenant, 'tab' => 'notes'])
            ->with('success', "{$label} {$note->note_number} issued for {$tenant->name}.");
    }

    public function destroy(Tenant $tenant, InvoiceNote $invoiceNote): RedirectResponse
    {
        $invoiceNote->delete();

        return redirect()->route('tenants.show', ['tenant' => $tenant, 'tab' => 'notes'])
            ->with('success', 'Note removed.');
    }
}
