<?php

namespace App\Services;

use App\Models\EwaBill;
use App\Models\Invoice;
use App\Models\InvoiceNote;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the outstanding-bill ledger used by the Tenant Statement and
 * Ageing reports. Draft implementation — see known gaps noted where relevant.
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
        // Notes are shown as their own line below, so the invoice's own row
        // must exclude their effect here — otherwise a note tied to an
        // invoice would be counted twice (once inside balance_due, once as
        // its own row).
        $invoiceRows = Invoice::where('tenant_id', $tenant->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->get()
            ->map(fn (Invoice $invoice) => [
                'date'           => $invoice->invoice_date,
                'bill_ref'       => $invoice->invoice_number,
                'description'    => $this->invoicePeriodLabel($invoice),
                'opening_amount' => (float) $invoice->total_incl_vat,
                'pending_amount' => (float) $invoice->total_incl_vat - $invoice->total_paid,
                'due_on'         => $invoice->invoice_date,
            ]);

        // EwaBill has no direct tenant_id column yet (only via leaseContract),
        // so bills whose lease contract link is missing won't show up here.
        $ewaRows = EwaBill::whereHas('leaseContract', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->whereBetween('reading_date', [$from, $to])
            ->get()
            ->map(fn (EwaBill $bill) => [
                'date'           => $bill->reading_date ?? $bill->created_at,
                'bill_ref'       => $bill->bill_number,
                'description'    => 'EWA — ' . ($bill->billing_period ?: '—'),
                'opening_amount' => (float) $bill->effective_tenant_portion,
                'pending_amount' => (float) $bill->balance_due,
                'due_on'         => $bill->due_date,
            ]);

        // Every credit/debit note against this tenant gets its own visible
        // line — whether issued generally or against a specific invoice
        // (the invoice row above no longer nets these in, to avoid double
        // counting). Credit reduces the balance (negative), debit increases
        // it (positive).
        $noteRows = InvoiceNote::where('tenant_id', $tenant->id)
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

        return $invoiceRows->concat($ewaRows)->concat($noteRows)
            ->filter(fn ($row) => abs($row['pending_amount']) > 0.001)
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
