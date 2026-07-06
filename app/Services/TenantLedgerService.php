<?php

namespace App\Services;

use App\Models\EwaBill;
use App\Models\Invoice;
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
     * Every outstanding (unpaid / partially paid) rent invoice and EWA bill
     * for a tenant within a date range, normalised to one shape and sorted
     * chronologically, with a running balance.
     */
    public function buildLedger(Tenant $tenant, Carbon $from, Carbon $to): Collection
    {
        $invoiceRows = Invoice::where('tenant_id', $tenant->id)
            ->whereBetween('invoice_date', [$from, $to])
            ->get()
            ->map(fn (Invoice $invoice) => [
                'date'           => $invoice->invoice_date,
                'bill_ref'       => $invoice->invoice_number,
                'description'    => $this->invoicePeriodLabel($invoice),
                'opening_amount' => (float) $invoice->total_incl_vat,
                'pending_amount' => (float) $invoice->balance_due,
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

        return $invoiceRows->concat($ewaRows)
            ->filter(fn ($row) => $row['pending_amount'] > 0.001)
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
