<?php

namespace App\Services;

use App\Models\EwaPayment;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the Collection Report — every rent and EWA payment received within
 * a date range, normalised to one shape and sorted chronologically. Unlike
 * the ledger reports, this is a receipt-by-receipt cash log, not a
 * bill-by-bill balance statement.
 */
class CollectionReportService
{
    public function build(Carbon $from, Carbon $to): Collection
    {
        $rentRows = Payment::with('invoice.tenant')
            ->whereBetween('payment_date', [$from, $to])
            ->get()
            ->map(fn (Payment $payment) => [
                'receipt_no'    => $payment->payment_number,
                'date'          => $payment->payment_date,
                'cheque_number' => $payment->cheque_number,
                'cheque_date'   => $payment->cheque_date,
                'tenant_name'   => $payment->invoice?->tenant_name ?? $payment->invoice?->tenant?->name ?? '—',
                'particulars'   => 'Rent — Inv ' . ($payment->invoice?->invoice_number ?? '—'),
                'amount'        => (float) $payment->amount,
            ]);

        $ewaRows = EwaPayment::with('ewaBill.leaseContract.tenant')
            ->whereBetween('payment_date', [$from, $to])
            ->get()
            ->map(fn (EwaPayment $payment) => [
                'receipt_no'    => $payment->payment_number,
                'date'          => $payment->payment_date,
                'cheque_number' => $payment->cheque_number,
                'cheque_date'   => $payment->cheque_date,
                'tenant_name'   => $payment->ewaBill?->leaseContract?->tenant?->name ?? '—',
                'particulars'   => 'EWA — Bill ' . ($payment->ewaBill?->bill_number ?? '—'),
                'amount'        => (float) $payment->amount,
            ]);

        return $rentRows->concat($ewaRows)
            ->sortBy('date')
            ->values();
    }

    public function total(Collection $rows): float
    {
        return round((float) $rows->sum('amount'), 3);
    }
}
