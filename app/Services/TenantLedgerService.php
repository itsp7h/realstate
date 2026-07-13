<?php

namespace App\Services;

use App\Models\EwaBill;
use App\Models\EwaPayment;
use App\Models\Invoice;
use App\Models\InvoiceNote;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the outstanding-bill ledger used by the Tenant Statement and
 * Ageing reports, plus the full-history running-balance ledger used by the
 * Tenant Ledger report. Draft implementation — see known gaps noted where relevant.
 */
class TenantLedgerService
{
    /**
     * Every outstanding (unpaid / partially paid) rent invoice and EWA bill,
     * plus any general credit/debit notes issued directly against the
     * tenant, within a date range — normalised to one shape and sorted
     * chronologically, with a running balance.
     */
    public function buildLedger(Tenant $tenant, Carbon $from, Carbon $to): Collection
    {
        // Fully settled bills (balance_due ~ 0, factoring in every payment
        // and note against them) are left out entirely — this is an
        // outstanding-bill statement, not a full transaction history. Bills
        // that still owe something are broken into their component lines
        // (bill, payments, notes) instead of one collapsed number, so it's
        // clear what actually reduced the balance.
        $activeInvoices = Invoice::where('tenant_id', $tenant->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->get()
            ->filter(fn (Invoice $invoice) => abs($invoice->balance_due) > 0.001);

        $invoiceRows = $activeInvoices->map(fn (Invoice $invoice) => [
            'date'           => $invoice->invoice_date,
            'bill_ref'       => $invoice->invoice_number,
            'description'    => $this->invoicePeriodLabel($invoice),
            'opening_amount' => (float) $invoice->total_incl_vat,
            'pending_amount' => (float) $invoice->total_incl_vat,
            'due_on'         => $invoice->invoice_date,
        ]);

        // EwaBill has no direct tenant_id column yet (only via leaseContract),
        // so bills whose lease contract link is missing won't show up here.
        $activeEwaBills = EwaBill::whereHas('leaseContract', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->whereBetween('reading_date', [$from, $to])
            ->get()
            ->filter(fn (EwaBill $bill) => abs($bill->balance_due) > 0.001);

        $ewaRows = $activeEwaBills->map(fn (EwaBill $bill) => [
            'date'           => $bill->reading_date ?? $bill->created_at,
            'bill_ref'       => $bill->bill_number,
            'description'    => 'EWA — ' . ($bill->billing_period ?: '—'),
            'opening_amount' => (float) $bill->effective_tenant_portion,
            'pending_amount' => (float) $bill->effective_tenant_portion,
            'due_on'         => $bill->due_date,
        ]);

        // Every payment against a still-outstanding invoice/EWA bill gets
        // its own visible line, reducing the running balance. Payments
        // belonging to an already-fully-settled bill are skipped along
        // with that bill, to avoid a lone payment line with no bill above it.
        $paymentRows = Payment::whereIn('invoice_id', $activeInvoices->pluck('id'))
            ->whereBetween('payment_date', [$from, $to])
            ->with('invoice:id,invoice_number')
            ->get()
            ->map(fn (Payment $payment) => [
                'date'           => $payment->payment_date,
                'bill_ref'       => $payment->payment_number,
                'description'    => "Payment — {$payment->method_label}" . ($payment->invoice ? " (Inv {$payment->invoice->invoice_number})" : ''),
                'opening_amount' => -(float) $payment->amount,
                'pending_amount' => -(float) $payment->amount,
                'due_on'         => $payment->payment_date,
            ]);

        $ewaPaymentRows = EwaPayment::whereIn('ewa_bill_id', $activeEwaBills->pluck('id'))
            ->whereBetween('payment_date', [$from, $to])
            ->with('ewaBill:id,bill_number')
            ->get()
            ->map(fn (EwaPayment $payment) => [
                'date'           => $payment->payment_date,
                'bill_ref'       => $payment->payment_number,
                'description'    => "Payment — {$payment->method_label}" . ($payment->ewaBill ? " (EWA {$payment->ewaBill->bill_number})" : ''),
                'opening_amount' => -(float) $payment->amount,
                'pending_amount' => -(float) $payment->amount,
                'due_on'         => $payment->payment_date,
            ]);

        // Every credit/debit note against this tenant gets its own visible
        // line — general notes always show, while invoice-scoped notes only
        // show if their invoice is still outstanding (same reasoning as
        // payments above). Credit reduces the balance (negative), debit
        // increases it (positive).
        $noteRows = InvoiceNote::where('tenant_id', $tenant->id)
            ->where(fn ($q) => $q->whereNull('invoice_id')->orWhereIn('invoice_id', $activeInvoices->pluck('id')))
            ->whereBetween('note_date', [$from, $to])
            ->with('invoice:id,invoice_number')
            ->get()
            ->map(fn (InvoiceNote $note) => [
                'date'           => $note->note_date,
                'bill_ref'       => $note->note_number,
                'description'    => $note->invoice
                    ? "{$note->type_label} — {$note->reason} (Inv {$note->invoice->invoice_number})"
                    : "{$note->type_label} — {$note->reason}",
                'opening_amount' => $note->type === 'credit' ? -(float) $note->amount : (float) $note->amount,
                'pending_amount' => $note->type === 'credit' ? -(float) $note->amount : (float) $note->amount,
                'due_on'         => $note->note_date,
            ]);

        return $invoiceRows->concat($ewaRows)->concat($paymentRows)->concat($ewaPaymentRows)->concat($noteRows)
            ->sortBy('date')
            ->values()
            ->map(function ($row) use ($to) {
                $dueOn = ($row['due_on'] instanceof Carbon ? $row['due_on'] : Carbon::parse($row['due_on']))->copy()->startOfDay();
                $asOf  = $to->copy()->startOfDay();

                $row['overdue_days'] = $asOf->greaterThan($dueOn) ? $dueOn->diffInDays($asOf) : 0;
                $row['bucket']       = $this->bucketFor($row['overdue_days']);

                return $row;
            });
    }

    public function runningBalance(Collection $rows): float
    {
        return round($rows->sum('pending_amount'), 3);
    }

    /**
     * Full transaction history for a tenant — every rent invoice, EWA bill,
     * payment, and credit/debit note in the date range, in chronological
     * order, each with a running balance after it. Unlike buildLedger(),
     * this never hides fully-settled bills: it's a real ledger (complete
     * history), not an outstanding-bill statement.
     */
    public function buildTransactionLedger(Tenant $tenant, Carbon $from, Carbon $to): Collection
    {
        $invoiceIds = Invoice::where('tenant_id', $tenant->id)->pluck('id', 'id');
        $ewaBillIds = EwaBill::whereHas('leaseContract', fn ($q) => $q->where('tenant_id', $tenant->id))->pluck('id', 'id');

        $invoiceRows = Invoice::where('tenant_id', $tenant->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->get()
            ->map(fn (Invoice $invoice) => [
                'date'        => $invoice->invoice_date,
                'bill_ref'    => $invoice->invoice_number,
                'description' => $this->invoicePeriodLabel($invoice),
                'debit'       => (float) $invoice->total_incl_vat,
                'credit'      => 0.0,
            ]);

        $ewaRows = EwaBill::whereHas('leaseContract', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->whereBetween('reading_date', [$from, $to])
            ->get()
            ->map(fn (EwaBill $bill) => [
                'date'        => $bill->reading_date ?? $bill->created_at,
                'bill_ref'    => $bill->bill_number,
                'description' => 'EWA — ' . ($bill->billing_period ?: '—'),
                'debit'       => (float) $bill->effective_tenant_portion,
                'credit'      => 0.0,
            ]);

        $paymentRows = Payment::whereIn('invoice_id', $invoiceIds)
            ->whereBetween('payment_date', [$from, $to])
            ->with('invoice:id,invoice_number')
            ->get()
            ->map(fn (Payment $payment) => [
                'date'        => $payment->payment_date,
                'bill_ref'    => $payment->payment_number,
                'description' => "Payment — {$payment->method_label}" . ($payment->invoice ? " (Inv {$payment->invoice->invoice_number})" : ''),
                'debit'       => 0.0,
                'credit'      => (float) $payment->amount,
            ]);

        $ewaPaymentRows = EwaPayment::whereIn('ewa_bill_id', $ewaBillIds)
            ->whereBetween('payment_date', [$from, $to])
            ->with('ewaBill:id,bill_number')
            ->get()
            ->map(fn (EwaPayment $payment) => [
                'date'        => $payment->payment_date,
                'bill_ref'    => $payment->payment_number,
                'description' => "Payment — {$payment->method_label}" . ($payment->ewaBill ? " (EWA {$payment->ewaBill->bill_number})" : ''),
                'debit'       => 0.0,
                'credit'      => (float) $payment->amount,
            ]);

        $noteRows = InvoiceNote::where('tenant_id', $tenant->id)
            ->whereBetween('note_date', [$from, $to])
            ->with('invoice:id,invoice_number')
            ->get()
            ->map(fn (InvoiceNote $note) => [
                'date'        => $note->note_date,
                'bill_ref'    => $note->note_number,
                'description' => $note->invoice
                    ? "{$note->type_label} — {$note->reason} (Inv {$note->invoice->invoice_number})"
                    : "{$note->type_label} — {$note->reason}",
                'debit'       => $note->type === 'debit' ? (float) $note->amount : 0.0,
                'credit'      => $note->type === 'credit' ? (float) $note->amount : 0.0,
            ]);

        $balance = 0.0;

        return $invoiceRows->concat($ewaRows)->concat($paymentRows)->concat($ewaPaymentRows)->concat($noteRows)
            ->sortBy('date')
            ->values()
            ->map(function ($row) use (&$balance) {
                $balance += $row['debit'] - $row['credit'];
                $row['balance'] = round($balance, 3);

                return $row;
            });
    }

    /**
     * One row per tenant with an outstanding balance in the range, bucketed
     * the same way as the per-tenant ageing report.
     */
    public function buildGroupOutstanding(Carbon $from, Carbon $to): Collection
    {
        return Tenant::orderBy('name')->get()
            ->map(function (Tenant $tenant) use ($from, $to) {
                $rows = $this->buildLedger($tenant, $from, $to);
                if ($rows->isEmpty()) {
                    return null;
                }

                return [
                    'tenant'   => $tenant,
                    'pending'  => $this->runningBalance($rows),
                    'lt60'     => round($rows->where('bucket', 'lt60')->sum('pending_amount'), 3),
                    'b60_120'  => round($rows->where('bucket', 'b60_120')->sum('pending_amount'), 3),
                    'gt120'    => round($rows->where('bucket', 'gt120')->sum('pending_amount'), 3),
                    // "On Account" (unallocated tenant credit) isn't modelled yet — always 0 for now.
                    'on_account' => 0.0,
                ];
            })
            ->filter()
            ->values();
    }

    private function bucketFor(int $overdueDays): string
    {
        if ($overdueDays < 60) return 'lt60';
        if ($overdueDays <= 120) return 'b60_120';
        return 'gt120';
    }

    private function invoicePeriodLabel(Invoice $invoice): string
    {
        $lines = $invoice->lines ?? [];
        $first = $lines[0] ?? null;

        if ($first && !empty($first['rental_period_start'])) {
            $start = Carbon::parse($first['rental_period_start'])->format('d-M-Y');
            $end   = !empty($first['rental_period_end']) ? Carbon::parse($first['rental_period_end'])->format('d-M-Y') : '';
            return "From {$start}" . ($end ? " To {$end}" : '');
        }

        return $invoice->description ?: $invoice->type_label;
    }
}
