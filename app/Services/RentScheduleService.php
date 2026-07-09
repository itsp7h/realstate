<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a month-by-month rent payment schedule for a tenant: what was
 * expected, what was actually invoiced/paid, and whether each month was
 * never invoiced, unpaid, partially paid, or fully paid.
 */
class RentScheduleService
{
    public function build(Tenant $tenant, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $contracts = LeaseContract::where('tenant_id', $tenant->id)
            ->whereNotNull('rent_per_month')
            ->where('rent_per_month', '>', 0)
            ->get();

        if ($contracts->isEmpty()) {
            return collect();
        }

        $periods = $contracts->map(fn (LeaseContract $contract) => [
            'contract' => $contract,
            'start'    => ($contract->rent_start_date ?? $contract->lease_start_date)->copy()->startOfMonth(),
            'end'      => ($contract->rent_end_date ?? $contract->lease_end_date)->copy()->startOfMonth(),
        ]);

        $scheduleStart = $periods->min('start');
        $scheduleEnd   = $periods->max('end');

        if ($from && $from->copy()->startOfMonth()->greaterThan($scheduleStart)) {
            $scheduleStart = $from->copy()->startOfMonth();
        }
        if ($to && $to->copy()->startOfMonth()->lessThan($scheduleEnd)) {
            $scheduleEnd = $to->copy()->startOfMonth();
        }

        $currentMonth = Carbon::today()->startOfMonth();
        if ($scheduleEnd->greaterThan($currentMonth)) {
            $scheduleEnd = $currentMonth;
        }

        $rows   = collect();
        $cursor = $scheduleStart->copy();

        while ($cursor->lessThanOrEqualTo($scheduleEnd)) {
            $activePeriods = $periods->filter(fn ($p) => $cursor->between($p['start'], $p['end']));

            if ($activePeriods->isNotEmpty()) {
                $rows->push($this->buildMonthRow($tenant, $cursor->copy(), $activePeriods));
            }

            $cursor->addMonth();
        }

        return $rows;
    }

    private function buildMonthRow(Tenant $tenant, Carbon $month, Collection $activePeriods): array
    {
        $expected = $activePeriods->sum(fn ($p) => (float) $p['contract']->rent_per_month);

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->where('type', 'rent')
            ->whereYear('invoice_date', $month->year)
            ->whereMonth('invoice_date', $month->month)
            ->get();

        $invoiced  = (float) $invoices->sum('total_incl_vat');
        $paid      = (float) $invoices->sum('total_paid');
        $remaining = (float) $invoices->sum('balance_due');

        if ($invoices->isEmpty()) {
            $status    = 'not_invoiced';
            $remaining = round($expected, 3);
        } elseif ($paid <= 0.001) {
            $status = 'unpaid';
        } elseif ($remaining <= 0.001) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        return [
            'month'     => $month,
            'expected'  => round($expected, 3),
            'invoiced'  => round($invoiced, 3),
            'paid'      => round($paid, 3),
            'remaining' => round($remaining, 3),
            'status'    => $status,
            'invoices'  => $invoices,
        ];
    }
}
